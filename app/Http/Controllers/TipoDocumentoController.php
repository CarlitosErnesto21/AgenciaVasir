<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TipoDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Si es una petición AJAX, devolver JSON
        if ($request->expectsJson()) {
            $tiposDocumentos = TipoDocumento::orderBy('nombre')->get();
            return response()->json([
                'success' => true,
                'tipos' => $tiposDocumentos
            ]);
        }

        // Si es una petición normal, devolver la vista Inertia
        $tiposDocumentos = TipoDocumento::orderBy('nombre')->get();
        return Inertia::render('Configuracion/TiposDocumento', [
            'tiposDocumentos' => $tiposDocumentos
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Limpiar y validar el nombre
        $nombre = trim($request->input('nombre'));
        
        // Validar que solo contenga letras, espacios y acentos
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
            return response()->json([
                'success' => false,
                'message' => 'El nombre solo puede contener letras, espacios y acentos. No se permiten números ni caracteres especiales.'
            ], 422);
        }

        // Limpiar múltiples espacios consecutivos
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        
        // Convertir a mayúsculas
        $nombre = strtoupper($nombre);
        
        // Verificar si ya existe un tipo de documento con el mismo nombre
        if (TipoDocumento::where('nombre', $nombre)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe un tipo de documento con el nombre "' . $nombre . '". Por favor, elige un nombre diferente.'
            ], 422);
        }

        // Validar los datos del formulario
        $request->validate([
            'nombre' => 'required|string|min:3|max:20',
        ], [
            'nombre.required' => 'El nombre del tipo de documento es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 20 caracteres.',
        ]);

        try {
            // Crear un nuevo tipo de documento
            $tipoDocumento = TipoDocumento::create([
                'nombre' => $nombre
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de documento creado exitosamente',
                'tipo_documento' => $tipoDocumento,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tipo de documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoDocumento $tipoDocumento)
    {
        // Mostrar los detalles de un tipo de documento específico
        return response()->json($tipoDocumento);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoDocumento $tipoDocumento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoDocumento $tipoDocumento)
    {
        // Limpiar y validar el nombre
        $nombre = trim($request->input('nombre'));
        
        // Validar que solo contenga letras, espacios y acentos
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $nombre)) {
            return response()->json([
                'success' => false,
                'message' => 'El nombre solo puede contener letras, espacios y acentos. No se permiten números ni caracteres especiales.'
            ], 422);
        }

        // Limpiar múltiples espacios consecutivos
        $nombre = preg_replace('/\s+/', ' ', $nombre);
        
        // Convertir a mayúsculas
        $nombre = strtoupper($nombre);
        
        // Verificar si ya existe un tipo de documento con el mismo nombre (excluyendo el actual)
        if (TipoDocumento::where('nombre', $nombre)->where('id', '!=', $tipoDocumento->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe otro tipo de documento con el nombre "' . $nombre . '". Por favor, elige un nombre diferente.'
            ], 422);
        }

        // Validar los datos del formulario
        $request->validate([
            'nombre' => 'required|string|min:3|max:20',
        ], [
            'nombre.required' => 'El nombre del tipo de documento es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede tener más de 20 caracteres.',
        ]);

        try {
            // Actualizar el tipo de documento
            $tipoDocumento->update([
                'nombre' => $nombre
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tipo de documento actualizado exitosamente',
                'tipo_documento' => $tipoDocumento,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tipo de documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoDocumento $tipoDocumento)
    {
        try {
            // Verificar si hay clientes usando este tipo de documento
            if ($tipoDocumento->clientes()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar este tipo de documento porque está siendo usado por clientes.'
                ], 400);
            }

            // Eliminar el tipo de documento
            $tipoDocumento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tipo de documento eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tipo de documento: ' . $e->getMessage()
            ], 500);
        }
    }
}
