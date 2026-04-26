<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Services\EstadoPrestamoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CuotasController extends Controller
{
    /**
     * Mostrar formulario de edición de cuota
     */
    public function edit($cuota_id)
    {
        Log::info('Accediendo a editar cuota', ['cuota_id' => $cuota_id]);

        try {
            $cuota = Cuota::with([
                'prestamo.cliente.persona',
                'prestamo.cuotas',
                'operaciones.user',
                'moras',
            ])->findOrFail($cuota_id);

            // Verificar que la cuota pertenezca a un préstamo válido
            if (! $cuota->prestamo) {
                return redirect()->back()
                    ->withErrors(['error' => 'La cuota no está asociada a un préstamo válido.']);
            }

            // Verificar restricciones de edición
            $restricciones = $this->verificarRestriccionesEdicion($cuota);
            if ($restricciones['bloqueado']) {
                return redirect()->back()
                    ->withErrors(['error' => $restricciones['mensaje']]);
            }

            Log::info('Cargando edición de cuota', [
                'cuota_id' => $cuota_id,
                'prestamo_id' => $cuota->prestamo_id,
                'numero_cuota' => $cuota->numero,
                'estado_actual' => $cuota->estado->value,
            ]);

            return view('admin.cuotas.edit', compact('cuota'));

        } catch (\Exception $e) {
            Log::error('Error al cargar edición de cuota: '.$e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Error al cargar la cuota para edición.']);
        }
    }

    /**
     * Actualizar cuota
     */
    public function update(Request $request, $cuota_id)
    {
        $request->validate([
            'fecha_pago' => 'required|date',
            'monto' => 'required|numeric|min:0.01',
            'pago_capital' => 'required|numeric|min:0',
            'interes' => 'required|numeric|min:0',
            'comision' => 'required|numeric|min:0',
            'gas' => 'nullable|numeric|min:0',
            'igv' => 'required|numeric|min:0',
            'justificacion_edicion' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $cuota = Cuota::with(['prestamo', 'operaciones'])->findOrFail($cuota_id);

            // Verificar restricciones
            $restricciones = $this->verificarRestriccionesEdicion($cuota);
            if ($restricciones['bloqueado']) {
                throw new \Exception($restricciones['mensaje']);
            }

            // Guardar valores originales para auditoría
            $valoresOriginales = [
                'fecha_pago' => $cuota->fecha_pago,
                'monto' => $cuota->monto,
                'pago_capital' => $cuota->pago_capital,
                'interes' => $cuota->interes,
                'comision' => $cuota->comision,
                'gas' => $cuota->gas,
                'igv' => $cuota->igv,
            ];

            // Verificar que la suma de componentes sea correcta
            $montoCalculado = $request->pago_capital + $request->interes + $request->comision + ($request->gas ?? 0) + $request->igv;
            $diferencia = abs($montoCalculado - $request->monto);

            if ($diferencia > 0.02) { // Tolerancia de 2 centavos por redondeo
                throw new \Exception("La suma de los componentes (S/{$montoCalculado}) no coincide con el monto total (S/{$request->monto}).");
            }

            // Actualizar la cuota
            $cuota->update([
                'fecha_pago' => $request->fecha_pago,
                'monto' => $request->monto,
                'pago_capital' => $request->pago_capital,
                'interes' => $request->interes,
                'comision' => $request->comision,
                'gas' => $request->gas ?? 0,
                'igv' => $request->igv,
            ]);

            // Detectar si cambió la fecha para regenerar estados
            $fechaCambio = $valoresOriginales['fecha_pago'] != $cuota->fecha_pago;

            if ($fechaCambio) {
                Log::info('🔄 Fecha de cuota cambiada - regenerando estados', [
                    'cuota_id' => $cuota->id,
                    'fecha_anterior' => $valoresOriginales['fecha_pago'],
                    'fecha_nueva' => $cuota->fecha_pago,
                ]);
            }

            // 💰 PROCESAR ABONOS A FAVOR DESPUÉS DE EDITAR CUOTA
            $this->procesarAbonosFavorDespuesEdicion($cuota, $valoresOriginales);

            // Usar EstadoPrestamoService para recálculo completo y consistente
            $estadoService = app(EstadoPrestamoService::class);
            $resultadoRecalculo = $estadoService->recalcularTodo($cuota->prestamo);

            Log::info('✅ Cuota editada con EstadoPrestamoService', [
                'cuota_id' => $cuota->id,
                'fecha_cambio' => $fechaCambio,
                'resultado_recalculo' => $resultadoRecalculo,
            ]);

            // Registrar auditoría
            $this->registrarAuditoriaEdicion($cuota, $valoresOriginales, $request->justificacion_edicion);

            // Log de la edición
            Log::info('Cuota editada', [
                'cuota_id' => $cuota_id,
                'prestamo_id' => $cuota->prestamo_id,
                'numero_cuota' => $cuota->numero,
                'editado_por' => auth()->user()->name,
                'cambios' => $this->obtenerCambios($valoresOriginales, $cuota),
                'justificacion' => $request->justificacion_edicion,
            ]);

            DB::commit();

            return redirect()->route('admin.prestamos.show', $cuota->prestamo_id)
                ->with('success', 'Cuota editada correctamente.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al editar cuota: '.$e->getMessage());

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Error al editar la cuota: '.$e->getMessage()]);
        }
    }

    /**
     * Verificar restricciones para edición de cuota
     */
    private function verificarRestriccionesEdicion($cuota)
    {
        // No permitir editar cuotas completamente pagadas
        if ($cuota->estado == CuotaEstado::PAGADO) {
            return [
                'bloqueado' => true,
                'mensaje' => 'No se puede editar una cuota que ya está completamente pagada.',
            ];
        }

        // No permitir editar si tiene pagos parciales (requiere más cuidado)
        if ($cuota->estado == CuotaEstado::PARCIAL && $cuota->monto_pagado > 0) {
            return [
                'bloqueado' => true,
                'mensaje' => 'No se puede editar una cuota con pagos parciales. Debe anular los pagos primero.',
            ];
        }

        // Verificar si la cuota está vencida por mucho tiempo
        $diasVencida = Carbon::now()->diffInDays($cuota->fecha_pago, false);
        if ($diasVencida < -90) { // Más de 90 días vencida
            return [
                'bloqueado' => true,
                'mensaje' => 'No se puede editar una cuota vencida por más de 90 días.',
            ];
        }

        return [
            'bloqueado' => false,
            'mensaje' => '',
        ];
    }

    /**
     * MÉTODO ELIMINADO: recalcularEstadoCuota()
     *
     * Este método ha sido reemplazado por EstadoPrestamoService::recalcularTodo()
     * que considera fechas de vencimiento y proporciona mayor consistencia.
     *
     * Fecha de eliminación: 2025-08-30
     * Razón: Unificación con EstadoPrestamoService para considerar fechas correctamente
     *
     * NOTA: Si necesitas recalcular estados, usa:
     * app(EstadoPrestamoService::class)->recalcularTodo($prestamo);
     */

    /**
     * Registrar auditoría de edición
     */
    private function registrarAuditoriaEdicion($cuota, $valoresOriginales, $justificacion)
    {
        // Crear registro de auditoría en la tabla cuotas_audit (si existe)
        // Por ahora solo logueamos, pero se puede crear una tabla de auditoría
        Log::info('Auditoría de edición de cuota', [
            'cuota_id' => $cuota->id,
            'usuario_id' => auth()->id(),
            'usuario_nombre' => auth()->user()->name,
            'valores_originales' => $valoresOriginales,
            'valores_nuevos' => [
                'fecha_pago' => $cuota->fecha_pago,
                'monto' => $cuota->monto,
                'pago_capital' => $cuota->pago_capital,
                'interes' => $cuota->interes,
                'comision' => $cuota->comision,
                'gas' => $cuota->gas,
                'igv' => $cuota->igv,
            ],
            'justificacion' => $justificacion,
            'fecha_edicion' => now(),
        ]);
    }

    /**
     * Obtener cambios realizados
     */
    private function obtenerCambios($valoresOriginales, $cuota)
    {
        $cambios = [];

        $campos = ['fecha_pago', 'monto', 'pago_capital', 'interes', 'comision', 'gas', 'igv'];

        foreach ($campos as $campo) {
            $valorOriginal = $valoresOriginales[$campo];
            $valorNuevo = $cuota->$campo;

            if ($valorOriginal != $valorNuevo) {
                $cambios[$campo] = [
                    'anterior' => $valorOriginal,
                    'nuevo' => $valorNuevo,
                ];
            }
        }

        return $cambios;
    }

    /**
     * Procesar abonos a favor después de editar una cuota
     * Si cambió la fecha de vencimiento, puede afectar las moras y por tanto los abonos a favor
     */
    private function procesarAbonosFavorDespuesEdicion($cuota, $valoresOriginales)
    {
        // Solo procesar si hay abonos a favor en esta cuota
        $abonosFavor = $cuota->abonosMoraFavor()->where('saldo_favor', '>', 0)->get();

        if ($abonosFavor->isEmpty()) {
            return; // No hay abonos a favor que procesar
        }

        Log::info("💰 Procesando {$abonosFavor->count()} abonos a favor después de editar cuota {$cuota->id}");

        // Si cambió la fecha de vencimiento, las moras futuras pueden cambiar
        $fechaCambio = $valoresOriginales['fecha_pago'] != $cuota->fecha_pago;

        if ($fechaCambio) {
            Log::info('📅 Fecha de cuota cambió - verificando aplicación de abonos a favor a moras existentes');

            // Obtener moras existentes que podrían beneficiarse de los abonos a favor
            $morasPendientes = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                ->orderBy('fecha', 'asc')
                ->get();

            foreach ($morasPendientes as $mora) {
                try {
                    $montoAplicado = $cuota->aplicarAbonosFavorAMora($mora);
                    if ($montoAplicado > 0) {
                        Log::info("💰 Aplicado S/{$montoAplicado} de abonos a favor a mora {$mora->id} tras edición de cuota");
                    }
                } catch (\Exception $e) {
                    Log::error("Error aplicando abono a favor a mora {$mora->id} tras edición: ".$e->getMessage());
                }
            }
        }

        // Log resumen de abonos a favor activos
        $saldoTotalFavor = $abonosFavor->sum('saldo_favor');
        Log::info("💰 Abonos a favor activos en cuota {$cuota->id}: S/{$saldoTotalFavor}");
    }
}
