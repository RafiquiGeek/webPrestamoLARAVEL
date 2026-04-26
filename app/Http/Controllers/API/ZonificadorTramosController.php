<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZonificadorTramosController extends Controller
{
    /**
     * API ZONIFICADOR - Según estructura REAL de tu BD
     */
    public function getClientes(Request $request)
    {
        try {
            $startTime = microtime(true);

            Log::info('=== ZONIFICADOR INICIADO ===', $request->all());

            // NO existe `p.codigo`, NO existe `p.cantidadsolicitada`
            // Solo existe: `p.id`, `p.cliente_id`, `p.direccion_cobro_id`, etc.
            
            $query = DB::table('cuotas as c')
                ->select(
                    'cli.id as cliente_id',
                    'per.nombres',
                    'per.ape_pat',
                    'per.ape_mat',
                    'per.documento as dni',
                    'p.id as prestamo_id',
                    'dir.direccion',
                    'dir.latitud',
                    'dir.longitud',
                    'dir.referencia',
                    's.sucursal',
                    's.id as sucursal_id',
                    DB::raw('MIN(c.fecha_pago) as primera_cuota_vencida'),
                    DB::raw('MIN(c.numero) as numero_primera_cuota'),
                    DB::raw('COUNT(*) as total_cuotas_vencidas'),
                    DB::raw('SUM(c.monto - COALESCE(c.monto_pagado, 0)) as deuda_total'),
                    DB::raw('MAX(c.monto) as monto_cuota')
                )
                ->join('prestamos as p', 'c.prestamo_id', '=', 'p.id')
                ->join('clientes as cli', 'p.cliente_id', '=', 'cli.id')
                ->join('personas as per', 'cli.persona_id', '=', 'per.id')
                ->leftJoin('direcciones as dir', 'p.direccion_cobro_id', '=', 'dir.id')
                ->leftJoin('sucursales as s', 'dir.sucursal_id', '=', 's.id')
                ->where('c.fecha_pago', '<=', Carbon::yesterday()->endOfDay())
                ->whereIn('c.estado', [0, 1, 3])
                ->whereNotIn('p.estado', ['Finalizado', 'liquidado'])
                ->whereNotNull('dir.latitud')
                ->whereNotNull('dir.longitud')
                ->groupBy(
                    'cli.id', 'per.nombres', 'per.ape_pat', 'per.ape_mat', 'per.documento',
                    'p.id',
                    'dir.direccion', 'dir.latitud', 'dir.longitud', 'dir.referencia',
                    's.sucursal', 's.id'
                );

            // ==========================================
            // FILTROS
            // ==========================================

            // FILTRO: Zonas múltiples
            if ($request->filled('zona_id')) {
                $zonaIds = is_array($request->zona_id) ? $request->zona_id : [$request->zona_id];
                
                $sucursalesZonas = DB::table('zona_sucursal')
                    ->whereIn('zona_id', $zonaIds)
                    ->pluck('sucursal_id')
                    ->toArray();
                
                if (!empty($sucursalesZonas)) {
                    $query->whereIn('s.id', $sucursalesZonas);
                }
            }

            // FILTRO: Sucursales múltiples
            if ($request->filled('sucursal_id')) {
                $sucursalIds = is_array($request->sucursal_id) ? $request->sucursal_id : [$request->sucursal_id];
                $query->whereIn('s.id', $sucursalIds);
            }

            // FILTRO: JCC múltiples
            if ($request->filled('jcc_id')) {
                $jccIds = is_array($request->jcc_id) ? $request->jcc_id : [$request->jcc_id];
                $query->whereExists(function($subQuery) use ($jccIds) {
                    $subQuery->select(DB::raw(1))
                        ->from('carteras_jcc as cj')
                        ->whereColumn('cj.prestamo_id', 'p.id')
                        ->whereIn('cj.jcc_id', $jccIds)
                        ->where('cj.estado', 1);
                });
            }

            // FILTRO: Asesor múltiples
            if ($request->filled('asesor_id')) {
                $asesorIds = is_array($request->asesor_id) ? $request->asesor_id : [$request->asesor_id];
                $query->whereExists(function($subQuery) use ($asesorIds) {
                    $subQuery->select(DB::raw(1))
                        ->from('carteras_asesor as ca')
                        ->whereColumn('ca.prestamo_id', 'p.id')
                        ->whereIn('ca.asesor_id', $asesorIds)
                        ->where('ca.estado', 1);
                });
            }

            // FILTRO: Analista múltiples
            if ($request->filled('analista_id')) {
                $analistaIds = is_array($request->analista_id) ? $request->analista_id : [$request->analista_id];
                $query->whereExists(function($subQuery) use ($analistaIds) {
                    $subQuery->select(DB::raw(1))
                        ->from('carteras_analista as can')
                        ->whereColumn('can.prestamo_id', 'p.id')
                        ->whereIn('can.analista_id', $analistaIds)
                        ->where('can.estado', 1);
                });
            }

            // FILTRO: Fechas
            $this->aplicarFiltroFechas($query, $request);

            // EJECUTAR QUERY
            $resultados = $query->get();

            Log::info('Query ejecutada', ['registros' => $resultados->count()]);

            // ==========================================
            // PROCESAR RESULTADOS
            // ==========================================
            $fechaActual = Carbon::today();
            $clientesData = [];

            // Obtener zonas
            $sucursalIds = $resultados->pluck('sucursal_id')->filter()->unique()->toArray();
            $zonasMap = [];
            
            if (!empty($sucursalIds)) {
                $zonasMap = DB::table('zona_sucursal as zs')
                    ->join('zonas as z', 'zs.zona_id', '=', 'z.id')
                    ->whereIn('zs.sucursal_id', $sucursalIds)
                    ->pluck('z.nombre', 'zs.sucursal_id')
                    ->toArray();
            }

            foreach ($resultados as $row) {
                // Calcular días de atraso
                $fechaPrimeraCuota = Carbon::parse($row->primera_cuota_vencida)->startOfDay();
                $diasAtraso = $fechaActual->diffInDays($fechaPrimeraCuota);

                // Calcular tramo
                $tramo = $this->calcularTramo($diasAtraso);

                // FILTRO: Tramos múltiples
                if ($request->filled('tramos')) {
                    $tramosSeleccionados = is_array($request->tramos) ? $request->tramos : [$request->tramos];
                    $tramosSeleccionados = array_map('intval', $tramosSeleccionados);
                    
                    if (!in_array($tramo, $tramosSeleccionados)) {
                        continue;
                    }
                }

                // Nombre completo
                $nombreCompleto = trim($row->nombres . ' ' . $row->ape_pat . ' ' . ($row->ape_mat ?? ''));
                
                // Iniciales
                $palabras = explode(' ', $nombreCompleto);
                $iniciales = '';
                foreach ($palabras as $palabra) {
                    if (!empty($palabra)) {
                        $iniciales .= strtoupper(substr($palabra, 0, 1));
                        if (strlen($iniciales) >= 2) break;
                    }
                }

                // Zona
                $zonaNombre = $zonasMap[$row->sucursal_id] ?? 'N/A';

                $clientesData[] = [
                    'id' => $row->cliente_id,
                    'dni' => $row->dni ?? 'N/A',
                    'nombre' => $nombreCompleto,
                    'iniciales' => $iniciales ?: 'NA',
                    'zona' => $zonaNombre,
                    'sucursal' => $row->sucursal ?? 'N/A',
                    'direccion' => $row->direccion ?? 'N/A',
                    'referencia' => $row->referencia ?? 'N/A',
                    'lat' => $row->latitud ? (float)$row->latitud : null,
                    'lng' => $row->longitud ? (float)$row->longitud : null,
                    'deuda' => (float)$row->deuda_total,
                    'monto_cuota' => (float)$row->monto_cuota,
                    'cuotas_vencidas' => (int)$row->total_cuotas_vencidas,
                    'tramo' => $tramo,
                    'tramo_nombre' => $this->getTramoNombre($tramo),
                    'dias_atraso' => $diasAtraso,
                    'primera_cuota_numero' => $row->numero_primera_cuota,
                    'primera_cuota_fecha' => Carbon::parse($row->primera_cuota_vencida)->format('d/m/Y'),
                    'prestamo_id' => $row->prestamo_id,
                ];
            }

            $tiempo = round(microtime(true) - $startTime, 2);

            Log::info('API completado', [
                'registros' => count($clientesData),
                'tiempo' => $tiempo . 's'
            ]);

            return response()->json([
                'success' => true,
                'data' => $clientesData,
                'meta' => [
                    'total' => count($clientesData),
                    'tiempo_segundos' => $tiempo
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('ERROR en API', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function aplicarFiltroFechas($query, $request)
    {
        $tipoRango = $request->tipo_rango_fecha;

        if ($tipoRango === 'dia' && $request->filled('fecha_dia')) {
            try {
                $fecha = Carbon::createFromFormat('d/m/Y', $request->fecha_dia)->startOfDay();
                $query->whereDate('c.fecha_pago', '=', $fecha->format('Y-m-d'));
            } catch (\Exception $e) {
                Log::warning('Formato fecha_dia inválido');
            }
        } elseif ($tipoRango === 'mes' && $request->filled('fecha_mes')) {
            try {
                $fecha = Carbon::createFromFormat('m/Y', $request->fecha_mes);
                $query->whereYear('c.fecha_pago', $fecha->year)
                      ->whereMonth('c.fecha_pago', $fecha->month);
            } catch (\Exception $e) {
                Log::warning('Formato fecha_mes inválido');
            }
        } elseif ($tipoRango === 'entre_fechas') {
            if ($request->filled('fecha_desde')) {
                try {
                    $desde = Carbon::createFromFormat('d/m/Y', $request->fecha_desde)->startOfDay();
                    $query->where('c.fecha_pago', '>=', $desde);
                } catch (\Exception $e) {}
            }
            if ($request->filled('fecha_hasta')) {
                try {
                    $hasta = Carbon::createFromFormat('d/m/Y', $request->fecha_hasta)->endOfDay();
                    $query->where('c.fecha_pago', '<=', $hasta);
                } catch (\Exception $e) {}
            }
        }
    }

    private function calcularTramo($diasAtraso)
    {
        if ($diasAtraso <= 6) return 0;
        if ($diasAtraso <= 14) return 1;
        if ($diasAtraso <= 21) return 2;
        if ($diasAtraso <= 30) return 3;
        return 4;
    }

    private function getTramoNombre($tramo)
    {
        return [
            0 => 'T0 (0-6 días)',
            1 => 'T1 (7-14 días)',
            2 => 'T2 (15-21 días)',
            3 => 'T3 (22-30 días)',
            4 => 'T4 (31+ días)',
            5 => 'Solo Mora'
        ][$tramo] ?? 'Desconocido';
    }

    public function getZonas()
    {
        try {
            $zonas = DB::table('zonas')->select('id', 'nombre')->get();
            return response()->json(['success' => true, 'data' => $zonas]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getFiltersData()
    {
        try {
            $jccs = DB::table('users as u')
                ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                ->leftJoin('personas as p', 'u.persona_id', '=', 'p.id')
                ->where('r.name', 'JCC')
                ->where('u.status', 1)
                ->select('u.id', 'u.codigo', DB::raw("CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.ape_pat, '')) as nombre"))
                ->get();

            $asesores = DB::table('users as u')
                ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                ->leftJoin('personas as p', 'u.persona_id', '=', 'p.id')
                ->where('r.name', 'Asesor')
                ->where('u.status', 1)
                ->select('u.id', 'u.codigo', DB::raw("CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.ape_pat, '')) as nombre"))
                ->get();

            $analistas = DB::table('users as u')
                ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                ->leftJoin('personas as p', 'u.persona_id', '=', 'p.id')
                ->where('r.name', 'Analista')
                ->where('u.status', 1)
                ->select('u.id', 'u.codigo', DB::raw("CONCAT(COALESCE(p.nombres, ''), ' ', COALESCE(p.ape_pat, '')) as nombre"))
                ->get();

            $zonas = DB::table('zonas')->select('id', 'nombre')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'jccs' => $jccs,
                    'asesores' => $asesores,
                    'analistas' => $analistas,
                    'zonas' => $zonas
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
