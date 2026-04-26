<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConvenioEstado;
use App\Enums\CuotaEstado;
use App\Enums\CuotaConvenio as CuotaConvenioEstado;
use App\Enums\MoraCuotaEstado;
use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\CuotaConvenioModel;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Zona;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DeudasControllerOptimizado extends Controller
{
    /**
     * MÉTODO INDEX OPTIMIZADO CON AGREGACIONES SQL
     *
     * Mejoras implementadas:
     * 1. Usa agregaciones SQL en lugar de procesar en memoria
     * 2. Paginación antes de agrupar (reduce carga de memoria)
     * 3. Subconsultas optimizadas con índices
     * 4. Cache de datos de filtros
     * 5. Query builder optimizado para convenios
     */
    public function index(Request $request)
    {
        try {
            $startTime = microtime(true);
            \Log::info('=== INICIANDO CONSULTA OPTIMIZADA DE DEUDAS ===', ['filtros' => $request->all()]);

            $tipo = $request->input('tipo', 'ambos');

            // ============================================
            // PASO 1: OBTENER IDS DE CLIENTES CON FILTROS
            // ============================================
            $clientesQuery = $this->construirConsultaClientesConDeuda($request, $tipo);

            // Obtener total de clientes para paginación
            $totalClientes = $clientesQuery->count();
            \Log::info('Total clientes encontrados', ['total' => $totalClientes]);

            // Aplicar paginación a nivel de clientes
            $perPage = $request->input('per_page', 50);
            $currentPage = $request->input('page', 1);
            $offset = ($currentPage - 1) * $perPage;

            // Obtener solo los IDs de clientes paginados
            $clienteIds = $clientesQuery
                ->skip($offset)
                ->take($perPage)
                ->pluck('cliente_id');

            \Log::info('Clientes paginados', [
                'page' => $currentPage,
                'per_page' => $perPage,
                'ids_count' => $clienteIds->count()
            ]);

            // ============================================
            // PASO 2: OBTENER DETALLES DE CUOTAS PARA CLIENTES PAGINADOS
            // ============================================
            $cuotasAgrupadas = $this->obtenerDeudasAgrupadasPorCliente($clienteIds, $tipo, $request);

            // ============================================
            // PASO 3: CALCULAR TOTALES GLOBALES
            // ============================================
            $totales = $this->calcularTotalesGlobales($request, $tipo);

            // ============================================
            // PASO 4: DATOS PARA FILTROS (CON CACHE)
            // ============================================
            $filtrosData = $this->obtenerDatosFiltros();

            $executionTime = microtime(true) - $startTime;
            \Log::info('Consulta optimizada completada', [
                'tiempo_segundos' => round($executionTime, 2),
                'clientes_totales' => $totalClientes,
                'clientes_pagina' => $cuotasAgrupadas->count()
            ]);

            // ============================================
            // PASO 5: CREAR PAGINADOR
            // ============================================
            $cuotasAgrupadasPaginadas = new \Illuminate\Pagination\LengthAwarePaginator(
                $cuotasAgrupadas,
                $totalClientes,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // ============================================
            // RESPUESTAS
            // ============================================

            // Exportación
            if ($request->has('export')) {
                return $this->exportData(null, $cuotasAgrupadas, $request->input('export'), $totales, $request);
            }

            // AJAX
            if ($request->ajax()) {
                return view('admin.Deudas.table_grouped', [
                    'cuotasAgrupadas' => $cuotasAgrupadasPaginadas,
                    'totalMonto' => $totales['monto'],
                    'totalMora' => $totales['mora'],
                    'totalDeuda' => $totales['deuda'],
                    'totalClientes' => $totalClientes
                ]);
            }

            // Vista normal
            return view('admin.Deudas.index', array_merge(
                $filtrosData,
                [
                    'cuotasAgrupadas' => $cuotasAgrupadasPaginadas,
                    'totalMonto' => $totales['monto'],
                    'totalMora' => $totales['mora'],
                    'totalDeuda' => $totales['deuda'],
                    'totalClientes' => $totalClientes
                ]
            ));

        } catch (\Exception $e) {
            \Log::error('ERROR en DeudasControllerOptimizado@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return redirect()->back()->with('error', 'Error al cargar los datos: ' . $e->getMessage());
        }
    }

    /**
     * Construye consulta optimizada para obtener IDs de clientes con deuda
     */
    private function construirConsultaClientesConDeuda(Request $request, string $tipo)
    {
        // Subconsulta para cuotas de préstamos
        $cuotasPrestamosQuery = Cuota::query()
            ->select('prestamos.cliente_id')
            ->join('prestamos', 'cuotas.prestamo_id', '=', 'prestamos.id')
            ->conDeuda(); // Usa el scope optimizado

        // Filtros comunes
        $this->aplicarFiltrosComunes($cuotasPrestamosQuery, $request);

        // Tipo de deuda
        if ($tipo === 'prestamos') {
            $cuotasPrestamosQuery->sinConveniosActivos();
        }

        // Para convenios o ambos, unir con cuotas de convenios
        if ($tipo === 'convenios' || $tipo === 'ambos') {
            $cuotasConveniosQuery = CuotaConvenioModel::query()
                ->select('prestamos.cliente_id')
                ->join('convenios', 'cuota_convenio_models.convenio_id', '=', 'convenios.id')
                ->join('prestamos', 'convenios.prestamo_id', '=', 'prestamos.id')
                ->whereIn('cuota_convenio_models.estado', [
                    CuotaConvenioEstado::PENDIENTE->value,
                    CuotaConvenioEstado::PARCIAL->value,
                    CuotaConvenioEstado::VENCIDO->value
                ])
                ->where('convenios.estado', ConvenioEstado::ACTIVO->value);

            $this->aplicarFiltrosConvenios($cuotasConveniosQuery, $request);

            if ($tipo === 'convenios') {
                // Solo convenios
                return $cuotasConveniosQuery
                    ->groupBy('prestamos.cliente_id')
                    ->orderBy('prestamos.cliente_id');
            } else {
                // Ambos: excluir préstamos con convenios activos de la consulta de préstamos
                $cuotasPrestamosQuery->sinConveniosActivos();

                // Unir ambas consultas
                return DB::table(DB::raw("({$cuotasPrestamosQuery->toSql()}) as prestamos_tabla"))
                    ->mergeBindings($cuotasPrestamosQuery->getQuery())
                    ->select('cliente_id')
                    ->union(
                        DB::table(DB::raw("({$cuotasConveniosQuery->toSql()}) as convenios_tabla"))
                            ->mergeBindings($cuotasConveniosQuery->getQuery())
                            ->select('cliente_id')
                    )
                    ->groupBy('cliente_id')
                    ->orderBy('cliente_id');
            }
        }

        // Solo préstamos
        return $cuotasPrestamosQuery
            ->groupBy('prestamos.cliente_id')
            ->orderBy('prestamos.cliente_id');
    }

    /**
     * Obtiene deudas agrupadas por cliente usando agregaciones SQL
     */
    private function obtenerDeudasAgrupadasPorCliente($clienteIds, string $tipo, Request $request)
    {
        if ($clienteIds->isEmpty()) {
            return collect();
        }

        // Consulta optimizada con agregaciones
        $deudas = DB::table('cuotas as c')
            ->join('prestamos as p', 'c.prestamo_id', '=', 'p.id')
            ->join('clientes as cl', 'p.cliente_id', '=', 'cl.id')
            ->join('personas as per', 'cl.persona_id', '=', 'per.id')
            ->leftJoin('direcciones as dir', function($join) {
                $join->on('per.id', '=', 'dir.persona_id')
                    ->whereRaw('dir.id = (SELECT MIN(id) FROM direcciones WHERE persona_id = per.id)');
            })
            ->leftJoin('sucursales as suc', 'dir.sucursal_id', '=', 'suc.id')
            ->leftJoin(DB::raw('(
                SELECT
                    cuota_id,
                    SUM(monto - COALESCE(monto_pagado, 0)) as total_mora,
                    MAX(dias_mora) as max_dias_mora
                FROM mora_cuota
                WHERE estado IN (' . MoraCuotaEstado::PENDIENTE->value . ', ' . MoraCuotaEstado::PARCIAL->value . ')
                GROUP BY cuota_id
            ) as moras'), 'c.id', '=', 'moras.cuota_id')
            ->whereIn('cl.id', $clienteIds)
            ->whereIn('c.estado', [
                CuotaEstado::PENDIENTE->value,
                CuotaEstado::PARCIAL->value,
                CuotaEstado::VENCIDO->value
            ])
            ->select([
                'cl.id as cliente_id',
                'cl.codigo as cliente_codigo',
                'per.nombres',
                'per.ape_pat',
                'per.ape_mat',
                'per.documento',
                'suc.id as sucursal_id',
                'suc.sucursal as sucursal_nombre',
                'dir.direccion',
                'dir.numero',
                'dir.referencia',
                DB::raw('COUNT(DISTINCT c.id) as total_cuotas'),
                DB::raw('SUM(c.monto) as monto_total'),
                DB::raw('SUM(COALESCE(moras.total_mora, 0)) as mora_total'),
                DB::raw('SUM(c.monto + COALESCE(moras.total_mora, 0)) as deuda_total'),
                DB::raw('MAX(COALESCE(moras.max_dias_mora, 0)) as dias_mora_max'),
                DB::raw('MIN(c.fecha_pago) as fecha_primera_cuota')
            ])
            ->groupBy([
                'cl.id', 'cl.codigo', 'per.nombres', 'per.ape_pat',
                'per.ape_mat', 'per.documento', 'suc.id', 'suc.sucursal', 'dir.direccion', 'dir.numero', 'dir.referencia'
            ])
            ->orderBy('per.nombres')
            ->get();

        // Obtener información adicional (carteras, zona) en segunda consulta
        $deudas = $deudas->map(function($deuda) {
            // Cargar carteras activas
            $carteras = $this->obtenerCarterasCliente($deuda->cliente_id);

            // Cargar zona
            $zona = $this->obtenerZonaCliente($deuda->sucursal_id);

            return [
                'cliente' => (object)[
                    'id' => $deuda->cliente_id,
                    'codigo' => $deuda->cliente_codigo,
                    'persona' => (object)[
                        'nombres' => $deuda->nombres,
                        'ape_pat' => $deuda->ape_pat,
                        'ape_mat' => $deuda->ape_mat,
                        'documento' => $deuda->documento,
                    ]
                ],
                'nombre_completo' => trim("{$deuda->nombres} {$deuda->ape_pat} {$deuda->ape_mat}"),
                'total_cuotas' => $deuda->total_cuotas,
                'monto_total' => $deuda->monto_total,
                'mora_total' => $deuda->mora_total,
                'deuda_total' => $deuda->deuda_total,
                'dias_mora_max' => $deuda->dias_mora_max,
                'sucursal' => $deuda->sucursal_id ? (object)['id' => $deuda->sucursal_id, 'sucursal' => $deuda->sucursal_nombre] : null,
                'zona' => $zona,
                'direccion' => trim(($deuda->direccion ?? '') . ' ' . ($deuda->numero ?? '')),
                'referencia' => $deuda->referencia,
                'jcc_nombre' => $carteras['jcc']['nombre'] ?? null,
                'jcc_codigo' => $carteras['jcc']['codigo'] ?? null,
                'asesor_nombre' => $carteras['asesor']['nombre'] ?? null,
                'asesor_codigo' => $carteras['asesor']['codigo'] ?? null,
                'analista_nombre' => $carteras['analista']['nombre'] ?? null,
                'analista_codigo' => $carteras['analista']['codigo'] ?? null,
                'ultima_gestion' => null, // Puede agregarse si es necesario
                'ultimo_compromiso' => null,
            ];
        });

        return $deudas;
    }

    /**
     * Obtiene carteras activas de un cliente
     */
    private function obtenerCarterasCliente($clienteId)
    {
        static $cache = [];

        if (!isset($cache[$clienteId])) {
            $prestamo = DB::table('prestamos')
                ->where('cliente_id', $clienteId)
                ->orderBy('id', 'desc')
                ->first();

            if (!$prestamo) {
                $cache[$clienteId] = ['jcc' => null, 'asesor' => null, 'analista' => null];
                return $cache[$clienteId];
            }

            $jcc = DB::table('cartera_jcc as c')
                ->join('users as u', 'c.jcc_id', '=', 'u.id')
                ->leftJoin('personas as p', 'u.persona_id', '=', 'p.id')
                ->where('c.prestamo_id', $prestamo->id)
                ->where('c.estado', 1)
                ->select('u.codigo', 'p.nombres', 'p.ape_pat')
                ->first();

            $asesor = DB::table('cartera_asesor as c')
                ->join('users as u', 'c.asesor_id', '=', 'u.id')
                ->leftJoin('personas as p', 'u.persona_id', '=', 'p.id')
                ->where('c.prestamo_id', $prestamo->id)
                ->where('c.estado', 1)
                ->select('u.codigo', 'p.nombres', 'p.ape_pat')
                ->first();

            $analista = DB::table('cartera_analista as c')
                ->join('users as u', 'c.analista_id', '=', 'u.id')
                ->leftJoin('personas as p', 'u.persona_id', '=', 'p.id')
                ->where('c.prestamo_id', $prestamo->id)
                ->where('c.estado', 1)
                ->select('u.codigo', 'p.nombres', 'p.ape_pat')
                ->first();

            $cache[$clienteId] = [
                'jcc' => $jcc ? ['nombre' => trim(($jcc->nombres ?? '') . ' ' . ($jcc->ape_pat ?? '')), 'codigo' => $jcc->codigo] : null,
                'asesor' => $asesor ? ['nombre' => trim(($asesor->nombres ?? '') . ' ' . ($asesor->ape_pat ?? '')), 'codigo' => $asesor->codigo] : null,
                'analista' => $analista ? ['nombre' => trim(($analista->nombres ?? '') . ' ' . ($analista->ape_pat ?? '')), 'codigo' => $analista->codigo] : null,
            ];
        }

        return $cache[$clienteId];
    }

    /**
     * Obtiene zona de una sucursal
     */
    private function obtenerZonaCliente($sucursalId)
    {
        if (!$sucursalId) {
            return null;
        }

        static $cache = [];

        if (!isset($cache[$sucursalId])) {
            $zona = DB::table('sucursal_zona')
                ->join('zonas', 'sucursal_zona.zona_id', '=', 'zonas.id')
                ->where('sucursal_zona.sucursal_id', $sucursalId)
                ->select('zonas.id', 'zonas.nombre')
                ->first();

            $cache[$sucursalId] = $zona ? (object)['id' => $zona->id, 'nombre' => $zona->nombre] : null;
        }

        return $cache[$sucursalId];
    }

    /**
     * Calcula totales globales con agregaciones SQL
     */
    private function calcularTotalesGlobales(Request $request, string $tipo)
    {
        $query = Cuota::query()
            ->join('prestamos', 'cuotas.prestamo_id', '=', 'prestamos.id')
            ->conDeuda();

        $this->aplicarFiltrosComunes($query, $request);

        if ($tipo === 'prestamos') {
            $query->sinConveniosActivos();
        }

        $totales = $query->select([
            DB::raw('SUM(cuotas.monto) as monto_total'),
            DB::raw('SUM(COALESCE((
                SELECT SUM(m.monto - COALESCE(m.monto_pagado, 0))
                FROM mora_cuota m
                WHERE m.cuota_id = cuotas.id
                AND m.estado IN (' . MoraCuotaEstado::PENDIENTE->value . ', ' . MoraCuotaEstado::PARCIAL->value . ')
            ), 0)) as mora_total')
        ])->first();

        return [
            'monto' => $totales->monto_total ?? 0,
            'mora' => $totales->mora_total ?? 0,
            'deuda' => ($totales->monto_total ?? 0) + ($totales->mora_total ?? 0)
        ];
    }

    /**
     * Aplica filtros comunes a la consulta de cuotas
     */
    private function aplicarFiltrosComunes($query, Request $request)
    {
        // Carteras
        $query->porCarteras(
            $request->input('jcc_id'),
            $request->input('asesor_id'),
            $request->input('analista_id')
        );

        // Ubicación
        $query->porUbicacion(
            $request->input('zona_id'),
            $request->input('sucursal_id')
        );

        // Fechas
        $query->porFechaVencimiento(
            $request->input('vencimiento_desde'),
            $request->input('vencimiento_hasta')
        );

        // Días de mora
        $query->porDiasMora(
            $request->input('dias_mora_min'),
            $request->input('dias_mora_max')
        );

        // Búsqueda
        if ($request->filled('search')) {
            $query->buscarCliente($request->input('search'));
        }

        // Cuotas vencidas mínimas
        if ($request->filled('cuotas_vencidas')) {
            $cuotasMin = $request->input('cuotas_vencidas');
            $prestamosIds = Cuota::query()
                ->where('fecha_pago', '<', Carbon::today())
                ->whereIn('estado', [
                    CuotaEstado::PENDIENTE->value,
                    CuotaEstado::PARCIAL->value,
                    CuotaEstado::VENCIDO->value
                ])
                ->select('prestamo_id')
                ->groupBy('prestamo_id')
                ->havingRaw('COUNT(*) >= ?', [$cuotasMin])
                ->pluck('prestamo_id');

            $query->whereIn('cuotas.prestamo_id', $prestamosIds);
        }

        // Gestiones
        if ($request->has('tiene_gestion')) {
            if ($request->input('tiene_gestion') === '1') {
                $query->whereHas('prestamo.gestiones');
            } elseif ($request->input('tiene_gestion') === '0') {
                $query->whereDoesntHave('prestamo.gestiones');
            }
        }

        // Compromisos
        if ($request->has('tiene_compromiso')) {
            if ($request->input('tiene_compromiso') === '1') {
                $query->whereHas('prestamo.compromisos');
            } elseif ($request->input('tiene_compromiso') === '0') {
                $query->whereDoesntHave('prestamo.compromisos');
            }
        }

        return $query;
    }

    /**
     * Aplica filtros a consulta de convenios
     */
    private function aplicarFiltrosConvenios($query, Request $request)
    {
        // Similar a aplicarFiltrosComunes pero para convenios
        // (Implementar según necesidad)
        return $query;
    }

    /**
     * Obtiene datos para filtros (con caché)
     */
    private function obtenerDatosFiltros()
    {
        return [
            'jccs' => Cache::remember('deudas_jccs_v2', 3600, function () {
                return User::role('JCC')
                    ->where('status', 1)
                    ->with('persona:id,nombres,ape_pat')
                    ->get(['id', 'codigo', 'persona_id']);
            }),
            'asesores' => Cache::remember('deudas_asesores_v2', 3600, function () {
                return User::role('Asesor')
                    ->where('status', 1)
                    ->with('persona:id,nombres,ape_pat')
                    ->get(['id', 'codigo', 'persona_id']);
            }),
            'analistas' => Cache::remember('deudas_analistas_v2', 3600, function () {
                return User::role('Analista')
                    ->where('status', 1)
                    ->with('persona:id,nombres,ape_pat')
                    ->get(['id', 'codigo', 'persona_id']);
            }),
            'zonas' => Cache::remember('deudas_zonas_v2', 3600, function () {
                return Zona::select('id', 'nombre')->get();
            }),
            'sucursales' => Cache::remember('deudas_sucursales_v2', 3600, function () {
                return Sucursal::select('id', 'sucursal')->get();
            }),
        ];
    }

    // Los métodos exportData, previsualizacionEstadoCobranza, descargarEstadoCobranza, etc.
    // se mantienen igual que en el controlador original
}
