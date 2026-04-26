<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;

class ScheduleRunMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Ejecutar el scheduler solo en entornos de desarrollo o pruebas
        if (App::environment(['local', 'testing'])) {
            Artisan::call('schedule:run');
        }

        return $next($request);
    }
}
