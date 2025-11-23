<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedNotificationMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/', // al menos una mayúscula
                'regex:/[0-9]/', // al menos un número
                'regex:/^[^\s.]*$/', // no espacios ni puntos
            ],
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe incluir al menos una letra mayúscula y un número, y no puede contener espacios ni puntos.',
            'password.confirmed' => 'Las contraseñas no coinciden.'
        ]);

        $user = $request->user();

        // Actualizar la contraseña
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Enviar email de notificación de cambio de contraseña (directo, sin cola)
        try {
            Mail::to($user->email)->send(new PasswordChangedNotificationMail($user));
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el proceso
            Log::error('Error enviando email de notificación de cambio de contraseña: ' . $e->getMessage());
        }

        // Cerrar sesión automáticamente para mayor seguridad
        Auth::logout();

        // Invalidar la sesión actual
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirigir al login con mensaje de éxito
        return redirect()->route('login')->with('status', 'Contraseña actualizada exitosamente. Por seguridad, debes iniciar sesión nuevamente.');
    }
}
