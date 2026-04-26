<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MoraConvenioEstado;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Operacion;
use App\Models\Prestamo;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperacionesController extends Controller
{
    private $operacionEditandoId;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Inicia la consulta básica con las relaciones necesarias - Solo operaciones generales
        $query = Operacion::with([
            'cliente.persona',
            'prestamo',
            'metodoDePago',
            'editadoPor',
            'anuladoPor',
            'operacionesRelacionadas.cuotas',
            'operacionesRelacionadas.morasCuota',
        ])
            ->whereNull('operacion_general_id'); // Solo operaciones generales (no las relacionadas)

        // Filtro por DNI (nuevo)
        if ($request->has('dni') && $request->dni != '') {
            $dni = $request->dni;
            $query->whereHas('cliente.persona', function ($q) use ($dni) {
                $q->where('documento', 'like', "%$dni%");
            });
        }

        // Filtro por fecha exacta
        if ($request->has('fecha') && $request->fecha != '') {
            $query->whereDate('fecha', $request->fecha);
        }

        // Filtro por rango de fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin') && $request->fecha_inicio && $request->fecha_fin) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        // Filtro por zona - CORREGIDO
        if ($request->has('zona_id') && $request->zona_id != '') {
            $query->whereHas('cliente.persona.direcciones.sucursal.zonas', function ($q) use ($request) {
                $q->where('zona_id', $request->zona_id);
            });
        }

        // Filtro por sucursal
        if ($request->has('sucursal_id') && $request->sucursal_id != '') {
            $query->whereHas('cliente.persona.direcciones', function ($q) use ($request) {
                $q->where('sucursal_id', $request->sucursal_id);
            });
        }

        // Filtro por cliente
        if ($request->has('cliente_id') && $request->cliente_id != '') {
            $query->where('cliente_id', $request->cliente_id);
        }

        // Filtro por JCC - CORREGIDO (usando carterasJcc)
        if ($request->has('jcc_id') && $request->jcc_id != '') {
            $query->whereHas('prestamo.carterasJcc', function ($q) use ($request) {
                $q->where('user_id', $request->jcc_id);
            });
        }

        // Filtro por asesor - CORREGIDO (usando carterasAsesor)
        if ($request->has('asesor_id') && $request->asesor_id != '') {
            $query->whereHas('prestamo.carterasAsesor', function ($q) use ($request) {
                $q->where('user_id', $request->asesor_id);
            });
        }

        // Filtro por analista - CORREGIDO (usando carterasAnalista)
        if ($request->has('analista_id') && $request->analista_id != '') {
            $query->whereHas('prestamo.carterasAnalista', function ($q) use ($request) {
                $q->where('user_id', $request->analista_id);
            });
        }

        // Filtro por estado de préstamo
        if ($request->has('estado_prestamo') && $request->estado_prestamo != '') {
            $query->whereHas('prestamo', function ($q) use ($request) {
                $q->where('estado', $request->estado_prestamo);
            });
        }

        // Filtro por operación específica (para enlaces directos)
        if ($request->has('operacion_id') && $request->operacion_id != '') {
            $query->where('id', $request->operacion_id);
        }

        // Ordenar por las más recientes primero
        $query->orderBy('fecha', 'desc')->orderBy('id', 'desc');

        // Ejecutar la consulta y obtener los resultados
        $perPage = $request->get('per_page', 15); // Default 15 por página
        $operaciones = $query->paginate($perPage)->appends($request->query()); // Preservar filtros en paginación

        // Obtener los datos necesarios para los filtros
        $zonas = Zona::all();
        $sucursales = Sucursal::all();
        $clientes = Cliente::all();
        $jccs = User::role('JCC')->get();
        $asesores = User::role('Asesor')->get();
        $analistas = User::role('Analista')->get();
        $usuarios = User::all();

        return view('admin.Operaciones.index', compact('operaciones', 'zonas', 'sucursales', 'clientes', 'jccs', 'asesores', 'analistas', 'usuarios'));
    }

    public function generarPDF($prestamo_id)
    {
        // Obtener las operaciones generales y relacionadas
        $operacionesGenerales = Operacion::where('prestamo_id', $prestamo_id)
            ->whereNull('operacion_general_id')
            ->with('operacionesRelacionadas')
            ->get();

        // Generar el PDF
        $pdf = PDF::loadView('pdf.operaciones', compact('operacionesGenerales'));

        // Retornar el archivo descargable
        return $pdf->download('operaciones_generales.pdf');
    }

    public function desembolsar(Request $request)
    {
        try {
            // Validar los datos enviados desde el frontend
            $validated = $request->validate([
                'prestamo_id' => 'required|exists:prestamos,id',
                'monto' => 'required|numeric|min:0',
                'fecha' => 'required|date',
                'metodo_pago_id' => 'required|exists:metodos_de_pago,id',
                'user_id' => 'required|exists:users,id',
                'nro_operacion' => 'nullable|string|max:255',
                'imagen_deposito' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'tiene_comprobante' => 'required|boolean',
            ]);

            // Buscar el préstamo y verificar su estado
            $prestamo = Prestamo::findOrFail($validated['prestamo_id']);
            if ($prestamo->estado !== 'Por Desembolsar') {
                return response()->json([
                    'success' => false,
                    'message' => 'El préstamo debe estar en estado "Por Desembolsar" para ser desembolsado.',
                ], 400);
            }

            // Manejar la subida de la imagen
            $voucherPath = null;
            if ($request->hasFile('imagen_deposito')) {
                $voucherPath = $request->file('imagen_deposito')->store('depositos', 'public');
            }

            // Crear la operación de desembolso
            $operacion = Operacion::create([
                'prestamo_id' => $prestamo->id,
                'cliente_id' => $prestamo->cliente_id,
                'abono' => $validated['monto'],
                'fecha' => $validated['fecha'],
                'metodo_pago_id' => $validated['metodo_pago_id'],
                'tipo_operacion' => 'Desembolso',
                'codigo' => $validated['nro_operacion'],
                'user_id' => $validated['user_id'],
                'voucher_path' => $voucherPath,
            ]);

            // Actualizar el estado del préstamo
            $prestamo->update([
                'estado' => 'Vigente',
                'tiene_comprobante' => $validated['tiene_comprobante'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'El desembolso se ha registrado correctamente.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación en desembolso: '.json_encode($e->errors()));

            return response()->json([
                'success' => false,
                'message' => 'Errores de validación: '.implode(', ', $e->errors()[array_key_first($e->errors())]),
            ], 422);
        } catch (\Throwable $e) {
            \Log::error('Error en el desembolso: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al procesar el desembolso: '.$e->getMessage(),
            ], 500);
        }
    }

    public function buscarCliente(Request $request)
    {
        $term = $request->get('q');
        $page = $request->get('page', 1);
        $perPage = 15;

        // Usar la misma lógica que en la lista - todos los clientes
        $query = Cliente::with('persona')
            ->whereHas('persona', function ($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                    ->orWhere('ape_pat', 'like', "%{$term}%")
                    ->orWhere('ape_mat', 'like', "%{$term}%")
                    ->orWhere('documento', 'like', "%{$term}%");
            });

        $total = $query->count();
        $clientes = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $results = $clientes->map(function ($cliente) {
            return [
                'id' => $cliente->id,
                'text' => $cliente->persona->nombres.' '.$cliente->persona->ape_pat.' '.$cliente->persona->ape_mat.' - '.$cliente->persona->documento,
                'nombre' => $cliente->persona->nombres.' '.$cliente->persona->ape_pat.' '.$cliente->persona->ape_mat,
                'dni' => $cliente->persona->documento,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    /**
     * Display the specified operation with full details
     */
    public function show($id)
    {
        $operacion = Operacion::with([
            'cliente.persona',
            'prestamo',
            'metodoDePago',
            'operacionesRelacionadas.cuotas',
            'operacionesRelacionadas.morasCuota',
            'editadoPor',
            'anuladoPor',
        ])->findOrFail($id);

        return view('admin.Operaciones.show', compact('operacion'));
    }

    /**
     * Show the form for editing an operation
     */
    public function editar(Request $request, $operacion_id)
    {
        $operacion = Operacion::with([
            'prestamo',
            'cliente.persona',
            'user',
            'metodoDePago',
        ])->findOrFail($operacion_id);

        // Verificar que la operación se puede editar
        if ($operacion->estado === 'anulado') {
            return redirect()->back()->with('error', 'No se puede editar una operación anulada.');
        }

        // Obtener parámetros de retorno para convenio
        $returnTo = $request->input('return_to');
        $convenioId = $request->input('convenio_id');

        // Obtener todos los conceptos de pago de esta operación

        // PRIMERO: Obtener operaciones relacionadas (sub-operaciones)
        $operacionesRelacionadas = Operacion::where('operacion_general_id', $operacion_id)->get();

        \Log::info("Operación #{$operacion_id} - Operaciones relacionadas:", [
            'count' => $operacionesRelacionadas->count(),
            'detalles' => $operacionesRelacionadas->map(function ($op) {
                return [
                    'id' => $op->id,
                    'tipo_operacion' => $op->tipo_operacion,
                    'abono' => $op->abono,
                    'mora_cuota_id' => $op->mora_cuota_id,
                    'cuota_id' => $op->cuota_id,
                ];
            }),
        ]);

        // 1. CUOTAS - Buscar en múltiples fuentes

        // Método 1: Tabla pivot operaciones_cuota
        $cuotasDesdeTabla = DB::table('operaciones_cuota')
            ->join('cuotas', 'operaciones_cuota.cuota_id', '=', 'cuotas.id')
            ->where('operaciones_cuota.operacion_id', $operacion_id)
            ->select([
                'cuotas.id',
                'cuotas.numero',
                'cuotas.fecha_pago',
                'cuotas.monto',
                'cuotas.monto_pagado',
                'operaciones_cuota.monto_aplicado',
            ])
            ->get();

        \Log::info("Operación #{$operacion_id} - Cuotas desde tabla pivot:", [
            'count' => $cuotasDesdeTabla->count(),
            'cuotas' => $cuotasDesdeTabla->map(function ($c) {
                return [
                    'cuota_id' => $c->id,
                    'numero' => $c->numero,
                    'monto_aplicado' => $c->monto_aplicado,
                ];
            })->toArray(),
        ]);

        // Método 2: Operaciones relacionadas de tipo "Pago de cuota"
        $cuotasDesdeOperaciones = collect();
        foreach ($operacionesRelacionadas as $opRel) {
            if ($opRel->tipo_operacion === 'Pago de cuota') {
                // Buscar la cuota relacionada - puede estar en cuota_id o necesitamos buscarla por prestamo_id
                $cuota = null;

                if ($opRel->cuota_id) {
                    // Si tiene cuota_id directamente
                    $cuota = \App\Models\Cuota::find($opRel->cuota_id);
                } else {
                    // ❌ PROBLEMA: Esta lógica busca por cuotas disponibles, no por la cuota original
                    // Para operaciones EXISTENTES, deberíamos buscar en la tabla pivot operaciones_cuota
                    // o usar otra estrategia para determinar la cuota correcta

                    \Log::warning("Operación relacionada #{$opRel->id} no tiene cuota_id definido - puede causar asignación incorrecta");

                    // Intentar buscar en tabla pivot si esta operación relacionada tiene registro
                    $cuotaDesdeTablaRelacionada = DB::table('operaciones_cuota')
                        ->join('cuotas', 'operaciones_cuota.cuota_id', '=', 'cuotas.id')
                        ->where('operaciones_cuota.operacion_id', $opRel->id)
                        ->first();

                    if ($cuotaDesdeTablaRelacionada) {
                        $cuota = \App\Models\Cuota::find($cuotaDesdeTablaRelacionada->id);
                        \Log::info('✅ Cuota encontrada en tabla pivot para operación relacionada:', [
                            'operacion_rel_id' => $opRel->id,
                            'cuota_id' => $cuota->id,
                            'cuota_numero' => $cuota->numero,
                        ]);
                    } else {
                        \Log::warning("⚠️ No se pudo determinar la cuota para operación relacionada #{$opRel->id}");
                        $cuota = null; // No adivinar - mejor no mostrar datos incorrectos
                    }
                }

                if ($cuota) {
                    $cuotasDesdeOperaciones->push((object) [
                        'id' => $cuota->id,
                        'numero' => $cuota->numero,
                        'fecha_pago' => $cuota->fecha_pago,
                        'monto' => $cuota->monto,
                        'monto_pagado' => $cuota->monto_pagado,
                        'monto_aplicado' => $opRel->abono,
                        'operacion_relacionada_id' => $opRel->id,
                    ]);

                    \Log::info('Cuota encontrada para operación relacionada:', [
                        'operacion_rel_id' => $opRel->id,
                        'cuota_id' => $cuota->id,
                        'cuota_numero' => $cuota->numero,
                        'monto_aplicado' => $opRel->abono,
                    ]);
                }
            }
        }

        // Combinar ambas fuentes - SIEMPRE priorizar tabla pivot (más confiable)
        if ($cuotasDesdeTabla->count() > 0) {
            $cuotasAfectadas = $cuotasDesdeTabla;
            \Log::info('✅ Usando cuotas desde tabla pivot (fuente confiable)');
        } else {
            $cuotasAfectadas = $cuotasDesdeOperaciones;
            \Log::info('ℹ️ Usando cuotas desde operaciones relacionadas (fuente secundaria)');
        }

        \Log::info("Operación #{$operacion_id} - Cuotas consolidadas:", [
            'desde_tabla_pivot' => $cuotasDesdeTabla->count(),
            'desde_operaciones' => $cuotasDesdeOperaciones->count(),
            'cuotas_finales' => $cuotasAfectadas->count(),
            'fuente_usada' => $cuotasDesdeTabla->count() > 0 ? 'tabla_pivot' : 'operaciones_relacionadas',
        ]);

        // Verificación adicional: si no hay cuotas, revisar si la operación principal tiene cuota_id
        if ($cuotasAfectadas->count() === 0 && $operacion->cuota_id) {
            \Log::info("Recuperando cuota desde operación principal con cuota_id: {$operacion->cuota_id}");
            $cuotaPrincipal = \App\Models\Cuota::find($operacion->cuota_id);
            if ($cuotaPrincipal) {
                $cuotasAfectadas = collect([(object) [
                    'id' => $cuotaPrincipal->id,
                    'numero' => $cuotaPrincipal->numero,
                    'fecha_pago' => $cuotaPrincipal->fecha_pago,
                    'monto' => $cuotaPrincipal->monto,
                    'monto_pagado' => $cuotaPrincipal->monto_pagado,
                    'monto_aplicado' => $operacion->abono, // Usar el monto total de la operación principal
                ]]);
                \Log::info("✅ Cuota recuperada desde operación principal: Cuota #{$cuotaPrincipal->numero}");
            }
        }

        \Log::info('Cuotas afectadas por la operación (solo las que fueron parte del pago original):', [
            'total_cuotas' => $cuotasAfectadas->count(),
            'cuotas' => $cuotasAfectadas->map(function ($c) {
                return [
                    'id' => $c->id,
                    'numero' => $c->numero,
                    'monto_aplicado' => $c->monto_aplicado,
                ];
            })->toArray(),
        ]);

        // 2. MORAS - Buscar de múltiples formas
        $morasAfectadas = collect();

        \Log::info("Operación #{$operacion_id} - Buscando moras...");

        // Método 1: Operaciones relacionadas tipo "Pago de mora"
        foreach ($operacionesRelacionadas as $opRel) {
            \Log::info('Evaluando operación relacionada para moras:', [
                'id' => $opRel->id,
                'tipo' => $opRel->tipo_operacion,
                'mora_cuota_id' => $opRel->mora_cuota_id,
                'abono' => $opRel->abono,
            ]);

            if ($opRel->tipo_operacion === 'Pago de mora' && $opRel->mora_cuota_id) {
                $mora = \App\Models\MoraCuota::with('cuota')->find($opRel->mora_cuota_id);
                if ($mora) {
                    $mora->monto_aplicado = $opRel->abono;
                    $mora->operacion_relacionada_id = $opRel->id;
                    $morasAfectadas->push($mora);

                    \Log::info('✅ Mora encontrada desde operación relacionada:', [
                        'mora_id' => $mora->id,
                        'monto_aplicado' => $mora->monto_aplicado,
                        'cuota_numero' => $mora->cuota->numero ?? 'N/A',
                    ]);
                }
            }
        }

        // Método 2: Si no hay operaciones específicas de mora, intentar buscar en tabla pivot operacion_mora
        if ($morasAfectadas->count() === 0) {
            \Log::info('No se encontraron operaciones de mora específicas, verificando tabla pivot operacion_mora');

            // Buscar en tabla pivot operacion_mora (más confiable que recalcular por diferencia)
            $morasDesdeTabla = DB::table('operacion_mora')
                ->join('mora_cuota', 'operacion_mora.mora_cuota_id', '=', 'mora_cuota.id')
                ->join('cuotas', 'mora_cuota.cuota_id', '=', 'cuotas.id')
                ->where('operacion_mora.operacion_id', $operacion_id)
                ->select([
                    'mora_cuota.id',
                    'mora_cuota.fecha',
                    'mora_cuota.monto',
                    'mora_cuota.monto_pagado',
                    'cuotas.numero as cuota_numero',
                ])
                ->get();

            \Log::info('Moras encontradas en tabla pivot:', [
                'count' => $morasDesdeTabla->count(),
                'moras' => $morasDesdeTabla->toArray(),
            ]);

            if ($morasDesdeTabla->count() > 0) {
                // Si hay moras en la tabla pivot, necesitamos calcular correctamente cuánto se aplicó a cada una
                // Para esto, usaremos el cálculo de distribución secuencial que ya existe en el sistema

                $totalCuotas = $cuotasAfectadas->sum('monto_aplicado');

                // Obtener total de operaciones relacionadas existentes (abonos a cuota y favor)
                $totalOperacionesRelacionadas = DB::table('operaciones')
                    ->where('operacion_general_id', $operacion_id)
                    ->whereIn('tipo_operacion', ['Abono a cuota', 'Abono a favor'])
                    ->sum('abono');

                // Calcular cuánto dinero se destinó realmente a moras
                $montoDestinadoMoras = $operacion->abono - $totalCuotas - $totalOperacionesRelacionadas;

                \Log::info('Calculando distribución real de moras:', [
                    'total_operacion' => $operacion->abono,
                    'total_cuotas' => $totalCuotas,
                    'total_operaciones_relacionadas' => $totalOperacionesRelacionadas,
                    'monto_destinado_moras' => $montoDestinadoMoras,
                    'moras_en_tabla' => $morasDesdeTabla->count(),
                ]);

                if ($montoDestinadoMoras > 0) {
                    // Distribuir secuencialmente entre las moras (como se hace en el pago original)
                    $montoRestante = $montoDestinadoMoras;
                    $morasOrdenadas = $morasDesdeTabla->sortBy('fecha'); // Ordenar por fecha

                    foreach ($morasOrdenadas as $moraData) {
                        if ($montoRestante <= 0) {
                            break;
                        }

                        $mora = \App\Models\MoraCuota::with('cuota')->find($moraData->id);
                        if ($mora) {
                            $montoNecesario = $mora->monto; // Monto completo de la mora
                            $montoAAplicar = min($montoRestante, $montoNecesario);

                            $mora->monto_aplicado = $montoAAplicar;
                            $morasAfectadas->push($mora);
                            $montoRestante -= $montoAAplicar;

                            \Log::info('✅ Mora recuperada con distribución secuencial:', [
                                'mora_id' => $mora->id,
                                'fecha' => $mora->fecha,
                                'monto_mora' => $mora->monto,
                                'monto_aplicado' => $montoAAplicar,
                                'cuota_numero' => $mora->cuota->numero ?? 'N/A',
                                'restante' => $montoRestante,
                            ]);
                        }
                    }

                    // Si sobra dinero, podría haberse convertido en abono a favor
                    if ($montoRestante > 0) {
                        \Log::info("💰 Dinero sobrante después de distribuir moras: S/{$montoRestante} - podría estar como abono a favor");
                    }
                }
            } else {
                \Log::info('ℹ️  No hay registros de mora para esta operación - puede ser solo pago de cuotas o desembolso');
            }
        }

        // 3. AGREGAR MORAS DE LAS CUOTAS ESPECÍFICAS QUE ESTAMOS EDITANDO
        // Solo mostrar moras de las cuotas que fueron parte del pago original
        $idsCuotasAfectadas = $cuotasAfectadas->pluck('id')->toArray();

        if (! empty($idsCuotasAfectadas)) {
            $idsMorasYaIncluidas = $morasAfectadas->pluck('id')->toArray();

            // Buscar moras de las cuotas específicas que estamos editando
            $morasDelasCuotasAfectadas = \App\Models\MoraCuota::whereIn('cuota_id', $idsCuotasAfectadas)
                ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE->value, \App\Enums\MoraCuotaEstado::PARCIAL->value])
                ->whereNotIn('id', $idsMorasYaIncluidas) // Excluir las ya procesadas
                ->with('cuota')
                ->orderBy('fecha')
                ->get();

            \Log::info('Moras disponibles DE LAS CUOTAS que estamos editando:', [
                'cuotas_afectadas' => $idsCuotasAfectadas,
                'moras_count' => $morasDelasCuotasAfectadas->count(),
                'moras' => $morasDelasCuotasAfectadas->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'cuota_id' => $m->cuota_id,
                        'fecha' => $m->fecha,
                        'estado' => $m->estado,
                        'monto' => $m->monto,
                        'monto_pagado' => $m->monto_pagado,
                        'cuota_numero' => $m->cuota->numero ?? 'N/A',
                    ];
                })->toArray(),
            ]);

            // Agregar moras específicas con monto_aplicado = 0
            foreach ($morasDelasCuotasAfectadas as $moraDisponible) {
                $moraDisponible->monto_aplicado = 0; // Inicialmente sin aplicar
                $moraDisponible->es_disponible = true; // Marcar como disponible para edición
                $morasAfectadas->push($moraDisponible);
            }
        }

        \Log::info("Operación #{$operacion_id} - Resultado final moras (de cuotas específicas):", [
            'moras_ya_pagadas' => $morasAfectadas->where('es_disponible', '!=', true)->count(),
            'moras_disponibles_de_cuotas' => $morasAfectadas->where('es_disponible', true)->count(),
            'total_moras' => $morasAfectadas->count(),
            'total_monto_moras_aplicado' => $morasAfectadas->sum('monto_aplicado'),
        ]);

        // 4. ABONOS A CUOTA - desde las operaciones relacionadas tipo "Abono a cuota"
        $abonosCuotaAfectados = collect();
        foreach ($operacionesRelacionadas as $opRel) {
            if ($opRel->tipo_operacion === 'Abono a cuota' && $opRel->cuota_id) {
                $cuotaAbono = \App\Models\Cuota::find($opRel->cuota_id);
                if ($cuotaAbono) {
                    $abonosCuotaAfectados->push((object) [
                        'id' => $opRel->id,
                        'cuota_id' => $opRel->cuota_id,
                        'cuota_numero' => $cuotaAbono->numero,
                        'monto_aplicado' => $opRel->abono,
                        'cuota' => $cuotaAbono,
                        'operacion_relacionada_id' => $opRel->id,
                    ]);
                }
            }
        }

        // 5. ABONOS A FAVOR - desde las operaciones relacionadas tipo "Abono a favor"
        $abonosFavorAfectados = collect();
        foreach ($operacionesRelacionadas as $opRel) {
            if ($opRel->tipo_operacion === 'Abono a favor' && $opRel->cuota_id) {
                $cuotaFavor = \App\Models\Cuota::find($opRel->cuota_id);
                if ($cuotaFavor) {
                    $abonosFavorAfectados->push((object) [
                        'id' => $opRel->id,
                        'cuota_id' => $opRel->cuota_id,
                        'cuota_numero' => $cuotaFavor->numero,
                        'monto_aplicado' => $opRel->abono,
                        'cuota' => $cuotaFavor,
                        'operacion_relacionada_id' => $opRel->id,
                    ]);
                }
            }
        }

        // 6. CALCULAR SALDO A FAVOR TOTAL DISPONIBLE DEL PRÉSTAMO
        // Incluir tanto registros de AbonoMoraFavor como operaciones de tipo "Abono a favor"
        $saldoFavorAbonoMora = \App\Models\AbonoMoraFavor::whereHas('cuota', function ($query) use ($operacion) {
            $query->where('prestamo_id', $operacion->prestamo_id);
        })
            ->where('estado', \App\Models\AbonoMoraFavor::ESTADO_ACTIVO)
            ->sum('saldo_favor');

        // Sumar operaciones de tipo "Abono a favor" completadas
        $saldoFavorOperaciones = \App\Models\Operacion::where('prestamo_id', $operacion->prestamo_id)
            ->where('tipo_operacion', 'Abono a favor')
            ->where('estado', 'completado')
            ->sum('abono');

        $saldoFavorDisponible = $saldoFavorAbonoMora + $saldoFavorOperaciones;

        \Log::info("Operación #{$operacion_id} - Saldo a favor disponible: S/ {$saldoFavorDisponible}");

        // Calcular totales para verificación
        $totalCuotas = $cuotasAfectadas->sum('monto_aplicado');
        $totalMoras = $morasAfectadas->sum('monto_aplicado');
        $totalAbonosCuota = $abonosCuotaAfectados->sum('monto_aplicado');
        $totalAbonosFavor = $abonosFavorAfectados->sum('monto_aplicado');
        $totalCalculado = $totalCuotas + $totalMoras + $totalAbonosCuota + $totalAbonosFavor;

        \Log::info("Operación #{$operacion_id} - Desglose completo:", [
            'total_operacion' => $operacion->abono,
            'total_cuotas' => $totalCuotas,
            'total_moras' => $totalMoras,
            'total_abonos_cuota' => $totalAbonosCuota,
            'total_abonos_favor' => $totalAbonosFavor,
            'total_calculado' => $totalCalculado,
            'cuotas_count' => $cuotasAfectadas->count(),
            'moras_count' => $morasAfectadas->count(),
            'abonos_cuota_count' => $abonosCuotaAfectados->count(),
            'abonos_favor_count' => $abonosFavorAfectados->count(),
        ]);

        // Agrupar moras por cuota para mejor UX
        $morasPorCuota = $morasAfectadas->groupBy('cuota_id');

        // Crear estructura optimizada para la vista
        $cuotasConMoras = $cuotasAfectadas->map(function ($cuota) use ($morasPorCuota) {
            $cuota->moras = $morasPorCuota->get($cuota->id, collect());

            return $cuota;
        });

        \Log::info('Estructura final para vista:', [
            'cuotas_count' => $cuotasConMoras->count(),
            'estructura' => $cuotasConMoras->map(function ($c) {
                return [
                    'cuota_id' => $c->id,
                    'cuota_numero' => $c->numero,
                    'moras_count' => $c->moras->count(),
                ];
            })->toArray(),
        ]);

        // Obtener métodos de pago disponibles
        $metodosPago = \App\Models\MetodoDePago::where('status', 1)->get();

        return view('admin.Operaciones.editar', compact(
            'operacion',
            'cuotasAfectadas',
            'morasAfectadas',
            'abonosCuotaAfectados',
            'abonosFavorAfectados',
            'totalCalculado',
            'metodosPago',
            'saldoFavorDisponible',
            'cuotasConMoras', // Nueva estructura optimizada
            'returnTo',
            'convenioId'
        ));
    }

    public function actualizar(Request $request, $operacion_id)
    {
        // Guardar ID de operación que estamos editando para el fix selectivo
        $this->operacionEditandoId = $operacion_id;

        // Log de debugging
        \Log::info('Operación actualizar iniciada', [
            'operacion_id' => $operacion_id,
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'nuevo_abono' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'justificacion_edicion' => 'required|string|min:10',
            'metodo_pago_id' => 'nullable|exists:metodos_de_pago,id',
            'nro_operacion' => 'nullable|string|max:255',
            'cuotas.*' => 'nullable|numeric|min:0',
            'moras.*' => 'nullable|numeric|min:0',
            'abonos_cuota.*' => 'nullable|numeric|min:0',
            'abonos_favor.*' => 'nullable|numeric|min:0',
            'nuevo_abono_favor' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $operacion = Operacion::findOrFail($operacion_id);

            // Verificar que la operación se puede editar
            if ($operacion->estado === 'anulado') {
                throw new \Exception('No se puede editar una operación anulada.');
            }

            // Guardar datos originales para auditoría
            $datosOriginales = [
                'abono_original' => $operacion->abono,
                'fecha_original' => $operacion->fecha,
                'metodo_pago_original' => $operacion->metodo_pago_id,
            ];

            // Actualizar operación principal
            $operacion->update([
                'abono' => $request->nuevo_abono,
                'fecha' => $request->fecha,
                'metodo_pago_id' => $request->metodo_pago_id,
                'codigo' => $request->nro_operacion,
                'justificacion_edicion' => $request->justificacion_edicion,
                'editado_por' => auth()->id(),
                'editado_en' => now(),
            ]);

            // ACTUALIZAR TODOS LOS CONCEPTOS DE PAGO

            // 1. SISTEMA HÍBRIDO DE EDICIÓN DE CUOTAS
            if ($request->has('cuotas')) {
                $modoEdicion = $request->input('modo_edicion', 'manual'); // Default: manual
                \Log::info("🔄 INICIANDO edición híbrida - Modo: {$modoEdicion}");

                $montoNuevo = (float) $request->nuevo_abono;
                $montoOriginal = (float) $operacion->getOriginal('abono');
                $fechaNueva = $request->fecha;
                $fechaOriginal = $operacion->getOriginal('fecha');

                $diferenciaMonto = $montoNuevo - $montoOriginal;
                $cambioFecha = $fechaNueva !== $fechaOriginal;

                // DETECTAR TIPO DE CAMBIO
                $tiposCambio = [];
                if (abs($diferenciaMonto) > 0.01) {
                    $tiposCambio[] = $diferenciaMonto > 0 ? 'incremento_monto' : 'decremento_monto';
                }
                if ($cambioFecha) {
                    $tiposCambio[] = 'cambio_fecha';
                }

                \Log::info('📊 Cambios detectados: '.implode(', ', $tiposCambio)." (Diferencia monto: S/ {$diferenciaMonto})");

                // APLICAR LÓGICA SEGÚN MODO
                if ($modoEdicion === 'automatico') {
                    // MODO AUTOMÁTICO: Redistribución completa
                    $this->aplicarModoAutomatico($operacion_id, $operacion, $montoNuevo, $fechaNueva, $request);
                } else {
                    // MODO MANUAL: Edición selectiva
                    $this->aplicarModoManual($operacion_id, $operacion, $request, $cambioFecha, $fechaNueva);
                }

                \Log::info("✅ Edición híbrida completada - Modo: {$modoEdicion}");
            }

            // 2. Actualizar moras - DISTRIBUCIÓN SECUENCIAL CORRECTA
            if ($request->has('moras')) {
                // Obtener el total de monto para moras de esta operación
                $totalMontoMoras = array_sum($request->moras);
                \Log::info("Total monto moras a distribuir: {$totalMontoMoras}");

                // Obtener la cuota de la primera mora editada para distribuir secuencialmente
                $primerMoraId = array_key_first($request->moras);
                $primerMora = \App\Models\MoraCuota::find($primerMoraId);
                $cuotaId = $primerMora->cuota_id;

                // Obtener todas las moras pendientes de esta cuota ordenadas por fecha (max 7)
                $morasPendientes = \App\Models\MoraCuota::where('cuota_id', $cuotaId)
                    ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE->value, \App\Enums\MoraCuotaEstado::PARCIAL->value])
                    ->orderBy('fecha')
                    ->limit(7)
                    ->get();

                \Log::info("Moras pendientes encontradas para cuota {$cuotaId}: ".$morasPendientes->count());

                // Limpiar registros existentes de operacion_mora para esta operación
                DB::table('operacion_mora')
                    ->where('operacion_id', $operacion_id)
                    ->delete();

                // Distribuir secuencialmente el monto total entre las moras pendientes
                $montoRestante = $totalMontoMoras;
                $morasActualizadas = 0;

                foreach ($morasPendientes as $mora) {
                    if ($montoRestante <= 0 || $morasActualizadas >= 7) {
                        break;
                    }

                    $saldoMora = $mora->monto - ($mora->monto_pagado ?? 0);
                    if ($saldoMora <= 0) {
                        continue;
                    }

                    $montoAplicado = min($montoRestante, $saldoMora);

                    // Crear nueva relación operacion-mora
                    DB::table('operacion_mora')->insert([
                        'operacion_id' => $operacion_id,
                        'mora_cuota_id' => $mora->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $montoRestante -= $montoAplicado;
                    $morasActualizadas++;

                    \Log::info("Mora {$mora->id} procesada: monto aplicado = {$montoAplicado}");
                }

                // Si sobra dinero después de pagar hasta 7 moras, va a abono a favor
                if ($montoRestante > 0) {
                    \Log::info("Sobrante de {$montoRestante} irá a abono a favor de cuota {$cuotaId}");

                    // Crear operación relacionada de tipo "Abono a favor"
                    $operacionAbonoFavor = Operacion::create([
                        'fecha' => $request->fecha,
                        'abono' => $montoRestante,
                        'tipo_operacion' => 'Abono a favor',
                        'estado' => 'completado',
                        'prestamo_id' => $operacion->prestamo_id,
                        'cliente_id' => $operacion->cliente_id,
                        'operacion_general_id' => $operacion_id,
                        'metodo_pago_id' => $request->metodo_pago_id,
                        'user_id' => auth()->id(),
                    ]);

                    \Log::info("✅ Abono a favor creado: ID {$operacionAbonoFavor->id}, Monto S/ {$montoRestante}, Cuota #{$primerMora->cuota->numero}");
                }
            }

            // 3. Actualizar abonos a cuota (operaciones relacionadas de tipo "Abono a cuota")
            if ($request->has('abonos_cuota')) {
                foreach ($request->abonos_cuota as $abonoId => $nuevoMonto) {
                    if ($nuevoMonto !== null && $nuevoMonto >= 0) {
                        $operacionAbono = Operacion::where('operacion_general_id', $operacion_id)
                            ->where('tipo_operacion', 'Abono a cuota')
                            ->where('id', $abonoId)
                            ->first();

                        if ($operacionAbono) {
                            $operacionAbono->update(['abono' => $nuevoMonto]);
                            \Log::info("Abono a cuota {$abonoId} actualizado: nuevo monto = {$nuevoMonto}");
                        }
                    }
                }
            }

            // 4. Actualizar abonos a favor (operaciones relacionadas de tipo "Abono a favor")
            if ($request->has('abonos_favor')) {
                foreach ($request->abonos_favor as $favorId => $nuevoMonto) {
                    if ($nuevoMonto !== null && $nuevoMonto >= 0) {
                        $operacionFavor = Operacion::where('operacion_general_id', $operacion_id)
                            ->where('tipo_operacion', 'Abono a favor')
                            ->where('id', $favorId)
                            ->first();

                        if ($operacionFavor) {
                            $operacionFavor->update(['abono' => $nuevoMonto]);
                            \Log::info("Abono a favor {$favorId} actualizado: nuevo monto = {$nuevoMonto}");
                        }
                    }
                }
            }

            // 5. Procesar nuevo abono a favor (si se especificó)
            if ($request->has('nuevo_abono_favor') && $request->nuevo_abono_favor > 0) {
                $montoNuevoFavor = (float) $request->nuevo_abono_favor;

                // Verificar que no exceda el saldo disponible
                $saldoFavorAbonoMora = \App\Models\AbonoMoraFavor::whereHas('cuota', function ($query) use ($operacion) {
                    $query->where('prestamo_id', $operacion->prestamo_id);
                })
                    ->where('estado', \App\Models\AbonoMoraFavor::ESTADO_ACTIVO)
                    ->sum('saldo_favor');

                $saldoFavorOperaciones = \App\Models\Operacion::where('prestamo_id', $operacion->prestamo_id)
                    ->where('tipo_operacion', 'Abono a favor')
                    ->where('estado', 'completado')
                    ->sum('abono');

                $saldoFavorDisponible = $saldoFavorAbonoMora + $saldoFavorOperaciones;

                if ($montoNuevoFavor > $saldoFavorDisponible) {
                    throw new \Exception("El monto S/ {$montoNuevoFavor} excede el saldo a favor disponible S/ {$saldoFavorDisponible}");
                }

                // Obtener la primera cuota del préstamo para asignar el abono
                $primeraCuota = $operacion->prestamo->cuotas()->orderBy('numero')->first();

                if ($primeraCuota) {
                    // Crear operación relacionada de tipo "Abono a favor"
                    $operacionNuevoFavor = Operacion::create([
                        'fecha' => $request->fecha,
                        'abono' => $montoNuevoFavor,
                        'tipo_operacion' => 'Abono a favor',
                        'estado' => 'completado',
                        'prestamo_id' => $operacion->prestamo_id,
                        'cliente_id' => $operacion->cliente_id,
                        'operacion_general_id' => $operacion_id,
                        'metodo_pago_id' => $request->metodo_pago_id,
                        'user_id' => auth()->id(),
                    ]);

                    // Aplicar el monto contra los abonos a favor existentes
                    $montoRestante = $montoNuevoFavor;
                    $abonosFavorActivos = \App\Models\AbonoMoraFavor::whereHas('cuota', function ($query) use ($operacion) {
                        $query->where('prestamo_id', $operacion->prestamo_id);
                    })
                        ->where('estado', \App\Models\AbonoMoraFavor::ESTADO_ACTIVO)
                        ->where('saldo_favor', '>', 0)
                        ->orderBy('fecha_abono')
                        ->get();

                    foreach ($abonosFavorActivos as $abonoFavor) {
                        if ($montoRestante <= 0) {
                            break;
                        }

                        $montoAUtilizar = min($montoRestante, $abonoFavor->saldo_favor);

                        // Utilizar el saldo a favor
                        $abonoFavor->monto_utilizado += $montoAUtilizar;
                        $abonoFavor->saldo_favor -= $montoAUtilizar;

                        if ($abonoFavor->saldo_favor <= 0) {
                            $abonoFavor->estado = \App\Models\AbonoMoraFavor::ESTADO_UTILIZADO;
                        }

                        $abonoFavor->save();
                        $montoRestante -= $montoAUtilizar;

                        \Log::info("Abono a favor utilizado: ID {$abonoFavor->id}, Monto S/ {$montoAUtilizar}");
                    }

                    \Log::info("✅ Nuevo abono a favor aplicado: ID {$operacionNuevoFavor->id}, Monto S/ {$montoNuevoFavor}");
                }
            }

            // APLICAR REGULARIZACIÓN DE MORAS POR FECHA ANTES DEL RECÁLCULO
            $this->regularizarMorasPorFechaEdicion($operacion, $request);

            // RECALCULAR SOLO LAS CUOTAS AFECTADAS POR ESTA OPERACIÓN ESPECÍFICA
            $prestamo = $operacion->prestamo;

            // Obtener solo las cuotas que fueron afectadas por esta edición
            $cuotasAfectadasIds = collect();

            // Cuotas desde el request actual
            if ($request->has('cuotas')) {
                foreach ($request->cuotas as $cuotaId => $monto) {
                    if ((float) $monto >= 0) {
                        $cuotasAfectadasIds->push($cuotaId);
                    }
                }
            }

            // Cuotas desde registros existentes en la tabla pivot de esta operación
            $cuotasExistentes = DB::table('operaciones_cuota')
                ->where('operacion_id', $operacion_id)
                ->pluck('cuota_id');
            $cuotasAfectadasIds = $cuotasAfectadasIds->merge($cuotasExistentes);

            // Cuotas desde operaciones relacionadas
            $operacionesRelacionadas = \App\Models\Operacion::where('operacion_general_id', $operacion_id)->get();
            foreach ($operacionesRelacionadas as $opRel) {
                $cuotasDeOperacionRelacionada = DB::table('operaciones_cuota')
                    ->where('operacion_id', $opRel->id)
                    ->pluck('cuota_id');
                $cuotasAfectadasIds = $cuotasAfectadasIds->merge($cuotasDeOperacionRelacionada);
            }

            $cuotasAfectadasIds = $cuotasAfectadasIds->unique();
            $cuotasAfectadas = $prestamo->cuotas()->whereIn('id', $cuotasAfectadasIds)->get();

            \Log::info("Recalculando estados SOLO de {$cuotasAfectadas->count()} cuotas afectadas por operación #{$operacion_id}: ".$cuotasAfectadasIds->implode(', '));

            foreach ($cuotasAfectadas as $cuota) {
                // Obtener total pagado de TODAS las operaciones válidas para esta cuota (incluyendo relacionadas)
                $totalPagado = DB::table('operaciones_cuota')
                    ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                    ->where('operaciones_cuota.cuota_id', $cuota->id)
                    ->where('operaciones.estado', '!=', 'anulado')
                    ->sum('operaciones_cuota.monto_aplicado');

                // NO DUPLICAR: La consulta anterior ya incluye todas las operaciones (principales y relacionadas)

                $estadoAnterior = $cuota->estado;
                $montoPagadoAnterior = $cuota->monto_pagado;

                // ACTUALIZAR EL CAMPO monto_pagado EN LA TABLA CUOTAS
                $cuota->monto_pagado = $totalPagado;

                // Log detallado para debugging con SQL query y fallback para monto aplicado
                $querySQL = DB::table('operaciones_cuota')
                    ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                    ->where('operaciones_cuota.cuota_id', $cuota->id)
                    ->where('operaciones.estado', '!=', 'anulado')
                    ->select('operaciones_cuota.monto_aplicado', 'operaciones.id as op_id', 'operaciones.estado', 'operaciones.abono')
                    ->get();

                // CORREGIR monto aplicado SOLO para operaciones relacionadas con la edición actual
                // NO tocar operaciones de otros pagos/ediciones anteriores
                $operacionesRelacionadas = collect();
                if (isset($this->operacionEditandoId)) {
                    $operacionesRelacionadas = \App\Models\Operacion::where('operacion_general_id', $this->operacionEditandoId)
                        ->pluck('id');
                    $operacionesRelacionadas->push($this->operacionEditandoId);
                }

                foreach ($querySQL as $registro) {
                    if ((float) $registro->monto_aplicado <= 0 && (float) $registro->abono > 0) {
                        // SOLO corregir si es parte de la operación que estamos editando AHORA
                        if ($operacionesRelacionadas->contains($registro->op_id)) {
                            \Log::warning("🔧 CORRIGIENDO monto aplicado (SOLO operación actual) - Operación {$registro->op_id}: tabla pivot tiene {$registro->monto_aplicado}, operaciones tabla tiene {$registro->abono}");

                            // Actualizar tabla pivot con el valor correcto
                            DB::table('operaciones_cuota')
                                ->where('operacion_id', $registro->op_id)
                                ->where('cuota_id', $cuota->id)
                                ->update(['monto_aplicado' => $registro->abono]);

                            $registro->monto_aplicado = $registro->abono;
                        } else {
                            \Log::info("⏭️ IGNORANDO operación {$registro->op_id} - No es parte de la edición actual");
                        }
                    }
                }

                \Log::info("Debug cálculo cuota #{$cuota->id}", [
                    'total_calculado' => $totalPagado,
                    'monto_cuota' => $cuota->monto,
                    'operaciones_encontradas' => $querySQL->toArray(),
                ]);

                // Actualizar estado basado PRIMERO en monto pagado (es lo más importante)
                if ($totalPagado >= $cuota->monto) {
                    // Si el monto está completamente pagado, SIEMPRE es PAGADO
                    // No importa si se pagó antes o después del vencimiento
                    $cuota->estado = 2; // PAGADO
                } elseif ($totalPagado > 0) {
                    $cuota->estado = 1; // PARCIAL
                } else {
                    $cuota->estado = 0; // PENDIENTE
                }

                // Verificar si está vencida SOLO si NO está completamente pagada
                // Una cuota completamente pagada (totalPagado >= monto) NUNCA debe estar como VENCIDA
                $hoy = now();
                if ($totalPagado < $cuota->monto && $cuota->fecha_pago < $hoy) {
                    $cuota->estado = 3; // VENCIDO
                }

                $cuota->save();

                \Log::info("Cuota #{$cuota->id} recalculada", [
                    'monto_cuota' => $cuota->monto,
                    'monto_pagado_anterior' => $montoPagadoAnterior,
                    'monto_pagado_nuevo' => $totalPagado,
                    'estado_anterior' => $estadoAnterior,
                    'estado_nuevo' => $cuota->estado,
                    'fecha_pago' => $cuota->fecha_pago,
                    'vencida' => $cuota->fecha_pago < $hoy,
                ]);
            }

            // RECALCULAR SOLO LAS MORAS DE LAS CUOTAS AFECTADAS
            $morasAfectadas = collect();
            if ($cuotasAfectadasIds->count() > 0) {
                $morasAfectadas = \App\Models\MoraCuota::whereIn('cuota_id', $cuotasAfectadasIds)->get();
            }

            \Log::info("Recalculando estados SOLO de {$morasAfectadas->count()} moras de cuotas afectadas por operación #{$operacion_id}");

            foreach ($morasAfectadas as $mora) {
                // IMPORTANTE: NO recalcular moras que ya fueron regularizadas o pagadas
                // Las moras regularizadas (estado 3) o pagadas (estado 2) no deben ser sobrescritas
                if ($mora->estado == \App\Enums\MoraCuotaEstado::REGULARIZADA) {
                    \Log::info("⏭️ Mora #{$mora->id} YA REGULARIZADA - NO recalculando estado");
                    continue;
                }

                // NUEVO: NO recalcular moras que ya fueron pagadas completamente (por abono a favor)
                if ($mora->estado == \App\Enums\MoraCuotaEstado::PAGADO && ($mora->monto_pagado ?? 0) >= $mora->monto) {
                    \Log::info("⏭️ Mora #{$mora->id} YA PAGADA (monto_pagado: {$mora->monto_pagado}) - NO recalculando estado");
                    continue;
                }

                // IMPORTANTE: NO recalcular moras que ya tienen monto_pagado por abonos a favor
                // Si la mora tiene monto_pagado pero no tiene registros en operacion_mora,
                // significa que fue pagada con abono a favor y NO debe recalcularse
                $tienePagosEnOperacionMora = DB::table('operacion_mora')
                    ->join('operaciones', 'operacion_mora.operacion_id', '=', 'operaciones.id')
                    ->where('operacion_mora.mora_cuota_id', $mora->id)
                    ->where('operaciones.estado', '!=', 'anulado')
                    ->exists();

                if (($mora->monto_pagado ?? 0) > 0 && !$tienePagosEnOperacionMora) {
                    \Log::info("⏭️ Mora #{$mora->id} pagada con ABONO A FAVOR (monto_pagado: {$mora->monto_pagado}) - NO recalculando");
                    continue;
                }

                // Recalcular monto pagado total para esta mora usando distribución secuencial correcta
                $totalPagadoMora = $this->calcularMontoPagadoMoraCorrectamente($mora);

                // Log para debugging
                \Log::info("Debug cálculo mora #{$mora->id}", [
                    'total_calculado' => $totalPagadoMora,
                    'monto_mora' => $mora->monto,
                ]);

                // Actualizar monto_pagado en la mora
                $mora->monto_pagado = $totalPagadoMora;

                // Actualizar estado basado en monto pagado vs monto total
                if ($totalPagadoMora >= $mora->monto) {
                    $mora->estado = 2; // PAGADO
                } elseif ($totalPagadoMora > 0) {
                    $mora->estado = 1; // PARCIAL
                } else {
                    $mora->estado = 0; // PENDIENTE
                }

                $mora->save();

                \Log::info("Mora #{$mora->id} recalculada", [
                    'monto_mora' => $mora->monto,
                    'monto_pagado_nuevo' => $totalPagadoMora,
                    'estado_nuevo' => $mora->estado,
                ]);
            }

            // GENERAR MORAS ESPECÍFICAMENTE para este préstamo si es necesario
            // Incluir cuotas VENCIDAS (estado 3) y PARCIALES vencidas (estado 1 con fecha pasada)
            $hoy = now();
            $cuotasQuenecesitanMoras = $prestamo->cuotas->filter(function ($cuota) use ($hoy) {
                // Cuotas vencidas (estado 3)
                if ($cuota->estado == 3) {
                    return true;
                }
                // Cuotas parciales (estado 1) que han pasado su fecha de vencimiento
                if ($cuota->estado == 1 && $cuota->fecha_pago < $hoy) {
                    return true;
                }

                return false;
            });

            if ($cuotasQuenecesitanMoras->count() > 0) {
                \Log::info("Generando moras para {$cuotasQuenecesitanMoras->count()} cuotas vencidas/parciales del préstamo #{$prestamo->id}");

                // Generar moras específicamente para este préstamo
                try {
                    $moraService = app(\App\Services\MoraService::class);
                    $resultadoMoras = $moraService->regularizarMorasPrestamoIndividual($prestamo->id);

                    \Log::info("Resultado generación de moras para préstamo #{$prestamo->id}", $resultadoMoras);
                } catch (\Exception $e) {
                    \Log::warning("Error al generar moras específicas para préstamo #{$prestamo->id}: ".$e->getMessage());

                    // Fallback: usar comando general
                    try {
                        \Artisan::call('moras:generar-diarias');
                    } catch (\Exception $e2) {
                        \Log::warning('Error al generar moras con comando general: '.$e2->getMessage());
                    }
                }
            }

            // Disparar evento para actualizar estados del préstamo
            // Esto asegurará que el préstamo se muestre con el estado correcto
            \Artisan::call('prestamos:actualizar-estados', ['--prestamo' => $operacion->prestamo_id]);

            DB::commit();

            \Log::info('Operación actualizada exitosamente', [
                'operacion_id' => $operacion->id,
                'prestamo_id' => $operacion->prestamo_id,
                'nuevo_monto' => $request->nuevo_abono,
            ]);

            // Verificar si debemos redirigir a un convenio
            $returnTo = $request->input('return_to');
            $convenioId = $request->input('convenio_id');

            if ($returnTo === 'convenio' && $convenioId) {
                \Log::info('Actualización exitosa, redirigiendo a convenio', [
                    'operacion_id' => $operacion->id,
                    'convenio_id' => $convenioId,
                    'prestamo_id' => $operacion->prestamo_id,
                ]);

                return redirect()->route('admin.convenios.show', $convenioId)
                    ->with('success', 'Operación actualizada exitosamente. Estados recalculados. ID: '.$operacion->id);
            }

            return redirect()
                ->route('admin.prestamos.show', $operacion->prestamo_id)
                ->with('success', 'Operación actualizada exitosamente. Estados recalculados. ID: '.$operacion->id);

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Error al actualizar la operación: '.$e->getMessage());
        }
    }

    /**
     * Regularizar moras por fecha de pago cuando se edita una operación
     * Si se cambia la fecha del pago o queda parcial, recalcular qué moras son válidas
     */
    private function regularizarMorasPorFechaEdicion($operacion, $request)
    {
        \Log::info("🔄 Iniciando regularización de moras por fecha de edición - Operación {$operacion->id}");

        // Verificar si se cambió la fecha de pago
        $fechaOriginal = $operacion->getOriginal('fecha');
        $fechaNueva = $request->fecha;
        $cambioFecha = $fechaOriginal != $fechaNueva;

        \Log::info("Fechas - Original: {$fechaOriginal}, Nueva: {$fechaNueva}, Cambió: ".($cambioFecha ? 'SÍ' : 'NO'));

        // Obtener las cuotas afectadas por esta operación
        $cuotasAfectadas = DB::table('operaciones_cuota')
            ->where('operacion_id', $operacion->id)
            ->pluck('cuota_id')
            ->unique();

        if ($cuotasAfectadas->isEmpty()) {
            \Log::info('No hay cuotas afectadas por esta operación para regularizar');

            return;
        }

        foreach ($cuotasAfectadas as $cuotaId) {
            $cuota = \App\Models\Cuota::find($cuotaId);
            if (! $cuota) {
                continue;
            }

            \Log::info("🎯 Regularizando moras para cuota #{$cuota->numero} (ID: {$cuotaId})");

            // Calcular total pagado de la cuota después de la edición
            $totalPagadoCuota = DB::table('operaciones_cuota')
                ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                ->where('operaciones_cuota.cuota_id', $cuotaId)
                ->where('operaciones.estado', '!=', 'anulado')
                ->sum('operaciones_cuota.monto_aplicado');

            $esPagoParcial = $totalPagadoCuota < $cuota->monto;
            $esPagoCompleto = $totalPagadoCuota >= $cuota->monto;

            \Log::info("Cuota {$cuotaId}: Total pagado {$totalPagadoCuota}/{$cuota->monto} - ".
                       ($esPagoCompleto ? 'COMPLETO' : 'PARCIAL'));

            // Si el pago es completo o se cambió la fecha, regularizar moras
            if ($esPagoCompleto || $cambioFecha) {
                $this->regularizarMorasPorPagoTardio($cuota, $fechaNueva, $esPagoCompleto, $operacion->id);
            }
        }
    }

    /**
     * Regularizar moras por pago tardío
     * Solo las moras entre fecha_vencimiento y fecha_pago son válidas
     */
    private function regularizarMorasPorPagoTardio($cuota, $fechaPago, $esPagoCompleto, $operacionId = null)
    {
        $fechaVencimiento = \Carbon\Carbon::parse($cuota->fecha_pago);
        $fechaPagoReal = \Carbon\Carbon::parse($fechaPago);

        \Log::info("📅 Cuota {$cuota->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pagado {$fechaPagoReal->format('Y-m-d')}");

        // Si se paga antes o en la fecha de vencimiento, no debe haber moras (EXCEPTO las pagadas)
        if ($fechaPagoReal->lessThanOrEqualTo($fechaVencimiento)) {
            \Log::info("⏰ Pago a tiempo - Regularizando todas las moras y creando abonos a favor para moras pagadas");

            // Obtener todas las moras de esta cuota
            $todasLasMoras = \App\Models\MoraCuota::where('cuota_id', $cuota->id)->get();

            $abonosFavorGenerados = 0;
            $totalAbonoFavor = 0;

            foreach ($todasLasMoras as $mora) {
                $tienePagoAplicado = ($mora->monto_pagado ?? 0) > 0;

                if ($tienePagoAplicado) {
                    // Mora tiene pago aplicado pero el pago fue a tiempo - crear abono a favor
                    \Log::info("💸 Mora {$mora->id} tiene pago S/{$mora->monto_pagado} pero pago fue a tiempo - Creando abono a favor");

                    $abonoFavor = \App\Models\AbonoMoraFavor::create([
                        'cuota_id' => $cuota->id,
                        'operacion_id' => $operacionId,
                        'monto_abonado' => $mora->monto_pagado,
                        'monto_utilizado' => 0,
                        'saldo_favor' => $mora->monto_pagado,
                        'comentario' => "Abono a favor generado por pago a tiempo - Mora ID {$mora->id} regularizada. Fecha de pago: {$fechaPagoReal->format('Y-m-d')}, Vencimiento: {$fechaVencimiento->format('Y-m-d')}",
                        'estado' => \App\Models\AbonoMoraFavor::ESTADO_ACTIVO,
                        'fecha_abono' => now(),
                    ]);

                    \Log::info("✅ Abono a favor creado: ID {$abonoFavor->id}, Monto S/{$mora->monto_pagado}");

                    $abonosFavorGenerados++;
                    $totalAbonoFavor += $mora->monto_pagado;

                    // Regularizar la mora y limpiar el monto pagado
                    $mora->update([
                        'estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA,
                        'monto_pagado' => 0,
                    ]);

                    // Eliminar relaciones de operacion_mora
                    DB::table('operacion_mora')->where('mora_cuota_id', $mora->id)->delete();
                } else {
                    // Mora sin pago aplicado - solo regularizar
                    if ($mora->estado != \App\Enums\MoraCuotaEstado::REGULARIZADA->value) {
                        $mora->update(['estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA]);
                    }
                }
            }

            \Log::info("✅ Pago a tiempo procesado - {$abonosFavorGenerados} abonos a favor creados por S/{$totalAbonoFavor}");

            return;
        }

        // Si se paga tardío, calcular días válidos de mora
        $diasMoraValidos = $fechaVencimiento->diffInDays($fechaPagoReal, false);
        if ($diasMoraValidos < 0) {
            $diasMoraValidos = 0;
        }

        // Limitar a máximo 7 días
        $diasMoraValidos = min($diasMoraValidos, 7);

        \Log::info("📊 Días de mora válidos: {$diasMoraValidos} (máximo 7)");

        if ($diasMoraValidos > 0) {
            // NOTA: Las moras ya fueron generadas por MoraService->generarMorasHastaFecha() en recalcularMorasCuota()
            // Aquí solo regularizamos las que están fuera del período válido y redistribuimos pagos

            // PASO 1: Generar las fechas de moras válidas que deberían existir
            $fechasMorasValidas = [];
            for ($i = 1; $i <= $diasMoraValidos; $i++) {
                $fechasMorasValidas[] = $fechaVencimiento->copy()->addDays($i)->format('Y-m-d');
            }

            \Log::info("📅 Fechas de moras válidas (período del vencimiento al pago): ".implode(', ', $fechasMorasValidas));

            // PASO 2: Obtener todas las moras existentes de esta cuota
            $todasLasMoras = \App\Models\MoraCuota::where('cuota_id', $cuota->id)
                ->orderBy('fecha')
                ->get();

            \Log::info("📋 Total moras existentes para cuota {$cuota->id}: ".$todasLasMoras->count());

            // PASO 3: Regularizar/Restaurar moras según validez
            $morasValidas = 0;
            $morasRegularizadas = 0;
            $morasRestauradas = 0;

            foreach ($todasLasMoras as $mora) {
                $fechaMora = \Carbon\Carbon::parse($mora->fecha);
                $fechaMoraStr = $fechaMora->format('Y-m-d');

                // Verificar si esta mora debe estar válida
                $debeEstarValida = in_array($fechaMoraStr, $fechasMorasValidas);
                $tienePagoAplicado = ($mora->monto_pagado ?? 0) > 0;

                if ($debeEstarValida) {
                    // Esta mora DEBE estar activa (válida)
                    if ($mora->estado == \App\Enums\MoraCuotaEstado::REGULARIZADA->value) {
                        // Estaba regularizada pero ahora debe estar PENDIENTE
                        $mora->update(['estado' => \App\Enums\MoraCuotaEstado::PENDIENTE, 'monto_pagado' => 0]);
                        $morasRestauradas++;
                        \Log::info("🔄 Mora {$mora->id} ({$fechaMoraStr}) RESTAURADA a PENDIENTE (era regularizada)");
                    }
                    $morasValidas++;
                } else {
                    // Esta mora NO debe estar activa - regularizarla
                    if ($tienePagoAplicado) {
                        // Tiene pago pero ya no es válida - crear abono a favor
                        \Log::info("💸 Mora {$mora->id} ({$fechaMoraStr}) tiene pago S/{$mora->monto_pagado} pero ya no es válida - Creando abono a favor");

                        $abonoFavor = \App\Models\AbonoMoraFavor::create([
                            'cuota_id' => $cuota->id,
                            'operacion_id' => null,
                            'monto_abonado' => $mora->monto_pagado,
                            'monto_utilizado' => 0,
                            'saldo_favor' => $mora->monto_pagado,
                            'comentario' => "Abono a favor generado por regularización de mora ID {$mora->id} - Fecha de pago cambiada a {$fechaPagoReal->format('Y-m-d')}",
                            'estado' => \App\Models\AbonoMoraFavor::ESTADO_ACTIVO,
                            'fecha_abono' => now(),
                        ]);

                        \Log::info("✅ Abono a favor creado: ID {$abonoFavor->id}, Monto S/{$mora->monto_pagado}");

                        $mora->update([
                            'estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA,
                            'monto_pagado' => 0,
                        ]);

                        DB::table('operacion_mora')->where('mora_cuota_id', $mora->id)->delete();
                        $morasRegularizadas++;
                    } elseif ($mora->estado != \App\Enums\MoraCuotaEstado::REGULARIZADA->value) {
                        $mora->update(['estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA]);
                        $morasRegularizadas++;
                        \Log::info("🔄 Mora {$mora->id} ({$fechaMoraStr}) regularizada (fuera de período válido)");
                    }
                }
            }

            \Log::info("📊 Resultado regularización: {$morasValidas} válidas, {$morasRestauradas} restauradas, {$morasRegularizadas} regularizadas");

            // PASO 4: SIEMPRE redistribuir pagos de mora después de regularizar
            // Esto es necesario porque MoraService puede haber generado nuevas moras
            \Log::info("🔄 Redistribuyendo pagos de mora entre moras válidas");

            // Obtener el total pagado en moras para esta cuota desde operacion_mora
            $operacionesMora = DB::table('operacion_mora')
                ->join('mora_cuota', 'operacion_mora.mora_cuota_id', '=', 'mora_cuota.id')
                ->join('operaciones', 'operacion_mora.operacion_id', '=', 'operaciones.id')
                ->where('mora_cuota.cuota_id', $cuota->id)
                ->where('operaciones.estado', '!=', 'anulado')
                ->get();

            // Calcular total pagado en moras
            $totalPagadoMoras = $operacionesMora->sum(function($registro) {
                $operacion = \App\Models\Operacion::find($registro->operacion_id);
                return $operacion ? $operacion->abono : 0;
            });

            \Log::info("💰 Total pagado en moras (desde operacion_mora): S/{$totalPagadoMoras}");

            if ($totalPagadoMoras > 0) {
                // Resetear todos los montos pagados de moras válidas a 0
                $morasValidasIds = \App\Models\MoraCuota::where('cuota_id', $cuota->id)
                    ->whereIn('fecha', $fechasMorasValidas)
                    ->pluck('id');

                \App\Models\MoraCuota::whereIn('id', $morasValidasIds)
                    ->update(['monto_pagado' => 0]);

                // Redistribuir el pago entre las moras válidas ordenadas por fecha
                $morasValidasOrdenadas = \App\Models\MoraCuota::where('cuota_id', $cuota->id)
                    ->whereIn('fecha', $fechasMorasValidas)
                    ->orderBy('fecha')
                    ->get();

                \Log::info("📋 Moras válidas encontradas para redistribución: {$morasValidasOrdenadas->count()}");

                $montoRestante = $totalPagadoMoras;
                $morasPagadasCount = 0;

                foreach ($morasValidasOrdenadas as $mora) {
                    if ($montoRestante <= 0.01) {
                        // Asegurar que esté en PENDIENTE si no tiene pago
                        if ($mora->estado != \App\Enums\MoraCuotaEstado::PENDIENTE->value) {
                            $mora->update(['estado' => \App\Enums\MoraCuotaEstado::PENDIENTE]);
                        }
                        continue;
                    }

                    $montoAplicar = min($montoRestante, $mora->monto);
                    $mora->monto_pagado = $montoAplicar;

                    if ($montoAplicar >= $mora->monto) {
                        $mora->estado = \App\Enums\MoraCuotaEstado::PAGADO;
                    } elseif ($montoAplicar > 0) {
                        $mora->estado = \App\Enums\MoraCuotaEstado::PARCIAL;
                    } else {
                        $mora->estado = \App\Enums\MoraCuotaEstado::PENDIENTE;
                    }

                    $mora->save();
                    $montoRestante -= $montoAplicar;
                    $morasPagadasCount++;

                    \Log::info("💵 Mora {$mora->id} ({$mora->fecha}) redistribuida: S/{$montoAplicar} aplicados (estado: {$mora->estado})");
                }

                \Log::info("✅ {$morasPagadasCount} moras con pagos redistribuidos. Sobrante: S/{$montoRestante}");
            } else {
                \Log::info("ℹ️ No hay pagos de mora para redistribuir");
            }
        } else {
            \Log::info("⚠️ No hay días de mora válidos para cuota {$cuota->id}");
        }
    }

    /**
     * Show the form for canceling an operation
     */
    public function anular(Request $request, $operacion_id)
    {
        $operacion = Operacion::with([
            'cuotas',
            'morasCuota',
            'operacionesRelacionadas',
            'cliente.persona',
            'prestamo',
            'metodoDePago',
            'user',
        ])->findOrFail($operacion_id);

        // Check if already cancelled
        if ($operacion->estado === 'anulado') {
            return redirect()->to(url('/admin/prestamos/'.$operacion->prestamo->id))
                ->with('warning', 'La operación ya está anulada.');
        }

        // Obtener parámetros de retorno para convenio
        $returnTo = $request->input('return_to');
        $convenioId = $request->input('convenio_id');

        return view('admin.Operaciones.anular-confirmacion', compact('operacion', 'returnTo', 'convenioId'));
    }

    /**
     * Process the actual cancellation after confirmation
     */
    public function procesarAnulacion(Request $request, $operacion_id)
    {
        $request->validate([
            'justificacion' => 'required|string|min:10|max:500',
            'confirmacion' => 'required|accepted',
        ], [
            'justificacion.required' => 'La justificación es obligatoria.',
            'justificacion.min' => 'La justificación debe tener al menos 10 caracteres.',
            'justificacion.max' => 'La justificación no puede exceder 500 caracteres.',
            'confirmacion.accepted' => 'Debe confirmar que entiende las consecuencias de la anulación.',
        ]);

        try {
            $operacion = Operacion::with(['cuotas', 'morasCuota', 'operacionesRelacionadas', 'prestamo'])->findOrFail($operacion_id);

            // Check if already cancelled (double-check)
            if ($operacion->estado === 'anulado') {
                return redirect()->route('admin.prestamos.show', $operacion->prestamo->id)
                    ->with('warning', 'La operación ya está anulada.');
            }

            // Usar el servicio centralizado de anulación que se encarga de:
            // - Eliminar relaciones en operaciones_cuota y operacion_mora (detach)
            // - Recalcular estados de cuotas y moras
            // - Actualizar estado del préstamo
            // - Marcar la operación como anulada
            $estadoPrestamoService = new \App\Services\EstadoPrestamoService();
            $resultado = $estadoPrestamoService->anularOperacion(
                $operacion,
                $request->justificacion,
                auth()->id()
            );

            // Manejo especial: Revertir cuotas de convenio si aplica
            if ($operacion->tipo_operacion === 'PAGO_CONVENIO' && $operacion->comentario) {
                \Log::info("🔄 Iniciando reversión de PAGO_CONVENIO - Operación: {$operacion->id}");

                // Buscar convenio sin restricción de estado
                $convenio = \App\Models\Convenio::where('prestamo_id', $operacion->prestamo_id)->first();

                if (!$convenio) {
                    \Log::warning("⚠️ No se encontró convenio para préstamo {$operacion->prestamo_id}");
                } else {
                    \Log::info("✅ Convenio encontrado - ID: {$convenio->id}, Estado: {$convenio->estado->label()}");

                    // Intentar extraer número de cuota del comentario
                    preg_match('/cuota #(\d+)/', $operacion->comentario, $matches);

                    if (isset($matches[1])) {
                        // CASO 1: Operación HIJA (tiene número de cuota en el comentario)
                        $numeroCuota = $matches[1];
                        \Log::info("📋 Operación HIJA detectada - Cuota #{$numeroCuota}");

                        $this->revertirCuotaConvenio($convenio, $numeroCuota, $operacion);

                        // Nota: Esta operación ya fue marcada como 'anulado' por EstadoPrestamoService
                        // No necesitamos marcarla de nuevo aquí

                    } else {
                        // CASO 2: Operación PADRE (no tiene número de cuota)
                        \Log::info("📦 Operación PADRE detectada - Procesando operaciones hijas");

                        // Verificar si tiene operaciones hijas
                        $operacionesHijas = $operacion->operacionesRelacionadas()
                            ->where('tipo_operacion', 'PAGO_CONVENIO')
                            ->get();

                        if ($operacionesHijas->count() > 0) {
                            \Log::info("🔍 Encontradas {$operacionesHijas->count()} operaciones hijas para revertir");

                            // Procesar cada operación hija manualmente (no usar recursión genérica)
                            foreach ($operacionesHijas as $opHija) {
                                if ($opHija->estado !== 'anulado') {
                                    // Extraer número de cuota de la operación hija
                                    preg_match('/cuota #(\d+)/', $opHija->comentario, $matchesHija);
                                    if (isset($matchesHija[1])) {
                                        $this->revertirCuotaConvenio($convenio, $matchesHija[1], $opHija);

                                        // Marcar operación hija como anulada para evitar doble procesamiento
                                        $opHija->update([
                                            'estado' => 'anulado',
                                            'anulado_por' => auth()->id(),
                                            'anulado_en' => now(),
                                            'justificacion_anulacion' => $request->justificacion ?? 'Anulación automática por operación principal',
                                        ]);

                                        \Log::info("✅ Operación hija {$opHija->id} marcada como anulada");
                                    }
                                }
                            }
                        } else {
                            \Log::warning("⚠️ Operación padre sin operaciones hijas - Revirtiendo cuotas directamente");

                            // CASO 3: Operación PADRE sin hijas - Revertir por monto
                            // Esto pasa cuando se anularon todas las operaciones o en sistemas legacy
                            $this->revertirCuotasConvenioPorMonto($convenio, $operacion);
                        }
                    }

                    // REVERTIR ABONOS A FAVOR: Anular abonos a favor generados por esta operación
                    $this->revertirAbonosFavorConvenio($operacion);
                }
            }

            // Manejo especial: Revertir moras de convenio si es pago directo de mora
            if ($operacion->tipo_operacion === 'PAGO_MORA_CONVENIO' && $operacion->comentario) {
                // Extraer ID de la mora del comentario (formato: "Pago mora #123 de cuota...")
                preg_match('/mora #(\d+)/', $operacion->comentario, $matchesMora);
                if (isset($matchesMora[1])) {
                    $moraId = $matchesMora[1];
                    $mora = \App\Models\MoraConvenio::find($moraId);

                    if ($mora) {
                        $nuevoMontoPagado = max(0, $mora->monto_pagado - $operacion->abono);
                        $nuevoEstado = $nuevoMontoPagado == 0
                            ? 'pendiente'
                            : ($nuevoMontoPagado >= $mora->monto
                                ? 'pagado'
                                : 'parcial');

                        $mora->update([
                            'monto_pagado' => $nuevoMontoPagado,
                            'estado' => $nuevoEstado,
                        ]);

                        \Log::info('Mora de convenio revertida', [
                            'mora_id' => $mora->id,
                            'monto_revertido' => $operacion->abono,
                            'nuevo_monto_pagado' => $nuevoMontoPagado,
                            'nuevo_estado' => $nuevoEstado,
                        ]);

                        // IMPORTANTE: Recalcular moras de la cuota después de anular
                        $this->recalcularMorasConvenio($mora->cuotaConvenio);
                    }
                }
            }

            // Anular operaciones relacionadas (hijas) recursivamente
            foreach ($operacion->operacionesRelacionadas as $relacionada) {
                if ($relacionada->estado !== 'anulado') {
                    $this->anularOperacionRecursiva($relacionada);
                }
            }

            // Get the loan ID to ensure proper redirect
            $prestamoId = $operacion->prestamo->id;

            // Verificar si debemos redirigir a un convenio
            $returnTo = $request->input('return_to');
            $convenioId = $request->input('convenio_id');

            if ($returnTo === 'convenio' && $convenioId) {
                \Log::info('Anulación exitosa, redirigiendo a convenio', [
                    'operacion_id' => $operacion->id,
                    'convenio_id' => $convenioId,
                    'prestamo_id' => $prestamoId,
                    'user_id' => auth()->id(),
                ]);

                return redirect()->route('admin.convenios.show', $convenioId)
                    ->with('success', 'Operación #'.$operacion->id.' anulada correctamente. Todos los pagos y estados han sido revertidos.');
            }

            \Log::info('Anulación exitosa, redirigiendo a préstamo', [
                'operacion_id' => $operacion->id,
                'prestamo_id' => $prestamoId,
                'redirect_url' => route('admin.prestamos.show', $prestamoId),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('admin.prestamos.show', $prestamoId)
                ->with('success', 'Operación #'.$operacion->id.' anulada correctamente. Todos los pagos y estados han sido revertidos.');

        } catch (\Exception $e) {
            // El servicio ya maneja el rollback automáticamente en caso de error

            // Try to get loan ID even in error case
            $prestamoId = $operacion->prestamo_id ?? null;
            if (!$prestamoId) {
                try {
                    $operacionTemp = Operacion::find($operacion_id);
                    $prestamoId = $operacionTemp ? $operacionTemp->prestamo_id : null;
                } catch (\Exception $ex) {
                    // If we can't get the loan ID, we'll redirect back
                }
            }

            \Log::error('Error al anular operación: '.$e->getMessage(), [
                'operacion_id' => $operacion_id,
                'prestamo_id' => $prestamoId,
                'user_id' => auth()->id(),
                'justificacion' => $request->justificacion,
                'trace' => $e->getTraceAsString(),
            ]);

            if ($prestamoId) {
                return redirect()->route('admin.prestamos.show', $prestamoId)
                    ->with('error', 'Error al anular la operación: '.$e->getMessage());
            } else {
                return redirect()->back()
                    ->with('error', 'Error al anular la operación: '.$e->getMessage());
            }
        }
    }

    /**
     * Determine cuota state based on payment amount
     */
    private function determinarEstadoCuota($cuota, $montoPagado)
    {
        if ($montoPagado == 0) {
            // Check if overdue
            return Carbon::now()->greaterThan($cuota->fecha_pago)
                ? \App\Enums\CuotaEstado::VENCIDO
                : \App\Enums\CuotaEstado::PENDIENTE;
        } elseif ($montoPagado >= $cuota->monto) {
            return \App\Enums\CuotaEstado::PAGADO;
        } else {
            return \App\Enums\CuotaEstado::PARCIAL;
        }
    }

    /**
     * Determine mora state based on payment amount
     */
    private function determinarEstadoMora($mora, $montoPagado)
    {
        if ($montoPagado == 0) {
            return \App\Enums\MoraCuotaEstado::PENDIENTE;
        } elseif ($montoPagado >= $mora->monto) {
            return \App\Enums\MoraCuotaEstado::PAGADO;
        } else {
            return \App\Enums\MoraCuotaEstado::PARCIAL;
        }
    }

    /**
     * Calculate how much of an operation was applied to a specific mora
     */
    private function calcularMontoAplicadoMora($operacion, $mora)
    {
        // This is a simplified calculation - you might need to adjust based on your business logic
        // For now, we assume the entire operation amount was applied to the mora if it was associated
        // In a more complex scenario, you'd need to track partial applications
        return min($operacion->abono, $mora->monto_pagado);
    }

    /**
     * Recursively cancel related operations
     */
    private function anularOperacionRecursiva($operacion)
    {
        // Mark as cancelled
        $operacion->update([
            'estado' => 'anulado',
            'anulado_por' => auth()->id(),
            'anulado_en' => now(),
            'justificacion_anulacion' => 'Anulación automática por operación principal',
        ]);

        // Reverse effects on cuotas and moras
        foreach ($operacion->cuotas as $cuota) {
            $nuevoMontoPagado = max(0, $cuota->monto_pagado - $operacion->abono);
            $nuevoEstado = $this->determinarEstadoCuota($cuota, $nuevoMontoPagado);

            $cuota->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado' => $nuevoEstado,
            ]);
        }

        foreach ($operacion->morasCuota as $mora) {
            $montoAplicado = $this->calcularMontoAplicadoMora($operacion, $mora);
            $nuevoMontoPagado = max(0, $mora->monto_pagado - $montoAplicado);
            $nuevoEstado = $this->determinarEstadoMora($mora, $nuevoMontoPagado);

            $mora->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado' => $nuevoEstado,
            ]);
        }

        // Recalcular moras de cuotas afectadas después de la anulación recursiva
        $cuotasAfectadas = $operacion->cuotas;

        // También incluir cuotas de moras que fueron revertidas
        foreach ($operacion->morasCuota as $mora) {
            if ($mora->cuota && !$cuotasAfectadas->contains($mora->cuota)) {
                $cuotasAfectadas->push($mora->cuota);
            }
        }

        $moraService = new \App\Services\MoraService();
        foreach ($cuotasAfectadas as $cuota) {
            try {
                $resultadosRecalculo = $moraService->recalcularMorasDespuesAnulacion($cuota);

                if ($resultadosRecalculo['abonos_favor_creados'] > 0) {
                    \Log::info("💰 Abono a favor creado (recursivo) en cuota #{$cuota->numero}: S/{$resultadosRecalculo['monto_total_abonos_favor']}");
                }

                if ($resultadosRecalculo['moras_nuevas_generadas'] > 0) {
                    \Log::info("🔥 Moras adicionales generadas (recursivo) en cuota #{$cuota->numero}: {$resultadosRecalculo['moras_nuevas_generadas']}");
                }
            } catch (\Exception $e) {
                \Log::error("Error recalculando moras (recursivo) de cuota {$cuota->id}: ".$e->getMessage());
            }
        }
    }

    /**
     * Update loan status after operation changes
     */
    private function actualizarEstadoPrestamo($prestamo)
    {
        // This should call your existing loan status update logic
        // For now, we'll use a simple recalculation
        $cuotasPendientes = $prestamo->cuotas()->whereIn('estado', [
            \App\Enums\CuotaEstado::PENDIENTE,
            \App\Enums\CuotaEstado::PARCIAL,
            \App\Enums\CuotaEstado::VENCIDO,
        ])->count();

        if ($cuotasPendientes == 0) {
            $prestamo->update(['estado' => 'completado']);
        } elseif ($cuotasPendientes == $prestamo->cuotas()->count()) {
            $prestamo->update(['estado' => 'activo']);
        } else {
            $prestamo->update(['estado' => 'activo']); // or 'en_pago' based on your business logic
        }
    }

    /**
     * Show the history of an operation
     */
    public function historial($operacion_id)
    {
        $operacion = Operacion::with([
            'prestamo',
            'cliente.persona',
            'user',
            'metodoDePago',
            'editadoPor',
            'anuladoPor',
        ])->findOrFail($operacion_id);

        return view('admin.Operaciones.historial', compact('operacion'));
    }

    /**
     * Calcular moras dinámicamente para la edición basándose en la fecha de pago
     */
    public function calcularMorasEdicion(Request $request, $operacion_id)
    {
        $request->validate([
            'fecha_pago' => 'required|date',
            'cuotas_ids' => 'required|array',
            'cuotas_ids.*' => 'exists:cuotas,id',
        ]);

        $operacion = Operacion::findOrFail($operacion_id);
        $fechaPago = \Carbon\Carbon::parse($request->fecha_pago);
        $cuotasIds = $request->cuotas_ids;

        $morasCalculadas = [];

        foreach ($cuotasIds as $cuotaId) {
            $cuota = \App\Models\Cuota::find($cuotaId);
            if (! $cuota) {
                continue;
            }

            $fechaVencimiento = \Carbon\Carbon::parse($cuota->fecha_pago);

            // Calcular días de mora
            $diasMora = 0;
            if ($fechaPago->greaterThan($fechaVencimiento)) {
                $diasMora = $fechaVencimiento->diffInDays($fechaPago);
                $diasMora = min($diasMora, 7); // Máximo 7 días
            }

            // Generar estructura de moras
            $morasCuota = [];
            for ($i = 1; $i <= $diasMora; $i++) {
                $fechaMora = $fechaVencimiento->copy()->addDays($i);

                // Buscar si ya existe esta mora en la BD
                $moraExistente = \App\Models\MoraCuota::where('cuota_id', $cuotaId)
                    ->whereDate('fecha', $fechaMora)
                    ->first();

                $morasCuota[] = [
                    'id' => $moraExistente ? $moraExistente->id : 'nueva_'.$cuotaId.'_'.$i,
                    'cuota_id' => $cuotaId,
                    'fecha' => $fechaMora->format('Y-m-d'),
                    'fecha_display' => $fechaMora->format('d/m'),
                    'monto' => $moraExistente ? $moraExistente->monto : 5.00, // Monto por defecto
                    'monto_pagado' => $moraExistente ? ($moraExistente->monto_pagado ?? 0) : 0,
                    'saldo_pendiente' => $moraExistente ? ($moraExistente->monto - ($moraExistente->monto_pagado ?? 0)) : 5.00,
                    'estado' => $moraExistente ? $moraExistente->estado : 0,
                    'estado_text' => $moraExistente ? ($moraExistente->estado == 0 ? 'Pendiente' : 'Parcial') : 'Pendiente',
                    'es_disponible' => true,
                    'monto_aplicado' => 0,
                ];
            }

            $morasCalculadas[$cuotaId] = [
                'cuota_numero' => $cuota->numero,
                'fecha_vencimiento' => $fechaVencimiento->format('d/m/Y'),
                'moras' => $morasCuota,
                'total_moras' => $diasMora,
            ];
        }

        return response()->json([
            'success' => true,
            'moras_calculadas' => $morasCalculadas,
            'fecha_pago' => $fechaPago->format('d/m/Y'),
            'total_dias_mora' => array_sum(array_column($morasCalculadas, 'total_moras')),
        ]);
    }

    /**
     * MODO AUTOMÁTICO: Redistribución completa SOLO de cuotas (moras quedan igual)
     */
    private function aplicarModoAutomatico($operacion_id, $operacion, $montoNuevo, $fechaNueva, $request)
    {
        \Log::info('🤖 APLICANDO MODO AUTOMÁTICO - Redistribución completa DE CUOTAS');

        // ANULAR operaciones hijas anteriores si existen
        // Esto evita duplicación cuando se edita una operación múltiples veces
        $operacionesHijasAnteriores = \App\Models\Operacion::where('operacion_general_id', $operacion_id)
            ->where('estado', '!=', 'anulado')
            ->get();

        if ($operacionesHijasAnteriores->isNotEmpty()) {
            \Log::info("🔄 Anulando {$operacionesHijasAnteriores->count()} operaciones hijas anteriores antes de aplicar nueva edición");

            foreach ($operacionesHijasAnteriores as $hijaAnterior) {
                $hijaAnterior->update([
                    'estado' => 'anulado',
                    'anulado_por' => auth()->id(),
                    'anulado_en' => now(),
                    'justificacion_anulacion' => 'Anulación automática por nueva edición de operación padre #' . $operacion_id,
                ]);

                \Log::info("✅ Operación hija #{$hijaAnterior->id} anulada");
            }
        }

        // GUARDAR cuotas originales ANTES del delete para manejar moras
        $cuotasOriginales = DB::table('operaciones_cuota')
            ->where('operacion_id', $operacion_id)
            ->pluck('cuota_id')
            ->toArray();

        // Encontrar cuota base
        $cuotaBaseId = null;
        foreach ($request->cuotas as $cuotaId => $monto) {
            if ($monto > 0) {
                $cuotaBaseId = $cuotaId;
                break;
            }
        }

        if (! $cuotaBaseId) {
            throw new \Exception('Debe especificar al menos una cuota con monto mayor que cero');
        }

        // LIMPIAR Y REDISTRIBUIR CUOTAS COMPLETAMENTE
        DB::table('operaciones_cuota')->where('operacion_id', $operacion_id)->delete();

        // Redistribuir solo cuotas
        $this->aplicarDistribucionSecuencial($operacion_id, $cuotaBaseId, $montoNuevo, $operacion->prestamo_id, $fechaNueva);

        // REGULARIZAR MORAS solo si cambió fecha Y SOLO de cuotas ORIGINALES
        $fechaOriginal = $operacion->getOriginal('fecha');
        $cambioFecha = $fechaNueva !== $fechaOriginal;

        if ($cambioFecha && count($cuotasOriginales) > 0) {
            \Log::info('🕒 Regularizando moras SOLO por cambio de fecha - Cuotas originales: '.count($cuotasOriginales));

            foreach ($cuotasOriginales as $cuotaId) {
                $cuota = \App\Models\Cuota::find($cuotaId);
                if ($cuota) {
                    \Log::info("🕒 Regularizando moras de cuota original #{$cuota->numero}");
                    $this->regularizarMorasSegunFechaPago($cuota, $fechaNueva);
                } else {
                    \Log::warning("⚠️ Cuota original #{$cuotaId} no encontrada");
                }
            }
        } else {
            \Log::info('⏭️ No regularizando moras - No cambió fecha o sin cuotas originales');
        }
    }

    /**
     * MODO MANUAL: Edición selectiva (preserva otras cuotas)
     */
    private function aplicarModoManual($operacion_id, $operacion, $request, $cambioFecha, $fechaNueva)
    {
        \Log::info('✋ APLICANDO MODO MANUAL - Edición selectiva');

        // ANULAR operaciones hijas anteriores si existen
        // Esto evita duplicación cuando se edita una operación múltiples veces
        $operacionesHijasAnteriores = \App\Models\Operacion::where('operacion_general_id', $operacion_id)
            ->where('estado', '!=', 'anulado')
            ->get();

        if ($operacionesHijasAnteriores->isNotEmpty()) {
            \Log::info("🔄 Anulando {$operacionesHijasAnteriores->count()} operaciones hijas anteriores antes de aplicar nueva edición");

            foreach ($operacionesHijasAnteriores as $hijaAnterior) {
                $hijaAnterior->update([
                    'estado' => 'anulado',
                    'anulado_por' => auth()->id(),
                    'anulado_en' => now(),
                    'justificacion_anulacion' => 'Anulación automática por nueva edición de operación padre #' . $operacion_id,
                ]);

                \Log::info("✅ Operación hija #{$hijaAnterior->id} anulada");
            }
        }

        // GUARDAR cuotas originales ANTES de cualquier cambio
        $cuotasOriginales = DB::table('operaciones_cuota')
            ->where('operacion_id', $operacion_id)
            ->pluck('cuota_id')
            ->toArray();

        // Obtener registros actuales
        $registrosActuales = DB::table('operaciones_cuota')
            ->where('operacion_id', $operacion_id)
            ->get()
            ->keyBy('cuota_id');

        $cuotasAfectadas = [];

        // Procesar cada cuota del request
        foreach ($request->cuotas as $cuotaId => $nuevoMonto) {
            $nuevoMonto = (float) $nuevoMonto;
            $registroExistente = $registrosActuales->get($cuotaId);

            if ($registroExistente) {
                $montoAnterior = (float) $registroExistente->monto_aplicado;

                if (abs($nuevoMonto - $montoAnterior) > 0.01) {
                    if ($nuevoMonto > 0) {
                        // Actualizar registro
                        DB::table('operaciones_cuota')
                            ->where('operacion_id', $operacion_id)
                            ->where('cuota_id', $cuotaId)
                            ->update([
                                'monto_aplicado' => $nuevoMonto,
                                'updated_at' => now(),
                            ]);

                        $cuotasAfectadas[] = $cuotaId;
                        \Log::info("✅ Cuota #{$cuotaId} actualizada: {$montoAnterior} → {$nuevoMonto}");
                    } else {
                        // Eliminar registro
                        DB::table('operaciones_cuota')
                            ->where('operacion_id', $operacion_id)
                            ->where('cuota_id', $cuotaId)
                            ->delete();

                        $cuotasAfectadas[] = $cuotaId;
                        \Log::info("🗑️ Cuota #{$cuotaId} eliminada");
                    }
                }
            } else {
                // Crear nuevo registro
                if ($nuevoMonto > 0) {
                    DB::table('operaciones_cuota')->insert([
                        'operacion_id' => $operacion_id,
                        'cuota_id' => $cuotaId,
                        'monto_aplicado' => $nuevoMonto,
                        'concepto' => 'pago_manual',
                        'aplicado_en' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $cuotasAfectadas[] = $cuotaId;
                    \Log::info("➕ Cuota #{$cuotaId} agregada: {$nuevoMonto}");
                }
            }
        }

        // ACTUALIZAR FECHA_PAGO_REAL Y RECALCULAR MORAS cuando cambió fecha
        // Procesar TODAS las cuotas afectadas (originales + nuevas)
        if ($cambioFecha) {
            // Obtener todas las cuotas que tienen monto aplicado en el request
            $cuotasAProcesar = array_keys(array_filter($request->cuotas, function($monto) {
                return (float)$monto > 0;
            }));

            if (count($cuotasAProcesar) > 0) {
                \Log::info('🕒 Actualizando fecha_pago_real y recalculando moras - Cuotas a procesar: '.count($cuotasAProcesar));

                foreach ($cuotasAProcesar as $cuotaId) {
                    $cuota = \App\Models\Cuota::find($cuotaId);
                    if ($cuota) {
                        // Actualizar fecha_pago_real con la NUEVA fecha de la operación
                        $cuota->update(['fecha_pago_real' => $fechaNueva]);
                        \Log::info("📅 Fecha_pago_real de cuota #{$cuota->numero} actualizada a {$fechaNueva}");

                        // Regularizar moras según la nueva fecha
                        \Log::info("🕒 Regularizando moras existentes de cuota #{$cuota->numero}");
                        $this->regularizarMorasSegunFechaPago($cuota, $fechaNueva);

                        // Recalcular/generar moras según la nueva fecha de pago
                        \Log::info("🔄 Recalculando/generando moras para cuota #{$cuota->numero}");
                        $this->recalcularMorasCuota($cuota, $fechaNueva);
                    } else {
                        \Log::warning("⚠️ Cuota #{$cuotaId} no encontrada");
                    }
                }
            }
        }

        // DETECTAR Y CREAR ABONO A FAVOR AUTOMÁTICAMENTE en modo manual
        // Verificar si alguna cuota fue sobrepagada y crear abono a favor automáticamente
        $this->crearAbonoFavorAutomaticoModoManual($operacion_id, $request, $operacion);
    }

    /**
     * Crear abono a favor automáticamente en modo manual cuando se detecta sobrepago
     */
    private function crearAbonoFavorAutomaticoModoManual($operacion_id, $request, $operacion)
    {
        foreach ($request->cuotas as $cuotaId => $montoAplicado) {
            $montoAplicado = (float) $montoAplicado;

            if ($montoAplicado > 0) {
                $cuota = \App\Models\Cuota::find($cuotaId);
                if ($cuota && $montoAplicado > $cuota->monto) {
                    $exceso = $montoAplicado - $cuota->monto;

                    \Log::info("💰 DETECTADO SOBREPAGO EN MODO MANUAL - Cuota #{$cuota->numero}: Aplicado S/ {$montoAplicado}, Monto cuota S/ {$cuota->monto}, Exceso S/ {$exceso}");

                    // Ajustar el monto en la tabla pivot a solo el monto de la cuota
                    DB::table('operaciones_cuota')
                        ->where('operacion_id', $operacion_id)
                        ->where('cuota_id', $cuotaId)
                        ->update(['monto_aplicado' => $cuota->monto]);

                    \Log::info("🔧 Ajustado monto en tabla pivot: Cuota #{$cuota->numero} de S/ {$montoAplicado} a S/ {$cuota->monto}");

                    // REDISTRIBUIR EXCESO A LA SIGUIENTE CUOTA automáticamente
                    \Log::info("🎯 REDISTRIBUYENDO EXCESO S/ {$exceso} a siguiente cuota disponible");
                    $this->redistribuirExceso($operacion_id, $cuotaId, $exceso, $operacion->prestamo_id, $request->fecha);
                }
            }
        }
    }

    /**
     * Redistribuir exceso desde modo manual a siguiente cuota disponible
     */
    private function redistribuirExceso($operacion_id, $cuotaActualId, $montoExceso, $prestamo_id, $fechaPago)
    {
        \Log::info("🔄 REDISTRIBUYENDO EXCESO desde cuota {$cuotaActualId}: S/ {$montoExceso}");

        // Buscar la siguiente cuota disponible DESPUÉS de la cuota actual
        $cuotaActual = \App\Models\Cuota::find($cuotaActualId);
        $siguienteCuota = \App\Models\Cuota::where('prestamo_id', $prestamo_id)
            ->whereIn('estado', [\App\Enums\CuotaEstado::PENDIENTE, \App\Enums\CuotaEstado::PARCIAL, \App\Enums\CuotaEstado::VENCIDO])
            ->whereRaw('(monto_pagado IS NULL OR monto_pagado < monto)')
            ->where('numero', '>', $cuotaActual->numero)
            ->orderBy('numero')
            ->first();

        if ($siguienteCuota) {
            \Log::info("📍 Siguiente cuota disponible encontrada: #{$siguienteCuota->numero} (ID: {$siguienteCuota->id})");

            // Usar distribución secuencial empezando desde la siguiente cuota
            $this->aplicarDistribucionSecuencial($operacion_id, $siguienteCuota->id, $montoExceso, $prestamo_id, $fechaPago);
        } else {
            \Log::info("💰 No hay siguiente cuota disponible - creando abono a favor por S/ {$montoExceso}");

            // Si no hay siguiente cuota, crear abono a favor
            $operacionAbonoFavor = \App\Models\Operacion::create([
                'fecha' => $fechaPago,
                'abono' => $montoExceso,
                'tipo_operacion' => 'Abono a favor',
                'estado' => 'completado',
                'prestamo_id' => $prestamo_id,
                'cliente_id' => \App\Models\Prestamo::find($prestamo_id)->cliente_id,
                'operacion_general_id' => $operacion_id,
                'metodo_pago_id' => \App\Models\Operacion::find($operacion_id)->metodo_pago_id,
                'user_id' => auth()->id(),
            ]);

            \Log::info("✅ Abono a favor creado: ID {$operacionAbonoFavor->id}, Monto S/ {$montoExceso}");
        }
    }

    /**
     * Aplicar distribución secuencial SOLO PARA CUOTAS (las moras NO se redistribuyen)
     */
    private function aplicarDistribucionSecuencial($operacion_id, $cuotaBaseId, $montoTotal, $prestamo_id, $fechaPago)
    {
        \Log::info("🎯 APLICANDO distribución secuencial SOLO CUOTAS: Monto S/ {$montoTotal}");

        // APLICAR DISTRIBUCIÓN SECUENCIAL (misma lógica que RegistrarPagoController)
        $montoRestante = (float) $montoTotal;
        $cuotaActualId = $cuotaBaseId;

        while ($montoRestante > 0.01) {
            // Buscar siguiente cuota disponible (igual que sistema de pagos)
            $cuota = null;

            if ($cuotaActualId) {
                // PRIORIDAD 1: Usar cuota específica
                $cuotaEspecifica = \App\Models\Cuota::where('id', $cuotaActualId)
                    ->where('prestamo_id', $prestamo_id)
                    ->whereIn('estado', [\App\Enums\CuotaEstado::PENDIENTE, \App\Enums\CuotaEstado::PARCIAL, \App\Enums\CuotaEstado::VENCIDO])
                    ->whereRaw('(monto_pagado IS NULL OR monto_pagado < monto)')
                    ->first();

                if ($cuotaEspecifica) {
                    $cuota = $cuotaEspecifica;
                    \Log::info("🎯 USANDO cuota específica #{$cuota->numero} (ID: {$cuota->id})");
                    $cuotaActualId = null; // Limpiar para próxima iteración
                } else {
                    \Log::info("⚠️ Cuota específica {$cuotaActualId} no disponible");
                    $cuotaActualId = null;
                }
            }

            if (! $cuota) {
                // PRIORIDAD 2: Lógica secuencial (igual que sistema de pagos)
                $cuota = \App\Models\Cuota::where('prestamo_id', $prestamo_id)
                    ->whereIn('estado', [\App\Enums\CuotaEstado::PENDIENTE, \App\Enums\CuotaEstado::PARCIAL, \App\Enums\CuotaEstado::VENCIDO])
                    ->whereRaw('(monto_pagado IS NULL OR monto_pagado < monto)')
                    ->orderBy('numero')
                    ->first();

                if ($cuota) {
                    \Log::info("📋 Usando lógica secuencial - Cuota #{$cuota->numero}");
                }
            }

            if (! $cuota) {
                \Log::info("❌ No hay más cuotas disponibles. Monto restante: S/ {$montoRestante}");
                break;
            }

            // CALCULAR SALDO PENDIENTE (excluyendo OTRAS operaciones)
            $pagadoPorOtras = DB::table('operaciones_cuota')
                ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                ->where('operaciones_cuota.cuota_id', $cuota->id)
                ->where('operaciones.id', '!=', $operacion_id)
                ->where('operaciones.estado', '!=', 'anulado')
                ->sum('operaciones_cuota.monto_aplicado');

            $saldoPendiente = max(0, $cuota->monto - $pagadoPorOtras);

            if ($saldoPendiente <= 0) {
                \Log::info("⏭️ Cuota #{$cuota->numero} ya está completa. Continuando...");

                continue;
            }

            // APLICAR MONTO (igual que sistema de pagos)
            $montoAAplicar = min($montoRestante, $saldoPendiente);

            // CREAR REGISTRO
            DB::table('operaciones_cuota')->insert([
                'operacion_id' => $operacion_id,
                'cuota_id' => $cuota->id,
                'monto_aplicado' => $montoAAplicar,
                'concepto' => 'pago_automatico',
                'aplicado_en' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $montoRestante -= $montoAAplicar;

            \Log::info("✅ Cuota #{$cuota->numero}: S/ {$montoAAplicar} aplicado, restante: S/ {$montoRestante}");

            // IMPORTANTE: NO regularizar moras automáticamente aquí
            // Las moras se manejan por separado y solo de cuotas originales
        }

        // Si sobra dinero después de completar todas las cuotas, crear abono a favor automáticamente
        if ($montoRestante > 0.01) {
            \Log::info("💰 CREANDO ABONO A FAVOR AUTOMÁTICO - Sobrante: S/ {$montoRestante}");

            // Buscar la primera cuota del préstamo para asignar el abono a favor
            $primeraCuota = \App\Models\Cuota::where('prestamo_id', $prestamo_id)
                ->orderBy('numero')
                ->first();

            if ($primeraCuota) {
                // Obtener datos necesarios de forma segura
                $prestamo = \App\Models\Prestamo::find($prestamo_id);
                $operacionOriginal = \App\Models\Operacion::find($operacion_id);

                if ($prestamo && $operacionOriginal) {
                    // Crear operación relacionada de tipo "Abono a favor"
                    $operacionAbonoFavor = \App\Models\Operacion::create([
                        'fecha' => $fechaPago,
                        'abono' => $montoRestante,
                        'tipo_operacion' => 'Abono a favor',
                        'estado' => 'completado',
                        'prestamo_id' => $prestamo_id,
                        'cliente_id' => $prestamo->cliente_id,
                        'operacion_general_id' => $operacion_id,
                        'metodo_pago_id' => $operacionOriginal->metodo_pago_id,
                        'user_id' => auth()->id(),
                    ]);

                    \Log::info("✅ Abono a favor creado automáticamente: ID {$operacionAbonoFavor->id}, Monto S/ {$montoRestante}, Cuota #{$primeraCuota->numero}");
                } else {
                    \Log::warning('⚠️ No se pudo crear abono a favor - datos insuficientes (prestamo o operacion original null)');
                }
            } else {
                \Log::warning('⚠️ No se pudo crear abono a favor - no se encontró primera cuota del préstamo');
            }
        }

        \Log::info('✅ Distribución secuencial de CUOTAS completada');
    }

    /**
     * Regularizar moras según fecha de pago (igual que sistema de pagos normal)
     */
    private function regularizarMorasSegunFechaPago($cuota, $fechaPago)
    {
        try {
            $fechaVencimiento = \Carbon\Carbon::parse($cuota->fecha_pago)->startOfDay();
            $fechaPagoReal = \Carbon\Carbon::parse($fechaPago)->startOfDay();

            \Log::info("🕒 Regularizando moras cuota #{$cuota->numero}: Vence {$fechaVencimiento->format('Y-m-d')}, Pago {$fechaPagoReal->format('Y-m-d')}");

            if ($fechaPagoReal->lte($fechaVencimiento)) {
                // CASO 1: Pago a tiempo - Regularizar SOLO moras sin pagos aplicados
                // NO regularizar moras que tienen monto_pagado > 0 (fueron pagadas con abono a favor o dinero)
                $morasParaRegularizar = $cuota->moras()
                    ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE, \App\Enums\MoraCuotaEstado::PARCIAL])
                    ->where(function($query) {
                        $query->where('monto_pagado', 0)
                              ->orWhereNull('monto_pagado');
                    })
                    ->get();

                if ($morasParaRegularizar->count() > 0) {
                    \Log::info("✅ Regularizando {$morasParaRegularizar->count()} moras sin pagos aplicados (pago a tiempo)");

                    foreach ($morasParaRegularizar as $mora) {
                        $mora->update(['estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA]);
                        \Log::info("🔄 Mora {$mora->id} regularizada (sin pagos aplicados)");
                    }
                }

                // Mantener las moras PAGADAS (con monto_pagado > 0) como PAGADAS
                $morasPagadas = $cuota->moras()
                    ->where('monto_pagado', '>', 0)
                    ->whereIn('estado', [\App\Enums\MoraCuotaEstado::PENDIENTE, \App\Enums\MoraCuotaEstado::PARCIAL])
                    ->get();

                if ($morasPagadas->count() > 0) {
                    \Log::info("💰 Manteniendo {$morasPagadas->count()} moras con pagos aplicados como PAGADAS");

                    foreach ($morasPagadas as $mora) {
                        $nuevoEstado = $mora->monto_pagado >= $mora->monto
                            ? \App\Enums\MoraCuotaEstado::PAGADO
                            : \App\Enums\MoraCuotaEstado::PARCIAL;

                        $mora->update(['estado' => $nuevoEstado]);
                        \Log::info("💵 Mora {$mora->id} marcada como {$nuevoEstado->name} (pagado S/{$mora->monto_pagado} de S/{$mora->monto})");
                    }
                }
            } else {
                // CASO 2: Pago tardío - Regularizar moras POSTERIORES al pago (pero NO las pagadas)
                $todasLasMoras = $cuota->moras()->orderBy('fecha')->get();
                $morasRegularizadas = 0;
                $morasPagadasMantenidas = 0;

                foreach ($todasLasMoras as $mora) {
                    $fechaMora = \Carbon\Carbon::parse($mora->fecha)->startOfDay();

                    // Regularizar moras posteriores a la fecha de pago
                    if ($fechaMora->gt($fechaPagoReal)) {
                        // Solo regularizar si NO tiene monto pagado (no fue pagada con abono a favor)
                        if (in_array($mora->estado->value, [\App\Enums\MoraCuotaEstado::PENDIENTE->value, \App\Enums\MoraCuotaEstado::PARCIAL->value])) {
                            if (($mora->monto_pagado ?? 0) > 0) {
                                // Mantener como PAGADA porque tiene monto aplicado
                                $nuevoEstado = $mora->monto_pagado >= $mora->monto
                                    ? \App\Enums\MoraCuotaEstado::PAGADO
                                    : \App\Enums\MoraCuotaEstado::PARCIAL;
                                $mora->update(['estado' => $nuevoEstado]);
                                \Log::info("💵 Mora {$mora->id} (fecha {$mora->fecha}) mantenida como {$nuevoEstado->name} - tiene pago aplicado S/{$mora->monto_pagado}");
                                $morasPagadasMantenidas++;
                            } else {
                                // Regularizar porque no tiene pagos aplicados
                                $mora->update(['estado' => \App\Enums\MoraCuotaEstado::REGULARIZADA]);
                                \Log::info("🔄 Mora {$mora->id} (fecha {$mora->fecha}) regularizada - posterior al pago sin pagos aplicados");
                                $morasRegularizadas++;
                            }
                        }
                    } else {
                        \Log::info("✅ Mora {$mora->id} (fecha {$mora->fecha}) válida - anterior/igual al pago");
                    }
                }

                \Log::info("📊 Total moras regularizadas: {$morasRegularizadas}, Moras pagadas mantenidas: {$morasPagadasMantenidas}");
            }

        } catch (\Exception $e) {
            \Log::error("❌ Error regularizando moras cuota {$cuota->id}: ".$e->getMessage());
        }
    }

    /**
     * Recalcular moras de una cuota según una nueva fecha de pago
     */
    private function recalcularMorasCuota($cuota, $fechaPago)
    {
        try {
            $fechaVencimiento = \Carbon\Carbon::parse($cuota->fecha_pago)->startOfDay();
            $fechaPagoReal = \Carbon\Carbon::parse($fechaPago)->startOfDay();

            \Log::info("🔄 Recalculando moras cuota #{$cuota->numero}: Vence {$fechaVencimiento->format('Y-m-d')}, Pago {$fechaPagoReal->format('Y-m-d')}");

            if ($fechaPagoReal->lte($fechaVencimiento)) {
                // Pago a tiempo - NO hay moras que calcular
                \Log::info("✅ Pago a tiempo - No se generan moras");
                return;
            }

            // Pago tardío - Calcular días de mora
            $diasMora = $fechaVencimiento->diffInDays($fechaPagoReal);
            \Log::info("⏰ Días de mora: {$diasMora}");

            // Usar el servicio de moras para generar las moras faltantes hasta la fecha específica
            $moraService = app(\App\Services\MoraService::class);

            // Generar moras hasta la fecha de pago (método específico para ediciones)
            $resultado = $moraService->generarMorasHastaFecha($cuota, $fechaPagoReal);

            if (isset($resultado['generadas'])) {
                \Log::info("✅ Moras generadas: {$resultado['generadas']} nuevas moras, días vencidos: {$resultado['dias_vencidos']}");
            } else {
                \Log::info("ℹ️ Resultado: {$resultado['mensaje']}");
            }

        } catch (\Exception $e) {
            \Log::error("❌ Error recalculando moras cuota {$cuota->id}: ".$e->getMessage());
        }
    }

    /**
     * Calcula correctamente el monto pagado para una mora específica
     * usando distribución secuencial de operaciones
     */
    private function calcularMontoPagadoMoraCorrectamente(\App\Models\MoraCuota $mora): float
    {
        // Obtener todas las operaciones que afectan esta mora
        $operaciones = DB::table('operacion_mora')
            ->join('operaciones', 'operacion_mora.operacion_id', '=', 'operaciones.id')
            ->where('operacion_mora.mora_cuota_id', $mora->id)
            ->where('operaciones.estado', '!=', 'anulado')
            ->select('operaciones.*')
            ->get();

        $totalPagado = 0;

        foreach ($operaciones as $operacion) {
            // Obtener todas las moras que esta operación está pagando para esta cuota
            $morasDeEstaCuota = DB::table('operacion_mora')
                ->join('mora_cuota', 'operacion_mora.mora_cuota_id', '=', 'mora_cuota.id')
                ->where('operacion_mora.operacion_id', $operacion->id)
                ->where('mora_cuota.cuota_id', $mora->cuota_id)
                ->orderBy('mora_cuota.fecha') // Orden cronológico
                ->select('mora_cuota.*')
                ->get();

            if ($morasDeEstaCuota->count() == 1) {
                // Si la operación solo paga esta mora, usa el abono completo
                $totalPagado += $operacion->abono;
                \Log::info("Mora {$mora->id}: Operación {$operacion->id} pago único = {$operacion->abono}");
            } else {
                // Distribución secuencial: completar moras en orden hasta agotar el monto
                $montoRestante = (float) $operacion->abono;
                $montoAsignado = 0;

                foreach ($morasDeEstaCuota as $moraActual) {
                    if ($montoRestante <= 0) {
                        break;
                    }

                    $montoNecesario = (float) $moraActual->monto;
                    $montoPagar = min($montoNecesario, $montoRestante);

                    if ($moraActual->id == $mora->id) {
                        $montoAsignado = $montoPagar;
                    }

                    $montoRestante -= $montoPagar;
                }

                $totalPagado += $montoAsignado;
                \Log::info("Mora {$mora->id}: Operación {$operacion->id} distribuida secuencialmente = {$montoAsignado}");
            }
        }

        // NOTA: NO sumamos abonos a favor aquí porque ya están reflejados en mora->monto_pagado
        // Los abonos a favor se aplican automáticamente cuando se generan las moras
        // y actualizan directamente el campo monto_pagado de la mora.
        // Si los sumáramos aquí, estaríamos duplicando el monto aplicado.

        return round($totalPagado, 2);
    }

    /**
     * Revierte una cuota de convenio específica al anular un pago
     * Similar al proceso de préstamos: revierte el pago, actualiza estados y recalcula moras
     */
    private function revertirCuotaConvenio($convenio, $numeroCuota, $operacion)
    {
        try {
            \Log::info("🔄 Revirtiendo cuota convenio #{$numeroCuota} - Operación {$operacion->id}");

            // Buscar la cuota de convenio
            $cuotaConvenio = \App\Models\CuotaConvenioModel::where('convenio_id', $convenio->id)
                ->where('numero_cuota', $numeroCuota)
                ->first();

            if (!$cuotaConvenio) {
                \Log::warning("⚠️ No se encontró cuota convenio #{$numeroCuota} para convenio {$convenio->id}");
                return;
            }

            \Log::info("✅ Cuota convenio encontrada - ID: {$cuotaConvenio->id}, Monto pagado actual: {$cuotaConvenio->monto_pagado}");

            // PASO 1: Revertir el monto pagado de la cuota
            $montoPagadoAnterior = $cuotaConvenio->monto_pagado;
            $nuevoMontoPagado = max(0, $cuotaConvenio->monto_pagado - $operacion->abono);

            // PASO 2: Determinar nuevo estado de la cuota (similar a préstamos)
            $nuevoEstado = $nuevoMontoPagado == 0
                ? (now()->greaterThan($cuotaConvenio->fecha_vencimiento)
                    ? \App\Enums\CuotaConvenio::VENCIDO
                    : \App\Enums\CuotaConvenio::PENDIENTE)
                : ($nuevoMontoPagado >= $cuotaConvenio->monto_cuota
                    ? \App\Enums\CuotaConvenio::PAGADO
                    : \App\Enums\CuotaConvenio::PARCIAL);

            // PASO 3: Actualizar la cuota
            $cuotaConvenio->update([
                'monto_pagado' => $nuevoMontoPagado,
                'estado' => $nuevoEstado,
                'fecha_pago' => $nuevoMontoPagado > 0 ? $cuotaConvenio->fecha_pago : null,
            ]);

            \Log::info('✅ Cuota de convenio revertida', [
                'cuota_convenio_id' => $cuotaConvenio->id,
                'numero_cuota' => $numeroCuota,
                'monto_anterior' => $montoPagadoAnterior,
                'monto_revertido' => $operacion->abono,
                'nuevo_monto_pagado' => $nuevoMontoPagado,
                'nuevo_estado' => $nuevoEstado->label(),
            ]);

            // PASO 4: Revertir moras asociadas a esta cuota
            $this->revertirMorasConvenio($cuotaConvenio, $operacion);

            // PASO 5: Revertir regularizaciones de moras (si el pago fue a tiempo)
            $this->revertirRegularizacionesMorasConvenio($cuotaConvenio, $operacion);

            // PASO 6: Recalcular y recrear moras si es necesario (similar a préstamos)
            $this->recalcularMorasConvenio($cuotaConvenio);

            return [
                'cuota_id' => $cuotaConvenio->id,
                'monto_revertido' => $operacion->abono,
                'nuevo_monto_pagado' => $nuevoMontoPagado,
                'nuevo_estado' => $nuevoEstado->label(),
            ];

        } catch (\Exception $e) {
            \Log::error("Error al revertir cuota convenio #{$numeroCuota}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Revierte el pago de moras de convenio asociadas a una operación anulada
     * IMPORTANTE: Solo revierte el estado y monto pagado, NO anula ni elimina las moras
     */
    private function revertirMorasConvenio($cuotaConvenio, $operacion)
    {
        try {
            \Log::info("🔍 Revirtiendo moras de cuota convenio {$cuotaConvenio->id}");

            // Buscar moras pagadas o parciales (las que tienen pago)
            $moras = $cuotaConvenio->moras()
                ->whereIn('estado', [MoraConvenioEstado::PAGADO->value, MoraConvenioEstado::PARCIAL->value])
                ->get();

            if ($moras->count() === 0) {
                \Log::info("✅ No hay moras para revertir en cuota {$cuotaConvenio->id}");
                return 0;
            }

            $morasRevertidas = 0;
            $montoTotalRevertido = 0;

            \Log::info("🔄 Revirtiendo {$moras->count()} moras a estado pendiente");

            foreach ($moras as $mora) {
                $estadoAnterior = $mora->estado;
                $montoPagadoAnterior = $mora->monto_pagado ?? 0;

                // REVERTIR el estado a pendiente sin anular
                $mora->update([
                    'monto_pagado' => 0,
                    'estado' => MoraConvenioEstado::PENDIENTE->value,
                ]);

                $morasRevertidas++;
                $montoTotalRevertido += $montoPagadoAnterior;

                \Log::info("🔄 Mora revertida", [
                    'mora_id' => $mora->id,
                    'estado_anterior' => $estadoAnterior,
                    'monto_pagado_anterior' => $montoPagadoAnterior,
                    'monto_mora' => $mora->monto,
                    'nuevo_estado' => 'pendiente',
                ]);
            }

            if ($morasRevertidas > 0) {
                \Log::info("✅ Total moras revertidas: {$morasRevertidas}, Monto total revertido: S/{$montoTotalRevertido}");
            }

            return $morasRevertidas;

        } catch (\Exception $e) {
            \Log::error("Error al revertir moras de cuota convenio {$cuotaConvenio->id}: ".$e->getMessage());
            return 0;
        }
    }

    /**
     * Recalcula las moras de una cuota de convenio después de revertir un pago
     * Similar al proceso de préstamos: genera moras faltantes basadas en fechas
     */
    private function recalcularMorasConvenio($cuotaConvenio)
    {
        try {
            \Log::info("🔄 Recalculando moras para cuota convenio {$cuotaConvenio->id}");

            $fechaVencimiento = \Carbon\Carbon::parse($cuotaConvenio->fecha_vencimiento)->startOfDay();
            $hoy = \Carbon\Carbon::now()->startOfDay();

            // Si la cuota no está vencida, no hay moras que generar
            if ($hoy->lte($fechaVencimiento)) {
                \Log::info("✅ Cuota no vencida - No se generan moras");
                return;
            }

            // Si la cuota está completamente pagada, no generamos moras
            if ($cuotaConvenio->estado === \App\Enums\CuotaConvenio::PAGADO) {
                \Log::info("✅ Cuota pagada - No se generan moras");
                return;
            }

            // Calcular cuántas moras deberían existir
            $diasVencidos = $fechaVencimiento->diffInDays($hoy);
            $intervaloMora = 7; // Moras semanales (cada 7 días)
            $morasMaximas = 7; // Máximo 7 moras por cuota
            $cantidadMorasEsperadas = min(floor($diasVencidos / $intervaloMora), $morasMaximas);

            \Log::info("📅 Cálculo de moras", [
                'dias_vencidos' => $diasVencidos,
                'moras_calculadas' => floor($diasVencidos / $intervaloMora),
                'moras_esperadas' => $cantidadMorasEsperadas,
                'limite_maximo' => $morasMaximas,
            ]);

            if ($cantidadMorasEsperadas === 0) {
                \Log::info("✅ No hay días suficientes para generar moras");
                return;
            }

            // Obtener moras existentes (no anuladas)
            $morasExistentes = $cuotaConvenio->moras()
                ->where('estado', '!=', MoraConvenioEstado::ANULADO->value)
                ->orderBy('fecha')
                ->get();

            $cantidadMorasExistentes = $morasExistentes->count();

            \Log::info("📊 Moras - Esperadas: {$cantidadMorasEsperadas}, Existentes: {$cantidadMorasExistentes}");

            // Si ya existen suficientes moras, no generamos más
            if ($cantidadMorasExistentes >= $cantidadMorasEsperadas) {
                \Log::info("✅ Ya existen suficientes moras");
                return;
            }

            // Generar moras faltantes
            $morasGeneradas = 0;
            $montoMora = $cuotaConvenio->convenio->monto_mora ?? 25; // Usar monto de mora del convenio

            \Log::info("💰 Monto de mora a usar: S/{$montoMora} (del convenio #{$cuotaConvenio->convenio_id})");

            for ($i = $cantidadMorasExistentes; $i < $cantidadMorasEsperadas; $i++) {
                $fechaMora = $fechaVencimiento->copy()->addDays(($i + 1) * $intervaloMora);

                // No generar moras futuras
                if ($fechaMora->gt($hoy)) {
                    break;
                }

                \App\Models\MoraConvenio::create([
                    'cuota_convenio_id' => $cuotaConvenio->id,
                    'convenio_id' => $cuotaConvenio->convenio_id,
                    'monto' => $montoMora,
                    'monto_pagado' => 0,
                    'fecha' => $fechaMora,
                    'estado' => MoraConvenioEstado::PENDIENTE->value,
                ]);

                $morasGeneradas++;

                \Log::info("🔥 Mora generada", [
                    'numero_mora' => $i + 1,
                    'fecha' => $fechaMora->format('Y-m-d'),
                    'monto' => $montoMora,
                ]);
            }

            if ($morasGeneradas > 0) {
                \Log::info("✅ Total moras generadas: {$morasGeneradas}");
            }

        } catch (\Exception $e) {
            \Log::error("Error al recalcular moras de cuota convenio {$cuotaConvenio->id}: ".$e->getMessage());
        }
    }

    /**
     * Revierte las regularizaciones de moras de convenio cuando se anula un pago
     * Las moras que fueron regularizadas por el pago deben volver a estado pendiente
     */
    private function revertirRegularizacionesMorasConvenio($cuotaConvenio, $operacion)
    {
        try {
            $fechaOperacion = \Carbon\Carbon::parse($operacion->fecha)->startOfDay();
            $fechaVencimiento = \Carbon\Carbon::parse($cuotaConvenio->fecha_vencimiento)->startOfDay();

            \Log::info("🔄 REVERSIÓN DE REGULARIZACIONES - Cuota convenio {$cuotaConvenio->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pago anulado {$fechaOperacion->format('Y-m-d')}");

            // Obtener todas las moras regularizadas de esta cuota
            $morasRegularizadas = $cuotaConvenio->moras()
                ->where('estado', MoraConvenioEstado::REGULARIZADA->value)
                ->get();

            if ($morasRegularizadas->count() === 0) {
                \Log::info("✅ No hay moras regularizadas para revertir en cuota {$cuotaConvenio->id}");
                return;
            }

            $morasReactivadas = 0;

            if ($fechaOperacion->lte($fechaVencimiento)) {
                // CASO 1: El pago fue a tiempo - reactivar TODAS las moras regularizadas
                \Log::info("✅ Pago fue a tiempo - Reactivando {$morasRegularizadas->count()} moras regularizadas");

                foreach ($morasRegularizadas as $mora) {
                    $mora->update(['estado' => MoraConvenioEstado::PENDIENTE->value]);
                    $morasReactivadas++;
                    \Log::info("   🔄 Mora {$mora->id} (fecha {$mora->fecha}) reactivada: REGULARIZADA → PENDIENTE");
                }

            } else {
                // CASO 2: El pago fue tardío - reactivar solo moras POSTERIORES a la fecha del pago anulado
                \Log::info("⏰ Pago fue tardío - Revisando moras para reactivar solo las posteriores a {$fechaOperacion->format('Y-m-d')}");

                foreach ($morasRegularizadas as $mora) {
                    $fechaMora = \Carbon\Carbon::parse($mora->fecha)->startOfDay();

                    // Solo reactivar moras posteriores a la fecha del pago que se está anulando
                    if ($fechaMora->gt($fechaOperacion)) {
                        $mora->update(['estado' => MoraConvenioEstado::PENDIENTE->value]);
                        $morasReactivadas++;
                        \Log::info("   🔄 Mora {$mora->id} (fecha {$mora->fecha}) reactivada: REGULARIZADA → PENDIENTE (posterior al pago)");
                    } else {
                        \Log::info("   ✅ Mora {$mora->id} (fecha {$mora->fecha}) se mantiene regularizada (anterior/igual al pago)");
                    }
                }
            }

            if ($morasReactivadas > 0) {
                \Log::info("🔄 Total moras de convenio reactivadas: {$morasReactivadas}");
            } else {
                \Log::info('✅ No se reactivaron moras (posiblemente todas eran anteriores al pago)');
            }

        } catch (\Exception $e) {
            \Log::error("Error al revertir regularizaciones de moras para cuota convenio {$cuotaConvenio->id}: ".$e->getMessage());
        }
    }

    /**
     * Revierte los abonos a favor generados por una operación de convenio anulada
     */
    private function revertirAbonosFavorConvenio($operacion)
    {
        try {
            \Log::info("🔍 Buscando abonos a favor generados por operación {$operacion->id}");

            // Buscar todos los abonos a favor generados por esta operación
            $abonosFavor = \App\Models\AbonoMoraFavorConvenio::where('operacion_id', $operacion->id)
                ->where('estado', \App\Models\AbonoMoraFavorConvenio::ESTADO_ACTIVO)
                ->get();

            if ($abonosFavor->count() === 0) {
                \Log::info("✅ No hay abonos a favor activos para revertir");
                return;
            }

            $totalRevertido = 0;
            $abonosRevertidos = 0;

            foreach ($abonosFavor as $abono) {
                // Guardar valores antes de anular
                $saldoFavorOriginal = $abono->saldo_favor;
                $montoUtilizado = $abono->monto_utilizado;

                // Si el abono ya fue utilizado parcialmente, necesitamos manejarlo
                if ($montoUtilizado > 0) {
                    \Log::warning("⚠️ Abono a favor {$abono->id} ya fue utilizado parcialmente (S/{$montoUtilizado} de S/{$abono->monto_abonado})");

                    // TODO: Aquí podríamos revertir las aplicaciones de este abono a moras futuras
                    // Por ahora, solo marcamos como anulado
                }

                // Marcar el abono como anulado
                $abono->update([
                    'estado' => \App\Models\AbonoMoraFavorConvenio::ESTADO_ANULADO,
                    'saldo_favor' => 0,
                ]);

                $totalRevertido += $saldoFavorOriginal;
                $abonosRevertidos++;

                \Log::info("🚫 Abono a favor anulado", [
                    'abono_favor_id' => $abono->id,
                    'monto_abonado' => $abono->monto_abonado,
                    'monto_utilizado' => $montoUtilizado,
                    'saldo_favor_original' => $saldoFavorOriginal,
                ]);
            }

            \Log::info("✅ Total abonos a favor revertidos: {$abonosRevertidos}, Monto total: S/{$totalRevertido}");

        } catch (\Exception $e) {
            \Log::error("Error al revertir abonos a favor para operación {$operacion->id}: ".$e->getMessage());
        }
    }

    /**
     * Revierte cuotas de convenio basándose en el monto de la operación
     * Distribuye la reversión desde las cuotas pagadas/parciales en orden inverso (LIFO)
     */
    private function revertirCuotasConvenioPorMonto($convenio, $operacion)
    {
        try {
            \Log::info("🔄 Revirtiendo cuotas de convenio por monto - Operación: {$operacion->id}, Monto: S/{$operacion->abono}");

            // Obtener cuotas que tienen pagos (pagadas o parciales) en ORDEN INVERSO
            // Para revertir desde la última cuota pagada hacia atrás (LIFO)
            $cuotasConPago = $convenio->cuotasConvenio()
                ->where('monto_pagado', '>', 0)
                ->orderByDesc('numero_cuota')
                ->get();

            \Log::info("📊 Cuotas con pago encontradas: {$cuotasConPago->count()}");

            if ($cuotasConPago->count() === 0) {
                \Log::warning("⚠️ No se encontraron cuotas con pagos para revertir");
                return;
            }

            $montoRestante = $operacion->abono;
            $cuotasRevertidas = 0;

            foreach ($cuotasConPago as $cuota) {
                if ($montoRestante <= 0) {
                    break;
                }

                $montoPagadoCuota = $cuota->monto_pagado ?? 0;

                \Log::info("🔍 Procesando cuota #{$cuota->numero_cuota}: Pagado actual S/{$montoPagadoCuota}, Monto a revertir restante: S/{$montoRestante}");

                if ($montoPagadoCuota > 0) {
                    // Calcular cuánto revertir de esta cuota
                    $montoARevertir = min($montoRestante, $montoPagadoCuota);

                    \Log::info("🔄 Revirtiendo S/{$montoARevertir} de cuota #{$cuota->numero_cuota}");

                    // Crear una operación temporal para la reversión
                    $operacionTemp = (object)[
                        'id' => $operacion->id,
                        'abono' => $montoARevertir,
                        'fecha' => $operacion->fecha,
                    ];

                    // Revertir la cuota (esto llamará a recalcularMorasConvenio)
                    $resultado = $this->revertirCuotaConvenio($convenio, $cuota->numero_cuota, $operacionTemp);

                    $montoRestante -= $montoARevertir;
                    $cuotasRevertidas++;

                    \Log::info("✅ Cuota #{$cuota->numero_cuota} revertida exitosamente: S/{$montoARevertir}, Estado: {$resultado['nuevo_estado']}, Monto pagado: {$resultado['nuevo_monto_pagado']}");
                }
            }

            if ($cuotasRevertidas > 0) {
                \Log::info("✅ REVERSIÓN COMPLETADA: {$cuotasRevertidas} cuotas revertidas, Monto total: S/" . ($operacion->abono - $montoRestante));
            } else {
                \Log::warning("⚠️ No se revirtió ninguna cuota");
            }

        } catch (\Exception $e) {
            \Log::error("❌ Error al revertir cuotas de convenio por monto: ".$e->getMessage());
            \Log::error("Stack trace: ".$e->getTraceAsString());
        }
    }
}
