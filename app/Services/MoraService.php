<?php

namespace App\Services;

use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use App\Models\Cuota;
use App\Models\MoraCuota;
use App\Models\MoraHistory;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoraService
{
    const MAX_MORAS_POR_CUOTA = 7;

    /**
     * Genera moras masivas para todas las cuotas vencidas
     */
    public function generarMorasMasivas(): array
    {
        $resultados = [
            'procesadas' => 0,
            'generadas' => 0,
            'omitidas' => 0,
            'errores' => 0,
            'detalles' => [],
        ];

        try {
            DB::beginTransaction();

            // Obtener todas las cuotas vencidas con estado PENDIENTE o PARCIAL
            $cuotasVencidas = $this->obtenerCuotasVencidas();

            Log::info("Iniciando generación masiva de moras para {$cuotasVencidas->count()} cuotas vencidas");

            foreach ($cuotasVencidas as $cuota) {
                $resultados['procesadas']++;

                try {
                    $resultado = $this->procesarCuotaParaMoras($cuota);

                    if ($resultado['generadas'] > 0) {
                        $resultados['generadas'] += $resultado['generadas'];
                        $resultados['detalles'][] = [
                            'cuota_id' => $cuota->id,
                            'prestamo_id' => $cuota->prestamo_id,
                            'moras_generadas' => $resultado['generadas'],
                            'dias_vencidos' => $resultado['dias_vencidos'],
                        ];
                    } else {
                        $resultados['omitidas']++;
                    }

                } catch (\Exception $e) {
                    $resultados['errores']++;
                    Log::error("Error procesando cuota {$cuota->id}: ".$e->getMessage());
                    $resultados['detalles'][] = [
                        'cuota_id' => $cuota->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Actualizar el estado de los préstamos que ahora tienen moras
            $this->actualizarEstadoPrestamos();

            DB::commit();

            // TODO: Registrar en historial masivo (requiere tabla separada)
            // $this->registrarHistorialMasivo($resultados);

            Log::info('Generación masiva de moras completada', $resultados);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en generación masiva de moras: '.$e->getMessage());
            $resultados['errores']++;
            throw $e;
        }

        return $resultados;
    }

    /**
     * Procesa una cuota individual para generar moras
     */
    public function procesarCuotaParaMoras(Cuota $cuota): array
    {
        $hoy = Carbon::today();
        $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();

        // Solo procesar cuotas vencidas
        if ($fechaVencimiento >= $hoy) {
            return ['generadas' => 0, 'dias_vencidos' => 0, 'mensaje' => 'Cuota no vencida'];
        }

        // Solo procesar cuotas pendientes o parciales
        if (! in_array($cuota->estado, [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL])) {
            return ['generadas' => 0, 'dias_vencidos' => 0, 'mensaje' => 'Cuota ya pagada'];
        }

        $diasVencidos = $fechaVencimiento->diffInDays($hoy);
        // CORRECCIÓN: Para cuotas parciales, contar solo moras realmente pendientes (no pagadas)
        // Esto permite generar las 7 moras completas independientemente de pagos anteriores
        $morasRealmentePendientes = $cuota->moras()
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
            ->count();

        // Verificar límite máximo de moras basado en moras realmente pendientes
        if ($morasRealmentePendientes >= self::MAX_MORAS_POR_CUOTA) {
            return ['generadas' => 0, 'dias_vencidos' => $diasVencidos, 'mensaje' => 'Límite máximo de moras alcanzado'];
        }

        // Calcular cuántas moras faltan por generar (basado en moras realmente pendientes)
        $morasParaGenerar = min(self::MAX_MORAS_POR_CUOTA - $morasRealmentePendientes, $diasVencidos - $morasRealmentePendientes);

        if ($morasParaGenerar <= 0) {
            return ['generadas' => 0, 'dias_vencidos' => $diasVencidos, 'mensaje' => 'No hay moras pendientes por generar'];
        }

        $morasGeneradas = 0;

        // Generar las moras faltantes
        for ($i = 0; $i < $morasParaGenerar; $i++) {
            $diaMora = $morasRealmentePendientes + $i + 1;
            $fechaMora = $fechaVencimiento->copy()->addDays($diaMora);

            // No generar moras futuras
            if ($fechaMora > $hoy) {
                break;
            }

            $montoMora = $this->calcularMontoMora($cuota);

            $mora = MoraCuota::create([
                'cuota_id' => $cuota->id,
                'fecha' => $fechaMora,
                'dias_mora' => $diaMora,
                'monto' => $montoMora,
                'estado' => MoraCuotaEstado::PENDIENTE,
            ]);

            $morasGeneradas++;

            Log::info("Mora generada: Cuota {$cuota->id}, Día {$diaMora}, Monto {$montoMora}");

            // 🎯 APLICAR ABONOS A FAVOR AUTOMÁTICAMENTE
            try {
                $montoAplicadoFavor = $cuota->aplicarAbonosFavorAMora($mora);
                if ($montoAplicadoFavor > 0) {
                    // Recargar la mora para obtener el estado actualizado
                    $mora->refresh();
                    Log::info("💰 Abono a favor aplicado: S/{$montoAplicadoFavor} a mora {$mora->id}, nuevo estado: {$mora->estado->name}");
                }
            } catch (\Exception $e) {
                Log::error("Error aplicando abono a favor a mora {$mora->id}: ".$e->getMessage());
            }
        }

        // Actualizar cantidad_mora en la cuota (considerando moras realmente pendientes después de aplicar abonos a favor)
        if ($morasGeneradas > 0) {
            $totalMoras = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                ->sum(\DB::raw('COALESCE(monto - monto_pagado, monto)'));
            $cuota->update(['cantidad_mora' => $totalMoras]);
        }

        return [
            'generadas' => $morasGeneradas,
            'dias_vencidos' => $diasVencidos,
            'mensaje' => "Se generaron {$morasGeneradas} moras",
        ];
    }

    /**
     * Obtiene todas las cuotas vencidas que requieren procesamiento
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function obtenerCuotasVencidas()
    {
        $hoy = Carbon::today();

        return Cuota::with(['prestamo', 'moras'])
            ->whereIn('estado', [CuotaEstado::PENDIENTE->value, CuotaEstado::PARCIAL->value])
            ->where('fecha_pago', '<', $hoy)
            ->whereHas('prestamo', function ($query) {
                $query->whereIn('estado', ['Vigente', 'Moroso']);
            })
            ->get();
    }

    /**
     * Calcula el monto de mora para una cuota usando la mora del préstamo
     */
    private function calcularMontoMora(Cuota $cuota): float
    {
        // Usar la mora específica del préstamo (configurada al crear el préstamo)
        $prestamo = $cuota->prestamo;
        $moraPrestamo = $prestamo->mora ?? 4.00; // Default 4.00 S/. si no está configurada

        return round($moraPrestamo, 2);
    }

    /**
     * Actualiza el estado de los préstamos basado en moras pendientes
     */
    private function actualizarEstadoPrestamos(): void
    {
        // Obtener préstamos que tienen cuotas con moras pendientes o parciales
        $prestamosConMoras = Prestamo::whereHas('cuotas.moras', function ($query) {
            $query->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL]);
        })->where('estado', 'Vigente')->get();

        foreach ($prestamosConMoras as $prestamo) {
            $prestamo->update(['estado' => 'Moroso']);
            Log::info("Préstamo {$prestamo->id} marcado como Moroso");
        }
    }

    /**
     * Registra en el historial la generación masiva de moras
     */
    private function registrarHistorialMasivo(array $resultados): void
    {
        MoraHistory::create([
            'tipo_operacion' => 'generacion_masiva',
            'fecha' => Carbon::now(),
            'cuotas_procesadas' => $resultados['procesadas'],
            'moras_generadas' => $resultados['generadas'],
            'cuotas_omitidas' => $resultados['omitidas'],
            'errores' => $resultados['errores'],
            'detalles' => json_encode($resultados['detalles']),
            'usuario_id' => auth()->id(),
        ]);
    }

    /**
     * Método específico para el comando diario automático
     */
    public function generarMorasDiarias(): array
    {
        Log::info('Iniciando generación automática diaria de moras');

        $resultados = $this->generarMorasMasivas();

        // Agregar información específica para el proceso diario
        $resultados['fecha_proceso'] = Carbon::now()->format('Y-m-d H:i:s');
        $resultados['tipo_proceso'] = 'automatico_diario';

        return $resultados;
    }

    /**
     * Obtiene estadísticas de moras
     */
    public function obtenerEstadisticasMoras(): array
    {
        return [
            'total_moras_pendientes' => MoraCuota::whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])->count(),
            'total_moras_pagadas' => MoraCuota::where('estado', MoraCuotaEstado::PAGADO)->count(),
            'monto_total_moras_pendientes' => MoraCuota::whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])->sum('monto'),
            'cuotas_con_moras' => Cuota::whereHas('moras', function ($q) {
                $q->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL]);
            })->count(),
            'prestamos_morosos' => Prestamo::where('estado', 'Moroso')->count(),
        ];
    }

    /**
     * Verifica y corrige cuotas que deberían tener moras pero no las tienen
     */
    public function verificarYCorregirMoras(): array
    {
        $corregidas = 0;
        $errores = 0;

        $cuotasVencidas = $this->obtenerCuotasVencidas();

        foreach ($cuotasVencidas as $cuota) {
            $diasVencidos = Carbon::parse($cuota->fecha_pago)->diffInDays(Carbon::today());
            // CORRECCIÓN: Contar solo moras realmente pendientes para cuotas parciales
            $morasRealmentePendientes = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                ->count();

            if ($diasVencidos > $morasRealmentePendientes && $morasRealmentePendientes < self::MAX_MORAS_POR_CUOTA) {
                try {
                    $this->procesarCuotaParaMoras($cuota);
                    $corregidas++;
                } catch (\Exception $e) {
                    $errores++;
                    Log::error("Error corrigiendo cuota {$cuota->id}: ".$e->getMessage());
                }
            }
        }

        return [
            'cuotas_corregidas' => $corregidas,
            'errores' => $errores,
        ];
    }

    /**
     * Regulariza moras basándose en las fechas reales de pago de las cuotas
     * Si una cuota se pagó después de su vencimiento, las moras posteriores
     * a la fecha de pago deben quedar como pendientes
     */
    public function regularizarMorasPorFechaPago(): array
    {
        $resultados = [
            'cuotas_procesadas' => 0,
            'moras_regularizadas' => 0,
            'moras_ajustadas' => 0,
            'errores' => 0,
            'detalles' => [],
        ];

        try {
            DB::beginTransaction();

            // MÉTODO COMPLETO: Buscar TODAS las cuotas que tienen moras para verificar/corregir regularizaciones
            Log::info('Buscando TODAS las cuotas con moras en el sistema...');

            // Obtener TODAS las cuotas que tienen moras, sin importar el estado de las moras
            $cuotasConMoras = Cuota::with(['prestamo', 'operaciones' => function ($query) {
                $query->where('tipo_operacion', 'Pago de cuota')
                    ->orderBy('fecha', 'asc');
            }, 'moras'])
                ->whereHas('moras')
                ->get();

            Log::info("Encontradas {$cuotasConMoras->count()} cuotas con moras en el sistema");

            // Contar moras por estado para estadísticas
            $totalMoras = MoraCuota::count();
            $morasPendientes = MoraCuota::where('estado', MoraCuotaEstado::PENDIENTE->value)->count();
            $morasRegularizadas = MoraCuota::where('estado', MoraCuotaEstado::REGULARIZADA->value)->count();
            $morasPagadas = MoraCuota::where('estado', MoraCuotaEstado::PAGADO->value)->count();

            Log::info("Estado actual de moras: Total={$totalMoras}, Pendientes={$morasPendientes}, Regularizadas={$morasRegularizadas}, Pagadas={$morasPagadas}");

            Log::info("Iniciando regularización COMPLETA de moras para {$cuotasConMoras->count()} cuotas con moras");

            foreach ($cuotasConMoras as $index => $cuota) {
                $resultados['cuotas_procesadas']++;

                // Log de progreso cada 100 cuotas
                if (($index + 1) % 100 == 0) {
                    $porcentaje = round((($index + 1) / $cuotasConMoras->count()) * 100, 1);
                    Log::info("🔄 Progreso: {$porcentaje}% - Procesadas ".($index + 1)." de {$cuotasConMoras->count()} cuotas");
                }

                try {
                    $resultado = $this->procesarRegularizacionCuota($cuota);

                    $resultados['moras_regularizadas'] += $resultado['regularizadas'];
                    $resultados['moras_ajustadas'] += $resultado['ajustadas'];

                    if ($resultado['regularizadas'] > 0 || $resultado['ajustadas'] > 0) {
                        $resultados['detalles'][] = [
                            'cuota_id' => $cuota->id,
                            'cuota_numero' => $cuota->numero,
                            'prestamo_id' => $cuota->prestamo_id,
                            'fecha_vencimiento' => $cuota->fecha_pago,
                            'fecha_pago_real' => $resultado['fecha_pago_real'],
                            'moras_regularizadas' => $resultado['regularizadas'],
                            'moras_ajustadas' => $resultado['ajustadas'],
                            'accion' => $resultado['accion'],
                        ];
                    }

                } catch (\Exception $e) {
                    $resultados['errores']++;
                    Log::error("Error procesando cuota {$cuota->id} para regularización: ".$e->getMessage());
                    $resultados['detalles'][] = [
                        'cuota_id' => $cuota->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info('Regularización de moras completada', $resultados);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en regularización de moras: '.$e->getMessage());
            $resultados['errores']++;
            throw $e;
        }

        return $resultados;
    }

    /**
     * Procesa la regularización de moras para una cuota específica
     */
    private function procesarRegularizacionCuota(Cuota $cuota): array
    {
        // RECARGAR CUOTA FRESH PARA OBTENER FECHA_PAGO CORRECTA
        $cuotaFresh = Cuota::find($cuota->id);
        if (! $cuotaFresh) {
            Log::error("No se pudo recargar cuota {$cuota->id}");

            return ['regularizadas' => 0, 'ajustadas' => 0, 'fecha_pago_real' => null, 'accion' => 'error'];
        }

        // VERIFICACIÓN: Contar moras por estado para decidir si procesar o no
        $morasPendientes = $cuotaFresh->moras()
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
            ->count();

        $morasRegularizadas = $cuotaFresh->moras()
            ->where('estado', MoraCuotaEstado::REGULARIZADA->value)
            ->count();

        // Si no hay moras pendientes NI regularizadas, no hay nada que procesar
        if ($morasPendientes == 0 && $morasRegularizadas == 0) {
            Log::info("Cuota {$cuota->id} no tiene moras para procesar");

            return ['regularizadas' => 0, 'ajustadas' => 0, 'fecha_pago_real' => null, 'accion' => 'sin_moras'];
        }

        // Log de estado actual para debug
        Log::info("Cuota {$cuota->id} - Moras pendientes: {$morasPendientes}, Moras regularizadas: {$morasRegularizadas}");

        $fechaVencimiento = Carbon::parse($cuotaFresh->fecha_pago)->startOfDay();
        $resultado = [
            'regularizadas' => 0,
            'ajustadas' => 0,
            'fecha_pago_real' => null,
            'accion' => 'sin_cambios',
        ];

        // Obtener la fecha del primer pago de esta cuota usando la cuota fresh
        $primerPago = $cuotaFresh->operaciones()
            ->where('tipo_operacion', 'Pago de cuota')
            ->orderBy('fecha', 'asc')
            ->first();

        if (! $primerPago) {
            // Si no hay operación de pago, revisar si la cuota está pagada y usar la fecha de vencimiento
            if ($cuotaFresh->estado == \App\Enums\CuotaEstado::PAGADO->value || $cuotaFresh->estado == \App\Enums\CuotaEstado::PARCIAL->value) {
                Log::info("Cuota {$cuota->id} está pagada pero sin operación de pago registrada - Usando fecha de vencimiento");
                // Para cuotas marcadas como pagadas sin operación, asumir pago el día del vencimiento
                $fechaPagoReal = $fechaVencimiento;
            } else {
                Log::info("Cuota {$cuota->id} no tiene operaciones de pago y no está pagada - Verificando si hay moras pagadas individualmente");

                // CORRECCIÓN: Verificar si hay moras PAGADAS antes de cambiar las REGULARIZADAS
                $morasPagadas = $cuotaFresh->moras()
                    ->where('estado', MoraCuotaEstado::PAGADO->value)
                    ->count();

                if ($morasPagadas > 0) {
                    Log::info("✅ Cuota {$cuota->id} tiene {$morasPagadas} moras pagadas individualmente - No corrigiendo regularizadas");
                    $resultado['accion'] = 'moras_pagadas_individualmente';

                    return $resultado;
                }

                // Solo para cuotas sin pago de cuota Y sin moras pagadas, corregir las regularizadas
                $morasRegularizadasIncorrectamente = $cuotaFresh->moras()
                    ->where('estado', MoraCuotaEstado::REGULARIZADA->value)
                    ->get();

                if ($morasRegularizadasIncorrectamente->count() > 0) {
                    Log::info("✅ Corrigiendo {$morasRegularizadasIncorrectamente->count()} moras regularizadas incorrectamente en cuota sin pago {$cuota->id}");

                    foreach ($morasRegularizadasIncorrectamente as $mora) {
                        // 🎯 NUEVO: Convertir pago de mora a abono a favor antes de corregir
                        $montoPagado = $mora->monto_pagado ?? 0;
                        if ($montoPagado > 0) {
                            $this->convertirPagoMoraAAbonoFavor($mora, $montoPagado, 'correccion_cuota_sin_pago');
                        }

                        // Registrar en historial el cambio
                        DB::table('moras_history')->insert([
                            'mora_id' => $mora->id,
                            'monto_anterior' => $mora->monto_pagado ?? 0,
                            'status_anterior' => $mora->estado->value,
                            'monto_nuevo' => $mora->monto_pagado ?? 0,
                            'status_nuevo' => MoraCuotaEstado::PENDIENTE->value,
                            'user_id' => auth()->id() ?? 1,
                            'accion' => 'correccion_btn',
                            'created_at' => now(),
                        ]);

                        // Cambiar de REGULARIZADA (3) a PENDIENTE (0)
                        $mora->update(['estado' => MoraCuotaEstado::PENDIENTE->value]);
                        $resultado['regularizadas']++;

                        Log::info("   🔄 CORREGIDA mora {$mora->id} - de REGULARIZADA a PENDIENTE (cuota sin pago) con abono a favor");
                    }

                    $resultado['accion'] = 'cuota_sin_pago_corregida';
                    Log::info("🔄 Cuota {$cuota->id} sin pago: {$morasRegularizadasIncorrectamente->count()} moras corregidas de REGULARIZADA a PENDIENTE");
                } else {
                    Log::info("✅ Cuota {$cuota->id} sin pago ya tiene todas las moras en estado correcto (PENDIENTES)");
                    $resultado['accion'] = 'cuota_sin_pago_correcta';
                }

                return $resultado;
            }
        } else {
            $fechaPagoReal = Carbon::parse($primerPago->fecha)->startOfDay();
        }

        $resultado['fecha_pago_real'] = $fechaPagoReal->format('Y-m-d');

        // VALIDACIÓN: Detectar fechas anómalas
        $añoActual = Carbon::now()->year;
        if ($fechaPagoReal->year > $añoActual + 1 || $fechaPagoReal->year < $añoActual - 10) {
            Log::warning("⚠️ Fecha de pago anómala en cuota {$cuota->id}: {$fechaPagoReal->format('Y-m-d')} - Saltando regularización");

            return $resultado;
        }

        Log::info("🔍 REGULARIZACIÓN MASIVA - Cuota {$cuota->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pagada {$fechaPagoReal->format('Y-m-d')}");

        // LÓGICA CORREGIDA: Comparar fecha_pago (vencimiento) vs fecha de operaciones (pago real)
        if ($fechaPagoReal->lte($fechaVencimiento)) {
            // CASO 1: Pago a tiempo o anticipado - Regularizar TODAS las moras
            $morasParaRegularizar = $cuotaFresh->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                ->get();

            if ($morasParaRegularizar->count() > 0) {
                Log::info("✅ Pago a tiempo - Regularizando {$morasParaRegularizar->count()} moras de la cuota {$cuota->id}");

                foreach ($morasParaRegularizar as $mora) {
                    $this->regularizarMora($mora, 'pago_ok');
                    $resultado['regularizadas']++;
                }
                $resultado['accion'] = 'pago_a_tiempo';
            }

        } else {
            // CASO 2: Pago tardío - Manejar moras según la fecha de pago real
            $diasTarde = $fechaVencimiento->diffInDays($fechaPagoReal);
            Log::info("⏰ Pago tardío - Cuota {$cuota->id} pagada {$diasTarde} días después del vencimiento");

            // LÓGICA CORREGIDA: Buscar TODAS las moras y procesarlas correctamente
            $todasLasMoras = $cuotaFresh->moras()->orderBy('fecha')->get();
            $morasRegularizadas = 0;
            $morasCorregidas = 0;

            Log::info("📋 Revisando {$todasLasMoras->count()} moras para pago tardío");

            foreach ($todasLasMoras as $mora) {
                $fechaMora = Carbon::parse($mora->fecha)->startOfDay();

                if ($fechaMora->gt($fechaPagoReal)) {
                    // POSTERIOR al pago - debe estar REGULARIZADA
                    if (in_array($mora->estado->value, [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])) {
                        Log::info("   🔄 Regularizando mora {$mora->id} (fecha {$mora->fecha}) - posterior a pago {$fechaPagoReal->format('Y-m-d')}");
                        $this->regularizarMora($mora, 'historico_tarde');
                        $morasRegularizadas++;
                    } else {
                        Log::info("   ✅ Mora {$mora->id} (fecha {$mora->fecha}) ya está regularizada correctamente");
                    }
                } else {
                    // ANTERIOR/IGUAL al pago - debe estar PENDIENTE
                    if ($mora->estado->value == MoraCuotaEstado::REGULARIZADA->value) {
                        // 🎯 NUEVO: Convertir pago de mora regularizada a abono a favor antes de corregir
                        $montoPagado = $mora->monto_pagado ?? 0;
                        if ($montoPagado > 0) {
                            $this->convertirPagoMoraAAbonoFavor($mora, $montoPagado, 'correccion_fecha_reducida');
                        }

                        // CORRECCIÓN: Cambiar de REGULARIZADA a PENDIENTE
                        DB::table('moras_history')->insert([
                            'mora_id' => $mora->id,
                            'monto_anterior' => $mora->monto_pagado ?? 0,
                            'status_anterior' => $mora->estado->value,
                            'monto_nuevo' => $mora->monto_pagado ?? 0,
                            'status_nuevo' => MoraCuotaEstado::PENDIENTE->value,
                            'user_id' => auth()->id() ?? 1,
                            'accion' => 'correccion_btn',
                            'created_at' => now(),
                        ]);

                        $mora->update(['estado' => MoraCuotaEstado::PENDIENTE->value]);
                        $morasCorregidas++;

                        Log::info("   🔄 CORREGIDA mora {$mora->id} (fecha {$mora->fecha}) - de REGULARIZADA a PENDIENTE con abono a favor");
                    } else {
                        Log::info("   📝 Mora {$mora->id} (fecha {$mora->fecha}) ya está correcta - PENDIENTE");
                    }
                }
            }

            $resultado['regularizadas'] = $morasRegularizadas + $morasCorregidas;
            if ($morasRegularizadas > 0 || $morasCorregidas > 0) {
                Log::info("🔄 Total procesadas - Regularizadas: {$morasRegularizadas}, Corregidas: {$morasCorregidas}");
                $resultado['accion'] = 'pago_tardio_procesado';
            } else {
                Log::info('✅ Todas las moras ya están en el estado correcto');
                $resultado['accion'] = 'sin_cambios';
            }
        }

        // Actualizar cantidad_mora de la cuota usando cuota fresh
        $totalMorasPendientes = $cuotaFresh->moras()
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
            ->sum('monto');
        $cuotaFresh->update(['cantidad_mora' => $totalMorasPendientes]);

        return $resultado;
    }

    /**
     * Regulariza una mora específica
     */
    private function regularizarMora(MoraCuota $mora, string $motivo): void
    {
        // 🎯 NUEVO: Convertir pagos de mora a abono a favor antes de regularizar
        $montoPagado = $mora->monto_pagado ?? 0;
        if ($montoPagado > 0) {
            $this->convertirPagoMoraAAbonoFavor($mora, $montoPagado, $motivo);
        }

        // CORRECCIÓN: Textos más cortos para la columna 'accion'
        // Registrar en historial antes del cambio
        DB::table('moras_history')->insert([
            'mora_id' => $mora->id,
            'monto_anterior' => $mora->monto_pagado ?? 0,
            'status_anterior' => $mora->estado,
            'monto_nuevo' => $mora->monto_pagado ?? 0,
            'status_nuevo' => MoraCuotaEstado::REGULARIZADA->value,
            'user_id' => auth()->id() ?? 1, // Sistema
            'accion' => "auto_{$motivo}", // Texto más corto
            'created_at' => now(),
        ]);

        // Actualizar estado de la mora
        $mora->update(['estado' => MoraCuotaEstado::REGULARIZADA->value]);

        Log::info("Mora {$mora->id} regularizada por: {$motivo}");
    }

    /**
     * Convierte el pago de una mora que se va a regularizar en abono a favor
     * Esto preserva el dinero pagado para aplicarlo a futuras moras de la misma cuota
     */
    private function convertirPagoMoraAAbonoFavor(MoraCuota $mora, float $montoPagado, string $motivo): void
    {
        if ($montoPagado <= 0) {
            return;
        }

        // Verificar si ya existe un abono a favor activo para esta cuota
        $abonoExistente = \App\Models\AbonoMoraFavor::where('cuota_id', $mora->cuota_id)
            ->where('estado', \App\Models\AbonoMoraFavor::ESTADO_ACTIVO)
            ->where('saldo_favor', '>', 0)
            ->first();

        if ($abonoExistente) {
            // Sumar al abono existente
            $nuevoSaldo = $abonoExistente->saldo_favor + $montoPagado;
            $nuevoMontoAbonado = $abonoExistente->monto_abonado + $montoPagado;

            $abonoExistente->update([
                'saldo_favor' => $nuevoSaldo,
                'monto_abonado' => $nuevoMontoAbonado,
                'comentario' => $abonoExistente->comentario." + S/{$montoPagado} de mora regularizada por {$motivo}",
            ]);

            Log::info("💰 Abono a favor actualizado: +S/{$montoPagado} (total: S/{$nuevoSaldo}) para cuota {$mora->cuota_id}");
        } else {
            // Buscar una operación asociada a esta cuota para el abono a favor
            $operacionCuota = \DB::table('operaciones_cuota')
                ->where('cuota_id', $mora->cuota_id)
                ->orderBy('created_at', 'desc')
                ->first();

            $operacionId = $operacionCuota ? $operacionCuota->operacion_id : 1; // Usar operación 1 como fallback

            // Crear nuevo abono a favor
            \App\Models\AbonoMoraFavor::create([
                'cuota_id' => $mora->cuota_id,
                'operacion_id' => $operacionId, // Asociar con operación existente de la cuota
                'fecha_abono' => now(),
                'monto_abonado' => $montoPagado,
                'saldo_favor' => $montoPagado,
                'monto_utilizado' => 0,
                'estado' => \App\Models\AbonoMoraFavor::ESTADO_ACTIVO,
                'comentario' => "Abono generado automáticamente por regularización de mora (motivo: {$motivo})",
            ]);

            Log::info("💰 Nuevo abono a favor creado: S/{$montoPagado} para cuota {$mora->cuota_id} (mora regularizada)");
        }
    }

    /**
     * Regulariza moras de pagos antiguos que no fueron procesados correctamente
     * Útil para corregir registros históricos donde se registraron pagos pero no se regularizaron las moras
     */
    public function regularizarPagosAntiguos(): array
    {
        $resultados = [
            'cuotas_procesadas' => 0,
            'moras_regularizadas' => 0,
            'errores' => 0,
            'detalles' => [],
        ];

        try {
            DB::beginTransaction();

            // Buscar cuotas pagadas que tienen moras pendientes
            $cuotasPagadas = Cuota::with(['prestamo', 'operaciones' => function ($query) {
                $query->where('tipo_operacion', 'Pago de cuota')
                    ->orderBy('fecha', 'asc');
            }, 'moras' => function ($query) {
                $query->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value]);
            }])
                ->whereIn('estado', [CuotaEstado::PAGADO->value, CuotaEstado::PARCIAL->value])
                ->whereHas('moras', function ($query) {
                    $query->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value]);
                })
                ->get();

            Log::info("Encontradas {$cuotasPagadas->count()} cuotas pagadas con moras pendientes para regularizar");

            foreach ($cuotasPagadas as $cuota) {
                $resultados['cuotas_procesadas']++;

                try {
                    // Obtener la primera operación de pago de esta cuota
                    $primerPago = $cuota->operaciones()
                        ->where('tipo_operacion', 'Pago de cuota')
                        ->orderBy('fecha', 'asc')
                        ->first();

                    if (! $primerPago) {
                        Log::warning("Cuota pagada {$cuota->id} sin operación de pago registrada");

                        continue;
                    }

                    $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();
                    $fechaPagoReal = Carbon::parse($primerPago->fecha)->startOfDay();

                    Log::info("🔄 Procesando cuota {$cuota->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pagada {$fechaPagoReal->format('Y-m-d')}");

                    // Aplicar la misma lógica que en RegistrarPagoController
                    if ($fechaPagoReal->lte($fechaVencimiento)) {
                        // Pago a tiempo - Regularizar todas las moras
                        $morasParaRegularizar = $cuota->moras()
                            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                            ->get();

                        foreach ($morasParaRegularizar as $mora) {
                            $this->regularizarMora($mora, 'historico_atiempo');
                            $resultados['moras_regularizadas']++;
                        }

                    } else {
                        // Pago tardío - Regularizar solo moras posteriores a la fecha de pago
                        $morasPosteriores = $cuota->moras()
                            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                            ->whereDate('fecha', '>', $fechaPagoReal)
                            ->get();

                        foreach ($morasPosteriores as $mora) {
                            $this->regularizarMora($mora, 'historico_tarde');
                            $resultados['moras_regularizadas']++;
                        }
                    }

                    // Actualizar cantidad_mora de la cuota
                    $totalMorasRestantes = $cuota->moras()
                        ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                        ->sum('monto');
                    $cuota->update(['cantidad_mora' => $totalMorasRestantes]);

                    $resultados['detalles'][] = [
                        'cuota_id' => $cuota->id,
                        'prestamo_id' => $cuota->prestamo_id,
                        'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d'),
                        'fecha_pago' => $fechaPagoReal->format('Y-m-d'),
                        'moras_regularizadas' => $resultados['moras_regularizadas'],
                    ];

                } catch (\Exception $e) {
                    $resultados['errores']++;
                    Log::error("Error procesando cuota {$cuota->id} para regularización histórica: ".$e->getMessage());
                }
            }

            DB::commit();
            Log::info('Regularización de pagos antiguos completada', $resultados);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en regularización de pagos antiguos: '.$e->getMessage());
            $resultados['errores']++;
            throw $e;
        }

        return $resultados;
    }

    /**
     * Corrige cuotas que fueron incorrectamente regularizadas
     * Busca cuotas SIN pago que tienen todas sus moras REGULARIZADAS y las revierte a PENDIENTES
     */
    public function corregirMorasRegularizadasIncorrectamente(): array
    {
        $resultados = [
            'cuotas_corregidas' => 0,
            'moras_revertidas' => 0,
            'errores' => 0,
            'detalles' => [],
        ];

        try {
            DB::beginTransaction();

            // Buscar cuotas SIN pago que tienen TODAS sus moras regularizadas
            $cuotasIncorrectas = Cuota::with(['operaciones' => function ($query) {
                $query->where('tipo_operacion', 'Pago de cuota');
            }, 'moras'])
                ->whereIn('estado', [CuotaEstado::PENDIENTE->value])
                ->whereDoesntHave('operaciones', function ($query) {
                    $query->where('tipo_operacion', 'Pago de cuota');
                })
                ->whereHas('moras', function ($query) {
                    $query->where('estado', MoraCuotaEstado::REGULARIZADA->value);
                })
                ->get();

            Log::info("Encontradas {$cuotasIncorrectas->count()} cuotas sin pago con moras incorrectamente regularizadas");

            foreach ($cuotasIncorrectas as $cuota) {
                $resultados['cuotas_corregidas']++;

                try {
                    // Obtener todas las moras regularizadas de esta cuota
                    $morasRegularizadas = $cuota->moras()
                        ->where('estado', MoraCuotaEstado::REGULARIZADA->value)
                        ->get();

                    $morasRevertidas = 0;
                    foreach ($morasRegularizadas as $mora) {
                        // 🎯 NUEVO: Convertir pago de mora a abono a favor antes de revertir
                        $montoPagado = $mora->monto_pagado ?? 0;
                        if ($montoPagado > 0) {
                            $this->convertirPagoMoraAAbonoFavor($mora, $montoPagado, 'correccion_masiva');
                        }

                        // Registrar en historial el cambio
                        DB::table('moras_history')->insert([
                            'mora_id' => $mora->id,
                            'monto_anterior' => $mora->monto_pagado ?? 0,
                            'status_anterior' => $mora->estado->value,
                            'monto_nuevo' => $mora->monto_pagado ?? 0,
                            'status_nuevo' => MoraCuotaEstado::PENDIENTE->value,
                            'user_id' => auth()->id() ?? 1,
                            'accion' => 'correccion_auto',
                            'created_at' => now(),
                        ]);

                        // Revertir el estado a PENDIENTE
                        $mora->update(['estado' => MoraCuotaEstado::PENDIENTE->value]);
                        $morasRevertidas++;

                        Log::info("Mora {$mora->id} revertida de REGULARIZADA a PENDIENTE (cuota sin pago {$cuota->id}) con abono a favor");
                    }

                    // Actualizar cantidad_mora de la cuota
                    $totalMorasPendientes = $cuota->moras()
                        ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                        ->sum('monto');
                    $cuota->update(['cantidad_mora' => $totalMorasPendientes]);

                    $resultados['moras_revertidas'] += $morasRevertidas;
                    $resultados['detalles'][] = [
                        'cuota_id' => $cuota->id,
                        'prestamo_id' => $cuota->prestamo_id,
                        'cuota_numero' => $cuota->numero,
                        'moras_revertidas' => $morasRevertidas,
                        'fecha_vencimiento' => $cuota->fecha_pago,
                    ];

                    Log::info("Cuota {$cuota->id} (#{$cuota->numero}) corregida: {$morasRevertidas} moras revertidas a PENDIENTES");

                } catch (\Exception $e) {
                    $resultados['errores']++;
                    Log::error("Error corrigiendo cuota {$cuota->id}: ".$e->getMessage());
                }
            }

            DB::commit();
            Log::info('Corrección de moras regularizadas incorrectamente completada', $resultados);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error en corrección de moras regularizadas incorrectamente: '.$e->getMessage());
            $resultados['errores']++;
            throw $e;
        }

        return $resultados;
    }

    /**
     * Método de prueba: Permite revertir moras de un préstamo específico para testing
     * Cambia moras REGULARIZADAS a PENDIENTES para poder probar la corrección
     */
    public function revertirMorasParaPrueba(int $prestamoId): array
    {
        $resultados = [
            'moras_revertidas' => 0,
            'cuotas_afectadas' => 0,
        ];

        try {
            DB::beginTransaction();

            // Buscar cuotas del préstamo que tienen moras regularizadas
            $cuotas = Cuota::where('prestamo_id', $prestamoId)
                ->whereHas('moras', function ($query) {
                    $query->where('estado', MoraCuotaEstado::REGULARIZADA->value);
                })
                ->with('moras')
                ->get();

            foreach ($cuotas as $cuota) {
                $morasRegularizadas = $cuota->moras()
                    ->where('estado', MoraCuotaEstado::REGULARIZADA->value)
                    ->get();

                if ($morasRegularizadas->count() > 0) {
                    $resultados['cuotas_afectadas']++;

                    foreach ($morasRegularizadas as $mora) {
                        $mora->update(['estado' => MoraCuotaEstado::PENDIENTE->value]);
                        $resultados['moras_revertidas']++;
                    }

                    // Actualizar cantidad_mora
                    $totalMoras = $cuota->moras()
                        ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                        ->sum('monto');
                    $cuota->update(['cantidad_mora' => $totalMoras]);
                }
            }

            DB::commit();
            Log::info("Revertidas {$resultados['moras_revertidas']} moras en {$resultados['cuotas_afectadas']} cuotas del préstamo {$prestamoId}");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error revirtiendo moras: '.$e->getMessage());
        }

        return $resultados;
    }

    /**
     * Regularizar moras para un préstamo individual
     * Procesa solo las cuotas de un préstamo específico
     */
    public function regularizarMorasPrestamoIndividual(int $prestamoId): array
    {
        $resultados = [
            'cuotas_procesadas' => 0,
            'moras_regularizadas' => 0,
            'moras_ajustadas' => 0,
            'errores' => 0,
            'detalles' => [],
        ];

        try {
            DB::beginTransaction();

            Log::info("Iniciando regularización individual de moras para préstamo {$prestamoId}");

            // Obtener todas las cuotas del préstamo que necesitan regularización
            // Incluye: cuotas con moras O cuotas con pagos (pueden necesitar creación/eliminación de moras)
            $cuotasConMoras = Cuota::with(['prestamo', 'operaciones' => function ($query) {
                $query->whereIn('tipo_operacion', ['Pago de cuota', 'Pago general'])
                    ->where('estado', '!=', 'anulado')
                    ->orderBy('fecha', 'asc');
            }, 'moras'])
                ->where('prestamo_id', $prestamoId)
                ->where(function ($query) {
                    $query->whereHas('moras') // Cuotas que ya tienen moras
                        ->orWhereHas('operaciones', function ($q) { // O cuotas que tienen pagos
                            $q->whereIn('tipo_operacion', ['Pago de cuota', 'Pago general'])
                                ->where('estado', '!=', 'anulado');
                        });
                })
                ->orderBy('numero', 'asc')
                ->get();

            Log::info("Encontradas {$cuotasConMoras->count()} cuotas para regularizar en el préstamo {$prestamoId} (incluye cuotas con moras y cuotas con pagos)");

            if ($cuotasConMoras->isEmpty()) {
                Log::info("El préstamo {$prestamoId} no tiene cuotas con moras para regularizar");
                DB::commit();

                return $resultados;
            }

            // Estadísticas del préstamo
            $totalMoras = MoraCuota::whereHas('cuota', function ($query) use ($prestamoId) {
                $query->where('prestamo_id', $prestamoId);
            })->count();

            $morasPendientes = MoraCuota::whereHas('cuota', function ($query) use ($prestamoId) {
                $query->where('prestamo_id', $prestamoId);
            })->where('estado', MoraCuotaEstado::PENDIENTE->value)->count();

            $morasRegularizadas = MoraCuota::whereHas('cuota', function ($query) use ($prestamoId) {
                $query->where('prestamo_id', $prestamoId);
            })->where('estado', MoraCuotaEstado::REGULARIZADA->value)->count();

            Log::info("Préstamo {$prestamoId} - Moras: Total={$totalMoras}, Pendientes={$morasPendientes}, Regularizadas={$morasRegularizadas}");

            foreach ($cuotasConMoras as $cuota) {
                $resultados['cuotas_procesadas']++;

                try {
                    $resultado = $this->procesarRegularizacionCuota($cuota);

                    $resultados['moras_regularizadas'] += $resultado['regularizadas'];
                    $resultados['moras_ajustadas'] += $resultado['ajustadas'];

                    if ($resultado['regularizadas'] > 0 || $resultado['ajustadas'] > 0) {
                        $resultados['detalles'][] = [
                            'cuota_id' => $cuota->id,
                            'cuota_numero' => $cuota->numero,
                            'fecha_vencimiento' => $cuota->fecha_pago,
                            'fecha_pago_real' => $resultado['fecha_pago_real'],
                            'moras_regularizadas' => $resultado['regularizadas'],
                            'moras_ajustadas' => $resultado['ajustadas'],
                            'accion' => $resultado['accion'],
                        ];
                    }

                } catch (\Exception $e) {
                    $resultados['errores']++;
                    Log::error("Error procesando cuota {$cuota->id} del préstamo {$prestamoId}: ".$e->getMessage());
                    $resultados['detalles'][] = [
                        'cuota_id' => $cuota->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info("Regularización individual completada para préstamo {$prestamoId}", $resultados);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Error en regularización individual del préstamo {$prestamoId}: ".$e->getMessage());
            $resultados['errores']++;
            throw $e;
        }

        return $resultados;
    }

    /**
     * Genera moras para una cuota hasta una fecha específica
     * Útil para edición de operaciones donde se cambia la fecha de pago
     *
     * @param Cuota $cuota La cuota para la cual generar moras
     * @param Carbon|string $fechaHasta Fecha hasta la cual generar moras
     * @return array Resultados de la generación
     */
    public function generarMorasHastaFecha(Cuota $cuota, $fechaHasta): array
    {
        $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();
        $fechaLimite = Carbon::parse($fechaHasta)->startOfDay();

        Log::info("🔄 Generando moras hasta fecha específica para cuota #{$cuota->numero}: Vence {$fechaVencimiento->format('Y-m-d')}, Límite {$fechaLimite->format('Y-m-d')}");

        // Si la fecha límite es anterior o igual al vencimiento, no hay moras
        if ($fechaLimite->lte($fechaVencimiento)) {
            Log::info("✅ Fecha límite no excede vencimiento - No se generan moras");
            return ['generadas' => 0, 'dias_vencidos' => 0, 'mensaje' => 'Pago a tiempo o anticipado'];
        }

        $diasMora = $fechaVencimiento->diffInDays($fechaLimite);

        // IMPORTANTE: Contar solo moras NO REGULARIZADAS (las regularizadas no cuentan para el límite)
        $morasNoRegularizadas = $cuota->moras()
            ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL, MoraCuotaEstado::PAGADO])
            ->count();

        $todasLasMoras = $cuota->moras()->count();
        $morasRegularizadas = $todasLasMoras - $morasNoRegularizadas;

        Log::info("📊 Días de mora: {$diasMora}, Moras existentes: {$todasLasMoras} (No regularizadas: {$morasNoRegularizadas}, Regularizadas: {$morasRegularizadas})");

        // Verificar límite máximo usando solo moras NO regularizadas
        if ($morasNoRegularizadas >= self::MAX_MORAS_POR_CUOTA) {
            Log::info("⚠️ Límite máximo de moras alcanzado ({$morasNoRegularizadas})");
            return ['generadas' => 0, 'dias_vencidos' => $diasMora, 'mensaje' => 'Límite máximo alcanzado'];
        }

        // Calcular cuántas moras faltan por generar
        // Si hay moras regularizadas, podemos generar nuevas moras en su lugar
        $morasParaGenerar = min(self::MAX_MORAS_POR_CUOTA - $morasNoRegularizadas, $diasMora);

        if ($morasParaGenerar <= 0) {
            Log::info("ℹ️ No hay moras faltantes por generar");
            return ['generadas' => 0, 'dias_vencidos' => $diasMora, 'mensaje' => 'Moras ya generadas'];
        }

        $morasGeneradas = 0;
        $montoMora = $this->calcularMontoMora($cuota);

        // Generar las moras faltantes
        for ($i = 0; $i < $morasParaGenerar; $i++) {
            // IMPORTANTE: Usar solo moras no regularizadas para calcular el próximo día
            $diaMora = $morasNoRegularizadas + $i + 1;
            $fechaMora = $fechaVencimiento->copy()->addDays($diaMora);

            // No generar moras posteriores a la fecha límite
            if ($fechaMora->gt($fechaLimite)) {
                Log::info("⚠️ Mora del día {$diaMora} (fecha {$fechaMora->format('Y-m-d')}) excede fecha límite - deteniendo generación");
                break;
            }

            try {
                $mora = MoraCuota::create([
                    'cuota_id' => $cuota->id,
                    'fecha' => $fechaMora,
                    'dias_mora' => $diaMora,
                    'monto' => $montoMora,
                    'estado' => MoraCuotaEstado::PENDIENTE,
                ]);

                $morasGeneradas++;
                Log::info("✅ Mora generada: Día {$diaMora}, Fecha {$fechaMora->format('Y-m-d')}, Monto S/{$montoMora}");

                // Aplicar abonos a favor si existen
                try {
                    $montoAplicadoFavor = $cuota->aplicarAbonosFavorAMora($mora);
                    if ($montoAplicadoFavor > 0) {
                        $mora->refresh();
                        Log::info("💰 Abono a favor aplicado: S/{$montoAplicadoFavor} a mora {$mora->id}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error aplicando abono a favor a mora {$mora->id}: ".$e->getMessage());
                }

            } catch (\Exception $e) {
                Log::error("Error generando mora día {$diaMora}: ".$e->getMessage());
            }
        }

        // Actualizar cantidad_mora de la cuota
        if ($morasGeneradas > 0) {
            $totalMoras = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                ->sum(DB::raw('COALESCE(monto - monto_pagado, monto)'));
            $cuota->update(['cantidad_mora' => $totalMoras]);
        }

        Log::info("✅ Generación completada: {$morasGeneradas} moras nuevas, total días mora: {$diasMora}");

        return [
            'generadas' => $morasGeneradas,
            'dias_vencidos' => $diasMora,
            'mensaje' => "Se generaron {$morasGeneradas} moras hasta {$fechaLimite->format('Y-m-d')}",
        ];
    }

    /**
     * Recalcula las moras de una cuota después de anular una operación
     * Si se pagó más mora de la que debería haber, el excedente se convierte en "mora a favor"
     *
     * @param Cuota $cuota La cuota cuyas moras deben recalcularse
     * @return array Resultados del recálculo
     */
    public function recalcularMorasDespuesAnulacion(Cuota $cuota): array
    {
        $resultados = [
            'moras_actualizadas' => 0,
            'moras_nuevas_generadas' => 0,
            'abonos_favor_creados' => 0,
            'monto_total_abonos_favor' => 0,
            'detalles' => [],
        ];

        try {
            $hoy = Carbon::today();
            $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();

            Log::info("🔄 Recalculando moras después de anulación para cuota #{$cuota->numero} (ID: {$cuota->id})");

            // Solo procesar si la cuota está vencida
            if ($fechaVencimiento >= $hoy) {
                Log::info("✅ Cuota no vencida - No se generan moras");
                return $resultados;
            }

            $diasVencidos = $fechaVencimiento->diffInDays($hoy);
            $diasMoraReales = min($diasVencidos, self::MAX_MORAS_POR_CUOTA);

            Log::info("📅 Días vencidos: {$diasVencidos}, Días mora reales (máx 7): {$diasMoraReales}");

            // Calcular el monto de mora correcto por día
            $montoMoraPorDia = $this->calcularMontoMora($cuota);
            $montoMoraTotal = $montoMoraPorDia * $diasMoraReales;

            Log::info("💰 Monto mora por día: S/{$montoMoraPorDia}, Total esperado: S/{$montoMoraTotal}");

            // Obtener todas las moras no regularizadas de esta cuota
            $morasExistentes = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL, MoraCuotaEstado::PAGADO])
                ->orderBy('dias_mora', 'asc')
                ->get();

            // Calcular el total pagado en moras
            $totalPagadoMoras = $morasExistentes->sum('monto_pagado');

            Log::info("📊 Moras existentes: {$morasExistentes->count()}, Total pagado: S/{$totalPagadoMoras}");

            // CASO 1: Si hay menos moras de las que deberían existir, generar las faltantes
            if ($morasExistentes->count() < $diasMoraReales) {
                $morasFaltantes = $diasMoraReales - $morasExistentes->count();
                Log::info("⚠️ Faltan {$morasFaltantes} moras - Generando...");

                for ($i = 0; $i < $morasFaltantes; $i++) {
                    $diaMora = $morasExistentes->count() + $i + 1;
                    $fechaMora = $fechaVencimiento->copy()->addDays($diaMora);

                    if ($fechaMora > $hoy) {
                        break;
                    }

                    $mora = MoraCuota::create([
                        'cuota_id' => $cuota->id,
                        'fecha' => $fechaMora,
                        'dias_mora' => $diaMora,
                        'monto' => $montoMoraPorDia,
                        'monto_pagado' => 0,
                        'estado' => MoraCuotaEstado::PENDIENTE,
                    ]);

                    $resultados['moras_nuevas_generadas']++;
                    Log::info("✅ Nueva mora generada: Día {$diaMora}, Monto S/{$montoMoraPorDia}");

                    // Aplicar abonos a favor si existen
                    try {
                        $montoAplicadoFavor = $cuota->aplicarAbonosFavorAMora($mora);
                        if ($montoAplicadoFavor > 0) {
                            $mora->refresh();
                            Log::info("💰 Abono a favor aplicado: S/{$montoAplicadoFavor} a nueva mora {$mora->id}");
                        }
                    } catch (\Exception $e) {
                        Log::error("Error aplicando abono a favor: ".$e->getMessage());
                    }
                }

                // Recargar moras después de generar nuevas
                $morasExistentes = $cuota->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL, MoraCuotaEstado::PAGADO])
                    ->orderBy('dias_mora', 'asc')
                    ->get();
            }

            // CASO 2: Si se pagó más de lo que debería, crear abono a favor
            if ($totalPagadoMoras > $montoMoraTotal) {
                $excedente = $totalPagadoMoras - $montoMoraTotal;
                Log::info("💵 Excedente pagado: S/{$excedente} - Creando abono a favor");

                // Buscar la última operación de mora de esta cuota para asociar el abono
                $ultimaOperacionMora = DB::table('operacion_mora')
                    ->join('mora_cuota', 'operacion_mora.mora_cuota_id', '=', 'mora_cuota.id')
                    ->where('mora_cuota.cuota_id', $cuota->id)
                    ->orderBy('operacion_mora.created_at', 'desc')
                    ->first();

                $operacionId = $ultimaOperacionMora ? $ultimaOperacionMora->operacion_id : 1;

                // Verificar si ya existe un abono a favor activo para acumular
                $abonoExistente = \App\Models\AbonoMoraFavor::where('cuota_id', $cuota->id)
                    ->where('estado', \App\Models\AbonoMoraFavor::ESTADO_ACTIVO)
                    ->first();

                if ($abonoExistente) {
                    $nuevoSaldo = $abonoExistente->saldo_favor + $excedente;
                    $nuevoMontoAbonado = $abonoExistente->monto_abonado + $excedente;

                    $abonoExistente->update([
                        'saldo_favor' => $nuevoSaldo,
                        'monto_abonado' => $nuevoMontoAbonado,
                        'comentario' => $abonoExistente->comentario." + S/{$excedente} por anulación de operación",
                    ]);

                    Log::info("💰 Abono a favor actualizado: +S/{$excedente} (total: S/{$nuevoSaldo})");
                } else {
                    \App\Models\AbonoMoraFavor::create([
                        'cuota_id' => $cuota->id,
                        'operacion_id' => $operacionId,
                        'fecha_abono' => now(),
                        'monto_abonado' => $excedente,
                        'saldo_favor' => $excedente,
                        'monto_utilizado' => 0,
                        'estado' => \App\Models\AbonoMoraFavor::ESTADO_ACTIVO,
                        'comentario' => "Abono generado por anulación de operación - excedente de pago de mora",
                    ]);

                    Log::info("💰 Nuevo abono a favor creado: S/{$excedente}");
                }

                $resultados['abonos_favor_creados']++;
                $resultados['monto_total_abonos_favor'] = $excedente;

                // Ahora ajustar las moras para distribuir el pago correctamente
                $pagoPendiente = $montoMoraTotal;
                foreach ($morasExistentes as $mora) {
                    if ($pagoPendiente <= 0) {
                        // Ya no hay pago que asignar, marcar como pendiente
                        $mora->update([
                            'monto_pagado' => 0,
                            'estado' => MoraCuotaEstado::PENDIENTE,
                        ]);
                        $resultados['moras_actualizadas']++;
                    } elseif ($pagoPendiente >= $mora->monto) {
                        // Pagar completamente esta mora
                        $mora->update([
                            'monto_pagado' => $mora->monto,
                            'estado' => MoraCuotaEstado::PAGADO,
                        ]);
                        $pagoPendiente -= $mora->monto;
                        $resultados['moras_actualizadas']++;
                    } else {
                        // Pago parcial
                        $mora->update([
                            'monto_pagado' => $pagoPendiente,
                            'estado' => MoraCuotaEstado::PARCIAL,
                        ]);
                        $pagoPendiente = 0;
                        $resultados['moras_actualizadas']++;
                    }
                }
            }

            // Actualizar cantidad_mora de la cuota
            $totalMorasPendientes = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                ->sum(DB::raw('COALESCE(monto - monto_pagado, monto)'));

            $cuota->update(['cantidad_mora' => $totalMorasPendientes]);

            Log::info("✅ Recálculo completado - Nueva cantidad_mora: S/{$totalMorasPendientes}");

        } catch (\Exception $e) {
            Log::error("❌ Error recalculando moras: ".$e->getMessage());
            throw $e;
        }

        return $resultados;
    }
}
