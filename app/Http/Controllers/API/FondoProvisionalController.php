<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FondoProvisional;
use App\Models\MetodoDePago;
use App\Models\Prestamo;
use App\Models\Operacion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FondoProvisionalController extends Controller
{
    /**
     * Listar fondos provisionales
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = FondoProvisional::with(['usuario', 'aprobadoPor']);

            // Filtros
            if ($request->has('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->has('fecha_desde')) {
                $query->where('fecha_solicitud', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha_solicitud', '<=', $request->fecha_hasta);
            }

            // Solo mostrar fondos del usuario si no es admin
            $user = auth()->user();
            if (!$user->hasRole(['Admin', 'Supervisor'])) {
                $query->where('usuario_id', $user->id);
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $fondos = $query->orderBy('fecha_solicitud', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $fondos->items(),
                'pagination' => [
                    'current_page' => $fondos->currentPage(),
                    'last_page' => $fondos->lastPage(),
                    'per_page' => $fondos->perPage(),
                    'total' => $fondos->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los fondos provisionales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear nueva solicitud de fondo provisional
     */
    // public function store(Request $request): JsonResponse
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'tipo' => 'required|in:adelanto,prestamo,gastos_operativos,emergencia',
    //             'monto_solicitado' => 'required|numeric|min:1|max:999999999.99',
    //             'motivo' => 'required|string|max:1000',
    //             'fecha_necesaria' => 'required|date|after_or_equal:today',
    //             'forma_descuento' => 'required|in:cuotas,total,porcentaje',
    //             'numero_cuotas' => 'required_if:forma_descuento,cuotas|nullable|integer|min:1|max:12',
    //             'porcentaje_descuento' => 'required_if:forma_descuento,porcentaje|nullable|numeric|min:1|max:100',
    //             'comprobante_solicitud' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Datos de validación incorrectos',
    //                 'errors' => $validator->errors(),
    //             ], 422);
    //         }

    //         // Verificar si el usuario tiene fondos pendientes
    //         $fondosPendientes = FondoProvisional::where('usuario_id', auth()->id())
    //             ->whereIn('estado', ['pendiente', 'aprobado'])
    //             ->count();

    //         if ($fondosPendientes > 0) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'No puede solicitar un nuevo fondo mientras tenga solicitudes pendientes o aprobadas',
    //             ], 400);
    //         }

    //         DB::beginTransaction();

    //         // Subir comprobante si existe
    //         $comprobantePath = null;
    //         if ($request->hasFile('comprobante_solicitud')) {
    //             $comprobantePath = $request->file('comprobante_solicitud')
    //                 ->store('fondos/solicitudes', 'public');
    //         }

    //         // Generar código único
    //         $codigo = $this->generarCodigoFondo();

    //         $fondo = FondoProvisional::create([
    //             'codigo' => $codigo,
    //             'usuario_id' => auth()->id(),
    //             'tipo' => $request->tipo,
    //             'monto_solicitado' => $request->monto_solicitado,
    //             'motivo' => $request->motivo,
    //             'fecha_solicitud' => now(),
    //             'fecha_necesaria' => $request->fecha_necesaria,
    //             'forma_descuento' => $request->forma_descuento,
    //             'numero_cuotas' => $request->numero_cuotas,
    //             'porcentaje_descuento' => $request->porcentaje_descuento,
    //             'comprobante_solicitud' => $comprobantePath,
    //             'estado' => 'pendiente',
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Solicitud de fondo provisional creada exitosamente',
    //             'data' => $fondo->load('usuario'),
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear la solicitud de fondo provisional',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function store(Request $request): JsonResponse
{
    // 1. Validar los datos entrantes desde Flutter
    $validator = Validator::make($request->all(), [
        'prestamo_id' => 'required|exists:prestamos,id',
        'monto_capital' => 'required|numeric|min:0',
        'monto_fondo' => 'required|numeric|min:0',
        'monto_personalizado' => 'required|numeric|min:0',
        'fecha_entrega' => 'required|date',
        'metodo_pago' => 'required|integer', // Esperamos el ID (ej: 1 o 3)
        'observaciones' => 'nullable|string',
        'imagen_yape' => 'nullable|file|image|max:5120', // Max 5MB
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        DB::beginTransaction();

        // 2. Obtener datos del préstamo
        $prestamo = Prestamo::findOrFail($request->prestamo_id);

        // 3. Procesar la imagen si existe (Yape/Plin)
        $voucherPath = null;
        if ($request->hasFile('imagen_yape')) {
            $voucherPath = $request->file('imagen_yape')->store('fondos_provisionales', 'public');
        }

        // 4. Calcular porcentaje real
        $montoFinal = $request->monto_personalizado;
        $porcentajeReal = $montoFinal > 0 
            ? ($montoFinal / $request->monto_capital) * 100 
            : 0;

        // 5. Crear el registro en FondoProvisional
        $fondoProvisional = FondoProvisional::create([
            'prestamo_id'   => $request->prestamo_id,
            'asesor_id'     => Auth::id(), // El usuario logueado en la App
            'monto_capital' => $request->monto_capital,
            'porcentaje'    => round($porcentajeReal, 2),
            'monto_fondo'   => $montoFinal,
            'fecha_entrega' => $request->fecha_entrega,
            'estado'        => FondoProvisional::ESTADO_ENTREGADO, // Asumimos entregado directo
            'observaciones' => $request->observaciones,
        ]);

        // 6. Crear la Operación (Movimiento de Caja)
        $operacion = Operacion::create([
            'prestamo_id'      => $request->prestamo_id,
            'cliente_id'       => $prestamo->cliente_id,
            'fecha'            => $request->fecha_entrega,
            'metodo_pago_id'   => $request->metodo_pago,
            'abono'            => $montoFinal,
            'tipo_operacion'   => 'Fondo Provisional',
            'estado_rendicion' => 'pendiente',
            'user_id'          => Auth::id(),
            'voucher_path'     => $voucherPath, // La ruta de la imagen guardada
            'comentario'       => "Fondo provisional (App): " . ($request->observaciones ?? 'Sin obs'),
        ]);

        // 7. Vincular la operación al fondo
        $fondoProvisional->update(['operacion_id' => $operacion->id]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Fondo provisional registrado correctamente',
            'data' => [
                'fondo_id' => $fondoProvisional->id,
                'operacion_id' => $operacion->id,
                'voucher_url' => $voucherPath ? asset('storage/' . $voucherPath) : null
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error API Fondo Provisional: ' . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Error interno al procesar la solicitud',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Mostrar fondo provisional específico
     */
    public function show($id): JsonResponse
    {
        try {
            $fondo = FondoProvisional::with([
                'usuario',
                'aprobadoPor',
                'rechazadoPor',
                'liquidadoPor',
            ])->find($id);

            if (!$fondo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fondo provisional no encontrado',
                ], 404);
            }

            // Verificar permisos
            $user = auth()->user();
            if (!$user->hasRole(['Admin', 'Supervisor']) && $fondo->usuario_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver este fondo provisional',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $fondo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el fondo provisional',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprobar fondo provisional
     */
    public function aprobar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'monto_aprobado' => 'required|numeric|min:1',
                'observaciones_aprobacion' => 'nullable|string|max:500',
                'fecha_entrega' => 'nullable|date|after_or_equal:today',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $fondo = FondoProvisional::find($id);

            if (!$fondo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fondo provisional no encontrado',
                ], 404);
            }

            if ($fondo->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden aprobar fondos pendientes',
                ], 400);
            }

            // Verificar permisos
            if (!auth()->user()->hasRole(['Admin', 'Supervisor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para aprobar fondos provisionales',
                ], 403);
            }

            $fondo->update([
                'estado' => 'aprobado',
                'monto_aprobado' => $request->monto_aprobado,
                'fecha_aprobacion' => now(),
                'aprobado_por' => auth()->id(),
                'observaciones_aprobacion' => $request->observaciones_aprobacion,
                'fecha_entrega_programada' => $request->fecha_entrega ?? now()->addDay(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fondo provisional aprobado exitosamente',
                'data' => $fondo->load(['usuario', 'aprobadoPor']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar el fondo provisional',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rechazar fondo provisional
     */
    public function rechazar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'motivo_rechazo' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El motivo del rechazo es requerido',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $fondo = FondoProvisional::find($id);

            if (!$fondo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fondo provisional no encontrado',
                ], 404);
            }

            if ($fondo->estado !== 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar fondos pendientes',
                ], 400);
            }

            // Verificar permisos
            if (!auth()->user()->hasRole(['Admin', 'Supervisor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para rechazar fondos provisionales',
                ], 403);
            }

            $fondo->update([
                'estado' => 'rechazado',
                'fecha_rechazo' => now(),
                'rechazado_por' => auth()->id(),
                'motivo_rechazo' => $request->motivo_rechazo,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fondo provisional rechazado',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el fondo provisional',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Entregar fondo provisional
     */
    public function entregar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'metodo_entrega' => 'required|in:efectivo,transferencia,cheque',
                'numero_operacion' => 'nullable|string|max:50',
                'observaciones_entrega' => 'nullable|string|max:500',
                'comprobante_entrega' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $fondo = FondoProvisional::find($id);

            if (!$fondo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fondo provisional no encontrado',
                ], 404);
            }

            if ($fondo->estado !== 'aprobado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden entregar fondos aprobados',
                ], 400);
            }

            DB::beginTransaction();

            // Subir comprobante si existe
            $comprobantePath = null;
            if ($request->hasFile('comprobante_entrega')) {
                $comprobantePath = $request->file('comprobante_entrega')
                    ->store('fondos/entregas', 'public');
            }

            $fondo->update([
                'estado' => 'entregado',
                'fecha_entrega' => now(),
                'metodo_entrega' => $request->metodo_entrega,
                'numero_operacion_entrega' => $request->numero_operacion,
                'observaciones_entrega' => $request->observaciones_entrega,
                'comprobante_entrega' => $comprobantePath,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fondo provisional entregado exitosamente',
                'data' => $fondo->load('usuario'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al entregar el fondo provisional',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liquidar fondo provisional
     */
    public function liquidar(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'monto_liquidado' => 'required|numeric|min:0',
                'monto_descuento' => 'required|numeric|min:0',
                'observaciones_liquidacion' => 'nullable|string|max:500',
                'comprobantes_liquidacion.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $fondo = FondoProvisional::find($id);

            if (!$fondo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fondo provisional no encontrado',
                ], 404);
            }

            if ($fondo->estado !== 'entregado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden liquidar fondos entregados',
                ], 400);
            }

            DB::beginTransaction();

            // Subir comprobantes si existen
            $comprobantesLiquidacion = [];
            if ($request->hasFile('comprobantes_liquidacion')) {
                foreach ($request->file('comprobantes_liquidacion') as $comprobante) {
                    $path = $comprobante->store('fondos/liquidaciones', 'public');
                    $comprobantesLiquidacion[] = $path;
                }
            }

            $fondo->update([
                'estado' => 'liquidado',
                'fecha_liquidacion' => now(),
                'monto_liquidado' => $request->monto_liquidado,
                'monto_descuento' => $request->monto_descuento,
                'saldo_pendiente' => max(0, $fondo->monto_aprobado - $request->monto_liquidado - $request->monto_descuento),
                'observaciones_liquidacion' => $request->observaciones_liquidacion,
                'comprobantes_liquidacion' => json_encode($comprobantesLiquidacion),
                'liquidado_por' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fondo provisional liquidado exitosamente',
                'data' => $fondo->load(['usuario', 'liquidadoPor']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al liquidar el fondo provisional',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener resumen de fondos provisionales
     */
    public function resumen(Request $request): JsonResponse
    {
        try {
            $usuarioId = $request->get('usuario_id');
            $user = auth()->user();

            // Si no es admin/supervisor, solo puede ver sus propios fondos
            if (!$user->hasRole(['Admin', 'Supervisor'])) {
                $usuarioId = $user->id;
            }

            $query = FondoProvisional::query();

            if ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            }

            $fondos = $query->get();

            $resumen = [
                'total_solicitudes' => $fondos->count(),
                'pendientes' => $fondos->where('estado', 'pendiente')->count(),
                'aprobados' => $fondos->where('estado', 'aprobado')->count(),
                'entregados' => $fondos->where('estado', 'entregado')->count(),
                'liquidados' => $fondos->where('estado', 'liquidado')->count(),
                'rechazados' => $fondos->where('estado', 'rechazado')->count(),
                'monto_total_solicitado' => $fondos->sum('monto_solicitado'),
                'monto_total_aprobado' => $fondos->whereNotNull('monto_aprobado')->sum('monto_aprobado'),
                'monto_total_liquidado' => $fondos->whereNotNull('monto_liquidado')->sum('monto_liquidado'),
                'saldo_pendiente_total' => $fondos->sum('saldo_pendiente'),
            ];

            // Fondos por tipo
            $porTipo = $fondos->groupBy('tipo')->map(function ($fondosTipo) {
                return [
                    'count' => $fondosTipo->count(),
                    'monto_solicitado' => $fondosTipo->sum('monto_solicitado'),
                    'monto_aprobado' => $fondosTipo->sum('monto_aprobado'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'resumen_general' => $resumen,
                    'por_tipo' => $porTipo,
                    'fecha_consulta' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de fondos provisionales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener fondos del usuario autenticado
     */
    public function misFondos(Request $request): JsonResponse
    {
        try {
            // Usar asesor_id en lugar de usuario_id
            $query = FondoProvisional::where('asesor_id', auth()->id());

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            // Usar created_at en lugar de fecha_solicitud
            $fondos = $query->orderBy('created_at', 'desc')->get();

            // Calcular saldo pendiente basado en monto_fondo y estado
            $saldoPendiente = $fondos->where('estado', '!=', 'rendido')->sum('monto_fondo');

            return response()->json([
                'success' => true,
                'data' => $fondos,
                'resumen' => [
                    'total' => $fondos->count(),
                    'pendientes' => $fondos->where('estado', 'pendiente')->count(),
                    'aprobados' => $fondos->where('estado', 'entregado')->count(),
                    'saldo_total_pendiente' => $saldoPendiente,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener mis fondos provisionales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener fondo provisional por préstamo ID
     */
    public function getByPrestamo($prestamo_id): JsonResponse
    {
        try {
            $fondo = FondoProvisional::with(['asesor', 'operacion'])
                ->where('prestamo_id', $prestamo_id)
                ->first();

            if (!$fondo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró fondo provisional para este préstamo',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fondo provisional encontrado',
                'data' => $fondo,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener fondo provisional',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Obtener todos los métodos de pago activos
     */
    public function getMetodosPago(): JsonResponse
    {
        try {
            $metodos = MetodoDePago::activos()
                ->whereNotNull('metodo_pago')
                ->orderBy('id')
                ->get()
                ->map(function ($metodo) {
                    return [
                        'id' => $metodo->id,
                        'nombre' => $metodo->metodo_pago ?? 'Sin nombre',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $metodos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Generar código único para fondo provisional
     */
    private function generarCodigoFondo(): string
    {
        $fecha = Carbon::now()->format('ymd');
        $ultimoFondo = FondoProvisional::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();

        $secuencial = $ultimoFondo ?
            intval(substr($ultimoFondo->codigo, -4)) + 1 : 1;

        return 'FP' . $fecha . str_pad($secuencial, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Registrar fondo provisional de préstamo desde app móvil
     */
    // public function registrarFondoProvisional(Request $request): JsonResponse
    // {
    //     try {
    //         $prestamo = Prestamo::find($request->prestamo_id);

    //         if (!$prestamo) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Préstamo no encontrado',
    //             ], 404);
    //         }

    //         $montoMaximo = FondoProvisional::calcularMontoFondo($prestamo->cantidad_solicitada);

    //         $rules = [
    //             'prestamo_id' => 'required|exists:prestamos,id',
    //             'monto_capital' => 'required|numeric|min:0',
    //             'monto_fondo' => 'required|numeric|min:0',
    //             'monto_personalizado' => 'required|numeric|min:0|max:' . $montoMaximo,
    //             'fecha_entrega' => 'required|date',
    //             'metodo_pago' => 'required|exists:metodos_de_pago,id',
    //             'observaciones' => 'nullable|string|max:1000',
    //         ];

    //         $validator = Validator::make($request->all(), $rules);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Datos de validación incorrectos',
    //                 'errors' => $validator->errors(),
    //             ], 422);
    //         }

    //         $fondoExistente = FondoProvisional::where('prestamo_id', $request->prestamo_id)->first();
    //         if ($fondoExistente) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Ya existe un fondo provisional para este préstamo',
    //             ], 400);
    //         }

    //         DB::beginTransaction();

    //         // CORRECCIÓN: Verificar si está exonerado usando comparación float
    //         $montoPersonalizado = (float) $request->monto_personalizado;
    //         $exonerado = $montoPersonalizado <= 0;

    //         // Calcular el porcentaje real solo si no está exonerado
    //         $porcentajeReal = $exonerado ? 0 : ($montoPersonalizado / $request->monto_capital) * 100;

    //         // Subir imagen solo si no está exonerado y tiene archivo
    //         $imagenYapePath = null;
    //         if (!$exonerado && $request->hasFile('imagen_yape')) {
    //             $imagenYapePath = $request->file('imagen_yape')
    //                 ->store('fondos/yape', 'public');
    //         }

    //         $fondoProvisional = FondoProvisional::create([
    //             'prestamo_id' => $request->prestamo_id,
    //             'asesor_id' => auth()->id(),
    //             'monto_capital' => $request->monto_capital,
    //             'porcentaje' => round($porcentajeReal, 2),
    //             'monto_fondo' => $exonerado ? 0 : $montoPersonalizado,
    //             'fecha_entrega' => $request->fecha_entrega,
    //             'estado' => $exonerado
    //                 ? FondoProvisional::ESTADO_EXONERADO
    //                 : FondoProvisional::ESTADO_ENTREGADO,
    //             'observaciones' => $request->observaciones,
    //         ]);

    //         // Solo crear operación si NO está exonerado
    //         if (!$exonerado) {

    //             $operacion = Operacion::create([
    //                 'prestamo_id' => $request->prestamo_id,
    //                 'cliente_id' => $prestamo->cliente_id,
    //                 'fecha' => $request->fecha_entrega,
    //                 'metodo_pago_id' => $request->metodo_pago,
    //                 'abono' => $montoPersonalizado,
    //                 'numero_operacion' => $request->numero_operacion_yape,
    //                 'tipo_operacion' => 'Fondo Provisional',
    //                 'estado_rendicion' => 'pendiente',
    //                 'user_id' => auth()->id(),
    //                 'comentario' => "Fondo provisional entregado por el cliente (" .
    //                     round($porcentajeReal, 2) . "% del capital: S/ {$request->monto_capital})" .
    //                     ($request->observaciones ? ". {$request->observaciones}" : ''),
    //                 'comprobante' => $imagenYapePath,
    //             ]);

    //             $fondoProvisional->update(['operacion_id' => $operacion->id]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => $exonerado
    //                 ? 'Fondo provisional exonerado exitosamente'
    //                 : 'Fondo provisional registrado exitosamente',
    //             'data' => $fondoProvisional->load(['prestamo.cliente.persona', 'asesor', 'operacion']),
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al registrar el fondo provisional',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function registrarFondoProvisional(Request $request): JsonResponse
{
    try {
        // 1. Validar Préstamo
        $prestamo = Prestamo::find($request->prestamo_id);
        if (!$prestamo) {
            return response()->json(['success' => false, 'message' => 'Préstamo no encontrado'], 404);
        }

        // 2. Validaciones
        $montoMaximo = FondoProvisional::calcularMontoFondo($prestamo->cantidad_solicitada);
        
        $validator = Validator::make($request->all(), [
            'prestamo_id' => 'required|exists:prestamos,id',
            'monto_capital' => 'required|numeric|min:0',
            'monto_fondo' => 'required|numeric|min:0',
            'monto_personalizado' => 'required|numeric|min:0|max:' . $montoMaximo,
            'fecha_entrega' => 'required|date',
            'metodo_pago' => 'required|exists:metodos_de_pago,id',
            'observaciones' => 'nullable|string|max:1000',
            'imagen_yape' => 'nullable|file|image|max:5120', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false, 
                'message' => 'Datos de validación incorrectos', 
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Verificar duplicados
        $fondoExistente = FondoProvisional::where('prestamo_id', $request->prestamo_id)->first();
        if ($fondoExistente) {
            return response()->json(['success' => false, 'message' => 'Ya existe un fondo provisional para este préstamo'], 400);
        }

        DB::beginTransaction();

        // 4. Lógica de Exoneración
        $montoPersonalizado = (float) $request->monto_personalizado;
        $exonerado = $montoPersonalizado <= 0;
        
        $porcentajeReal = $exonerado ? 0 : ($montoPersonalizado / $request->monto_capital) * 100;

        // 5. Guardar Imagen (Físicamente)
        $imagenYapePath = null;
        if (!$exonerado && $request->hasFile('imagen_yape')) {
            // Esto guarda en storage/app/public/fondos/yape
            $imagenYapePath = $request->file('imagen_yape')->store('fondos/yape', 'public');
        }

        // 6. Crear Fondo Provisional
        $fondoProvisional = FondoProvisional::create([
            'prestamo_id'   => $request->prestamo_id,
            'asesor_id'     => auth()->id(),
            'monto_capital' => $request->monto_capital,
            'porcentaje'    => round($porcentajeReal, 2),
            'monto_fondo'   => $exonerado ? 0 : $montoPersonalizado,
            'fecha_entrega' => $request->fecha_entrega,
            'estado'        => $exonerado ? FondoProvisional::ESTADO_EXONERADO : FondoProvisional::ESTADO_ENTREGADO,
            'observaciones' => $request->observaciones,
        ]);

        // 7. Crear Operación (Solo si no es exonerado)
        if (!$exonerado) {
            $operacion = Operacion::create([
                'prestamo_id'      => $request->prestamo_id,
                'cliente_id'       => $prestamo->cliente_id,
                'fecha'            => $request->fecha_entrega,
                'metodo_pago_id'   => $request->metodo_pago,
                'abono'            => $montoPersonalizado,
                'numero_operacion'    => $request->numero_operacion_yape, 
                
                'tipo_operacion'   => 'Fondo Provisional',
                'estado_rendicion' => 'pendiente',
                'user_id'          => auth()->id(),
                'comentario'       => "Fondo provisional (" . round($porcentajeReal, 2) . "%): " . ($request->observaciones ?? ''),
                'voucher_path'     => $imagenYapePath, 
            ]);

            // Vincular
            $fondoProvisional->update(['operacion_id' => $operacion->id]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $exonerado ? 'Fondo provisional exonerado' : 'Fondo provisional registrado',
            'data' => [
                'fondo' => $fondoProvisional,
                'voucher_url' => $imagenYapePath ? asset('storage/' . $imagenYapePath) : null
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error al registrar el fondo provisional',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
