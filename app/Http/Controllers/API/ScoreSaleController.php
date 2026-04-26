<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScoreSaleController extends Controller
{
    private const NIVELES = [
        'BRONCE' => ['puntaje' => 0],
        'PLATA' => ['puntaje' => 40],
        'ORO' => ['puntaje' => 50],
        'DIAMANTE' => ['puntaje' => 80]
    ];

    /**
     * Score de ventas SOLO ASESORES - Consolidado por usuario
     */

    public function scoreSale(Request $request)
    {
        $query = DB::table('prestamos as p')
            // Joins de Zonas
            ->join('direcciones as d', 'p.direccion_cobro_id', '=', 'd.id')
            ->join('zonas as z', 'd.zona_id', '=', 'z.id')

            // Joins del Asesor (Usuario)
            ->join('carteras_asesor as cas', 'p.id', '=', 'cas.prestamo_id')
            ->join('users as u', 'u.id', '=', 'cas.asesor_id')
            ->leftJoin('personas as per', 'u.persona_id', '=', 'per.id') // Persona del Asesor
            ->join('model_has_roles as mhr', function ($join) {
                $join->on('u.id', '=', 'mhr.model_id')
                    ->where('mhr.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles as r', 'mhr.role_id', '=', 'r.id')

            // JOINS: Cliente y Persona del Cliente
            ->join('clientes as c', 'p.cliente_id', '=', 'c.id')
            ->join('personas as cliente_per', 'c.persona_id', '=', 'cliente_per.id')

            ->select(
                'u.id as usuario_id',
                DB::raw('IFNULL(CONCAT(per.nombres, " ", per.ape_pat, " ", per.ape_mat), "SIN ASIGNAR") as usuario_nombre'),
                'u.email as usuario_email',
                'z.id as zona_id',
                'z.nombre as zona_nombre',
                'p.id as prestamo_id',
                'p.tipo_solicitud',
                'p.cantidad_solicitada',
                'p.saldo_restante',
                DB::raw('CASE WHEN LOWER(p.estado) IN ("moroso", "vigente moroso") THEN 1 ELSE 0 END as es_moroso'),

                // ✅ NOMBRE DEL CLIENTE (Concatenado)
                DB::raw('CONCAT(cliente_per.nombres, " ", cliente_per.ape_pat, " ", cliente_per.ape_mat) as cliente_nombre')
            )
            ->whereNotNull('p.fecha_atencion')
            ->whereNotNull('cas.asesor_id')
            ->where('r.name', '=', 'Asesor');

        $this->aplicarFiltros($query, $request);

        $resultados = $query->get();

        // AGRUPAR POR USUARIO
        $reporteFinal = $resultados->groupBy('usuario_id')->map(function ($prestamosUsuario) {
            $u = $prestamosUsuario->first();

            // Ventas brutas solo de solicitudes NUEVAS
            $ventasBrutas = $prestamosUsuario->where('tipo_solicitud', 'Nueva')->count();

            $morosos = $prestamosUsuario->sum('es_moroso');

            // Puntaje = Ventas brutas (solo nuevas) - morosos
            $puntajeTotal = $ventasBrutas - $morosos;
            $nivel = $this->determinarNivel($puntajeTotal);

            // DESGLOSE POR ZONA
            $zonasAsignadas = $prestamosUsuario->groupBy('zona_id')->map(function ($itemsEnZona) {
                $z = $itemsEnZona->first();

                // Extraer solo ventas nuevas en esta zona
                $ventasNuevasEnZona = $itemsEnZona->where('tipo_solicitud', 'Nueva')->map(function ($item) {
                    return [
                        'prestamo_id' => $item->prestamo_id,
                        'cliente_nombre' => trim($item->cliente_nombre),
                        'tipo_solicitud' => $item->tipo_solicitud
                    ];
                })->values();


                // EXTRAER SOLO LOS CLIENTES MOROSOS DE ESTA ZONA
                $listaMorosos = $itemsEnZona->where('es_moroso', 1)->map(function ($item) {
                    return [
                        'prestamo_id' => $item->prestamo_id,
                        'cliente_nombre' => trim($item->cliente_nombre),
                        'tipo_solicitud' => $item->tipo_solicitud
                    ];
                })->values();

                return [
                    'id' => $z->zona_id,
                    'nombre' => $z->zona_nombre,
                    'total_prestamos' => $itemsEnZona->count(),
                    'nuevas' => $itemsEnZona->where('tipo_solicitud', 'Nueva')->count(),
                    'renovaciones' => $itemsEnZona->where('tipo_solicitud', 'Renovación')->count(),
                    'lista_morosos' => $listaMorosos,
                    'ventas_nuevas_en_zona' => $ventasNuevasEnZona
                ];
            })->values();

            return [
                'usuario_id' => $u->usuario_id,
                'usuario_nombre' => $u->usuario_nombre,
                'usuario_email' => $u->usuario_email,
                'nivel' => $nivel,
                'puntaje' => $puntajeTotal,
                'ventas_brutas' => $ventasBrutas, // ✅ Ahora solo nuevas
                'clientes_morosos' => $morosos,
                'total_nuevas' => $prestamosUsuario->where('tipo_solicitud', 'Nueva')->count(),
                'total_renovaciones' => $prestamosUsuario->where('tipo_solicitud', 'Renovación')->count(),
                'monto_total' => $prestamosUsuario->sum('cantidad_solicitada'),
                'saldo_total' => $prestamosUsuario->sum('saldo_restante'),
                'zonas' => $zonasAsignadas
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $reporteFinal
        ]);
    }

    /**
     * Aplica filtros comunes
     */
    private function aplicarFiltros(&$query, Request $request)
    {
        // Filtro por RANGO DE FECHAS
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $query->whereBetween('p.fecha_atencion', [
                $request->fecha_inicio . ' 00:00:00',
                $request->fecha_fin . ' 23:59:59'
            ]);
        } else {
            // Filtro por MES y AÑO
            if ($request->has('mes')) {
                $query->whereRaw('MONTH(p.fecha_atencion) = ?', [$request->mes]);
            } else {
                $query->whereRaw('MONTH(p.fecha_atencion) = ?', [date('n')]);
            }

            if ($request->has('anio')) {
                $query->whereRaw('YEAR(p.fecha_atencion) = ?', [$request->anio]);
            } else {
                $query->whereRaw('YEAR(p.fecha_atencion) = ?', [date('Y')]);
            }
        }

        if ($request->has('zona_id')) {
            $query->where('d.zona_id', $request->zona_id);
        }

        if ($request->has('sucursal_id')) {
            $query->where('d.sucursal_id', $request->sucursal_id);
        }

        if ($request->has('usuario_id')) {
            $query->where('cas.asesor_id', $request->usuario_id);
        }

        if ($request->has('estado')) {
            $query->where('p.estado', $request->estado);
        }

        if ($request->has('tipo_solicitud')) {
            $query->where('p.tipo_solicitud', $request->tipo_solicitud);
        }
    }

    private function determinarNivel($puntaje)
    {
        $nivelesOrdenados = self::NIVELES;
        uasort($nivelesOrdenados, function ($a, $b) {
            return $b['puntaje'] - $a['puntaje'];
        });

        foreach ($nivelesOrdenados as $nivel => $config) {
            if ($puntaje >= $config['puntaje']) {
                return $nivel;
            }
        }

        return 'BRONCE';
    }

    private function puntajeParaSiguienteNivel($puntajeActual, $nivelActual)
    {
        $ordenNiveles = ['BRONCE', 'PLATA', 'ORO', 'DIAMANTE'];
        $indexActual = array_search($nivelActual, $ordenNiveles);

        if ($indexActual === false || $indexActual === count($ordenNiveles) - 1) {
            return [
                'siguiente_nivel' => null,
                'puntos_necesarios' => 0,
                'puntos_faltantes' => 0,
                'mensaje' => 'Nivel máximo alcanzado'
            ];
        }

        $siguienteNivel = $ordenNiveles[$indexActual + 1];
        $puntosNecesarios = self::NIVELES[$siguienteNivel]['puntaje'];
        $puntosFaltantes = $puntosNecesarios - $puntajeActual;

        return [
            'siguiente_nivel' => $siguienteNivel,
            'puntos_necesarios' => $puntosNecesarios,
            'puntos_faltantes' => max(0, $puntosFaltantes),
            'mensaje' => $puntosFaltantes > 0
                ? "Necesitas {$puntosFaltantes} puntos más para llegar a {$siguienteNivel}"
                : "¡Listo para subir a {$siguienteNivel}!"
        ];
    }
}