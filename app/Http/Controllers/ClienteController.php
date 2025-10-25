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
            'genero' => 'required|in:MASCULINO,FEMENINO',
            'direccion' => 'required|string|max:200',
            'telefono' => 'required|string|max:30',
            'user_id' => 'required|exists:users,id',
            'tipo_documento_id' => 'required|exists:tipos_documentos,id',
        ]);

        $cliente = Cliente::create($validated);
        return response()->json($cliente, 201);
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
        $ventas = $cliente->ventas()->with(['detalles.producto'])->orderBy('fecha', 'desc')->get();

        return Inertia::render('Configuracion/ClienteComponents/VentasCliente', [
            'cliente' => $cliente,
            'ventas' => $ventas
        ]);
    }
}
