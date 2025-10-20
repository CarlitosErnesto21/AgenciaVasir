<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class DebugAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Solo logear rutas API que empiecen con /api/
        if (!$request->is('api/*')) {
            return $next($request);
        }

        try {
            Log::info('🔐 API Request Debug', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                'ip' => $request->ip(),
            ]);

            // Verificar Authorization header
            $authHeader = $request->header('Authorization');
            Log::info('📋 Headers Debug', [
                'authorization' => $authHeader ? 'Bearer ' . substr(str_replace('Bearer ', '', $authHeader), 0, 12) . '...' : 'MISSING',
                'x_requested_with' => $request->header('X-Requested-With'),
                'content_type' => $request->header('Content-Type'),
                'cookies_present' => !empty($request->cookies->all()),
            ]);

            // Si hay token Bearer, verificarlo
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $token = substr($authHeader, 7);
                $accessToken = PersonalAccessToken::findToken($token);

                Log::info('🎫 Token Validation', [
                    'token_prefix' => substr($token, 0, 12) . '...',
                    'token_exists_in_db' => $accessToken ? 'YES' : 'NO',
                    'token_user_id' => $accessToken?->tokenable_id,
                    'token_name' => $accessToken?->name,
                    'token_abilities' => $accessToken?->abilities,
                    'token_created' => $accessToken?->created_at?->diffForHumans(),
                    'token_last_used' => $accessToken?->last_used_at?->diffForHumans(),
                ]);
            }

            // Estado de autenticación después del middleware Sanctum
            $response = $next($request);

            Log::info('👤 Auth State After Sanctum', [
                'is_authenticated' => Auth::check(),
                'user_id' => Auth::id(),
                'user_email' => Auth::user()?->email,
                'user_roles' => Auth::user()?->roles?->pluck('name')->toArray(),
                'response_status' => $response->getStatusCode(),
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('🚨 Debug Auth Middleware Error: ' . $e->getMessage());
            return $next($request);
        }
    }
}
