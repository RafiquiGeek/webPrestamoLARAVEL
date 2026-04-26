<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MetodoDePago;
use App\Models\Operacion;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AprobacionPrestamoController extends Controller
{
    /**
     * Listar préstamos pendientes de aprobación
     */
    public function pendientes(Request $request): JsonResponse
    {
        try {
            $query = Prestamo::with(['cliente', 'cuenta'])
                ->where('estado', 'Nueva Solicitud');

            // Filtros básicos - ajusta según tus campos reales
            if ($request->has('fecha_desde')) {
                $query->where('created_at', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('created_at', '<=', $request->fecha_hasta);
            }

            // Buscar por ID de préstamo
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('cliente', function ($q) use ($search) {
                            $q->whereHas('persona', function ($q) use ($search) {
                                $q->where('nombres', 'like', "%{$search}%")
                                    ->orWhere('apellidos', 'like', "%{$search}%")
                                    ->orWhere('dni', 'like', "%{$search}%");
                            });
                        });
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $prestamos = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $prestamos->items(),
                'pagination' => [
                    'current_page' => $prestamos->currentPage(),
                    'last_page' => $prestamos->lastPage(),
                    'per_page' => $prestamos->perPage(),
                    'total' => $prestamos->total(),
                    'from' => $prestamos->firstItem(),
                    'to' => $prestamos->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener préstamos pendientes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprobar préstamo
     */
    public function aprobar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_atencion' => 'nullable|date',
                'observaciones' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

        $prestamo = Prestamo::with(['cliente'])->find($id);

        if (!$prestamo) {
            return response()->json([
                'success' => false,
                'message' => 'Préstamo no encontrado',
            ], 404);
        }

        // DEBUG: Agregar log para ver el estado actual
        \Log::info('Estado actual del préstamo', [
            'prestamo_id' => $id,
            'estado_actual' => $prestamo->estado,
        ]);

        // Verificar estado - permite solo 'Nueva Solicitud'
        if ($prestamo->estado !== 'Nueva Solicitud') {
            \Log::warning('Intento de aprobar préstamo con estado incorrecto', [
                'prestamo_id' => $id,
                'estado_actual' => $prestamo->estado,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden aprobar préstamos en estado "Nueva Solicitud". Estado actual: ' . $prestamo->estado,
                'estado_actual' => $prestamo->estado,
            ], 400);
        }

        DB::beginTransaction();

        // Cambiar estado del préstamo a 'Por Desembolsar'
        $updateData = [
            'estado' => 'Por Desembolsar',
            'fecha_atencion' => $request->fecha_atencion ?? now(),
        ];

        // Actualizar observaciones si se proporcionan
        if ($request->has('observaciones') && !empty($request->observaciones)) {
            $updateData['observaciones'] = $request->observaciones;
        }

        $prestamo->update($updateData);

        DB::commit();

        \Log::info('Préstamo aprobado exitosamente', [
            'prestamo_id' => $id,
            'nuevo_estado' => 'Por Desembolsar',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Préstamo aprobado exitosamente',
            'data' => $prestamo->fresh(['cliente']),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Error al aprobar préstamo ID: ' . $id, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Error al aprobar el préstamo: ' . $e->getMessage(),
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Rechazar préstamo
     */
    public function rechazar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'motivo_rechazo' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El motivo del rechazo es requerido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $prestamo = Prestamo::find($id);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            if ($prestamo->estado !== 'Nueva Solicitud') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar préstamos en estado "Nueva Solicitud"',
                ], 400);
            }

            $prestamo->update([
                'estado' => 'rechazado',
                'observaciones' => $request->motivo_rechazo,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo rechazado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el préstamo: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener métodos de pago disponibles
     */
    public function metodosPago(): JsonResponse
    {
        try {
            $metodos = MetodoDePago::where('status', true)
                ->orderBy('metodo_pago')
                ->get(['id', 'metodo_pago']);

            return response()->json([
                'success' => true,
                'data' => $metodos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de pago',
            ], 500);
        }
    }

    /**
     * Desembolsar préstamo
     */
    public function desembolsar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_desembolso' => 'required|date',
                'metodo_pago_id' => 'required|exists:metodos_de_pago,id',
                'observaciones' => 'nullable|string|max:500',
                'nro_operacion' => 'nullable|string|max:100',
                'fecha_operacion' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $prestamo = Prestamo::with('cliente')->find($id);

            if (!$prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            // Verifica el estado 'Por Desembolsar'
            if ($prestamo->estado !== 'Por Desembolsar') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden desembolsar préstamos en estado "Por Desembolsar". Estado actual: ' . $prestamo->estado,
                ], 400);
            }

            if (!$prestamo->cliente_id || !$prestamo->cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'El préstamo no tiene un cliente asociado válido.',
                ], 400);
            }

            DB::beginTransaction();

            // Crear operación de desembolso (igual que en admin)
            $operacion = new Operacion;
            $operacion->prestamo_id = $prestamo->id;
            $operacion->cliente_id = $prestamo->cliente_id;
            $operacion->tipo_operacion = 'Desembolso';
            $operacion->fecha = $request->fecha_desembolso;
            $operacion->abono = $prestamo->cantidad_solicitada;
            $operacion->metodo_pago_id = $request->metodo_pago_id;
            $operacion->comentario = $request->observaciones;
            $operacion->user_id = auth()->id();
            $operacion->estado_rendicion = 'pendiente';

            // Solo guardar codigo si hay nro_operacion (Yape/Plin/Transferencia)
            if ($request->nro_operacion) {
                $operacion->codigo = $request->nro_operacion;
            }

            $operacion->save();

            // Cambiar estado del préstamo a Vigente
            $prestamo->estado = 'Vigente';
            $prestamo->fecha_atencion = $request->fecha_desembolso;
            $prestamo->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Préstamo desembolsado exitosamente',
                'data' => $prestamo->fresh(['cliente']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error al desembolsar préstamo ID: ' . $id, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al desembolsar el préstamo: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar préstamos aprobados
     */
    public function aprobados(Request $request): JsonResponse
    {
        try {
            $query = Prestamo::with(['cliente'])
                ->where('estado', 'Por Desembolsar');

            // Buscar por ID de préstamo
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                        ->orWhereHas('cliente', function ($q) use ($search) {
                            $q->whereHas('persona', function ($q) use ($search) {
                                $q->where('nombres', 'like', "%{$search}%")
                                    ->orWhere('apellidos', 'like', "%{$search}%");
                            });
                        });
                });
            }

            $perPage = $request->get('per_page', 15);
            $prestamos = $query->orderBy('fecha_atencion', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $prestamos->items(),
                'pagination' => [
                    'current_page' => $prestamos->currentPage(),
                    'last_page' => $prestamos->lastPage(),
                    'per_page' => $prestamos->perPage(),
                    'total' => $prestamos->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener préstamos aprobados',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener historial de aprobaciones
     */
    public function historial(Request $request): JsonResponse
    {
        try {
            $query = Prestamo::with(['cliente'])
                ->whereIn('estado', ['Por Desembolsar', 'rechazado', 'desembolsado']);

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('fecha_desde')) {
                $query->where('fecha_atencion', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha_atencion', '<=', $request->fecha_hasta);
            }

            $perPage = $request->get('per_page', 15);
            $prestamos = $query->orderBy('fecha_atencion', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $prestamos->items(),
                'pagination' => [
                    'current_page' => $prestamos->currentPage(),
                    'last_page' => $prestamos->lastPage(),
                    'per_page' => $prestamos->perPage(),
                    'total' => $prestamos->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de aprobaciones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}