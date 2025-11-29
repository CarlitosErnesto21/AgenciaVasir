<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Mostrar la vista de verificación de email
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        // Caso 1: Usuario autenticado (empleados/admin que necesitan verificar)
        if ($request->user()) {
            // Si ya está verificado, redirigir según su rol
            if ($request->user()->hasVerifiedEmail()) {
                if ($request->user()->hasRole('Administrador') || $request->user()->hasRole('Empleado')) {
                    return redirect()->intended(route('dashboard', absolute: false));
                } else {
                    return redirect()->intended(route('inicio', absolute: false));
                }
            }

            // Usuario autenticado pero no verificado - mostrar vista de verificación
            return Inertia::render('Auth/VerifyEmail', [
                'status' => session('status'),
                'email' => $request->user()->email,
                'resendCount' => session('resend_count', 0),
                'isAuthenticated' => true,
            ]);
        }

        // Caso 2: Usuario NO autenticado (cliente en proceso de registro)
        // Obtener email de la query string o sesión
        $email = $request->query('email') ?: session('pending_registration.email');

        if (!$email) {
            return redirect()->route('register')
                ->withErrors(['email' => 'No se encontraron datos de registro pendientes.']);
        }

        // Mostrar vista de verificación para cliente no autenticado
        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
            'email' => $email,
            'resendCount' => session('resend_count', 0),
            'isAuthenticated' => false,
        ]);
    }

    /**
     * Reenviar email de verificación
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Si es un usuario autenticado que necesita verificar su email
        if ($user && !$user->hasVerifiedEmail()) {
            // Usar el mismo sistema que EmailVerificationNotificationController
            if ($user->hasRole('Cliente')) {
                // Para clientes, usar el sistema personalizado
                $verificationUrl = URL::temporarySignedRoute(
                    'custom.verification.verify',
                    now()->addMinutes(15),
                    [
                        'email' => $user->email,
                        'hash' => sha1($user->email),
                    ]
                );

                $userData = [
                    'name' => $user->name,
                    'email' => $user->email,
                ];

                Mail::to($user->email)->send(new WelcomeUserMail($userData, $verificationUrl));
            } else {
                // Para empleados/admin, usar sistema por defecto
                $user->sendEmailVerificationNotification();
            }

            return back()->with('status', 'verification-link-sent');
        }

        // Proceso para usuarios no autenticados (nuevos registros)
        $request->validate([
            'email' => 'required|email',
        ]);

        // Verificar si hay datos pendientes en sesión
        $pendingData = session('pending_registration');

        if (!$pendingData || $pendingData['email'] !== $request->email) {
            return back()->withErrors([
                'email' => 'No se encontraron datos de registro para este email.'
            ]);
        }

        // Verificar límite de reenvíos (máximo 3)
        $resendCount = session('resend_count', 0);
        if ($resendCount >= 3) {
            return back()->withErrors([
                'limit' => 'Has alcanzado el límite máximo de 3 envíos de correo. Por favor, vuelve a intentar el registro o contacta a soporte si necesitas ayuda.'
            ]);
        }

        // Generar nueva URL de verificación
        $verificationUrl = URL::temporarySignedRoute(
            'custom.verification.verify',
            now()->addMinutes(15),
            [
                'email' => $request->email,
                'hash' => sha1($request->email),
            ]
        );

        // Crear datos de usuario para el email
        $userData = [
            'name' => $pendingData['name'],
            'email' => $pendingData['email'],
        ];

        // Reenviar email
        Mail::to($request->email)->send(new WelcomeUserMail($userData, $verificationUrl));

        // Incrementar contador de reenvíos
        session(['resend_count' => $resendCount + 1]);

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Reenviar email de verificación (público - sin autenticación)
     */
    public function resendPublic(Request $request): RedirectResponse
    {
        // Este método es idéntico al resend(), pero está disponible públicamente
        return $this->resend($request);
    }
}
