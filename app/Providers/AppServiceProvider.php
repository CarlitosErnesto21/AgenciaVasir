<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // Configurar longitud por defecto de las cadenas para compatibilidad con MySQL
        Schema::defaultStringLength(191);

        Vite::prefetch(concurrency: 3);

        // Forzar HTTPS en producción para evitar Mixed Content
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));

            // Configurar proxies confiables para detectar HTTPS correctamente
            $this->app['request']->server->set('HTTPS', 'on');
            $this->app['request']->server->set('SERVER_PORT', 443);
        }

        // Configuración para PlanetScale
        if (config('database.default') === 'mysql') {
            // Deshabilitar foreign key constraints para PlanetScale
            Schema::disableForeignKeyConstraints();
        }
    }
}
