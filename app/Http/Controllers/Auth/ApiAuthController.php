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

    /**
     * Validar si un nombre de usuario es único
     */
    public function validarNombre(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:255',
            'usuario_id' => 'nullable|integer|exists:users,id'
        ]);

        $nombre = trim($request->name);
        $usuarioId = $request->usuario_id;

        // Verificar si el nombre ya existe (excluyendo al usuario actual si se proporciona)
        $existeNombre = User::where('name', $nombre)
            ->when($usuarioId, function ($query, $usuarioId) {
                return $query->where('id', '!=', $usuarioId);
            })
            ->exists();

        if ($existeNombre) {
            return response()->json([
                'disponible' => false,
                'message' => 'Este nombre ya está registrado por otro usuario'
            ]);
        }

        return response()->json([
            'disponible' => true,
            'message' => 'Nombre disponible'
        ]);
    }
}