<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RutasAdmin;
use App\Http\Middleware\CustomSignedMiddleware;
use App\Http\Middleware\TrustedProxies;
use App\Http\Middleware\ValidateVentaPagoIntegrity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (config('app.debug')) {
                Route::prefix('api')->group(base_path('routes/debug.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Middleware global para proxies confiables
        $middleware->web(prepend: [
            TrustedProxies::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class
        ]);

        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        // Mueve tu alias aquí:
        $middleware->alias([
            'rutas.admin' => RutasAdmin::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'custom.signed' => CustomSignedMiddleware::class,
            'venta.integrity' => ValidateVentaPagoIntegrity::class
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })
    ->create();
