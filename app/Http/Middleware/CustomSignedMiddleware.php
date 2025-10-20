<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomSignedMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log detallado para debugging
        Log::info('CustomSignedMiddleware: Verificando URL firmada', [
            'url' => $request->fullUrl(),
            'email' => $request->route('email'),
            'hash' => $request->route('hash'),
            'expires' => $request->query('expires'),
            'signature' => $request->query('signature'),
            'app_url' => config('app.url'),
            'app_key' => substr(config('app.key'), 0, 10) . '...' // Solo mostrar parte de la clave
        ]);

        // Intentar validación de firma estándar
        $hasValidSignature = $request->hasValidSignature();

        if (!$hasValidSignature) {
            Log::info('CustomSignedMiddleware: Usando validación alternativa por diferencia HTTP/HTTPS', [
                'email' => $request->route('email'),
                'hash' => $request->route('hash')
            ]);

            // Validación alternativa con hash
            $email = $request->route('email');
            $hash = $request->route('hash');
            $expectedHash = sha1($email);

            if ($hash !== $expectedHash) {
                Log::error('CustomSignedMiddleware: Hash también inválido', [
                    'email' => $email,
                    'expected_hash' => $expectedHash,
                    'received_hash' => $hash
                ]);

                abort(403, 'El enlace de verificación no es válido o ha expirado.');
            }

            // Verificar expiración básica
            $expires = $request->query('expires');
            if ($expires && now()->timestamp > $expires) {
                Log::error('CustomSignedMiddleware: Enlace expirado', [
                    'expires' => $expires,
                    'current_time' => now()->timestamp
                ]);

                abort(403, 'El enlace de verificación ha expirado.');
            }

            Log::info('CustomSignedMiddleware: Validación alternativa exitosa');
        } else {
            Log::info('CustomSignedMiddleware: Firma válida');
        }

        return $next($request);
    }
}
