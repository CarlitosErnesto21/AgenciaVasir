<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Rutas de debug para diagnosticar problemas de autenticación
Route::prefix('debug')->group(function () {

    // Ruta para verificar el estado de la sesión y autenticación
    Route::get('/auth-status', function (Request $request) {
        return response()->json([
            'session_id' => session()->getId(),
            'csrf_token' => csrf_token(),
            'authenticated' => Auth::check(),
            'user_id' => Auth::id(),
            'user' => Auth::user(),
            'guards' => [
                'web' => Auth::guard('web')->check(),
                'sanctum' => Auth::guard('sanctum')->check(),
            ],
            'session_data' => session()->all(),
            'cookies' => $request->cookies->all(),
            'headers' => [
                'user-agent' => $request->header('user-agent'),
                'x-requested-with' => $request->header('x-requested-with'),
                'referer' => $request->header('referer'),
                'origin' => $request->header('origin'),
            ],
            'sanctum_stateful_domains' => config('sanctum.stateful'),
            'app_url' => config('app.url'),
        ]);
    });

    // Ruta para probar autenticación con Sanctum
    Route::middleware('auth:sanctum')->get('/sanctum-test', function (Request $request) {
        return response()->json([
            'message' => 'Sanctum authentication working!',
            'user' => $request->user(),
            'user_roles' => $request->user()->roles->pluck('name'),
        ]);
    });

    // Ruta para limpiar sesiones
    Route::post('/clear-session', function () {
        Session::flush();
        Auth::logout();
        return response()->json(['message' => 'Session cleared']);
    });
});
