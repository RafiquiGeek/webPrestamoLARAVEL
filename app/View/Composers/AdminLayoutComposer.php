<?php

namespace App\View\Composers;

use App\Services\MenuPermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminLayoutComposer
{
    protected $menuService;

    public function __construct(MenuPermissionService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function compose(View $view)
    {
        // Solo ejecutar si hay usuario autenticado
        if (! Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $cacheKey = "admin_layout_data_{$userId}";

        // Cache por 1 hora
        $layoutData = Cache::remember($cacheKey, 60 * 60, function () {
            return [
                'user' => Auth::user()->only(['id', 'name', 'email']),
                'menuItems' => $this->menuService->getFilteredMenuItems(),
                'brandConfig' => $this->getBrandConfig(),
                'permissions' => Auth::user()->getAllPermissions()->pluck('name')->toArray(),
            ];
        });

        $view->with($layoutData);
    }

    private function getBrandConfig()
    {
        // Cache de configuración de marca por 24 horas
        return Cache::remember('brand_config', 24 * 60 * 60, function () {
            // Aquí cargarías la configuración de la marca desde la base de datos
            return [
                'logo_path' => null, // Por defecto
                'company_name' => config('app.name', 'Admin'),
            ];
        });
    }

    public static function clearCache($userId = null)
    {
        if ($userId) {
            Cache::forget("admin_layout_data_{$userId}");
        } else {
            // Limpiar todos los caches relacionados
            Cache::flush(); // Cuidado con esto en producción
        }
    }
}
