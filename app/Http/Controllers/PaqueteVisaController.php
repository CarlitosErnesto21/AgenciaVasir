<?php

namespace App\Http\Controllers;

use App\Models\PaqueteVisa;
use Illuminate\Http\Request;

class PaqueteVisaController extends Controller
{
    public function index()
    {
        return PaqueteVisa::with('user', 'imagenes')->get();
    }

    public function show($id)
    {
        return PaqueteVisa::with('user', 'imagenes')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);
        $paquete = PaqueteVisa::create($data);
        return response()->json($paquete, 201);
    }

    public function update(Request $request, $id)
    {
        $paquete = PaqueteVisa::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);
        $paquete->update($data);
        return response()->json($paquete);
    }

    public function destroy($id)
    {
        $paquete = PaqueteVisa::findOrFail($id);
        $paquete->delete();
        return response()->json(['message' => 'Eliminado correctamente']);
    }
}
