<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MenuPermissionService
{
    public function getFilteredMenuItems()
    {
        $userId = Auth::id();
        $cacheKey = "menu_items_user_{$userId}";

        // Cache por 30 minutos
        return Cache::remember($cacheKey, 30 * 60, function () {
            $menuItems = config('adminlte.menu', []);
            $user = Auth::user();

            // Pre-cargar TODOS los permisos del usuario de una vez
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

            return $this->filterMenuItems($menuItems, $userPermissions);
        });
    }

    private function filterMenuItems($items, $userPermissions)
    {
        $filtered = [];

        foreach ($items as $item) {
            // Verificar permisos sin consulta adicional
            if (isset($item['can']) && ! in_array($item['can'], $userPermissions)) {
                continue;
            }

            // Saltar items del navbar
            if (isset($item['topnav_right']) && $item['topnav_right']) {
                continue;
            }

            // Filtrar submenu si existe
            if (isset($item['submenu'])) {
                $item['submenu'] = $this->filterMenuItems($item['submenu'], $userPermissions);
            }

            $filtered[] = $item;
        }

        return $filtered;
    }

    public function clearUserMenuCache($userId = null)
    {
        $userId = $userId ?? Auth::id();
        Cache::forget("menu_items_user_{$userId}");
    }
}
