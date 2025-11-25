<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountDeletedByAdminMail;
use Inertia\Inertia;

class ClienteController extends Controller
{
    // Vista de configuración de clientes (Inertia)
    public function index()
    {
        // Obtener todos los usuarios con el rol 'Cliente' incluyendo sus datos de cliente si existen
        $clientes = User::whereHas('roles', function($query) {
            $query->where('name', 'Cliente');
        })
        ->with(['cliente']) // Cargar relación cliente si existe
        ->get()
        ->map(function($user) {
            // Si el usuario tiene datos de cliente, combinar la información
            if ($user->cliente) {
                return [
                    'id' => $user->cliente->id,
                    'user_id' => $user->id,
                    'user' => $user,
                    'numero_identificacion' => $user->cliente->numero_identificacion,
                    'fecha_nacimiento' => $user->cliente->fecha_nacimiento,
                    'genero' => $user->cliente->genero,
                    'tipo_documento' => $user->cliente->tipo_documento,
                    'direccion' => $user->cliente->direccion,
                    'telefono' => $user->cliente->telefono,
                    'created_at' => $user->cliente->created_at,
                    'updated_at' => $user->cliente->updated_at,
                ];
            }
            // Si no tiene datos de cliente, crear estructura con datos básicos del usuario
            return [
                'id' => null, // Sin ID de cliente
                'user_id' => $user->id,
                'user' => $user,
                'numero_identificacion' => 'No registrado',
                'fecha_nacimiento' => null,
                'genero' => 'No registrado',
                'tipo_documento' => 'No registrado',
                'direccion' => 'No registrada',
                'telefono' => 'No registrado',
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return Inertia::render('Configuracion/Clientes', [
            'clientes' => $clientes
        ]);
    }

    // API para obtener clientes (JSON)
    public function getClientes()
    {
        // Obtener todos los usuarios con el rol 'Cliente' incluyendo sus datos de cliente si existen
        $clientes = User::whereHas('roles', function($query) {
            $query->where('name', 'Cliente');
        })
        ->with(['cliente']) // Cargar relación cliente si existe
        ->get()
        ->map(function($user) {
            // Si el usuario tiene datos de cliente, combinar la información
            if ($user->cliente) {
                return [
                    'id' => $user->cliente->id,
                    'user_id' => $user->id,
                    'user' => $user,
                    'numero_identificacion' => $user->cliente->numero_identificacion,
                    'fecha_nacimiento' => $user->cliente->fecha_nacimiento,
                    'genero' => $user->cliente->genero,
                    'tipo_documento' => $user->cliente->tipo_documento,
                    'direccion' => $user->cliente->direccion,
                    'telefono' => $user->cliente->telefono,
                    'created_at' => $user->cliente->created_at,
                    'updated_at' => $user->cliente->updated_at,
                ];
            }
            // Si no tiene datos de cliente, crear estructura con datos básicos del usuario
            return [
                'id' => null, // Sin ID de cliente
                'user_id' => $user->id,
                'user' => $user,
                'numero_identificacion' => 'No registrado',
                'fecha_nacimiento' => null,
                'genero' => 'No registrado',
                'tipo_documento' => 'No registrado',
                'direccion' => 'No registrada',
                'telefono' => 'No registrado',
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'clientes' => $clientes
        ]);
    }

    // Mostrar un cliente específico
    public function show($id)
    {
        $cliente = Cliente::with(['user'])->findOrFail($id);
        return response()->json($cliente);
    }

    /**
     * Normalizar y validar número de identificación según tipo de documento
     */
    private function validateDocumentNumber(&$validated)
    {
        // Normalizar número de identificación para PASAPORTE
        if ($validated['tipo_documento'] === 'PASAPORTE') {
            $validated['numero_identificacion'] = strtoupper(preg_replace('/[\s-]/', '', $validated['numero_identificacion']));
        }

        // Validar formato según tipo de documento
        if ($validated['tipo_documento'] === 'DUI') {
            if (!preg_match('/^\d{8}-\d{1}$/', $validated['numero_identificacion'])) {
                return [
                    'success' => false,
                    'message' => 'El DUI debe tener el formato: 12345678-9',
                    'errors' => [
                        'numero_identificacion' => ['El DUI debe tener el formato: 12345678-9']
                    ]
                ];
            }
        } elseif ($validated['tipo_documento'] === 'PASAPORTE') {
            // Validar el número ya normalizado (solo A-Z y 0-9, entre 6 y 9 caracteres)
            if (!preg_match('/^[A-Z0-9]{6,9}$/', $validated['numero_identificacion'])) {
                return [
                    'success' => false,
                    'message' => 'El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)',
                    'errors' => [
                        'numero_identificacion' => ['El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)']
                    ]
                ];
            }
        }

        return ['success' => true];
    }

    /**
     * Validar edad mínima de 18 años
     */
    public function validateMinimumAge($fechaNacimiento)
    {
        if (!$fechaNacimiento) {
            return [
                'success' => false,
                'message' => 'La fecha de nacimiento es requerida',
                'errors' => [
                    'fecha_nacimiento' => ['La fecha de nacimiento es requerida']
                ]
            ];
        }

        $fechaNac = \Carbon\Carbon::parse($fechaNacimiento);
        $edad = $fechaNac->age;

        if ($edad < 18) {
            return [
                'success' => false,
                'message' => "Debe ser mayor de edad (18 años). Edad actual: {$edad} años",
                'errors' => [
                    'fecha_nacimiento' => ["Debe ser mayor de edad (18 años). Edad actual: {$edad} años"]
                ]
            ];
        }

        // Validar que la fecha no sea futura
        if ($fechaNac->isFuture()) {
            return [
                'success' => false,
                'message' => 'La fecha de nacimiento no puede ser futura',
                'errors' => [
                    'fecha_nacimiento' => ['La fecha de nacimiento no puede ser futura']
                ]
            ];
        }

        return ['success' => true];
    }

    // Crear un nuevo cliente
    public function store(Request $request)
    {

        $validated = $request->validate([
            // Número de identificación: requerido, formato según tipo_documento
            'numero_identificacion' => [
                'required',
                'string',
                'max:25',
                function($attribute, $value, $fail) use ($request) {
                    $tipo = $request->input('tipo_documento');
                    if ($tipo === 'DUI') {
                        if (!preg_match('/^\d{8}-\d{1}$/', $value)) {
                            $fail('El DUI debe tener 9 dígitos (formato: 12345678-9)');
                        }
                    } elseif ($tipo === 'PASAPORTE') {
                        if (!preg_match('/^[A-Z0-9]{6,9}$/', strtoupper($value))) {
                            $fail('El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)');
                        }
                    }
                }
            ],
            // Fecha de nacimiento: requerido, mayor de edad
            'fecha_nacimiento' => [
                'required',
                'date',
                function($attribute, $value, $fail) {
                    $fecha = \Carbon\Carbon::parse($value);
                    $edad = $fecha->age;
                    if ($edad < 18) {
                        $fail('Debe ser mayor de edad (18 años). Edad actual: ' . $edad . ' años');
                    }
                    if ($fecha->isFuture()) {
                        $fail('La fecha de nacimiento no puede ser futura');
                    }
                }
            ],
            // Género: solo MASCULINO o FEMENINO
            'genero' => 'required|in:MASCULINO,FEMENINO',
            // Dirección: requerido, máximo 200
            'direccion' => 'required|string|max:200',
            // Teléfono: requerido, internacional, máximo 30
            'telefono' => [
                'required',
                'string',
                'max:30',
                'regex:/^\+[0-9\s\-()]+$/',
            ],
            'user_id' => 'sometimes|exists:users,id',
            // Tipo de documento: solo DUI o PASAPORTE
            'tipo_documento' => 'required|in:DUI,PASAPORTE',
        ]);

        // Validar y normalizar número de identificación
        $validationResult = $this->validateDocumentNumber($validated);
        if (!$validationResult['success']) {
            // Estructura de error compatible con el frontend
            return response()->json([
                'success' => false,
                'message' => $validationResult['message'],
                'errors' => [
                    'numero_identificacion' => $validationResult['errors']['numero_identificacion'] ?? [$validationResult['message']]
                ]
            ], 422);
        }

        // Validar edad mínima de 18 años
        $ageValidationResult = $this->validateMinimumAge($validated['fecha_nacimiento']);
        if (!$ageValidationResult['success']) {
            return response()->json($ageValidationResult, 422);
        }

        // Validar que número de identificación sea único
        $existeDocumento = Cliente::where('numero_identificacion', $validated['numero_identificacion'])->first();
        if ($existeDocumento) {
            return response()->json([
                'success' => false,
                'message' => 'Este número de identificación ya está registrado.'
            ], 422);
        }

        // Validar que el teléfono no exista en la tabla clientes
        $existeTelefonoCliente = Cliente::where('telefono', $validated['telefono'])->first();
        if ($existeTelefonoCliente) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ], 422);
        }

        // Validar que el teléfono no exista en la tabla empleados
        $existeTelefonoEmpleado = \App\Models\Empleado::where('telefono', $validated['telefono'])->first();
        if ($existeTelefonoEmpleado) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ], 422);
        }

        // Si no se proporciona user_id, usar el usuario autenticado
        if (!isset($validated['user_id']) && $request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        // Verificar que no exista ya un cliente para este usuario
        $existeCliente = Cliente::where('user_id', $validated['user_id'])->first();
        if ($existeCliente) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un perfil de cliente para este usuario'
            ], 422);
        }

        $cliente = Cliente::create($validated);
        $cliente->load('tipoDocumento', 'user');

        return response()->json([
            'success' => true,
            'data' => $cliente,
            'message' => 'Cliente creado exitosamente'
        ], 201);
    }

    // Actualizar un cliente existente
    public function update(Request $request, $id)
    {

        $validated = $request->validate([
            'numero_identificacion' => [
                'required',
                'string',
                'max:25',
                function($attribute, $value, $fail) use ($request) {
                    $tipo = $request->input('tipo_documento');
                    if ($tipo === 'DUI') {
                        if (!preg_match('/^\d{8}-\d{1}$/', $value)) {
                            $fail('El DUI debe tener 9 dígitos (formato: 12345678-9)');
                        }
                    } elseif ($tipo === 'PASAPORTE') {
                        if (!preg_match('/^[A-Z0-9]{6,9}$/', strtoupper($value))) {
                            $fail('El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)');
                        }
                    }
                }
            ],
            'fecha_nacimiento' => [
                'required',
                'date',
                function($attribute, $value, $fail) {
                    $fecha = \Carbon\Carbon::parse($value);
                    $edad = $fecha->age;
                    if ($edad < 18) {
                        $fail('Debe ser mayor de edad (18 años). Edad actual: ' . $edad . ' años');
                    }
                    if ($fecha->isFuture()) {
                        $fail('La fecha de nacimiento no puede ser futura');
                    }
                }
            ],
            'genero' => 'required|in:MASCULINO,FEMENINO',
            'direccion' => 'required|string|max:200',
            'telefono' => [
                'required',
                'string',
                'max:30',
                'regex:/^\+[0-9\s\-()]+$/',
            ],
            'user_id' => 'required|exists:users,id',
            'tipo_documento' => 'required|in:DUI,PASAPORTE',
        ]);

        // Validar y normalizar número de identificación
        $validationResult = $this->validateDocumentNumber($validated);
        if (!$validationResult['success']) {
            // Estructura de error compatible con el frontend
            return response()->json([
                'success' => false,
                'message' => $validationResult['message'],
                'errors' => [
                    'numero_identificacion' => $validationResult['errors']['numero_identificacion'] ?? [$validationResult['message']]
                ]
            ], 422);
        }

        // Validar edad mínima de 18 años
        $ageValidationResult = $this->validateMinimumAge($validated['fecha_nacimiento']);
        if (!$ageValidationResult['success']) {
            return response()->json($ageValidationResult, 422);
        }

        $cliente = Cliente::findOrFail($id);

        // Validar que número de identificación sea único (excepto el actual)
        $existeDocumento = Cliente::where('numero_identificacion', $validated['numero_identificacion'])
            ->where('id', '!=', $id)
            ->first();
        if ($existeDocumento) {
            return response()->json([
                'success' => false,
                'message' => 'Este número de identificación ya está registrado.'
            ], 422);
        }

        // Validar que el teléfono no exista en otros clientes (excepto el actual)
        $existeTelefonoCliente = Cliente::where('telefono', $validated['telefono'])
            ->where('id', '!=', $id)
            ->first();
        if ($existeTelefonoCliente) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ], 422);
        }

        // Validar que el teléfono no exista en la tabla empleados
        $existeTelefonoEmpleado = \App\Models\Empleado::where('telefono', $validated['telefono'])->first();
        if ($existeTelefonoEmpleado) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ], 422);
        }

        $cliente->update($validated);
        return response()->json($cliente);
    }

    // Eliminar un usuario con rol cliente y todos sus datos relacionados
    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            // El $id ahora puede ser user_id (si no tiene registro de cliente) o cliente_id
            // Primero intentar encontrar por cliente_id, si no existe, buscar por user_id
            $user = null;
            $cliente = null;

            if ($id) {
                // Intentar encontrar cliente por ID
                $cliente = Cliente::with(['user', 'reservas', 'ventas'])->find($id);

                if ($cliente) {
                    $user = $cliente->user;
                } else {
                    // Si no se encuentra cliente, buscar usuario directamente con rol Cliente
                    $user = User::whereHas('roles', function($query) {
                        $query->where('name', 'Cliente');
                    })->with(['cliente.reservas', 'cliente.ventas'])->findOrFail($id);

                    $cliente = $user->cliente; // Puede ser null si no tiene datos de cliente
                }
            }

            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Obtener el motivo de eliminación del request
            $deletionReason = $request->input('deletion_reason', 'No se especificó un motivo');

            // Información del usuario antes de eliminar
            $userInfo = [
                'nombre' => $user->name,
                'email' => $user->email,
                'reservas_count' => $cliente ? $cliente->reservas->count() : 0,
                'ventas_count' => $cliente ? $cliente->ventas->count() : 0
            ];

            // Enviar correo de notificación antes de eliminar
            try {
                Mail::to($user->email)->send(new AccountDeletedByAdminMail($user, $deletionReason));
            } catch (\Exception $mailException) {
                Log::warning('No se pudo enviar el correo de eliminación de cuenta: ' . $mailException->getMessage());
                // Continuar con la eliminación aunque falle el envío del correo
            }

            // Si el usuario tiene datos de cliente, eliminar datos asociados
            if ($cliente) {
                // 1. Eliminar todas las reservas del cliente
                foreach ($cliente->reservas as $reserva) {
                    // Eliminar detalles de la reserva de tours si existen
                    $reserva->detallesTours()->delete();

                    // Eliminar la reserva
                    $reserva->delete();
                }

                // 2. Eliminar todas las ventas del cliente
                foreach ($cliente->ventas as $venta) {
                    // Eliminar detalles de la venta
                    $venta->detalleVentas()->delete();

                    // Eliminar pagos asociados a la venta
                    $venta->pagos()->delete();

                    // Eliminar la venta
                    $venta->delete();
                }
            }

            // 3. Eliminar el usuario (esto también eliminará el cliente por el evento booted si existe)
            $user->delete();

            DB::commit();

            return response()->json([
                'message' => 'Usuario cliente eliminado correctamente junto con todos sus datos. Se ha enviado una notificación por correo.',
                'info' => $userInfo,
                'deletion_reason' => $deletionReason
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Error al eliminar el cliente: ' . $e->getMessage()
            ], 500);
        }
    }    // Obtener datos del cliente autenticado
    public function obtenerDatosAutenticado(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Buscar el cliente asociado al usuario autenticado
        $cliente = Cliente::where('user_id', $user->id)
            ->first();

        if (!$cliente) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron datos de cliente para este usuario'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'cliente' => $cliente
        ]);
    }

    // Alias para obtener el perfil del usuario autenticado
    public function miPerfil(Request $request)
    {
        return $this->obtenerDatosAutenticado($request);
    }

    // Verificar si el usuario autenticado tiene datos de cliente completos
    public function verificarDatosCompletos(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado',
                'tiene_datos_completos' => false
            ], 401);
        }

        // Buscar el cliente asociado al usuario autenticado
        $cliente = Cliente::where('user_id', $user->id)
            ->first();

        if (!$cliente) {
            return response()->json([
                'success' => true,
                'message' => 'Usuario sin datos de cliente',
                'tiene_datos_completos' => false,
                'cliente' => null
            ]);
        }

        // Verificar que todos los campos requeridos estén completos
        $camposRequeridos = [
            'numero_identificacion',
            'fecha_nacimiento',
            'genero',
            'direccion',
            'telefono',
            'tipo_documento'
        ];

        $datosCompletos = true;
        foreach ($camposRequeridos as $campo) {
            if (empty($cliente->$campo) ||
                ($campo === 'numero_identificacion' && $cliente->$campo === $user->email) ||
                ($campo === 'direccion' && $cliente->$campo === 'No especificada') ||
                ($campo === 'telefono' && $cliente->$campo === 'No especificado') ||
                ($campo === 'genero' && $cliente->$campo === 'No especificado')
            ) {
                $datosCompletos = false;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $datosCompletos ? 'Usuario con datos completos' : 'Usuario con datos incompletos',
            'tiene_datos_completos' => $datosCompletos,
            'cliente' => $datosCompletos ? $cliente : null
        ]);
    }

    // Método específico para registro de cliente desde la tienda
    public function registroCliente(Request $request)
    {
        // Verificar que el usuario esté autenticado (desde middleware o header)
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $validated = $request->validate([
            'numero_identificacion' => [
                'required',
                'string',
                'max:25',
                function($attribute, $value, $fail) use ($request) {
                    $tipo = $request->input('tipo_documento');
                    if ($tipo === 'DUI') {
                        if (!preg_match('/^\d{8}-\d{1}$/', $value)) {
                            $fail('El DUI debe tener 9 dígitos (formato: 12345678-9)');
                        }
                    } elseif ($tipo === 'PASAPORTE') {
                        if (!preg_match('/^[A-Z0-9]{6,9}$/', strtoupper($value))) {
                            $fail('El PASAPORTE debe tener entre 6 y 9 caracteres (solo letras mayúsculas y números)');
                        }
                    }
                }
            ],
            'fecha_nacimiento' => [
                'required',
                'date',
                function($attribute, $value, $fail) {
                    $fecha = \Carbon\Carbon::parse($value);
                    $edad = $fecha->age;
                    if ($edad < 18) {
                        $fail('Debe ser mayor de edad (18 años). Edad actual: ' . $edad . ' años');
                    }
                    if ($fecha->isFuture()) {
                        $fail('La fecha de nacimiento no puede ser futura');
                    }
                }
            ],
            'genero' => 'required|in:MASCULINO,FEMENINO',
            'direccion' => 'required|string|max:200',
            'telefono' => [
                'required',
                'string',
                'max:30',
                'regex:/^\+[0-9\s\-()]+$/',
            ],
            'tipo_documento' => 'required|in:DUI,PASAPORTE',
        ]);

        // Validar y normalizar número de identificación
        $validationResult = $this->validateDocumentNumber($validated);
        if (!$validationResult['success']) {
            return response()->json($validationResult, 422);
        }

        // Validar edad mínima de 18 años
        $ageValidationResult = $this->validateMinimumAge($validated['fecha_nacimiento']);
        if (!$ageValidationResult['success']) {
            return response()->json($ageValidationResult, 422);
        }

        // Validar que número de identificación sea único
        $existeDocumento = Cliente::where('numero_identificacion', $validated['numero_identificacion'])->first();
        if ($existeDocumento) {
            return response()->json([
                'success' => false,
                'message' => 'Este número de identificación ya está registrado.'
            ], 422);
        }

        // Agregar el user_id automáticamente
        $validated['user_id'] = $user->id;

        // Verificar que no exista ya un cliente para este usuario
        $existeCliente = Cliente::where('user_id', $user->id)->first();
        if ($existeCliente) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un perfil de cliente para este usuario'
            ], 422);
        }

        // Validar que el teléfono no exista en la tabla clientes (comparación directa)
        $existeTelefonoCliente = Cliente::where('telefono', $validated['telefono'])->first();
        if ($existeTelefonoCliente) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ], 422);
        }

        // Validar que el teléfono no exista en la tabla empleados (comparación directa)
        $existeTelefonoEmpleado = \App\Models\Empleado::where('telefono', $validated['telefono'])->first();
        if ($existeTelefonoEmpleado) {
            return response()->json([
                'success' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ], 422);
        }

        $cliente = Cliente::create($validated);
        $cliente->load('user');
        return response()->json([
            'success' => true,
            'data' => $cliente,
            'message' => 'Cliente creado exitosamente'
        ], 201);
    }



    // Buscar clientes por nombre (API para informes)
    public function buscarClientes(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['clientes' => []]);
        }

        try {
            // Buscar usuarios con rol Cliente que coincidan con el nombre
            $usuarios = User::whereHas('roles', function($q) {
                $q->where('name', 'Cliente');
            })
            ->where('name', 'LIKE', "%{$query}%")
            ->with(['cliente'])
            ->limit(10)
            ->get();

            $clientes = $usuarios->map(function($user) {
                return [
                    'id' => $user->cliente ? $user->cliente->id : null,
                    'user_id' => $user->id,
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'name' => $user->name,
                    'email' => $user->email,
                    'numero_identificacion' => $user->cliente ? $user->cliente->numero_identificacion : null,
                    'telefono' => $user->cliente ? $user->cliente->telefono : null,
                ];
            });

            return response()->json(['clientes' => $clientes]);

        } catch (\Exception $e) {
            Log::error('Error buscando clientes: ' . $e->getMessage());
            return response()->json(['clientes' => []], 500);
        }
    }

    // Validar teléfono único en tiempo real
    public function validarTelefono(Request $request)
    {
        $telefono = $request->input('telefono');
        $clienteId = $request->input('cliente_id'); // Para excluir en ediciones

        if (!$telefono) {
            return response()->json([
                'success' => false,
                'message' => 'Teléfono requerido'
            ], 400);
        }

        // Verificar en tabla clientes (comparación directa como EmpleadoController)
        $queryClientes = Cliente::where('telefono', $telefono);
        if ($clienteId) {
            $queryClientes->where('id', '!=', $clienteId);
        }
        $existeEnClientes = $queryClientes->first();

        if ($existeEnClientes) {
            return response()->json([
                'success' => false,
                'disponible' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ]);
        }

        // Verificar en tabla empleados (comparación directa como EmpleadoController)
        $existeEnEmpleados = \App\Models\Empleado::where('telefono', $telefono)->first();
        if ($existeEnEmpleados) {
            return response()->json([
                'success' => false,
                'disponible' => false,
                'message' => 'Ingresa un número de teléfono diferente, este ya está registrado.'
            ]);
        }
        return response()->json([
            'success' => true,
            'disponible' => true,
            'message' => 'Teléfono disponible'
        ]);
    }

    public function validarDocumento(Request $request)
    {
        $numeroIdentificacion = $request->input('numero_identificacion');
        $usuarioId = $request->input('usuario_id'); // Para excluir en ediciones (perfil)

        if (!$numeroIdentificacion) {
            return response()->json([
                'success' => false,
                'message' => 'Número de identificación requerido'
            ], 400);
        }

        // Verificar en tabla clientes
        $queryClientes = Cliente::where('numero_identificacion', $numeroIdentificacion);
        if ($usuarioId) {
            // Excluir por usuario_id en lugar de cliente_id para el perfil
            $queryClientes->whereHas('user', function($query) use ($usuarioId) {
                $query->where('id', '!=', $usuarioId);
            });
        }
        $existeEnClientes = $queryClientes->first();

        if ($existeEnClientes) {
            return response()->json([
                'success' => false,
                'disponible' => false,
                'message' => 'Este número de identificación ya está registrado por otro cliente.'
            ]);
        }

        return response()->json([
            'success' => true,
            'disponible' => true,
            'message' => 'Número de identificación disponible'
        ]);
    }
}
