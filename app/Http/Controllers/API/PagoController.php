<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Operacion;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagoController extends Controller
{
    /**
     * Registrar pago de cuota
     */
    public function registrarPago(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cuota_id' => 'required|exists:cuotas,id',
                'monto_pago' => 'required|numeric|min:0.01',
                'metodo_pago' => 'required|in:efectivo,transferencia,yape,plin,cheque,deposito',
                'numero_operacion' => 'nullable|string|max:50',
                'observaciones' => 'nullable|string|max:500',
                'comprobante_pago' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $cuota = Cuota::with(['prestamo', 'moraCuotas'])->find($request->cuota_id);

            if (! $cuota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cuota no encontrada',
                ], 404);
            }

            if ($cuota->prestamo->estado !== 'desembolsado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El préstamo debe estar desembolsado para registrar pagos',
                ], 400);
            }

            DB::beginTransaction();

            $montoPago = $request->monto_pago;

            // Calcular monto total adeudado (cuota + moras)
            $moraPendiente = $cuota->moraCuotas()->where('estado', 0)->sum('monto');
            $cuotaPendiente = $cuota->monto_cuota - $cuota->monto_pagado;
            $totalAdeudado = $cuotaPendiente + $moraPendiente;

            if ($montoPago > $totalAdeudado) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto a pagar ({$montoPago}) excede la deuda total ({$totalAdeudado})",
                ], 400);
            }

            // Subir comprobante si existe
            $comprobantePath = null;
            if ($request->hasFile('comprobante_pago')) {
                $comprobantePath = $request->file('comprobante_pago')
                    ->store('pagos', 'public');
            }

            // Distribuir el pago: primero moras, luego cuota
            $montoRestante = $montoPago;

            // Pagar moras primero
            if ($moraPendiente > 0 && $montoRestante > 0) {
                $moras = $cuota->moraCuotas()->where('estado', 0)->orderBy('fecha_generacion')->get();

                foreach ($moras as $mora) {
                    if ($montoRestante <= 0) {
                        break;
                    }

                    $pagoMora = min($mora->monto, $montoRestante);

                    $mora->update([
                        'monto_pagado' => $mora->monto_pagado + $pagoMora,
                        'estado' => ($mora->monto_pagado + $pagoMora >= $mora->monto) ? 2 : 1, // 2=PAGADO, 1=PARCIAL
                        'fecha_pago' => now(),
                    ]);

                    $montoRestante -= $pagoMora;
                }
            }

            // Pagar cuota con el monto restante
            if ($montoRestante > 0) {
                $nuevoPagadoCuota = $cuota->monto_pagado + $montoRestante;
                $nuevoEstado = $this->determinarEstadoCuota($nuevoPagadoCuota, $cuota->monto_cuota);

                $cuota->update([
                    'monto_pagado' => $nuevoPagadoCuota,
                    'estado' => $nuevoEstado,
                    'fecha_pago' => now(),
                ]);
            }

            // Registrar operación
            $operacion = Operacion::create([
                'prestamo_id' => $cuota->prestamo_id,
                'cuota_id' => $cuota->id,
                'tipo' => 'pago',
                'metodo_pago' => $request->metodo_pago,
                'monto' => $montoPago,
                'fecha' => now(),
                'usuario_id' => auth()->id(),
                'numero_operacion' => $request->numero_operacion,
                'observaciones' => $request->observaciones ?? "Pago de cuota #{$cuota->numero_cuota}",
                'comprobante_pago' => $comprobantePath,
                'estado' => 'activo',
            ]);

            // Verificar si el préstamo está completamente pagado
            $this->verificarPrestamoCompleto($cuota->prestamo);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
                'data' => [
                    'operacion' => $operacion->load('cuota'),
                    'cuota_actualizada' => $cuota->fresh(['moraCuotas']),
                    'prestamo' => $cuota->prestamo->fresh(['cuotas']),
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar pagos de un préstamo
     */
    public function listarPagos($prestamoId, Request $request): JsonResponse
    {
        try {
            $prestamo = Prestamo::find($prestamoId);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            $query = Operacion::with(['cuota', 'user'])
                ->where('prestamo_id', $prestamoId)
                ->where('tipo', 'pago')
                ->where('estado', 'activo');

            // Filtros
            if ($request->has('fecha_desde')) {
                $query->where('fecha', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha', '<=', $request->fecha_hasta);
            }

            if ($request->has('metodo_pago')) {
                $query->where('metodo_pago', $request->metodo_pago);
            }

            $perPage = $request->get('per_page', 15);
            $pagos = $query->orderBy('fecha', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $pagos->items(),
                'pagination' => [
                    'current_page' => $pagos->currentPage(),
                    'last_page' => $pagos->lastPage(),
                    'per_page' => $pagos->perPage(),
                    'total' => $pagos->total(),
                ],
                'resumen' => [
                    'total_pagado' => $pagos->sum('monto'),
                    'numero_pagos' => $pagos->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pagos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener detalle de pago específico
     */
    public function detallePago($operacionId): JsonResponse
    {
        try {
            $operacion = Operacion::with([
                'prestamo.cliente.persona',
                'cuota',
                'user',
            ])->find($operacionId);

            if (! $operacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado',
                ], 404);
            }

            if ($operacion->tipo !== 'pago') {
                return response()->json([
                    'success' => false,
                    'message' => 'La operación especificada no es un pago',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $operacion,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el detalle del pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Anular pago
     */
    public function anularPago(Request $request, $operacionId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'motivo_anulacion' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El motivo de anulación es requerido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $operacion = Operacion::with(['cuota', 'prestamo'])->find($operacionId);

            if (! $operacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado',
                ], 404);
            }

            if ($operacion->tipo !== 'pago' || $operacion->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden anular pagos activos',
                ], 400);
            }

            DB::beginTransaction();

            // Revertir efectos del pago en la cuota
            $cuota = $operacion->cuota;
            $nuevoPagado = $cuota->monto_pagado - $operacion->monto;
            $nuevoEstado = $this->determinarEstadoCuota($nuevoPagado, $cuota->monto_cuota);

            $cuota->update([
                'monto_pagado' => max(0, $nuevoPagado),
                'estado' => $nuevoEstado,
                'fecha_pago' => $nuevoPagado > 0 ? $cuota->fecha_pago : null,
            ]);

            // Anular la operación
            $operacion->update([
                'estado' => 'anulado',
                'fecha_anulacion' => now(),
                'anulado_por' => auth()->id(),
                'motivo_anulacion' => $request->motivo_anulacion,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago anulado exitosamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al anular el pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener cuotas pendientes de un préstamo
     */
    public function cuotasPendientes($prestamoId): JsonResponse
    {
        try {
            $prestamo = Prestamo::with([
                'cuotas' => function ($query) {
                    $query->with('moraCuotas')
                        ->where('estado', '!=', 2) // No incluir cuotas completamente pagadas
                        ->orderBy('numero_cuota');
                },
            ])->find($prestamoId);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            $cuotasPendientes = $prestamo->cuotas->map(function ($cuota) {
                $moraPendiente = $cuota->moraCuotas->where('estado', '!=', 2)->sum('monto');
                $cuotaPendiente = $cuota->monto_cuota - $cuota->monto_pagado;

                return [
                    'id' => $cuota->id,
                    'numero_cuota' => $cuota->numero_cuota,
                    'fecha_vencimiento' => $cuota->fecha_vencimiento,
                    'monto_cuota' => $cuota->monto_cuota,
                    'monto_pagado' => $cuota->monto_pagado,
                    'cuota_pendiente' => $cuotaPendiente,
                    'mora_pendiente' => $moraPendiente,
                    'total_pendiente' => $cuotaPendiente + $moraPendiente,
                    'estado' => $cuota->estado,
                    'estado_texto' => $this->getEstadoTexto($cuota->estado),
                    'dias_vencidos' => Carbon::now()->diffInDays($cuota->fecha_vencimiento, false),
                    'moras' => $cuota->moraCuotas,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'prestamo' => [
                        'id' => $prestamo->id,
                        'codigo' => $prestamo->codigo,
                        'cliente' => $prestamo->cliente->persona ?? null,
                    ],
                    'cuotas_pendientes' => $cuotasPendientes,
                    'resumen' => [
                        'total_cuotas_pendientes' => $cuotasPendientes->sum('cuota_pendiente'),
                        'total_moras_pendientes' => $cuotasPendientes->sum('mora_pendiente'),
                        'total_adeudado' => $cuotasPendientes->sum('total_pendiente'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cuotas pendientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determinar estado de cuota basado en monto pagado
     */
    private function determinarEstadoCuota($montoPagado, $montoCuota): int
    {
        if ($montoPagado >= $montoCuota) {
            return 2; // PAGADO
        } elseif ($montoPagado > 0) {
            return 1; // PARCIAL
        } else {
            return 0; // PENDIENTE
        }
    }

    /**
     * Verificar si el préstamo está completamente pagado
     */
    private function verificarPrestamoCompleto($prestamo): void
    {
        $cuotasPendientes = $prestamo->cuotas()->where('estado', '!=', 2)->count();

        if ($cuotasPendientes === 0) {
            $prestamo->update([
                'estado' => 'cancelado',
                'fecha_cancelacion' => now(),
            ]);
        }
    }

    /**
     * Obtener texto del estado de cuota
     */
    private function getEstadoTexto($estado): string
    {
        return match ($estado) {
            0 => 'Pendiente',
            1 => 'Parcial',
            2 => 'Pagado',
            3 => 'Vencido',
            default => 'Desconocido'
        };
    }
}
