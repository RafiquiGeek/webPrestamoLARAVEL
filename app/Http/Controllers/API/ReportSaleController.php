<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportSaleController extends Controller
{
    /**
     * Obtiene el total de préstamos con filtros avanzados
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function totalPrestamos(Request $request)
    {
        $query = DB::table('prestamos as p')
            ->leftJoin('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->select(
                DB::raw('COUNT(p.id) as total_prestamos'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Nueva" THEN 1 ELSE 0 END) as total_nuevas'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Renovación" THEN 1 ELSE 0 END) as total_renovaciones'),
                DB::raw('SUM(p.cantidad_solicitada) as monto_total'),
                DB::raw('SUM(p.saldo_restante) as saldo_total')
            );

        $this->aplicarFiltros($query, $request, false, false); // FALSE: No incluir filtro de usuario, FALSE: No incluir filtro de rol

        return response()->json($query->first());
    }

    /**
     * Obtiene préstamos de tipo "Nueva" con información completa
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function prestamosNuevos(Request $request)
    {
        $perPage = $request->get('per_page', 50);

        $query = $this->baseQueryPrestamos()
            ->where('p.tipo_solicitud', 'Nueva');

        $this->aplicarFiltros($query, $request);

        $query->orderBy('p.fecha_atencion', 'desc');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Obtiene préstamos de tipo "Renovación" con información completa
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function prestamosRenovaciones(Request $request)
    {
        $perPage = $request->get('per_page', 50);

        $query = $this->baseQueryPrestamos()
            ->where('p.tipo_solicitud', 'Renovación');

        $this->aplicarFiltros($query, $request);

        $query->orderBy('p.fecha_atencion', 'desc');

        return response()->json($query->paginate($perPage));
    }

    /**
     * Reporte consolidado con agrupación por ROL y luego por USUARIO
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportePorUsuario(Request $request)
    {
        $query = DB::table('prestamos as p')
            ->leftJoin('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            // Identificación del responsable según las 3 carteras
            ->leftJoin('carteras_analista as ca', 'p.id', '=', 'ca.prestamo_id')
            ->leftJoin('carteras_asesor as cas', 'p.id', '=', 'cas.prestamo_id')
            ->leftJoin('carteras_jcc as cj', 'p.id', '=', 'cj.prestamo_id')
            
            // Join con usuarios basado en la prioridad de carteras
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', DB::raw('COALESCE(ca.analista_id, cas.asesor_id, cj.jcc_id)'));
            })
            // Join con personas para nombres reales
            ->leftJoin('personas as per', 'u.persona_id', '=', 'per.id')
            
            // --- UNIÓN CON ROLES (Spatie/Laravel estándar) ---
            ->leftJoin('model_has_roles as mhr', function($join) {
                $join->on('u.id', '=', 'mhr.model_id')
                     ->where('mhr.model_type', '=', 'App\\Models\\User'); 
            })
            ->leftJoin('roles as r', 'mhr.role_id', '=', 'r.id')
            // -------------------------------------------------

            ->select(
                DB::raw('IFNULL(r.name, "SIN ROL") as rol_nombre'),
                DB::raw('COALESCE(ca.analista_id, cas.asesor_id, cj.jcc_id) as usuario_id'),
                DB::raw('IFNULL(per.nombres, "SIN ASIGNAR") as usuario_nombre'),
                DB::raw('IFNULL(per.ape_pat, "") as usuario_ape_pat'),
                'u.email as usuario_email',
                DB::raw('COUNT(p.id) as total_prestamos'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Nueva" THEN 1 ELSE 0 END) as total_nuevas'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Renovación" THEN 1 ELSE 0 END) as total_renovaciones'),
                DB::raw('SUM(p.cantidad_solicitada) as monto_total'),
                DB::raw('SUM(p.saldo_restante) as saldo_total')
            )
            ->whereNotNull('p.fecha_atencion');

        // Aplicamos los filtros globales, incluyendo filtro por rol (TRUE)
        $this->aplicarFiltros($query, $request, true, true);

        $query->groupBy(
                'r.name',
                DB::raw('COALESCE(ca.analista_id, cas.asesor_id, cj.jcc_id)'),
                'per.nombres',
                'per.ape_pat',
                'u.email'
            )
            ->orderBy('rol_nombre', 'asc')
            ->orderBy('total_prestamos', 'desc');

        $resultados = $query->get();

        // Agrupamos la colección resultante por el nombre del rol
        $reporteJerarquico = $resultados->groupBy('rol_nombre');

        return response()->json($reporteJerarquico);
    }

    /**
     * Reporte agrupado por zona
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportePorZona(Request $request)
    {
        $query = DB::table('prestamos as p')
            ->join('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->join('zonas as z', 'd.zona_id', '=', 'z.id')
            ->select(
                'z.id as zona_id',
                'z.nombre as zona_nombre',
                DB::raw('COUNT(p.id) as total_prestamos'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Nueva" THEN 1 ELSE 0 END) as total_nuevas'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Renovación" THEN 1 ELSE 0 END) as total_renovaciones'),
                DB::raw('SUM(p.cantidad_solicitada) as monto_total'),
                DB::raw('SUM(p.saldo_restante) as saldo_total')
            )
            ->whereNotNull('p.fecha_atencion');

        // FALSE: No incluir filtro de usuario, FALSE: No incluir filtro de rol
        $this->aplicarFiltros($query, $request, false, false);

        $query->groupBy('z.id', 'z.nombre')
            ->orderBy('total_prestamos', 'desc');

        return response()->json($query->get());
    }

    /**
     * Reporte agrupado por sucursal
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportePorSucursal(Request $request)
    {
        $query = DB::table('prestamos as p')
            ->join('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->join('sucursales as s', 'd.sucursal_id', '=', 's.id')
            ->select(
                's.id as sucursal_id',
                's.sucursal as sucursal_nombre',
                DB::raw('COUNT(p.id) as total_prestamos'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Nueva" THEN 1 ELSE 0 END) as total_nuevas'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Renovación" THEN 1 ELSE 0 END) as total_renovaciones'),
                DB::raw('SUM(p.cantidad_solicitada) as monto_total'),
                DB::raw('SUM(p.saldo_restante) as saldo_total')
            )
            ->whereNotNull('p.fecha_atencion');

        // FALSE: No incluir filtro de usuario, FALSE: No incluir filtro de rol
        $this->aplicarFiltros($query, $request, false, false);

        $query->groupBy('s.id', 's.sucursal')
            ->orderBy('total_prestamos', 'desc');

        return response()->json($query->get());
    }

    /**
     * Reporte agrupado por mes
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportePorMes(Request $request)
    {
        $query = DB::table('prestamos as p')
            ->leftJoin('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->select(
                DB::raw('YEAR(p.fecha_atencion) as anio'),
                DB::raw('MONTH(p.fecha_atencion) as mes'),
                DB::raw('DATE_FORMAT(p.fecha_atencion, "%Y-%m") as periodo'),
                DB::raw('MONTHNAME(p.fecha_atencion) as mes_nombre'),
                DB::raw('COUNT(p.id) as total_prestamos'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Nueva" THEN 1 ELSE 0 END) as total_nuevas'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Renovación" THEN 1 ELSE 0 END) as total_renovaciones'),
                DB::raw('SUM(p.cantidad_solicitada) as monto_total'),
                DB::raw('SUM(p.saldo_restante) as saldo_total')
            )
            ->whereNotNull('p.fecha_atencion');

        // FALSE: No incluir filtro de usuario, FALSE: No incluir filtro de rol
        $this->aplicarFiltros($query, $request, false, false);

        $query->groupBy(
            DB::raw('YEAR(p.fecha_atencion)'),
            DB::raw('MONTH(p.fecha_atencion)'),
            DB::raw('periodo'),
            DB::raw('mes_nombre')
        )
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc');

        return response()->json($query->get());
    }

    /**
     * Reporte agrupado por año
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportePorAnio(Request $request)
    {
        $query = DB::table('prestamos as p')
            ->leftJoin('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->select(
                DB::raw('YEAR(p.fecha_atencion) as anio'),
                DB::raw('COUNT(p.id) as total_prestamos'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Nueva" THEN 1 ELSE 0 END) as total_nuevas'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Renovación" THEN 1 ELSE 0 END) as total_renovaciones'),
                DB::raw('SUM(p.cantidad_solicitada) as monto_total'),
                DB::raw('SUM(p.saldo_restante) as saldo_total')
            )
            ->whereNotNull('p.fecha_atencion');

        // FALSE: No incluir filtro de usuario, FALSE: No incluir filtro de rol
        $this->aplicarFiltros($query, $request, false, false);

        $query->groupBy(DB::raw('YEAR(p.fecha_atencion)'))
            ->orderBy('anio', 'desc');

        return response()->json($query->get());
    }

    /**
     * Reporte completo con múltiples agrupaciones
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reporteCompleto(Request $request)
    {
        $query = DB::table('prestamos as p')
            ->join('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->leftJoin('zonas as z', 'd.zona_id', '=', 'z.id')
            ->leftJoin('sucursales as s', 'd.sucursal_id', '=', 's.id')
            // Joins a las carteras para obtener los IDs de usuario
            ->leftJoin('carteras_analista as ca', 'p.id', '=', 'ca.prestamo_id')
            ->leftJoin('carteras_asesor as cas', 'p.id', '=', 'cas.prestamo_id')
            ->leftJoin('carteras_jcc as cj', 'p.id', '=', 'cj.prestamo_id')

            // Unimos con users: El ID del usuario está en alguna de las 3 tablas de cartera
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', DB::raw('COALESCE(ca.analista_id, cas.asesor_id, cj.jcc_id)'));
            })

            // Unimos con personas para obtener nombres y apellidos reales
            ->leftJoin('personas as per', 'u.persona_id', '=', 'per.id')

            ->select(
                DB::raw('YEAR(p.fecha_atencion) as anio'),
                DB::raw('MONTH(p.fecha_atencion) as mes'),
                DB::raw('DATE_FORMAT(p.fecha_atencion, "%Y-%m") as periodo'),
                'z.nombre as zona_nombre',
                's.sucursal as sucursal_nombre',
                // Si no hay nombre, ponemos un indicador para saber qué falló
                DB::raw('IFNULL(per.nombres, "SIN ASIGNAR") as usuario_nombre'),
                DB::raw('IFNULL(per.ape_pat, "") as usuario_ape_pat'),
                DB::raw('COUNT(p.id) as total_prestamos'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Nueva" THEN 1 ELSE 0 END) as total_nuevas'),
                DB::raw('SUM(CASE WHEN p.tipo_solicitud = "Renovación" THEN 1 ELSE 0 END) as total_renovaciones'),
                DB::raw('SUM(p.cantidad_solicitada) as monto_total'),
                DB::raw('SUM(p.saldo_restante) as saldo_total')
            )
            ->whereNotNull('p.fecha_atencion');

        // TRUE: Incluir filtro de usuario, FALSE: No incluir filtro de rol (porque no hay join con roles)
        $this->aplicarFiltros($query, $request, true, false);

        $query->groupBy(
            DB::raw('YEAR(p.fecha_atencion)'),
            DB::raw('MONTH(p.fecha_atencion)'),
            DB::raw('periodo'),
            'z.nombre',
            's.sucursal',
            'per.nombres',
            'per.ape_pat'
        )
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc');

        return response()->json($query->get());
    }

    /**
     * Query base para préstamos con toda la información
     * @return \Illuminate\Database\Query\Builder
     */
    private function baseQueryPrestamos()
    {
        return DB::table('prestamos as p')
            ->join('clientes as c', 'p.cliente_id', '=', 'c.id')
            ->join('personas as per', 'c.persona_id', '=', 'per.id')
            ->leftJoin('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->leftJoin('zonas as z', 'd.zona_id', '=', 'z.id')
            ->leftJoin('sucursales as s', 'd.sucursal_id', '=', 's.id')
            ->leftJoin('carteras_analista as ca', 'p.id', '=', 'ca.prestamo_id')
            ->leftJoin('carteras_asesor as cas', 'p.id', '=', 'cas.prestamo_id')
            ->leftJoin('carteras_jcc as cj', 'p.id', '=', 'cj.prestamo_id')
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', DB::raw('COALESCE(ca.analista_id, cas.asesor_id, cj.jcc_id)'));
            })
            ->select(
                'p.id as prestamo_id',
                'p.cantidad_solicitada',
                'p.saldo_restante',
                'p.fecha_atencion',
                'p.fecha_primer_pago',
                'p.estado as prestamo_estado',
                'p.tipo_solicitud',
                'p.plazo',
                'p.tasa_interes',
                'p.frecuencia_pago',
                DB::raw('CONCAT(per.nombres, " ", per.ape_pat, " ", IFNULL(per.ape_mat, "")) as cliente_nombre'),
                'per.documento as cliente_documento',
                'c.codigo as cliente_codigo',
                'z.nombre as zona_nombre',
                's.sucursal as sucursal_nombre',
                DB::raw('COALESCE(ca.analista_id, cas.asesor_id, cj.jcc_id) as usuario_id'),
                'u.name as usuario_nombre',
                'u.email as usuario_email',
                'u.codigo as usuario_codigo'
            );
    }

    /**
     * Aplica filtros comunes a las consultas
     * @param $query
     * @param Request $request
     * @param bool $includeUsuarioFilter
     * @param bool $includeRolFilter - NUEVO: Indica si la consulta tiene JOIN con roles
     */
    private function aplicarFiltros(&$query, Request $request, $includeUsuarioFilter = true, $includeRolFilter = false)
    {
        // Filtro por rango de fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('p.fecha_atencion', [
                $request->fecha_inicio . ' 00:00:00',
                $request->fecha_fin . ' 23:59:59'
            ]);
        }

        // Filtro por mes específico (puede venir como número o como nombre)
        if ($request->has('mes') && $request->has('anio')) {
            $mes = $request->mes;
            
            // Si el mes viene como nombre (ej: "enero"), convertirlo a número
            if (!is_numeric($mes)) {
                $meses = [
                    'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
                    'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
                    'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
                ];
                $mes = strtolower($mes);
                $mesNumero = isset($meses[$mes]) ? $meses[$mes] : null;
                
                if ($mesNumero) {
                    $query->whereRaw('MONTH(p.fecha_atencion) = ?', [$mesNumero])
                        ->whereRaw('YEAR(p.fecha_atencion) = ?', [$request->anio]);
                }
            } else {
                $query->whereRaw('MONTH(p.fecha_atencion) = ?', [$mes])
                    ->whereRaw('YEAR(p.fecha_atencion) = ?', [$request->anio]);
            }
        }

        // Filtro solo por año
        if ($request->has('anio') && !$request->has('mes')) {
            $query->whereRaw('YEAR(p.fecha_atencion) = ?', [$request->anio]);
        }

        // Filtro por zona
        if ($request->has('zona_id')) {
            $query->where('d.zona_id', $request->zona_id);
        }

        // Filtro por sucursal
        if ($request->has('sucursal_id')) {
            $query->where('d.sucursal_id', $request->sucursal_id);
        }

        // Filtro por estado del préstamo
        if ($request->has('estado')) {
            $query->where('p.estado', $request->estado);
        }

        // Filtro por usuario (analista, asesor o jcc)
        if ($includeUsuarioFilter && $request->has('usuario_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('ca.analista_id', $request->usuario_id)
                    ->orWhere('cas.asesor_id', $request->usuario_id)
                    ->orWhere('cj.jcc_id', $request->usuario_id);
            });
        }

        // Filtro por tipo de solicitud
        if ($request->has('tipo_solicitud')) {
            $query->where('p.tipo_solicitud', $request->tipo_solicitud);
        }

        // Nuevo filtro por rol - SOLO si la consulta tiene JOIN con roles
        if ($includeRolFilter && $request->has('rol')) {
            $query->where(function ($q) use ($request) {
                $q->where('r.name', $request->rol)
                  ->orWhere('r.name', 'like', '%' . $request->rol . '%');
            });
        }
    }
}