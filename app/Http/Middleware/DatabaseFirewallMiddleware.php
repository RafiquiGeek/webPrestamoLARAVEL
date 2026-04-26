<?php

namespace App\Http\Middleware;

use App\Services\SecurityAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DatabaseFirewallMiddleware
{
    private SecurityAuditService $securityAudit;

    public function __construct(SecurityAuditService $securityAudit)
    {
        $this->securityAudit = $securityAudit;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir acceso directo a administradores autenticados
        if (auth()->check() && auth()->user()->hasRole('Admin')) {
            Cache::put('user_last_activity:'.auth()->id(), time(), 3600);

            return $next($request);
        }

        // Verificar si el sistema está en modo de emergencia
        if ($this->securityAudit->isEmergencyMode()) {
            Log::channel('database-sync-critical')->warning('Acceso bloqueado por modo de emergencia', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Sistema en modo de mantenimiento por seguridad',
                'code' => 'EMERGENCY_MODE',
            ], 503);
        }

        // Verificar IP bloqueada
        if ($this->isIPBlocked($request->ip())) {
            $this->securityAudit->recordFailedAttempt($request->ip());

            Log::channel('database-sync')->warning('Acceso desde IP bloqueada', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'error' => 'Acceso denegado',
                'code' => 'IP_BLOCKED',
            ], 403);
        }

        // Verificar rate limiting por IP
        if (! $this->checkIPRateLimit($request->ip())) {
            $this->securityAudit->recordFailedAttempt($request->ip());

            return response()->json([
                'error' => 'Demasiadas solicitudes',
                'code' => 'RATE_LIMIT_EXCEEDED',
            ], 429);
        }

        // Verificar patrones de ataque en la solicitud
        if ($this->detectAttackPatterns($request)) {
            $this->securityAudit->blockIP($request->ip(), 'Patrón de ataque detectado');

            return response()->json([
                'error' => 'Solicitud sospechosa detectada',
                'code' => 'SUSPICIOUS_REQUEST',
            ], 400);
        }

        // Verificar autenticación para operaciones sensibles
        if ($this->requiresStrongAuth($request) && ! $this->verifyStrongAuth($request)) {
            return response()->json([
                'error' => 'Autenticación adicional requerida',
                'code' => 'STRONG_AUTH_REQUIRED',
            ], 401);
        }

        $response = $next($request);

        // Log de acceso exitoso
        $this->logSuccessfulAccess($request);

        return $response;
    }

    /**
     * Verifica si una IP está bloqueada
     */
    private function isIPBlocked(string $ip): bool
    {
        $blockedIPs = Cache::get('blocked_ips', []);

        return in_array($ip, $blockedIPs);
    }

    /**
     * Verifica rate limiting por IP
     */
    private function checkIPRateLimit(string $ip): bool
    {
        $key = 'ip_rate_limit:'.md5($ip).':'.floor(time() / 60);
        $attempts = Cache::get($key, 0);

        // Límite: 60 requests por minuto por IP
        if ($attempts >= 60) {
            return false;
        }

        Cache::put($key, $attempts + 1, 120);

        return true;
    }

    /**
     * Detecta patrones de ataque en la solicitud
     */
    private function detectAttackPatterns(Request $request): bool
    {
        $suspiciousPatterns = [
            // SQL Injection patterns
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bDELETE\b|\bUPDATE\b|\bDROP\b)/i',
            '/(\bOR\s+1=1|\bAND\s+1=1)/i',
            '/(\'|\"|;|--|\#|\*|\bxp_cmdshell\b)/i',

            // XSS patterns
            '/(<script|<iframe|<object|<embed|javascript:|data:)/i',

            // Command injection patterns
            '/(\||&|;|\$\(|\`)/i',

            // Path traversal
            '/(\.\.\/|\.\.\\\|%2e%2e%2f)/i',
        ];

        // Verificar en query string, post data y headers
        $allInput = array_merge(
            $request->all(),
            [$request->header('User-Agent', ''), $request->path()]
        );

        foreach ($allInput as $value) {
            if (! is_string($value)) {
                continue;
            }

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    Log::channel('database-sync-critical')->critical('Patrón de ataque detectado', [
                        'ip' => $request->ip(),
                        'pattern' => $pattern,
                        'value' => substr($value, 0, 100),
                        'user_agent' => $request->userAgent(),
                    ]);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Verifica si la solicitud requiere autenticación fuerte
     */
    private function requiresStrongAuth(Request $request): bool
    {
        $sensitivePaths = [
            'admin/backup',
            'admin/database',
            'admin/sync',
            'admin/security',
        ];

        $path = $request->path();

        foreach ($sensitivePaths as $sensitivePath) {
            if (str_starts_with($path, $sensitivePath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica autenticación fuerte
     */
    private function verifyStrongAuth(Request $request): bool
    {
        // Verificar que el usuario esté autenticado
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Verificar roles autorizados para operaciones de base de datos
        $authorizedRoles = ['Admin', 'Supervisor'];

        if (! $user->hasAnyRole($authorizedRoles)) {
            return false;
        }

        // Verificar token de sesión reciente (máximo 30 minutos)
        $lastActivity = Cache::get('user_last_activity:'.$user->id);

        if (! $lastActivity || (time() - $lastActivity) > 1800) {
            return false;
        }

        // Actualizar última actividad
        Cache::put('user_last_activity:'.$user->id, time(), 3600);

        return true;
    }

    /**
     * Log de acceso exitoso
     */
    private function logSuccessfulAccess(Request $request): void
    {
        // Solo log para paths importantes
        $importantPaths = ['admin/', 'api/', 'sync/', 'backup/'];

        $shouldLog = false;
        foreach ($importantPaths as $path) {
            if (str_contains($request->path(), $path)) {
                $shouldLog = true;
                break;
            }
        }

        if ($shouldLog) {
            Log::channel('database-sync')->info('Acceso autorizado', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    /**
     * Verifica la geolocalización de la IP (opcional)
     */
    private function checkGeolocation(string $ip): bool
    {
        // Lista de países permitidos (códigos ISO)
        $allowedCountries = config('security.allowed_countries', ['PE', 'US', 'ES']);

        if (empty($allowedCountries)) {
            return true; // Si no hay restricciones, permitir
        }

        // Aquí puedes integrar un servicio de geolocalización
        // Por ejemplo: MaxMind GeoIP, IP2Location, etc.
        // Para este ejemplo, asumimos que está permitido

        return true;
    }

    /**
     * Detecta bots y crawlers maliciosos
     */
    private function detectMaliciousBots(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        $maliciousBots = [
            'sqlmap',
            'nikto',
            'nessus',
            'openvas',
            'nmap',
            'masscan',
            'zap',
            'burp',
            'metasploit',
        ];

        foreach ($maliciousBots as $bot) {
            if (str_contains($userAgent, $bot)) {
                Log::channel('database-sync-critical')->critical('Bot malicioso detectado', [
                    'ip' => $request->ip(),
                    'user_agent' => $userAgent,
                    'detected_bot' => $bot,
                ]);

                return true;
            }
        }

        return false;
    }
}
