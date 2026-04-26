<?php

namespace App\Providers;

use App\Helpers\BrandHelper;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class BrandServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', function ($view) {
            $view->with('brandConfig', BrandHelper::getBrandConfig());
        });
    }

    public function register()
    {
        //
    }
}
