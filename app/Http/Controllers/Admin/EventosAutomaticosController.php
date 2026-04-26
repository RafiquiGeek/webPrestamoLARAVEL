<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventoAutomatico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EventosAutomaticosController extends Controller
{
    /**
     * VENTANA PRINCIPAL DE MONITOREO - Eventos automáticos en tiempo real
     */
    public function index(Request $request)
    {
        $query = EventoAutomatico::with(['prestamo', 'cuota', 'operacion', 'usuario'])
            ->orderBy('created_at', 'desc');

        // Filtros opcionales
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('resultado')) {
            $query->where('resultado', $request->resultado);
        }

        if ($request->filled('prestamo_id')) {
            $query->where('prestamo_id', $request->prestamo_id);
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        // Paginación
        $eventos = $query->paginate(50);

        // Estadísticas generales
        $estadisticas = [
            'total_eventos' => EventoAutomatico::count(),
            'eventos_hoy' => EventoAutomatico::whereDate('created_at', today())->count(),
            'eventos_exitosos_hoy' => EventoAutomatico::whereDate('created_at', today())->exitosos()->count(),
            'eventos_fallidos_hoy' => EventoAutomatico::whereDate('created_at', today())->fallidos()->count(),
            'categorias' => EventoAutomatico::selectRaw('categoria, COUNT(*) as total')
                ->whereDate('created_at', '>=', now()->subDays(7))
                ->groupBy('categoria')
                ->pluck('total', 'categoria')
                ->toArray(),
            'tiempo_promedio_procesamiento' => EventoAutomatico::whereDate('created_at', today())
                ->avg('tiempo_procesamiento'),
        ];

        return view('admin.eventos-automaticos.index', compact('eventos', 'estadisticas'));
    }

    /**
     * VISTA DETALLADA DE UN EVENTO ESPECÍFICO
     */
    public function show($id)
    {
        $evento = EventoAutomatico::with(['prestamo', 'cuota', 'operacion', 'usuario'])
            ->findOrFail($id);

        return view('admin.eventos-automaticos.show', compact('evento'));
    }

    /**
     * API PARA EVENTOS EN TIEMPO REAL (AJAX)
     */
    public function api(Request $request)
    {
        $eventos = EventoAutomatico::with(['prestamo', 'usuario'])
            ->when($request->filled('ultimo_id'), function ($query) use ($request) {
                $query->where('id', '>', $request->ultimo_id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'eventos' => $eventos->map(function ($evento) {
                return [
                    'id' => $evento->id,
                    'prestamo_id' => $evento->prestamo_id,
                    'evento' => $evento->evento,
                    'categoria' => $evento->categoria,
                    'mensaje_humano' => $evento->mensaje_humano,
                    'resultado' => $evento->resultado,
                    'icono' => $evento->icono,
                    'color' => $evento->color,
                    'duracion' => $evento->duracion_humana,
                    'timestamp' => $evento->created_at->format('H:i:s'),
                    'fecha_completa' => $evento->created_at->format('d/m/Y H:i:s'),
                    'usuario' => $evento->usuario?->name ?? 'Sistema',
                ];
            }),
            'ultimo_id' => $eventos->first()?->id ?? 0,
        ]);
    }

    /**
     * ESTADÍSTICAS DEL SISTEMA AUTOMÁTICO
     */
    public function estadisticas(Request $request)
    {
        $periodo = $request->get('periodo', 7); // días

        $estadisticas = [
            'resumen_periodo' => [
                'total_eventos' => EventoAutomatico::where('created_at', '>=', now()->subDays($periodo))->count(),
                'eventos_por_dia' => EventoAutomatico::selectRaw('DATE(created_at) as fecha, COUNT(*) as total')
                    ->where('created_at', '>=', now()->subDays($periodo))
                    ->groupBy('fecha')
                    ->orderBy('fecha')
                    ->get(),
                'eventos_por_categoria' => EventoAutomatico::selectRaw('categoria, COUNT(*) as total, AVG(tiempo_procesamiento) as tiempo_promedio')
                    ->where('created_at', '>=', now()->subDays($periodo))
                    ->groupBy('categoria')
                    ->get(),
                'eventos_por_resultado' => EventoAutomatico::selectRaw('resultado, COUNT(*) as total')
                    ->where('created_at', '>=', now()->subDays($periodo))
                    ->groupBy('resultado')
                    ->get(),
            ],
            'prestamos_mas_activos' => EventoAutomatico::selectRaw('prestamo_id, COUNT(*) as total_eventos')
                ->with('prestamo:id,cliente_id')
                ->where('created_at', '>=', now()->subDays($periodo))
                ->groupBy('prestamo_id')
                ->orderBy('total_eventos', 'desc')
                ->limit(10)
                ->get(),
            'performance' => [
                'tiempo_promedio_global' => EventoAutomatico::where('created_at', '>=', now()->subDays($periodo))
                    ->avg('tiempo_procesamiento'),
                'evento_mas_lento' => EventoAutomatico::where('created_at', '>=', now()->subDays($periodo))
                    ->orderBy('tiempo_procesamiento', 'desc')
                    ->first(),
                'evento_mas_rapido' => EventoAutomatico::where('created_at', '>=', now()->subDays($periodo))
                    ->where('tiempo_procesamiento', '>', 0)
                    ->orderBy('tiempo_procesamiento', 'asc')
                    ->first(),
            ],
        ];

        return response()->json($estadisticas);
    }

    /**
     * LIMPIAR EVENTOS ANTIGUOS (mantenimiento)
     */
    public function limpiar(Request $request)
    {
        $diasConservar = $request->get('dias', 30);

        $eliminados = EventoAutomatico::where('created_at', '<', now()->subDays($diasConservar))
            ->delete();

        Log::info("🧹 Limpieza de eventos automáticos: {$eliminados} eventos eliminados (conservando últimos {$diasConservar} días)");

        return response()->json([
            'success' => true,
            'eliminados' => $eliminados,
            'mensaje' => "Se eliminaron {$eliminados} eventos automáticos anteriores a {$diasConservar} días",
        ]);
    }
}
