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
            'numero_placa' => [
                'required',
                'string',
                'max:10',
                'unique:transportes,numero_placa',
                'regex:/^(P|M|C|A|R|D|V|G|AB|MB|CD|CC|CR|MI|FA|PE|OF|OI)[ -]?[0-9A-F]{6}$/i'
            ],
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:transportes,nombre',
                'regex:/^[A-ZÁÉÍÓÚÑÜ0-9\s]+$/', // Solo letras con tildes, números y espacios
            ],
            'capacidad' => 'required|integer|min:1',
            'marca' => [
                'required',
                'string',
                'min:2',
                'max:30',
                'regex:/^[A-ZÁÉÍÓÚÑÜ\s]+$/', // Solo letras con tildes y espacios
            ],
            'estado' => 'required|in:DISPONIBLE,NO_DISPONIBLE',
        ], [
            // Solo mensajes que no se validan del lado cliente
            'numero_placa.unique' => 'Esta placa ya está registrada en el sistema.',
            'numero_placa.regex' => 'El formato de la placa no es válido. Debe iniciar con un prefijo válido seguido de 6 caracteres alfanuméricos.',
            'nombre.unique' => 'Ya existe un transporte con esta descripción.',
            'nombre.min' => 'La descripción debe tener al menos 3 caracteres.',
            'nombre.max' => 'La descripción no puede exceder 50 caracteres.',
            'nombre.regex' => 'La descripción solo puede contener letras con tildes, números y espacios.',
            'marca.min' => 'La marca debe tener al menos 2 caracteres.',
            'marca.max' => 'La marca no puede exceder 30 caracteres.',
            'marca.regex' => 'La marca solo puede contener letras con tildes y espacios.',
        ]);

        // Normalizar campos antes de guardar
        $validated['numero_placa'] = strtoupper($validated['numero_placa']); // Convertir placa a mayúsculas
        $validated['nombre'] = $this->normalizeText($validated['nombre'], true); // Con números
        $validated['marca'] = $this->normalizeText($validated['marca'], false); // Sin números

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
            'numero_placa' => [
                'required',
                'string',
                'max:10',
                'unique:transportes,numero_placa,' . $transporte->id,
                'regex:/^(P|M|C|A|R|D|V|G|AB|MB|CD|CC|CR|MI|FA|PE|OF|OI)[ -]?[0-9A-F]{6}$/i'
            ],
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:transportes,nombre,' . $transporte->id,
                'regex:/^[A-ZÁÉÍÓÚÑÜ0-9\s]+$/', // Solo letras con tildes, números y espacios
            ],
            'capacidad' => 'required|integer|min:1',
            'marca' => [
                'required',
                'string',
                'min:2',
                'max:30',
                'regex:/^[A-ZÁÉÍÓÚÑÜ\s]+$/', // Solo letras con tildes y espacios
            ],
            'estado' => 'required|in:DISPONIBLE,NO_DISPONIBLE',
        ], [
            // Solo mensajes que no se validan del lado cliente
            'numero_placa.unique' => 'Esta placa ya está registrada en el sistema.',
            'numero_placa.regex' => 'El formato de la placa no es válido. Debe iniciar con un prefijo válido seguido de 6 caracteres alfanuméricos.',
            'nombre.unique' => 'Ya existe un transporte con esta descripción.',
            'nombre.min' => 'La descripción debe tener al menos 3 caracteres.',
            'nombre.max' => 'La descripción no puede exceder 50 caracteres.',
            'nombre.regex' => 'La descripción solo puede contener letras con tildes, números y espacios.',
            'marca.min' => 'La marca debe tener al menos 2 caracteres.',
            'marca.max' => 'La marca no puede exceder 30 caracteres.',
            'marca.regex' => 'La marca solo puede contener letras con tildes y espacios.',
        ]);

        // Normalizar campos antes de actualizar
        $validated['numero_placa'] = strtoupper($validated['numero_placa']); // Convertir placa a mayúsculas
        $validated['nombre'] = $this->normalizeText($validated['nombre'], true); // Con números
        $validated['marca'] = $this->normalizeText($validated['marca'], false); // Sin números

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