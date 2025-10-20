<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomVerifyEmailController extends Controller
{
    /**
     * Verificar email y crear usuario
     */
    public function verify(Request $request): RedirectResponse
    {
        // El middleware CustomSignedMiddleware ya validó la URL
        
        // Obtener datos de registro pendientes de la sesión
        $pendingData = session('pending_registration');

        if (!$pendingData) {
            return redirect()->route('register')
                ->withErrors(['email' => 'No se encontraron datos de registro pendientes.']);
        }

        // Verificar que el email coincide
        if ($pendingData['email'] !== $request->email) {
            return redirect()->route('register')
                ->withErrors(['email' => 'El enlace no coincide con los datos de registro.']);
        }

        // Verificar si ya existe un usuario con este email
        if (User::where('email', $request->email)->exists()) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Esta cuenta ya existe. Por favor, inicia sesión.']);
        }

        try {
            // AHORA SÍ CREAR EL USUARIO
            $user = User::create([
                'name' => $pendingData['name'],
                'email' => $pendingData['email'],
                'password' => $pendingData['password'],
                'email_verified_at' => now(), // Marcar como verificado
            ]);

            // Asignar rol de cliente
            $user->assignRole('Cliente');

            // Disparar solo evento de verificación (ya está verificado)
            event(new Verified($user));

            // Hacer login automático
            Auth::login($user);

            // Limpiar datos temporales
            session()->forget('pending_registration');

            // Verificar si hay reserva o compra pendiente
            $reservaPendiente = session('tour_reserva_pendiente');
            $sessionActiva = session('reserva_session_activa');
            $productoPendiente = session('producto_compra_pendiente');
            $sessionActivaProducto = session('compra_session_activa');

            // Redirigir según el contexto
            if ($reservaPendiente && $sessionActiva === 'true') {
                $tourInfo = json_decode($reservaPendiente, true);
                session()->forget(['tour_reserva_pendiente', 'reserva_session_activa']);
                return redirect($tourInfo['returnUrl'] ?? route('inicio'))
                    ->with('success', '¡Cuenta creada y verificada! Ahora puedes completar tu reserva.');
            }

            if ($productoPendiente && $sessionActivaProducto === 'true') {
                $productoInfo = json_decode($productoPendiente, true);
                session()->forget(['producto_compra_pendiente', 'compra_session_activa']);
                return redirect($productoInfo['returnUrl'] ?? route('inicio'))
                    ->with('success', '¡Cuenta creada y verificada! Ahora puedes completar tu compra.');
            }

            // Redirigir al inicio para clientes normales
            return redirect()->route('inicio')
                ->with('success', '¡Bienvenido! Tu cuenta ha sido creada y verificada exitosamente.');

        } catch (\Exception $e) {
            return redirect()->route('register')
                ->withErrors(['email' => 'Error al crear la cuenta. Por favor, inténtalo de nuevo.']);
        }
    }
}
