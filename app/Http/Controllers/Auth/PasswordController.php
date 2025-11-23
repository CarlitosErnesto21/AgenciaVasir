<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedConfirmationMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Enviar email de confirmación de cambio de contraseña
        $changeDetails = [
            'timestamp' => now()->format('d/m/Y H:i:s'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        try {
            Mail::to($request->user()->email)->send(new PasswordChangedConfirmationMail($request->user(), $changeDetails));
        } catch (\Exception $e) {
            // Log del error pero no interrumpir el proceso
            Log::error('Error enviando email de confirmación de cambio de contraseña: ' . $e->getMessage());
        }

        return back();
    }
}
