<?php

namespace App\Http\Controllers;

use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaisController extends Controller
{
    public function index()
    {
        $paises = Pais::all();
        return response()->json($paises);
    }

    public function store(Request $request)
    {
        // Normalizar el nombre ANTES de validar
        $nombreNormalizado = $this->normalizarNombre($request->nombre);
        
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($nombreNormalizado) {
                    // Validar que solo contenga letras, espacios y tildes (sin números ni caracteres especiales)
                    if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/u', $value)) {
                        $fail('El nombre solo puede contener letras y espacios. No se permiten números ni caracteres especiales.');
                    }
                    // Validar duplicados
                    if (Pais::whereRaw('UPPER(TRIM(REGEXP_REPLACE(nombre, "[[:space:]]+", " "))) = ?', [strtoupper($nombreNormalizado)])->exists()) {
                        $fail('Ya existe un país con este nombre.');
                    }
                }
            ],
        ], [
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
        ]);

        // Asignar el nombre normalizado
        $validated['nombre'] = $nombreNormalizado;
        
        $pais = Pais::create($validated);

        return response()->json([
            'message' => 'País creado exitosamente',
            'pais' => $pais,
        ]);
    }

    public function show(Pais $pais)
    {
        return response()->json($pais);
    }

    public function update(Request $request, Pais $pais)
    {
        // Normalizar el nombre ANTES de validar
        $nombreNormalizado = $this->normalizarNombre($request->nombre);
        
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($nombreNormalizado, $pais) {
                    // Validar que solo contenga letras, espacios y tildes (sin números ni caracteres especiales)
                    if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/u', $value)) {
                        $fail('El nombre solo puede contener letras y espacios. No se permiten números ni caracteres especiales.');
                    }
                    // Validar duplicados
                    $existe = Pais::whereRaw('UPPER(TRIM(REGEXP_REPLACE(nombre, "[[:space:]]+", " "))) = ?', [strtoupper($nombreNormalizado)])
                        ->where('id', '!=', $pais->id)
                        ->exists();
                    
                    if ($existe) {
                        $fail('Ya existe un país con este nombre.');
                    }
                }
            ],
        ], [
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
        ]);

        // Asignar el nombre normalizado
        $validated['nombre'] = $nombreNormalizado;

        $pais->update($validated);
        $pais->refresh();

        return response()->json([
            'message' => 'País actualizado exitosamente',
            'pais' => $pais,
        ]);
    }

    public function destroy(Pais $pais)
    {
        try {
            if ($pais->provincias()->count() > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar el país porque tiene provincias asociadas.'
                ], 422);
            }

            $pais->delete();

            return response()->json([
                'message' => 'País eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el país: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Normalizar nombre: trim, múltiples espacios a uno, convertir a mayúsculas
     */
    private function normalizarNombre($nombre)
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', $nombre)));
    }
}