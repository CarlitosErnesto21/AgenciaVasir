<?php

namespace App\Http\Controllers;

use App\Models\Provincia;
use Illuminate\Http\Request;

class ProvinciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Provincia::with('pais');

        // Filtrar por país si se proporciona el parámetro pais_id
        if ($request->has('pais_id') && $request->pais_id) {
            $query->where('pais_id', $request->pais_id);
        }

        $provincias = $query->get();
        return response()->json($provincias);
    }

   public function store(Request $request)
   {
        // Normalizar el nombre antes de validar
        $nombreNormalizado = $this->normalizarNombre($request->nombre);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($nombreNormalizado, $request) {
                    // Validación: solo letras y espacios (permitiendo tildes)
                    if (!preg_match('/^[\p{L} ]+$/u', $value)) {
                        $fail('El nombre solo puede contener letras.');
                    }

                    // Validación: no permitir provincia repetida en el mismo país
                    if (Provincia::where('pais_id', $request->pais_id)
                        ->whereRaw('LOWER(TRIM(REGEXP_REPLACE(nombre, "[[:space:]]+", " "))) = ?', [strtolower($nombreNormalizado)])
                        ->exists()) {
                        $fail('Este país ya tiene registrada una provincia con este nombre.');
                    }
                }
            ],
            'pais_id' => ['required', 'exists:paises,id'],
        ], [
            'nombre.required' => 'El nombre de la provincia es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
            'pais_id.required' => 'Debe seleccionar un país.',
            'pais_id.exists' => 'El país seleccionado no existe.',
        ]);

        // Asignar el nombre normalizado
        $validated['nombre'] = $nombreNormalizado;

        $provincia = Provincia::create($validated);

        return response()->json([
            'message' => 'Provincia creada exitosamente',
            'provincia' => $provincia,
        ]);
    }


    public function show(Provincia $provincia)
    {
        $provincia->load('pais');
        return response()->json($provincia);
    }

    public function update(Request $request, Provincia $provincia)
    {
        // Normalizar el nombre antes de validar
        $nombreNormalizado = $this->normalizarNombre($request->nombre);

        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($nombreNormalizado, $request, $provincia) {
                    // Validación: solo letras y espacios (permitiendo tildes)
                    if (!preg_match('/^[\p{L} ]+$/u', $value)) {
                        $fail('El nombre solo puede contener letras y espacios.');
                    }

                    // Validación: no permitir provincia repetida en el mismo país (excepto si es la misma provincia que se actualiza)
                    $existe = Provincia::where('pais_id', $request->pais_id)
                        ->whereRaw('LOWER(TRIM(REGEXP_REPLACE(nombre, "[[:space:]]+", " "))) = ?', [strtolower($nombreNormalizado)])
                        ->where('id', '!=', $provincia->id)
                        ->exists();

                    if ($existe) {
                        $fail('Este país ya tiene registrada una provincia con este nombre.');
                    }
                }
            ],
            'pais_id' => ['required', 'exists:paises,id'],
        ], [
            'nombre.required' => 'El nombre de la provincia es obligatorio.',
            'nombre.string' => 'El nombre debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
            'pais_id.required' => 'Debe seleccionar un país.',
            'pais_id.exists' => 'El país seleccionado no existe.',
        ]);

        // Asignar el nombre normalizado
        $validated['nombre'] = $nombreNormalizado;

        // Actualizar la provincia
        $provincia->update($validated);
        $provincia->refresh();
        $provincia->load('pais');

        return response()->json([
            'message' => 'Provincia actualizada exitosamente',
            'provincia' => $provincia,
        ]);
    }

    public function destroy(Provincia $provincia)
    {
        $provincia->delete();

        return response()->json([
            'message' => 'Provincia eliminada exitosamente',
        ]);
    }

     private function normalizarNombre($nombre)
    {
        return ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $nombre))));
    }
}
