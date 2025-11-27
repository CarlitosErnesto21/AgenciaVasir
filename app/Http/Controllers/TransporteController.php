<?php

namespace App\Http\Controllers;

use App\Models\Transporte;
use Illuminate\Http\Request;

class TransporteController extends Controller
{
    public function index()
    {
        $transportes = Transporte::all();
        return response()->json($transportes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:transportes,nombre',
                'regex:/^[A-ZÁÉÍÓÚÑÜ0-9\s]+$/', // Solo letras con tildes, números y espacios
            ],
            'capacidad' => 'required|integer|min:1',
        ], [
            // Solo mensajes que no se validan del lado cliente
            'nombre.unique' => 'Ya existe un transporte con esta descripción.',
            'nombre.min' => 'La descripción debe tener al menos 3 caracteres.',
            'nombre.max' => 'La descripción no puede exceder 50 caracteres.',
            'nombre.regex' => 'La descripción solo puede contener letras con tildes, números y espacios.',
        ]);

        // Normalizar campos antes de guardar
        $validated['nombre'] = $this->normalizeText($validated['nombre'], true); // Con números

        $transporte = Transporte::create($validated);

        return response()->json([
            'message' => 'Transporte creado exitosamente',
            'transporte' => $transporte,
        ]);
    }

    public function show(Transporte $transporte)
    {
        return response()->json($transporte);
    }

    public function update(Request $request, Transporte $transporte)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:transportes,nombre,' . $transporte->id,
                'regex:/^[A-ZÁÉÍÓÚÑÜ0-9\s]+$/', // Solo letras con tildes, números y espacios
            ],
            'capacidad' => 'required|integer|min:1',
        ], [
            // Solo mensajes que no se validan del lado cliente
            'nombre.unique' => 'Ya existe un transporte con esta descripción.',
            'nombre.min' => 'La descripción debe tener al menos 3 caracteres.',
            'nombre.max' => 'La descripción no puede exceder 50 caracteres.',
            'nombre.regex' => 'La descripción solo puede contener letras con tildes, números y espacios.',
        ]);

        // Normalizar campos antes de actualizar
        $validated['nombre'] = $this->normalizeText($validated['nombre'], true); // Con números

        $transporte->update($validated);

        return response()->json([
            'message' => 'Transporte actualizado exitosamente',
            'transporte' => $transporte,
        ]);
    }

    public function destroy(Transporte $transporte)
    {
        // Verificar si el transporte tiene tours asociados
        $toursAsociados = $transporte->tours()->count();
        
        if ($toursAsociados > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el transporte porque tiene ' . $toursAsociados . ' tour(s) asociado(s).',
                'error' => 'TOURS_ASOCIADOS',
                'tours_count' => $toursAsociados
            ], 400);
        }

        $transporte->delete();

        return response()->json([
            'message' => 'Transporte eliminado exitosamente',
        ]);
    }

    /**
     * Normalizar texto: convertir a mayúsculas y limpiar espacios múltiples
     */
    private function normalizeText($text, $allowNumbers = false)
    {
        // Convertir a mayúsculas
        $text = strtoupper($text);
        
        // Limpiar espacios múltiples y espacios al inicio/final
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        return $text;
    }
}