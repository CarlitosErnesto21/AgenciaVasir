<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Mail\GoodbyeUserMail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load('roles', 'cliente', 'empleado');
        Log::debug('Perfil usuario', [
            'user_id' => $user->id,
            'roles' => $user->roles->pluck('name'),
            'empleado' => $user->empleado,
        ]);
            // Debug directo en la respuesta
            $debug = [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name'),
                'empleado_from_user' => $user->empleado,
            ];

            // Buscar manualmente el empleado por user_id
            $empleadoManual = \App\Models\Empleado::where('user_id', $user->id)->first();
            $debug['empleado_manual'] = $empleadoManual;

            return Inertia::render('Profile/Edit', [
                'mustVerifyEmail' => $user instanceof MustVerifyEmail,
                'status' => session('status'),
                'user' => $user,
                'cliente' => $user->cliente,
                'empleado' => $user->empleado,
                'debugEmpleado' => $debug,
            ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();


        // Cliente: actualizar o crear
        if ($user->hasRole('Cliente')) {
            if ($user->cliente) {
                $user->cliente->update([
                    'numero_identificacion' => $request->input('numero_identificacion'),
                    'fecha_nacimiento'      => $request->input('fecha_nacimiento'),
                    'genero'                => $request->input('genero'),
                    'tipo_documento'        => $request->input('tipo_documento'),
                    'direccion'             => $request->input('direccion'),
                    'telefono'              => $request->input('telefono'),
                ]);
            } else {
                $user->cliente()->create([
                    'numero_identificacion' => $request->input('numero_identificacion'),
                    'fecha_nacimiento'      => $request->input('fecha_nacimiento'),
                    'genero'                => $request->input('genero'),
                    'tipo_documento'        => $request->input('tipo_documento'),
                    'direccion'             => $request->input('direccion'),
                    'telefono'              => $request->input('telefono'),
                ]);
            }
        }

        // Empleado: actualizar o crear
        if ($user->hasRole('Empleado') || $user->hasRole('Administrador')) {
            if ($user->empleado) {
                $user->empleado->update([
                    'cargo'    => $request->input('cargo'),
                    'telefono' => $request->input('telefono'),
                ]);
            } else {
                $user->empleado()->create([
                    'cargo'    => $request->input('cargo'),
                    'telefono' => $request->input('telefono'),
                ]);
            }
        }

        return Redirect::route('profile.edit')->with('status', 'Información actualizada correctamente');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Enviar email de despedida antes de eliminar la cuenta
        try {
            Mail::to($user->email)->send(new GoodbyeUserMail($user));
        } catch (\Exception $e) {
            Log::error('Error enviando email de despedida: ' . $e->getMessage());
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
