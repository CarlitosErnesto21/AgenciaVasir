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
        $email = $request->route('email');
        $hash = $request->route('hash');
        
        // Intentar validación de firma estándar
        $hasValidSignature = $request->hasValidSignature();

        if (!$hasValidSignature) {
            // Log solo cuando hay problema
            Log::info('Email verification: Using alternative validation', [
                'email' => $email,
                'reason' => 'signature_mismatch'
            ]);

            // Validación alternativa con hash
            $expectedHash = sha1($email);

            if ($hash !== $expectedHash) {
                Log::warning('Email verification failed: Invalid hash', [
                    'email' => $email
                ]);
                abort(403, 'El enlace de verificación no es válido o ha expirado.');
            }

            // Verificar expiración básica
            $expires = $request->query('expires');
            if ($expires && now()->timestamp > $expires) {
                Log::warning('Email verification failed: Link expired', [
                    'email' => $email,
                    'expired_since' => now()->timestamp - $expires . ' seconds'
                ]);
                abort(403, 'El enlace de verificación ha expirado.');
            }

            // Solo log exitoso si usamos validación alternativa
            Log::info('Email verification successful (alternative method)', [
                'email' => $email
            ]);
        }
        // Si la firma es válida, no necesitamos log (funciona normal)

        return $next($request);
    }
}
