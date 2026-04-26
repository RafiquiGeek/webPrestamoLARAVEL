<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Compromiso;
use App\Models\Cuota;
use App\Models\EstadoGestion;
use App\Models\Gestion;
use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    /**
     * Mostrar el dashboard principal con notificaciones en tiempo real
     */
    public function index()
    {
        // Cuotas vencidas - ordenadas por días de vencimiento
        $cuotasVencidas = Cuota::where('estado', 0) // Estado 0 = pendiente
            ->where('fecha_pago', '<', now())
            ->with([
                'prestamo.cliente.persona',
                'prestamo.carterasJcc.jcc.persona',
                'prestamo.carterasAsesor.asesor.persona',
                'prestamo.carterasAnalista.analista.persona',
            ])
            ->orderBy(DB::raw('DATEDIFF(NOW(), fecha_pago)'), 'desc')
            ->get();

        // Compromisos de pago - agrupados por estado y recientes
        $compromisos = Compromiso::with([
            'prestamo.cliente.persona',
            'gestion.asesor',
        ])
            ->orderBy('fecha_compromiso_pago', 'asc')
            ->take(50)
            ->get();

        // Asegurarse de que los compromisos están correctamente filtrados
        $compromisosPendientes = $compromisos->where('estado', 0);
        $compromisosCompletados = $compromisos->where('estado', 1);
        $compromisosCancelados = $compromisos->where('estado', 2);

        // Si por alguna razón no hay ninguna colección, crear una vacía
        if (! $compromisosPendientes) {
            $compromisosPendientes = collect();
        }
        if (! $compromisosCompletados) {
            $compromisosCompletados = collect();
        }
        if (! $compromisosCancelados) {
            $compromisosCancelados = collect();
        }

        // Verificar si hay datos para depuración
        // dd('Compromisos: ' . $compromisos->count(), 'Pendientes: ' . $compromisosPendientes->count());

        // Gestiones recientes
        $gestionesRecientes = Gestion::with([
            'prestamo.cliente.persona',
            'estadoGestion',
            'compromiso',
            'asesor',
        ])
            ->orderBy('fecha', 'desc')
            ->take(30)
            ->get();

        // Estados de gestión para los filtros
        $estadosGestion = EstadoGestion::all();

        // Préstamos recientes y cambios de estado
        $prestamos = Prestamo::with([
            'cliente.persona',
            'carterasJcc' => function ($q) {
                $q->where('estado', 1);
            },
            'carterasJcc.jcc.persona',
            'carterasAsesor' => function ($q) {
                $q->where('estado', 1);
            },
            'carterasAsesor.asesor.persona',
            'carterasAnalista' => function ($q) {
                $q->where('estado', 1);
            },
            'carterasAnalista.analista.persona',
        ])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        $prestamosNuevos = $prestamos->where('estado', 'Nuevo');
        $prestamosAprobados = $prestamos->where('estado', 'Aprobado');
        $prestamosRechazados = $prestamos->where('estado', 'Rechazado');

        return view('admin.index', compact(
            'cuotasVencidas',
            'compromisos',
            'compromisosPendientes',
            'compromisosCompletados',
            'compromisosCancelados',
            'gestionesRecientes',
            'estadosGestion',
            'prestamos',
            'prestamosNuevos',
            'prestamosAprobados',
            'prestamosRechazados'
        ));
    }

    /**
     * Actualizar secciones específicas del dashboard mediante AJAX
     */
    public function refreshSection(Request $request)
    {
        $section = $request->input('section');
        $count = 0;
        $html = '';

        switch ($section) {
            case 'cuotas-vencidas':
                // Obtener cuotas vencidas actualizadas
                $cuotasVencidas = Cuota::where('estado', 0)
                    ->where('fecha_pago', '<', now())
                    ->with([
                        'prestamo.cliente.persona',
                        'prestamo.carterasJcc.jcc.persona',
                        'prestamo.carterasAsesor.asesor.persona',
                        'prestamo.carterasAnalista.analista.persona',
                    ])
                    ->orderBy(DB::raw('DATEDIFF(NOW(), fecha_pago)'), 'desc')
                    ->get();

                $count = $cuotasVencidas->count();
                $html = View::make('admin.partials.cuotas-vencidas-table', compact('cuotasVencidas'))->render();
                break;

            case 'compromisos':
                // Obtener compromisos actualizados
                $compromisos = Compromiso::with([
                    'prestamo.cliente.persona',
                    'gestion.asesor',
                ])
                    ->orderBy('fecha_compromiso_pago', 'asc')
                    ->take(50)
                    ->get();

                $count = $compromisos->count();
                $html = View::make('admin.partials.compromisos-table', compact('compromisos'))->render();
                break;

            case 'gestiones':
                // Obtener gestiones actualizadas
                $gestionesRecientes = Gestion::with([
                    'prestamo.cliente.persona',
                    'estadoGestion',
                    'compromiso',
                    'asesor',
                ])
                    ->orderBy('fecha', 'desc')
                    ->take(30)
                    ->get();

                $count = $gestionesRecientes->count();
                $html = View::make('admin.partials.gestiones-table', compact('gestionesRecientes'))->render();
                break;

            case 'prestamos':
                // Obtener préstamos actualizados
                $prestamos = Prestamo::with([
                    'cliente.persona',
                    'carterasJcc' => function ($q) {
                        $q->where('estado', 1);
                    },
                    'carterasJcc.jcc.persona',
                    'carterasAsesor' => function ($q) {
                        $q->where('estado', 1);
                    },
                    'carterasAsesor.asesor.persona',
                    'carterasAnalista' => function ($q) {
                        $q->where('estado', 1);
                    },
                    'carterasAnalista.analista.persona',
                ])
                    ->orderBy('created_at', 'desc')
                    ->take(50)
                    ->get();

                $count = $prestamos->count();
                $html = View::make('admin.partials.prestamos-table', compact('prestamos'))->render();
                break;
        }

        return response()->json([
            'success' => true,
            'count' => $count,
            'html' => $html,
        ]);
    }

    /**
     * Comprobar actualizaciones y devolver recuentos actuales
     */
    public function checkUpdates()
    {
        // Contar cuotas vencidas
        $cuotasCount = Cuota::where('estado', 0)
            ->where('fecha_pago', '<', now())
            ->count();

        // Contar compromisos pendientes
        $compromisosCount = Compromiso::where('estado', 0)->count();

        // Contar gestiones de los últimos 7 días
        $gestionesCount = Gestion::where('fecha', '>=', now()->subDays(7))->count();

        // Contar préstamos nuevos de los últimos 30 días
        $prestamosCount = Prestamo::where('created_at', '>=', now()->subDays(30))->count();

        return response()->json([
            'cuotas' => $cuotasCount,
            'compromisos' => $compromisosCount,
            'gestiones' => $gestionesCount,
            'prestamos' => $prestamosCount,
        ]);
    }

    /**
     * Obtener estadísticas para el dashboard
     */
    public function getStatistics()
    {
        // Cuotas vencidas por mes (últimos 6 meses)
        $cuotasPorMes = Cuota::where('estado', 0)
            ->where('fecha_pago', '<', now())
            ->where('fecha_pago', '>=', now()->subMonths(6))
            ->select(
                DB::raw('MONTH(fecha_pago) as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->pluck('total', 'mes')
            ->toArray();

        // Compromisos por mes (últimos 6 meses)
        $compromisosPorMes = Compromiso::where('fecha_compromiso_pago', '>=', now()->subMonths(6))
            ->select(
                DB::raw('MONTH(fecha_compromiso_pago) as mes'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->pluck('total', 'mes')
            ->toArray();

        // Gestiones por estado
        $gestionesPorEstado = Gestion::select(
            'estados_gestion.estado',
            DB::raw('COUNT(*) as total')
        )
            ->join('estados_gestion', 'gestiones.estado_id', '=', 'estados_gestion.id')
            ->groupBy('estado_id', 'estados_gestion.estado')
            ->get()
            ->pluck('total', 'estado')
            ->toArray();

        // Préstamos por estado
        $prestamosPorEstado = Prestamo::select(
            'estado',
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('estado')
            ->get()
            ->pluck('total', 'estado')
            ->toArray();

        return response()->json([
            'cuotasPorMes' => $cuotasPorMes,
            'compromisosPorMes' => $compromisosPorMes,
            'gestionesPorEstado' => $gestionesPorEstado,
            'prestamosPorEstado' => $prestamosPorEstado,
        ]);
    }
}
