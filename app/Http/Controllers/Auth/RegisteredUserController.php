<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeUserMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email:rfc,dns|max:255|unique:users,email',
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
                'email.unique' => 'Este correo ya está registrado.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.regex' => 'La contraseña debe incluir al menos una letra mayúscula y un número.',
                'password.confirmed' => 'Las contraseñas no coinciden.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Enviar los mensajes personalizados al frontend
            throw $e;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        //Implementando lógica de admin automático
        $this->assignUserRole($user);
        event(new Registered($user));
        Auth::login($user);

        // Enviar email de bienvenida personalizado con verificación
        $this->sendWelcomeEmail($user);

        //Redireccionando según el rol asignado
        return $this->redirectBasedOnUserRole($user);
    }

    /**
    * Asignar rol al usuario recién registrado
    * Los administradores se crean SOLO por seeder o comando artisan
    */

    private function assignUserRole(User $user): void { $user->assignRole('Cliente'); }

    /**
    * Redirigir al usuario según su rol
    */
    private function redirectBasedOnUserRole(User $user): RedirectResponse
    {
        // Actualizar roles del usuario en la sesión
        $user->load('roles');

        if ($user->hasRole('Administrador')) {
            return redirect(route('dashboard')); // ← Dashboard admin
        } elseif ($user->hasRole('Empleado')) {
            return redirect(route('dashboard')); // ← Dashboard empleado
        } else {
            return redirect(route('inicio')); // ← Página cliente
        }
    }

    /**
     * Endpoint para validar si el nombre de usuario ya existe
     */
    public function checkName(Request $request)
    {
        $name = $request->input('name');
        $exists = User::where('name', $name)->first() !== null;
        return response()->json(['exists' => $exists]);
    }

    /**
     * Endpoint para validar si el correo ya existe
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email:rfc,dns'
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.'
        ]);

        $exists = User::where('email', $request->email)->first() !== null;
        $response = ['exists' => $exists];
        if ($exists) {
            $response['message'] = 'Este correo ya está registrado.';
        }
        return response()->json($response);
    }

    /**
     * Enviar email de bienvenida personalizado con verificación
     */
    private function sendWelcomeEmail(User $user): void
    {
        // Generar URL de verificación
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        // Enviar email personalizado
        Mail::to($user->email)->send(new WelcomeUserMail($user, $verificationUrl));
    }
}
