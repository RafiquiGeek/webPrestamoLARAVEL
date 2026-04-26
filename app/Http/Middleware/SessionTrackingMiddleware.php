<?php

namespace App\Http\Middleware;

use App\Models\ModuleTimeTracking;
use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SessionTrackingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $this->shouldTrack($request)) {
            $this->trackUserSession($request);
            $this->trackModuleTime($request);
        }

        return $next($request);
    }

    private function shouldTrack(Request $request): bool
    {
        // No trackear rutas de assets, AJAX, etc.
        $excludePatterns = [
            '/storage/',
            '/images/',
            '/css/',
            '/js/',
            '/fonts/',
            '/_debugbar/',
            '/livewire/',
            '/telescope/',
            '.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.ico',
        ];

        $path = $request->getPathInfo();

        foreach ($excludePatterns as $pattern) {
            if (Str::contains($path, $pattern)) {
                return false;
            }
        }

        return true;
    }

    private function trackUserSession(Request $request): void
    {
        try {
            $sessionId = session()->getId();
            $userId = Auth::id();

            // Buscar sesión activa
            $userSession = UserSession::where('session_id', $sessionId)
                ->where('user_id', $userId)
                ->active()
                ->first();

            if (! $userSession) {
                // Crear nueva sesión
                $userSession = UserSession::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'login_time' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            // Guardar sesión en cache para uso posterior
            cache()->put("user_session_{$userId}_{$sessionId}", $userSession->id, 3600);

        } catch (\Exception $e) {
            \Log::error('Error tracking user session: '.$e->getMessage());
        }
    }

    private function trackModuleTime(Request $request): void
    {
        try {
            $userId = Auth::id();
            $sessionId = session()->getId();
            $userSessionId = cache()->get("user_session_{$userId}_{$sessionId}");

            if (! $userSessionId) {
                return;
            }

            $currentModule = $this->determineModule($request);
            $currentSection = $this->determineSection($request);
            $currentUrl = $request->fullUrl();

            // Buscar tracking activo anterior
            $previousTracking = ModuleTimeTracking::where('user_id', $userId)
                ->where('user_session_id', $userSessionId)
                ->active()
                ->latest()
                ->first();

            // Si hay tracking anterior, finalizarlo
            if ($previousTracking) {
                $endTime = now();
                $duration = $endTime->diffInSeconds($previousTracking->start_time);

                $previousTracking->update([
                    'end_time' => $endTime,
                    'duration' => $duration,
                ]);
            }

            // Solo crear nuevo tracking si no es la misma página
            if (! $previousTracking ||
                $previousTracking->module_name !== $currentModule ||
                $previousTracking->module_section !== $currentSection) {

                ModuleTimeTracking::create([
                    'user_id' => $userId,
                    'user_session_id' => $userSessionId,
                    'module_name' => $currentModule,
                    'module_section' => $currentSection,
                    'start_time' => now(),
                    'url' => $currentUrl,
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error tracking module time: '.$e->getMessage());
        }
    }

    private function determineModule(Request $request): string
    {
        $path = $request->getPathInfo();
        $segments = explode('/', trim($path, '/'));

        // Módulos conocidos del sistema
        $modules = [
            'clientes' => 'Clientes',
            'prestamos' => 'Préstamos',
            'cuotas' => 'Cuotas',
            'operaciones' => 'Operaciones',
            'reportes' => 'Reportes',
            'usuarios' => 'Usuarios',
            'caja' => 'Caja',
            'cartera' => 'Cartera',
            'configuracion' => 'Configuración',
            'auditoria' => 'Auditoría',
            'gestiones' => 'Gestiones',
            'moras' => 'Moras',
            'compromisos' => 'Compromisos',
            'sucursales' => 'Sucursales',
            'dashboard' => 'Dashboard',
            'asistencia' => 'Asistencia',
            'database-sync' => 'Sincronización',
        ];

        // Buscar módulo en los segmentos
        foreach ($segments as $segment) {
            if (isset($modules[$segment])) {
                return $modules[$segment];
            }
        }

        // Si no encuentra módulo específico, usar el primer segmento después de 'admin'
        $adminIndex = array_search('admin', $segments);
        if ($adminIndex !== false && isset($segments[$adminIndex + 1])) {
            $moduleKey = $segments[$adminIndex + 1];

            return $modules[$moduleKey] ?? ucfirst($moduleKey);
        }

        return 'Sistema';
    }

    private function determineSection(Request $request): ?string
    {
        $path = $request->getPathInfo();
        $method = $request->method();

        // Determinar sección basada en la URL y método
        if (Str::contains($path, '/create')) {
            return 'Crear';
        }

        if (Str::contains($path, '/edit')) {
            return 'Editar';
        }

        if (Str::contains($path, '/show')) {
            return 'Ver';
        }

        if (preg_match('/\/\d+$/', $path) && $method === 'GET') {
            return 'Ver';
        }

        if (Str::endsWith($path, '/index') || Str::contains($path, '?')) {
            return 'Listado';
        }

        // Secciones específicas
        $sections = [
            'reportes' => 'Reportes',
            'export' => 'Exportar',
            'import' => 'Importar',
            'dashboard' => 'Dashboard',
            'resumen' => 'Resumen',
        ];

        foreach ($sections as $key => $value) {
            if (Str::contains($path, $key)) {
                return $value;
            }
        }

        return $method === 'GET' ? 'Ver' : 'Acción';
    }

    public function terminate(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $this->finalizeCurrentTracking();
        }
    }

    private function finalizeCurrentTracking(): void
    {
        try {
            $userId = Auth::id();
            $sessionId = session()->getId();
            $userSessionId = cache()->get("user_session_{$userId}_{$sessionId}");

            if (! $userSessionId) {
                return;
            }

            // Finalizar tracking activo
            $activeTracking = ModuleTimeTracking::where('user_id', $userId)
                ->where('user_session_id', $userSessionId)
                ->active()
                ->latest()
                ->first();

            if ($activeTracking) {
                $endTime = now();
                $duration = $endTime->diffInSeconds($activeTracking->start_time);

                $activeTracking->update([
                    'end_time' => $endTime,
                    'duration' => $duration,
                ]);
            }

        } catch (\Exception $e) {
            \Log::error('Error finalizing tracking: '.$e->getMessage());
        }
    }
}
