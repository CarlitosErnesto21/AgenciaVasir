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
        ]);
    }

    /**
     * Reenviar email de verificación
     */
    public function resend(Request $request): RedirectResponse
    {
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

        return back()->with('status', 'verification-link-sent');
    }
}
