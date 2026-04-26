<?php

namespace App\Services;

use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use App\Models\Cuota;
use App\Models\MoraCuota;
use App\Models\Operacion;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstadoPrestamoService
{
    /**
     * Recalcula completamente todos los estados de un préstamo
     */
    public function recalcularTodo(Prestamo $prestamo): array
    {
        return DB::transaction(function () use ($prestamo) {
            Log::info("Iniciando recálculo completo para préstamo {$prestamo->id}");

            $resultados = [
                'prestamo_id' => $prestamo->id,
                'cuotas_actualizadas' => 0,
                'moras_actualizadas' => 0,
                'estado_anterior' => $prestamo->estado,
                'estado_nuevo' => null,
                'cambios' => [],
            ];

            // 0. Limpiar relaciones incorrectas de operaciones con moras cuando el pago fue a tiempo
            $this->limpiarRelacionesIncorrectasMoras($prestamo);

            // 1. Recalcular todas las cuotas
            foreach ($prestamo->cuotas as $cuota) {
                $cambiosCuota = $this->recalcularCuota($cuota);
                if ($cambiosCuota['hubo_cambios']) {
                    $resultados['cuotas_actualizadas']++;
                    $resultados['cambios'][] = $cambiosCuota;
                }
            }

            // 2. Recalcular estado del préstamo
            $estadoAnterior = $prestamo->estado;
            $this->actualizarEstadoPrestamo($prestamo);
            $prestamo->refresh();

            $resultados['estado_nuevo'] = $prestamo->estado;

            if ($estadoAnterior !== $prestamo->estado) {
                Log::info("Estado del préstamo {$prestamo->id} cambió de '{$estadoAnterior}' a '{$prestamo->estado}'");
            }

            Log::info("Recálculo completo finalizado para préstamo {$prestamo->id}", $resultados);

            return $resultados;
        });
    }

    /**
     * Recalcula el estado de una cuota específica basándose en sus pagos
     */
    public function recalcularCuota(Cuota $cuota): array
    {
        Log::info("Recalculando cuota {$cuota->id} del préstamo {$cuota->prestamo_id}");

        $resultados = [
            'cuota_id' => $cuota->id,
            'estado_anterior' => $cuota->estado,
            'monto_pagado_anterior' => $cuota->monto_pagado,
            'estado_nuevo' => null,
            'monto_pagado_nuevo' => null,
            'moras_actualizadas' => 0,
            'hubo_cambios' => false,
        ];

        // 0. GESTIÓN INTELIGENTE DE MORAS (CRÍTICO PARA REVERSIONES)
        // Asegurar que las moras existan o se reactiven ANTES de calcular montos
        // Esto corrige el bug donde moras regularizadas no se reactivaban al anular pagos
        $this->gestionarMorasCuota($cuota);
        $cuota->refresh(); // Recargar relaciones (especialmente moras)

        // 1. Calcular monto total pagado desde operaciones
        $montoPagadoCalculado = $this->calcularMontoPagadoCuota($cuota);
        Log::info("💰 Cuota {$cuota->id} - Monto pagado: DB={$cuota->monto_pagado}, Calculado={$montoPagadoCalculado}");

        // 2. Recalcular moras de la cuota (respetando decisiones de gestión inteligente)
        foreach ($cuota->moras as $mora) {
            // Solo recalcular moras que NO fueron regularizadas por gestión inteligente
            if ($mora->estado !== MoraCuotaEstado::REGULARIZADA) {
                $cambiosMora = $this->recalcularMora($mora);
                if ($cambiosMora['hubo_cambios']) {
                    $resultados['moras_actualizadas']++;
                }
            } else {
                Log::info("⏭️ Mora {$mora->id} ya fue regularizada por gestión inteligente - saltando recálculo");
            }
        }

        // 3. Determinar nuevo estado basado en monto pagado y fecha
        $nuevoEstado = $this->determinarEstadoCuota($cuota, $montoPagadoCalculado);
        Log::info("🎯 Cuota {$cuota->id} - Estado: Actual={$cuota->estado->name}, Calculado={$nuevoEstado->name}");

        // 4. Actualizar si hay cambios
        if ($cuota->monto_pagado != $montoPagadoCalculado || $cuota->estado !== $nuevoEstado) {
            $cuota->update([
                'monto_pagado' => $montoPagadoCalculado,
                'estado' => $nuevoEstado,
            ]);

            $resultados['hubo_cambios'] = true;
            $resultados['estado_nuevo'] = $nuevoEstado;
            $resultados['monto_pagado_nuevo'] = $montoPagadoCalculado;

            Log::info("Cuota {$cuota->id} actualizada - Estado: {$resultados['estado_anterior']->name} → {$nuevoEstado->name}, Monto: {$resultados['monto_pagado_anterior']} → {$montoPagadoCalculado}");

            // 🤖 REGISTRAR EVENTO AUTOMÁTICO
            \App\Models\EventoAutomatico::registrar(
                $cuota->prestamo,
                'cuota_actualizada',
                'calculos',
                [
                    'cuota_id' => $cuota->id,
                    'estado_anterior' => $resultados['estado_anterior']->name,
                    'estado_nuevo' => $nuevoEstado->name,
                    'monto_pagado_anterior' => $resultados['monto_pagado_anterior'],
                    'monto_pagado_nuevo' => $montoPagadoCalculado,
                ],
                [
                    'cuota_id' => $cuota->id,
                    'estado_anterior' => $resultados['estado_anterior']->name,
                    'monto_pagado_anterior' => $resultados['monto_pagado_anterior'],
                ],
                null,
                'exitoso',
                $cuota
            );

        } else {
            Log::info("🔄 Cuota {$cuota->id} sin cambios - Estado: {$cuota->estado->name}, Monto: {$cuota->monto_pagado}");
        }

        return $resultados;
    }

    /**
     * Recalcula una mora específica
     */
    public function recalcularMora(MoraCuota $mora): array
    {
        Log::info("Recalculando mora {$mora->id} de cuota {$mora->cuota_id}");

        $resultados = [
            'mora_id' => $mora->id,
            'estado_anterior' => $mora->estado,
            'monto_pagado_anterior' => $mora->monto_pagado !== null ? $mora->monto_pagado : 0,
            'estado_nuevo' => null,
            'monto_pagado_nuevo' => null,
            'hubo_cambios' => false,
        ];

        // Calcular monto total pagado (puede incluir excedentes)
        $montoPagadoCalculado = $this->calcularMontoPagadoMora($mora);

        // 🎯 GESTIONAR EXCEDENTES (Abono a Favor)
        $this->gestionarExcedentesMora($mora, $montoPagadoCalculado);
        
        // Limitar el monto pagado para la mora al valor de la mora
        $montoPagadoReal = min($montoPagadoCalculado, $mora->monto);

        // Determinar nuevo estado con el monto real (cappeado)
        $nuevoEstado = $this->determinarEstadoMora($mora, $montoPagadoReal);

        // Actualizar si hay cambios
        if (abs($mora->monto_pagado - $montoPagadoReal) > 0.001 || $mora->estado !== $nuevoEstado) {
            $mora->update([
                'monto_pagado' => $montoPagadoReal,
                'estado' => $nuevoEstado,
            ]);

            $resultados['hubo_cambios'] = true;
            $resultados['estado_nuevo'] = $nuevoEstado;
            $resultados['monto_pagado_nuevo'] = $montoPagadoReal;

            Log::info("Mora {$mora->id} actualizada - Estado: {$resultados['estado_anterior']->name} → {$nuevoEstado->name}, Monto pagado: {$resultados['monto_pagado_anterior']} → {$montoPagadoReal} (Total Bruto: {$montoPagadoCalculado})");
        }

        return $resultados;
    }

    /**
     * Gestiona los excedentes de pago de una mora creando Abonos a Favor
     */
    private function gestionarExcedentesMora(MoraCuota $mora, float $totalPagado): void
    {
        if ($totalPagado <= $mora->monto) {
            return;
        }

        $excedenteTotal = $totalPagado - $mora->monto; // Excedente global
        
        // Obtener operaciones NO anuladas ordenadas cronológicamente
        $operaciones = $mora->operaciones()
            ->where('operaciones.estado', '!=', 'anulado')
            ->orderBy('operaciones.id', 'asc') // Cronológico
            ->get();
            
        $acumuladoPagado = 0;
        
        foreach ($operaciones as $op) {
            // Calcular cuánto de ESTA operación se usó legítimamente para la mora
            $inicioAplicacion = $acumuladoPagado;
            $finAplicacion = $acumuladoPagado + $op->abono;
            $acumuladoPagado += $op->abono;
            
            // Intersección con el rango válido [0, montoMora]
            $utilizadoEnMora = 0;
            if ($inicioAplicacion < $mora->monto) {
                $hasta = min($finAplicacion, $mora->monto);
                $utilizadoEnMora = max(0, $hasta - $inicioAplicacion);
            }
            
            // El resto es excedente de ESTA operación
            $excedenteOp = $op->abono - $utilizadoEnMora;
            
            if ($excedenteOp > 0.001) {
                // Crear o actualizar AbonoMoraFavor
                $abono = \App\Models\AbonoMoraFavor::updateOrCreate(
                    [
                        'cuota_id' => $mora->cuota_id,
                        'operacion_id' => $op->id,
                        'estado' => 'activo'
                    ],
                    [
                        'fecha_abono' => $op->fecha ?? now(),
                        'monto_abonado' => $excedenteOp,
                        // El saldo se calcula restando lo utilizado (si existe)
                    ]
                );
                
                // Actualizar saldo_favor y comentario
                $abono->saldo_favor = $abono->monto_abonado - $abono->monto_utilizado;
                $abono->comentario = "Generado por excedente de mora {$mora->id}";
                $abono->save();
                
                Log::info("💰 Excedente mora {$mora->id}: Op {$op->id} generó Abono a Favor de {$excedenteOp}");
            }
        }
    }

    /**
     * Reversa completamente los efectos de una operación
     * MEJORADO: Maneja anulaciones granulares y pagos combinados
     */
    public function reversarOperacion(Operacion $operacion): array
    {
        return DB::transaction(function () use ($operacion) {
            Log::info("🔄 Iniciando reversión granular de operación {$operacion->id} tipo '{$operacion->tipo_operacion}' monto {$operacion->abono}");

            $resultados = [
                'operacion_id' => $operacion->id,
                'tipo_operacion' => $operacion->tipo_operacion,
                'monto_anulado' => $operacion->abono,
                'cuotas_afectadas' => [],
                'moras_afectadas' => [],
                'prestamo_actualizado' => false,
                'cambios' => [],
            ];

            // 1. Procesar cuotas afectadas
            $cuotasAfectadas = $operacion->cuotas;
            foreach ($cuotasAfectadas as $cuota) {
                $estadoAnterior = $cuota->estado;
                $montoPagadoAnterior = $cuota->monto_pagado;

                // Remover relación
                $operacion->cuotas()->detach($cuota->id);
                
                // 🛑 ELIMINAR ABONOS A FAVOR GENERADOS POR ESTA OPERACIÓN EN ESTA CUOTA
                \App\Models\AbonoMoraFavor::where('operacion_id', $operacion->id)
                    ->where('cuota_id', $cuota->id)
                    ->delete();
                Log::info("🗑️ Abonos a favor de operación {$operacion->id} en cuota {$cuota->id} eliminados por reversión");

                // Recalcular sin esta operación
                $cambiosCuota = $this->recalcularCuota($cuota);

                $resultados['cuotas_afectadas'][] = $cuota->id;
                $resultados['cambios'][] = [
                    'tipo' => 'cuota',
                    'id' => $cuota->id,
                    'monto_pagado_anterior' => $montoPagadoAnterior,
                    'monto_pagado_nuevo' => $cuota->monto_pagado,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $cuota->estado,
                ];

                Log::info("📉 Cuota {$cuota->id} revertida - Estado: {$estadoAnterior->name} → {$cuota->estado->name}, Pagado: {$montoPagadoAnterior} → {$cuota->monto_pagado}");
            }

            // 2. Procesar moras afectadas
            $morasAfectadas = $operacion->morasCuota;
            $cuotasConMorasAfectadas = collect(); // Para evitar duplicados

            foreach ($morasAfectadas as $mora) {
                $estadoAnterior = $mora->estado;
                $montoPagadoAnterior = $mora->monto_pagado ?? 0;

                // Remover relación
                $operacion->morasCuota()->detach($mora->id);

                // 🛑 ELIMINAR ABONOS A FAVOR GENERADOS POR ESTA OPERACIÓN EN ESTA MORA (CUOTA)
                if ($mora->cuota_id) {
                    \App\Models\AbonoMoraFavor::where('operacion_id', $operacion->id)
                        ->where('cuota_id', $mora->cuota_id)
                        ->delete();
                }

                // Recalcular sin esta operación
                $cambiosMora = $this->recalcularMora($mora);

                $resultados['moras_afectadas'][] = $mora->id;
                $resultados['cambios'][] = [
                    'tipo' => 'mora',
                    'id' => $mora->id,
                    'monto_pagado_anterior' => $montoPagadoAnterior,
                    'monto_pagado_nuevo' => $mora->monto_pagado,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $mora->estado,
                ];

                // Agregar la cuota que contiene esta mora para recálculo posterior
                if ($mora->cuota && ! $cuotasConMorasAfectadas->has($mora->cuota->id)) {
                    $cuotasConMorasAfectadas->put($mora->cuota->id, $mora->cuota);
                }

                Log::info("📉 Mora {$mora->id} revertida - Estado: {$estadoAnterior->name} → {$mora->estado->name}, Pagado: {$montoPagadoAnterior} → {$mora->monto_pagado}");
            }

            // 2.1. Recalcular cuotas que tenían moras afectadas
            foreach ($cuotasConMorasAfectadas as $cuota) {
                if (! in_array($cuota->id, $resultados['cuotas_afectadas'])) {
                    $estadoAnteriorCuota = $cuota->estado;
                    $montoPagadoAnteriorCuota = $cuota->monto_pagado;

                    // Recalcular cuota (esto incluye recálculo de sus moras)
                    $cambiosCuota = $this->recalcularCuota($cuota);

                    if ($cambiosCuota['hubo_cambios']) {
                        $resultados['cuotas_afectadas'][] = $cuota->id;
                        $resultados['cambios'][] = [
                            'tipo' => 'cuota_por_moras',
                            'id' => $cuota->id,
                            'monto_pagado_anterior' => $montoPagadoAnteriorCuota,
                            'monto_pagado_nuevo' => $cuota->monto_pagado,
                            'estado_anterior' => $estadoAnteriorCuota,
                            'estado_nuevo' => $cuota->estado,
                        ];

                        Log::info("📉 Cuota {$cuota->id} recalculada por moras revertidas - Estado: {$estadoAnteriorCuota->name} → {$cuota->estado->name}, Pagado: {$montoPagadoAnteriorCuota} → {$cuota->monto_pagado}");
                    }
                }
            }

            // 3. Actualizar estado del préstamo
            if ($operacion->prestamo) {
                $this->actualizarEstadoPrestamo($operacion->prestamo);
                $resultados['prestamo_actualizado'] = true;
            }

            Log::info('✅ Reversión granular completada', [
                'operacion_id' => $operacion->id,
                'cuotas_revertidas' => count($cuotasAfectadas),
                'moras_revertidas' => count($morasAfectadas),
                'monto_total_anulado' => $operacion->abono,
            ]);

            return $resultados;
        });
    }

    /**
     * Anula una operación específica y recalcula estados
     */
    public function anularOperacion(Operacion $operacion, string $justificacion, int $usuarioId): array
    {
        return DB::transaction(function () use ($operacion, $justificacion, $usuarioId) {
            Log::info("🚫 Iniciando anulación de operación {$operacion->id}");

            // 1. Reversar efectos de la operación
            $resultadoReversion = $this->reversarOperacion($operacion);

            // 2. Marcar operación como anulada
            $operacion->update([
                'estado' => 'anulado',
                'justificacion_anulacion' => $justificacion,
                'anulado_por' => $usuarioId,
                'anulado_en' => now(),
            ]);

            $resultadoReversion['operacion_anulada'] = true;
            $resultadoReversion['anulado_por'] = $usuarioId;
            $resultadoReversion['justificacion'] = $justificacion;

            Log::info("✅ Operación {$operacion->id} anulada correctamente");

            return $resultadoReversion;
        });
    }

    /**
     * Calcula el monto total pagado de una cuota desde sus operaciones
     */
    private function calcularMontoPagadoCuota(Cuota $cuota): float
    {
        return $cuota->operaciones()
            ->where('operaciones.estado', '!=', 'anulado')
            ->sum('operaciones.abono');
    }

    /**
     * Calcula el monto pagado de una mora desde sus operaciones
     * CORREGIDO: Distribución secuencial para completar moras en orden
     */
    private function calcularMontoPagadoMora(MoraCuota $mora): float
    {
        $operaciones = $mora->operaciones()
            ->where('operaciones.estado', '!=', 'anulado')
            ->get();

        $totalPagado = 0;

        foreach ($operaciones as $operacion) {
            // Obtener todas las moras que esta operación está pagando, ordenadas por ID
            $morasEnOperacion = $operacion->morasCuota()->orderBy('id')->get();

            if ($morasEnOperacion->count() == 1) {
                // Si la operación solo paga esta mora, usa el abono completo
                $totalPagado += $operacion->abono;
            } else {
                // Distribución secuencial: completar moras en orden hasta agotar el monto
                $montoRestante = $operacion->abono;
                $montoAsignado = 0;

                foreach ($morasEnOperacion as $moraActual) {
                    if ($montoRestante <= 0) {
                        break;
                    }

                    $montoNecesario = $moraActual->monto;
                    $montoPagar = min($montoNecesario, $montoRestante);

                    if ($moraActual->id == $mora->id) {
                        $montoAsignado = $montoPagar;
                    }

                    $montoRestante -= $montoPagar;
                }

                $totalPagado += $montoAsignado;
                Log::info("Mora {$mora->id}: Operación {$operacion->id} distribuida secuencialmente = {$montoAsignado}");
            }
        }

        return round($totalPagado, 2);
    }

    /**
     * Determina el estado correcto de una cuota basándose en pagos, moras y fecha
     */
    private function determinarEstadoCuota(Cuota $cuota, float $montoPagado): CuotaEstado
    {
        // 1. Si la cuota no está pagada completamente
        if ($montoPagado < $cuota->monto) {
            // Si tiene pagos parciales
            if ($montoPagado > 0) {
                return CuotaEstado::PARCIAL;
            }

            // Si no tiene pagos y ya venció
            if ($cuota->fecha_pago && Carbon::parse($cuota->fecha_pago)->isPast()) {
                return CuotaEstado::VENCIDO;
            }

            // Por defecto, pendiente
            return CuotaEstado::PENDIENTE;
        }

        // 2. Si la cuota está completamente pagada
        // NOTA: El estado de la cuota depende ÚNICAMENTE del monto principal
        // Las moras son un concepto separado y NO afectan el estado PAGADO/PARCIAL
        return CuotaEstado::PAGADO;
    }

    /**
     * Determina el estado correcto de una mora basándose en pagos
     */
    private function determinarEstadoMora(MoraCuota $mora, float $montoPagado): MoraCuotaEstado
    {
        // Si ya está regularizada, mantenerla regularizada (no cambiar por pagos)
        if ($mora->estado === MoraCuotaEstado::REGULARIZADA->value) {
            return MoraCuotaEstado::REGULARIZADA;
        }

        // Si está completamente pagada
        if ($montoPagado >= $mora->monto) {
            return MoraCuotaEstado::PAGADO;
        }

        // Si tiene pagos parciales
        if ($montoPagado > 0) {
            return MoraCuotaEstado::PARCIAL;
        }

        // Por defecto, pendiente
        return MoraCuotaEstado::PENDIENTE;
    }

    /**
     * Actualiza el estado de un préstamo basándose en sus cuotas
     */
    private function actualizarEstadoPrestamo(Prestamo $prestamo): void
    {
        // No cambiar si ya está finalizado
        if ($prestamo->estado === 'Finalizado') {
            return;
        }

        $cuotas = $prestamo->cuotas()->get();

        if ($cuotas->isEmpty()) {
            return;
        }

        // Verificar si todas las cuotas están pagadas
        $todasPagadas = $cuotas->every(function ($cuota) {
            return $cuota->estado === CuotaEstado::PAGADO;
        });

        if ($todasPagadas) {
            // Verificar si hay moras pendientes antes de finalizar
            $hayMorasPendientes = $prestamo->cuotas()
                ->whereHas('moras', function ($query) {
                    $query->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL]);
                })->exists();

            if (! $hayMorasPendientes) {
                // Solo finalizar si NO hay moras pendientes
                $prestamo->update(['estado' => 'Finalizado']);

                return;
            }
            // Si hay moras pendientes, continuar para evaluar como moroso
        }

        // Verificar si hay cuotas vencidas
        $hayVencidas = $cuotas->some(function ($cuota) {
            return $cuota->estado === CuotaEstado::VENCIDO ||
                   ($cuota->fecha_pago && Carbon::parse($cuota->fecha_pago)->isPast() &&
                    in_array($cuota->estado, [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL]));
        });

        // Verificar si hay moras pendientes
        $hayMorasPendientes = $prestamo->cuotas()
            ->whereHas('moras', function ($query) {
                $query->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL]);
            })->exists();

        if ($hayVencidas || $hayMorasPendientes) {
            $prestamo->update(['estado' => 'Moroso']);
        } else {
            // Verificar si hay pagos realizados
            $hayPagos = $cuotas->some(function ($cuota) {
                return $cuota->estado === CuotaEstado::PARCIAL || $cuota->estado === CuotaEstado::PAGADO;
            });

            if ($hayPagos) {
                $prestamo->update(['estado' => 'Vigente']);
            }
        }
    }

    /**
     * Valida la integridad de un préstamo
     */
    public function validarIntegridad(Prestamo $prestamo): array
    {
        $errores = [];
        $advertencias = [];

        // Validar cuotas
        foreach ($prestamo->cuotas as $cuota) {
            $montoPagadoCalculado = $this->calcularMontoPagadoCuota($cuota);

            if (abs($cuota->monto_pagado - $montoPagadoCalculado) > 0.01) {
                $errores[] = "Cuota {$cuota->id}: monto_pagado inconsistente (DB: {$cuota->monto_pagado}, Calculado: {$montoPagadoCalculado})";
            }

            $estadoCalculado = $this->determinarEstadoCuota($cuota, $montoPagadoCalculado);
            if ($cuota->estado !== $estadoCalculado) {
                $advertencias[] = "Cuota {$cuota->id}: estado inconsistente (Actual: {$cuota->estado->name}, Debería ser: {$estadoCalculado->name})";
            }

            // Validar moras de la cuota
            foreach ($cuota->moras as $mora) {
                $montoPagadoMora = $this->calcularMontoPagadoMora($mora);
                $montoPagadoDB = $mora->monto_pagado !== null ? $mora->monto_pagado : 0;
                if (abs($montoPagadoDB - $montoPagadoMora) > 0.01) {
                    $errores[] = "Mora {$mora->id}: monto_pagado inconsistente (DB: ".$montoPagadoDB.", Calculado: {$montoPagadoMora})";
                }
            }
        }

        return [
            'prestamo_id' => $prestamo->id,
            'valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias,
            'total_errores' => count($errores),
            'total_advertencias' => count($advertencias),
        ];
    }

    /**
     * Gestión inteligente de moras basada en fechas de pago reales
     * Crea, elimina o regulariza moras según corresponda
     */
    private function limpiarRelacionesIncorrectasMoras(Prestamo $prestamo): void
    {
        Log::info("🧹 Iniciando gestión inteligente de moras para préstamo {$prestamo->id}");

        // Procesar cada cuota del préstamo
        foreach ($prestamo->cuotas as $cuota) {
            $this->gestionarMorasCuota($cuota);
        }

        Log::info("✅ Gestión inteligente de moras completada para préstamo {$prestamo->id}");
    }

    /**
     * Gestiona las moras de una cuota específica según su fecha Y monto de pago real
     * MEJORADO: Considera tanto fecha como monto para decisiones inteligentes
     */
    private function gestionarMorasCuota(Cuota $cuota): void
    {
        $fechaVencimiento = Carbon::parse($cuota->fecha_pago);
        $fechaActual = Carbon::now();

        // Obtener TODOS los pagos de la cuota para análisis de monto total
        $pagosOperaciones = $cuota->operaciones()
            ->where('tipo_operacion', 'Pago de cuota')
            ->where('estado', '!=', 'anulado')
            ->orderBy('fecha', 'asc')
            ->get();

        if ($pagosOperaciones->isEmpty()) {
            // Sin pagos - distinguir entre cuota vencida vs no vencida
            if ($fechaActual->lte($fechaVencimiento)) {
                // Cuota NO vencida sin pagos: eliminar moras incorrectas
                Log::info("🚫 Cuota {$cuota->id} sin pagos NO VENCIDA - regularizando moras incorrectas");
                $this->eliminarMorasIncorrectas($cuota);
            } else {
                // Cuota VENCIDA sin pagos: debe tener moras pendientes
                $diasVencida = $fechaVencimiento->diffInDays($fechaActual, false);
                $diasMoraParaGenerar = min($diasVencida, 7); // Límite de 7 moras

                Log::info("⏰ Cuota {$cuota->id} sin pagos VENCIDA ({$diasVencida} días) - generando {$diasMoraParaGenerar} moras");

                // Reactivar moras existentes que estén regularizadas incorrectamente
                $this->reactivarMorasVencidas($cuota);

                // Generar moras faltantes si es necesario
                $this->generarMorasParaCuotaVencida($cuota, $fechaVencimiento, $diasMoraParaGenerar);
            }

            return;
        }

        // Análisis de FECHA: usar el primer pago (más crítico para moras)
        $primerPago = $pagosOperaciones->first();
        $fechaPago = Carbon::parse($primerPago->fecha);
        $diasTarde = $fechaVencimiento->diffInDays($fechaPago, false);

        // Análisis de MONTO: sumar todos los pagos de la cuota
        $montoTotalPagado = $pagosOperaciones->sum('abono');
        $montoCuota = $cuota->monto;
        $saldoPendiente = max(0, $montoCuota - $montoTotalPagado);

        Log::info("📅💰 Cuota {$cuota->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pagada {$fechaPago->format('Y-m-d')}, Días tarde: {$diasTarde}, Monto: {$montoTotalPagado}/{$montoCuota}, Saldo: {$saldoPendiente}");

        // DECISIÓN INTELIGENTE basada en FECHA + MONTO
        if ($diasTarde <= 0 && $saldoPendiente == 0) {
            // PAGO A TIEMPO Y COMPLETO - no debería tener moras
            $this->procesarPagoCompletoATiempo($cuota);
        } elseif ($diasTarde <= 0 && $saldoPendiente > 0) {
            // PAGO A TIEMPO PERO PARCIAL - generar moras por saldo pendiente
            $this->procesarPagoParcialATiempo($cuota, $saldoPendiente);
        } elseif ($diasTarde > 0 && $saldoPendiente == 0) {
            // PAGO TARDÍO PERO COMPLETO - moras por días de tardanza
            $this->procesarPagoCompletoTardio($cuota, $diasTarde, $fechaVencimiento, $fechaPago);
        } else {
            // PAGO TARDÍO Y PARCIAL - moras por tardanza + saldo
            $this->procesarPagoParcialTardio($cuota, $diasTarde, $saldoPendiente, $fechaVencimiento, $fechaPago);
        }
    }

    /**
     * CASO 1: Pago COMPLETO a TIEMPO - no debería tener moras
     */
    private function procesarPagoCompletoATiempo(Cuota $cuota): void
    {
        $morasIncorrectas = $cuota->moras()->whereIn('estado', [
            MoraCuotaEstado::PENDIENTE,
            MoraCuotaEstado::PARCIAL,
        ])->get();

        if ($morasIncorrectas->count() > 0) {
            Log::info("✅🚫 Cuota {$cuota->id} pagada COMPLETA A TIEMPO - regularizando {$morasIncorrectas->count()} moras incorrectas");

            foreach ($morasIncorrectas as $mora) {
                $mora->operaciones()->detach();
                $mora->update([
                    'estado' => MoraCuotaEstado::REGULARIZADA,
                    'monto_pagado' => 0,
                ]);

                Log::info("✅ Mora {$mora->id} regularizada - pago completo a tiempo");
            }
        }
    }

    /**
     * CASO 2: Pago PARCIAL a TIEMPO - generar moras por saldo pendiente según fecha actual
     */
    private function procesarPagoParcialATiempo(Cuota $cuota, float $saldoPendiente): void
    {
        $fechaActual = now();
        $fechaVencimiento = Carbon::parse($cuota->fecha_pago);

        // Si ya pasó la fecha de vencimiento, generar moras por días transcurridos
        if ($fechaActual->gt($fechaVencimiento)) {
            $diasVencidos = $fechaVencimiento->diffInDays($fechaActual, false);
            $diasMoraCrear = min($diasVencidos, 7); // Máximo 7 días

            Log::info("⚠️💰 Cuota {$cuota->id} pagada PARCIAL A TIEMPO pero con saldo S/{$saldoPendiente} - generando {$diasMoraCrear} moras por días vencidos");

            $this->crearMorasParaSaldoPendiente($cuota, $diasMoraCrear, $fechaVencimiento);
        } else {
            Log::info("✅💰 Cuota {$cuota->id} pagada PARCIAL A TIEMPO con saldo S/{$saldoPendiente} - aún no vencida, sin moras");
            // Regularizar moras existentes si la cuota aún no vence
            $this->regularizarMorasExistentes($cuota, 'pago_parcial_sin_vencer');
        }
    }

    /**
     * CASO 3: Pago COMPLETO TARDÍO - moras por días de tardanza
     */
    private function procesarPagoCompletoTardio(Cuota $cuota, int $diasTarde, Carbon $fechaVencimiento, Carbon $fechaPago): void
    {
        $diasMoraCrear = min($diasTarde, 7); // Máximo 7 días

        Log::info("⏰✅ Cuota {$cuota->id} pagada COMPLETA TARDÍA ({$diasTarde} días) - generando {$diasMoraCrear} moras");

        // Crear/verificar moras por días de tardanza
        $fechaActualMora = $fechaVencimiento->copy()->addDay();

        for ($dia = 1; $dia <= $diasMoraCrear; $dia++) {
            $moraExistente = $cuota->moras()->where('fecha', $fechaActualMora->format('Y-m-d'))->first();

            if (! $moraExistente) {
                $this->crearMoraCuota($cuota, $fechaActualMora->copy());
            }

            $fechaActualMora->addDay();
        }

        // Regularizar moras posteriores al pago
        $this->regularizarMorasPosteriores($cuota, $fechaPago);
    }

    /**
     * CASO 4: Pago PARCIAL TARDÍO - moras por tardanza + saldo adicional
     */
    private function procesarPagoParcialTardio(Cuota $cuota, int $diasTarde, float $saldoPendiente, Carbon $fechaVencimiento, Carbon $fechaPago): void
    {
        $diasMoraCrear = min($diasTarde, 7); // Máximo 7 días por tardanza

        Log::info("⏰⚠️ Cuota {$cuota->id} pagada PARCIAL TARDÍA ({$diasTarde} días, saldo S/{$saldoPendiente}) - generando {$diasMoraCrear} moras base + moras adicionales");

        // Crear moras por días de tardanza del pago realizado
        $fechaActualMora = $fechaVencimiento->copy()->addDay();

        for ($dia = 1; $dia <= $diasMoraCrear; $dia++) {
            $moraExistente = $cuota->moras()->where('fecha', $fechaActualMora->format('Y-m-d'))->first();

            if (! $moraExistente) {
                $this->crearMoraCuota($cuota, $fechaActualMora->copy());
            }

            $fechaActualMora->addDay();
        }

        // Crear moras adicionales por saldo pendiente hasta fecha actual (si aplica)
        $fechaActual = now();
        if ($fechaActual->gt($fechaPago)) {
            $diasAdicionales = $fechaPago->diffInDays($fechaActual, false);
            $diasAdicionalesLimitados = min($diasAdicionales, 7 - $diasMoraCrear); // Respetando límite total

            if ($diasAdicionalesLimitados > 0) {
                Log::info("➕ Generando {$diasAdicionalesLimitados} moras adicionales por saldo pendiente");
                $this->crearMorasParaSaldoPendiente($cuota, $diasAdicionalesLimitados, $fechaPago);
            }
        }
    }

    /**
     * Elimina moras de cuotas sin pagos que no deberían tenerlas
     */
    private function eliminarMorasIncorrectas(Cuota $cuota): void
    {
        $morasIncorrectas = $cuota->moras()->whereIn('estado', [
            MoraCuotaEstado::PENDIENTE,
            MoraCuotaEstado::PARCIAL,
        ])->get();

        if ($morasIncorrectas->count() > 0) {
            Log::info("🚫 Cuota {$cuota->id} sin pagos - regularizando {$morasIncorrectas->count()} moras");

            foreach ($morasIncorrectas as $mora) {
                $mora->operaciones()->detach();
                $mora->update([
                    'estado' => MoraCuotaEstado::REGULARIZADA,
                    'monto_pagado' => 0,
                ]);
            }
        }
    }

    /**
     * Crea moras para saldo pendiente desde una fecha base
     * MEJORADO: Reactiva moras existentes y crea faltantes
     */
    private function crearMorasParaSaldoPendiente(Cuota $cuota, int $diasMora, Carbon $fechaBase): void
    {
        $fechaActualMora = $fechaBase->copy()->addDay();
        $morasCreadas = 0;
        $morasReactivadas = 0;

        for ($dia = 1; $dia <= $diasMora; $dia++) {
            $moraExistente = $cuota->moras()->where('fecha', $fechaActualMora->format('Y-m-d'))->first();

            if ($moraExistente) {
                // REACTIVAR mora existente si está regularizada
                if ($moraExistente->estado === MoraCuotaEstado::REGULARIZADA) {
                    $moraExistente->update([
                        'estado' => MoraCuotaEstado::PENDIENTE,
                        'monto_pagado' => 0,
                    ]);
                    $morasReactivadas++;
                    Log::info("🔄 Mora {$moraExistente->id} reactivada para cuota {$cuota->id} - fecha {$fechaActualMora->format('Y-m-d')}");
                }
            } else {
                // CREAR nueva mora
                $this->crearMoraCuota($cuota, $fechaActualMora->copy());
                $morasCreadas++;
            }

            $fechaActualMora->addDay();
        }

        if ($morasCreadas > 0 || $morasReactivadas > 0) {
            Log::info("📊 Cuota {$cuota->id} - Moras: {$morasCreadas} creadas, {$morasReactivadas} reactivadas");
        }
    }

    /**
     * Regulariza moras existentes con un motivo específico
     */
    private function regularizarMorasExistentes(Cuota $cuota, string $motivo): void
    {
        $morasExistentes = $cuota->moras()->whereIn('estado', [
            MoraCuotaEstado::PENDIENTE,
            MoraCuotaEstado::PARCIAL,
        ])->get();

        foreach ($morasExistentes as $mora) {
            $mora->operaciones()->detach();
            $mora->update([
                'estado' => MoraCuotaEstado::REGULARIZADA,
                'monto_pagado' => 0,
            ]);

            Log::info("✅ Mora {$mora->id} regularizada por: {$motivo}");
        }
    }

    /**
     * Regulariza moras posteriores a una fecha de pago
     */
    private function regularizarMorasPosteriores(Cuota $cuota, Carbon $fechaPago): void
    {
        $morasPosteriores = $cuota->moras()
            ->where('fecha', '>', $fechaPago->format('Y-m-d'))
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
            ->get();

        foreach ($morasPosteriores as $mora) {
            Log::info("🚫 Regularizando mora posterior {$mora->id} - fecha {$mora->fecha} > pago {$fechaPago->format('Y-m-d')}");
            $mora->operaciones()->detach();
            $mora->update([
                'estado' => MoraCuotaEstado::REGULARIZADA,
                'monto_pagado' => 0,
            ]);
        }
    }

    /**
     * Crea una mora para una cuota en una fecha específica
     */
    private function crearMoraCuota(Cuota $cuota, Carbon $fecha): void
    {
        MoraCuota::create([
            'cuota_id' => $cuota->id,
            'fecha' => $fecha->format('Y-m-d'),
            'monto' => 4.0, // Monto por defecto
            'estado' => MoraCuotaEstado::PENDIENTE,
            'monto_pagado' => 0,
        ]);

        Log::info("➕ Mora creada para cuota {$cuota->id} en fecha {$fecha->format('Y-m-d')}");
    }

    /**
     * Reactiva moras que fueron regularizadas incorrectamente para cuotas vencidas sin pagos
     */
    private function reactivarMorasVencidas(Cuota $cuota): void
    {
        $morasRegularizadas = $cuota->moras()
            ->where('estado', MoraCuotaEstado::REGULARIZADA)
            ->get();

        foreach ($morasRegularizadas as $mora) {
            $mora->update([
                'estado' => MoraCuotaEstado::PENDIENTE,
                'monto_pagado' => 0, // Resetear pagos porque la operación fue anulada
            ]);

            Log::info("🔄 Mora reactivada: Cuota {$cuota->id}, Mora {$mora->id}, Fecha {$mora->fecha} - de REGULARIZADA a PENDIENTE");
        }
    }

    /**
     * Genera moras faltantes para una cuota vencida sin pagos
     */
    private function generarMorasParaCuotaVencida(Cuota $cuota, Carbon $fechaVencimiento, int $diasMora): void
    {
        $fechaActualMora = $fechaVencimiento->copy()->addDay();
        $morasCreadas = 0;

        for ($dia = 1; $dia <= $diasMora; $dia++) {
            $moraExistente = $cuota->moras()->where('fecha', $fechaActualMora->format('Y-m-d'))->first();

            if (! $moraExistente) {
                $this->crearMoraCuota($cuota, $fechaActualMora->copy());
                $morasCreadas++;
            }

            $fechaActualMora->addDay();
        }

        if ($morasCreadas > 0) {
            Log::info("➕ {$morasCreadas} moras creadas para cuota vencida {$cuota->id}");
        }
    }
}
