<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ApiAuthController extends Controller
{
        public function login(Request $request) 
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        if (!$user->hasAnyRole(['Administrador', 'Empleado'])) {
            throw ValidationException::withMessages([
                'email' => ['No tienes permisos para acceder a la API administrativa.'],
            ]);
        }

        // Autenticación dual: token para API y sesión para web
        $token = $user->createToken('admin-api-token')->plainTextToken;
        
        // También loguear en la sesión web para compatibilidad
        Auth::login($user);

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token,
            'role' => $user->roles->first()?->name,
            'message' => 'Acceso administrativo autorizado',
            'session_authenticated' => Auth::check()
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Sesión administrativa cerrada correctamente']);
    }
}