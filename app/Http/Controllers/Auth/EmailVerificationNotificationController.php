<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Verificar si el usuario es un cliente o empleado/admin
        if ($user->hasRole('Cliente')) {
            // Para clientes, usar el sistema personalizado de verificación
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
            // Para empleados/admin, usar el sistema por defecto de Laravel
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'verification-link-sent');
    }
}
