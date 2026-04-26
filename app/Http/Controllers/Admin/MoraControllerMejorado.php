<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mora;
use App\Models\MoraCuota;
use App\Models\Operacion;
use App\Services\EstadoPrestamoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoraControllerMejorado extends Controller
{
    protected EstadoPrestamoService $estadoService;

    public function __construct(EstadoPrestamoService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    /**
     * Método mejorado para editar moras con validaciones de integridad
     */
    public function editarMoraMejorado(Request $request, $moraId)
    {
        $request->validate([
            'monto_total_pago' => 'required|numeric|min:0',
            'fecha_pago' => 'required|date',
            'justificacion' => 'required|string|min:10',
            'metodo_pago_id' => 'nullable|exists:metodos_de_pago,id',
            'numero_operacion' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $moraId) {
            try {
                $mora = MoraCuota::with([
                    'operaciones',
                    'cuota.prestamo',
                    'operaciones.prestamo',
                ])->findOrFail($moraId);

                // 1. Validaciones de negocio
                $this->validarMoraParaEdicion($mora);

                // 2. Validar integridad del préstamo
                $integridad = $this->estadoService->validarIntegridad($mora->cuota->prestamo);
                if (! $integridad['valido'] && count($integridad['errores']) > 3) {
                    throw new \Exception('El préstamo tiene problemas de integridad críticos. Ejecute el comando de reparación primero.');
                }

                Log::info("Iniciando edición de mora {$moraId}", [
                    'mora_id' => $moraId,
                    'monto_anterior' => $mora->monto_pagado,
                    'monto_nuevo' => $request->monto_total_pago,
                    'prestamo_id' => $mora->cuota->prestamo_id,
                    'usuario' => Auth::id(),
                ]);

                // 3. Obtener operación principal asociada
                $operacion = $mora->operaciones()->where('estado', '!=', 'anulado')->first();

                if (! $operacion) {
                    // Si no hay operación, crear una nueva
                    $operacion = $this->crearOperacionParaMora($mora, $request);
                } else {
                    // Editar operación existente
                    $this->editarOperacionMora($operacion, $request);
                }

                // 4. Recalcular solo esta mora primero
                $cambiosMora = $this->estadoService->recalcularMora($mora);

                // 5. Recalcular toda la cuota y préstamo
                $cambiosCuota = $this->estadoService->recalcularCuota($mora->cuota);

                // 6. Actualizar estado del préstamo si es necesario
                $prestamo = $mora->cuota->prestamo;
                $estadoAnterior = $prestamo->estado;
                $resultadoCompleto = $this->estadoService->recalcularTodo($prestamo);

                Log::info('Edición de mora completada exitosamente', [
                    'mora_id' => $moraId,
                    'operacion_id' => $operacion->id,
                    'cambios_mora' => $cambiosMora,
                    'cambios_cuota' => $cambiosCuota,
                    'estado_prestamo' => "{$estadoAnterior} → {$prestamo->fresh()->estado}",
                ]);

                return redirect()->route('admin.prestamos.show', $prestamo->id)
                    ->with('success', 'Mora editada exitosamente. '.
                           'Mora actualizada: '.($cambiosMora['hubo_cambios'] ? 'Sí' : 'No').', '.
                           "Estado mora: {$cambiosMora['estado_nuevo']->name}, ".
                           "Estado préstamo: {$prestamo->fresh()->estado}");

            } catch (\Exception $e) {
                Log::error("Error editando mora {$moraId}: ".$e->getMessage(), [
                    'mora_id' => $moraId,
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->withErrors(['error' => 'Error al editar la mora: '.$e->getMessage()]);
            }
        });
    }

    /**
     * Método mejorado para anular operaciones de mora
     */
    public function anularPagoMora(Request $request, $operacionId)
    {
        $request->validate([
            'justificacion_anulacion' => 'required|string|min:10|max:500',
        ]);

        return DB::transaction(function () use ($request, $operacionId) {
            try {
                $operacion = Operacion::with(['morasCuota', 'prestamo'])->findOrFail($operacionId);

                // Validar que sea una operación de mora
                if ($operacion->tipo_operacion !== 'Pago de mora') {
                    throw new \Exception('Solo se pueden anular operaciones de pago de mora.');
                }

                if ($operacion->estado === 'anulado') {
                    throw new \Exception('La operación ya está anulada.');
                }

                Log::info('Iniciando anulación de pago de mora', [
                    'operacion_id' => $operacionId,
                    'monto' => $operacion->abono,
                    'moras_relacionadas' => $operacion->morasCuota->count(),
                    'usuario' => Auth::id(),
                ]);

                // Reversar efectos de la operación
                $resultadoReversion = $this->estadoService->reversarOperacion($operacion);

                // Marcar operación como anulada
                $operacion->update([
                    'estado' => 'anulado',
                    'justificacion_anulacion' => $request->justificacion_anulacion,
                    'anulado_por' => Auth::id(),
                    'anulado_en' => now(),
                ]);

                // Recalcular préstamo completo
                $resultadoRecalculo = $this->estadoService->recalcularTodo($operacion->prestamo);

                Log::info('Anulación de pago de mora completada', [
                    'operacion_id' => $operacionId,
                    'reversion' => $resultadoReversion,
                    'recalculo' => $resultadoRecalculo,
                ]);

                return redirect()->route('admin.prestamos.show', $operacion->prestamo_id)
                    ->with('success', 'Pago de mora anulado correctamente. '.
                           'Moras afectadas: '.count($resultadoReversion['moras_afectadas']).', '.
                           "Cuotas actualizadas: {$resultadoRecalculo['cuotas_actualizadas']}");

            } catch (\Exception $e) {
                Log::error("Error anulando pago de mora {$operacionId}: ".$e->getMessage());

                return redirect()->back()
                    ->withErrors(['error' => 'Error al anular el pago de mora: '.$e->getMessage()]);
            }
        });
    }

    /**
     * Regularizar mora - marcar como regularizada sin pago
     */
    public function regularizarMora(Request $request, $moraId)
    {
        $request->validate([
            'justificacion_regularizacion' => 'required|string|min:10|max:500',
        ]);

        return DB::transaction(function () use ($request, $moraId) {
            try {
                $mora = MoraCuota::with(['cuota.prestamo'])->findOrFail($moraId);

                // Validar que se pueda regularizar
                if ($mora->estado === \App\Enums\MoraCuotaEstado::PAGADO) {
                    throw new \Exception('No se puede regularizar una mora ya pagada.');
                }

                if ($mora->estado === \App\Enums\MoraCuotaEstado::REGULARIZADA) {
                    throw new \Exception('La mora ya está regularizada.');
                }

                Log::info("Regularizando mora {$moraId}", [
                    'mora_id' => $moraId,
                    'monto' => $mora->monto,
                    'estado_anterior' => $mora->estado->name,
                    'usuario' => Auth::id(),
                ]);

                // Marcar como regularizada
                $mora->update([
                    'estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA,
                ]);

                // Crear registro de la regularización (opcional - en operaciones)
                $operacionRegularizacion = Operacion::create([
                    'cliente_id' => $mora->cuota->prestamo->cliente_id,
                    'prestamo_id' => $mora->cuota->prestamo_id,
                    'fecha' => now(),
                    'tipo_operacion' => 'Regularización de mora',
                    'abono' => 0.00,
                    'comentario' => "Mora regularizada: {$request->justificacion_regularizacion}",
                    'user_id' => Auth::id(),
                    'estado' => 'completado',
                ]);

                // Relacionar operación con la mora
                $operacionRegularizacion->morasCuota()->attach($mora->id, [
                    'monto_aplicado' => 0.00,
                    'observaciones' => 'Regularización administrativa',
                    'aplicado_en' => now(),
                ]);

                // Recalcular estado del préstamo
                $resultadoRecalculo = $this->estadoService->recalcularTodo($mora->cuota->prestamo);

                Log::info('Mora regularizada exitosamente', [
                    'mora_id' => $moraId,
                    'operacion_regularizacion_id' => $operacionRegularizacion->id,
                    'resultado_recalculo' => $resultadoRecalculo,
                ]);

                return redirect()->route('admin.prestamos.show', $mora->cuota->prestamo_id)
                    ->with('success', 'Mora regularizada exitosamente. '.
                           "Estado préstamo: {$resultadoRecalculo['estado_nuevo']}");

            } catch (\Exception $e) {
                Log::error("Error regularizando mora {$moraId}: ".$e->getMessage());

                return redirect()->back()
                    ->withErrors(['error' => 'Error al regularizar la mora: '.$e->getMessage()]);
            }
        });
    }

    /**
     * Validar mora para edición
     */
    private function validarMoraParaEdicion(MoraCuota $mora): void
    {
        if ($mora->estado === \App\Enums\MoraCuotaEstado::REGULARIZADA) {
            throw new \Exception('No se puede editar una mora regularizada.');
        }

        // Validar que la cuota padre esté en estado válido
        $cuota = $mora->cuota;
        if (! $cuota) {
            throw new \Exception('La mora no tiene una cuota asociada válida.');
        }

        if ($cuota->prestamo->estado === 'Finalizado') {
            throw new \Exception('No se puede editar moras de un préstamo finalizado.');
        }
    }

    /**
     * Crear nueva operación para mora
     */
    private function crearOperacionParaMora(MoraCuota $mora, Request $request): Operacion
    {
        $operacion = Operacion::create([
            'cliente_id' => $mora->cuota->prestamo->cliente_id,
            'prestamo_id' => $mora->cuota->prestamo_id,
            'fecha' => $request->fecha_pago,
            'metodo_pago_id' => $request->metodo_pago_id,
            'tipo_operacion' => 'Pago de mora',
            'abono' => $request->monto_total_pago,
            'nro_operacion' => $request->numero_operacion,
            'comentario' => "Pago de mora creado: {$request->justificacion}",
            'user_id' => Auth::id(),
            'estado' => 'completado',
        ]);

        // Relacionar con la mora
        $operacion->morasCuota()->attach($mora->id, [
            'monto_aplicado' => min($request->monto_total_pago, $mora->monto),
            'observaciones' => $request->justificacion,
            'aplicado_en' => now(),
        ]);

        return $operacion;
    }

    /**
     * Editar operación existente de mora
     */
    private function editarOperacionMora(Operacion $operacion, Request $request): void
    {
        // Guardar valores originales para auditoría y detección de cambios
        $valoresOriginales = [
            'abono' => $operacion->abono,
            'fecha' => $operacion->fecha,
            'metodo_pago_id' => $operacion->metodo_pago_id,
            'nro_operacion' => $operacion->nro_operacion,
            'comentario' => $operacion->comentario,
        ];

        $operacion->update([
            'abono' => $request->monto_total_pago,
            'fecha' => $request->fecha_pago,
            'metodo_pago_id' => $request->metodo_pago_id,
            'nro_operacion' => $request->numero_operacion,
            'comentario' => "Pago de mora editado: {$request->justificacion}",
            'justificacion_edicion' => $request->justificacion,
            'editado_por' => Auth::id(),
            'editado_en' => now(),
        ]);

        // Detectar cambios importantes para regenerar estados
        $montoChanged = $valoresOriginales['abono'] != $operacion->abono;
        $fechaChanged = $valoresOriginales['fecha'] != $operacion->fecha;

        if ($montoChanged) {
            Log::info('💰 Monto de mora cambiado - regenerando estados', [
                'operacion_id' => $operacion->id,
                'monto_anterior' => $valoresOriginales['abono'],
                'monto_nuevo' => $operacion->abono,
            ]);
        }

        if ($fechaChanged) {
            Log::info('📅 Fecha de mora cambiada - regenerando estados', [
                'operacion_id' => $operacion->id,
                'fecha_anterior' => $valoresOriginales['fecha'],
                'fecha_nueva' => $operacion->fecha,
            ]);
        }

        // Registrar auditoría de la edición
        $this->registrarAuditoriaEdicionMora($operacion, $valoresOriginales, $request->justificacion);

        // Actualizar relaciones pivot - primero eliminar las existentes
        $operacion->morasCuota()->detach();

        // Redistribuir el monto entre las moras relacionadas
        $morasRelacionadas = $operacion->morasCuota()->get();
        $montoRestante = $request->monto_total_pago;

        foreach ($morasRelacionadas as $mora) {
            if ($montoRestante <= 0) {
                break;
            }

            $saldoMora = $mora->monto - $mora->monto_pagado;
            $montoAplicar = min($montoRestante, $saldoMora);

            $operacion->morasCuota()->attach($mora->id, [
                'monto_aplicado' => $montoAplicar,
                'observaciones' => "Edición: {$request->justificacion}",
                'aplicado_en' => now(),
            ]);

            $montoRestante -= $montoAplicar;
        }
    }

    /**
     * Registrar auditoría de edición de mora
     */
    private function registrarAuditoriaEdicionMora($operacion, $valoresOriginales, $justificacion)
    {
        Log::info('Auditoría de edición de mora', [
            'operacion_id' => $operacion->id,
            'prestamo_id' => $operacion->prestamo_id,
            'tipo_operacion' => $operacion->tipo_operacion,
            'usuario_id' => Auth::id(),
            'usuario_nombre' => Auth::user()->name,
            'valores_originales' => $valoresOriginales,
            'valores_nuevos' => [
                'abono' => $operacion->abono,
                'fecha' => $operacion->fecha,
                'metodo_pago_id' => $operacion->metodo_pago_id,
                'nro_operacion' => $operacion->nro_operacion,
                'comentario' => $operacion->comentario,
            ],
            'justificacion' => $justificacion,
            'fecha_edicion' => now(),
        ]);
    }
}
