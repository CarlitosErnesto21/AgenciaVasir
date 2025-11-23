<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeUserMail;
use App\Mail\PasswordChangedConfirmationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $empleados = Empleado::with(['user.roles'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($empleado) {
                    return [
                        'id' => $empleado->id,
                        'cargo' => $empleado->cargo,
                        'telefono' => $empleado->telefono,
                        'nombre' => $empleado->user->name,
                        'email' => $empleado->user->email,
                        'user_id' => $empleado->user_id,
                        'email_verified_at' => $empleado->user->email_verified_at,
                        'created_at' => $empleado->created_at,
                        'updated_at' => $empleado->updated_at,
                        'roles' => $empleado->user->roles->pluck('name')
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $empleados
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de empleados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar formato básico
        $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]+$/', // Solo letras con tildes, espacios y guiones
            ],
            'email' => 'required|string|lowercase|email:rfc,dns|max:255',
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[A-Z]/', // al menos una mayúscula
                'regex:/[0-9]/', // al menos un número
                'regex:/^[^\s.]*$/', // no espacios ni puntos
            ],
            'cargo' => [
                'required',
                'string',
                'min:2',
                'max:25',
                'regex:/^[A-Z\s]+$/', // Solo letras mayúsculas y espacios
            ],
            'telefono' => 'required|string|min:8|max:20',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras, espacios y guiones.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe incluir al menos una letra mayúscula y un número, y no puede contener espacios ni puntos.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'cargo.required' => 'El cargo es obligatorio.',
            'cargo.min' => 'El cargo debe tener al menos 2 caracteres.',
            'cargo.max' => 'El cargo no puede exceder 25 caracteres.',
            'cargo.regex' => 'El cargo solo puede contener letras y espacios (sin tildes ni números).',
            'telefono.required' => 'El teléfono es obligatorio.',
            'telefono.min' => 'El teléfono debe tener al menos 8 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
        ]);

        // Normalizar cargo y nombre
        $cargo = $this->normalizeCargo($request->cargo);
        $normalizedName = $this->normalizeNameForComparison($request->name);

        // Verificar si el nombre ya existe (case-insensitive)
        if (User::whereRaw('LOWER(TRIM(REGEXP_REPLACE(name, "[[:space:]]+", " "))) = ?', [$normalizedName])->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un empleado con este nombre.'
            ]);
        }

        // Verificar si el email ya existe
        if (User::where('email', $request->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Este correo electrónico ya está en uso.'
            ]);
        }

        // Verificar si el teléfono ya existe en empleados
        if (Empleado::where('telefono', $request->telefono)->exists()) {
            throw ValidationException::withMessages([
                'telefono' => 'Este número de teléfono ya está en uso por otro empleado.'
            ]);
        }

        // Verificar si el teléfono ya existe en clientes
        if (Cliente::where('telefono', $request->telefono)->exists()) {
            throw ValidationException::withMessages([
                'telefono' => 'Este número de teléfono ya está en uso por un cliente.'
            ]);
        }

        try {
            DB::beginTransaction();

            // Crear el usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(), // Los empleados se consideran verificados automáticamente
            ]);

            // Asignar rol de Empleado
            $employeeRole = Role::where('name', 'Empleado')->first();
            if ($employeeRole) {
                $user->assignRole($employeeRole);
            }

            // Crear el empleado
            $empleado = Empleado::create([
                'cargo' => $cargo, // Usar el cargo convertido a mayúsculas
                'telefono' => $request->telefono,
                'user_id' => $user->id,
            ]);

            // Enviar email de bienvenida
            try {
                $userData = [
                    'name' => $user->name,
                    'email' => $user->email,
                ];

                // Para empleados, no necesitamos URL de verificación ya que están verificados automáticamente
                Mail::to($user->email)->send(new WelcomeUserMail($userData));
            } catch (\Exception $e) {
                // Log del error pero no interrumpir el proceso de creación
                Log::error('Error enviando email de bienvenida a empleado: ' . $e->getMessage());
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Empleado creado exitosamente',
                'data' => [
                    'id' => $empleado->id,
                    'cargo' => $empleado->cargo,
                    'telefono' => $empleado->telefono,
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'user_id' => $user->id,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $empleado->created_at,
                    'updated_at' => $empleado->updated_at,
                    'roles' => ['Empleado']
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el empleado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $empleado = Empleado::with(['user.roles'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $empleado->id,
                    'cargo' => $empleado->cargo,
                    'telefono' => $empleado->telefono,
                    'nombre' => $empleado->user->name,
                    'email' => $empleado->user->email,
                    'user_id' => $empleado->user_id,
                    'email_verified_at' => $empleado->user->email_verified_at,
                    'created_at' => $empleado->created_at,
                    'updated_at' => $empleado->updated_at,
                    'roles' => $empleado->user->roles->pluck('name')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $empleado = Empleado::with('user')->findOrFail($id);

            // Validar los datos
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'min:3',
                    'max:255',
                    'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\-]+$/', // Solo letras con tildes, espacios y guiones
                ],
                'email' => 'required|string|lowercase|email:rfc,dns|max:255',
                'cargo' => [
                    'required',
                    'string',
                    'min:2',
                    'max:25',
                    'regex:/^[A-Z\s]+$/', // Solo letras mayúsculas y espacios
                ],
                'telefono' => 'required|string|min:8|max:20',
            ], [
                'name.required' => 'El nombre es obligatorio.',
                'name.min' => 'El nombre debe tener al menos 3 caracteres.',
                'name.max' => 'El nombre no puede exceder 255 caracteres.',
                'name.regex' => 'El nombre solo puede contener letras, espacios y guiones.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'El formato del correo electrónico no es válido.',
                'email.max' => 'El correo electrónico no puede exceder 255 caracteres.',
                'cargo.required' => 'El cargo es obligatorio.',
                'cargo.min' => 'El cargo debe tener al menos 2 caracteres.',
                'cargo.max' => 'El cargo no puede exceder 25 caracteres.',
                'cargo.regex' => 'El cargo solo puede contener letras y espacios (sin tildes ni números).',
                'telefono.required' => 'El teléfono es obligatorio.',
                'telefono.min' => 'El teléfono debe tener al menos 8 caracteres.',
                'telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
            ]);

            // Normalizar cargo y nombre
            $cargo = $this->normalizeCargo($request->cargo);
            $normalizedName = $this->normalizeNameForComparison($request->name);

            // Verificar si el email ya existe en otro usuario
            if (User::where('email', $request->email)->where('id', '!=', $empleado->user_id)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Este correo electrónico ya está en uso por otro usuario.'
                ]);
            }

            // Verificar si el nombre ya existe en otro usuario (case-insensitive)
            if (User::whereRaw('LOWER(TRIM(REGEXP_REPLACE(name, "[[:space:]]+", " "))) = ? AND id != ?', [$normalizedName, $empleado->user_id])->exists()) {
                throw ValidationException::withMessages([
                    'name' => 'Ya existe un empleado con este nombre.'
                ]);
            }

            // Verificar si el teléfono ya existe en otros empleados
            if (Empleado::where('telefono', $request->telefono)->where('id', '!=', $empleado->id)->exists()) {
                throw ValidationException::withMessages([
                    'telefono' => 'Este número de teléfono ya está en uso por otro empleado.'
                ]);
            }

            // Verificar si el teléfono ya existe en clientes
            if (Cliente::where('telefono', $request->telefono)->exists()) {
                throw ValidationException::withMessages([
                    'telefono' => 'Este número de teléfono ya está en uso por un cliente.'
                ]);
            }

            DB::beginTransaction();

            // Actualizar usuario
            $empleado->user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            // Actualizar empleado
            $empleado->update([
                'cargo' => $cargo, // Usar el cargo convertido a mayúsculas
                'telefono' => $request->telefono,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Empleado actualizado exitosamente',
                'data' => [
                    'id' => $empleado->id,
                    'cargo' => $empleado->cargo,
                    'telefono' => $empleado->telefono,
                    'nombre' => $empleado->user->name,
                    'email' => $empleado->user->email,
                    'user_id' => $empleado->user_id,
                    'email_verified_at' => $empleado->user->email_verified_at,
                    'created_at' => $empleado->created_at,
                    'updated_at' => $empleado->updated_at,
                    'roles' => $empleado->user->roles->pluck('name')
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el empleado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $empleado = Empleado::with(['user', 'reservas'])->findOrFail($id);

            Log::info("Intentando eliminar empleado ID: {$id}, Usuario ID: {$empleado->user_id}");

            // Verificar si el empleado tiene reservas asociadas
            if ($empleado->reservas()->count() > 0) {
                $details = [];

                if ($empleado->reservas()->count() > 0) {
                    $details[] = "Tiene {$empleado->reservas()->count()} reserva(s) asociada(s)";
                }

                Log::warning("No se puede eliminar empleado ID: {$id} - Tiene reservas asociadas");

                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el empleado',
                    'error' => 'El empleado está siendo utilizado en el sistema.',
                    'details' => $details
                ], 422);
            }

            DB::beginTransaction();

            // Eliminar empleado y usuario
            $user = $empleado->user;
            $userId = $user->id;
            $userName = $user->name;

            Log::info("Eliminando empleado ID: {$empleado->id}");
            $empleado->delete();

            Log::info("Eliminando usuario ID: {$userId}, Nombre: {$userName}");
            $user->delete();

            Log::info("Empleado y usuario eliminados exitosamente - Empleado ID: {$id}, Usuario ID: {$userId}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Empleado y usuario eliminados exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al eliminar empleado ID: {$id} - " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el empleado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar contraseña de un empleado
     */
    public function updatePassword(Request $request, string $id)
    {
        try {
            $empleado = Empleado::with('user')->findOrFail($id);

            $request->validate([
                'password' => [
                    'required',
                    'confirmed',
                    'min:8',
                    'regex:/[A-Z]/', // al menos una mayúscula
                    'regex:/[0-9]/', // al menos un número
                    'regex:/^[^\s.]*$/', // no espacios ni puntos
                ],
            ], [
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
                'password.regex' => 'La contraseña debe incluir al menos una letra mayúscula y un número, y no puede contener espacios ni puntos.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
            ]);

            $empleado->user->update([
                'password' => Hash::make($request->password),
            ]);

            // 🔐 Invalidar todas las sesiones del usuario por seguridad
            // Esto cierra automáticamente las sesiones activas del usuario
            DB::table('sessions')
                ->where('user_id', $empleado->user->id)
                ->delete();

            // Enviar email de confirmación de cambio de contraseña
            $changeDetails = [
                'timestamp' => now()->format('d/m/Y H:i:s'),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            try {
                Mail::to($empleado->user->email)->send(new PasswordChangedConfirmationMail($empleado->user, $changeDetails));
            } catch (\Exception $e) {
                // Log del error pero no interrumpir el proceso
                Log::error('Error enviando email de confirmación de cambio de contraseña (admin): ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente. Las sesiones del usuario han sido cerradas por seguridad.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la contraseña',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad de teléfono
     */
    public function checkTelefonoAvailability(Request $request)
    {
        $telefono = $request->get('telefono');
        $empleadoId = $request->get('empleado_id'); // Para edición

        if (!$telefono) {
            return response()->json([
                'available' => false,
                'message' => 'Teléfono requerido'
            ], 400);
        }

        // Verificar en empleados
        $empleadoExists = Empleado::where('telefono', $telefono)
            ->when($empleadoId, function ($query, $empleadoId) {
                return $query->where('id', '!=', $empleadoId);
            })
            ->exists();

        // Verificar en clientes
        $clienteExists = Cliente::where('telefono', $telefono)->exists();

        if ($empleadoExists) {
            return response()->json([
                'available' => false,
                'message' => 'Este número de teléfono ya está en uso por otro empleado.'
            ]);
        }

        if ($clienteExists) {
            return response()->json([
                'available' => false,
                'message' => 'Este número de teléfono ya está en uso por un cliente.'
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Teléfono disponible'
        ]);
    }

    /**
     * Normaliza el cargo eliminando caracteres no permitidos y convirtiendo a mayúsculas
     */
    private function normalizeCargo($cargo)
    {
        // Convertir a mayúsculas y eliminar caracteres no permitidos
        return strtoupper(preg_replace('/[^A-Za-z\s]/', '', trim($cargo)));
    }

    /**
     * Normaliza el nombre eliminando espacios extra y convirtiendo a minúsculas para comparación
     */
    private function normalizeNameForComparison($name)
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $name)));
    }

        /**
     * Guardar o actualizar datos de empleado desde el Dashboard.
     */
    public function completarDatos(Request $request)
    {
        $validated = $request->validate([
            'cargo' => 'required|string|max:255',
            'telefono' => 'required|string|max:30',
            'user_id' => 'required|exists:users,id',
        ]);

        $empleado = Empleado::firstOrNew(['user_id' => $validated['user_id']]);
        $empleado->cargo = $validated['cargo'];
        $empleado->telefono = $validated['telefono'];
        $empleado->save();

        return redirect()->route('dashboard')->with('status', 'Datos de empleado actualizados correctamente.');
    }
}
