<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Telefono;
use App\Models\Direccion;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Distrito;
use App\Models\Zona;
use App\Models\Sucursal;
use App\Models\TipoCuenta;
use App\Models\Prestamo;
use App\Models\Cuota;
use App\Models\Conyuge;
use App\Models\Laboral;
use App\Models\CuentaCliente;
use App\Models\EntidadBancaria;
use App\Models\BilleteraDigital;
use App\Models\DocumentoCliente;
use App\Models\ApiConfig;
use App\Models\EtiquetaCliente;
use App\Models\Etiqueta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ClienteController extends Controller
{
    /**
     * Listar clientes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Cliente::with([
                'persona',
                'sucursal',
                'prestamos' => function ($q) {
                    // Solo préstamos activos (no cancelados ni finalizados)
                    $q->whereNotIn('estado', ['Cancelado', 'Finalizado'])
                        ->orderBy('created_at', 'desc')
                        ->limit(1); // Solo el más reciente
                }
            ]);

            $search = $request->input('search');

            // Búsqueda por DNI o nombres (por palabras individuales con AND)
            if (!empty($search)) {
                $words = array_values(array_filter(explode(' ', trim($search))));
                $query->whereHas('persona', function ($q) use ($search, $words) {
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

            // Filtro por estado de préstamo
            if ($request->has('tiene_prestamo') && !empty($request->tiene_prestamo)) {
                \Log::info('Filtro tiene_prestamo recibido: ' . $request->tiene_prestamo);

                if ($request->tiene_prestamo === 'si' || $request->tiene_prestamo === '1' || $request->tiene_prestamo === 1) {
                    // Clientes CON préstamos activos
                    \Log::info('Filtrando clientes CON préstamos');
                    $query->whereHas('prestamos', function ($q) {
                        $q->whereNotIn('estado', ['Cancelado', 'Finalizado']);
                    });
                } elseif ($request->tiene_prestamo === 'no' || $request->tiene_prestamo === '0' || $request->tiene_prestamo === 0) {
                    // Clientes SIN préstamos activos
                    \Log::info('Filtrando clientes SIN préstamos');
                    $query->whereDoesntHave('prestamos', function ($q) {
                        $q->whereNotIn('estado', ['Cancelado', 'Finalizado']);
                    });
                }
            }

            // Filtro por zona (a través de direcciones)
            if ($request->has('zona_id') && !empty($request->zona_id)) {
                $query->whereHas('direcciones', function ($q) use ($request) {
                    $q->where('zona_id', $request->zona_id)
                        ->where('estado', 1); // Solo direcciones activas
                });
            }

            // Filtro por sucursal (a través de direcciones)
            if ($request->has('sucursal_id') && !empty($request->sucursal_id)) {
                $query->whereHas('direcciones', function ($q) use ($request) {
                    $q->where('sucursal_id', $request->sucursal_id)
                        ->where('estado', 1); // Solo direcciones activas
                });
            }

            // Paginación
            $perPage = $request->get('per_page', 15);
            $perPage = min($perPage, 100); // Máximo 100 por página

            $clientes = $query->orderBy('created_at', 'desc')->paginate($perPage);

            \Log::info('Clientes encontrados: ' . $clientes->total() . ' con filtros: ', [
                'tiene_prestamo' => $request->tiene_prestamo ?? 'null',
                'zona_id' => $request->zona_id ?? 'null',
                'sucursal_id' => $request->sucursal_id ?? 'null',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Clientes obtenidos exitosamente',
                'data' => [
                    'clientes' => $clientes->items(),
                    'pagination' => [
                        'current_page' => $clientes->currentPage(),
                        'last_page' => $clientes->lastPage(),
                        'per_page' => $clientes->perPage(),
                        'total' => $clientes->total(),
                        'has_more_pages' => $clientes->hasMorePages(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en ClienteController@index: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar clientes con SOLICITUDES (préstamos en estado inicial)
     * Estados: Nueva Solicitud, Aprobado
     */
    public function indexSolicitudes(Request $request): JsonResponse
    {
        try {
            $estadosDefault = ['Nueva Solicitud', 'Aprobado'];
            $estadoFiltro = $request->filled('estado') ? $request->estado : null;
            $estadosAFiltrar = $estadoFiltro ? [$estadoFiltro] : $estadosDefault;

            $query = Cliente::with([
                'persona',
                'sucursal',
                'prestamos' => function ($q) use ($estadosAFiltrar) {
                    $q->whereIn('estado', $estadosAFiltrar)
                        ->orderBy('created_at', 'desc');
                }
            ]);

            if ($request->filled('search')) {
                $search = trim($request->search);
                $words = array_values(array_filter(explode(' ', $search)));
                $query->whereHas('persona', function ($q) use ($search, $words) {
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

            $query->whereHas('prestamos', function ($q) use ($estadosAFiltrar) {
                $q->whereIn('estado', $estadosAFiltrar);
            });

            if ($request->filled('zona_id')) {
                $query->whereHas('direcciones', function ($q) use ($request) {
                    $q->where('zona_id', $request->zona_id)->where('estado', 1);
                });
            }

            if ($request->filled('sucursal_id')) {
                $query->whereHas('direcciones', function ($q) use ($request) {
                    $q->where('sucursal_id', $request->sucursal_id)->where('estado', 1);
                });
            }

            $perPage = min($request->get('per_page', 15), 100);
            $clientes = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $clientes->getCollection()->transform(function ($cliente) {
                if ($cliente->prestamos->isNotEmpty()) {
                    $cliente->prestamos = [$cliente->prestamos->first()];
                }
                return $cliente;
            });

            return response()->json([
                'success' => true,
                'message' => 'Clientes con solicitudes obtenidos exitosamente',
                'data' => [
                    'clientes' => $clientes->items(),
                    'pagination' => [
                        'current_page' => $clientes->currentPage(),
                        'last_page' => $clientes->lastPage(),
                        'per_page' => $clientes->perPage(),
                        'total' => $clientes->total(),
                        'has_more_pages' => $clientes->hasMorePages(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en ClienteController@indexSolicitudes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes con solicitudes: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Listar clientes con PRÉSTAMOS activos
     * Estados: Por Desembolsar, Vigente, Moroso, Con Convenio, Liquidado, Finalizado
     */
    public function indexPrestamos(Request $request): JsonResponse
    {
        try {
            // CORREGIDO: Incluye todos los estados vigentes (Vigente, Moroso, Con Convenio)
            $estadosDefault = ['Por Desembolsar', 'Vigente', 'Moroso', 'Con Convenio', 'Liquidado', 'Finalizado'];
            $estadoFiltro = $request->filled('estado') ? $request->estado : null;
            $estadosAFiltrar = $estadoFiltro ? [$estadoFiltro] : $estadosDefault;

            $query = Cliente::with([
                'persona',
                'sucursal',
                'prestamos' => function ($q) use ($estadosAFiltrar) {
                    $q->whereIn('estado', $estadosAFiltrar)
                        ->orderBy('created_at', 'desc');
                    // REMOVIDO EL LIMIT - se filtrará en PHP si es necesario
                }
            ]);

            // Filtro de búsqueda
            if ($request->filled('search')) {
                $search = trim($request->search);
                $words = array_values(array_filter(explode(' ', $search)));
                $query->whereHas('persona', function ($q) use ($search, $words) {
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

            // CRÍTICO: Solo clientes que TENGAN al menos un préstamo con el estado filtrado
            $query->whereHas('prestamos', function ($q) use ($estadosAFiltrar) {
                $q->whereIn('estado', $estadosAFiltrar);
            });

            // Filtro por zona
            if ($request->filled('zona_id')) {
                $query->whereHas('direcciones', function ($q) use ($request) {
                    $q->where('zona_id', $request->zona_id)
                        ->where('estado', 1);
                });
            }

            // Filtro por sucursal
            if ($request->filled('sucursal_id')) {
                $query->whereHas('direcciones', function ($q) use ($request) {
                    $q->where('sucursal_id', $request->sucursal_id)
                        ->where('estado', 1);
                });
            }

            $perPage = min($request->get('per_page', 15), 100);
            $clientes = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Filtrar solo el préstamo más reciente del estado en cada cliente
            $clientes->getCollection()->transform(function ($cliente) {
                if ($cliente->prestamos->isNotEmpty()) {
                    $cliente->prestamos = [$cliente->prestamos->first()];
                }
                return $cliente;
            });

            \Log::info('Clientes con préstamos activos', [
                'total' => $clientes->total(),
                'estados' => $estadosAFiltrar
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Clientes con préstamos activos obtenidos exitosamente',
                'data' => [
                    'clientes' => $clientes->items(),
                    'pagination' => [
                        'current_page' => $clientes->currentPage(),
                        'last_page' => $clientes->lastPage(),
                        'per_page' => $clientes->perPage(),
                        'total' => $clientes->total(),
                        'has_more_pages' => $clientes->hasMorePages(),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en ClienteController@indexPrestamos: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes con préstamos: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Mostrar cliente específico
     */
    public function show($id): JsonResponse
    {
        try {
            $cliente = Cliente::with([
                'persona',
                'persona.telefonos',
                'persona.direcciones',
                'persona.direcciones.distrito.provincia.departamento',
                'persona.direcciones.zona',
                'persona.direcciones.sucursal',
                'cuentasCliente.entidadBancaria',
                'cuentasCliente.billeteraDigital',
                'conyuge',
                'conyuge.persona',
                'conyuge.persona.telefonos',
                'laborales',
                'documentosCliente',
                'prestamos' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])->find($id);

            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cliente obtenido exitosamente',
                'data' => $cliente,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Crear nuevo cliente
     */
    // public function store(Request $request): JsonResponse
    // {
    //     try {
    //         // Decodificar arrays JSON si vienen como strings
    //         $telefonos = is_string($request->telefonos) ? json_decode($request->telefonos, true) : $request->telefonos;
    //         $direcciones = is_string($request->direcciones) ? json_decode($request->direcciones, true) : $request->direcciones;
    //         $cuentasBancarias = is_string($request->cuentas_bancarias) ? json_decode($request->cuentas_bancarias, true) : $request->cuentas_bancarias;
    //         $billeterasDigitales = is_string($request->billeteras_digitales) ? json_decode($request->billeteras_digitales, true) : $request->billeteras_digitales;
    //         $trabajos = is_string($request->trabajos) ? json_decode($request->trabajos, true) : $request->trabajos;

    //         // Reemplazar en el request para la validación
    //         $request->merge([
    //             'telefonos' => $telefonos,
    //             'direcciones' => $direcciones,
    //             'cuentas_bancarias' => $cuentasBancarias,
    //             'billeteras_digitales' => $billeterasDigitales,
    //             'trabajos' => $trabajos,
    //         ]);

    //         $validator = Validator::make($request->all(), [
    //             // Datos de persona
    //             'tipo_documento' => 'required|in:DNI,CE,RUC',
    //             'numero_documento' => 'required|string|max:20|unique:personas,documento',
    //             'nombres' => 'required|string|max:100',
    //             'apellido_paterno' => 'required|string|max:50',
    //             'apellido_materno' => 'required|string|max:50',
    //             'fecha_nacimiento' => 'required|date|before:today',
    //             'sexo' => 'required|in:M,F',
    //             'estado_civil' => 'required|in:soltero,casado,divorciado,viudo,conviviente',
    //             'email' => 'nullable|email|max:100',

    //             // Teléfonos múltiples
    //             'telefonos' => 'required|array|min:1',
    //             'telefonos.*.numero' => 'required|string|max:20',
    //             'telefonos.*.tipo' => 'required|in:celular,telefono,trabajo,casa,referencia',
    //             'telefonos.*.descripcion' => 'nullable|string|max:100',

    //             // Direcciones múltiples
    //             'direcciones.*.zona_id' => 'nullable|exists:zonas,id',
    //             'direcciones.*.sucursal_id' => 'nullable|exists:sucursales,id',
    //             'direcciones' => 'required|array|min:1',
    //             'direcciones.*.direccion' => 'required|string|max:200',
    //             'direcciones.*.nLotes' => 'nullable|string|max:50',
    //             'direcciones.*.referencia' => 'nullable|string|max:200',
    //             'direcciones.*.material_inmueble' => 'nullable|in:material_noble,prefabricada,machimbrado,otros',
    //             'direcciones.*.cantPisos' => 'required|integer|min:1|max:10',
    //             'direcciones.*.tipo_residencia' => 'nullable|in:Propia,Familiar,Alquilada,Otros',
    //             'direcciones.*.tiempo_residencia' => 'required|integer|min:0',
    //             'direcciones.*.anios_meses' => 'required|in:meses,años',
    //             'direcciones.*.tipo_direccion' => 'nullable|in:principal,secundario',

    //             // Datos del cliente
    //             'ocupacion' => 'required|string|max:100',
    //             'ingresos_mensuales' => 'required|numeric|min:0',
    //             'observaciones' => 'nullable|string|max:500',

    //             // Información financiera
    //             'tipo_cuenta_id' => 'nullable|integer',
    //             'cuentas_bancarias' => 'nullable|array',
    //             'cuentas_bancarias.*.banco' => 'required_with:cuentas_bancarias|string|max:100',
    //             'cuentas_bancarias.*.numero_cuenta' => 'required_with:cuentas_bancarias|string|max:50',
    //             'billeteras_digitales' => 'nullable|array',
    //             'billeteras_digitales.*.nombre' => 'required_with:billeteras_digitales|string|max:50',
    //             'billeteras_digitales.*.numero' => 'required_with:billeteras_digitales|string|max:20',

    //             // Información laboral (opcional)
    //             'trabajos' => 'nullable|array',
    //             'trabajos.*.actividad_economica' => 'required_with:trabajos|string|in:Dependiente,Independiente,Casa,Otros',
    //             'trabajos.*.nombre_lugar_trabajo' => 'nullable|string|max:200',
    //             'trabajos.*.cargo' => 'nullable|string|max:100',
    //             'trabajos.*.direccion_trabajo' => 'nullable|string|max:200',

    //             // Información del cónyuge (opcional)
    //             'conyuge_dni' => 'nullable|string|max:8',
    //             'conyuge_nombre' => 'nullable|string|max:100',
    //             'conyuge_apellido_pat' => 'nullable|string|max:50',
    //             'conyuge_apellido_mat' => 'nullable|string|max:50',
    //             'conyuge_actividad' => 'nullable|string|max:100',
    //             'conyuge_telefono' => 'nullable|string|max:20',
    //             'conyuge_direccion_trabajo' => 'nullable|string|max:200',
    //             'ref_conyuge_direccion_trabajo' => 'nullable|string|max:200',

    //             // Validación de archivos
    //             'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // Máximo 5MB
    //             'documentos' => 'nullable|array',
    //             'documentos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120', // Máximo 5MB cada uno
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Datos de validación incorrectos',
    //                 'errors' => $validator->errors(),
    //             ], 422);
    //         }

    //         // Validación adicional para tipo de cuenta
    //         if ($request->filled('tipo_cuenta_id')) {
    //             $tipoCuentaId = $request->tipo_cuenta_id;

    //             // Si es tipo 2 (propias) o 3 (de terceros), debe tener al menos una cuenta
    //             if (($tipoCuentaId == 2 || $tipoCuentaId == 3)) {
    //                 $tieneCuentasBancarias = $request->filled('cuentas_bancarias') && count($request->cuentas_bancarias) > 0;
    //                 $tieneBilleterasDigitales = $request->filled('billeteras_digitales') && count($request->billeteras_digitales) > 0;

    //                 if (!$tieneCuentasBancarias && !$tieneBilleterasDigitales) {
    //                     $tipoCuentaTexto = $tipoCuentaId == 2 ? 'propias' : 'de terceros';
    //                     return response()->json([
    //                         'success' => false,
    //                         'message' => "Debe agregar al menos una cuenta bancaria o billetera digital para cuentas {$tipoCuentaTexto}",
    //                         'errors' => ['tipo_cuenta_id' => ["Debe agregar al menos una cuenta bancaria o billetera digital para cuentas {$tipoCuentaTexto}"]],
    //                     ], 422);
    //                 }
    //             }
    //         }

    //         // NO VALIDAR DNI EN RENIEC - Permitir guardar sin validación externa
    //         // La validación de existencia ya se hace en las reglas (unique:personas,documento)

    //         DB::beginTransaction();

    //         // Obtener primer teléfono para campos principales
    //         $primerTelefono = $request->telefonos[0] ?? null;
    //         $telefonoPrincipal = null;
    //         $celularPrincipal = null;

    //         if ($primerTelefono) {
    //             if ($primerTelefono['tipo'] === 'celular') {
    //                 $celularPrincipal = $primerTelefono['numero'];
    //             } else {
    //                 $telefonoPrincipal = $primerTelefono['numero'];
    //             }
    //         }

    //         // Buscar celular si no fue el primero
    //         if (!$celularPrincipal) {
    //             foreach ($request->telefonos as $tel) {
    //                 if ($tel['tipo'] === 'celular') {
    //                     $celularPrincipal = $tel['numero'];
    //                     break;
    //                 }
    //             }
    //         }

    //         // Obtener primera dirección para campos principales
    //         $primeraDireccion = $request->direcciones[0] ?? null;

    //         // Crear persona
    //         $persona = Persona::create([
    //             'tipo_documento' => $request->tipo_documento,
    //             'documento' => $request->numero_documento,
    //             'nombres' => $request->nombres,
    //             'ape_pat' => $request->apellido_paterno,
    //             'ape_mat' => $request->apellido_materno,
    //             'fecha_nacimiento' => $request->fecha_nacimiento,
    //             'sexo' => $request->sexo,
    //             'estado_civil' => $request->estado_civil,
    //             'telefono' => $telefonoPrincipal,
    //             'celular' => $celularPrincipal ?? ($primerTelefono['numero'] ?? null),
    //             'email' => $request->email,
    //             'departamento_id' => $primeraDireccion['departamento'] ?? null,
    //             'provincia_id' => $primeraDireccion['provincia'] ?? null,
    //             'distrito_id' => $primeraDireccion['distrito'] ?? null,
    //             'direccion' => $primeraDireccion['direccion'] ?? null,
    //             'referencia' => $primeraDireccion['referencia'] ?? null,
    //         ]);

    //         // Crear cliente
    //         $cliente = Cliente::create([
    //             'persona_id' => $persona->id,
    //             'codigo' => $this->generarCodigoCliente(),
    //             'ocupacion' => $request->ocupacion,
    //             'ingresos_mensuales' => $request->ingresos_mensuales,
    //             'observaciones' => $request->observaciones,
    //             'estado' => 'activo',
    //             'fecha_registro' => now(),
    //             'user_id' => auth()->id(),
    //             'carga_familiar' => $request->carga_familiar ?? null,
    //         ]);

    //         // Procesar información adicional si está presente
    //         $informacionAdicional = [];

    //         // Información del cónyuge
    //         // CÓNYUGE - Guardar registro completo si estado civil requiere y datos presentes
    //         if (
    //             in_array($request->estado_civil, ['casado', 'conviviente'])
    //             && $request->filled('conyuge_dni')
    //             && $request->filled('conyuge_nombre')
    //         ) {

    //             // Crear/actualizar persona cónyuge
    //             $personaConyuge = Persona::firstOrNew(['documento' => $request->conyuge_dni]);
    //             $personaConyuge->nombres = $request->conyuge_nombre;
    //             $personaConyuge->ape_pat = $request->conyuge_apellido_pat ?? '';
    //             $personaConyuge->ape_mat = $request->conyuge_apellido_mat ?? '';
    //             $personaConyuge->save();

    //             // Crear enlace cónyuge
    //             Conyuge::create([
    //                 'cliente_id' => $cliente->id,
    //                 'persona_id' => $personaConyuge->id,
    //                 'oficio' => $request->conyuge_actividad ?? '',
    //                 'direccion_trabajo' => $request->conyuge_direccion_trabajo ?? '',
    //                 'referencia_direccion' => $request->ref_conyuge_direccion_trabajo ?? '',
    //             ]);

    //             // Teléfono cónyuge
    //             if ($request->filled('conyuge_telefono')) {
    //                 Telefono::create([
    //                     'persona_id' => $personaConyuge->id,
    //                     'tipo_telefono' => 'celular',
    //                     'numero' => $request->conyuge_telefono,
    //                 ]);
    //             }

    //         }


    //         // GUARDAR CUENTAS BANCARIAS EN LA TABLA cuentas_cliente
    //         if ($request->filled('cuentas_bancarias') && is_array($request->cuentas_bancarias)) {
    //             foreach ($request->cuentas_bancarias as $cuenta) {
    //                 if (!empty($cuenta['banco']) && !empty($cuenta['numero_cuenta'])) {
    //                     // Buscar o crear la entidad bancaria
    //                     $entidadBancaria = EntidadBancaria::firstOrCreate(
    //                         ['banco' => $cuenta['banco']],
    //                         ['status' => 1]
    //                     );

    //                     // Crear la cuenta del cliente
    //                     CuentaCliente::create([
    //                         'cliente_id' => $cliente->id,
    //                         'entidad_bancaria_id' => $entidadBancaria->id,
    //                         'tipo_cuenta_id' => $request->tipo_cuenta_id ?? null,
    //                         'numero_cuenta' => $cuenta['numero_cuenta'],
    //                         'titular_cuenta' => $cuenta['titular_cuenta'] ?? '',
    //                         'status' => 1,
    //                     ]);
    //                 }
    //             }
    //             $informacionAdicional['cuentas_bancarias'] = $request->cuentas_bancarias;
    //         }

    //         // GUARDAR BILLETERAS DIGITALES EN LA TABLA cuentas_cliente
    //         if ($request->filled('billeteras_digitales') && is_array($request->billeteras_digitales)) {
    //             foreach ($request->billeteras_digitales as $billetera) {
    //                 if (!empty($billetera['nombre']) && !empty($billetera['numero'])) {
    //                     // Buscar o crear la billetera digital
    //                     $billeteraDigital = BilleteraDigital::firstOrCreate(
    //                         ['nombre' => $billetera['nombre']],
    //                         ['status' => 1]
    //                     );

    //                     // Crear la cuenta del cliente con billetera digital
    //                     CuentaCliente::create([
    //                         'cliente_id' => $cliente->id,
    //                         'billetera_digital_id' => $billeteraDigital->id,
    //                         'tipo_cuenta_id' => $request->tipo_cuenta_id ?? null,
    //                         'numero_cuenta' => $billetera['numero'],
    //                         'titular_cuenta' => $billetera['titular_cuenta'] ?? '',
    //                         'status' => 1,
    //                     ]);
    //                 }
    //             }
    //             $informacionAdicional['billeteras_digitales'] = $request->billeteras_digitales;
    //         }

    //         // GUARDAR INFORMACIÓN LABORAL EN LA TABLA laborales
    //         if ($request->filled('trabajos') && is_array($request->trabajos)) {
    //             foreach ($request->trabajos as $trabajo) {
    //                 if (!empty($trabajo['actividad_economica'])) {
    //                     Laboral::create([
    //                         'cliente_id' => $cliente->id,
    //                         'actividad_economica' => $trabajo['actividad_economica'],
    //                         'nombre_lugar_trabajo' => $trabajo['nombre_lugar_trabajo'] ?? null,
    //                         'cargo' => $trabajo['cargo'] ?? null,
    //                         'direccion' => $trabajo['direccion'] ?? null,
    //                         'status' => 1,
    //                     ]);
    //                 }
    //             }
    //             $informacionAdicional['trabajos'] = $request->trabajos;
    //         }

    //         // Procesar foto de perfil si existe
    //         $nombreImagenPerfil = null;
    //         if ($request->hasFile('foto_perfil') && $request->file('foto_perfil')->isValid()) {
    //             $fotoPerfil = $request->file('foto_perfil');
    //             $nombreImagenPerfil = 'cliente_' . $persona->id . '_' . time() . '.' . $fotoPerfil->getClientOriginalExtension();

    //             // Crear directorio si no existe
    //             $directorioImagenes = public_path('img/clientes_img');
    //             if (!file_exists($directorioImagenes)) {
    //                 mkdir($directorioImagenes, 0755, true);
    //             }

    //             // Mover archivo
    //             $fotoPerfil->move($directorioImagenes, $nombreImagenPerfil);

    //             // Actualizar persona con la imagen
    //             $persona->update(['imagen' => $nombreImagenPerfil]);
    //         }

    //         // Procesar documentos si existen
    //         if ($request->hasFile('documentos')) {
    //             $directorioDocumentos = public_path('files/client_files');
    //             if (!file_exists($directorioDocumentos)) {
    //                 mkdir($directorioDocumentos, 0755, true);
    //             }
    //             $descripciones = $request->input('descripciones', []);

    //             foreach ($request->file('documentos') as $index => $documento) {
    //                 if ($documento && $documento->isValid()) {
    //                     $nombreDocumento = 'doc_cliente_' . $cliente->id . '_' . time() . '_' . $index . '.' . $documento->getClientOriginalExtension();
    //                     $documento->move($directorioDocumentos, $nombreDocumento);
    //                     $descripcion = isset($descripciones[$index]) && !empty($descripciones[$index])
    //                         ? $descripciones[$index]
    //                         : 'Documento adjunto';

    //                     // Guardar en tabla documentos_clientes
    //                     DocumentoCliente::create([
    //                         'cliente_id' => $cliente->id,
    //                         'tipo_documento' => $descripcion,
    //                         'ruta_archivo' => $nombreDocumento,
    //                         'descripcion' => $descripcion,
    //                     ]);
    //                 }
    //             }
    //         }

    //         // Crear teléfonos en la tabla telefonos
    //         if (count($request->telefonos) > 0) {
    //             foreach ($request->telefonos as $tel) {
    //                 Telefono::create([
    //                     'persona_id' => $persona->id,
    //                     'tipo_telefono' => $tel['tipo'],
    //                     'numero' => $tel['numero'],
    //                     'comentario' => $tel['descripcion'] ?? '',
    //                 ]);
    //             }
    //             $informacionAdicional['telefonos'] = $request->telefonos;
    //         }

    //         // Crear direcciones en la tabla direcciones
    //         if (count($request->direcciones) > 0) {
    //             foreach ($request->direcciones as $dir) {
    //                 Direccion::create([
    //                     'persona_id' => $persona->id,
    //                     'distrito_id' => $dir['distrito'],
    //                     'zona_id' => $dir['zona_id'] ?? null,
    //                     'sucursal_id' => $dir['sucursal_id'] ?? null,
    //                     'direccion' => $dir['direccion'],
    //                     'numero' => $dir['nLotes'] ?? '',
    //                     'referencia' => $dir['referencia'] ?? '',
    //                     'material_inmueble' => $dir['material_inmueble'] ?? '',
    //                     'cant_pisos' => $dir['cantPisos'] ?? null,
    //                     'tipo_residencia' => $dir['tipo_residencia'] ?? '',
    //                     'tiempo_residencia' => $dir['tiempo_residencia'] ?? null,
    //                     'anios_meses' => $dir['anios_meses'] ?? '',
    //                     'tipo_direccion' => $dir['tipo_direccion'] ?? 'secundario',
    //                     'estado' => 1,
    //                 ]);
    //             }
    //             $informacionAdicional['direcciones'] = $request->direcciones;
    //         }

    //         // Guardar información adicional en el campo JSON si existe
    //         if (!empty($informacionAdicional)) {
    //             $cliente->update(['informacion_adicional' => json_encode($informacionAdicional)]);
    //         }

    //         DB::commit();

    //         // Cargar relaciones para la respuesta
    //         $cliente->load(['persona', 'sucursal']);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Cliente creado exitosamente',
    //             'data' => $cliente,
    //         ], 201);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error al crear el cliente',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function store(Request $request): JsonResponse
    {
        try {
            // Decodificar arrays JSON si vienen como strings
            $telefonos = is_string($request->telefonos) ? json_decode($request->telefonos, true) : $request->telefonos;
            $direcciones = is_string($request->direcciones) ? json_decode($request->direcciones, true) : $request->direcciones;
            $cuentasBancarias = is_string($request->cuentas_bancarias) ? json_decode($request->cuentas_bancarias, true) : $request->cuentas_bancarias;
            $billeterasDigitales = is_string($request->billeteras_digitales) ? json_decode($request->billeteras_digitales, true) : $request->billeteras_digitales;
            $trabajos = is_string($request->trabajos) ? json_decode($request->trabajos, true) : $request->trabajos;

            // Reemplazar en el request para la validación
            $request->merge([
                'telefonos' => $telefonos,
                'direcciones' => $direcciones,
                'cuentas_bancarias' => $cuentasBancarias,
                'billeteras_digitales' => $billeterasDigitales,
                'trabajos' => $trabajos,
            ]);

            $validator = Validator::make($request->all(), [
                // Datos de persona
                'tipo_documento' => 'required|in:DNI,CE,RUC',
                // 'numero_documento' => 'required|string|max:20|unique:personas,documento',
                'nombres' => 'required|string|max:100',
                'apellido_paterno' => 'required|string|max:50',
                'apellido_materno' => 'required|string|max:50',
                'fecha_nacimiento' => 'required|date|before:today',
                'sexo' => 'required|in:M,F',
                'estado_civil' => 'required|in:soltero,casado,divorciado,viudo,conviviente',
                'email' => 'nullable|email|max:100',

                // Teléfonos múltiples
                'telefonos' => 'required|array|min:1',
                'telefonos.*.numero' => 'required|string|max:20',
                'telefonos.*.tipo' => 'required|in:celular,telefono,trabajo,casa,referencia',
                'telefonos.*.descripcion' => 'nullable|string|max:100',

                // Direcciones múltiples
                'direcciones.*.zona_id' => 'nullable|exists:zonas,id',
                'direcciones.*.sucursal_id' => 'nullable|exists:sucursales,id',
                'direcciones' => 'required|array|min:1',
                'direcciones.*.direccion' => 'required|string|max:200',
                'direcciones.*.nLotes' => 'nullable|string|max:50',
                'direcciones.*.referencia' => 'nullable|string|max:200',
                'direcciones.*.material_inmueble' => 'nullable|in:material_noble,prefabricada,machimbrado,otros',
                'direcciones.*.cantPisos' => 'required|integer|min:1|max:10',
                'direcciones.*.tipo_residencia' => 'nullable|in:Propia,Familiar,Alquilada,Otros',
                'direcciones.*.tiempo_residencia' => 'required|integer|min:0',
                'direcciones.*.anios_meses' => 'required|in:meses,años',
                'direcciones.*.tipo_direccion' => 'nullable|in:principal,secundario',

                // Datos del cliente
                'ocupacion' => 'required|string|max:100',
                'ingresos_mensuales' => 'required|numeric|min:0',
                'observaciones' => 'nullable|string|max:500',

                // Información financiera
                'tipo_cuenta_id' => 'nullable|integer',
                'cuentas_bancarias' => 'nullable|array',
                'cuentas_bancarias.*.banco' => 'required_with:cuentas_bancarias|string|max:100',
                'cuentas_bancarias.*.numero_cuenta' => 'required_with:cuentas_bancarias|string|max:50',
                'billeteras_digitales' => 'nullable|array',
                'billeteras_digitales.*.nombre' => 'required_with:billeteras_digitales|string|max:50',
                'billeteras_digitales.*.numero' => 'required_with:billeteras_digitales|string|max:20',

                // Información laboral (opcional)
                'trabajos' => 'nullable|array',
                'trabajos.*.actividad_economica' => 'required_with:trabajos|string|in:Dependiente,Independiente,Casa,Otros',
                'trabajos.*.nombre_lugar_trabajo' => 'nullable|string|max:200',
                'trabajos.*.cargo' => 'nullable|string|max:100',
                'trabajos.*.direccion_trabajo' => 'nullable|string|max:200',

                // Información del cónyuge (opcional)
                'conyuge_dni' => 'nullable|string|max:8',
                'conyuge_nombre' => 'nullable|string|max:100',
                'conyuge_apellido_pat' => 'nullable|string|max:50',
                'conyuge_apellido_mat' => 'nullable|string|max:50',
                'conyuge_actividad' => 'nullable|string|max:100',
                'conyuge_telefono' => 'nullable|string|max:20',
                'conyuge_direccion_trabajo' => 'nullable|string|max:200',
                'ref_conyuge_direccion_trabajo' => 'nullable|string|max:200',

                // Validación de archivos
                'foto_perfil' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // Máximo 5MB
                'documentos' => 'nullable|array',
                'documentos.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120', // Máximo 5MB cada uno
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Validación adicional para tipo de cuenta
            if ($request->filled('tipo_cuenta_id')) {
                $tipoCuentaId = $request->tipo_cuenta_id;

                // Si es tipo 2 (propias) o 3 (de terceros), debe tener al menos una cuenta
                if (($tipoCuentaId == 2 || $tipoCuentaId == 3)) {
                    $tieneCuentasBancarias = $request->filled('cuentas_bancarias') && count($request->cuentas_bancarias) > 0;
                    $tieneBilleterasDigitales = $request->filled('billeteras_digitales') && count($request->billeteras_digitales) > 0;

                    if (!$tieneCuentasBancarias && !$tieneBilleterasDigitales) {
                        $tipoCuentaTexto = $tipoCuentaId == 2 ? 'propias' : 'de terceros';
                        return response()->json([
                            'success' => false,
                            'message' => "Debe agregar al menos una cuenta bancaria o billetera digital para cuentas {$tipoCuentaTexto}",
                            'errors' => ['tipo_cuenta_id' => ["Debe agregar al menos una cuenta bancaria o billetera digital para cuentas {$tipoCuentaTexto}"]],
                        ], 422);
                    }
                }
            }

            // NO VALIDAR DNI EN RENIEC - Permitir guardar sin validación externa
            // La validación de existencia ya se hace en las reglas (unique:personas,documento)

            DB::beginTransaction();

            // Obtener primer teléfono para campos principales
            $primerTelefono = $request->telefonos[0] ?? null;
            $telefonoPrincipal = null;
            $celularPrincipal = null;

            if ($primerTelefono) {
                if ($primerTelefono['tipo'] === 'celular') {
                    $celularPrincipal = $primerTelefono['numero'];
                } else {
                    $telefonoPrincipal = $primerTelefono['numero'];
                }
            }

            // Buscar celular si no fue el primero
            if (!$celularPrincipal) {
                foreach ($request->telefonos as $tel) {
                    if ($tel['tipo'] === 'celular') {
                        $celularPrincipal = $tel['numero'];
                        break;
                    }
                }
            }

            // Obtener primera dirección para campos principales
            $primeraDireccion = $request->direcciones[0] ?? null;

            // Crear persona
            // $persona = Persona::create([
            //     'tipo_documento' => $request->tipo_documento,
            //     'documento' => $request->numero_documento,
            //     'nombres' => $request->nombres,
            //     'ape_pat' => $request->apellido_paterno,
            //     'ape_mat' => $request->apellido_materno,
            //     'fecha_nacimiento' => $request->fecha_nacimiento,
            //     'sexo' => $request->sexo,
            //     'estado_civil' => $request->estado_civil,
            //     'telefono' => $telefonoPrincipal,
            //     'celular' => $celularPrincipal ?? ($primerTelefono['numero'] ?? null),
            //     'email' => $request->email,
            //     'departamento_id' => $primeraDireccion['departamento'] ?? null,
            //     'provincia_id' => $primeraDireccion['provincia'] ?? null,
            //     'distrito_id' => $primeraDireccion['distrito'] ?? null,
            //     'direccion' => $primeraDireccion['direccion'] ?? null,
            //     'referencia' => $primeraDireccion['referencia'] ?? null,
            // ]);

            // // Crear cliente
            // $cliente = Cliente::create([
            //     'persona_id' => $persona->id,
            //     'codigo' => $this->generarCodigoCliente(),
            //     'ocupacion' => $request->ocupacion,
            //     'ingresos_mensuales' => $request->ingresos_mensuales,
            //     'observaciones' => $request->observaciones,
            //     'estado' => 'activo',
            //     'fecha_registro' => now(),
            //     'user_id' => auth()->id(),
            //     'carga_familiar' => $request->carga_familiar ?? null,
            // ]);

             // =========================================================================
            // CAMBIO 2: BUSCAR SI LA PERSONA YA EXISTE O CREARLA
            // =========================================================================
            if ($request->filled('persona_id')) {
                // Viene desde el formulario de conversión (campo hidden)
                $persona = Persona::findOrFail($request->persona_id);
            } else {
                // Buscamos por documento por si acaso
                $persona = Persona::where('documento', $request->numero_documento)->first();
                if (!$persona) {
                    $persona = new Persona(); // Si no existe, preparamos una nueva
                }
            }

            // CAMBIO 3: VALIDAR QUE NO SEA YA UN CLIENTE
            if ($persona->exists && Cliente::where('persona_id', $persona->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta persona ya está registrada como un cliente activo.',
                ], 422);
            }

            // Actualizamos o asignamos los datos a la Persona
            // $persona->tipo_documento = $request->tipo_documento;
            $persona->documento = $request->numero_documento;
            $persona->nombres = $request->nombres;
            $persona->ape_pat = $request->apellido_paterno;
            $persona->ape_mat = $request->apellido_materno;
            $persona->fecha_nacimiento = $request->fecha_nacimiento;
            // $persona->sexo = $request->sexo;
            $persona->estado_civil = $request->estado_civil;
            // $persona->telefono = $telefonoPrincipal ?? $persona->telefono;
            // $persona->celular = $celularPrincipal ?? ($primerTelefono['numero'] ?? $persona->celular);
            $persona->email = $request->email;
            // $persona->departamento_id = $primeraDireccion['departamento'] ?? $persona->departamento_id;
            // $persona->provincia_id = $primeraDireccion['provincia'] ?? $persona->provincia_id;
            // $persona->distrito_id = $primeraDireccion['distrito'] ?? $persona->distrito_id;
            // $persona->direccion = $primeraDireccion['direccion'] ?? $persona->direccion;
            // $persona->referencia = $primeraDireccion['referencia'] ?? $persona->referencia;
            
            // Guardamos los cambios de la persona
            $persona->save();
            // =========================================================================


            // Crear cliente (Ahora usa el $persona->id de la que acabamos de crear/actualizar)
            $cliente = Cliente::create([
                'persona_id' => $persona->id,
                'codigo' => $this->generarCodigoCliente(),
                'ocupacion' => $request->ocupacion,
                'ingresos_mensuales' => $request->ingresos_mensuales,
                'observaciones' => $request->observaciones,
                'estado' => 'activo',
                'fecha_registro' => now(),
                'user_id' => auth()->id(),
                'carga_familiar' => $request->carga_familiar ?? null,
            ]);


            // Procesar información adicional si está presente
            $informacionAdicional = [];

            // Información del cónyuge
            // CÓNYUGE - Guardar registro completo si estado civil requiere y datos presentes
            if (
                in_array($request->estado_civil, ['casado', 'conviviente'])
                && $request->filled('conyuge_dni')
                && $request->filled('conyuge_nombre')
            ) {

                // Crear/actualizar persona cónyuge
                $personaConyuge = Persona::firstOrNew(['documento' => $request->conyuge_dni]);
                $personaConyuge->nombres = $request->conyuge_nombre;
                $personaConyuge->ape_pat = $request->conyuge_apellido_pat ?? '';
                $personaConyuge->ape_mat = $request->conyuge_apellido_mat ?? '';
                $personaConyuge->save();

                // Crear enlace cónyuge
                Conyuge::create([
                    'cliente_id' => $cliente->id,
                    'persona_id' => $personaConyuge->id,
                    'oficio' => $request->conyuge_actividad ?? '',
                    'direccion_trabajo' => $request->conyuge_direccion_trabajo ?? '',
                    'referencia_direccion' => $request->ref_conyuge_direccion_trabajo ?? '',
                ]);

                // Teléfono cónyuge
                if ($request->filled('conyuge_telefono')) {
                    Telefono::create([
                        'persona_id' => $personaConyuge->id,
                        'tipo_telefono' => 'celular',
                        'numero' => $request->conyuge_telefono,
                    ]);
                }

            }


            // GUARDAR CUENTAS BANCARIAS EN LA TABLA cuentas_cliente
            if ($request->filled('cuentas_bancarias') && is_array($request->cuentas_bancarias)) {
                foreach ($request->cuentas_bancarias as $cuenta) {
                    if (!empty($cuenta['banco']) && !empty($cuenta['numero_cuenta'])) {
                        // Buscar o crear la entidad bancaria
                        $entidadBancaria = EntidadBancaria::firstOrCreate(
                            ['banco' => $cuenta['banco']],
                            ['status' => 1]
                        );

                        // Crear la cuenta del cliente
                        CuentaCliente::create([
                            'cliente_id' => $cliente->id,
                            'entidad_bancaria_id' => $entidadBancaria->id,
                            'tipo_cuenta_id' => $request->tipo_cuenta_id ?? null,
                            'numero_cuenta' => $cuenta['numero_cuenta'],
                            'titular_cuenta' => $cuenta['titular_cuenta'] ?? '',
                            'status' => 1,
                        ]);
                    }
                }
                $informacionAdicional['cuentas_bancarias'] = $request->cuentas_bancarias;
            }

            // GUARDAR BILLETERAS DIGITALES EN LA TABLA cuentas_cliente
            if ($request->filled('billeteras_digitales') && is_array($request->billeteras_digitales)) {
                foreach ($request->billeteras_digitales as $billetera) {
                    if (!empty($billetera['nombre']) && !empty($billetera['numero'])) {
                        // Buscar o crear la billetera digital
                        $billeteraDigital = BilleteraDigital::firstOrCreate(
                            ['nombre' => $billetera['nombre']],
                            ['status' => 1]
                        );

                        // Crear la cuenta del cliente con billetera digital
                        CuentaCliente::create([
                            'cliente_id' => $cliente->id,
                            'billetera_digital_id' => $billeteraDigital->id,
                            'tipo_cuenta_id' => $request->tipo_cuenta_id ?? null,
                            'numero_cuenta' => $billetera['numero'],
                            'titular_cuenta' => $billetera['titular_cuenta'] ?? '',
                            'status' => 1,
                        ]);
                    }
                }
                $informacionAdicional['billeteras_digitales'] = $request->billeteras_digitales;
            }

            // GUARDAR INFORMACIÓN LABORAL EN LA TABLA laborales
            if ($request->filled('trabajos') && is_array($request->trabajos)) {
                foreach ($request->trabajos as $trabajo) {
                    if (!empty($trabajo['actividad_economica'])) {
                        Laboral::create([
                            'cliente_id' => $cliente->id,
                            'actividad_economica' => $trabajo['actividad_economica'],
                            'nombre_lugar_trabajo' => $trabajo['nombre_lugar_trabajo'] ?? null,
                            'cargo' => $trabajo['cargo'] ?? null,
                            'direccion' => $trabajo['direccion'] ?? null,
                            'status' => 1,
                        ]);
                    }
                }
                $informacionAdicional['trabajos'] = $request->trabajos;
            }

            // Procesar foto de perfil si existe
            $nombreImagenPerfil = null;
            if ($request->hasFile('foto_perfil') && $request->file('foto_perfil')->isValid()) {
                $fotoPerfil = $request->file('foto_perfil');
                $nombreImagenPerfil = 'cliente_' . $persona->id . '_' . time() . '.' . $fotoPerfil->getClientOriginalExtension();

                // Crear directorio si no existe
                $directorioImagenes = public_path('img/clientes_img');
                if (!file_exists($directorioImagenes)) {
                    mkdir($directorioImagenes, 0755, true);
                }

                // Mover archivo
                $fotoPerfil->move($directorioImagenes, $nombreImagenPerfil);

                // Actualizar persona con la imagen
                $persona->update(['imagen' => $nombreImagenPerfil]);
            }

            // Procesar documentos si existen
            if ($request->hasFile('documentos')) {
                $directorioDocumentos = public_path('files/client_files');
                if (!file_exists($directorioDocumentos)) {
                    mkdir($directorioDocumentos, 0755, true);
                }
                $descripciones = $request->input('descripciones', []);

                foreach ($request->file('documentos') as $index => $documento) {
                    if ($documento && $documento->isValid()) {
                        $nombreDocumento = 'doc_cliente_' . $cliente->id . '_' . time() . '_' . $index . '.' . $documento->getClientOriginalExtension();
                        $documento->move($directorioDocumentos, $nombreDocumento);
                        $descripcion = isset($descripciones[$index]) && !empty($descripciones[$index])
                            ? $descripciones[$index]
                            : 'Documento adjunto';

                        // Guardar en tabla documentos_clientes
                        DocumentoCliente::create([
                            'cliente_id' => $cliente->id,
                            'tipo_documento' => $descripcion,
                            'ruta_archivo' => $nombreDocumento,
                            'descripcion' => $descripcion,
                        ]);
                    }
                }
            }

            // Crear teléfonos en la tabla telefonos
            if (count($request->telefonos) > 0) {
                foreach ($request->telefonos as $tel) {
                    Telefono::create([
                        'persona_id' => $persona->id,
                        'tipo_telefono' => $tel['tipo'],
                        'numero' => $tel['numero'],
                        'comentario' => $tel['descripcion'] ?? '',
                    ]);
                }
                $informacionAdicional['telefonos'] = $request->telefonos;
            }

            // Crear direcciones en la tabla direcciones
            if (count($request->direcciones) > 0) {
                foreach ($request->direcciones as $dir) {
                    Direccion::create([
                        'persona_id' => $persona->id,
                        'distrito_id' => $dir['distrito'],
                        'zona_id' => $dir['zona_id'] ?? null,
                        'sucursal_id' => $dir['sucursal_id'] ?? null,
                        'direccion' => $dir['direccion'],
                        'numero' => $dir['nLotes'] ?? '',
                        'referencia' => $dir['referencia'] ?? '',
                        'material_inmueble' => $dir['material_inmueble'] ?? '',
                        'cant_pisos' => $dir['cantPisos'] ?? null,
                        'tipo_residencia' => $dir['tipo_residencia'] ?? '',
                        'tiempo_residencia' => $dir['tiempo_residencia'] ?? null,
                        'anios_meses' => $dir['anios_meses'] ?? '',
                        'tipo_direccion' => $dir['tipo_direccion'] ?? 'secundario',
                        'estado' => 1,
                    ]);
                }
                $informacionAdicional['direcciones'] = $request->direcciones;
            }

            // Guardar información adicional en el campo JSON si existe
            if (!empty($informacionAdicional)) {
                $cliente->update(['informacion_adicional' => json_encode($informacionAdicional)]);
            }

            DB::commit();

            // Cargar relaciones para la respuesta
            $cliente->load(['persona', 'sucursal']);

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => $cliente,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //cuentas del cliente
    public function getCuentasCliente($clienteId): JsonResponse
    {
        $cliente = Cliente::find($clienteId);
        if (!$cliente) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        // Obtener todas las cuentas del cliente
        $cuentas = CuentaCliente::where('cliente_id', $clienteId)
            ->where('status', 1)
            ->with(['entidadBancaria', 'billeteraDigital', 'tipoCuenta'])
            ->get()
            ->map(function ($cuenta) {
                $data = [
                    'id' => $cuenta->id,
                    'numero_cuenta' => $cuenta->numero_cuenta,
                    'titular_cuenta' => $cuenta->titular_cuenta,
                    'tipo_cuenta_id' => $cuenta->tipo_cuenta_id,
                    'tipo_cuenta_nombre' => $cuenta->tipoCuenta->tipo_cuenta,
                    'banco' => $cuenta->entidadBancaria?->banco ?? null,
                    'billetera' => $cuenta->billeteraDigital?->nombre ?? null,
                ];

                return $data;
            });

        return response()->json([
            'success' => true,
            'cuentas' => $cuentas,
        ], 200);
    }


    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // ============================================
            // 1. BUSCAR CLIENTE EXISTENTE
            // ============================================
            $cliente = Cliente::findOrFail($id);
            $persona = $cliente->persona;

            if (!$persona) {
                throw new \Exception('No se encontró la persona asociada al cliente');
            }

            // ============================================
            // 2. ACTUALIZAR DATOS DE LA PERSONA
            // ============================================
            $persona->update([
                'nombres' => $request->nombres,
                'ape_pat' => $request->ape_pat,
                'ape_mat' => $request->ape_mat,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'estado_civil' => $request->estado_civil,
                'email' => $request->email,
            ]);

            // ============================================
            // 3. SUBIR IMAGEN DE PERFIL (SI EXISTE)
            // ============================================
            if ($request->hasFile('foto_perfil')) {
                $profileImage = $request->file('foto_perfil');
                $profileImageName = time() . '_' . uniqid() . '.' . $profileImage->getClientOriginalExtension();
                $profileImage->move(public_path('img/clientes_img'), $profileImageName);
                $persona->update(['imagen' => $profileImageName]);
            }

            // ============================================
            // 4. ACTUALIZAR DATOS DEL CLIENTE
            // ============================================
            $cliente->update([
                'ocupacion' => $request->ocupacion,
                'ingresos_mensuales' => $request->ingresos_mensuales,
                'observaciones' => $request->observaciones,
                'carga_familiar' => $request->carga_familiar,
            ]);

            // ============================================
            // 5. ACTUALIZAR TELÉFONOS
            // ============================================
            if ($request->has('telefono')) {
                Telefono::where('persona_id', $persona->id)->delete();

                $telefonos = json_decode($request->telefono, true);
                $tipos = json_decode($request->tipo, true);
                $comentarios = json_decode($request->comentario, true);

                if (is_array($telefonos) && count($telefonos) > 0) {
                    foreach ($telefonos as $index => $numero) {
                        if (!empty($numero)) {
                            Telefono::create([
                                'persona_id' => $persona->id,
                                'numero' => $numero,
                                'tipo_telefono' => $tipos[$index] ?? 'celular',
                                'comentario' => $comentarios[$index] ?? null,
                            ]);
                        }
                    }
                }
            }

            // ============================================
            // 6. ACTUALIZAR CUENTAS BANCARIAS
            // ============================================
            if ($request->has('cuentas_bancarias')) {
                CuentaCliente::where('cliente_id', $cliente->id)
                    ->whereNotNull('entidad_bancaria_id')
                    ->delete();

                $cuentasBancarias = json_decode($request->cuentas_bancarias, true);

                if (is_array($cuentasBancarias) && count($cuentasBancarias) > 0) {
                    foreach ($cuentasBancarias as $cuenta) {
                        if (!empty($cuenta['banco']) && !empty($cuenta['numerocuenta'])) {
                            $entidadBancaria = EntidadBancaria::firstOrCreate(
                                ['banco' => $cuenta['banco']],
                                ['status' => 1]
                            );

                            $cuentaData = [
                                'cliente_id' => $cliente->id,
                                'entidad_bancaria_id' => $entidadBancaria->id,
                                'numero_cuenta' => $cuenta['numerocuenta'],
                                'status' => 1,
                            ];

                            if (isset($cuenta['tipo_cuenta_id'])) {
                                $cuentaData['tipo_cuenta_id'] = $cuenta['tipo_cuenta_id'];
                            } elseif ($request->has('tipo_cuenta_id')) {
                                $cuentaData['tipo_cuenta_id'] = $request->tipo_cuenta_id;
                            } else {
                                $cuentaData['tipo_cuenta_id'] = 1;
                            }

                            if (isset($cuenta['tipocuenta']) && !empty($cuenta['tipocuenta'])) {
                                $cuentaData['titular_cuenta'] = $cuenta['tipocuenta'];
                            } elseif (isset($cuenta['titular_cuenta']) && !empty($cuenta['titular_cuenta'])) {
                                $cuentaData['titular_cuenta'] = $cuenta['titular_cuenta'];
                            }

                            if (isset($cuenta['codigo']) && !empty($cuenta['codigo'])) {
                                $cuentaData['codigo'] = $cuenta['codigo'];
                            }

                            CuentaCliente::create($cuentaData);
                        }
                    }
                }
            }

            // ============================================
            // 7. ACTUALIZAR BILLETERAS DIGITALES
            // ============================================
            \Log::debug('Procesando billeteras digitales...');
            if ($request->has('billeteras_digitales')) {
                CuentaCliente::where('cliente_id', $cliente->id)
                    ->whereNotNull('billetera_digital_id')
                    ->delete();

                $billeterasDigitales = json_decode($request->billeteras_digitales, true);

                if (is_array($billeterasDigitales) && count($billeterasDigitales) > 0) {
                    foreach ($billeterasDigitales as $billetera) {
                        if (!empty($billetera['nombre']) && !empty($billetera['numero'])) {
                            $billeteraDigital = BilleteraDigital::firstOrCreate(
                                ['nombre' => $billetera['nombre']],
                                ['status' => 1]
                            );

                            $billeteraData = [
                                'cliente_id' => $cliente->id,
                                'billetera_digital_id' => $billeteraDigital->id,
                                'numero_cuenta' => $billetera['numero'],
                                'status' => 1,
                            ];

                            if (isset($billetera['tipo_cuenta_id'])) {
                                $billeteraData['tipo_cuenta_id'] = $billetera['tipo_cuenta_id'];
                            } elseif ($request->has('tipo_cuenta_id')) {
                                $billeteraData['tipo_cuenta_id'] = $request->tipo_cuenta_id;
                            } else {
                                $billeteraData['tipo_cuenta_id'] = 1;
                            }

                            if (isset($billetera['titular_cuenta']) && !empty($billetera['titular_cuenta'])) {
                                $billeteraData['titular_cuenta'] = $billetera['titular_cuenta'];
                            } elseif (isset($billetera['titularcuenta']) && !empty($billetera['titularcuenta'])) {
                                $billeteraData['titular_cuenta'] = $billetera['titularcuenta'];
                            }

                            if (isset($billetera['codigo']) && !empty($billetera['codigo'])) {
                                $billeteraData['codigo'] = $billetera['codigo'];
                            }

                            CuentaCliente::create($billeteraData);
                        }
                    }
                }
            }

            // ============================================
            // 8. ACTUALIZAR DIRECCIONES
            // ============================================
            if ($request->has('direccion')) {
                Direccion::where('persona_id', $persona->id)->delete();

                $direcciones = json_decode($request->direccion, true);
                $distritos = json_decode($request->distrito, true);
                $numeros = json_decode($request->nLotes, true);
                $referencias = json_decode($request->referencia, true);
                $materiales = json_decode($request->material_inmueble, true);
                $pisos = json_decode($request->cant_pisos, true);
                $tiposResidencia = json_decode($request->tipo_residencia, true);
                $tiemposResidencia = json_decode($request->tiempo_residencia, true);
                $aniosMeses = json_decode($request->anios_meses, true);

                // ✅ AGREGAR ZONA Y SUCURSAL POR DIRECCIÓN
                $zonasDir = json_decode($request->zona_direccion ?? '[]', true);
                $sucursalesDir = json_decode($request->sucursal_direccion ?? '[]', true);

                if (is_array($direcciones) && count($direcciones) > 0) {
                    foreach ($direcciones as $index => $direccion) {
                        if (!empty($direccion) && !empty($distritos[$index])) {
                            Direccion::create([
                                'persona_id' => $persona->id,
                                'direccion' => $direccion,
                                'numero' => $numeros[$index] ?? null,
                                'referencia' => $referencias[$index] ?? null,
                                'distrito_id' => $distritos[$index],
                                'zona_id' => $zonasDir[$index] ?? $request->zona_id ?? null,  // ✅ Zona por dirección
                                'sucursal_id' => $sucursalesDir[$index] ?? $request->sucursal_id ?? null,  // ✅ Sucursal por dirección
                                'material_inmueble' => $materiales[$index] ?? '',
                                'cant_pisos' => $pisos[$index] ?? 1,
                                'tipo_residencia' => $tiposResidencia[$index] ?? '',
                                'tiempo_residencia' => $tiemposResidencia[$index] ?? 0,
                                'anios_meses' => $aniosMeses[$index] ?? '',
                                'tipo_direccion' => $index == 0 ? 'principal' : 'secundario',
                                'estado' => 1,  // ✅ Agregar estado
                            ]);
                        }
                    }
                    \Log::info('Direcciones actualizadas', ['cantidad' => count($direcciones)]);
                }
            }

            // ============================================
            // 9. ACTUALIZAR INFORMACIÓN LABORAL
            // ============================================
            if ($request->has('actividad_economica')) {
                Laboral::where('cliente_id', $cliente->id)->delete();

                $actividadesEconomicas = json_decode($request->actividad_economica, true);
                $nombresLugarTrabajo = json_decode($request->nombre_lugar_trabajo, true);
                $cargos = json_decode($request->cargo, true);
                $direccionesTrabajo = json_decode($request->direccion_trabajo, true);

                if (is_array($actividadesEconomicas) && count($actividadesEconomicas) > 0) {
                    foreach ($actividadesEconomicas as $index => $actividad) {
                        Laboral::create([
                            'cliente_id' => $cliente->id,
                            'actividad_economica' => $actividad ?? 'Dependiente',
                            'nombre_lugar_trabajo' => $nombresLugarTrabajo[$index] ?? null,
                            'cargo' => $cargos[$index] ?? null,
                            'direccion' => $direccionesTrabajo[$index] ?? null,
                            'status' => 1,
                        ]);
                    }
                }
            }

            // ============================================
            // 10. ACTUALIZAR INFORMACIÓN DEL CÓNYUGE
            // ============================================
            if ($request->filled('conyuge_dni')) {
                \Log::debug('Procesando información del cónyuge...');

                $personaConyuge = Persona::firstOrCreate(
                    ['documento' => $request->conyuge_dni],
                    [
                        'nombres' => $request->conyuge_nombre,
                        'ape_pat' => $request->conyuge_apellido_pat,
                        'ape_mat' => $request->conyuge_apellido_mat,
                    ]
                );

                Conyuge::updateOrCreate(
                    ['cliente_id' => $cliente->id],
                    [
                        'persona_id' => $personaConyuge->id,
                        'oficio' => $request->conyuge_actividad,
                        'direccion_trabajo' => $request->conyuge_direccion_trabajo,
                        'referencia_direccion' => $request->ref_conyuge_direccion_trabajo,
                    ]
                );

                if ($request->filled('conyuge_telefono')) {
                    Telefono::updateOrCreate(
                        [
                            'persona_id' => $personaConyuge->id,
                            'tipo_telefono' => 'celular'
                        ],
                        [
                            'numero' => $request->conyuge_telefono,
                        ]
                    );
                }

                \Log::info('Información del cónyuge actualizada');
            }

            // ============================================
            // 11. PROCESAR DOCUMENTOS
            // ============================================
            if ($request->hasFile('files_to_upload')) {
                \Log::debug('Procesando documentos...');

                $files = $request->file('files_to_upload');
                $descripciones = json_decode($request->descripciones ?? '[]', true);

                foreach ($files as $index => $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('files/client_files'), $fileName);

                    DocumentoCliente::create([
                        'cliente_id' => $cliente->id,
                        'tipo_documento' => $descripciones[$index] ?? 'Documento',
                        'ruta_archivo' => $fileName,
                    ]);

                    \Log::info('Documento guardado', ['archivo' => $fileName]);
                }
            }

            DB::commit();

            \Log::info('=== CLIENTE ACTUALIZADO EXITOSAMENTE ===', ['cliente_id' => $cliente->id]);

            // ✅ Recargar cliente con relaciones
            $clienteActualizado = Cliente::with([
                'persona',
                'persona.telefonos',
                'persona.direcciones.distrito',
            ])->find($cliente->id);

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente',
                'data' => $clienteActualizado
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('ERROR AL ACTUALIZAR CLIENTE', [
                'cliente_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar cliente por documento
     */
    public function buscarPorDocumento(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'numero_documento' => 'nullable|string|max:20',
                'busqueda' => 'nullable|string|max:150',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe enviar al menos un criterio de búsqueda',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $numeroDocumento = $request->numero_documento;
            $busqueda = trim((string) $request->busqueda);

            if (!$numeroDocumento && !$busqueda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ingrese número de documento o nombre para buscar',
                ], 422);
            }

            // 1) Buscar si existe como CLIENTE
            $cliente = Cliente::whereHas('persona', function ($query) use ($numeroDocumento, $busqueda) {
                if ($numeroDocumento) {
                    $query->where('documento', $numeroDocumento);
                }

                if ($busqueda) {
                    $terms = preg_split('/\s+/', trim($busqueda));
                    $query->where(function ($q) use ($terms) {
                        foreach ($terms as $term) {
                            if (!empty($term)) {
                                $q->where(function ($subQuery) use ($term) {
                                    $subQuery->where('nombres', 'like', "%{$term}%")
                                        ->orWhere('ape_pat', 'like', "%{$term}%")
                                        ->orWhere('ape_mat', 'like', "%{$term}%");
                                });
                            }
                        }
                    });
                }
            })
                ->with(['persona', 'sucursal'])
                ->first();

            if ($cliente) {

                $prestamos = Prestamo::where('cliente_id', $cliente->id)
                    ->where('estado', '!=', 'Anulado')
                    ->with(['cuotas'])
                    ->orderBy('created_at', 'desc')
                    ->get();

                $tienePrestamos = $prestamos->isNotEmpty();
                $prestamosData = [];
                $puedeCrearPrestamo = true;
                $mensajeBloqueo = '';

                $estadosPermitidos = ['Cancelado', 'Finalizado', 'Liquidado', 'rechazado'];

                if ($tienePrestamos) {
                    $ultimoPrestamo = $prestamos->first();

                    if (!in_array($ultimoPrestamo->estado, $estadosPermitidos)) {
                        $puedeCrearPrestamo = false;
                        $mensajeBloqueo = "El cliente tiene un préstamo en estado '{$ultimoPrestamo->estado}'. Solo se puede crear un nuevo préstamo si el último está Cancelado, Finalizado o LIQUIDADO.";
                    }

                    $prestamosData = $prestamos->map(function ($prestamo) {
                        $cuotasPendientes = $prestamo->cuotas->where('estado', '!=', 2)->count();
                        $cuotasPagadas = $prestamo->cuotas->where('estado', 2)->count();
                        $totalCuotas = $prestamo->cuotas->count();

                        return [
                            'id' => $prestamo->id,
                            'codigo' => $prestamo->codigo,
                            'estado' => $prestamo->estado,
                            'tipo' => 'Cliente Titular',
                            'monto_solicitado' => $prestamo->cantidad_solicitada,
                            'fecha_solicitud' => $prestamo->fecha_atencion,
                            'fecha_primer_pago' => $prestamo->fecha_primer_pago,
                            'fecha_desembolso' => $prestamo->fecha_desembolso,
                            'cuotas_pendientes' => $cuotasPendientes,
                            'cuotas_pagadas' => $cuotasPagadas,
                            'total_cuotas' => $totalCuotas,
                            'plazo' => $prestamo->plazo,
                        ];
                    })->toArray();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Cliente encontrado',
                    'tipo_resultado' => 'cliente',
                    'data' => $cliente,
                    'tiene_prestamos' => $tienePrestamos,
                    'prestamos' => $prestamosData,
                    'puede_crear_prestamo' => $puedeCrearPrestamo,
                    'mensaje_bloqueo' => $mensajeBloqueo,
                ]);
            }

            // 2) Si no es cliente, buscar si existe como PERSONA
            $personaQuery = Persona::query();

            if ($numeroDocumento) {
                $personaQuery->where('documento', $numeroDocumento);
            }

            if ($busqueda) {
                $busquedaLike = '%' . $busqueda . '%';
                $personaQuery->whereRaw(
                    "CONCAT_WS(' ', nombres, ape_pat, ape_mat) LIKE ?",
                    [$busquedaLike]
                );
            }

            $persona = $personaQuery->first();

            if ($persona) {
                return response()->json([
                    'success' => true,
                    'message' => 'Persona encontrada en el sistema (no registrada como cliente)',
                    'tipo_resultado' => 'persona',
                    'data' => [
                        'persona' => $persona,
                        'nombres' => $persona->nombres,
                        'apellido_paterno' => $persona->ape_pat,
                        'apellido_materno' => $persona->ape_mat,
                        'fecha_nacimiento' => $persona->fecha_nacimiento,
                        'persona_id' => $persona->id,
                        'es_cliente' => false
                    ],
                    'tiene_prestamos' => false,
                    'prestamos' => [],
                    'puede_crear_prestamo' => true,
                    'mensaje_bloqueo' => '',
                ]);
            }

            // 3) Si no existe ni como persona, consultar RENIEC (si hay DNI)
            if ($numeroDocumento) {

                $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
                $token = ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7');
                $method = ApiConfig::getValue('dni_api_method', 'GET');

                $finalUrl = str_replace('{dni}', $numeroDocumento, $url);

                $httpClient = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ])->timeout(10);

                if (strtoupper($method) === 'POST') {
                    $response = $httpClient->post($finalUrl, ['dni' => $numeroDocumento]);
                } else {
                    $response = $httpClient->get($finalUrl);
                }

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                        $apiData = $data['data'];

                        $mappedData = [
                            'nombres' => $apiData['nombres'] ?? '',
                            'apellido_paterno' => $apiData['apellido_paterno'] ?? '',
                            'apellido_materno' => $apiData['apellido_materno'] ?? '',
                            'fecha_nacimiento' => $apiData['fecha_nacimiento'] ?? '',
                            'direccion' => $apiData['direccion'] ?? '',
                            'direccion_completa' => $apiData['direccion_completa'] ?? '',
                            'departamento' => $apiData['departamento'] ?? '',
                            'provincia' => $apiData['provincia'] ?? '',
                            'distrito' => $apiData['distrito'] ?? '',
                            'ubigeo' => $apiData['ubigeo'] ?? null,
                            'persona_existe' => false,
                            'es_cliente' => false
                        ];

                        return response()->json([
                            'success' => true,
                            'message' => 'Datos obtenidos de RENIEC',
                            'tipo_resultado' => 'reniec',
                            'data' => $mappedData,
                            'tiene_prestamos' => false,
                            'prestamos' => [],
                            'puede_crear_prestamo' => true,
                            'mensaje_bloqueo' => '',
                        ]);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Documento o nombre no encontrado en el sistema ni en RENIEC',
                'tipo_resultado' => 'no_encontrado',
            ], 404);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Validar DNI con API externa
     */
    private function validarDNI($dni): array
    {
        try {
            $response = Http::timeout(10)->get("https://apiperu.dev/api/dni/{$dni}", [
                'Authorization' => 'Bearer ' . env('API_PERU_TOKEN', '')
            ]);

            if ($response->successful() && $response->json('success')) {
                return ['valid' => true, 'data' => $response->json('data')];
            }

            return ['valid' => false];
        } catch (\Exception $e) {
            // Si falla la API, consideramos válido para no bloquear
            return ['valid' => true];
        }
    }

    /**
     * Generar código único para cliente
     */
    private function generarCodigoCliente(): string
    {
        do {
            $codigo = 'CLI-' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Cliente::where('codigo', $codigo)->exists());

        return $codigo;
    }

    /**
     * Consultar DNI en RENIEC para obtener datos personales
     */
    public function consultarDNI(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'numero_documento' => 'required|string|size:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI debe tener 8 dígitos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $dni = $request->numero_documento;

            // Verificar si ya es cliente
            $existingClient = Cliente::whereHas('persona', function ($query) use ($dni) {
                $query->where('documento', $dni);
            })->with(['persona'])->first();

            if ($existingClient) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI ya registrado como cliente',
                    'data' => [
                        'cliente_existe' => true,
                        'cliente' => $existingClient
                    ]
                ], 409);
            }

            // Buscar en personas existentes
            $persona = Persona::where('documento', $dni)->first();
            if ($persona) {
                return response()->json([
                    'success' => true,
                    'message' => 'Datos encontrados en base de datos',
                    'data' => [
                        'nombres' => $persona->nombres,
                        'apellido_paterno' => $persona->ape_pat,
                        'apellido_materno' => $persona->ape_mat,
                        'fecha_nacimiento' => $persona->fecha_nacimiento,
                        'persona_existe' => true,
                        'persona_id' => $persona->id,
                    ],
                ]);
            }

            // Consultar API externa (usar configuración del sistema)
            $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
            $token = ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7');
            $method = ApiConfig::getValue('dni_api_method', 'GET');

            $finalUrl = str_replace('{dni}', $dni, $url);

            $httpClient = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(10);

            if (strtoupper($method) === 'POST') {
                $response = $httpClient->post($finalUrl, ['dni' => $dni]);
            } else {
                $response = $httpClient->get($finalUrl);
            }

            if ($response->successful()) {
                $data = $response->json();

                // Manejar respuesta de API Factiliza
                if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    $apiData = $data['data'];

                    $mappedData = [
                        'nombres' => $apiData['nombres'] ?? '',
                        'apellido_paterno' => $apiData['apellido_paterno'] ?? '',
                        'apellido_materno' => $apiData['apellido_materno'] ?? '',
                        'fecha_nacimiento' => $apiData['fecha_nacimiento'] ?? '',
                        'direccion' => $apiData['direccion'] ?? '',
                        'direccion_completa' => $apiData['direccion_completa'] ?? '',
                        'departamento' => $apiData['departamento'] ?? '',
                        'provincia' => $apiData['provincia'] ?? '',
                        'distrito' => $apiData['distrito'] ?? '',
                        'ubigeo' => $apiData['ubigeo'] ?? null,
                        'persona_existe' => false,
                    ];

                    return response()->json([
                        'success' => true,
                        'message' => 'Datos obtenidos de RENIEC',
                        'data' => $mappedData,
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'DNI no encontrado en RENIEC',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar DNI: ' . $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Buscar o crear persona para aval
     */
    public function buscarOCrearPersonaAval(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'numero_documento' => 'required|string|size:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI debe tener 8 dígitos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $dni = $request->numero_documento;

            // Buscar en personas existentes
            $persona = Persona::where('documento', $dni)->first();
            if ($persona) {
                // Verificar si es cliente
                $esCliente = Cliente::where('persona_id', $persona->id)->exists();
                // Verificar morosidad**
                $esMoroso = false;
                $detallesMorosidad = [];

                if ($esCliente) {
                    $cliente = Cliente::where('persona_id', $persona->id)->first();

                    // Buscar préstamos con cuotas vencidas
                    $prestamos = Prestamo::where('cliente_id', $cliente->id)
                        ->whereNotIn('estado', ['Cancelado', 'Finalizado', 'Liquidado', 'Anulado', 'rechazado'])
                        ->get();

                    foreach ($prestamos as $prestamo) {
                        $cuotasVencidas = $prestamo->cuotas->count();
                        if ($cuotasVencidas > 0) {
                            if ($prestamo->estado === 'Moroso') {
                                $esMoroso = true;
                                $detallesMorosidad[] = [
                                    'prestamo_id' => $prestamo->id,
                                    'cuotas_vencidas' => $cuotasVencidas,
                                    'estado_prestamo' => $prestamo->estado
                                ];
                            }
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Persona encontrada en base de datos',
                    'data' => [
                        'persona_id' => $persona->id,
                        'nombres' => $persona->nombres,
                        'apellido_paterno' => $persona->ape_pat,
                        'apellido_materno' => $persona->ape_mat,
                        'fecha_nacimiento' => $persona->fecha_nacimiento,
                        'persona_existe' => true,
                        'es_cliente' => $esCliente,
                        'es_moroso' => $esMoroso,
                        'detalles_morosidad' => $detallesMorosidad,
                    ],
                ]);
            }

            // Consultar API externa (RENIEC)
            $url = ApiConfig::getValue('dni_api_url', 'https://api.factiliza.com/v1/dni/info/{dni}');
            $token = ApiConfig::getValue('dni_api_token', 'ece92d9d2a2bb54717373740c3180e722cf7a94b5501ed01a263e21e64a4b6a7');
            $method = ApiConfig::getValue('dni_api_method', 'GET');

            $finalUrl = str_replace('{dni}', $dni, $url);

            $httpClient = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])->timeout(10);

            if (strtoupper($method) === 'POST') {
                $response = $httpClient->post($finalUrl, ['dni' => $dni]);
            } else {
                $response = $httpClient->get($finalUrl);
            }

            if ($response->successful()) {
                $data = $response->json();

                // Manejar respuesta de API Factiliza
                if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                    $apiData = $data['data'];

                    // Convertir fecha de dd/mm/yyyy a yyyy-mm-dd
                    $fechaNacimiento = null;
                    if (isset($apiData['fecha_nacimiento']) && !empty($apiData['fecha_nacimiento'])) {
                        try {
                            // Intentar convertir formato dd/mm/yyyy
                            $fechaNacimiento = \Carbon\Carbon::createFromFormat('d/m/Y', $apiData['fecha_nacimiento'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            \Log::warning('Error al parsear fecha de nacimiento: ' . $apiData['fecha_nacimiento']);
                            $fechaNacimiento = now()->subYears(25)->format('Y-m-d'); // Fecha por defecto
                        }
                    } else {
                        $fechaNacimiento = now()->subYears(25)->format('Y-m-d'); // Fecha por defecto
                    }

                    DB::beginTransaction();

                    try {
                        // Crear persona en la base de datos
                        $nuevaPersona = Persona::create([
                            'documento' => $dni,
                            'nombres' => $apiData['nombres'] ?? '',
                            'ape_pat' => $apiData['apellido_paterno'] ?? '',
                            'ape_mat' => $apiData['apellido_materno'] ?? '',
                            'fecha_nacimiento' => $fechaNacimiento,
                            'estado_civil' => 'soltero', // Por defecto
                            'email' => null,
                        ]);

                        DB::commit();

                        \Log::info('Persona creada desde RENIEC para aval', [
                            'persona_id' => $nuevaPersona->id,
                            'dni' => $dni,
                            'nombres' => $nuevaPersona->nombres
                        ]);

                        return response()->json([
                            'success' => true,
                            'message' => 'Persona creada desde RENIEC',
                            'data' => [
                                'persona_id' => $nuevaPersona->id,
                                'nombres' => $nuevaPersona->nombres,
                                'apellido_paterno' => $nuevaPersona->ape_pat,
                                'apellido_materno' => $nuevaPersona->ape_mat,
                                'fecha_nacimiento' => $nuevaPersona->fecha_nacimiento,
                                'persona_existe' => false,
                                'es_cliente' => false,
                                'desde_reniec' => true,
                            ],
                        ]);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        \Log::error('Error al crear persona desde RENIEC', [
                            'error' => $e->getMessage(),
                            'dni' => $dni
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Error al guardar persona: ' . $e->getMessage(),
                        ], 500);
                    }
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'DNI no encontrado en RENIEC',
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error en buscarOCrearPersonaAval', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar/crear persona: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener sucursales por zona
     */
    public function getSucursalesByZona($zona_id): JsonResponse
    {
        try {
            $zona = Zona::with('sucursales')->find($zona_id);

            if (!$zona) {
                return response()->json([
                    'success' => false,
                    'message' => 'Zona no encontrada',
                ], 404);
            }

            $sucursales = $zona->sucursales->map(function ($sucursal) {
                return [
                    'id' => $sucursal->id,
                    'nombre' => $sucursal->sucursal
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $sucursales,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sucursales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener datos para formularios
     */
    public function datosFormulario(): JsonResponse
    {
        try {
            $data = [
                'departamentos' => Departamento::orderBy('departamento')->get(['id', 'departamento as nombre']),
                'zonas' => Zona::orderBy('nombre')->get(['id', 'nombre']),
                'sucursales' => Sucursal::orderBy('sucursal')->get(['id', 'sucursal as nombre']),
                'tipos_cuenta' => TipoCuenta::where('status', 1)->orderBy('id')->get(['id', 'tipo_cuenta']),
                'tipos_documento' => [
                    ['value' => 'DNI', 'label' => 'DNI'],
                    ['value' => 'CE', 'label' => 'Carnet de Extranjería'],
                    ['value' => 'RUC', 'label' => 'RUC'],
                ],
                'estados_civiles' => [
                    ['value' => 'soltero', 'label' => 'Soltero(a)'],
                    ['value' => 'casado', 'label' => 'Casado(a)'],
                    ['value' => 'divorciado', 'label' => 'Divorciado(a)'],
                    ['value' => 'viudo', 'label' => 'Viudo(a)'],
                    ['value' => 'conviviente', 'label' => 'Conviviente'],
                ],
            ];

            return response()->json([
                'success' => true,
                'message' => 'Datos obtenidos exitosamente',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del formulario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener direcciones de un cliente con todos los campos
     */
    public function getDirecciones($clienteId): JsonResponse
    {
        try {
            // Buscar cliente por su ID (no persona_id)
            $cliente = Cliente::with([
                'persona.direcciones.distrito.provincia.departamento',
                'persona.direcciones.sucursal',
                'persona.direcciones.zona'
            ])->find($clienteId); // Este es el ID de la tabla clientes

            // Validar si el cliente existe
            if (!$cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                ], 404);
            }

            $persona = $cliente->persona; // Obtiene la persona usando persona_id

            // Validar si tiene persona asociada
            if (!$persona) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cliente no tiene una persona asociada',
                ], 404);
            }

            // Aquí obtenemos las direcciones usando el persona_id de la tabla direcciones
            $direcciones = $persona->direcciones;

            // Si no hay direcciones, retornar array vacío con mensaje
            if ($direcciones->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'El cliente no tiene direcciones registradas',
                ]);
            }

            // Formatear direcciones con todos los campos
            $direccionesFormateadas = $direcciones->map(function ($direccion, $index) {
                $distrito = $direccion->distrito;
                $provincia = $distrito ? $distrito->provincia : null;
                $departamento = $provincia ? $provincia->departamento : null;
                $sucursal = $direccion->sucursal;
                $zona = $direccion->zona;

                $direccionCompleta = trim(
                    ($direccion->direccion ?? '') . ' ' .
                    ($distrito ? $distrito->distrito . ', ' : '') .
                    ($provincia ? $provincia->provincia . ', ' : '') .
                    ($departamento ? $departamento->departamento : '')
                );

                return [
                    'id' => $direccion->id,
                    'persona_id' => $direccion->persona_id,
                    'distrito_id' => $direccion->distrito_id,
                    'sucursal_id' => $direccion->sucursal_id,
                    'zona_id' => $direccion->zona_id,
                    'direccion' => $direccion->direccion ?? '',
                    'direccion_completa' => $direccionCompleta,
                    'numero' => $direccion->numero ?? ($index + 1),
                    'referencia' => $direccion->referencia ?? '',
                    'material_inmueble' => $direccion->material_inmueble,
                    'cant_pisos' => $direccion->cant_pisos,
                    'tipo_residencia' => $direccion->tipo_residencia,
                    'tipo_direccion' => $direccion->tipo_direccion,
                    'tiempo_residencia' => $direccion->tiempo_residencia,
                    'anios_meses' => $direccion->anios_meses,
                    'nombre_propietario' => $direccion->nombre_propietario,
                    'telefono_propietario' => $direccion->telefono_propietario,
                    'latitud' => $direccion->latitud,
                    'longitud' => $direccion->longitud,
                    'estado' => $direccion->estado,
                    'departamento' => $departamento->departamento ?? '',
                    'provincia' => $provincia->provincia ?? '',
                    'distrito' => $distrito->distrito ?? '',
                    'sucursal' => $sucursal->nombre ?? '',
                    'zona' => $zona->nombre ?? '',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $direccionesFormateadas,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener direcciones del cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Crear nueva dirección para un cliente
     */
    public function storeDireccion(Request $request, $clienteId): JsonResponse
    {
        try {
            $cliente = Cliente::findOrFail($clienteId);
            $persona = $cliente->persona;

            $validator = Validator::make($request->all(), [
                'direccion' => 'required|string|max:200',
                'referencia' => 'nullable|string|max:200',
                'distrito_id' => 'required|exists:distritos,id',
                'sucursal_id' => 'required|exists:sucursales,id',
                'zona_id' => 'nullable|exists:zonas,id',
                'latitud' => 'nullable|numeric',
                'longitud' => 'nullable|numeric',
                'numero' => 'nullable|string|max:50',
                'material_inmueble' => 'nullable|in:material_noble,prefabricada,machimbrado,otros',
                'cant_pisos' => 'nullable|integer|min:1',
                'tipo_residencia' => 'nullable|in:Propia,Familiar,Alquilada,Otros',
                'tiempo_residencia' => 'nullable|integer|min:0',
                'anios_meses' => 'nullable|in:meses,años',
                'tipo_direccion.*' => 'nullable|in:principal,secundario',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $direccion = Direccion::create([
                'persona_id' => $persona->id,
                'distrito_id' => $request->distrito_id,
                'sucursal_id' => $request->sucursal_id,
                'zona_id' => $request->zona_id,
                'direccion' => $request->direccion,
                'referencia' => $request->referencia,
                'latitud' => $request->latitud,
                'longitud' => $request->longitud,
                'numero' => $request->numero,
                'material_inmueble' => $request->material_inmueble,
                'cant_pisos' => $request->cant_pisos,
                'tipo_residencia' => $request->tipo_residencia,
                'tiempo_residencia' => $request->tiempo_residencia,
                'anios_meses' => $request->anios_meses,
                'tipo_direccion' => $request->tipo_direccion ? implode(',', $request->tipo_direccion) : null,
                'estado' => 1,
            ]);

            DB::commit();

            // Cargar relaciones
            $direccion->load('distrito.provincia.departamento');

            return response()->json([
                'success' => true,
                'message' => 'Dirección creada exitosamente',
                'data' => [
                    'id' => $direccion->id,
                    'numero' => $direccion->numero,
                    'direccion' => $direccion->direccion,
                    'referencia' => $direccion->referencia,
                    'distrito' => $direccion->distrito->distrito ?? '',
                    'provincia' => $direccion->distrito->provincia->provincia ?? '',
                    'departamento' => $direccion->distrito->provincia->departamento->departamento ?? '',
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al crear dirección: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}