<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ClienteController extends Controller
{
    // Vista de configuración de clientes (Inertia)
    public function index()
    {
        $clientes = Cliente::with(['user'])->get();

        return Inertia::render('Configuracion/Clientes', [
            'clientes' => $clientes
        ]);
    }

    // API para obtener clientes (JSON)
    public function getClientes()
    {
        $clientes = Cliente::with(['user'])->get();
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
            'numero_identificacion' => 'required|string|max:25',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|string|max:50', // Actualizado para permitir más opciones de género
            'direccion' => 'required|string|max:200',
            'telefono' => 'required|string|max:30',
            'user_id' => 'sometimes|exists:users,id', // Opcional, se puede obtener del usuario autenticado
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
            'numero_identificacion' => 'required|string|max:25',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:MASCULINO,FEMENINO',
            'direccion' => 'required|string|max:200',
            'telefono' => 'required|string|max:30',
            'user_id' => 'required|exists:users,id',
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

    // Eliminar un cliente
    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->delete();
        return response()->json(['message' => 'Cliente eliminado correctamente']);
    }

    // Obtener datos del cliente autenticado
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
            'numero_identificacion' => 'required|string|max:25',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|string|max:50',
            'direccion' => 'required|string|max:200',
            'telefono' => 'required|string|max:30',
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

    // Mostrar reservas del cliente
    public function reservas($id)
    {
        $cliente = Cliente::with(['user'])->findOrFail($id);
        $reservas = $cliente->reservas()->with(['empleado.user'])->orderBy('fecha', 'desc')->get();

        return Inertia::render('Configuracion/ClienteComponents/ReservasCliente', [
            'cliente' => $cliente,
            'reservas' => $reservas
        ]);
    }

    // Mostrar ventas del cliente
    public function ventas($id)
    {
        $cliente = Cliente::with(['user'])->findOrFail($id);
        $ventas = $cliente->ventas()->with(['detalleVentas.producto'])->orderBy('fecha', 'desc')->get();

        return Inertia::render('Configuracion/ClienteComponents/VentasCliente', [
            'cliente' => $cliente,
            'ventas' => $ventas
        ]);
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
}
