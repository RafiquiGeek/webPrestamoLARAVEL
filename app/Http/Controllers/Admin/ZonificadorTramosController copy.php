<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ZonificadorTramosController extends Controller
{
    /**
     * Mostrar vista principal del zonificador
     */
    public function index()
    {
        // Obtener datos para los select2
        $jccs = User::role('JCC')
            ->where('status', 1)
            ->with('persona:id,nombres,ape_pat')
            ->get(['id', 'codigo', 'persona_id']);

        $asesores = User::role('Asesor')
            ->where('status', 1)
            ->with('persona:id,nombres,ape_pat')
            ->get(['id', 'codigo', 'persona_id']);

        $analistas = User::role('Analista')
            ->where('status', 1)
            ->with('persona:id,nombres,ape_pat')
            ->get(['id', 'codigo', 'persona_id']);

        // Cargar zonas con sus sucursales
        $zonas = Zona::with('sucursales')->get();

        return view('admin.zonificador-tramos.index', compact('jccs', 'asesores', 'analistas', 'zonas'));
    }

    /**
     * API para obtener datos filtrados de clientes por tramos
     */
    public function getData(Request $request)
    {
        try {
            \Log::info('=== ZONIFICADOR DE TRAMOS - CONSULTA INICIADA ===', ['filtros' => $request->all()]);

            $startTime = microtime(true);

            // ==========================================
            // CONSULTA: CUOTAS DE PRÉSTAMOS (sin convenios activos)
            // ==========================================
            $query = Cuota::query()
                ->select('cuotas.id', 'cuotas.prestamo_id', 'cuotas.numero', 'cuotas.monto', 'cuotas.estado', 'cuotas.fecha_pago', 'cuotas.monto_pagado')
                ->conDeuda()
                ->where('fecha_pago', '<=', Carbon::now()->subDay()->endOfDay())
                ->sinConveniosActivos()
                ->with([
                    'prestamo' => function($q) {
                        $q->select('id', 'cliente_id', 'direccion_cobro_id', 'estado')
                          ->with([
                              'cliente.persona' => function($subQ) {
                                  $subQ->select('id', 'nombres', 'ape_pat', 'ape_mat', 'documento');
                              },
                              'cuotas' => function($subQ) {
                                  $subQ->select('id', 'prestamo_id', 'numero', 'monto', 'estado', 'monto_pagado', 'fecha_pago');
                              },
                              'carterasJcc' => function($subQ) {
                                  $subQ->select('prestamo_id', 'jcc_id', 'estado')
                                       ->where('estado', 1);
                              },
                              'carterasAsesor' => function($subQ) {
                                  $subQ->select('prestamo_id', 'asesor_id', 'estado')
                                       ->where('estado', 1);
                              },
                              'carterasAnalista' => function($subQ) {
                                  $subQ->select('prestamo_id', 'analista_id', 'estado')
                                       ->where('estado', 1);
                              }
                          ]);
                    },
                    'moras' => function($q) {
                        $q->select('id', 'cuota_id', 'monto', 'monto_pagado');
                    }
                ])
                ->whereHas('prestamo', function($q) {
                    $q->whereNotIn('estado', ['Finalizado', 'liquidado']);
                });

            // ==========================================
            // APLICAR FILTROS
            // ==========================================

            // FILTRO: Tipo de consulta (Préstamos/Convenios/Ambos)
            $tipo = $request->input('tipo_consulta', 'ambos');
            
            // FILTRO: JCC
            if ($request->filled('jcc_id')) {
                $jccIds = $request->input('jcc_id');
                $query->whereHas('prestamo.carterasJcc', function ($q) use ($jccIds) {
                    $q->whereIn('jcc_id', is_array($jccIds) ? $jccIds : [$jccIds])->where('estado', 1);
                });
            }

            // FILTRO: Asesor
            if ($request->filled('asesor_id')) {
                $asesorIds = $request->input('asesor_id');
                $query->whereHas('prestamo.carterasAsesor', function ($q) use ($asesorIds) {
                    $q->whereIn('asesor_id', is_array($asesorIds) ? $asesorIds : [$asesorIds])->where('estado', 1);
                });
            }

            // FILTRO: Analista
            if ($request->filled('analista_id')) {
                $analistaIds = $request->input('analista_id');
                $query->whereHas('prestamo.carterasAnalista', function ($q) use ($analistaIds) {
                    $q->whereIn('analista_id', is_array($analistaIds) ? $analistaIds : [$analistaIds])->where('estado', 1);
                });
            }

            // FILTRO: Sucursal
            if ($request->filled('sucursal_id')) {
                $sucursalIds = $request->input('sucursal_id');
                $sucursalIds = is_array($sucursalIds) ? $sucursalIds : [$sucursalIds];

                $query->where(function ($q) use ($sucursalIds) {
                    $q->whereHas('prestamo.direccionCobro', function ($subQ) use ($sucursalIds) {
                        $subQ->whereIn('sucursal_id', $sucursalIds);
                    })
                    ->orWhereHas('prestamo.cliente.persona.direcciones', function ($subQ) use ($sucursalIds) {
                        $subQ->whereIn('sucursal_id', $sucursalIds);
                    });
                });
            }

            // FILTRO: Zona
            if ($request->filled('zona_id')) {
                $zonaIds = $request->input('zona_id');
                $zonaIds = is_array($zonaIds) ? $zonaIds : [$zonaIds];

                $query->where(function ($q) use ($zonaIds) {
                    $sucursalesEnZonas = \DB::table('zona_sucursal')
                        ->whereIn('zona_id', $zonaIds)
                        ->pluck('sucursal_id')
                        ->toArray();

                    if (!empty($sucursalesEnZonas)) {
                        $q->whereHas('prestamo.direccionCobro', function ($subQ) use ($sucursalesEnZonas) {
                            $subQ->whereIn('sucursal_id', $sucursalesEnZonas);
                        })
                        ->orWhereHas('prestamo.cliente.persona.direcciones', function ($subQ) use ($sucursalesEnZonas) {
                            $subQ->whereIn('sucursal_id', $sucursalesEnZonas);
                        });
                    }
                });
            }

            // FILTRO: Fechas
            $fechaDesde = null;
            $fechaHasta = null;
            $tipoRangoFecha = $request->input('tipo_rango_fecha');

            if ($tipoRangoFecha === 'dia' && $request->filled('fecha_dia')) {
                $fechaDia = Carbon::createFromFormat('d/m/Y', $request->input('fecha_dia'))->startOfDay();
                $fechaDesde = $fechaDia;
                $fechaHasta = $fechaDia->copy()->endOfDay();
            } elseif ($tipoRangoFecha === 'mes' && $request->filled('fecha_mes')) {
                $fechaMes = Carbon::createFromFormat('m/Y', $request->input('fecha_mes'))->startOfMonth();
                $fechaDesde = $fechaMes;
                $fechaHasta = $fechaMes->copy()->endOfMonth();
            } elseif ($tipoRangoFecha === 'entre_fechas') {
                if ($request->filled('fecha_desde')) {
                    $fechaDesdeInput = $request->input('fecha_desde');
                    $fechaDesde = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaDesdeInput)
                        ? Carbon::createFromFormat('d/m/Y', $fechaDesdeInput)->startOfDay()
                        : Carbon::parse($fechaDesdeInput)->startOfDay();
                }
                if ($request->filled('fecha_hasta')) {
                    $fechaHastaInput = $request->input('fecha_hasta');
                    $fechaHasta = preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaHastaInput)
                        ? Carbon::createFromFormat('d/m/Y', $fechaHastaInput)->endOfDay()
                        : Carbon::parse($fechaHastaInput)->endOfDay();
                }
            }

            if ($fechaDesde) {
                $query->where('fecha_pago', '>=', $fechaDesde);
            }

            if ($fechaHasta) {
                $query->where('fecha_pago', '<=', $fechaHasta);
            }

            // ==========================================
            // EJECUTAR CONSULTA
            // ==========================================
            $cuotas = $query->orderBy('fecha_pago', 'asc')->get();

            // Validar cuotas
            $cuotasValidas = $cuotas->filter(function ($cuota) {
                return $cuota->prestamo &&
                       $cuota->prestamo->cliente &&
                       $cuota->prestamo->cliente->persona;
            });

            // Agrupar por cliente y calcular información
            $resultado = $cuotasValidas->groupBy(function ($cuota) {
                return $cuota->prestamo->cliente->id;
            })->map(function ($cuotasCliente) {
                $primeraCuota = $cuotasCliente->first();
                $cliente = $primeraCuota->prestamo->cliente;
                $prestamo = $primeraCuota->prestamo;

                // Calcular totales
                $todasCuotas = $prestamo->cuotas;
                $cuotasTotal = $todasCuotas->count();
                $montoTotalCuotas = 0;
                $montoPagado = 0;
                $moraTotal = 0;
                $cuotasPagadas = 0;
                $cuotasVencidas = 0;
                $cuotasNoPagadas = 0;
                $numerosCuotasVencidas = [];
                $montoCuota = 0;
                $fechaHoy = Carbon::now()->startOfDay();

                foreach ($todasCuotas as $cuota) {
                    $monto = $cuota->monto;
                    $montoTotalCuotas += $monto;

                    if ($montoCuota == 0) {
                        $montoCuota = $monto;
                    }

                    $estado = $cuota->estado;
                    $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);

                    if ($estadoValor === 2) { // PAGADO
                        $cuotasPagadas++;
                        $montoPagado += $monto;
                    } elseif (in_array($estadoValor, [0, 1, 3])) { // Pendiente, Parcial, Vencido
                        $cuotasNoPagadas++;

                        $fechaPagoCuota = Carbon::parse($cuota->fecha_pago)->startOfDay();
                        if ($fechaPagoCuota->lt($fechaHoy)) {
                            $cuotasVencidas++;
                            $numerosCuotasVencidas[] = $cuota->numero;
                        }

                        if ($estadoValor === 1 && isset($cuota->monto_pagado)) {
                            $montoPagado += $cuota->monto_pagado;
                        }
                    }
                }

                // Calcular moras
                foreach ($cuotasCliente as $cuota) {
                    $saldoMorasCuota = 0;
                    foreach ($cuota->moras as $mora) {
                        $saldoMorasCuota += ($mora->monto - ($mora->monto_pagado ?? 0));
                    }
                    $moraTotal += $saldoMorasCuota;
                }

                $deudaReal = $cuotasNoPagadas * $montoCuota;

                // Calcular tramo y días de atraso
                $tramo = 0;
                $diasAtraso = 0;
                $primeraCuotaNumero = null;
                $primeraCuotaFechaVencimiento = null;

                $todasLasCuotasOrdenadas = $todasCuotas->sortBy('numero')->values();
                $fechaActual = Carbon::now()->startOfDay();
                $primeraCuotaVencida = null;

                foreach ($todasLasCuotasOrdenadas as $cuota) {
                    $estado = $cuota->estado;
                    $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
                    $noEstaPagada = in_array($estadoValor, [0, 1, 3]);
                    $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();
                    $yaVencio = $fechaVencimiento->lt($fechaActual);

                    if ($noEstaPagada && $yaVencio) {
                        $primeraCuotaVencida = $cuota;
                        break;
                    }
                }

                if ($primeraCuotaVencida) {
                    $fechaVencimiento = Carbon::parse($primeraCuotaVencida->fecha_pago)->startOfDay();
                    $primeraCuotaNumero = $primeraCuotaVencida->numero;
                    $primeraCuotaFechaVencimiento = $fechaVencimiento->format('d/m/Y');
                    $diasAtraso = $fechaActual->diffInDays($fechaVencimiento);

                    if ($diasAtraso <= 6) {
                        $tramo = 0;
                    } elseif ($diasAtraso <= 14) {
                        $tramo = 1;
                    } elseif ($diasAtraso <= 21) {
                        $tramo = 2;
                    } elseif ($diasAtraso <= 30) {
                        $tramo = 3;
                    } else {
                        $tramo = 4;
                    }
                }

                // Tramo especial: Solo mora
                if ($cuotasVencidas == 0 && $moraTotal > 0) {
                    $tramo = 5;
                }

                // Obtener ubicación
                $zonaNombre = 'N/A';
                $sucursalNombre = 'N/A';
                $sucursalId = null;
                $latitud = null;
                $longitud = null;

                // Obtener dirección de cobro
                if ($prestamo && $prestamo->direccion_cobro_id) {
                    $direccionCobro = \App\Models\Direccion::select('id', 'sucursal_id', 'direccion', 'latitud', 'longitud')
                        ->with(['sucursal:id,sucursal'])
                        ->find($prestamo->direccion_cobro_id);

                    if ($direccionCobro) {
                        if ($direccionCobro->sucursal) {
                            $sucursalNombre = $direccionCobro->sucursal->sucursal;
                            $sucursalId = $direccionCobro->sucursal->id;
                        }
                        $latitud = $direccionCobro->latitud;
                        $longitud = $direccionCobro->longitud;
                    }
                }

                // Obtener zona
                if ($sucursalId !== null) {
                    $zona = \DB::table('zona_sucursal')
                        ->join('zonas', 'zona_sucursal.zona_id', '=', 'zonas.id')
                        ->where('zona_sucursal.sucursal_id', $sucursalId)
                        ->select('zonas.nombre')
                        ->first();

                    if ($zona) {
                        $zonaNombre = $zona->nombre;
                    }
                }

                // Nombre completo
                $nombreCompleto = trim(
                    ($cliente->persona->nombres ?? '') . ' ' .
                    ($cliente->persona->ape_pat ?? '') . ' ' .
                    ($cliente->persona->ape_mat ?? '')
                );

                sort($numerosCuotasVencidas);

                return [
                    'id' => $cliente->id,
                    'dni' => $cliente->persona->documento ?? 'N/A',
                    'nombre' => $nombreCompleto,
                    'zona' => $zonaNombre,
                    'sucursal' => $sucursalNombre,
                    'direccion' => $direccionCobro->direccion ?? 'N/A',
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'creditoTotal' => $montoTotalCuotas,
                    'montoPagado' => $montoPagado,
                    'deudaReal' => $deudaReal,
                    'montoCuota' => $montoCuota,
                    'cuotasPagadas' => $cuotasPagadas,
                    'cuotasTotal' => $cuotasTotal,
                    'cuotasVencidas' => $cuotasVencidas,
                    'cuotasNoPagadas' => $cuotasNoPagadas,
                    'numerosCuotasVencidas' => $numerosCuotasVencidas,
                    'tramo' => $tramo,
                    'moraAcumulada' => $moraTotal,
                    'diasAtraso' => $diasAtraso,
                    'primeraCuotaVencidaNumero' => $primeraCuotaNumero,
                    'primeraCuotaVencidaFecha' => $primeraCuotaFechaVencimiento,
                ];
            })->values();

            // Filtrar por tramos seleccionados
            if ($request->filled('tramos')) {
                $tramosSeleccionados = $request->input('tramos');
                $resultado = $resultado->filter(function ($item) use ($tramosSeleccionados) {
                    return in_array($item['tramo'], $tramosSeleccionados);
                })->values();
            }

            // Filtrar por estados crediticios
            if ($request->filled('estado')) {
                $estadosSeleccionados = $request->input('estado');
                $estadosSeleccionados = is_array($estadosSeleccionados) ? $estadosSeleccionados : [$estadosSeleccionados];

                $resultado = $resultado->filter(function ($item) use ($estadosSeleccionados) {
                    return in_array($item['estado'], $estadosSeleccionados);
                })->values();
            }

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            \Log::info('Zonificador de tramos completado', [
                'total_registros' => $resultado->count(),
                'tiempo_ejecucion' => $executionTime
            ]);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'performance' => [
                    'execution_time' => $executionTime,
                    'total_records' => $resultado->count()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en zonificador de tramos: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
}
