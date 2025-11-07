<?php

namespace App\Http\Controllers;

use App\Models\CategoriaHotel;
use Illuminate\Http\Request;

class CategoriaHotelController extends Controller
{
    /**
     * Valida y formatea el nombre de la categoría
     */
    private function validateAndFormatNombre($nombre)
    {
        if (empty($nombre)) {
            return $nombre;
        }
        
        // Convertir a mayúsculas
        $nombre = mb_strtoupper($nombre, 'UTF-8');
        
        
        // ❌ NO permite: @#$%&*()[]{}!¿?.,;:-_+=|\/~`"'<>
        $nombre = preg_replace('/[^A-ZÁÉÍÓÚÑ0-9\s]/u', '', $nombre);
        
        // Reemplazar múltiples espacios consecutivos con uno solo
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        
        // Eliminar espacios al inicio y al final
        $nombre = trim($nombre);
        
        return $nombre;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = CategoriaHotel::all();
        return response()->json($categorias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:50', 'regex:/^[A-ZÁÉÍÓÚÑ0-9\s]+$/u'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, números, espacios y tildes. No se permiten caracteres especiales.',
        ]);

        // Aplicar validaciones de formato
        $validated['nombre'] = $this->validateAndFormatNombre($validated['nombre']);

        $categoria = CategoriaHotel::create($validated);

        return response()->json([
            'message' => 'Categoría de hotel creada exitosamente',
            'categoria' => $categoria,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(CategoriaHotel $categoriaHotel)
    {
        return response()->json($categoriaHotel);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $categoriaHotel = CategoriaHotel::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:50', 'regex:/^[A-ZÁÉÍÓÚÑ0-9\s]+$/u'],
        ], [
            'nombre.regex' => 'El nombre solo puede contener letras, números, espacios y tildes. No se permiten caracteres especiales.',
        ]);

        // Aplicar validaciones de formato
        $validated['nombre'] = $this->validateAndFormatNombre($validated['nombre']);

        $categoriaHotel->update($validated);

        return response()->json([
            'message' => 'Categoría de hotel actualizada exitosamente',
            'categoria' => $categoriaHotel,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $categoriaHotel = CategoriaHotel::findOrFail($id);
        $categoriaHotel->delete();

        return response()->json([
            'message' => 'Categoría de hotel eliminada exitosamente',
            'success' => true
        ]);
    }
}
