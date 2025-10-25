<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Mail\StaffLoginNotificationMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            // Solo permitir "remember me" para clientes, no para staff por seguridad
            $rememberMe = $this->shouldRememberUser($request);
            
            // Sobrescribir el remember en la request para control específico
            $request->merge(['remember' => $rememberMe]);
            
            $request->authenticate();
            $request->session()->regenerate();

            $user = Auth::user();

            // Verificar si el email está verificado
            if ($user && !$user->hasVerifiedEmail()) {
                // Si es staff (creado por admin), marcar como verificado automáticamente
                if ($user->hasRole('Administrador') || $user->hasRole('Empleado')) {
                    $user->markEmailAsVerified();
                } else {
                    // Cliente sin verificar - redirigir a verificación
                    return redirect()->route('verification.notice')
                        ->with('status', 'Debes verificar tu email antes de continuar.');
                }
            }

            if ($user && ($user->hasRole('Administrador') || $user->hasRole('Empleado'))) {
                // Enviar email de notificación para staff
                $this->sendStaffLoginNotification($user, $request);

                //Crear token
                $token = $user->createToken('web-admin-token')->plainTextToken;

                $cookie = cookie('api_token', $token, 60 * 24 * 7, null, null, true, true);
                return redirect()->route('dashboard')->withCookie($cookie);
            } else {
                return redirect()->route('inicio');
            }
        } catch (ValidationException $e) {
            return back()->withErrors([
                'email' => 'Estas credenciales no coinciden con nuestros registros.',
            ])->withInput($request->only('email'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $cookie = cookie()->forget('api_token');
        return redirect('/')->withCookie($cookie);
    }

    /**
     * Determinar si debemos recordar al usuario
     */
    private function shouldRememberUser(Request $request): bool
    {
        // Solo permitir "remember me" si:
        // 1. El usuario marcó la casilla
        // 2. Y el usuario que intenta loguear es un Cliente (no staff)
        if (!$request->boolean('remember')) {
            return false;
        }

        // Verificar temporalmente las credenciales para obtener el usuario
        $credentials = $request->only('email', 'password');
        
        // Intentar encontrar el usuario por email para verificar su rol
        $user = \App\Models\User::where('email', $request->email)->first();
        
        if (!$user) {
            return false;
        }

        // Solo permitir remember me para Clientes, no para staff
        return $user->hasRole('Cliente');
    }

    /**
     * Enviar notificación de login para staff (Empleados y Administradores)
     */
    private function sendStaffLoginNotification($user, Request $request): void
    {
        try {
            // Recopilar información del login
            $loginDetails = [
                'timestamp' => now()->format('d/m/Y H:i:s'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
            ];

            // Enviar email de notificación
            Mail::to($user->email)->send(new StaffLoginNotificationMail($user, $loginDetails));

        } catch (\Exception $e) {
            // Log del error pero no interrumpir el login
            Log::error('Error enviando notificación de login para staff: ' . $e->getMessage());
        }
    }
}
