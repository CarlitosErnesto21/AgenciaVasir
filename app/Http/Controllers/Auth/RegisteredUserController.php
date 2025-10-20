<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Validar formato básico
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email:rfc,dns|max:255',
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/', // al menos una mayúscula
                'regex:/[0-9]/', // al menos un número
            ],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe incluir al menos una letra mayúscula y un número.',
            'password.confirmed' => 'Las contraseñas no coinciden.'
        ]);

        // Verificar si las credenciales ya existen
        if (User::where('name', $request->name)->exists() || User::where('email', $request->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Estas credenciales ya están en uso. Por favor, crea una cuenta nueva o inicia sesión.'
            ]);
        }

        // GUARDAR DATOS TEMPORALMENTE EN SESIÓN (no crear usuario aún)
        session([
            'pending_registration' => [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]
        ]);

        // Generar URL de verificación personalizada con datos en sesión
        $verificationUrl = URL::temporarySignedRoute(
            'custom.verification.verify',
            now()->addMinutes(15),
            [
                'email' => $request->email,
                'hash' => sha1($request->email)
            ]
        );

        // Enviar email de verificación con datos del usuario
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        Mail::to($request->email)->send(new WelcomeUserMail($userData, $verificationUrl));

        // Redirigir a la vista de verificación con el email
        return redirect()->route('verification.notice', ['email' => $request->email]);
    }
}
