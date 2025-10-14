<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Empleado;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;

class RoleController extends Controller
{
    /**
     * Display the role management page
     */
    public function index()
    {
        // Solo traer usuarios que sean empleados o administradores (no clientes)
        $users = User::with('roles')
            ->whereHas('roles', function($query) {
                $query->whereIn('name', ['Administrador', 'Empleado']);
            })
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ];
            });

        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        });

        $permissions = Permission::all()->map(function ($permission) {
            return [
                'id' => $permission->id,
                'name' => $permission->name,
            ];
        });

        return Inertia::render('Configuracion/Roles', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update user roles
     */
    public function updateUserRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        try {
            // Sincronizar roles (esto elimina los roles anteriores y asigna los nuevos)
            $user->syncRoles($request->roles);

            // Actualizar el timestamp updated_at
            $user->touch();

            return response()->json([
                'success' => true,
                'message' => 'Roles actualizados correctamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign a single role to user
     */
    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        try {
            if (!$user->hasRole($request->role)) {
                $user->assignRole($request->role);
                // Actualizar el timestamp updated_at
                $user->touch();
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol asignado correctamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a role from user
     */
    public function removeRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        try {
            if ($user->hasRole($request->role)) {
                $user->removeRole($request->role);
                // Actualizar el timestamp updated_at
                $user->touch();
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado correctamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user permissions (only direct permissions, no role inheritance)
     */
    public function getUserPermissions(User $user)
    {
        try {
            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

            return response()->json([
                'success' => true,
                'permissions' => $directPermissions,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permission to user
     */
    public function assignPermission(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        try {
            // Get current direct permissions
            $currentPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

            // Add the new permission if not already present
            if (!in_array($request->permission, $currentPermissions)) {
                $currentPermissions[] = $request->permission;

                // Use syncPermissions to ensure consistency
                $user->syncPermissions($currentPermissions);

                // Actualizar el timestamp updated_at
                $user->touch();
            }

            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Permiso asignado correctamente',
                'permissions' => $directPermissions,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el permiso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove permission from user
     */
    public function removePermission(Request $request, User $user)
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        try {
            // Get current direct permissions
            $currentPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

            // Remove the permission if present
            $currentPermissions = array_filter($currentPermissions, function($permission) use ($request) {
                return $permission !== $request->permission;
            });

            // Use syncPermissions to ensure consistency
            $user->syncPermissions(array_values($currentPermissions));

            // Actualizar el timestamp updated_at
            $user->touch();

            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Permiso eliminado correctamente',
                'permissions' => $directPermissions,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el permiso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync all permissions for a user
     */
    public function syncPermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        try {
            // Sync all permissions at once
            $user->syncPermissions($request->permissions);

            // Update the timestamp
            $user->touch();

            $directPermissions = $user->getDirectPermissions()->pluck('name')->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Permisos sincronizados correctamente',
                'permissions' => $directPermissions,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new internal user (employee or admin)
     */
    public function createInternalUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:Administrador,Empleado',
            'cargo' => 'required|string|max:25',
            'telefono' => 'required|string|size:8',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name'
        ]);

        try {
            DB::beginTransaction();

            // Crear el usuario
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Asignar el rol
            $user->assignRole($request->role);

            // Asignar permisos si se proporcionaron
            if ($request->has('permissions') && is_array($request->permissions)) {
                // Usar syncPermissions para asignar todos los permisos de una vez
                $user->syncPermissions($request->permissions);
            }

            // Crear el registro en la tabla empleados
            $empleado = Empleado::create([
                'cargo' => $request->cargo,
                'telefono' => $request->telefono,
                'user_id' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario y empleado creados correctamente con ' . (count($request->permissions ?? []) . ' permisos asignados'),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'created_at' => $user->created_at->format('d/m/Y'),
                    'updated_at' => $user->updated_at->format('d/m/Y'),
                ],
                'Empleado' => [
                    'id' => $empleado->id,
                    'cargo' => $empleado->cargo,
                    'telefono' => $empleado->telefono,
                    'user_id' => $empleado->user_id,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el usuario y empleado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employee data for a user
     */
    public function getEmployeeData(User $user)
    {
        try {
            $empleado = Empleado::where('user_id', $user->id)->first();

            return response()->json([
                'success' => true,
                'employee' => $empleado ? [
                    'id' => $empleado->id,
                    'cargo' => $empleado->cargo,
                    'telefono' => $empleado->telefono,
                    'user_id' => $empleado->user_id,
                ] : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos del empleado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update employee data for a user
     */
    public function updateEmployeeData(Request $request, User $user)
    {
        $request->validate([
            'cargo' => 'required|string|max:25',
            'telefono' => 'required|string|size:8',
        ]);

        try {
            DB::beginTransaction();

            // Buscar o crear el empleado
            $empleado = Empleado::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'cargo' => $request->cargo,
                    'telefono' => $request->telefono,
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Datos del empleado actualizados correctamente',
                'employee' => [
                    'id' => $empleado->id,
                    'cargo' => $empleado->cargo,
                    'telefono' => $empleado->telefono,
                    'user_id' => $empleado->user_id,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los datos del empleado: ' . $e->getMessage()
            ], 500);
        }
    }
}
