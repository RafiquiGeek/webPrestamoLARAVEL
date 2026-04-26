<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConvenioEstado;
use App\Enums\CuotaEstado;
use App\Enums\CuotaConvenio as CuotaConvenioEstado;
use App\Enums\MoraConvenioEstado;
use App\Enums\MoraCuotaEstado;
use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\Cuota;
use App\Models\CuotaConvenioModel;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
//log
use Illuminate\Support\Facades\Log;

class DeudasController extends Controller
{

    public function tramos()
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

        // Cargar zonas con sus sucursales relacionadas desde la tabla zona_sucursal
        $zonas = Zona::with('sucursales')->get();

        return view('admin.Deudas.tramos', compact('jccs', 'asesores', 'analistas', 'zonas'));
    }

    /**
     * API para obtener datos filtrados de tramos
     */
    public function getTramosData(Request $request)
    {
        try {
            // Aumentar timeout para reportes pesados
            set_time_limit(120);

            // OPTIMIZACIÓN: Agregar límite de tiempo para debug
            $startTime = microtime(true);

            // ==========================================
// CONSULTA 1: CUOTAS DE PRÉSTAMOS (sin convenios activos)
// ==========================================
            $query = Cuota::query()
                ->conDeuda()
                // CRÍTICO: Solo mostrar cuotas vencidas (fecha_pago <= ayer)
                ->where('cuotas.fecha_pago', '<=', Carbon::now()->subDay()->endOfDay())
                ->with([
                    'prestamo' => function ($q) {
                        $q->select('id', 'cliente_id', 'direccion_cobro_id', 'estado', 'cantidad_solicitada')
                            ->with([
                                'cliente.persona' => function ($subQ) {
                                    $subQ->select('id', 'nombres', 'ape_pat', 'ape_mat', 'documento');
                                },
                                'cuotas' => function ($subQ) {
                                    $subQ->select('id', 'prestamo_id', 'numero', 'monto', 'estado', 'monto_pagado', 'fecha_pago');
                                },
                                'carterasJcc' => function ($subQ) {
                                    $subQ->select('prestamo_id', 'jcc_id', 'estado')
                                        ->where('estado', 1);
                                },
                                'carterasAsesor' => function ($subQ) {
                                    $subQ->select('prestamo_id', 'asesor_id', 'estado')
                                        ->where('estado', 1);
                                },
                                'carterasAnalista' => function ($subQ) {
                                    $subQ->select('prestamo_id', 'analista_id', 'estado')
                                        ->where('estado', 1);
                                },
                                'latestOperation' => function ($subQ) {
                                    $subQ->select('operaciones.id', 'operaciones.prestamo_id', 'operaciones.fecha', 'operaciones.tipo_operacion');
                                },
                                'latestCuotaPayment' => function ($subQ) {
                                    $subQ->select('operaciones.id', 'operaciones.prestamo_id', 'operaciones.fecha', 'operaciones.tipo_operacion');
                                },
                                'latestMoraPayment' => function ($subQ) {
                                    $subQ->select('operaciones.id', 'operaciones.prestamo_id', 'operaciones.fecha', 'operaciones.tipo_operacion');
                                },
                                'direccionCobro:id,sucursal_id',
                                'direccionCobro.sucursal:id,sucursal',
                                'cliente.persona.direcciones' => function ($subQ) {
                                    $subQ->select('id', 'persona_id', 'sucursal_id', 'tipo_direccion');
                                },
                                'cliente.persona.direcciones.sucursal:id,sucursal',
                            ]);
                    },
                    'moras' => function ($q) {
                        $q->select('id', 'cuota_id', 'monto', 'monto_pagado');
                    }
                ])
                ->join('prestamos', 'cuotas.prestamo_id', '=', 'prestamos.id')
                // Excluir préstamos finalizados, liquidados, anulados, cancelados o pagados
                ->whereNotIn('prestamos.estado', [
                    'Finalizado',
                    'finalizado',
                    'Liquidado',
                    'liquidado',
                    'Anulado',
                    'anulado',
                    'Cancelado',
                    'cancelado',
                    'Pagado',
                    'pagado'
                ])
                // CRÍTICO: Excluir préstamos que tienen convenios activos
                ->whereNotExists(function ($sub) {
                    $sub->select(\DB::raw(1))
                        ->from('convenios')
                        ->whereColumn('convenios.prestamo_id', 'prestamos.id')
                        ->where('convenios.estado', \App\Enums\ConvenioEstado::ACTIVO->value);
                })
                // Excluir préstamos que tienen convenio CUMPLIDO (deuda saldada vía convenio)
                ->whereNotExists(function ($sub) {
                    $sub->select(\DB::raw(1))
                        ->from('convenios')
                        ->whereColumn('convenios.prestamo_id', 'prestamos.id')
                        ->where('convenios.estado', \App\Enums\ConvenioEstado::CUMPLIDO->value);
                })
                ->select('cuotas.id', 'cuotas.prestamo_id', 'cuotas.numero', 'cuotas.monto', 'cuotas.estado', 'cuotas.fecha_pago', 'cuotas.monto_pagado');

            $tipo = $request->input('tipo', 'ambos');

            // FILTROS DE CARTERAS
            if ($request->filled('jcc_id')) {
                $jccIds = $request->input('jcc_id');
                $query->whereHas('prestamo.carterasJcc', function ($q) use ($jccIds) {
                    $q->whereIn('jcc_id', is_array($jccIds) ? $jccIds : [$jccIds])->where('estado', 1);
                });
            }

            if ($request->filled('asesor_id')) {
                $asesorIds = $request->input('asesor_id');
                $query->whereHas('prestamo.carterasAsesor', function ($q) use ($asesorIds) {
                    $q->whereIn('asesor_id', is_array($asesorIds) ? $asesorIds : [$asesorIds])->where('estado', 1);
                });
            }

            if ($request->filled('analista_id')) {
                $analistaIds = $request->input('analista_id');
                $query->whereHas('prestamo.carterasAnalista', function ($q) use ($analistaIds) {
                    $q->whereIn('analista_id', is_array($analistaIds) ? $analistaIds : [$analistaIds])->where('estado', 1);
                });
            }

            // FILTROS DE UBICACIÓN
            $query->porUbicacion(
                $request->input('zona_id'),
                $request->input('sucursal_id')
            );

            // FILTROS DE FECHA
            $tipoRangoFecha = $request->input('tipo_rango_fecha');
            $fechaDesde = null;
            $fechaHasta = null;

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
                    $fechaDesde = $request->input('fecha_desde');
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaDesde)) {
                        $fechaDesde = Carbon::createFromFormat('d/m/Y', $fechaDesde)->startOfDay();
                    } else {
                        $fechaDesde = Carbon::parse($fechaDesde)->startOfDay();
                    }
                }
                if ($request->filled('fecha_hasta')) {
                    $fechaHasta = $request->input('fecha_hasta');
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaHasta)) {
                        $fechaHasta = Carbon::createFromFormat('d/m/Y', $fechaHasta)->endOfDay();
                    } else {
                        $fechaHasta = Carbon::parse($fechaHasta)->endOfDay();
                    }
                }
            } else {
                if ($request->filled('fecha_desde')) {
                    $fechaDesdeInput = $request->input('fecha_desde');
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaDesdeInput)) {
                        $fechaDesde = Carbon::createFromFormat('d/m/Y', $fechaDesdeInput)->startOfDay();
                    } else {
                        $fechaDesde = Carbon::parse($fechaDesdeInput)->startOfDay();
                    }
                }
                if ($request->filled('fecha_hasta')) {
                    $fechaHastaInput = $request->input('fecha_hasta');
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaHastaInput)) {
                        $fechaHasta = Carbon::createFromFormat('d/m/Y', $fechaHastaInput)->endOfDay();
                    } else {
                        $fechaHasta = Carbon::parse($fechaHastaInput)->endOfDay();
                    }
                }
            }

            if ($fechaDesde) {
                $query->where('cuotas.fecha_pago', '>=', $fechaDesde);
            }

            if ($fechaHasta) {
                $query->where('cuotas.fecha_pago', '<=', $fechaHasta);
            }

            // BÚSQUEDA POR CLIENTE
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('prestamo.cliente.persona', function ($q) use ($search) {
                    $q->where('nombres', 'like', "%{$search}%")
                        ->orWhere('ape_pat', 'like', "%{$search}%")
                        ->orWhere('ape_mat', 'like', "%{$search}%")
                        ->orWhere('documento', 'like', "%{$search}%");
                });
            }

            // ==========================================
            // EJECUTAR CONSULTAS: Respetar filtro de tipo
            // ==========================================

            if ($tipo === 'convenios') {
                $cuotas = $this->obtenerCuotasConvenios($request);
            } elseif ($tipo === 'prestamos') {
                $cuotas = $query->orderBy('cuotas.fecha_pago', 'asc')->get();
            } else {
                $cuotasPrestamos = $query->orderBy('cuotas.fecha_pago', 'asc')->get();
                $cuotasConvenios = $this->obtenerCuotasConvenios($request);
                $cuotas = $cuotasPrestamos->merge($cuotasConvenios);
            }

            // Validar y agrupar cuotas
            $cuotasValidas = $cuotas->filter(function ($cuota) {
                return $cuota->prestamo &&
                    $cuota->prestamo->cliente &&
                    $cuota->prestamo->cliente->persona;
            });

            // =====================================================
            // PRE-CARGAR DATOS EN BULK para evitar N+1 queries
            // =====================================================

            // 1. Recolectar todos los sucursal_id únicos para cargar zonas en bulk
            $allSucursalIds = $cuotasValidas->map(function ($cuota) {
                $dirs = $cuota->prestamo->cliente->persona->direcciones ?? collect();
                $principal = $dirs->firstWhere('tipo_direccion', 'principal') ?? $dirs->first();
                if ($principal && $principal->sucursal_id)
                    return $principal->sucursal_id;
                $dirCobro = $cuota->prestamo->direccionCobro ?? null;
                return $dirCobro ? $dirCobro->sucursal_id : null;
            })->filter()->unique()->values();

            $zonaSucursalMap = [];
            if ($allSucursalIds->isNotEmpty()) {
                $zonaSucursalMap = \DB::table('zona_sucursal')
                    ->join('zonas', 'zona_sucursal.zona_id', '=', 'zonas.id')
                    ->whereIn('zona_sucursal.sucursal_id', $allSucursalIds->toArray())
                    ->pluck('zonas.nombre', 'zona_sucursal.sucursal_id')
                    ->toArray();
            }

            // 2. Pre-cargar cuotas de convenios en batch
            $convenioIds = $cuotasValidas->filter(function ($cuota) {
                return $cuota->es_cuota_convenio ?? false;
            })->map(function ($cuota) {
                return $cuota->convenio->id ?? null;
            })->filter()->unique()->values();

            $cuotasConvenioBatch = collect();
            if ($convenioIds->isNotEmpty()) {
                $cuotasConvenioBatch = CuotaConvenioModel::whereIn('convenio_id', $convenioIds->toArray())
                    ->get()
                    ->groupBy('convenio_id');
            }

            // =====================================================

            // Agrupar por cliente y calcular tramos
            $resultado = $cuotasValidas->groupBy(function ($cuota) {
                return $cuota->prestamo->cliente->id;
            })->map(function ($cuotasCliente) use ($zonaSucursalMap, $cuotasConvenioBatch) {
                $primeraCuota = $cuotasCliente->first();
                $cliente = $primeraCuota->prestamo->cliente;
                $prestamo = $primeraCuota->prestamo;

                $esConvenio = $primeraCuota->es_cuota_convenio ?? false;
                $tipoConvenio = $esConvenio ? ($primeraCuota->tipo_convenio ?? null) : null;

                // Variables para cálculos
                $montoTotalCuotas = 0;
                $montoPagado = 0;
                $moraTotal = 0;
                $cuotasPagadas = 0;
                $cuotasVencidas = 0;
                $cuotasNoPagadas = 0;
                $numerosCuotasVencidas = [];
                $montoCuota = 0;
                $fechaHoy = Carbon::now()->startOfDay();

                // Obtener las cuotas según el tipo
                if ($esConvenio) {
                    $convenio = $primeraCuota->convenio;
                    $convenioId = $convenio->id;

                    if ($tipoConvenio === 'flexible') {
                        // Convenio flexible: usar cuotas del préstamo original
                        $todasCuotas = $convenio->prestamo->cuotas ?? collect();
                        $cuotasTotal = $todasCuotas->count();
                        // CRÍTICO: El crédito total para convenio flexible es el total_convenio
                        $montoTotalCuotas = $convenio->total_convenio;

                        Log::info("Convenio flexible ID {$convenio->id} - datos", [
                            'prestamo_id' => $convenio->prestamo_id,
                            'cuotas_count' => $cuotasTotal,
                            'total_convenio' => $convenio->total_convenio,
                            'monto_cuota_prestamo' => $todasCuotas->first()->monto ?? 0,
                        ]);
                    } else {
                        // Convenio cuotas normal
                        $todasCuotas = $cuotasConvenioBatch->get($convenioId, collect());
                        $cuotasTotal = $todasCuotas->count();
                    }
                } else {
                    // Préstamo normal
                    $todasCuotas = $prestamo->cuotas;
                    $cuotasTotal = $todasCuotas->count();
                }

                // Recorrer todas las cuotas
                foreach ($todasCuotas as $cuota) {
                    $monto = $esConvenio && $tipoConvenio !== 'flexible' ? ($cuota->monto_cuota ?? 0) : ($cuota->monto ?? 0);
                    $numero = $esConvenio && $tipoConvenio !== 'flexible' ? ($cuota->numero_cuota ?? 0) : ($cuota->numero ?? 0);
                    $fechaPago = $esConvenio && $tipoConvenio !== 'flexible' ? ($cuota->fecha_vencimiento ?? null) : ($cuota->fecha_pago ?? null);

                    // Solo sumar montos si NO es convenio flexible (ya sumamos total_convenio antes)
                    if (!($esConvenio && $tipoConvenio === 'flexible')) {
                        $montoTotalCuotas += $monto;
                    }

                    // Obtener el monto de la cuota para deuda
                    if ($montoCuota == 0) {
                        if ($esConvenio && $tipoConvenio === 'flexible') {
                            // Para convenios flexibles, el monto de la cuota es total_convenio / cuotas_no_pagadas
                            // pero mejor usar el total_convenio como referencia
                            $montoCuota = $convenio->total_convenio;
                        } else {
                            $montoCuota = $monto;
                        }
                    }

                    // Determinar estado de la cuota
                    $estado = $cuota->estado;
                    $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);

                    if ($estadoValor === 2) { // PAGADO
                        $cuotasPagadas++;
                        $montoPagado += $monto;
                    } elseif (in_array($estadoValor, [0, 1, 3])) { // Pendiente, Parcial, Vencido
                        $cuotasNoPagadas++;

                        if ($fechaPago) {
                            $fechaPagoCuota = Carbon::parse($fechaPago)->startOfDay();
                            if ($fechaPagoCuota->lt($fechaHoy)) {
                                $cuotasVencidas++;
                                $numerosCuotasVencidas[] = $numero;
                            }
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

                // Deuda real
                if ($esConvenio && $tipoConvenio === 'flexible') {
                    // Para convenios flexibles: deuda = total_convenio
                    $deudaReal = $convenio->total_convenio;
                } else {
                    $deudaReal = $cuotasNoPagadas * $montoCuota;
                }

                // ==============================================================
                // CÁLCULO DE DÍAS DE ATRASO Y TRAMOS
                // ==============================================================

                $tramo = null;
                $diasAtraso = 0;

                $campoNumero = ($esConvenio && $tipoConvenio !== 'flexible') ? 'numero_cuota' : 'numero';
                $todasLasCuotasOrdenadas = $todasCuotas->sortBy($campoNumero)->values();
                $fechaActual = Carbon::now()->startOfDay();

                $primeraCuotaVencida = null;
                foreach ($todasLasCuotasOrdenadas as $cuota) {
                    $estado = $cuota->estado;
                    $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
                    $noEstaPagada = in_array($estadoValor, [0, 1, 3]);

                    $fechaVencimiento = null;
                    if ($esConvenio && $tipoConvenio !== 'flexible') {
                        $fechaVencimiento = $cuota->fecha_vencimiento ?? null;
                    } else {
                        $fechaVencimiento = $cuota->fecha_pago ?? null;
                    }

                    if ($fechaVencimiento) {
                        $fechaVenc = Carbon::parse($fechaVencimiento)->startOfDay();
                        $yaVencio = $fechaVenc->lt($fechaActual);

                        if ($noEstaPagada && $yaVencio) {
                            $primeraCuotaVencida = $cuota;
                            break;
                        }
                    }
                }

                $primeraCuotaNumero = null;
                $primeraCuotaFechaVencimiento = null;

                if ($primeraCuotaVencida) {
                    $fechaVencimiento = Carbon::parse(
                        ($esConvenio && $tipoConvenio !== 'flexible')
                        ? $primeraCuotaVencida->fecha_vencimiento
                        : $primeraCuotaVencida->fecha_pago
                    )->startOfDay();

                    $primeraCuotaNumero = ($esConvenio && $tipoConvenio !== 'flexible')
                        ? $primeraCuotaVencida->numero_cuota
                        : $primeraCuotaVencida->numero;
                    $primeraCuotaFechaVencimiento = $fechaVencimiento->format('d/m/Y');
                    $diasAtraso = $fechaActual->diffInDays($fechaVencimiento);

                    if ($diasAtraso >= 1 && $diasAtraso <= 6) {
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
                } else {
                    $diasAtraso = 0;
                    $tramo = null;
                }

                if ($cuotasVencidas == 0 && $moraTotal > 0) {
                    $tramo = 5;
                }

                // Fecha último pago
                $ultimoPago = $prestamo->latestOperation;
                $fechaUltimoPago = $ultimoPago ? Carbon::parse($ultimoPago->fecha) : null;
                $diasDesdeUltimoPago = $fechaUltimoPago ? $fechaUltimoPago->diffInDays(now()) : 999;
                $estaActivo = $fechaUltimoPago && $diasDesdeUltimoPago <= 21;
                $tieneMora = $moraTotal > 0;

                // Estado crediticio
                $ultimaCuotaOrdenada = $todasLasCuotasOrdenadas->last();
                $creditoVencido = false;
                if ($ultimaCuotaOrdenada) {
                    $fechaUltimaCuota = Carbon::parse(
                        ($esConvenio && $tipoConvenio !== 'flexible')
                        ? $ultimaCuotaOrdenada->fecha_vencimiento
                        : $ultimaCuotaOrdenada->fecha_pago
                    )->startOfDay();
                    $creditoVencido = $fechaUltimaCuota->lt($fechaActual);
                }

                $estado = 'DESCONOCIDO';
                if ($creditoVencido) {
                    $estado = $estaActivo ? 'CREDITO VENCIDO/ACTIVO' : 'CREDITO VENCIDO/INACTIVO';
                } elseif ($cuotasVencidas == 0 && $tieneMora) {
                    $estado = $estaActivo ? 'EN MORA/ACTIVA' : 'EN MORA/INACTIVA';
                } else {
                    $estado = $estaActivo ? 'ACTIVO' : 'INACTIVO';
                }

                // Ubicación
                $zonaNombre = 'N/A';
                $sucursalNombre = 'N/A';
                $sucursalId = null;

                if ($cliente->persona && $cliente->persona->direcciones->isNotEmpty()) {
                    $dirPersona = $cliente->persona->direcciones->firstWhere('tipo_direccion', 'principal')
                        ?? $cliente->persona->direcciones->first();
                    if ($dirPersona && $dirPersona->sucursal) {
                        $sucursalNombre = $dirPersona->sucursal->sucursal;
                        $sucursalId = $dirPersona->sucursal->id;
                    }
                }

                if ($sucursalId === null) {
                    $dirCobro = $prestamo->direccionCobro ?? null;
                    if ($dirCobro && $dirCobro->sucursal) {
                        $sucursalNombre = $dirCobro->sucursal->sucursal;
                        $sucursalId = $dirCobro->sucursal->id;
                    }
                }

                if ($sucursalId !== null && isset($zonaSucursalMap[$sucursalId])) {
                    $zonaNombre = $zonaSucursalMap[$sucursalId];
                }

                // Carteras
                $carteraJcc = $prestamo->carterasJcc->first();
                $carteraAsesor = $prestamo->carterasAsesor->first();
                $carteraAnalista = $prestamo->carterasAnalista->first();

                $nombrePersonal = null;
                if ($carteraAnalista && $carteraAnalista->analista) {
                    $nombrePersonal = $carteraAnalista->analista->codigo ?? 'N/A';
                } elseif ($carteraAsesor && $carteraAsesor->asesor) {
                    $nombrePersonal = $carteraAsesor->asesor->codigo ?? 'N/A';
                } elseif ($carteraJcc && $carteraJcc->jcc) {
                    $nombrePersonal = $carteraJcc->jcc->codigo ?? 'N/A';
                }

                $nombreCompleto = trim(
                    ($cliente->persona->nombres ?? '') . ' ' .
                    ($cliente->persona->ape_pat ?? '') . ' ' .
                    ($cliente->persona->ape_mat ?? '')
                );

                sort($numerosCuotasVencidas);

                $tipoMostrar = $esConvenio ? 'Convenio' : 'Préstamo';

                return [
                    'id' => $cliente->id,
                    'prestamo_id' => $prestamo->id,
                    'numero' => 0,
                    'dni' => $cliente->persona->documento ?? 'N/A',
                    'nombre' => $nombreCompleto,
                    'cliente_nombre' => $nombreCompleto,
                    'zona' => $zonaNombre ?? 'N/A',
                    'sucursal' => $sucursalNombre ?? 'N/A',
                    'tipo' => $tipoMostrar,
                    'codigo_prestamo' => $prestamo->codigo ?? 'N/A',
                    'monto_desembolsado' => $prestamo->cantidad_solicitada ?? 0,
                    'creditoTotal' => $montoTotalCuotas,
                    'montoPagado' => $montoPagado,
                    'deudaReal' => $deudaReal,
                    'montoCuota' => $montoCuota,
                    'cuotasPagadas' => $cuotasPagadas,
                    'cuotasTotal' => $cuotasTotal,
                    'cuotas_totales' => $cuotasTotal,
                    'cuotas_pagadas' => $cuotasPagadas,
                    'cuotas_no_pagadas' => $cuotasNoPagadas,
                    'cuotasVencidas' => $cuotasVencidas,
                    'cuotas_vencidas' => $cuotasVencidas,
                    'cuotasNoPagadas' => $cuotasNoPagadas,
                    'numerosCuotasVencidas' => $numerosCuotasVencidas,
                    'tramo' => $tramo,
                    'estado' => $estado,
                    'moraAcumulada' => $moraTotal,
                    'diasAtraso' => $diasAtraso,
                    'personal' => $nombrePersonal ?? 'N/A',
                    'fechaUltimoPago' => $fechaUltimoPago ? $fechaUltimoPago->format('d/m/Y') : 'Sin pagos',
                    'fechaUltimoPagoCuota' => $prestamo->latestCuotaPayment ? Carbon::parse($prestamo->latestCuotaPayment->fecha)->format('d/m/Y') : null,
                    'fechaUltimoPagoMora' => $prestamo->latestMoraPayment ? Carbon::parse($prestamo->latestMoraPayment->fecha)->format('d/m/Y') : null,
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

            // Filtrar por estados crediticios seleccionados
            if ($request->filled('estado')) {
                $estadosSeleccionados = $request->input('estado');
                $estadosSeleccionados = is_array($estadosSeleccionados) ? $estadosSeleccionados : [$estadosSeleccionados];
                $resultado = $resultado->filter(function ($item) use ($estadosSeleccionados) {
                    return in_array($item['estado'], $estadosSeleccionados);
                })->values();
            }

            // PAGINACIÓN
            $totalRegistros = $resultado->count();
            $totalCredito = $resultado->sum('creditoTotal');
            $totalMora = $resultado->sum('moraAcumulada');
            $totalDeuda = $resultado->sum('deudaReal');
            $atrasoPromedio = $resultado->avg('diasAtraso');

            $exportAll = $request->input('export_all', false);

            if ($exportAll) {
                $resultadoPaginado = $resultado;
                $page = 1;
                $perPage = $totalRegistros;
                $offset = 0;
            } else {
                $page = $request->input('page', 1);
                $perPage = $request->input('per_page', 50);
                $offset = ($page - 1) * $perPage;
                $resultadoPaginado = $resultado->slice($offset, $perPage)->values();
            }

            $resultadoPaginado = $resultadoPaginado->map(function ($item, $index) use ($offset) {
                $item['numero'] = $offset + $index + 1;
                return $item;
            })->values();

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            return response()->json([
                'success' => true,
                'data' => $resultadoPaginado,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalRegistros,
                    'last_page' => ceil($totalRegistros / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $totalRegistros)
                ],
                'totales' => [
                    'count' => $totalRegistros,
                    'credito' => $totalCredito,
                    'mora' => $totalMora,
                    'deuda' => $totalDeuda,
                    'atraso_promedio' => $atrasoPromedio,
                ],
                'performance' => [
                    'execution_time' => $executionTime,
                    'cuotas_procesadas' => $cuotas->count() ?? 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $startTime = microtime(true);

            // USAR SCOPES OPTIMIZADOS DEL MODELO
            $query = Cuota::query()
                ->whereIn('estado', [
                    CuotaEstado::PENDIENTE->value,
                    CuotaEstado::PARCIAL->value,
                    CuotaEstado::VENCIDO->value
                ])
                ->conRelacionesOptimizadas() // Scope con eager loading mínimo
                ->whereNotNull('prestamo_id') // Asegurar que tenga préstamo
                ->where('fecha_pago', '<=', Carbon::today()) // CUOTAS VENCIDAS + QUE VENCEN HOY
                // Excluir cuotas de préstamos finalizados, liquidados, anulados, cancelados o pagados
                ->whereHas('prestamo', function ($q) {
                    $q->whereNotIn('estado', [
                        'Finalizado',
                        'finalizado',
                        'Liquidado',
                        'liquidado',
                        'Anulado',
                        'anulado',
                        'Cancelado',
                        'cancelado',
                        'Pagado',
                    ])
                        // Excluir préstamos con estado 'Con Convenio' sin convenio activo
                        ->where(function ($sub) {
                            $sub->where('estado', '!=', 'Con Convenio')
                                ->orWhereHas('convenios', function ($c) {
                                    $c->where('estado', \App\Enums\ConvenioEstado::ACTIVO->value);
                                });
                        })
                        // Excluir préstamos cuyo convenio fue CUMPLIDO (deuda saldada)
                        ->whereDoesntHave('convenios', function ($c) {
                            $c->where('estado', \App\Enums\ConvenioEstado::CUMPLIDO->value);
                        });
                });


            // ===== FILTRO POR TIPO (CONVENIOS/PRÉSTAMOS/AMBOS) =====
            $tipo = $request->input('tipo', 'ambos');

            $esConsultaConvenios = ($tipo === 'convenios');

            // IMPORTANTE: Si es tipo "prestamos", EXCLUIR préstamos que tienen convenios
            if ($tipo === 'prestamos') {
                // TEMPORAL: Comentado por problemas de performance (whereDoesntHave escanea toda la tabla)
                // TODO: Optimizar con índices o reescribir con LEFT JOIN
                // $query->sinConveniosActivos();
            }
            // Si es "ambos", mostrar ambos tipos (préstamos sin convenio + convenios)
            // Si es "convenios", se manejará más abajo con consulta especial

            // FILTRO POR TIPO DE DEUDA (CUOTAS O MORAS)
            $tipoDeuda = $request->input('tipo_deuda', 'cuotas');
            if ($tipoDeuda === 'moras') {
                // Solo mostrar cuotas que tengan moras pendientes
                $query->whereHas('moras', function ($q) {
                    $q->whereIn('estado', [
                        MoraCuotaEstado::PENDIENTE->value,
                        MoraCuotaEstado::PARCIAL->value
                    ]);
                });
            }

            // FILTRO POR ESTADO DEL PRÉSTAMO
            $estadoPrestamo = $request->input('estado_prestamo', 'todos');
            if ($estadoPrestamo === 'vigente') {
                // Préstamos vigentes: aún tienen cuotas futuras por vencer
                $query->whereHas('prestamo', function ($q) {
                    $q->whereHas('cuotas', function ($subQ) {
                        $subQ->where('fecha_pago', '>=', Carbon::today())
                            ->whereIn('estado', [
                                CuotaEstado::PENDIENTE->value,
                                CuotaEstado::PARCIAL->value
                            ]);
                    });
                });
            } elseif ($estadoPrestamo === 'vencido') {
                // Préstamos vencidos: última cuota ya venció
                $query->whereHas('prestamo', function ($q) {
                    $q->whereDoesntHave('cuotas', function ($subQ) {
                        $subQ->where('fecha_pago', '>=', Carbon::today())
                            ->whereIn('estado', [
                                CuotaEstado::PENDIENTE->value,
                                CuotaEstado::PARCIAL->value
                            ]);
                    });
                });
            }

            // FILTRO POR CARTERAS - USANDO SCOPES OPTIMIZADOS
            $query->porCarteras(
                $request->input('jcc_id'),
                $request->input('asesor_id'),
                $request->input('analista_id')
            );

            // FILTRO POR UBICACIÓN - USANDO SCOPES OPTIMIZADOS
            // DEBUG: Ver qué sucursales se están recibiendo
            $sucursalIdDebug = $request->input('sucursal_id');
            \Log::info('🔍 DEBUG Sucursales recibidas en controlador:', [
                'tipo' => gettype($sucursalIdDebug),
                'valor' => $sucursalIdDebug,
                'es_array' => is_array($sucursalIdDebug),
                'count' => is_array($sucursalIdDebug) ? count($sucursalIdDebug) : 'N/A'
            ]);

            $query->porUbicacion(
                $request->input('zona_id'),
                $request->input('sucursal_id')
            );

            // FILTROS DE FECHA DE VENCIMIENTO - USANDO SCOPES
            // Primero procesar el tipo de rango de fecha
            $tipoRangoFecha = $request->input('tipo_rango_fecha');
            $fechaDesde = null;
            $fechaHasta = null;

            if ($tipoRangoFecha === 'dia' && $request->filled('fecha_dia')) {
                // Por día: buscar solo ese día
                $fechaDia = Carbon::createFromFormat('d/m/Y', $request->input('fecha_dia'))->startOfDay();
                $fechaDesde = $fechaDia;
                $fechaHasta = $fechaDia->copy()->endOfDay();
            } elseif ($tipoRangoFecha === 'mes' && $request->filled('fecha_mes')) {
                // Por mes: buscar todo el mes
                $fechaMes = Carbon::createFromFormat('m/Y', $request->input('fecha_mes'))->startOfMonth();
                $fechaDesde = $fechaMes;
                $fechaHasta = $fechaMes->copy()->endOfMonth();
            } elseif ($tipoRangoFecha === 'entre_fechas') {
                // Entre fechas: usar las fechas desde y hasta
                if ($request->filled('vencimiento_desde')) {
                    $fechaDesde = $request->input('vencimiento_desde');
                    // Si viene en formato dd/mm/yyyy, convertir
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaDesde)) {
                        $fechaDesde = Carbon::createFromFormat('d/m/Y', $fechaDesde)->startOfDay();
                    }
                }
                if ($request->filled('vencimiento_hasta')) {
                    $fechaHasta = $request->input('vencimiento_hasta');
                    // Si viene en formato dd/mm/yyyy, convertir
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaHasta)) {
                        $fechaHasta = Carbon::createFromFormat('d/m/Y', $fechaHasta)->endOfDay();
                    }
                }
            } else {
                // Fallback a los campos originales si no hay tipo_rango_fecha
                $fechaDesde = $request->input('vencimiento_desde');
                $fechaHasta = $request->input('vencimiento_hasta');
            }

            $query->porFechaVencimiento($fechaDesde, $fechaHasta);

            // FILTRO POR DÍAS DE MORA - USANDO SCOPES
            $query->porDiasMora(
                $request->input('dias_mora_min'),
                $request->input('dias_mora_max')
            );

            // NUEVO: FILTRO POR DÍAS DE ATRASO (INCLUSO SIN MORA GENERADA)
            if ($request->filled('dias_atraso_min')) {
                $diasAtraso = $request->input('dias_atraso_min');
                $fechaLimite = Carbon::now()->subDays($diasAtraso)->endOfDay();
                $query->where('fecha_pago', '<=', $fechaLimite);
            }

            if ($request->filled('dias_atraso_max')) {
                $diasAtraso = $request->input('dias_atraso_max');
                $fechaLimite = Carbon::now()->subDays($diasAtraso)->startOfDay();
                $query->where('fecha_pago', '>=', $fechaLimite);
            }

            // FILTRO POR CANTIDAD DE CUOTAS VENCIDAS - EXACTO
            // IMPORTANTE: Este filtro solo aplica a PRÉSTAMOS, no a convenios
            if ($request->filled('cuotas_vencidas')) {
                $cuotasVencidas = $request->input('cuotas_vencidas');

                // Forzar tipo a 'prestamos' para evitar incluir convenios
                $tipo = 'prestamos';

                $prestamosConCuotasExactas = Cuota::select('prestamo_id')
                    ->where('fecha_pago', '<', Carbon::today())
                    ->whereIn('estado', [CuotaEstado::PENDIENTE->value, CuotaEstado::PARCIAL->value, CuotaEstado::VENCIDO->value])
                    ->groupBy('prestamo_id')
                    ->havingRaw('COUNT(*) = ?', [$cuotasVencidas])  // Cambiado de >= a =
                    ->pluck('prestamo_id');

                $query->whereIn('prestamo_id', $prestamosConCuotasExactas);
            }

            // FILTRO POR COMPROMISOS
            if ($request->has('tiene_compromiso')) {
                $tieneCompromiso = $request->input('tiene_compromiso');
                if ($tieneCompromiso === '1') {
                    $query->whereHas('prestamo.compromisos', function ($q) {
                        $q->where('estado', '!=', \App\Models\Compromiso::ESTADO_POSTERGADO);
                    });
                } elseif ($tieneCompromiso === '0') {
                    $query->whereDoesntHave('prestamo.compromisos', function ($q) {
                        $q->where('estado', '!=', \App\Models\Compromiso::ESTADO_POSTERGADO);
                    });
                }
            }

            // FILTRO POR GESTIONES
            if ($request->has('tiene_gestion')) {
                $tieneGestion = $request->input('tiene_gestion');
                if ($tieneGestion === '1') {
                    $query->whereHas('prestamo.gestiones');
                } elseif ($tieneGestion === '0') {
                    $query->whereDoesntHave('prestamo.gestiones');
                }
            }

            // MODO DE CONSULTA ESPECIAL - TODAS LAS DEUDAS
            if ($request->filled('modo_consulta') && $request->input('modo_consulta') === 'todas_deudas') {
                $query->where(function ($subQuery) {
                    $subQuery->where('fecha_pago', '<', Carbon::today())
                        ->orWhereHas('moras', function ($q) {
                            $q->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value, MoraCuotaEstado::REGULARIZADA->value]);
                        });
                });
            }

            // BÚSQUEDA POR CLIENTE - USANDO SCOPE OPTIMIZADO
            if ($request->filled('search')) {
                $query->buscarCliente($request->input('search'));
            }

            // EJECUTAR CONSULTA CON LÍMITE
            $startTime = microtime(true);

            // LÍMITE DE REGISTROS (1000 cuotas para obtener ~100 clientes después de agrupar)
            $limit = $request->input('limit', 1000);

            if ($esConsultaConvenios) {
                // SOLO CONVENIOS: Consultar tabla cuotas_convenio
                $cuotas = $this->obtenerCuotasConvenios($request, $limit);
            } elseif ($tipo === 'ambos') {
                // Consultar todas las cuotas para agrupar y paginar correctamente

                $cuotas = $query->orderBy('fecha_pago', 'asc')->get();
            } else {
                // SOLO PRESTAMOS: traer todas las cuotas
                $cuotas = $query->orderBy('fecha_pago', 'asc')->get();
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // VALIDAR Y AGRUPAR CUOTAS
            $cuotasValidas = $cuotas->filter(function ($cuota) {
                $esValida = $cuota->prestamo &&
                    $cuota->prestamo->cliente &&
                    $cuota->prestamo->cliente->persona;

                if (!$esValida) {
                    \Log::warning('Cuota con relaciones inválidas', [
                        'cuota_id' => $cuota->id,
                        'tiene_prestamo' => !is_null($cuota->prestamo),
                        'tiene_cliente' => $cuota->prestamo && !is_null($cuota->prestamo->cliente),
                        'tiene_persona' => $cuota->prestamo && $cuota->prestamo->cliente && !is_null($cuota->prestamo->cliente->persona)
                    ]);
                }

                return $esValida;
            });


            // AGRUPAR POR CLIENTE
            $cuotasAgrupadas = $cuotasValidas->groupBy(function ($cuota) {
                return $cuota->prestamo->cliente->id;
            })->map(function ($cuotasCliente) {
                $primeraCuota = $cuotasCliente->first();
                $cliente = $primeraCuota->prestamo->cliente;
                $prestamo = $primeraCuota->prestamo;

                // CALCULAR TOTALES
                $montoTotal = 0;
                $moraTotal = 0;
                $diasMoraMax = 0;

                foreach ($cuotasCliente as $cuota) {
                    $montoTotal += $cuota->monto;

                    // Calcular saldo de moras
                    $saldoMorasCuota = 0;
                    foreach ($cuota->moras as $mora) {
                        $saldoMorasCuota += ($mora->monto - ($mora->monto_pagado ?? 0));
                    }
                    $cuota->moras_calculadas = $saldoMorasCuota;
                    $moraTotal += $saldoMorasCuota;

                    // Encontrar días de mora máximos
                    $diasMoraMaxCuota = $cuota->moras->max('dias_mora') ?? 0;
                    if ($diasMoraMaxCuota > $diasMoraMax) {
                        $diasMoraMax = $diasMoraMaxCuota;
                    }
                }

                // Calcular moras pendientes de cuotas ya pagadas ANTERIORES a la primera cuota no pagada
                // Usa EXACTAMENTE la misma lógica de estado_cuenta_detallado.blade.php
                $moraAcumuladaAnterior = 0;
                $todasCuotasPrestamo = $prestamo->cuotas ? $prestamo->cuotas->sortBy('numero') : collect();

                // Encontrar el número de la primera cuota no pagada
                $primeraCuotaNoPagadaNumero = PHP_INT_MAX; // Si no hay no pagadas, todas son anteriores
                foreach ($todasCuotasPrestamo as $cuota) {
                    $estadoVal = $cuota->estado instanceof \BackedEnum
                        ? $cuota->estado->value
                        : (int) $cuota->estado;
                    if ($estadoVal !== CuotaEstado::PAGADO->value) {
                        $primeraCuotaNoPagadaNumero = $cuota->numero;
                        break;
                    }
                }

                // Sumar moras no pagadas de cuotas pagadas anteriores a la primera no pagada
                // Lógica idéntica a estado_cuenta_detallado.blade.php
                foreach ($todasCuotasPrestamo as $cuotaPagada) {
                    $estadoVal = $cuotaPagada->estado instanceof \BackedEnum
                        ? $cuotaPagada->estado->value
                        : (int) $cuotaPagada->estado;

                    if ($estadoVal === CuotaEstado::PAGADO->value && $cuotaPagada->numero < $primeraCuotaNoPagadaNumero) {
                        // Solo contar moras PENDIENTE (0) o PARCIAL (1), NO pagadas
                        $totalMorasCuota = 0;
                        foreach ($cuotaPagada->moras as $mora) {
                            $estadoMora = $mora->estado instanceof \BackedEnum
                                ? $mora->estado->value
                                : (int) $mora->estado;

                            // Solo contar moras en estado PENDIENTE (0) o PARCIAL (1)
                            if ($estadoMora === MoraCuotaEstado::PENDIENTE->value || $estadoMora === MoraCuotaEstado::PARCIAL->value) {
                                $montoPendienteMora = $mora->monto - ($mora->monto_pagado ?? 0);
                                $totalMorasCuota += $montoPendienteMora;
                            }
                        }

                        // Restar abonos a favor de esta cuota
                        $abonoFavorCuota = $cuotaPagada->saldo_mora_favor ?? 0;
                        $moraNoPagadaCuota = $totalMorasCuota - $abonoFavorCuota;

                        if ($moraNoPagadaCuota > 0) {
                            $moraAcumuladaAnterior += $moraNoPagadaCuota;
                        }
                    }
                }

                $deudaTotal = $montoTotal + $moraTotal + $moraAcumuladaAnterior;

                // NOMBRE COMPLETO
                $nombreCompleto = trim(
                    ($cliente->persona->nombres ?? '') . ' ' .
                    ($cliente->persona->ape_pat ?? '') . ' ' .
                    ($cliente->persona->ape_mat ?? '')
                );

                if (empty($nombreCompleto)) {
                    $nombreCompleto = 'Cliente #' . $cliente->id;
                }

                // SUCURSAL Y ZONA - Priorizar dirección principal del cliente
                $zonaNombre = null;
                $sucursalNombre = null;
                $direccionTexto = null;
                $referenciaTexto = null;

                // Primero intentar con la dirección principal del cliente
                if ($cliente->persona && $cliente->persona->direcciones->isNotEmpty()) {
                    $direccion = $cliente->persona->direcciones->firstWhere('tipo_direccion', 'principal')
                        ?? $cliente->persona->direcciones->first();
                    $sucursal = $direccion->sucursal ?? null;

                    // Primero intentar obtener la zona directamente desde la dirección
                    $zona = $direccion->zona ?? null;

                    // Si no hay zona en la dirección, intentar obtener desde la sucursal (relación many-to-many)
                    if (!$zona && $sucursal && $sucursal->zonas && $sucursal->zonas->isNotEmpty()) {
                        $zona = $sucursal->zonas->first();
                    }

                    // Extraer los valores como strings
                    $zonaNombre = $zona ? $zona->nombre : null;
                    $sucursalNombre = $sucursal ? $sucursal->sucursal : null;
                    $direccionTexto = trim(($direccion->direccion ?? '') . ' ' . ($direccion->numero ?? ''));
                    $referenciaTexto = $direccion->referencia ?? null;
                }

                // Fallback a dirección de cobro del préstamo si el cliente no tiene direcciones
                if (!$direccionTexto && $prestamo && $prestamo->direccion_cobro_id && $prestamo->direccionCobro) {
                    $direccionCobro = $prestamo->direccionCobro;
                    $direccionTexto = trim(($direccionCobro->direccion ?? '') . ' ' . ($direccionCobro->numero ?? ''));
                    $referenciaTexto = $direccionCobro->referencia ?? null;

                    $sucursal = $direccionCobro->sucursal ?? null;
                    $zona = $direccionCobro->zona ?? null;

                    if (!$zona && $sucursal && $sucursal->zonas && $sucursal->zonas->isNotEmpty()) {
                        $zona = $sucursal->zonas->first();
                    }

                    $zonaNombre = $zona ? $zona->nombre : null;
                    $sucursalNombre = $sucursal ? $sucursal->sucursal : null;
                }

                // Log si no se pudo obtener ubicación
                if (!$zonaNombre && !$sucursalNombre) {
                    \Log::warning('Sin ubicación para préstamo', [
                        'prestamo_id' => $prestamo->id,
                        'tiene_direccion_cobro_id' => !is_null($prestamo->direccion_cobro_id),
                        'direccion_cobro_cargada' => !is_null($prestamo->direccionCobro),
                        'tiene_direcciones_cliente' => $cliente->persona && $cliente->persona->direcciones->isNotEmpty()
                    ]);
                }

                // ESTADO CREDITICIO
                // LÓGICA (21 días = plazo desde fecha de vencimiento de primera cuota vencida):
                //   CREDITO VENCIDO = última cuota del cronograma ya venció (todo el cronograma expiró)
                //     CREDITO VENCIDO/ACTIVO   = primera cuota vencida tiene ≤21 días de atraso
                //     CREDITO VENCIDO/INACTIVO = primera cuota vencida tiene >21 días de atraso
                //   EN MORA = sin cuotas vencidas pero con mora pendiente
                //     EN MORA/ACTIVA   = mora con ≤21 días
                //     EN MORA/INACTIVA = mora con >21 días
                //   ACTIVO/INACTIVO = cronograma vigente (aún tiene cuotas futuras)
                //     ACTIVO   = primera cuota vencida tiene ≤21 días de atraso
                //     INACTIVO = primera cuota vencida tiene >21 días de atraso
                $fechaActual = Carbon::now()->startOfDay();
                $tieneMora = $moraTotal > 0;

                // Buscar la PRIMERA cuota vencida (no pagada, fecha ya pasó) ordenada por fecha
                $primeraCuotaVencida = $cuotasCliente->filter(function ($cuota) use ($fechaActual) {
                    $estado = $cuota->estado;
                    $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
                    $noPagada = in_array($estadoValor, [0, 1, 3]);
                    $fechaVenc = Carbon::parse($cuota->fecha_pago)->startOfDay();
                    return $noPagada && $fechaVenc->lt($fechaActual);
                })->sortBy('fecha_pago')->first();

                $cuotasVencidasCount = $primeraCuotaVencida ? $cuotasCliente->filter(function ($cuota) use ($fechaActual) {
                    $estado = $cuota->estado;
                    $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
                    $noPagada = in_array($estadoValor, [0, 1, 3]);
                    $fechaVenc = Carbon::parse($cuota->fecha_pago)->startOfDay();
                    return $noPagada && $fechaVenc->lt($fechaActual);
                })->count() : 0;

                // ACTIVO/INACTIVO: basado en días desde la fecha de vencimiento de la primera cuota vencida
                $diasAtrasoVencimiento = 999;
                if ($primeraCuotaVencida) {
                    $fechaVencPrimera = Carbon::parse($primeraCuotaVencida->fecha_pago)->startOfDay();
                    $diasAtrasoVencimiento = $fechaActual->diffInDays($fechaVencPrimera);
                }
                $estaActivo = $diasAtrasoVencimiento <= 21;

                // Crédito vencido: la ÚLTIMA cuota del préstamo completo ya venció
                // CORRECCIÓN: usa cuotas ya eager-loaded en vez de queries adicionales N+1
                $todasCuotasPrestamo = $prestamo->cuotas ?? collect();
                $ultimaCuotaPrestamo = $todasCuotasPrestamo->sortByDesc('fecha_pago')->first();
                $primeraCuotaPrestamo = $todasCuotasPrestamo->sortBy('fecha_pago')->first();
                $creditoVencido = false;
                if ($ultimaCuotaPrestamo) {
                    $fechaUltimaCuota = Carbon::parse($ultimaCuotaPrestamo->fecha_pago)->startOfDay();
                    $creditoVencido = $fechaUltimaCuota->lt($fechaActual);
                }

                $estadoCrediticio = 'DESCONOCIDO';
                if ($creditoVencido) {
                    $estadoCrediticio = $estaActivo ? 'CREDITO VENCIDO/ACTIVO' : 'CREDITO VENCIDO/INACTIVO';
                } elseif ($cuotasVencidasCount == 0 && $tieneMora) {
                    $estadoCrediticio = $estaActivo ? 'EN MORA/ACTIVA' : 'EN MORA/INACTIVA';
                } else {
                    $estadoCrediticio = $estaActivo ? 'ACTIVO' : 'INACTIVO';
                }

                // GESTIONES Y COMPROMISOS
                $ultimaGestion = $prestamo->gestiones->first();
                $ultimoCompromiso = $prestamo->compromisos->first();

                // CARTERAS
                $carteraJcc = $prestamo->carterasJcc->first();
                $carteraAsesor = $prestamo->carterasAsesor->first();
                $carteraAnalista = $prestamo->carterasAnalista->first();

                // NOMBRES Y CÓDIGOS DE CARTERAS
                $nombreJcc = null;
                $nombreAsesor = null;
                $nombreAnalista = null;
                $codigoJcc = null;
                $codigoAsesor = null;
                $codigoAnalista = null;

                if ($carteraJcc && $carteraJcc->jcc) {
                    $nombreJcc = $carteraJcc->jcc->persona->nombres ?? $carteraJcc->jcc->name ?? 'N/A';
                    $codigoJcc = $carteraJcc->jcc->codigo ?? null;
                }

                if ($carteraAsesor && $carteraAsesor->asesor) {
                    $nombreAsesor = $carteraAsesor->asesor->persona->nombres ?? $carteraAsesor->asesor->name ?? 'N/A';
                    $codigoAsesor = $carteraAsesor->asesor->codigo ?? null;
                }

                if ($carteraAnalista && $carteraAnalista->analista) {
                    $nombreAnalista = $carteraAnalista->analista->persona->nombres ?? $carteraAnalista->analista->name ?? 'N/A';
                    $codigoAnalista = $carteraAnalista->analista->codigo ?? null;
                }

                // Fecha inicio y fin: primera y última cuota del préstamo (ya en memoria)
                $fechaInicio = $primeraCuotaPrestamo ? Carbon::parse($primeraCuotaPrestamo->fecha_pago)->format('d/m/Y') : '---';
                $fechaFin = $ultimaCuotaPrestamo ? Carbon::parse($ultimaCuotaPrestamo->fecha_pago)->format('d/m/Y') : '---';

                return [
                    'cliente' => $cliente,
                    'nombre_completo' => $nombreCompleto,
                    'cuotas' => $cuotasCliente->sortBy('fecha_pago'),
                    'total_cuotas' => $cuotasCliente->count(),
                    'monto_total' => $montoTotal,
                    'mora_total' => $moraTotal,
                    'mora_acumulada_anterior' => $moraAcumuladaAnterior,
                    'deuda_total' => $deudaTotal,
                    'sucursal' => $sucursalNombre,
                    'zona' => $zonaNombre,
                    'direccion' => $direccionTexto,
                    'referencia' => $referenciaTexto,
                    'dias_mora_max' => $diasMoraMax,
                    'estado' => $estadoCrediticio,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'ultima_gestion' => $ultimaGestion,
                    'ultimo_compromiso' => $ultimoCompromiso,
                    'jcc_nombre' => $nombreJcc,
                    'jcc_codigo' => $codigoJcc,
                    'asesor_nombre' => $nombreAsesor,
                    'asesor_codigo' => $codigoAsesor,
                    'analista_nombre' => $nombreAnalista,
                    'analista_codigo' => $codigoAnalista,
                ];
            })->sortBy('nombre_completo')->values(); // values() para reindexar


            // FILTRO POR TRAMO (después de agrupar) - soporta múltiples tramos
            if ($request->filled('tramo')) {
                $tramoInput = $request->input('tramo');
                $tramosFiltro = is_array($tramoInput) ? array_map('intval', $tramoInput) : [(int) $tramoInput];

                $cuotasAgrupadas = $cuotasAgrupadas->filter(function ($datos) use ($tramosFiltro) {
                    // Calcular el tramo del cliente
                    $cuotasOrdenadas = $datos['cuotas']->sortBy('fecha_pago');

                    $primeraCuotaNoPagada = $cuotasOrdenadas->first(function ($cuota) {
                        $estado = $cuota->estado;
                        $estadoValor = $estado instanceof \BackedEnum ? $estado->value : (is_numeric($estado) ? (int) $estado : null);
                        return in_array($estadoValor, [0, 1, 3]); // Pendiente, Parcial, Vencido
                    });

                    if (!$primeraCuotaNoPagada) {
                        return false; // Sin cuotas vencidas = no mostrar
                    }

                    $fechaVencimiento = \Carbon\Carbon::parse($primeraCuotaNoPagada->fecha_pago);
                    $diasAtraso = $fechaVencimiento->diffInDays(now(), false);

                    if ($diasAtraso < 1) {
                        return false; // Sin atraso o fecha futura = no mostrar
                    }

                    // Determinar el tramo (desde 1 día de atraso)
                    if ($diasAtraso >= 1 && $diasAtraso <= 6) {
                        $tramoCliente = 0;
                    } elseif ($diasAtraso <= 14) {
                        $tramoCliente = 1;
                    } elseif ($diasAtraso <= 21) {
                        $tramoCliente = 2;
                    } elseif ($diasAtraso <= 30) {
                        $tramoCliente = 3;
                    } else {
                        $tramoCliente = 4;
                    }

                    return in_array($tramoCliente, $tramosFiltro);
                })->values();
            }

            // CALCULAR TOTALES
            $totalMonto = $cuotasAgrupadas->sum('monto_total');
            $totalMora = $cuotasAgrupadas->sum('mora_total');
            $totalDeuda = $cuotasAgrupadas->sum('deuda_total');
            $totalClientes = $cuotasAgrupadas->count();

            // PAGINACIÓN - Mantener como Collection para preservar datos
            $perPage = $request->input('per_page', 100); // 100 clientes por página (aumentado para mejor visualización)
            $currentPage = $request->input('page', 1);

            // Slice de la colección directamente (sin convertir a array)
            $cuotasAgrupadasSlice = $cuotasAgrupadas->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $cuotasAgrupadasPaginadas = new \Illuminate\Pagination\LengthAwarePaginator(
                $cuotasAgrupadasSlice,
                $totalClientes,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // CACHEAR DATOS PARA FILTROS (optimizado)
            $jccs = cache()->remember('deudas_jccs_v3', 7200, function () {
                return User::role('JCC')
                    ->where('status', 1)
                    ->with('persona:id,nombres,ape_pat')
                    ->get(['id', 'codigo', 'persona_id']);
            });

            $asesores = cache()->remember('deudas_asesores_v3', 7200, function () {
                return User::role('Asesor')
                    ->where('status', 1)
                    ->with('persona:id,nombres,ape_pat')
                    ->get(['id', 'codigo', 'persona_id']);
            });

            $analistas = cache()->remember('deudas_analistas_v3', 7200, function () {
                return User::role('Analista')
                    ->where('status', 1)
                    ->with('persona:id,nombres,ape_pat')
                    ->get(['id', 'codigo', 'persona_id']);
            });

            $zonas = cache()->remember('deudas_zonas_v4', 7200, function () {
                return Zona::select('id', 'nombre')
                    ->orderBy('nombre', 'asc')
                    ->get();
            });

            // Verificar que las zonas se cargaron correctamente
            if ($zonas->isEmpty()) {
                \Log::warning('No se encontraron zonas en la base de datos');
                // Intentar cargar sin caché
                $zonas = Zona::select('id', 'nombre')
                    ->orderBy('nombre', 'asc')
                    ->get();
            }

            $sucursales = cache()->remember('deudas_sucursales_v4', 7200, function () {
                return Sucursal::select('id', 'sucursal')
                    ->orderBy('sucursal', 'asc')
                    ->get();
            });

            // Verificar que las sucursales se cargaron correctamente
            if ($sucursales->isEmpty()) {
                \Log::warning('No se encontraron sucursales en la base de datos');
                // Intentar cargar sin caché
                $sucursales = Sucursal::select('id', 'sucursal')
                    ->orderBy('sucursal', 'asc')
                    ->get();
            }

            // EXPORTACIÓN
            if ($request->has('export')) {
                // Para Excel, usar los datos agrupados completos (sin paginación)
                return $this->exportData($cuotas, $cuotasAgrupadas, $request->input('export'), [
                    'totalMonto' => $totalMonto,
                    'totalMora' => $totalMora,
                    'totalDeuda' => $totalDeuda,
                ], $request);
            }

            // RESPUESTA AJAX
            if ($request->ajax()) {

                return view('admin.Deudas.table_grouped', [
                    'cuotasAgrupadas' => $cuotasAgrupadasPaginadas,
                    'totalMonto' => $totalMonto,
                    'totalMora' => $totalMora,
                    'totalDeuda' => $totalDeuda,
                    'totalClientes' => $totalClientes
                ]);
            }

            // RESPUESTA NORMAL
            return view('admin.Deudas.index', [
                'cuotasAgrupadas' => $cuotasAgrupadasPaginadas,
                'jccs' => $jccs,
                'asesores' => $asesores,
                'analistas' => $analistas,
                'zonas' => $zonas,
                'sucursales' => $sucursales,
                'totalMonto' => $totalMonto,
                'totalMora' => $totalMora,
                'totalDeuda' => $totalDeuda,
                'totalClientes' => $totalClientes
            ]);
        } catch (\Exception $e) {
            \Log::error('ERROR CRÍTICO en DeudasController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $request->all(),
                'user_id' => auth()->id() ?? 'No autenticado'
            ]);

            if ($request->has('export')) {
                return response()->json([
                    'error' => 'Error al generar el reporte: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Error al cargar los datos: ' . $e->getMessage());
        }
    }

    public function exportData($cuotas, $cuotasAgrupadas, $format, $totales, $request = null)
    {
        try {
            if ($format == 'excel') {
                // Usar los datos agrupados para el reporte de Excel
                return Excel::download(
                    new \App\Exports\DeudasExport($cuotasAgrupadas, $totales, $request),
                    'reporte_deudas_agrupado_' . date('Y-m-d') . '.xlsx'
                );
            } elseif ($format == 'tramos') {
                // Exportar reporte de tramos
                $tramoFiltro = $request ? $request->input('tramo') : null;
                return Excel::download(
                    new \App\Exports\TramosExport($cuotasAgrupadas, $totales, $tramoFiltro, $request),
                    'reporte_tramos_' . date('Y-m-d') . '.xlsx'
                );
            } elseif ($format == 'pdf') {

                // Verificar que tenemos datos
                if ($cuotasAgrupadas->isEmpty()) {
                    \Log::warning('Intento de exportar PDF sin datos');

                    return redirect()->back()->with('error', 'No hay datos para exportar con los filtros aplicados.');
                }

                // Configurar límites para PDFs grandes
                set_time_limit(300); // 5 minutos
                ini_set('memory_limit', '512M'); // Aumentar memoria
                ini_set('max_execution_time', '300');

                // LIMPIAR BUFFER AGRESIVAMENTE
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // ... tu código de preparación de datos existente ...
                $datosConsolidados = $cuotasAgrupadas->map(function ($clienteData) {
                    $cliente = $clienteData['cliente'];
                    $cuotas = $clienteData['cuotas'];

                    $jcc = null;
                    $asesor = null;
                    $analista = null;

                    $primeraCuota = $cuotas->first();
                    if ($primeraCuota && $primeraCuota->prestamo) {
                        $carteraJcc = $primeraCuota->prestamo->carterasJcc->where('estado', 1)->first();
                        $carteraAsesor = $primeraCuota->prestamo->carterasAsesor->where('estado', 1)->first();
                        $carteraAnalista = $primeraCuota->prestamo->carterasAnalista->where('estado', 1)->first();

                        $jcc = $carteraJcc ? $carteraJcc->jcc : null;
                        $asesor = $carteraAsesor ? $carteraAsesor->asesor : null;
                        $analista = $carteraAnalista ? $carteraAnalista->analista : null;
                    }

                    return [
                        'cliente' => $cliente,
                        'cuotas' => $cuotas,
                        'jcc' => $jcc,
                        'asesor' => $asesor,
                        'analista' => $analista,
                        'total_cuotas' => $clienteData['total_cuotas'],
                        'monto_total' => $clienteData['monto_total'],
                        'mora_total' => $clienteData['mora_total'],
                        'mora_acumulada_anterior' => $clienteData['mora_acumulada_anterior'],
                        'deuda_total' => $clienteData['deuda_total'],
                        'sucursal' => $clienteData['sucursal'],
                        'zona' => $clienteData['zona'],
                        'direccion' => $clienteData['direccion'],
                        'referencia' => $clienteData['referencia'],
                        'dias_mora_max' => $clienteData['dias_mora_max'],
                        'estado' => $clienteData['estado'] ?? 'N/A',
                        'fecha_inicio' => $clienteData['fecha_inicio'] ?? '---',
                        'fecha_fin' => $clienteData['fecha_fin'] ?? '---',
                    ];
                });

                // Excluir préstamos que tienen convenios activos
                $prestamosConConvenio = \App\Models\Convenio::where('estado', ConvenioEstado::ACTIVO->value)
                    ->pluck('prestamo_id')
                    ->toArray();

                $datosConsolidados = $datosConsolidados->filter(function ($item) use ($prestamosConConvenio) {
                    $primeraCuota = $item['cuotas']->first();
                    if ($primeraCuota && $primeraCuota->prestamo_id) {
                        return !in_array($primeraCuota->prestamo_id, $prestamosConConvenio);
                    }
                    return true;
                })->values();

                $filtrosAplicados = $this->obtenerFiltrosAplicados($request);

                $datosPdf = [
                    'datosConsolidados' => $datosConsolidados,
                    'totalMonto' => $datosConsolidados->sum('monto_total'),
                    'totalMora' => $datosConsolidados->sum('mora_total'),
                    'totalDeuda' => $datosConsolidados->sum('deuda_total'),
                    'totalClientes' => $datosConsolidados->count(),
                    'totalCuotas' => $datosConsolidados->sum('total_cuotas'),
                    'filtrosAplicados' => $filtrosAplicados,
                    'fechaGeneracion' => now(),
                    'prestamo' => $datosConsolidados->first()['cuotas']->first()->prestamo ?? null,
                ];

                // Limpiar cache
                \Artisan::call('view:clear');

                // ==========================================
                // SOLUCIÓN MEJORADA - SIN PÁGINA EN BLANCO
                // ==========================================

                // 1. CREAR INSTANCIA DIRECTA DE DOMPDF
                $options = new \Dompdf\Options;
                $options->set('defaultFont', 'DejaVu Sans');
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isPhpEnabled', false);
                $options->set('isRemoteEnabled', true);
                $options->set('isJavascriptEnabled', false);
                $options->set('chroot', public_path());
                $options->set('dpi', 72);

                // OPCIONES ESPECÍFICAS PARA EVITAR PÁGINAS EN BLANCO
                $options->set('enable_font_subsetting', false);
                $options->set('fontHeightRatio', 1.0);
                $options->set('isFontSubsettingEnabled', false);
                $options->set('isPhpEnabled', false);

                $dompdf = new \Dompdf\Dompdf($options);

                // 2. GENERAR HTML Y LIMPIAR ESPACIOS
                $html = view('pdf.deudas-general', $datosPdf)->render();

                // LIMPIAR HTML PARA EVITAR PÁGINAS EN BLANCO
                $html = trim($html); // Eliminar espacios al inicio y final
                $html = preg_replace('/^\s+/m', '', $html); // Eliminar espacios al inicio de cada línea
                $html = str_replace(["\r\n", "\r", "\n\n\n"], "\n", $html); // Normalizar saltos de línea

                // VERIFICAR QUE EL HTML EMPIECE CORRECTAMENTE
                if (!str_starts_with(trim($html), '<!DOCTYPE html>')) {
                    \Log::warning('HTML no empieza con DOCTYPE', ['inicio_html' => substr($html, 0, 100)]);
                }

                // 3. CARGAR HTML LIMPIO
                $dompdf->loadHtml($html);

                // 4. CONFIGURAR PAPEL CON MÁRGENES MÍNIMOS
                $dompdf->setPaper('A4', 'landscape');

                // 5. CONFIGURAR CANVAS PARA EVITAR PÁGINAS EN BLANCO
                $dompdf->set_option('margin_top', 0);
                $dompdf->set_option('margin_bottom', 0);
                $dompdf->set_option('margin_left', 0);
                $dompdf->set_option('margin_right', 0);

                // 6. RENDERIZAR
                $dompdf->render();

                // 7. VERIFICAR NÚMERO DE PÁGINAS
                $canvas = $dompdf->get_canvas();
                $pageCount = $canvas->get_page_count();

                // 8. OBTENER PDF Y DESCARGAR
                $output = $dompdf->output();

                return response($output, 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="reporte_A5_SIN_BLANCO_' . date('Y-m-d_H-i') . '.pdf"');
            }

            return redirect()->back()->with('error', 'Formato no soportado');
        } catch (\Exception $e) {
            \Log::error('Error en exportData SIN BLANCO', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }

    /**
     * Obtener información de filtros aplicados
     */
    private function obtenerFiltrosAplicados($request)
    {
        if (!$request) {
            return [];
        }

        $filtros = [];

        if ($request->filled('search')) {
            $filtros['Cliente'] = $request->input('search');
        }

        if ($request->filled('jcc_id')) {
            $jcc = User::find($request->input('jcc_id'));
            $filtros['JCC'] = $jcc ? ($jcc->persona->nombres ?? 'N/A') . ' ' . ($jcc->persona->ape_pat ?? '') : 'N/A';
        }

        if ($request->filled('asesor_id')) {
            $asesor = User::find($request->input('asesor_id'));
            $filtros['Asesor'] = $asesor ? ($asesor->persona->nombres ?? 'N/A') . ' ' . ($asesor->persona->ape_pat ?? '') : 'N/A';
        }

        if ($request->filled('analista_id')) {
            $analista = User::find($request->input('analista_id'));
            $filtros['Analista'] = $analista ? ($analista->persona->nombres ?? 'N/A') . ' ' . ($analista->persona->ape_pat ?? '') : 'N/A';
        }

        if ($request->filled('sucursal_id')) {
            $sucursalIds = $request->input('sucursal_id');
            // Convertir a array si no lo es
            $sucursalIds = is_array($sucursalIds) ? $sucursalIds : [$sucursalIds];

            // Obtener todas las sucursales seleccionadas
            $sucursales = Sucursal::whereIn('id', $sucursalIds)->pluck('sucursal')->toArray();
            $filtros['Sucursal'] = !empty($sucursales) ? implode(', ', $sucursales) : 'N/A';
        }

        if ($request->filled('zona_id')) {
            $zonaIds = $request->input('zona_id');
            // Convertir a array si no lo es
            $zonaIds = is_array($zonaIds) ? $zonaIds : [$zonaIds];

            // Obtener todas las zonas seleccionadas
            $zonas = Zona::whereIn('id', $zonaIds)->pluck('nombre')->toArray();
            $filtros['Zona'] = !empty($zonas) ? implode(', ', $zonas) : 'N/A';
        }

        if ($request->filled('vencimiento_desde')) {
            $filtros['Fecha desde'] = Carbon::parse($request->input('vencimiento_desde'))->format('d/m/Y');
        }

        if ($request->filled('vencimiento_hasta')) {
            $filtros['Fecha hasta'] = Carbon::parse($request->input('vencimiento_hasta'))->format('d/m/Y');
        }

        if ($request->filled('dias_mora_min')) {
            $filtros['Días mora mín.'] = $request->input('dias_mora_min');
        }

        if ($request->filled('dias_mora_max')) {
            $filtros['Días mora máx.'] = $request->input('dias_mora_max');
        }

        if ($request->filled('cuotas_vencidas')) {
            $filtros['Cuotas vencidas'] = $request->input('cuotas_vencidas');
        }

        if ($request->has('tiene_compromiso')) {
            $valor = $request->input('tiene_compromiso');
            $filtros['Compromisos'] = $valor === '1' ? 'Con compromisos' : ($valor === '0' ? 'Sin compromisos' : 'Todos');
        }

        if ($request->has('tiene_gestion')) {
            $valor = $request->input('tiene_gestion');
            $filtros['Gestiones'] = $valor === '1' ? 'Con gestiones' : ($valor === '0' ? 'Sin gestiones' : 'Todos');
        }

        return $filtros;
    }

    /**
     * Obtiene las zonas para una sucursal específica
     */
    public function getZonasBySucursal(Request $request)
    {
        try {
            $sucursalId = $request->input('sucursal_id');
            $zonas = [];

            if ($sucursalId) {
                $sucursal = Sucursal::find($sucursalId);
                if ($sucursal) {
                    $zonas = $sucursal->zonas;
                }
            } else {
                $zonas = Zona::all();
            }

            return response()->json($zonas);
        } catch (\Exception $e) {
            \Log::error('Error al obtener zonas por sucursal', [
                'error' => $e->getMessage(),
                'sucursal_id' => $request->input('sucursal_id'),
            ]);

            return response()->json([]);
        }
    }

    /**
     * Obtiene las sucursales para una o múltiples zonas
     */
    public function getSucursalesByZona(Request $request)
    {
        try {
            $zonaIds = $request->input('zona_id');
            $sucursales = collect();

            if ($zonaIds) {
                // Convertir a array si es un solo valor
                $zonaIdsArray = is_array($zonaIds) ? $zonaIds : [$zonaIds];

                // Filtrar valores vacíos
                $zonaIdsArray = array_filter($zonaIdsArray, function ($id) {
                    return !empty($id);
                });

                if (!empty($zonaIdsArray)) {
                    // Obtener sucursales de todas las zonas seleccionadas
                    $sucursales = Sucursal::whereHas('zonas', function ($query) use ($zonaIdsArray) {
                        $query->whereIn('zonas.id', $zonaIdsArray);
                    })
                        ->select('id', 'sucursal')
                        ->orderBy('sucursal', 'asc')
                        ->get()
                        ->unique('id'); // Evitar duplicados si una sucursal pertenece a múltiples zonas
                } else {
                    // Si no hay zonas válidas, retornar todas
                    $sucursales = Sucursal::select('id', 'sucursal')
                        ->orderBy('sucursal', 'asc')
                        ->get();
                }
            } else {
                // Si no se envía zona_id, retornar todas las sucursales
                $sucursales = Sucursal::select('id', 'sucursal')
                    ->orderBy('sucursal', 'asc')
                    ->get();
            }

            return response()->json($sucursales->values());
        } catch (\Exception $e) {
            \Log::error('Error al obtener sucursales por zona', [
                'error' => $e->getMessage(),
                'zona_id' => $request->input('zona_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([]);
        }
    }

    /**
     * Mostrar previsualización HTML del estado de cobranza
     */
    public function previsualizacionEstadoCobranza($cuotaId)
    {
        try {

            // Obtener la cuota con las relaciones básicas
            $cuota = Cuota::with([
                'prestamo.cliente.persona',
                'prestamo.cuotas',
                'operaciones',
            ])->findOrFail($cuotaId);

            $prestamo = $cuota->prestamo;

            // Datos básicos
            $datos = [
                'prestamo' => $prestamo,
                'cuota' => $cuota,
                'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            ];

            // Usar la vista PDF original
            return view('pdf.estado_cobranza', $datos);
        } catch (\Exception $e) {
            \Log::error('Error en previsualización simplificada', [
                'error' => $e->getMessage(),
                'cuota_id' => $cuotaId,
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Descargar PDF individual de estado de cobranza
     */
    public function descargarEstadoCobranza($cuotaId)
    {
        try {
            // Obtener la cuota con todas sus relaciones necesarias
            $cuota = Cuota::with([
                'prestamo.cliente.persona.direcciones.sucursal.zonas',
                'prestamo.carterasJcc.jcc.persona',
                'prestamo.carterasAsesor.asesor.persona',
                'prestamo.carterasAnalista.analista.persona',
                'prestamo.cuotas',
                'moras' => function ($query) {
                    $query->where('estado', MoraCuotaEstado::PENDIENTE);
                },
                'operaciones.metodoDePago',
            ])->findOrFail($cuotaId);

            // Verificar que la cuota tenga datos válidos
            if (!$cuota->prestamo || !$cuota->prestamo->cliente || !$cuota->prestamo->cliente->persona) {
                throw new \Exception('La cuota no tiene datos válidos del préstamo o cliente');
            }

            // Usar directamente el préstamo desde la cuota
            $prestamo = $cuota->prestamo;

            // Obtener todas las carteras activas del préstamo
            $carterasJcc = $prestamo->carterasJcc()->where('estado', 1)->with('user.persona')->get();
            $carterasAsesor = $prestamo->carterasAsesor()->where('estado', 1)->with('user.persona')->get();
            $carterasAnalista = $prestamo->carterasAnalista()->where('estado', 1)->with('user.persona')->get();

            // Asignar carteras al préstamo para usar en la vista
            $prestamo->carterasJcc = $carterasJcc;
            $prestamo->carterasAsesor = $carterasAsesor;
            $prestamo->carterasAnalista = $carterasAnalista;

            // Datos para el PDF - usando la estructura que espera la vista
            $datos = [
                'prestamo' => $prestamo,
                'cuota' => $cuota,
                'fecha_generacion' => now()->format('d/m/Y H:i:s'),
                'usuario_generador' => auth()->user()->name ?? 'Sistema',
            ];

            // Generar el PDF con configuración específica para evitar errores de fuentes
            $pdf = PDF::loadView('pdf.estado_cobranza', $datos);

            // Configurar el PDF SIN fuentes personalizadas para evitar errores
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
                'isRemoteEnabled' => false,
                'fontHeightRatio' => 1.1,
                'isFontSubsettingEnabled' => false,
            ]);

            // Crear nombre del archivo
            $nombreCliente = str_replace(' ', '_', $prestamo->cliente->persona->nombres ?? 'cliente');
            $apellidoCliente = str_replace(' ', '_', $prestamo->cliente->persona->ape_pat ?? '');
            $nombreArchivo = 'estado_cobranza_' . $nombreCliente . '_' . $apellidoCliente .
                '_prestamo_' . $prestamo->id . '_' . now()->format('Y-m-d_H-i') . '.pdf';

            // Descargar el PDF
            return $pdf->download($nombreArchivo);
        } catch (\Exception $e) {
            \Log::error('Error al generar PDF Estado de Cobranza', [
                'error' => $e->getMessage(),
                'cuota_id' => $cuotaId,
                'usuario_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Error al generar el PDF del estado de cobranza: ' . $e->getMessage());
        }
    }

    /**
     * Obtener TODOS los convenios activos (cuotas y flexible) con su estructura completa
     * para compatibilidad con la vista de tramos
     */
    private function obtenerCuotasConvenios(Request $request, $limit = 1000)
    {
        Log::info('=== INICIO obtenerCuotasConvenios ===', [
            'filtros' => $request->all(),
            'limit' => $limit
        ]);

        // ==========================================
        // PASO 1: Obtener TODOS los convenios ACTIVOS de tipo cuotas y flexible
        // ==========================================
        $conveniosQuery = Convenio::query()
            ->where('estado', ConvenioEstado::ACTIVO->value)
            ->whereIn('tipo', ['cuotas', 'flexible'])
            ->with([
                // Préstamo y sus relaciones
                'prestamo:id,cliente_id,estado,cantidad_solicitada,direccion_cobro_id',
                'prestamo.cliente:id,codigo,persona_id',
                'prestamo.cliente.persona:id,nombres,ape_pat,ape_mat,documento',
                'prestamo.direccionCobro:id,sucursal_id,zona_id,direccion,numero,referencia',
                'prestamo.direccionCobro.sucursal:id,sucursal',
                'prestamo.direccionCobro.sucursal.zonas:id,nombre',
                'prestamo.direccionCobro.zona:id,nombre',
                'prestamo.cliente.persona.direcciones' => function ($q) {
                    $q->select('id', 'persona_id', 'sucursal_id', 'zona_id', 'direccion', 'numero', 'referencia', 'tipo_direccion');
                },
                'prestamo.cliente.persona.direcciones.sucursal:id,sucursal',
                'prestamo.cliente.persona.direcciones.sucursal.zonas:id,nombre',
                'prestamo.cliente.persona.direcciones.zona:id,nombre',
                'prestamo.cuotas' => function ($q) {
                    $q->select('id', 'prestamo_id', 'numero', 'monto', 'estado', 'monto_pagado', 'fecha_pago')
                        ->orderBy('fecha_pago', 'asc');
                },
                'prestamo.cuotas.moras' => function ($q) {
                    $q->select('id', 'cuota_id', 'monto', 'dias_mora', 'estado', 'monto_pagado')
                        ->whereIn('estado', [
                            MoraCuotaEstado::PENDIENTE->value,
                            MoraCuotaEstado::PARCIAL->value,
                        ]);
                },
                'prestamo.carterasJcc:id,prestamo_id,jcc_id,estado',
                'prestamo.carterasJcc.jcc:id,codigo,name,persona_id',
                'prestamo.carterasAsesor:id,prestamo_id,asesor_id,estado',
                'prestamo.carterasAsesor.asesor:id,codigo,name,persona_id',
                'prestamo.carterasAnalista:id,prestamo_id,analista_id,estado',
                'prestamo.carterasAnalista.analista:id,codigo,name,persona_id',
            ]);

        // Aplicar filtros
        $this->aplicarFiltrosConveniosCompleto($conveniosQuery, $request);

        // Ejecutar consulta
        $convenios = $conveniosQuery->get();

        Log::info('CONVENIOS ENCONTRADOS', [
            'total' => $convenios->count(),
            'por_tipo' => [
                'cuotas' => $convenios->where('tipo', 'cuotas')->count(),
                'flexible' => $convenios->where('tipo', 'flexible')->count(),
            ],
        ]);

        // ==========================================
        // PASO 2: Transformar cada convenio a estructura similar a Cuota
        // ==========================================
        $resultado = collect();

        foreach ($convenios as $convenio) {
            if ($convenio->tipo === 'cuotas') {
                // Para convenios tipo cuotas: obtener las cuotas del convenio
                $cuotasConvenio = CuotaConvenioModel::where('convenio_id', $convenio->id)
                    ->whereIn('estado', [
                        CuotaConvenioEstado::PENDIENTE->value,
                        CuotaConvenioEstado::PARCIAL->value,
                        CuotaConvenioEstado::VENCIDO->value
                    ])
                    ->where('fecha_vencimiento', '<=', Carbon::now()->subDay()->endOfDay())
                    ->get();

                foreach ($cuotasConvenio as $cuota) {
                    $resultado->push($this->transformarCuotaConvenio($cuota, $convenio));
                }
            } elseif ($convenio->tipo === 'flexible') {
                // Para convenios tipo flexible: crear una cuota virtual
                $resultado->push($this->transformarConvenioFlexible($convenio));
            }
        }

        Log::info('=== FIN obtenerCuotasConvenios ===', [
            'total_transformadas' => $resultado->count(),
        ]);

        return $resultado;
    }

    /**
     * Transformar una cuota de convenio tipo cuotas
     */
    private function transformarCuotaConvenio($cuotaConvenio, $convenio)
    {
        return new \App\DTOs\CuotaConvenioDTO($convenio, $cuotaConvenio);
    }

    /**
     * Transformar un convenio tipo flexible a estructura similar a Cuota
     */
    private function transformarConvenioFlexible($convenio)
    {
        return new \App\DTOs\CuotaConvenioDTO($convenio);
    }

    /**
     * Aplicar filtros a consulta de convenios (versión completa)
     */
    private function aplicarFiltrosConveniosCompleto($query, Request $request)
    {
        // Filtros de carteras
        if ($request->filled('jcc_id')) {
            $jccIds = $request->input('jcc_id');
            $query->whereHas('prestamo.carterasJcc', function ($q) use ($jccIds) {
                $q->whereIn('jcc_id', is_array($jccIds) ? $jccIds : [$jccIds])->where('estado', 1);
            });
        }

        if ($request->filled('asesor_id')) {
            $asesorIds = $request->input('asesor_id');
            $query->whereHas('prestamo.carterasAsesor', function ($q) use ($asesorIds) {
                $q->whereIn('asesor_id', is_array($asesorIds) ? $asesorIds : [$asesorIds])->where('estado', 1);
            });
        }

        if ($request->filled('analista_id')) {
            $analistaIds = $request->input('analista_id');
            $query->whereHas('prestamo.carterasAnalista', function ($q) use ($analistaIds) {
                $q->whereIn('analista_id', is_array($analistaIds) ? $analistaIds : [$analistaIds])->where('estado', 1);
            });
        }

        // Filtros geográficos
        if ($request->filled('sucursal_id')) {
            $sucursalIds = is_array($request->input('sucursal_id'))
                ? $request->input('sucursal_id')
                : [$request->input('sucursal_id')];

            $query->whereHas('prestamo.cliente.persona.direcciones', function ($q) use ($sucursalIds) {
                $q->whereIn('direcciones.sucursal_id', $sucursalIds);
            });
        } elseif ($request->filled('zona_id')) {
            $zonaIds = is_array($request->input('zona_id'))
                ? $request->input('zona_id')
                : [$request->input('zona_id')];

            $query->whereHas('prestamo.cliente.persona.direcciones.sucursal.zonas', function ($q) use ($zonaIds) {
                $q->whereIn('zonas.id', $zonaIds);
            });
        }

        // Búsqueda por cliente
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('prestamo.cliente.persona', function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('ape_pat', 'like', "%{$search}%")
                    ->orWhere('ape_mat', 'like', "%{$search}%")
                    ->orWhere('documento', 'like', "%{$search}%");
            });
        }

        // Excluir préstamos con estados no deseados
        $query->whereHas('prestamo', function ($q) {
            $q->whereNotIn('estado', [
                'Finalizado',
                'finalizado',
                'Liquidado',
                'liquidado',
                'Anulado',
                'anulado',
                'Cancelado',
                'cancelado',
                'Pagado',
                'pagado'
            ]);
        });
    }
}
