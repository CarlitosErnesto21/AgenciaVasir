<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ExtendSessionForLongProcesses
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Rutas que pueden tomar mucho tiempo y necesitan sesiones extendidas
        $longProcessRoutes = [
            'reservas.store',
            'reservas.update',
            'reservas.confirmar',
            'reservas.rechazar',
            'reservas.reprogramar',
            'reservas.completar',
            'emails.send',
            'upload.images',
            'bulk.operations'
        ];

        $currentRoute = $request->route() ? $request->route()->getName() : null;
        
        // Si la ruta actual está en la lista de procesos largos
        if ($currentRoute && in_array($currentRoute, $longProcessRoutes)) {
            // Extender la sesión por 4 horas para este request
            Session::put('_token', Session::token());
            config(['session.lifetime' => 240]); // 4 horas en minutos
            
            // También podemos regenerar el token para asegurar freshness
            if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
                Session::regenerateToken();
            }
        }
        
        // Para requests AJAX, agregar header con tiempo de expiración
        $response = $next($request);
        
        if ($request->ajax() || $request->wantsJson()) {
            $sessionLifetime = config('session.lifetime', 120);
            $expiresAt = now()->addMinutes($sessionLifetime)->timestamp;
            $response->headers->set('X-Session-Expires-At', $expiresAt);
            $response->headers->set('X-CSRF-Token', Session::token());
        }
        
        return $response;
    }
}