<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteController extends Controller
{
    // Vista de configuración de clientes (Inertia)
    public function index()
    {
        $clientes = Cliente::with(['user', 'tipoDocumento'])->get();

        return Inertia::render('Configuracion/Clientes', [
            'clientes' => $clientes
        ]);
    }

    // API para obtener clientes (JSON)
    public function getClientes()
    {
        $clientes = Cliente::with(['user', 'tipoDocumento'])->get();
        return response()->json([
            'success' => true,
            'clientes' => $clientes
        ]);
    }

    // Mostrar un cliente específico
    public function show($id)
    {
        $cliente = Cliente::with(['user', 'tipoDocumento'])->findOrFail($id);
        return response()->json($cliente);
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
            'tipo_documento_id' => 'required|exists:tipos_documentos,id',
        ]);

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
            'tipo_documento_id' => 'required|exists:tipos_documentos,id',
        ]);

        $cliente = Cliente::findOrFail($id);
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
        $cliente = Cliente::with('tipoDocumento')
            ->where('user_id', $user->id)
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
        $cliente = Cliente::with('tipoDocumento')
            ->where('user_id', $user->id)
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
            'tipo_documento_id'
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
            'tipo_documento_id' => 'required|exists:tipos_documentos,id',
        ]);

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

        $cliente = Cliente::create($validated);
        $cliente->load('tipoDocumento', 'user');

        return response()->json([
            'success' => true,
            'data' => $cliente,
            'message' => 'Cliente creado exitosamente'
        ], 201);
    }

    // Mostrar reservas del cliente
    public function reservas($id)
    {
        $cliente = Cliente::with(['user', 'tipoDocumento'])->findOrFail($id);
        $reservas = $cliente->reservas()->with(['empleado.user'])->orderBy('fecha', 'desc')->get();

        return Inertia::render('Configuracion/ClienteComponents/ReservasCliente', [
            'cliente' => $cliente,
            'reservas' => $reservas
        ]);
    }

    // Mostrar ventas del cliente
    public function ventas($id)
    {
        $cliente = Cliente::with(['user', 'tipoDocumento'])->findOrFail($id);
        $ventas = $cliente->ventas()->with(['detalleVentas.producto'])->orderBy('fecha', 'desc')->get();

        return Inertia::render('Configuracion/ClienteComponents/VentasCliente', [
            'cliente' => $cliente,
            'ventas' => $ventas
        ]);
    }
}
