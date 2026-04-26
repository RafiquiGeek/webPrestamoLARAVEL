<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAllowedIps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!empty($user->allowed_ips) && is_array($user->allowed_ips) && count($user->allowed_ips) > 0) {
                // Remove empty strings and trim whitespace from IPs
                $allowedIps = array_map('trim', $user->allowed_ips);
                $allowedIps = array_filter($allowedIps);

                // Obtener la IP real del cliente (considerando proxies)
                $clientIp = $request->ip();

                // Log para debugging
                Log::info('Verificación de IP', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'client_ip' => $clientIp,
                    'allowed_ips' => $allowedIps,
                    'is_localhost' => in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])
                ]);

                // DESHABILITADO: Permitir localhost automáticamente
                // Descomentar estas líneas si quieres permitir localhost siempre
                // if (in_array($clientIp, ['127.0.0.1', '::1', 'localhost'])) {
                //     Log::info('Acceso permitido desde localhost', ['user_id' => $user->id]);
                //     return $next($request);
                // }

                if (count($allowedIps) > 0 && !in_array($clientIp, $allowedIps)) {
                    Log::warning('Acceso denegado - IP no permitida', [
                        'user_id' => $user->id,
                        'client_ip' => $clientIp,
                        'allowed_ips' => $allowedIps
                    ]);

                    Auth::logout();

                    return redirect()->route('login')
                        ->withErrors(['email' => 'ACCESO NO PERMITIDO DESDE ESTE DISPOSITIVO, CONTACTE A SU ADMINISTRADOR']);
                }

                Log::info('Acceso permitido - IP válida', [
                    'user_id' => $user->id,
                    'client_ip' => $clientIp
                ]);
            }
        }

        return $next($request);
    }
}
