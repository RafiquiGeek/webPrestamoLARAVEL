<?php

namespace App\Helpers;

class BrandHelper
{
    public static function getBrandConfig()
    {
        $configPath = storage_path('app/brand_config.json');

        if (file_exists($configPath)) {
            return json_decode(file_get_contents($configPath), true);
        }

        return [
            'site_name' => config('adminlte.title', 'Banking'),
            'logo_path' => null,
        ];
    }
}
