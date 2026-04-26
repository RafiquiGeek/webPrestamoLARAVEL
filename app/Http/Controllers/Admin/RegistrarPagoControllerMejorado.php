<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Operacion;
use App\Services\EstadoPrestamoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarPagoControllerMejorado extends Controller
{
    protected EstadoPrestamoService $estadoService;

    public function __construct(EstadoPrestamoService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    /**
     * Método mejorado para actualizar operaciones
     */
    public function updateMejorado(Request $request, $operacion_id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'metodo_pago_id' => 'required|exists:metodos_de_pago,id',
            'cuenta_id' => 'nullable|exists:cuentas,id',
            'numero_operacion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'justificacion_edicion' => 'required|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $operacion_id) {
            try {
                $operacion = Operacion::with(['prestamo', 'cuotas', 'morasCuota'])->findOrFail($operacion_id);

                // 1. Validaciones de negocio
                $this->validarOperacionParaEdicion($operacion);

                // 2. Validar integridad ANTES de hacer cambios
                $integridad = $this->estadoService->validarIntegridad($operacion->prestamo);
                if (! $integridad['valido'] && count($integridad['errores']) > 3) {
                    throw new \Exception('El préstamo tiene problemas de integridad que deben resolverse primero. Ejecute el comando de reparación.');
                }

                Log::info("Iniciando edición de operación {$operacion->id}", [
                    'operacion_id' => $operacion->id,
                    'monto_anterior' => $operacion->abono,
                    'monto_nuevo' => $request->monto,
                    'usuario' => Auth::id(),
                ]);

                // 3. Reversar efectos de la operación actual
                $this->estadoService->reversarOperacion($operacion);

                // 4. Actualizar datos de la operación
                $operacion->update([
                    'abono' => $request->monto,
                    'fecha' => $request->fecha,
                    'metodo_pago_id' => $request->metodo_pago_id,
                    'cuenta_id' => $request->cuenta_id,
                    'nro_operacion' => $request->numero_operacion,
                    'comentario' => $request->observaciones,
                    'justificacion_edicion' => $request->justificacion_edicion,
                    'editado_por' => Auth::id(),
                    'editado_en' => now(),
                ]);

                // 5. Reaplicar la operación con nuevos valores
                $this->reaplicarOperacion($operacion, $request->monto);

                // 6. Recalcular todo el préstamo
                $resultadoRecalculo = $this->estadoService->recalcularTodo($operacion->prestamo);

                Log::info('Edición de operación completada exitosamente', [
                    'operacion_id' => $operacion->id,
                    'resultados_recalculo' => $resultadoRecalculo,
                ]);

                return redirect()->route('admin.prestamos.show', $operacion->prestamo_id)
                    ->with('success', 'Pago actualizado correctamente. '.
                           "Cuotas actualizadas: {$resultadoRecalculo['cuotas_actualizadas']}, ".
                           "Estado préstamo: {$resultadoRecalculo['estado_nuevo']}");

            } catch (\Exception $e) {
                Log::error("Error al editar operación {$operacion_id}: ".$e->getMessage(), [
                    'operacion_id' => $operacion_id,
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->withErrors(['error' => 'Error al actualizar el pago: '.$e->getMessage()]);
            }
        });
    }

    /**
     * Método mejorado para anular operaciones
     */
    public function anularMejorado(Request $request, $operacion_id)
    {
        $request->validate([
            'justificacion_anulacion' => 'required|string|max:500',
        ]);

        return DB::transaction(function () use ($request, $operacion_id) {
            try {
                $operacion = Operacion::with(['prestamo', 'cuotas', 'morasCuota'])->findOrFail($operacion_id);

                // 1. Validaciones de negocio
                $this->validarOperacionParaAnulacion($operacion);

                Log::info("Iniciando anulación de operación {$operacion->id}", [
                    'tipo_operacion' => $operacion->tipo_operacion,
                    'monto' => $operacion->abono,
                    'usuario' => Auth::id(),
                ]);

                // 2. Usar servicio centralizado que se encarga de:
                // - Eliminar relaciones pivote (detach)
                // - Recalcular estados de cuotas y moras
                // - Marcar operación como anulada
                // - Actualizar estado del préstamo
                $resultado = $this->estadoService->anularOperacion(
                    $operacion,
                    $request->justificacion_anulacion,
                    Auth::id()
                );

                // 3. Lógica específica para desembolsos
                if ($operacion->tipo_operacion === 'Desembolso') {
                    $operacion->prestamo->update(['estado' => 'Por Desembolsar']);
                    Log::info("Préstamo {$operacion->prestamo_id} revertido a estado 'Por Desembolsar'");
                }

                $tipoOperacion = $operacion->tipo_operacion === 'Desembolso' ? 'desembolso' : 'pago';

                return redirect()->route('admin.prestamos.show', $operacion->prestamo_id)
                    ->with('success', ucfirst($tipoOperacion).' anulado correctamente. '.
                           'Cuotas afectadas: '.count($resultado['cuotas_afectadas'] ?? []).', '.
                           'Moras afectadas: '.count($resultado['moras_afectadas'] ?? []).'. '.
                           'Relaciones eliminadas completamente.');

            } catch (\Exception $e) {
                Log::error("Error al anular operación {$operacion_id}: ".$e->getMessage(), [
                    'operacion_id' => $operacion_id,
                    'trace' => $e->getTraceAsString(),
                ]);

                return redirect()->back()
                    ->withErrors(['error' => 'Error al anular la operación: '.$e->getMessage()]);
            }
        });
    }

    /**
     * Validaciones para edición
     */
    private function validarOperacionParaEdicion(Operacion $operacion): void
    {
        $tiposPermitidos = ['Desembolso', 'Pago de cuota', 'Pago de mora', 'Pago general'];
        if (! in_array($operacion->tipo_operacion, $tiposPermitidos)) {
            throw new \Exception('Solo se pueden editar operaciones de pago o desembolso.');
        }

        if ($operacion->estado === 'anulado') {
            throw new \Exception('No se puede editar una operación anulada.');
        }

        // Validación específica para desembolsos
        if ($operacion->tipo_operacion === 'Desembolso') {
            $this->validarDesembolsoParaEdicion($operacion);
        }
    }

    /**
     * Validaciones para anulación
     */
    private function validarOperacionParaAnulacion(Operacion $operacion): void
    {
        $tiposPermitidos = ['Desembolso', 'Pago de cuota', 'Pago de mora', 'Pago general'];
        if (! in_array($operacion->tipo_operacion, $tiposPermitidos)) {
            throw new \Exception('Solo se pueden anular operaciones de pago o desembolso.');
        }

        if ($operacion->estado === 'anulado') {
            throw new \Exception('La operación ya está anulada.');
        }

        // Validación específica para desembolsos
        if ($operacion->tipo_operacion === 'Desembolso') {
            $this->validarDesembolsoParaAnulacion($operacion);
        }
    }

    /**
     * Validar desembolso para edición
     */
    private function validarDesembolsoParaEdicion(Operacion $operacion): void
    {
        $prestamo = $operacion->prestamo;
        $cuotasCount = $prestamo->cuotas()->count();

        if ($cuotasCount > 0) {
            // Verificar si alguna cuota tiene pagos
            $cuotasConPagos = $prestamo->cuotas()
                ->where('monto_pagado', '>', 0)
                ->count();

            if ($cuotasConPagos > 0) {
                throw new \Exception('No se puede editar el desembolso porque el préstamo ya tiene pagos registrados en las cuotas.');
            }

            throw new \Exception('No se puede editar el desembolso porque el préstamo ya tiene cuotas generadas.');
        }
    }

    /**
     * Validar desembolso para anulación
     */
    private function validarDesembolsoParaAnulacion(Operacion $operacion): void
    {
        $prestamo = $operacion->prestamo;
        $cuotasCount = $prestamo->cuotas()->count();

        if ($cuotasCount > 0) {
            $cuotasConPagos = $prestamo->cuotas()
                ->where('monto_pagado', '>', 0)
                ->count();

            if ($cuotasConPagos > 0) {
                throw new \Exception('No se puede anular el desembolso porque el préstamo ya tiene pagos registrados en las cuotas.');
            }

            throw new \Exception('No se puede anular el desembolso porque el préstamo ya tiene cuotas generadas. Elimine primero las cuotas si es necesario.');
        }
    }

    /**
     * Reaplicar operación con nuevo monto
     */
    private function reaplicarOperacion(Operacion $operacion, float $nuevoMonto): void
    {
        switch ($operacion->tipo_operacion) {
            case 'Pago de cuota':
            case 'Pago general':
                $this->reaplicarPagoCuotas($operacion, $nuevoMonto);
                break;

            case 'Pago de mora':
                $this->reaplicarPagoMoras($operacion, $nuevoMonto);
                break;

            case 'Desembolso':
                // Los desembolsos no requieren reaplicación de distribución
                break;
        }
    }

    /**
     * Reaplicar pago a cuotas con nueva distribución
     */
    private function reaplicarPagoCuotas(Operacion $operacion, float $monto): void
    {
        $prestamo = $operacion->prestamo;

        // Obtener cuotas pendientes ordenadas por fecha
        $cuotasPendientes = $prestamo->cuotas()
            ->whereIn('estado', [\App\Enums\CuotaEstado::PENDIENTE, \App\Enums\CuotaEstado::PARCIAL])
            ->orderBy('fecha_pago')
            ->get();

        if ($cuotasPendientes->isEmpty()) {
            Log::warning("No hay cuotas pendientes para aplicar el pago de operación {$operacion->id}");

            return;
        }

        $montoRestante = $monto;

        foreach ($cuotasPendientes as $cuota) {
            if ($montoRestante <= 0) {
                break;
            }

            $saldoCuota = $cuota->monto - $cuota->monto_pagado;
            $montoAplicar = min($montoRestante, $saldoCuota);

            // Crear relación en pivot con el nuevo campo monto_aplicado
            $operacion->cuotas()->attach($cuota->id, [
                'monto_aplicado' => $montoAplicar,
                'concepto' => 'pago_cuota',
                'observaciones' => "Pago editado - Monto aplicado: $montoAplicar",
                'aplicado_en' => now(),
            ]);

            $montoRestante -= $montoAplicar;

            Log::info("Reaplicado pago a cuota {$cuota->id}: $montoAplicar");
        }

        if ($montoRestante > 0) {
            Log::warning("Sobró monto al reaplicar pago: $montoRestante para operación {$operacion->id}");
        }
    }

    /**
     * Reaplicar pago a moras
     */
    private function reaplicarPagoMoras(Operacion $operacion, float $monto): void
    {
        $prestamo = $operacion->prestamo;

        // Obtener moras pendientes
        $morasPendientes = \App\Models\MoraCuota::whereHas('cuota', function ($q) use ($prestamo) {
            $q->where('prestamo_id', $prestamo->id);
        })
            ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE, \App\Enums\MoraCuotaEstado::PARCIAL])
            ->orderBy('fecha')
            ->get();

        if ($morasPendientes->isEmpty()) {
            Log::warning("No hay moras pendientes para aplicar el pago de operación {$operacion->id}");

            return;
        }

        $montoRestante = $monto;

        foreach ($morasPendientes as $mora) {
            if ($montoRestante <= 0) {
                break;
            }

            $saldoMora = $mora->monto - $mora->monto_pagado;
            $montoAplicar = min($montoRestante, $saldoMora);

            // Crear relación en pivot para moras
            $operacion->morasCuota()->attach($mora->id, [
                'monto_aplicado' => $montoAplicar,
                'observaciones' => "Pago de mora editado - Monto aplicado: $montoAplicar",
                'aplicado_en' => now(),
            ]);

            $montoRestante -= $montoAplicar;

            Log::info("Reaplicado pago a mora {$mora->id}: $montoAplicar");
        }
    }
}
