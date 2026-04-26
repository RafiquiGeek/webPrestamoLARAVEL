<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdjuntoGestion;
use App\Models\Compromiso;
use App\Models\EstadoGestion;
use App\Models\Gestion;
use App\Models\PagoGestion;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GestionController extends Controller
{
    /**
     * Listar gestiones
     */
    public function index(Request $request): JsonResponse
    {
        try {
        $query = Gestion::with([
            'prestamo.cliente.persona',
            'estadoGestion',
            'user',
            'compromiso',
            'adjuntos',
            'pago',
        ]);            // Filtros
            if ($request->has('prestamo_id')) {
                $query->where('prestamo_id', $request->prestamo_id);
            }

            if ($request->has('estado_gestion_id')) {
                $query->where('estado_id', $request->estado_gestion_id);
            }

            if ($request->has('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->has('fecha_desde')) {
                $query->where('fecha', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha', '<=', $request->fecha_hasta);
            }

            if ($request->has('tipo_gestion')) {
                $query->where('tipo_gestion', $request->tipo_gestion);
            }

            // Búsqueda por cliente
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('prestamo.cliente.persona', function ($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                        ->orWhere('ape_pat', 'like', "%{$search}%")
                        ->orWhere('ape_mat', 'like', "%{$search}%")
                        ->orWhere('documento', 'like', "%{$search}%");
                });
            }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $gestiones = $query->orderBy('fecha', 'desc')->paginate($perPage);

        // Asegurar que los accessors se incluyan en el JSON
        $gestiones->getCollection()->transform(function ($gestion) {
            $gestion->append(['nombre_cliente', 'dni_cliente']);
            return $gestion;
        });

        return response()->json([
                'success' => true,
                'data' => $gestiones->items(),
                'pagination' => [
                    'current_page' => $gestiones->currentPage(),
                    'last_page' => $gestiones->lastPage(),
                    'per_page' => $gestiones->perPage(),
                    'total' => $gestiones->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las gestiones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear nueva gestión
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'prestamo_id' => 'required|exists:prestamos,id',
                'estado_gestion_id' => 'required|exists:estados_gestion,id',
                'tipo_gestion' => 'required|in:llamada,visita,mensaje,whatsapp,email,sms,presencial,virtual',
                'observaciones' => 'required|string|max:1000',
                'resultado' => 'required|in:contacto_exitoso,no_contesta,telefono_incorrecto,promesa_pago,pago_parcial,refinanciamiento,cliente_molesto,otros',
                'fecha' => 'nullable|date',
                'fecha_siguiente_gestion' => 'nullable|date|after:now',
                'monto_prometido' => 'nullable|numeric|min:0',
                'fecha_promesa' => 'nullable|date|after_or_equal:today',
                'adjuntos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $prestamo = Prestamo::find($request->prestamo_id);
            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            DB::beginTransaction();

            // Mapear tipo_gestion si es necesario
            $tipoGestion = $request->tipo_gestion;
            if ($tipoGestion === 'visita') {
                $tipoGestion = 'presencial';
            }

            // Crear gestión
            $gestion = Gestion::create([
                'prestamo_id' => $request->prestamo_id,
                'estado_id' => $request->estado_gestion_id,
                'usuario_id' => auth()->id(),
                'tipo_gestion' => $tipoGestion,
                'observaciones' => $request->observaciones,
                'resultado' => $request->resultado,
                'fecha' => $request->fecha ?? now(),
                'fecha_siguiente_gestion' => $request->fecha_siguiente_gestion,
                'monto_prometido' => $request->monto_prometido,
                'fecha_promesa' => $request->fecha_promesa,
            ]);

            // Crear compromiso si es promesa de pago o si se especifica crear compromiso
            if (($request->resultado === 'promesa_pago' && $request->monto_prometido && $request->fecha_promesa) ||
                ($request->has('compromisoPago') && $request->compromisoPago == '1' && $request->has('monto') && $request->monto && $request->has('fecha_compromiso'))) {
                $compromiso = Compromiso::create([
                    'prestamo_id' => $request->prestamo_id,
                    'gestion_id' => $gestion->id,
                    'usuario_id' => auth()->id(),
                    'monto_compromiso' => $request->monto_prometido,
                    'fecha_compromiso' => $request->fecha_promesa,
                    'observaciones' => 'Compromiso generado desde gestión #'.$gestion->id,
                    'estado' => Compromiso::ESTADO_PENDIENTE,
                ]);

                $gestion->update(['compromiso_id' => $compromiso->id]);
            }

            // Subir adjuntos si existen
            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $adjunto) {
                    $path = $adjunto->store('gestiones/adjuntos', 'public');
                    
                    $extension = strtolower($adjunto->getClientOriginalExtension());
                    
                    // Lógica para determinar el TIPO de archivo según tu Modelo
                    $tipoArchivo = 'documento'; // Valor por defecto
                    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
                        $tipoArchivo = 'foto';
                    } elseif (in_array($extension, ['mp3', 'wav', 'ogg'])) {
                        $tipoArchivo = 'audio';
                    } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
                        $tipoArchivo = 'video';
                    }

                    AdjuntoGestion::create([
                        'gestion_id' => $gestion->id,
                        'nombre_archivo' => $adjunto->getClientOriginalName(),
                        'nombre_archivo_sistema' => basename($path), // Agregado por seguridad si lo necesitas
                        'ruta_archivo' => $path,
                        
                        // CORRECCIÓN PRINCIPAL:
                        'tipo_archivo' => $tipoArchivo, // Guarda 'foto' o 'documento'
                        'extension' => $extension,      // Guarda 'jpg', 'pdf'
                        
                        // CORRECCIÓN DE NOMBRES DE COLUMNA (según tu $fillable):
                        'tamaño' => $adjunto->getSize(), // Tu modelo dice 'tamaño', tu controller decía 'tamaño_archivo'
                        'subido_por' => auth()->id(),    // Dato útil definido en tu fillable
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gestión creada exitosamente',
                'data' => $gestion->load([
                    'prestamo.cliente.persona',
                    'estadoGestion',
                    'user',
                    'compromiso',
                    'adjuntos',
                ])->append(['nombre_cliente', 'dni_cliente']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la gestión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Mostrar gestión específica
     */
    public function show($id): JsonResponse
    {
        try {
            $gestion = Gestion::with([
                'prestamo.cliente.persona',
                'prestamo.cuotas',
                'user',
                'compromiso',
                'adjuntos',
                'pago',
            ])->find($id);

            // 2. Validación manual de no encontrado
            if (! $gestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gestión no encontrada',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $gestion->append(['nombre_cliente', 'dni_cliente']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la gestión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar gestión
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $gestion = Gestion::find($id);

            if (! $gestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta Gestión no encontrada',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'estado_id' => 'sometimes|exists:estados_gestion,id',
                'observaciones' => 'sometimes|string|max:1000',
                'resultado' => 'sometimes|in:contacto_exitoso,no_contesta,telefono_incorrecto,promesa_pago,pago_parcial,refinanciamiento,cliente_molesto,otros',
                'fecha_siguiente_gestion' => 'nullable|date|after:now',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $gestion->update([
                'estado_id' => $request->estado_gestion_id ?? $gestion->estado_id,
                'observaciones' => $request->observaciones,
                'resultado' => $request->resultado,
                'fecha_siguiente_gestion' => $request->fecha_siguiente_gestion,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gestión actualizada exitosamente',
                'data' => $gestion->load(['estadoGestion', 'user']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la gestión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar gestiones de un préstamo
     */
    public function gestionesPrestamo($prestamoId, Request $request): JsonResponse
    {
        try {
            $prestamo = Prestamo::find($prestamoId);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            $query = Gestion::with([
                'estadoGestion',
                'user',
                'compromiso',
                'adjuntos',
                'pago',
            ])->where('prestamo_id', $prestamoId);

            // Filtros
            if ($request->has('tipo_gestion')) {
                $query->where('tipo_gestion', $request->tipo_gestion);
            }

            if ($request->has('resultado')) {
                $query->where('resultado', $request->resultado);
            }

            $gestiones = $query->orderBy('fecha', 'desc')->get();

            // Asegurar que los accessors se incluyan
            $gestiones->transform(function ($gestion) {
                $gestion->append(['nombre_cliente', 'dni_cliente']);
                return $gestion;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'prestamo' => [
                        'id' => $prestamo->id,
                        'codigo' => $prestamo->codigo,
                        'cliente' => $prestamo->cliente->persona ?? null,
                    ],
                    'gestiones' => $gestiones,
                    'resumen' => [
                        'total_gestiones' => $gestiones->count(),
                        'ultima_gestion' => $gestiones->first()?->fecha,
                        'proxima_gestion' => $gestiones->whereNotNull('fecha_siguiente_gestion')
                            ->where('fecha_siguiente_gestion', '>', now())
                            ->min('fecha_siguiente_gestion'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener gestiones del préstamo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar pago en gestión
     */
    public function registrarPago(Request $request, $gestionId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'monto_pagado' => 'required|numeric|min:0.01',
                'metodo_pago' => 'required|in:efectivo,transferencia,yape,plin,cheque,deposito',
                'numero_operacion' => 'nullable|string|max:50',
                'observaciones' => 'nullable|string|max:500',
                'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $gestion = Gestion::find($gestionId);

            if (! $gestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gestión no encontrada',
                ], 404);
            }

            DB::beginTransaction();

            // Subir comprobante si existe
            $comprobantePath = null;
            if ($request->hasFile('comprobante')) {
                $comprobantePath = $request->file('comprobante')
                    ->store('gestiones/pagos', 'public');
            }

            // Registrar pago en gestión
            $pagoGestion = PagoGestion::create([
                'gestion_id' => $gestion->id,
                'prestamo_id' => $gestion->prestamo_id,
                'usuario_id' => auth()->id(),
                'monto_pagado' => $request->monto_pago,
                'metodo_pago' => $request->metodo_pago, //PagoGestion::TIPO_CUOTA
                'numero_operacion' => $request->numero_operacion,
                'observaciones' => $request->observaciones,
                'comprobante' => $comprobantePath,
                'fecha_pago' => now(),
            ]);

            // Actualizar resultado de gestión
            $gestion->update(['resultado' => 'pago_parcial']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente en la gestión',
                'data' => $pagoGestion->load(['gestion', 'user']),
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
     * Obtener estados de gestión disponibles
     */
    public function estadosGestion(): JsonResponse
    {
        try {
            $estados = EstadoGestion::orderBy('id')->get();

            return response()->json([
                'success' => true,
                'data' => $estados,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estados de gestión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de gestiones
     */
    public function estadisticas(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth());
            $fechaFin = $request->get('fecha_fin', now()->endOfMonth());
            $usuarioId = $request->get('usuario_id');

            $query = Gestion::whereBetween('fecha', [$fechaInicio, $fechaFin]);

            if ($usuarioId) {
                $query->where('usuario_id', $usuarioId);
            }

            $gestiones = $query->get();

            $estadisticas = [
                'total_gestiones' => $gestiones->count(),
                'gestiones_por_tipo' => $gestiones->groupBy('tipo_gestion')->map->count(),
                'gestiones_por_resultado' => $gestiones->groupBy('resultado')->map->count(),
                'promesas_pago' => $gestiones->where('resultado', 'promesa_pago')->count(),
                'pagos_parciales' => $gestiones->where('resultado', 'pago_parcial')->count(),
                'contactos_exitosos' => $gestiones->where('resultado', 'contacto_exitoso')->count(),
                'no_contactos' => $gestiones->where('resultado', 'no_contesta')->count(),
                'gestiones_por_dia' => $gestiones->groupBy(function ($gestion) {
                    return Carbon::parse($gestion->fecha)->format('Y-m-d');
                })->map->count()->sortKeys(),
                'monto_total_prometido' => $gestiones->whereNotNull('monto_prometido')->sum('monto_prometido'),
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'periodo' => [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas de gestiones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener agenda de gestiones pendientes
     */
    public function agenda(Request $request): JsonResponse
    {
        try {
            $fecha = $request->get('fecha', now()->format('Y-m-d'));
            $usuarioId = $request->get('usuario_id', auth()->id());

            $gestiones = Gestion::with([
                'prestamo.cliente.persona',
                'estadoGestion',
            ])
                ->where('fecha', '>=', $fecha.' 00:00:00')
                ->where('fecha', '<=', $fecha.' 23:59:59')
                ->where('usuario_id', $usuarioId)
                ->orderBy('fecha')
                ->get();

            $gestionesVencidas = Gestion::with([
                'prestamo.cliente.persona',
                'estadoGestion',
            ])
                ->where('fecha', '<', now())
                ->where('usuario_id', $usuarioId)
                ->whereNull('fecha')
                ->orderBy('fecha')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'fecha_consulta' => $fecha,
                    'gestiones_programadas' => $gestiones,
                    'gestiones_vencidas' => $gestionesVencidas,
                    'resumen' => [
                        'total_programadas' => $gestiones->count(),
                        'total_vencidas' => $gestionesVencidas->count(),
                        'total_pendientes' => $gestiones->count() + $gestionesVencidas->count(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener agenda de gestiones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
