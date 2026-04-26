<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Plazo;
use App\Models\Prestamo;
use App\Models\Tasa;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SolicitudPrestamoController extends Controller
{
    /**
     * Crear nueva solicitud de préstamo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cliente_id' => 'required|exists:clientes,id',
                'sucursal_id' => 'required|exists:sucursales,id',
                'tasa_id' => 'required|exists:tasas,id',
                'plazo_id' => 'required|exists:plazos,id',
                'capital' => 'required|numeric|min:100|max:999999999.99',
                'fecha_primer_pago' => 'required|date|after:today',
                'modalidad_pago' => 'required|in:diario,semanal,quincenal,mensual',
                'observaciones' => 'nullable|string|max:1000',
                'aval_id' => 'nullable|exists:clientes,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Verificar que el cliente esté activo
            $cliente = Cliente::find($request->cliente_id);
            if (! $cliente || $cliente->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no está activo o no existe',
                ], 400);
            }

            // Verificar que no tenga préstamos pendientes si es política de la empresa
            $prestamosActivos = Prestamo::where('cliente_id', $request->cliente_id)
                ->whereIn('estado', ['pendiente', 'aprobado', 'desembolsado'])
                ->count();

            if ($prestamosActivos > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente ya tiene préstamos activos',
                ], 400);
            }

            // Obtener tasa y plazo
            $tasa = Tasa::find($request->tasa_id);
            $plazo = Plazo::find($request->plazo_id);

            // Calcular datos del préstamo
            $datos_calculo = realizar_calculo($request->capital, $tasa->interes, $plazo->numero_cuotas);

            $prestamo = Prestamo::create([
                'cliente_id' => $request->cliente_id,
                'sucursal_id' => $request->sucursal_id,
                'user_id' => auth()->id(),
                'tasa_id' => $request->tasa_id,
                'plazo_id' => $request->plazo_id,
                'aval_id' => $request->aval_id,
                'codigo' => $this->generarCodigoPrestamo(),
                'capital' => $request->capital,
                'interes' => $tasa->interes,
                'numero_cuotas' => $plazo->numero_cuotas,
                'modalidad_pago' => $request->modalidad_pago,
                'fecha_primer_pago' => $request->fecha_primer_pago,
                'monto_cuota' => $datos_calculo['cuota'],
                'total_pagar' => $datos_calculo['total_pagar'],
                'total_interes' => $datos_calculo['total_interes'],
                'observaciones' => $request->observaciones,
                'estado' => 'pendiente',
                'fecha_solicitud' => now(),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de préstamo creada exitosamente',
                'data' => [
                    'prestamo' => $prestamo->load(['cliente.persona', 'sucursal', 'tasa', 'plazo', 'user', 'aval.persona']),
                    'calculo' => $datos_calculo,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la solicitud de préstamo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar solicitudes de préstamos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Prestamo::with(['cliente.persona', 'sucursal', 'tasa', 'plazo', 'user', 'aval.persona']);

            // Filtros
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('cliente_id')) {
                $query->where('cliente_id', $request->cliente_id);
            }

            if ($request->has('sucursal_id')) {
                $query->where('sucursal_id', $request->sucursal_id);
            }

            if ($request->has('fecha_desde')) {
                $query->where('fecha_solicitud', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha_solicitud', '<=', $request->fecha_hasta);
            }

            // Buscar por nombre de cliente o código
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('codigo', 'like', "%{$search}%")
                        ->orWhereHas('cliente.persona', function ($q) use ($search) {
                            $q->where('nombres', 'like', "%{$search}%")
                                ->orWhere('apellidos', 'like', "%{$search}%")
                                ->orWhere('dni', 'like', "%{$search}%");
                        });
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $prestamos = $query->orderBy('fecha_solicitud', 'desc')->paginate($perPage);

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
                'message' => 'Error al obtener las solicitudes de préstamos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar solicitud específica
     */
    public function show($id): JsonResponse
    {
        try {
            $prestamo = Prestamo::with([
                'cliente.persona',
                'cliente.direcciones',
                'cliente.telefonos',
                'sucursal',
                'tasa',
                'plazo',
                'user',
                'aval.persona',
                'cuotas',
                'operaciones',
            ])->find($id);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud de préstamo no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $prestamo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la solicitud de préstamo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar solicitud de préstamo (solo si está pendiente)
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $prestamo = Prestamo::find($id);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud de préstamo no encontrada',
                ], 404);
            }

            if ($prestamo->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar solicitudes pendientes',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'tasa_id' => 'sometimes|exists:tasas,id',
                'plazo_id' => 'sometimes|exists:plazos,id',
                'capital' => 'sometimes|numeric|min:100|max:999999999.99',
                'fecha_primer_pago' => 'sometimes|date|after:today',
                'modalidad_pago' => 'sometimes|in:diario,semanal,quincenal,mensual',
                'observaciones' => 'nullable|string|max:1000',
                'aval_id' => 'nullable|exists:clientes,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $updateData = $request->only([
                'tasa_id', 'plazo_id', 'capital', 'fecha_primer_pago',
                'modalidad_pago', 'observaciones', 'aval_id',
            ]);

            // Si se cambian datos que afectan el cálculo, recalcular
            if (isset($updateData['capital']) || isset($updateData['tasa_id']) || isset($updateData['plazo_id'])) {
                $tasa = isset($updateData['tasa_id']) ?
                    Tasa::find($updateData['tasa_id']) : $prestamo->tasa;
                $plazo = isset($updateData['plazo_id']) ?
                    Plazo::find($updateData['plazo_id']) : $prestamo->plazo;
                $capital = $updateData['capital'] ?? $prestamo->capital;

                $datos_calculo = realizar_calculo($capital, $tasa->interes, $plazo->numero_cuotas);

                $updateData['interes'] = $tasa->interes;
                $updateData['numero_cuotas'] = $plazo->numero_cuotas;
                $updateData['monto_cuota'] = $datos_calculo['cuota'];
                $updateData['total_pagar'] = $datos_calculo['total_pagar'];
                $updateData['total_interes'] = $datos_calculo['total_interes'];
            }

            $prestamo->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de préstamo actualizada exitosamente',
                'data' => $prestamo->load(['cliente.persona', 'sucursal', 'tasa', 'plazo', 'user', 'aval.persona']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la solicitud de préstamo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar solicitud de préstamo
     */
    public function cancel($id): JsonResponse
    {
        try {
            $prestamo = Prestamo::find($id);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud de préstamo no encontrada',
                ], 404);
            }

            if ($prestamo->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden cancelar solicitudes pendientes',
                ], 400);
            }

            $prestamo->update(['estado' => 'cancelado']);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de préstamo cancelada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la solicitud de préstamo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcular préstamo (simulador)
     */
    public function calculate(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'capital' => 'required|numeric|min:100|max:999999999.99',
                'tasa_id' => 'required|exists:tasas,id',
                'plazo_id' => 'required|exists:plazos,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $tasa = Tasa::find($request->tasa_id);
            $plazo = Plazo::find($request->plazo_id);

            $datos_calculo = realizar_calculo($request->capital, $tasa->interes, $plazo->numero_cuotas);

            return response()->json([
                'success' => true,
                'data' => [
                    'capital' => $request->capital,
                    'tasa' => $tasa,
                    'plazo' => $plazo,
                    'calculo' => $datos_calculo,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular el préstamo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar código único para préstamo
     */
    private function generarCodigoPrestamo(): string
    {
        $fecha = Carbon::now()->format('ymd');
        $ultimoPrestamo = Prestamo::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        $secuencial = $ultimoPrestamo ?
            intval(substr($ultimoPrestamo->codigo, -4)) + 1 : 1;

        return 'PR'.$fecha.str_pad($secuencial, 4, '0', STR_PAD_LEFT);
    }
}
