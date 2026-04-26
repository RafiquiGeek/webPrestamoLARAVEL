<?php

namespace App\Http\Controllers;

use App\Models\ModuleTimeTracking;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('name')->get();

        // Determinar si mostrar vista agrupada o detallada
        $vistaAgrupada = ! $request->has('detallado') || $request->detallado !== '1';

        if ($vistaAgrupada) {
            $usuariosConActividad = $this->getUsuariosConActividad($request);

            return view('admin.auditoria.index-agrupado', compact('usuariosConActividad', 'users'));
        } else {
            $activities = $this->getActivities($request);

            return view('admin.auditoria.index', compact('activities', 'users'));
        }
    }

    public function usuario($userId, Request $request)
    {
        $user = User::findOrFail($userId);
        $activities = UserActivity::with('user')
            ->where('user_id', $userId)
            ->when($request->fecha_inicio, function ($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->fecha_inicio);
            })
            ->when($request->fecha_fin, function ($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->fecha_fin);
            })
            ->when($request->accion, function ($query) use ($request) {
                return $query->where('action', $request->accion);
            })
            ->when($request->recurso, function ($query) use ($request) {
                return $query->where('resource', $request->recurso);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.auditoria.usuario', compact('user', 'activities'));
    }

    public function resumen()
    {
        $resumenHoy = $this->getResumenDia(today());
        $resumenAyer = $this->getResumenDia(today()->subDay());
        $usuariosMasActivos = $this->getUsuariosMasActivos();
        $accionesMasComunes = $this->getAccionesMasComunes();
        $recursosMasAccedidos = $this->getRecursosMasAccedidos();

        return view('admin.auditoria.resumen', compact(
            'resumenHoy',
            'resumenAyer',
            'usuariosMasActivos',
            'accionesMasComunes',
            'recursosMasAccedidos'
        ));
    }

    public function exportar(Request $request)
    {
        $activities = $this->getActivities($request, false);

        $filename = 'auditoria_'.now()->format('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
        ];

        $callback = function () use ($activities) {
            $file = fopen('php://output', 'w');

            // Headers del CSV
            fputcsv($file, [
                'Fecha/Hora',
                'Usuario',
                'Acción',
                'Recurso',
                'ID Recurso',
                'Descripción',
                'IP',
                'URL',
            ]);

            foreach ($activities as $activity) {
                fputcsv($file, [
                    $activity->created_at->format('d/m/Y H:i:s'),
                    $activity->user->name,
                    $activity->action,
                    $activity->resource,
                    $activity->resource_id,
                    $activity->description,
                    $activity->ip_address,
                    $activity->url,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getActivities(Request $request, $paginate = true)
    {
        $query = UserActivity::with('user')
            ->when($request->usuario_id, function ($query) use ($request) {
                return $query->where('user_id', $request->usuario_id);
            })
            ->when($request->fecha_inicio, function ($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->fecha_inicio);
            })
            ->when($request->fecha_fin, function ($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->fecha_fin);
            })
            ->when($request->accion, function ($query) use ($request) {
                return $query->where('action', $request->accion);
            })
            ->when($request->recurso, function ($query) use ($request) {
                return $query->where('resource', $request->recurso);
            })
            ->when($request->buscar, function ($query) use ($request) {
                return $query->where(function ($q) use ($request) {
                    $q->where('description', 'like', '%'.$request->buscar.'%')
                        ->orWhere('url', 'like', '%'.$request->buscar.'%');
                });
            })
            ->orderBy('created_at', 'desc');

        return $paginate ? $query->paginate(50) : $query->get();
    }

    private function getResumenDia($fecha)
    {
        return [
            'total_actividades' => UserActivity::whereDate('created_at', $fecha)->count(),
            'usuarios_activos' => UserActivity::whereDate('created_at', $fecha)
                ->distinct('user_id')->count(),
            'acciones_por_tipo' => UserActivity::whereDate('created_at', $fecha)
                ->select('action', DB::raw('count(*) as total'))
                ->groupBy('action')
                ->pluck('total', 'action'),
        ];
    }

    private function getUsuariosMasActivos($dias = 7)
    {
        return UserActivity::with('user')
            ->where('created_at', '>=', now()->subDays($dias))
            ->select('user_id', DB::raw('count(*) as total_actividades'))
            ->groupBy('user_id')
            ->orderBy('total_actividades', 'desc')
            ->limit(10)
            ->get();
    }

    private function getAccionesMasComunes($dias = 7)
    {
        return UserActivity::where('created_at', '>=', now()->subDays($dias))
            ->select('action', DB::raw('count(*) as total'))
            ->groupBy('action')
            ->orderBy('total', 'desc')
            ->get();
    }

    private function getRecursosMasAccedidos($dias = 7)
    {
        return UserActivity::where('created_at', '>=', now()->subDays($dias))
            ->select('resource', DB::raw('count(*) as total'))
            ->groupBy('resource')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();
    }

    public function sesiones(Request $request)
    {
        $users = User::orderBy('name')->get();

        // Por defecto, mostrar solo usuarios con sesiones activas
        // Si el usuario quiere ver historial completo, puede activar el toggle
        $verHistorial = $request->has('ver_historial') && $request->ver_historial == '1';

        if (!$verHistorial && !$request->has('usuario_id') && !$request->has('fecha_inicio') && !$request->has('estado')) {
            // Vista por defecto: solo sesiones activas agrupadas por usuario
            $sessions = $this->getActiveSessions($request);
        } else {
            // Vista de historial con filtros
            $sessions = $this->getSessions($request);
        }

        return view('admin.auditoria.sesiones', compact('sessions', 'users', 'verHistorial'));
    }

    private function getActiveSessions(Request $request)
    {
        // Obtener solo usuarios con sesiones activas (última sesión activa de cada usuario)
        return UserSession::with('user', 'moduleTimeTracking')
            ->whereNull('logout_time')
            ->whereIn('id', function($query) {
                $query->selectRaw('MAX(id)')
                    ->from('user_sessions')
                    ->whereNull('logout_time')
                    ->groupBy('user_id');
            })
            ->orderBy('login_time', 'desc')
            ->paginate(50);
    }

    public function sesionesUsuario($userId, Request $request)
    {
        $user = User::findOrFail($userId);
        $sessions = UserSession::with(['user', 'moduleTimeTracking'])
            ->where('user_id', $userId)
            ->when($request->fecha_inicio, function ($query) use ($request) {
                return $query->whereDate('login_time', '>=', $request->fecha_inicio);
            })
            ->when($request->fecha_fin, function ($query) use ($request) {
                return $query->whereDate('login_time', '<=', $request->fecha_fin);
            })
            ->orderBy('login_time', 'desc')
            ->paginate(20);

        // Calcular estadísticas del usuario
        $estadisticas = $this->getEstadisticasUsuario($userId, $request);

        return view('admin.auditoria.sesiones-usuario', compact('user', 'sessions', 'estadisticas'));
    }

    public function tiempoModulos($userId, Request $request)
    {
        $user = User::findOrFail($userId);

        $tiemposPorModulo = ModuleTimeTracking::getTotalTimeByModule(
            $userId,
            $request->fecha_inicio,
            $request->fecha_fin
        );

        $tiemposPorDia = ModuleTimeTracking::getTotalTimeByDay(
            $userId,
            $request->fecha_inicio,
            $request->fecha_fin
        );

        return view('admin.auditoria.tiempo-modulos', compact('user', 'tiemposPorModulo', 'tiemposPorDia'));
    }

    public function reporteSesiones(Request $request)
    {
        $usuarios = User::withCount(['userSessions as total_sesiones'])
            ->with(['userSessions' => function ($query) use ($request) {
                $query->when($request->fecha_inicio, function ($q) use ($request) {
                    return $q->whereDate('login_time', '>=', $request->fecha_inicio);
                })
                    ->when($request->fecha_fin, function ($q) use ($request) {
                        return $q->whereDate('login_time', '<=', $request->fecha_fin);
                    });
            }])
            ->orderBy('name')
            ->get();

        // Calcular estadísticas adicionales para cada usuario
        foreach ($usuarios as $usuario) {
            $usuario->tiempo_total = UserSession::calculateTotalDuration(
                $usuario->id,
                $request->fecha_inicio,
                $request->fecha_fin
            );

            $usuario->tiempo_promedio = $usuario->total_sesiones > 0
                ? $usuario->tiempo_total / $usuario->total_sesiones
                : 0;

            $usuario->ultima_sesion = UserSession::where('user_id', $usuario->id)
                ->latest('login_time')
                ->first();
        }

        return view('admin.auditoria.reporte-sesiones', compact('usuarios'));
    }

    private function getSessions(Request $request, $paginate = true)
    {
        $query = UserSession::with('user')
            ->when($request->usuario_id, function ($query) use ($request) {
                return $query->where('user_id', $request->usuario_id);
            })
            ->when($request->fecha_inicio, function ($query) use ($request) {
                return $query->whereDate('login_time', '>=', $request->fecha_inicio);
            })
            ->when($request->fecha_fin, function ($query) use ($request) {
                return $query->whereDate('login_time', '<=', $request->fecha_fin);
            })
            ->when($request->estado, function ($query) use ($request) {
                if ($request->estado === 'activa') {
                    return $query->active();
                } elseif ($request->estado === 'finalizada') {
                    return $query->completed();
                }
            })
            ->orderBy('login_time', 'desc');

        return $paginate ? $query->paginate(50) : $query->get();
    }

    private function getEstadisticasUsuario($userId, $request)
    {
        $fechaInicio = $request->fecha_inicio ?: now()->subMonth()->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?: now()->format('Y-m-d');

        return [
            'total_sesiones' => UserSession::byUser($userId)
                ->whereBetween('login_time', [$fechaInicio, $fechaFin])
                ->count(),
            'tiempo_total' => UserSession::calculateTotalDuration($userId, $fechaInicio, $fechaFin),
            'sesiones_hoy' => UserSession::byUser($userId)->today()->count(),
            'sesion_mas_larga' => UserSession::byUser($userId)
                ->completed()
                ->whereBetween('login_time', [$fechaInicio, $fechaFin])
                ->orderBy('total_duration', 'desc')
                ->first(),
            'modulos_mas_usados' => ModuleTimeTracking::getTotalTimeByModule($userId, $fechaInicio, $fechaFin)
                ->take(5),
        ];
    }

    private function getUsuariosConActividad(Request $request)
    {
        $fechaInicio = $request->fecha_inicio ?: now()->subDays(7)->format('Y-m-d');
        $fechaFin = $request->fecha_fin ?: now()->format('Y-m-d');

        return User::select('users.*')
            ->with(['userActivities' => function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->orderBy('created_at', 'desc');
            }])
            ->withCount(['userActivities as total_actividades' => function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
            }])
            ->having('total_actividades', '>', 0)
            ->orderBy('total_actividades', 'desc')
            ->get()
            ->map(function ($user) use ($fechaInicio, $fechaFin) {
                // Agregar estadísticas adicionales
                $user->actividades_hoy = $user->userActivities()
                    ->whereDate('created_at', today())
                    ->count();

                $user->ultima_actividad = $user->userActivities
                    ->first();

                $user->acciones_por_tipo = $user->userActivities()
                    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->select('action', DB::raw('count(*) as total'))
                    ->groupBy('action')
                    ->pluck('total', 'action');

                $user->recursos_mas_usados = $user->userActivities()
                    ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                    ->select('resource', DB::raw('count(*) as total'))
                    ->groupBy('resource')
                    ->orderBy('total', 'desc')
                    ->limit(3)
                    ->pluck('total', 'resource');

                return $user;
            });
    }

    /**
     * Cerrar sesiones abandonadas manualmente
     */
    public function cerrarSesionesAbandonadas(Request $request)
    {
        try {
            $hours = $request->input('hours', 2);

            // Buscar sesiones abandonadas
            $abandonedSessions = UserSession::whereNull('logout_time')
                ->where('login_time', '<', now()->subHours($hours))
                ->get();

            $closedCount = 0;

            DB::beginTransaction();

            foreach ($abandonedSessions as $session) {
                // Buscar el último tracking de módulo
                $lastTracking = ModuleTimeTracking::where('user_session_id', $session->id)
                    ->whereNotNull('end_time')
                    ->orderBy('end_time', 'desc')
                    ->first();

                // Determinar logout_time
                $logoutTime = $lastTracking && $lastTracking->end_time
                    ? $lastTracking->end_time
                    : $session->login_time->addHour();

                $totalDuration = $logoutTime->diffInSeconds($session->login_time);

                // Actualizar sesión
                $session->update([
                    'logout_time' => $logoutTime,
                    'total_duration' => $totalDuration,
                    'forced_logout' => true,
                ]);

                // Cerrar trackings activos
                $activeTracking = ModuleTimeTracking::where('user_session_id', $session->id)
                    ->whereNull('end_time')
                    ->get();

                foreach ($activeTracking as $tracking) {
                    $endTime = now();
                    $duration = min($endTime->diffInSeconds($tracking->start_time), 7200);

                    $tracking->update([
                        'end_time' => $tracking->start_time->addSeconds($duration),
                        'duration' => $duration,
                    ]);
                }

                $closedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Se cerraron exitosamente {$closedCount} sesiones abandonadas.",
                'closed_count' => $closedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al cerrar sesiones abandonadas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesiones: ' . $e->getMessage(),
            ], 500);
        }
    }
}
