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
            $request->authenticate();
            $request->session()->regenerate();

            $user = Auth::user();

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
