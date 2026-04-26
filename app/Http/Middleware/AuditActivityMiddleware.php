<?php

namespace App\Http\Middleware;

use App\Models\UserActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditActivityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo auditar si el usuario está autenticado
        if (Auth::check() && $this->shouldAudit($request)) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    private function shouldAudit(Request $request): bool
    {
        // No auditar rutas de assets, imágenes, CSS, JS, etc.
        $excludePatterns = [
            '/storage/',
            '/images/',
            '/css/',
            '/js/',
            '/fonts/',
            '/_debugbar/',
            '/livewire/',
            '/telescope/',
            '.css',
            '.js',
            '.png',
            '.jpg',
            '.jpeg',
            '.gif',
            '.svg',
            '.ico',
            '.woff',
            '.woff2',
            '.ttf',
        ];

        $path = $request->getPathInfo();

        foreach ($excludePatterns as $pattern) {
            if (Str::contains($path, $pattern)) {
                return false;
            }
        }

        // No auditar requests AJAX que solo obtienen datos sin modificar
        if ($request->ajax() && $request->isMethod('GET')) {
            return false;
        }

        return true;
    }

    private function logActivity(Request $request, Response $response): void
    {
        try {
            $action = $this->determineAction($request, $response);
            $resource = $this->determineResource($request);
            $resourceId = $this->extractResourceId($request);
            $description = $this->generateDescription($action, $resource, $request);

            UserActivity::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'resource' => $resource,
                'resource_id' => $resourceId,
                'description' => $description,
                'old_values' => $this->getOldValues($request),
                'new_values' => $this->getNewValues($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
            ]);
        } catch (\Exception $e) {
            // No interrumpir el flujo si falla el logging
            \Log::error('Error logging user activity: '.$e->getMessage());
        }
    }

    private function determineAction(Request $request, Response $response): string
    {
        $method = $request->method();
        $statusCode = $response->getStatusCode();

        // Si es una respuesta de error, no es una acción exitosa
        if ($statusCode >= 400) {
            return 'ERROR';
        }

        switch ($method) {
            case 'POST':
                return 'CREATE';
            case 'PUT':
            case 'PATCH':
                return 'UPDATE';
            case 'DELETE':
                return 'DELETE';
            case 'GET':
                return 'VIEW';
            default:
                return strtoupper($method);
        }
    }

    private function determineResource(Request $request): string
    {
        $path = $request->route()?->getName() ?? $request->getPathInfo();

        // Extraer el recurso principal de la ruta
        $segments = explode('/', trim($path, '/'));

        // Buscar segmentos que indiquen recursos conocidos
        $resources = ['clientes', 'prestamos', 'cuotas', 'operaciones', 'usuarios', 'reportes', 'configuracion'];

        foreach ($segments as $segment) {
            if (in_array($segment, $resources)) {
                return $segment;
            }
        }

        // Si no encuentra un recurso específico, usar el primer segmento después de 'admin'
        $adminIndex = array_search('admin', $segments);
        if ($adminIndex !== false && isset($segments[$adminIndex + 1])) {
            return $segments[$adminIndex + 1];
        }

        return $segments[0] ?? 'sistema';
    }

    private function extractResourceId(Request $request): ?string
    {
        // Intentar extraer ID de la ruta
        $route = $request->route();
        if ($route) {
            $parameters = $route->parameters();
            foreach (['id', 'cliente', 'prestamo', 'cuota', 'operacion'] as $param) {
                if (isset($parameters[$param])) {
                    return (string) $parameters[$param];
                }
            }
        }

        // Buscar ID en los datos del request
        $data = $request->all();
        if (isset($data['id'])) {
            return (string) $data['id'];
        }

        return null;
    }

    private function generateDescription(string $action, string $resource, Request $request): string
    {
        $user = Auth::user();
        $userName = $user->name ?? 'Usuario';

        $actionDescriptions = [
            'CREATE' => 'creó',
            'UPDATE' => 'actualizó',
            'DELETE' => 'eliminó',
            'VIEW' => 'consultó',
            'ERROR' => 'intentó acceder a',
        ];

        $actionText = $actionDescriptions[$action] ?? strtolower($action);

        return "{$userName} {$actionText} {$resource}";
    }

    private function getOldValues(Request $request): ?array
    {
        // Para updates, podríamos intentar obtener los valores anteriores
        // Por ahora devolvemos null, pero se puede implementar según necesidades
        return null;
    }

    private function getNewValues(Request $request): ?array
    {
        $data = $request->except(['_token', '_method', 'password', 'password_confirmation']);

        // No guardar datos sensibles
        $sensitiveFields = ['password', 'password_confirmation', 'api_key', 'secret'];
        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }

        return empty($data) ? null : $data;
    }
}
