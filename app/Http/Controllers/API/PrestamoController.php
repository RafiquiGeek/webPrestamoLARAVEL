<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Cuenta;
use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PrestamoController extends Controller
{

    /**
     * Lista de préstamos con paginación y filtros
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 25);
            $estado = $request->input('estado', '');
            $search = $request->input('search', '');
            $sucursalId = $request->input('sucursal_id', '');

            // Query base
            $baseQuery = Prestamo::query();

            // 🔐 FILTRO POR ROL: Solo asesores ven su cartera, admin y analista ven todo
            $userHasFullAccess = $user->hasAnyRole(['Admin', 'Analista']);

            if (!$userHasFullAccess) {
                // Si NO es admin ni analista, filtrar por su cartera (solo asesores)
                $baseQuery->whereIn('id', function ($sub) use ($user) {
                    $sub->select('prestamo_id')
                        ->from('carteras_asesor')
                        ->where('asesor_id', $user->id)
                        ->where('estado', 1)
                        ->whereNotNull('prestamo_id');
                });
            }

            // Filtrar por estado si se proporciona
            if (!empty($estado)) {
                $baseQuery->where('estado', $estado);
            }

            // Filtrar por sucursal (usando cuenta->codigo)
            if (!empty($sucursalId)) {
                $codigoCuenta = (int) $sucursalId - 1;
                $baseQuery->whereHas('cuenta', function ($q) use ($codigoCuenta) {
                    $q->where('codigo', $codigoCuenta);
                });
            }

            // Búsqueda por DNI o nombres
            if (!empty($search)) {
                $words = array_values(array_filter(explode(' ', trim($search))));

                $baseQuery->whereHas('cliente.persona', function ($q) use ($search, $words) {
                    $q->where('documento', 'like', '%' . $search . '%')
                        ->orWhere(function ($sub) use ($words) {
                            foreach ($words as $word) {
                                $sub->where(function ($inner) use ($word) {
                                    $inner->where('nombres', 'like', '%' . $word . '%')
                                        ->orWhere('ape_pat', 'like', '%' . $word . '%')
                                        ->orWhere('ape_mat', 'like', '%' . $word . '%');
                                });
                            }
                        });
                });
            }

            // Calcular contadores por estado
            $contadores = [
                'todos' => (clone $baseQuery)->count(),
                'nueva_solicitud' => (clone $baseQuery)->where('estado', 'Nueva Solicitud')->count(),
                'aprobado' => (clone $baseQuery)->where('estado', 'Aprobado')->count(),
                'por_desembolsar' => (clone $baseQuery)->where('estado', 'Por Desembolsar')->count(),
                'vigente' => (clone $baseQuery)->where('estado', 'Vigente')->count(),
                'moroso' => (clone $baseQuery)->where('estado', 'Moroso')->count(),
                'con_convenio' => (clone $baseQuery)->where('estado', 'Con Convenio')->count(),
                'liquidado' => (clone $baseQuery)->where('estado', 'Liquidado')->count(),
                'cancelado' => (clone $baseQuery)->where('estado', 'Cancelado')->count(),
                'finalizado' => (clone $baseQuery)->where('estado', 'Finalizado')->count(),
            ];

            // Query con relaciones
            $query = Prestamo::with([
                'cliente.persona.direcciones.sucursal',
                'cuenta'
            ]);

            // 🔐 APLICAR MISMO FILTRO DE ROL A LA QUERY PRINCIPAL
            if (!$userHasFullAccess) {
                $query->whereIn('id', function ($sub) use ($user) {
                    $sub->select('prestamo_id')
                        ->from('carteras_asesor')
                        ->where('asesor_id', $user->id)
                        ->where('estado', 1)
                        ->whereNotNull('prestamo_id');
                });
            }

            if (!empty($estado)) {
                $query->where('estado', $estado);
            }

            if (!empty($sucursalId)) {
                $codigoCuenta = (int) $sucursalId - 1;
                $query->whereHas('cuenta', function ($q) use ($codigoCuenta) {
                    $q->where('codigo', $codigoCuenta);
                });
            }

            if (!empty($search)) {
                $words = array_values(array_filter(explode(' ', trim($search))));
                $query->whereHas('cliente.persona', function ($q) use ($search, $words) {
                    $q->where('documento', 'like', '%' . $search . '%')
                        ->orWhere(function ($sub) use ($words) {
                            foreach ($words as $word) {
                                $sub->where(function ($inner) use ($word) {
                                    $inner->where('nombres', 'like', '%' . $word . '%')
                                        ->orWhere('ape_pat', 'like', '%' . $word . '%')
                                        ->orWhere('ape_mat', 'like', '%' . $word . '%');
                                });
                            }
                        });
                });
            }

            $query->orderBy('created_at', 'desc');

            $prestamos = $query->paginate($perPage);

            // ─── Adjuntar sucursal desde direcciones ──────────────────────
            foreach ($prestamos->items() as $prestamo) {
                if ($prestamo->cliente?->persona) {
                    $direccion = $prestamo->cliente->persona->direcciones
                        ->where('estado', 1)->first();
                    if ($direccion?->sucursal) {
                        $prestamo->sucursal = [
                            'id' => $direccion->sucursal->id,
                            'nombre' => $direccion->sucursal->sucursal,
                            'codigo' => $direccion->sucursal->codigo ?? ''
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $prestamos->items(),
                'pagination' => [
                    'total' => $prestamos->total(),
                    'per_page' => $prestamos->perPage(),
                    'current_page' => $prestamos->currentPage(),
                    'last_page' => $prestamos->lastPage(),
                    'from' => $prestamos->firstItem(),
                    'to' => $prestamos->lastItem(),
                ],
                'contadores' => $contadores
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener préstamos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detalle de un préstamo específico
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal',
                'aval.persona.direcciones.sucursal',
                'cuenta.entidadBancaria',
                'cuentaCliente.entidadBancaria',      // cuentaCliente
                'cuentaCliente.billeteraDigital',     // billetera
                'cuentaCliente.tipoCuenta',           // tipo cuenta
                'carterasAnalista.user.persona.telefonos',
                'carterasJcc.user.persona.telefonos',
                'carterasAsesor.user.persona.telefonos',
                'fondoProvisional',
            ])->find($id);

            if (!$prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado'
                ], 404);
            }

            // Agregar información de dirección y zona desde la persona del cliente
            if ($prestamo->cliente && $prestamo->cliente->persona) {
                // Buscar dirección directamente en la tabla direcciones
                $direccion = \App\Models\Direccion::where('persona_id', $prestamo->cliente->persona->id)
                    ->where('estado', 1)
                    ->with('sucursal.zonas')
                    ->first();

                if ($direccion) {
                    if ($direccion->sucursal) {
                        $prestamo->sucursal = [
                            'id' => $direccion->sucursal->id,
                            'nombre' => $direccion->sucursal->sucursal,
                            'codigo' => $direccion->sucursal->codigo ?? ''
                        ];
                    }

                    // Agregar dirección del cliente
                    $prestamo->direccion_cliente = $direccion->direccion ?? 'No disponible';

                    // Agregar zona desde las zonas de la sucursal
                    $zonaNombre = 'N/A';
                    if ($direccion->sucursal && $direccion->sucursal->zonas->isNotEmpty()) {
                        $zonaNombre = $direccion->sucursal->zonas->first()->nombre ?? 'N/A';
                    }
                    $prestamo->zona = $zonaNombre;
                } else {
                    $prestamo->direccion_cliente = 'No disponible';
                    $prestamo->zona = 'N/A';
                }
            } else {
                $prestamo->direccion_cliente = 'No disponible';
                $prestamo->zona = 'N/A';
            }

            // Agregar información del equipo (analista, asesor, jcc) con teléfonos
            $prestamo->analista = null;
            $prestamo->asesor = null;
            $prestamo->jcc = null;

            if ($prestamo->carterasAnalista && $prestamo->carterasAnalista->first()) {
                $carteraAnalista = $prestamo->carterasAnalista->first();
                if ($carteraAnalista->user) {
                    $prestamo->analista = [
                        'id' => $carteraAnalista->user->id,
                        'codigo' => $carteraAnalista->user->codigo ?? '',
                        'name' => $carteraAnalista->user->name,
                        'rol' => 'Analista',
                        'telefonos' => $carteraAnalista->user->persona && $carteraAnalista->user->persona->telefonos ?
                            $carteraAnalista->user->persona->telefonos->map(function ($telefono) {
                                return ['numero' => $telefono->numero];
                            })->toArray() : []
                    ];
                }
            }

            if ($prestamo->carterasAsesor && $prestamo->carterasAsesor->first()) {
                $carteraAsesor = $prestamo->carterasAsesor->first();
                if ($carteraAsesor->user) {
                    $prestamo->asesor = [
                        'id' => $carteraAsesor->user->id,
                        'codigo' => $carteraAsesor->user->codigo ?? '',
                        'name' => $carteraAsesor->user->name,
                        'rol' => 'Asesor',
                        'telefonos' => $carteraAsesor->user->persona && $carteraAsesor->user->persona->telefonos ?
                            $carteraAsesor->user->persona->telefonos->map(function ($telefono) {
                                return ['numero' => $telefono->numero];
                            })->toArray() : []
                    ];
                }
            }

            if ($prestamo->carterasJcc && $prestamo->carterasJcc->first()) {
                $carteraJcc = $prestamo->carterasJcc->first();
                if ($carteraJcc->user) {
                    $prestamo->jcc = [
                        'id' => $carteraJcc->user->id,
                        'codigo' => $carteraJcc->user->codigo ?? '',
                        'name' => $carteraJcc->user->name,
                        'rol' => 'JCC',
                        'telefonos' => $carteraJcc->user->persona && $carteraJcc->user->persona->telefonos ?
                            $carteraJcc->user->persona->telefonos->map(function ($telefono) {
                                return ['numero' => $telefono->numero];
                            })->toArray() : []
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $prestamo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el préstamo',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    /**
     * Obtener cuotas de un préstamo
     */
    public function cuotas($id)
    {
        try {
            $user = Auth::user();

            $prestamo = Prestamo::find($id);

            if (!$prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado'
                ], 404);
            }

            // Precalcular el saldo capital inicial
            $saldoCapitalInicial = $prestamo->cuotas()->sum('monto');
            $capitalPagadoAcumulado = 0;

            $cuotas = $prestamo->cuotas()
                ->with(['moras', 'operaciones.metodoDePago', 'operaciones.user'])
                ->orderBy('numero')
                ->get()
                ->map(function ($cuota) use (&$capitalPagadoAcumulado, $saldoCapitalInicial) {

                    // Calcular monto pagado desde operaciones_cuota (igual que en la vista)
                    $abonoTotal = DB::table('operaciones_cuota')
                        ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                        ->where('operaciones_cuota.cuota_id', $cuota->id)
                        ->where('operaciones.estado', '!=', 'anulado')
                        ->where(function ($query) {
                        // Solo incluir operaciones SIN hijas, o que sean hijas ellas mismas
                        $query->whereNotNull('operaciones.operacion_general_id') // Es una operación hija
                            ->orWhereNotExists(function ($subquery) {
                            $subquery->selectRaw('1')
                                ->from('operaciones as ops_hijas')
                                ->whereColumn('ops_hijas.operacion_general_id', 'operaciones.id');
                        });
                    })
                        ->sum('operaciones_cuota.monto_aplicado');

                    $monto = (float) ($cuota->monto ?? 0);
                    $montoPagado = (float) ($abonoTotal ?? 0);
                    $saldo = $monto - $montoPagado;

                    // Actualizar capital pagado acumulado
                    $capitalPagadoAcumulado += $montoPagado;

                    // Calcular saldo capital (igual que en la vista)
                    $saldoCapital = max(0, $saldoCapitalInicial - $capitalPagadoAcumulado);

                    // Calcular porcentaje de pago
                    $porcentajePago = $monto > 0 ? ($montoPagado / $monto) * 100 : 0;
                    if ($porcentajePago > 100) {
                        $porcentajePago = 100;
                    }

                    // Calcular mora pagada limitada
                    $moraPagadaLimitada = (float) ($cuota->monto_pagado_moras_limitado ?? 0);

                    // Calcular mora pendiente considerando abonos a favor
                    $moraPendienteBase = (float) ($cuota->monto_pendiente_moras ?? 0);
                    $abonoFavor = (float) ($cuota->saldoMoraFavor ?? 0);
                    $moraPendienteCalculada = $moraPendienteBase - $abonoFavor;

                    // Obtener la última operación
                    $ultimaOperacion = $cuota->operaciones
                        ->where('estado', '!=', 'anulado')
                        ->sortByDesc('fecha')
                        ->first();

                    // Determinar estado basado en pagos
                    if ($montoPagado >= $monto) {
                        $estadoTexto = 'Pagado';
                        $estadoClass = 'success';
                    } elseif ($montoPagado > 0) {
                        $estadoTexto = 'Pago parcial';
                        $estadoClass = 'warning';
                    } else {
                        $estaVencida = \Carbon\Carbon::parse($cuota->fecha_pago)->isPast();
                        if ($estaVencida) {
                            $estadoTexto = 'Vencida';
                            $estadoClass = 'danger';
                        } else {
                            $estadoTexto = 'Pendiente';
                            $estadoClass = 'secondary';
                        }
                    }

                    // Mapear moras (solo no regularizadas)
                    $morasPendientes = [];
                    if ($cuota->moras && $cuota->moras->count() > 0) {
                        foreach ($cuota->moras as $mora) {
                            // Excluir moras regularizadas
                            if ($mora->estado != 3) { // 3 = Regularizada
                                $moraMonto = (float) ($mora->monto ?? 0);
                                $moraMontoPagado = (float) ($mora->monto_pagado ?? 0);
                                $moraSaldo = $moraMonto - $moraMontoPagado;

                                $morasPendientes[] = [
                                    'id' => $mora->id,
                                    'fecha' => $mora->fecha,
                                    'dias_mora' => $mora->dias_mora,
                                    'monto' => $moraMonto,
                                    'monto_pagado' => $moraMontoPagado,
                                    'saldo' => $moraSaldo,
                                    'estado' => is_object($mora->estado) ? $mora->estado->value : (int) $mora->estado,
                                    'estado_nombre' => $mora->estado_nombre ?? 'Pendiente',
                                ];
                            }
                        }
                    }

                    // Mapear operaciones/pagos
                    $operacionesPagos = [];
                    if ($cuota->operaciones && $cuota->operaciones->count() > 0) {
                        foreach ($cuota->operaciones->where('estado', '!=', 'anulado') as $operacion) {
                            $operacionesPagos[] = [
                                'id' => $operacion->id,
                                'codigo' => $operacion->codigo ?? $operacion->id,
                                'fecha' => $operacion->fecha,
                                'abono' => (float) ($operacion->abono ?? 0),
                                'metodo_pago' => $operacion->metodoDePago ? $operacion->metodoDePago->metodo_pago : 'N/A',
                                'usuario' => $operacion->user ? $operacion->user->codigo : 'N/A',
                                'comentario' => $operacion->comentario ?? null,
                                'voucher_path' => $operacion->voucher_path ?? null,
                            ];
                        }
                    }

                    return [
                        'id' => $cuota->id,
                        'prestamo_id' => $cuota->prestamo_id,
                        'numero_cuota' => (int) $cuota->numero,
                        'fecha_vencimiento' => $cuota->fecha_pago,
                        'fecha_pago' => $cuota->fecha_pago,
                        'monto' => $monto,
                        'monto_pagado' => $montoPagado,
                        'saldo' => $saldo,
                        'saldo_capital' => $saldoCapital,
                        'porcentaje_pago' => round($porcentajePago, 2),
                        'estado' => is_object($cuota->estado) ? $cuota->estado->value : (int) $cuota->estado,
                        'estado_texto' => $estadoTexto,
                        'estado_class' => $estadoClass,
                        'pago_capital' => (float) ($cuota->pago_capital ?? 0),
                        'interes' => (float) ($cuota->interes ?? 0),
                        'comision' => (float) ($cuota->comision ?? 0),
                        'igv' => (float) ($cuota->igv ?? 0),
                        'gas' => (float) ($cuota->gas ?? 0),
                        'cantidad_mora' => (float) ($cuota->cantidad_mora ?? 0),
                        'mora_pagada_limitado' => $moraPagadaLimitada,
                        'mora_pendiente' => round($moraPendienteCalculada, 2),
                        'abono_favor' => $abonoFavor,
                        'moras' => $morasPendientes,
                        'operaciones' => $operacionesPagos,
                        'ultima_operacion' => $ultimaOperacion ? [
                            'id' => $ultimaOperacion->id,
                            'fecha' => $ultimaOperacion->fecha,
                            'metodo_pago' => $ultimaOperacion->metodoDePago ? $ultimaOperacion->metodoDePago->metodo_pago : 'N/A'
                        ] : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $cuotas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las cuotas',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }



    /**
     * Obtener datos del formulario (usuarios, cuentas, etc.)
     */
    public function formData()
    {
        try {
            // Obtener usuarios por rol con código - SOLO USUARIOS ACTIVOS (status = 1)
            $analistas = User::role('Analista')
                ->where('status', 1)
                ->select('id', 'name', 'codigo')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->codigo ?? $user->name, // Usar código si existe, sino name
                        'codigo' => $user->codigo,
                    ];
                });

            $asesores = User::role('Asesor')
                ->where('status', 1)
                ->select('id', 'name', 'codigo')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->codigo ?? $user->name, // Usar código si existe, sino name
                        'codigo' => $user->codigo,
                    ];
                });

            $jccs = User::role('JCC')
                ->where('status', 1)
                ->select('id', 'name', 'codigo')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->codigo ?? $user->name, // Usar código si existe, sino name
                        'codigo' => $user->codigo,
                    ];
                });

            // Obtener cuentas activas
            $cuentas = Cuenta::with('entidadBancaria')
                ->select('id', 'nro_cuenta', 'codigo', 'entidad_bancaria_id')
                ->get()
                ->map(function ($cuenta) {
                    return [
                        'id' => $cuenta->id,
                        'nombre' => $cuenta->entidadBancaria ?
                            $cuenta->entidadBancaria->razon_social . ' - ' . $cuenta->nro_cuenta :
                            $cuenta->nro_cuenta,
                        'codigo' => $cuenta->codigo,
                    ];
                });

            // Obtener departamentos
            $departamentos = Departamento::orderBy('departamento')->get(['id', 'departamento as nombre']);

            return response()->json([
                'success' => true,
                'data' => [
                    'analistas' => $analistas,
                    'asesores' => $asesores,
                    'jccs' => $jccs,
                    'cuentas' => $cuentas,
                    'departamentos' => $departamentos,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del formulario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular cuotas del préstamo
     */
    public function calcularCuotas(Request $request)
    {
        try {
            $validated = $request->validate([
                'monto' => 'required|numeric|min:0',
                'plazo' => 'required|integer|in:8,12,15,18,20',
                'fechaPrimerPago' => 'required|date',
                'mora' => 'required|numeric|min:0',
            ]);

            // Verificar que el controlador admin existe
            if (!class_exists('\App\Http\Controllers\Admin\PrestamosController')) {
                throw new \Exception('Controlador de cálculos no encontrado');
            }

            $montoSolicitado = $validated['monto'];
            $plazo = $validated['plazo'];
            $fechaPrimerPago = Carbon::parse($validated['fechaPrimerPago']);
            $mora = $validated['mora'];

            $prestamosController = new \App\Http\Controllers\Admin\PrestamosController();

            if ($plazo == 8) {
                if (!method_exists($prestamosController, 'calcularCuotas8Semanas')) {
                    throw new \Exception('Método calcularCuotas8Semanas no encontrado');
                }
                $resultado = $prestamosController->calcularCuotas8Semanas($montoSolicitado, $fechaPrimerPago);
            } else {
                if (!method_exists($prestamosController, 'calcularCuotasInterno')) {
                    throw new \Exception('Método calcularCuotasInterno no encontrado');
                }
                $resultado = $prestamosController->calcularCuotasInterno($montoSolicitado, $plazo, $fechaPrimerPago);
            }

            // Verificar que el resultado es válido
            if (!isset($resultado['cuotas']) || !is_array($resultado['cuotas'])) {
                throw new \Exception('El cálculo no retornó cuotas válidas');
            }

            // Mapear fecha_pago a fecha para compatibilidad con app móvil
            if (isset($resultado['cuotas']) && is_array($resultado['cuotas'])) {
                $resultado['cuotas'] = array_map(function ($cuota) {
                    if (isset($cuota['fecha_pago'])) {
                        $cuota['fecha'] = $cuota['fecha_pago'];
                    }
                    return $cuota;
                }, $resultado['cuotas']);
            }

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al calcular cuotas API: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al calcular las cuotas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo préstamo
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'direccion_cobro_id' => 'required|integer',
                'analista_id' => 'required|exists:users,id',
                'asesor_id' => 'required|exists:users,id',
                'jcc_id' => 'required|exists:users,id',
                'cuenta_id' => 'required|exists:cuentas,id',
                'tipo_solicitud' => 'required|in:Nueva,Renovación',
                'fecha_atencion' => 'required|date',
                'fecha_primer_pago' => 'required|date',
                'plazo' => 'required|integer|in:8,12,15,18,20',
                'cantidad_solicitada' => 'required|numeric|min:0',
                'cuenta_cliente_id' => 'required|integer',
                'mora' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string',
                'tiene_aval' => 'boolean',
                'aval_dni' => 'required_if:tiene_aval,true|nullable|size:8',
                'parentesco' => 'nullable|string',
                'observaciones_aval' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
            ]);

            \DB::beginTransaction();

            // Crear el préstamo
            $prestamoData = [
                'cliente_id' => $validated['cliente_id'],
                'direccion_cobro_id' => $validated['direccion_cobro_id'],
                'estado' => 'Nueva Solicitud',
                'tipo_solicitud' => $validated['tipo_solicitud'],
                'cuenta_id' => $validated['cuenta_id'],
                'fecha_atencion' => $validated['fecha_atencion'],
                'fecha_primer_pago' => $validated['fecha_primer_pago'],
                'cantidad_solicitada' => $validated['cantidad_solicitada'],
                'cuenta_cliente_id' => $validated['cuenta_cliente_id'],
                'plazo' => $validated['plazo'],
                'mora' => $validated['mora'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
            ];

            // Agregar coordenadas GPS si están disponibles
            // Actualizar coordenadas en la dirección de cobro
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                \App\Models\Direccion::where('id', $validated['direccion_cobro_id'])
                    ->update([
                        'latitud' => $validated['latitude'],
                        'longitud' => $validated['longitude']
                    ]);
            }

            $prestamo = Prestamo::create($prestamoData);

            // Calcular y generar las cuotas
            $prestamosController = new \App\Http\Controllers\Admin\PrestamosController();

            if ($validated['plazo'] == 8) {
                $resultadoCuotas = $prestamosController->calcularCuotas8Semanas(
                    $validated['cantidad_solicitada'],
                    Carbon::parse($validated['fecha_primer_pago'])
                );
            } else {
                $resultadoCuotas = $prestamosController->calcularCuotasInterno(
                    $validated['cantidad_solicitada'],
                    $validated['plazo'],
                    Carbon::parse($validated['fecha_primer_pago'])
                );
            }

            // Verificar que el resultado contiene las cuotas
            if (!isset($resultadoCuotas['cuotas']) || !is_array($resultadoCuotas['cuotas'])) {
                throw new \Exception('Error al calcular las cuotas.');
            }

            // Guardar las cuotas
            foreach ($resultadoCuotas['cuotas'] as $cuotaData) {
                \App\Models\Cuota::create([
                    'prestamo_id' => $prestamo->id,
                    'fecha_pago' => $cuotaData['fecha_pago'],
                    'numero' => $cuotaData['numero'],
                    'monto' => $cuotaData['cuota'],
                    'pago_capital' => $cuotaData['pagoCapital'] ?? null,
                    'interes' => $cuotaData['interes'] ?? null,
                    'comision' => $cuotaData['comision'] ?? null,
                    'igv' => $cuotaData['igv'] ?? null,
                    'cantidad_mora' => 0,
                    'estado' => 0, // Estado pendiente
                ]);
            }

            // Crear asignaciones de cartera
            \App\Models\CarteraAnalista::create([
                'prestamo_id' => $prestamo->id,
                'analista_id' => $validated['analista_id'],
                'fecha_registro' => now(),
                'estado' => 1,
            ]);

            \App\Models\CarteraAsesor::create([
                'prestamo_id' => $prestamo->id,
                'asesor_id' => $validated['asesor_id'],
                'fecha_registro' => now(),
                'estado' => 1,
            ]);

            \App\Models\CarteraJcc::create([
                'prestamo_id' => $prestamo->id,
                'jcc_id' => $validated['jcc_id'],
                'fecha_registro' => now(),
                'estado' => 1,
            ]);

            // Manejar aval si existe
            if (isset($validated['tiene_aval']) && $validated['tiene_aval'] && !empty($validated['aval_dni'])) {
                $personaAval = \App\Models\Persona::where('documento', $validated['aval_dni'])->first();

                if ($personaAval) {
                    \App\Models\Aval::create([
                        'prestamo_id' => $prestamo->id,
                        'persona_id' => $personaAval->id,
                        'parentesco' => $validated['parentesco'] ?? null,
                        'observaciones' => $validated['observaciones_aval'] ?? null,
                    ]);
                }
            }

            \DB::commit();

            // Cargar relaciones para la respuesta
            $prestamo->load(['cliente.persona', 'cuenta', 'cuotas']);

            return response()->json([
                'success' => true,
                'message' => 'Préstamo creado exitosamente con ' . count($resultadoCuotas['cuotas']) . ' cuotas',
                'data' => $prestamo
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Error al crear préstamo API: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el préstamo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si existe contrato de mutuo
     */
    public function checkContratoMutuo($id)
    {
        try {
            // Verificar si existe el documento en la base de datos
            $documento = \App\Models\PrestamoDocument::where('prestamo_id', $id)
                ->where('document_type', 'contrato_mutuo')
                ->latest()
                ->first();

            $exists = false;
            $filename = null;

            if ($documento) {
                // Verificar que el archivo físico también exista
                $path = storage_path("app/public/{$documento->file_path}");
                $exists = file_exists($path);
                $filename = $documento->filename;
            }

            return response()->json([
                'exists' => $exists,
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar contrato de mutuo
     */
    public function generateContratoMutuo($id)
    {
        try {
            \Log::info('🔵 Iniciando generación de contrato para préstamo: ' . $id);

            $prestamo = Prestamo::with([
                'cliente.persona',
                'cliente.direcciones',
                'aval.persona',
                'cuotas',
                'cuenta'
            ])->findOrFail($id);

            $firmaPath = public_path('img/pdf/firma.png');
            $firmaBase64 = '';
            if (file_exists($firmaPath)) {
                $firmaBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($firmaPath));
            }
            // resources/views/pdf/contrato-mutuo.blade.php
            $html = view('pdf.contrato-mutuo', compact('prestamo', 'firmaBase64'))->render();

            \Log::info('🔵 Préstamo cargado correctamente', ['prestamo_id' => $prestamo->id]);

            // Llamar al controlador web de documentos para generar el PDF
            \Log::info('🔵 Intentando instanciar PrestamoDocumentController');
            $controller = new \App\Http\Controllers\Admin\PrestamoDocumentController();

            \Log::info('🔵 Llamando a generateContratoMutuo del controlador web');
            $response = $controller->generateContratoMutuo($id);

            \Log::info('🔵 Respuesta recibida', ['status_code' => $response->getStatusCode()]);

            // Verificar si se generó correctamente
            if ($response->getStatusCode() === 200) {
                $responseData = json_decode($response->getContent(), true);
                \Log::info('✅ Contrato generado exitosamente');
                return response()->json([
                    'success' => true,
                    'message' => $responseData['message'] ?? 'Contrato generado exitosamente',
                    'filename' => "contrato_mutuo_{$prestamo->id}.pdf"
                ]);
            } else {
                \Log::error('❌ Error: Código de estado no es 200', ['status_code' => $response->getStatusCode()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar contrato'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('❌ Excepción al generar contrato', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al generar contrato',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Previsualizar contrato de mutuo
     */
    public function previewContratoMutuo($id)
    {
        try {
            // Buscar el documento en la base de datos
            $documento = \App\Models\PrestamoDocument::where('prestamo_id', $id)
                ->where('document_type', 'contrato_mutuo')
                ->latest()
                ->first();

            if (!$documento) {
                return response()->json([
                    'success' => false,
                    'message' => 'El contrato no existe. Genere el contrato primero.'
                ], 404);
            }

            // Construir la ruta completa al archivo
            $path = storage_path("app/public/{$documento->file_path}");

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo del contrato no existe en el servidor.'
                ], 404);
            }

            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $documento->filename . '"'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en previewContratoMutuo', [
                'prestamo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al previsualizar contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar contrato de mutuo
     */
    public function downloadContratoMutuo($id)
    {
        try {
            // Buscar el documento en la base de datos
            $documento = \App\Models\PrestamoDocument::where('prestamo_id', $id)
                ->where('document_type', 'contrato_mutuo')
                ->latest()
                ->first();

            if (!$documento) {
                return response()->json([
                    'success' => false,
                    'message' => 'El contrato no existe. Genere el contrato primero.'
                ], 404);
            }

            // Construir la ruta completa al archivo
            $path = storage_path("app/public/{$documento->file_path}");

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo del contrato no existe en el servidor.'
                ], 404);
            }

            return response()->download($path, $documento->filename, [
                'Content-Type' => 'application/pdf'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en downloadContratoMutuo', [
                'prestamo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar contrato',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir documentos para un cliente (asociados a un préstamo)
     */
    public function uploadDocumentos(Request $request, $prestamoId)
    {
        try {
            $prestamo = Prestamo::findOrFail($prestamoId);
            $clienteId = $prestamo->cliente_id;

            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'documentos' => 'required|array|min:1',
                'documentos.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $documentosGuardados = [];
            $directorioDocumentos = public_path('files/client_files');

            if (!file_exists($directorioDocumentos)) {
                mkdir($directorioDocumentos, 0755, true);
            }

            foreach ($request->file('documentos') as $index => $documento) {
                if ($documento && $documento->isValid()) {
                    $nombreDocumento = 'doc_cliente_' . $clienteId . '_prestamo_' . $prestamoId . '_' . time() . '_' . $index . '.' . $documento->getClientOriginalExtension();
                    $documento->move($directorioDocumentos, $nombreDocumento);

                    // Guardar en tabla documentos_cliente
                    $documentoModel = \App\Models\DocumentoCliente::create([
                        'cliente_id' => $clienteId,
                        'tipo_documento' => 'Documento de Préstamo #' . $prestamoId,
                        'ruta_archivo' => $nombreDocumento,
                    ]);

                    $documentosGuardados[] = [
                        'id' => $documentoModel->id,
                        'filename' => $nombreDocumento,
                        'path' => 'files/client_files/' . $nombreDocumento,
                    ];

                    \Log::info('Documento guardado para cliente', [
                        'cliente_id' => $clienteId,
                        'prestamo_id' => $prestamoId,
                        'documento' => $nombreDocumento
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => count($documentosGuardados) . ' documento(s) subido(s) exitosamente',
                'data' => $documentosGuardados,
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error al subir documentos del cliente: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir documentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}