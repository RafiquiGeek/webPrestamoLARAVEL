<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Compromiso;
use App\Models\Cuota;
use App\Models\Gasto;
use App\Models\Gestion;
use App\Models\Mora;
use App\Models\Operacion;
use App\Models\Prestamo;
use App\Observers\CompromisoObserver;
use App\Observers\CuotaObserver;
use App\Observers\DatabaseSyncObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('cartera-helper', function () {
            return new \App\Helpers\CarteraHelper;
        });

        // Registrar el servicio de estado de préstamos
        $this->app->singleton(\App\Services\EstadoPrestamoService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.bootstrap-5');
        Paginator::defaultSimpleView('pagination.simple-bootstrap-5');

        // View composer para optimizar layout admin
        view()->composer(['layouts.admin', 'layouts.admin-optimized'], \App\View\Composers\AdminLayoutComposer::class);

        // Observers existentes
        Cuota::observe(CuotaObserver::class);
        Compromiso::observe(CompromisoObserver::class);

        // Observer para sincronización de bases de datos
        // Solo habilitar si la sincronización está activa
        if (config('database.sync_enabled', true)) {
            Cliente::observe(DatabaseSyncObserver::class);
            Prestamo::observe(DatabaseSyncObserver::class);
            Cuota::observe(DatabaseSyncObserver::class);
            Operacion::observe(DatabaseSyncObserver::class);
            Gestion::observe(DatabaseSyncObserver::class);
            Mora::observe(DatabaseSyncObserver::class);
            Comprobante::observe(DatabaseSyncObserver::class);
            Gasto::observe(DatabaseSyncObserver::class);
        }
    }
}
