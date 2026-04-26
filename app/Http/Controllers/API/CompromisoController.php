<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Compromiso;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompromisoController extends Controller
{
    /**
     * Listar compromisos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Compromiso::with([
                'prestamo.cliente.persona',
                'user',
                'gestion',
            ]);

            // Filtros
            if ($request->has('prestamo_id')) {
                $query->where('prestamo_id', $request->prestamo_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->has('fecha_desde')) {
                $query->where('fecha_compromiso', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha_compromiso', '<=', $request->fecha_hasta);
            }

            // Compromisos vencidos
            if ($request->has('vencidos') && $request->vencidos) {
                $query->where('fecha_compromiso', '<', now())
                    ->where('estado', 'pendiente');
            }

            // Compromisos de hoy
            if ($request->has('hoy') && $request->hoy) {
                $query->whereDate('fecha_compromiso', now()->format('Y-m-d'));
            }

            // Búsqueda por cliente
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('prestamo.cliente.persona', function ($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%")
                        ->orWhere('dni', 'like', "%{$search}%");
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $compromisos = $query->orderBy('fecha_compromiso', 'desc')->paginate($perPage);

            // Agregar información adicional a cada compromiso
            $compromisos->getCollection()->transform(function ($compromiso) {
                $compromiso->dias_vencimiento = Carbon::now()->diffInDays($compromiso->fecha_compromiso, false);
                $compromiso->esta_vencido = Carbon::parse($compromiso->fecha_compromiso)->isPast() && $compromiso->estado === 'pendiente';

                return $compromiso;
            });

            return response()->json([
                'success' => true,
                'data' => $compromisos->items(),
                'pagination' => [
                    'current_page' => $compromisos->currentPage(),
                    'last_page' => $compromisos->lastPage(),
                    'per_page' => $compromisos->perPage(),
                    'total' => $compromisos->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los compromisos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear nuevo compromiso
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'prestamo_id' => 'required|exists:prestamos,id',
                'monto_compromiso' => 'required|numeric|min:0.01',
                'fecha_compromiso' => 'required|date|after_or_equal:today',
                'observaciones' => 'nullable|string|max:1000',
                'tipo_compromiso' => 'required|in:pago_total,pago_parcial,refinanciamiento,otro',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $prestamo = Prestamo::find($request->prestamo_id);
            if (! $prestamo || $prestamo->estado !== 'desembolsado') {
                return response()->json([
                    'success' => false,
                    'message' => 'El préstamo debe estar desembolsado para crear compromisos',
                ], 400);
            }

            $compromiso = Compromiso::create([
                'prestamo_id' => $request->prestamo_id,
                'usuario_id' => auth()->id(),
                'monto_compromiso' => $request->monto_compromiso,
                'fecha_compromiso' => $request->fecha_compromiso,
                'observaciones' => $request->observaciones,
                'tipo_compromiso' => $request->tipo_compromiso,
                'estado' => 'pendiente',
                'fecha_creacion' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compromiso creado exitosamente',
                'data' => $compromiso->load([
                    'prestamo.cliente.persona',
                    'user',
                ]),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el compromiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar compromiso específico
     */
    public function show($id): JsonResponse
    {
        try {
            $compromiso = Compromiso::with([
                'prestamo.cliente.persona',
                'prestamo.cuotas',
                'user',
                'gestion',
            ])->find($id);

            if (! $compromiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compromiso no encontrado',
                ], 404);
            }

            // Agregar información adicional
            $compromiso->dias_vencimiento = Carbon::now()->diffInDays($compromiso->fecha_compromiso, false);
            $compromiso->esta_vencido = Carbon::parse($compromiso->fecha_compromiso)->isPast() && $compromiso->estado === 'pendiente';

            return response()->json([
                'success' => true,
                'data' => $compromiso,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el compromiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar compromiso
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $compromiso = Compromiso::find($id);

            if (! $compromiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compromiso no encontrado',
                ], 404);
            }

            if ($compromiso->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden actualizar compromisos pendientes',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'monto_compromiso' => 'sometimes|numeric|min:0.01',
                'fecha_compromiso' => 'sometimes|date|after_or_equal:today',
                'observaciones' => 'nullable|string|max:1000',
                'tipo_compromiso' => 'sometimes|in:pago_total,pago_parcial,refinanciamiento,otro',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $compromiso->update($request->only([
                'monto_compromiso',
                'fecha_compromiso',
                'observaciones',
                'tipo_compromiso',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Compromiso actualizado exitosamente',
                'data' => $compromiso->load(['prestamo.cliente.persona', 'user']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el compromiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cumplir compromiso
     */
    public function cumplir(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'monto_pagado' => 'required|numeric|min:0.01',
                'metodo_pago' => 'required|in:efectivo,transferencia,yape,plin,cheque,deposito',
                'numero_operacion' => 'nullable|string|max:50',
                'observaciones_cumplimiento' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $compromiso = Compromiso::find($id);

            if (! $compromiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compromiso no encontrado',
                ], 404);
            }

            if ($compromiso->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'El compromiso ya fue procesado',
                ], 400);
            }

            DB::beginTransaction();

            // Determinar estado según monto pagado
            $estado = 'cumplido';
            if ($request->monto_pagado < $compromiso->monto_compromiso) {
                $estado = 'cumplido_parcial';
            }

            // Actualizar compromiso
            $compromiso->update([
                'estado' => $estado,
                'monto_pagado' => $request->monto_pagado,
                'fecha_cumplimiento' => now(),
                'metodo_pago' => $request->metodo_pago,
                'numero_operacion' => $request->numero_operacion,
                'observaciones_cumplimiento' => $request->observaciones_cumplimiento,
                'cumplido_por' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compromiso marcado como cumplido exitosamente',
                'data' => $compromiso->load(['prestamo.cliente.persona', 'user']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al cumplir el compromiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Incumplir compromiso
     */
    public function incumplir(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'motivo_incumplimiento' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El motivo de incumplimiento es requerido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $compromiso = Compromiso::find($id);

            if (! $compromiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compromiso no encontrado',
                ], 404);
            }

            if ($compromiso->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden incumplir compromisos pendientes',
                ], 400);
            }

            $compromiso->update([
                'estado' => 'incumplido',
                'fecha_incumplimiento' => now(),
                'motivo_incumplimiento' => $request->motivo_incumplimiento,
                'incumplido_por' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Compromiso marcado como incumplido',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al incumplir el compromiso',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar compromisos de un préstamo
     */
    public function compromisosPrestamo($prestamoId): JsonResponse
    {
        try {
            $prestamo = Prestamo::find($prestamoId);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            $compromisos = Compromiso::with(['user', 'gestion'])
                ->where('prestamo_id', $prestamoId)
                ->orderBy('fecha_compromiso', 'desc')
                ->get();

            // Agregar información adicional
            $compromisos->transform(function ($compromiso) {
                $compromiso->dias_vencimiento = Carbon::now()->diffInDays($compromiso->fecha_compromiso, false);
                $compromiso->esta_vencido = Carbon::parse($compromiso->fecha_compromiso)->isPast() && $compromiso->estado === 'pendiente';

                return $compromiso;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'prestamo' => [
                        'id' => $prestamo->id,
                        'codigo' => $prestamo->codigo,
                        'cliente' => $prestamo->cliente->persona ?? null,
                    ],
                    'compromisos' => $compromisos,
                    'resumen' => [
                        'total_compromisos' => $compromisos->count(),
                        'pendientes' => $compromisos->where('estado', 'pendiente')->count(),
                        'cumplidos' => $compromisos->where('estado', 'cumplido')->count(),
                        'incumplidos' => $compromisos->where('estado', 'incumplido')->count(),
                        'monto_total_comprometido' => $compromisos->where('estado', 'pendiente')->sum('monto_compromiso'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener compromisos del préstamo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dashboard de compromisos
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $usuarioId = $request->get('usuario_id', auth()->id());
            $fecha = $request->get('fecha', now()->format('Y-m-d'));

            // Compromisos de hoy
            $compromisosHoy = Compromiso::with(['prestamo.cliente.persona'])
                ->where('usuario_id', $usuarioId)
                ->whereDate('fecha_compromiso', $fecha)
                ->get();

            // Compromisos vencidos
            $compromisosVencidos = Compromiso::with(['prestamo.cliente.persona'])
                ->where('usuario_id', $usuarioId)
                ->where('fecha_compromiso', '<', now())
                ->where('estado', 'pendiente')
                ->get();

            // Compromisos próximos (próximos 7 días)
            $compromisosProximos = Compromiso::with(['prestamo.cliente.persona'])
                ->where('usuario_id', $usuarioId)
                ->whereBetween('fecha_compromiso', [
                    now()->addDay(),
                    now()->addDays(7),
                ])
                ->where('estado', 'pendiente')
                ->get();

            // Estadísticas del mes
            $inicioMes = now()->startOfMonth();
            $finMes = now()->endOfMonth();

            $estadisticasMes = Compromiso::where('usuario_id', $usuarioId)
                ->whereBetween('fecha_creacion', [$inicioMes, $finMes])
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = "cumplido" THEN 1 ELSE 0 END) as cumplidos,
                    SUM(CASE WHEN estado = "incumplido" THEN 1 ELSE 0 END) as incumplidos,
                    SUM(CASE WHEN estado = "pendiente" THEN 1 ELSE 0 END) as pendientes,
                    SUM(monto_compromiso) as monto_total_comprometido,
                    SUM(CASE WHEN estado = "cumplido" THEN monto_pagado ELSE 0 END) as monto_total_cobrado
                ')
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'fecha_consulta' => $fecha,
                    'compromisos_hoy' => $compromisosHoy,
                    'compromisos_vencidos' => $compromisosVencidos,
                    'compromisos_proximos' => $compromisosProximos,
                    'resumen_dia' => [
                        'total_hoy' => $compromisosHoy->count(),
                        'monto_hoy' => $compromisosHoy->sum('monto_compromiso'),
                        'total_vencidos' => $compromisosVencidos->count(),
                        'monto_vencido' => $compromisosVencidos->sum('monto_compromiso'),
                    ],
                    'estadisticas_mes' => [
                        'total_compromisos' => $estadisticasMes->total ?? 0,
                        'cumplidos' => $estadisticasMes->cumplidos ?? 0,
                        'incumplidos' => $estadisticasMes->incumplidos ?? 0,
                        'pendientes' => $estadisticasMes->pendientes ?? 0,
                        'monto_comprometido' => $estadisticasMes->monto_total_comprometido ?? 0,
                        'monto_cobrado' => $estadisticasMes->monto_total_cobrado ?? 0,
                        'tasa_cumplimiento' => $estadisticasMes->total > 0 ?
                            round(($estadisticasMes->cumplidos / $estadisticasMes->total) * 100, 2) : 0,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener dashboard de compromisos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
