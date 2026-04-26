<?php

namespace App\Http\Middleware;

use App\Models\Solicitud;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FondoProviMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->input('solicitud_id') !== null) {
            $solicitud_id = $request->input('solicitud_id');
        } elseif ($request->route('fondo_provicional') !== null) {
            $solicitud_id = $request->route('fondo_provicional');
        }

        $solicitud = Solicitud::find($solicitud_id);

        if ($request->is('admin/solicitudes/fondo-provicional/create') && $solicitud->fondo_provi == 1) {
            return abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        return $next($request);
    }
}
