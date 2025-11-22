<?php

namespace App\Http\Middleware;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // Compartir información de autenticación con todas las vistas de Inertia
            'auth' => [
                // 'user' será una función que retorna los datos del usuario autenticado
                'user' => fn () => (
                    $user = Auth::user() // Obtiene el usuario autenticado, si existe
                ) ? array_merge(
                    [
                        // Datos básicos del usuario
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    [
                        // Obtiene todos los roles del usuario como array de objetos
                        'roles' => $user->roles->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                            ];
                        })->toArray()
                    ]
                ) : null, // Si no hay usuario autenticado, retorna null
            ],

            // Compartir mensajes flash con todas las vistas de Inertia
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],

            // Compartir configuración del sistema
            'config' => [
                'admin_phone' => function () {
                    // Buscar ÚNICAMENTE usuario con rol Administrador que tenga empleado con teléfono
                    $adminUser = User::whereHas('roles', function ($query) {
                        $query->where('name', 'Administrador');
                    })->with('empleado')->first();

                    if ($adminUser && $adminUser->empleado && !empty($adminUser->empleado->telefono)) {
                        return $adminUser->empleado->telefono;
                    }

                    // Si no encuentra administrador con teléfono, retornar mensaje descriptivo
                    return 'Teléfono no disponible';
                },
                'admin_email' => function () {
                    // Buscar ÚNICAMENTE usuario con rol Administrador
                    $adminUser = User::whereHas('roles', function ($query) {
                        $query->where('name', 'Administrador');
                    })->first();

                    if ($adminUser && !empty($adminUser->email)) {
                        return $adminUser->email;
                    }

                    // Si no encuentra administrador con email, retornar mensaje descriptivo
                    return 'Email no disponible';
                },
            ],
        ]);
    }
}
