<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Forzar HTTPS en producción para evitar Mixed Content
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Configuración para PlanetScale
        if (config('database.default') === 'mysql') {
            // Deshabilitar foreign key constraints para PlanetScale
            \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        }
    }
}
