<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador centralizado para el cálculo y actualización del estado de préstamos
 * 
 * Este controlador unifica toda la lógica de recálculo de estado que antes estaba
 * dispersa en múltiples archivos (PrestamosController, ShowPrestamos, EstadoPrestamoService, etc.)
 * 
 * IMPORTANTE: Solo debe usarse cuando es NECESARIO recalcular el estado, no en cada vista.
 */
class EstadoPrestamoController extends Controller
{
    /**
     * Método único y centralizado para calcular y actualizar el estado de un préstamo
     * 
     * @param Prestamo $prestamo El préstamo a evaluar
     * @param bool $actualizarBD Si debe guardar el cambio en la base de datos (default: false)
     * @param string $origen Origen de la llamada para logging (ej: 'pago', 'liquidacion', 'manual')
     * @return array ['estado_anterior', 'estado_calculado', 'fue_actualizado', 'razon']
     */
    public function calcularYActualizarEstado(Prestamo $prestamo, bool $actualizarBD = false, string $origen = 'desconocido'): array
    {
        $estadoAnterior = $prestamo->estado;
        $estadoCalculado = $this->calcularEstadoReal($prestamo);
        $fueActualizado = false;
        $razon = '';

        // Log de la llamada para auditoría
        Log::info("🔍 calcularYActualizarEstado() llamado", [
            'prestamo_id' => $prestamo->id,
            'origen' => $origen,
            'estado_actual' => $estadoAnterior,
            'estado_calculado' => $estadoCalculado,
            'actualizar_bd' => $actualizarBD,
        ]);

        // Determinar si hubo cambio
        if ($estadoAnterior !== $estadoCalculado) {
            $razon = $this->obtenerRazonCambioEstado($prestamo, $estadoCalculado);
            
            // Solo actualizar si se solicita explícitamente
            if ($actualizarBD) {
                $prestamo->update(['estado' => $estadoCalculado]);
                $fueActualizado = true;

                Log::warning("⚠️ ESTADO ACTUALIZADO EN BD", [
                    'prestamo_id' => $prestamo->id,
                    'origen' => $origen,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $estadoCalculado,
                    'razon' => $razon,
                ]);
            } else {
                Log::info("ℹ️ Estado calculado pero NO actualizado (actualizarBD=false)", [
                    'prestamo_id' => $prestamo->id,
                    'estado_calculado' => $estadoCalculado,
                ]);
            }
        } else {
            $razon = 'Estado sin cambios';
            Log::info("✅ Estado sin cambios", [
                'prestamo_id' => $prestamo->id,
                'estado' => $estadoAnterior,
            ]);
        }

        return [
            'estado_anterior' => $estadoAnterior,
            'estado_calculado' => $estadoCalculado,
            'fue_actualizado' => $fueActualizado,
            'razon' => $razon,
            'origen' => $origen,
        ];
    }

    /**
     * Calcula el estado real del préstamo basándose en las condiciones actuales
     * Lógica unificada: usa fecha_pago (no fecha_vencimiento) y moras pendientes
     * 
     * IMPORTANTE: Este método SOLO calcula, NO actualiza la base de datos
     */
    private function calcularEstadoReal(Prestamo $prestamo): string
    {
        // Estados finales que no cambian
        if (in_array($prestamo->estado, ['Cancelado', 'Finalizado'])) {
            return $prestamo->estado;
        }

        // Estados administrativos que se mantienen
        if (in_array($prestamo->estado, ['Nueva Solicitud', 'Por Desembolsar'])) {
            return $prestamo->estado;
        }

        // Verificar convenio activo primero (prioridad alta)
        $tieneConvenioActivo = $prestamo->convenios &&
            $prestamo->convenios->where('estado', \App\Enums\ConvenioEstado::ACTIVO)->count() > 0;

        if ($tieneConvenioActivo) {
            return 'Con Convenio';
        }

        // Verificar si todas las cuotas están completamente pagadas
        $totalCuotas = $prestamo->cuotas->count();
        $cuotasPagadas = $prestamo->cuotas->where('estado', CuotaEstado::PAGADO)->count();

        // IMPORTANTE: Solo marcar como Liquidado si NO HAY MORAS PENDIENTES
        $todasCuotasPagadas = ($totalCuotas > 0 && $cuotasPagadas == $totalCuotas);

        // Verificar cuotas vencidas (fecha_pago < hoy) y no pagadas
        $hoy = Carbon::today();
        $cuotasVencidas = $prestamo->cuotas->filter(function ($cuota) use ($hoy) {
            return in_array($cuota->estado, [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL, CuotaEstado::VENCIDO]) &&
                   Carbon::parse($cuota->fecha_pago)->lt($hoy);
        });

        // Si hay cuotas vencidas, es Moroso (independientemente de las moras)
        if ($cuotasVencidas->count() > 0) {
            return 'Moroso';
        }

        // Verificar si tiene moras pendientes
        $tieneMorasPendientes = false;
        foreach ($prestamo->cuotas as $cuota) {
            if ($cuota->moras_pendientes && $cuota->moras_pendientes->count() > 0) {
                $tieneMorasPendientes = true;
                break;
            }
        }

        // Solo aquí podemos marcar como Liquidado si todas las cuotas están pagadas Y no hay moras
        if ($todasCuotasPagadas && !$tieneMorasPendientes) {
            return 'Liquidado';
        }

        // Si tiene cuotas pendientes pero ninguna vencida
        $cuotasPendientes = $prestamo->cuotas->whereIn('estado', [
            CuotaEstado::PENDIENTE,
            CuotaEstado::PARCIAL,
        ])->count();

        if ($cuotasPendientes > 0) {
            // NUEVO: Si está al día con las cuotas pero tiene moras pendientes
            if ($tieneMorasPendientes) {
                return 'Vigente con moras';
            }
            // Cuotas pendientes pero sin moras
            return 'Vigente';
        }

        // Si todas las cuotas están pagadas pero tiene moras pendientes
        if ($todasCuotasPagadas && $tieneMorasPendientes) {
            return 'Vigente con moras';
        }

        // Por defecto, mantener el estado actual
        return $prestamo->estado;
    }

    /**
     * Obtiene una explicación detallada de por qué cambió el estado
     */
    private function obtenerRazonCambioEstado(Prestamo $prestamo, string $nuevoEstado): string
    {
        $razones = [];

        // Analizar cuotas
        $totalCuotas = $prestamo->cuotas->count();
        $cuotasPagadas = $prestamo->cuotas->where('estado', CuotaEstado::PAGADO)->count();
        $cuotasPendientes = $prestamo->cuotas->whereIn('estado', [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL])->count();
        
        $hoy = Carbon::today();
        $cuotasVencidas = $prestamo->cuotas->filter(function ($cuota) use ($hoy) {
            return in_array($cuota->estado, [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL, CuotaEstado::VENCIDO]) &&
                   Carbon::parse($cuota->fecha_pago)->lt($hoy);
        })->count();

        // Analizar moras
        $morasPendientes = 0;
        foreach ($prestamo->cuotas as $cuota) {
            $morasPendientes += $cuota->moras_pendientes ? $cuota->moras_pendientes->count() : 0;
        }

        // Construir razón según el nuevo estado
        switch ($nuevoEstado) {
            case 'Liquidado':
                $razones[] = "Todas las cuotas pagadas ({$cuotasPagadas}/{$totalCuotas})";
                $razones[] = "Sin moras pendientes";
                break;

            case 'Moroso':
                if ($cuotasVencidas > 0) {
                    $razones[] = "{$cuotasVencidas} cuota(s) vencida(s)";
                }
                if ($morasPendientes > 0) {
                    $razones[] = "{$morasPendientes} mora(s) pendiente(s)";
                }
                break;

            case 'Vigente con moras':
                $razones[] = "Cuotas al día ({$cuotasPagadas} pagadas, {$cuotasPendientes} pendientes sin vencer)";
                $razones[] = "{$morasPendientes} mora(s) pendiente(s) de cuotas anteriores";
                break;

            case 'Vigente':
                $razones[] = "{$cuotasPagadas} cuota(s) pagada(s), {$cuotasPendientes} pendiente(s)";
                $razones[] = "Sin cuotas vencidas ni moras pendientes";
                break;

            case 'Con Convenio':
                $razones[] = "Tiene convenio de pago activo";
                break;

            default:
                $razones[] = "Estado mantenido por condiciones específicas";
        }

        return implode(', ', $razones);
    }

    /**
     * Endpoint HTTP para recalcular manualmente el estado de un préstamo
     * Uso: POST /admin/prestamos/{id}/recalcular-estado
     */
    public function recalcularEstadoManual(Request $request, $id)
    {
        try {
            $prestamo = Prestamo::with(['cuotas.moras_pendientes', 'convenios'])->findOrFail($id);

            $resultado = $this->calcularYActualizarEstado(
                $prestamo,
                $request->boolean('actualizar', false), // Solo actualizar si se envía ?actualizar=true
                'manual_http'
            );

            return response()->json([
                'success' => true,
                'prestamo_id' => $prestamo->id,
                'resultado' => $resultado,
            ]);

        } catch (\Exception $e) {
            Log::error("Error al recalcular estado del préstamo {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recalcula estados de múltiples préstamos (para procesos batch)
     */
    public function recalcularEstadosMultiples(array $prestamoIds, bool $actualizarBD = false, string $origen = 'batch'): array
    {
        $resultados = [];

        foreach ($prestamoIds as $prestamoId) {
            try {
                $prestamo = Prestamo::with(['cuotas.moras_pendientes', 'convenios'])->find($prestamoId);
                
                if (!$prestamo) {
                    $resultados[$prestamoId] = [
                        'success' => false,
                        'error' => 'Préstamo no encontrado',
                    ];
                    continue;
                }

                $resultado = $this->calcularYActualizarEstado($prestamo, $actualizarBD, $origen);
                $resultados[$prestamoId] = [
                    'success' => true,
                    'resultado' => $resultado,
                ];

            } catch (\Exception $e) {
                $resultados[$prestamoId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $resultados;
    }

    /**
     * Obtiene el estado calculado SIN actualizar la base de datos
     * Útil para previsualización o validación
     */
    public function obtenerEstadoCalculado(Prestamo $prestamo): string
    {
        return $this->calcularEstadoReal($prestamo);
    }
}
