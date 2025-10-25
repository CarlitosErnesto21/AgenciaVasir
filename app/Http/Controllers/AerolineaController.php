<?php

namespace App\Http\Controllers;

use App\Models\Aerolinea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AerolineaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtener todas las aerolíneas con sus imágenes
        $aerolineas = Aerolinea::with('imagenes')->get();
        return response()->json($aerolineas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'fecha' => 'required|date',
            'imagenes' => 'nullable|array|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Crear una nueva aerolínea
        $aerolinea = Aerolinea::create($validated);

        // Guardar imágenes usando Storage persistente
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('aerolineas', 'public');
                    $nombreArchivo = basename($path);
                    $aerolinea->imagenes()->create([
                        'nombre' => $nombreArchivo,
                        'imageable_type' => Aerolinea::class,
                        'imageable_id' => $aerolinea->id
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Aerolínea creada exitosamente',
            'aerolinea' => $aerolinea->load('imagenes'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Aerolinea $aerolinea)
    {
        // Validar los datos del formulario
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'fecha' => 'required|date',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'removed_images' => 'nullable|array',
        ]);

        // Validar límite total de imágenes (existentes + nuevas - eliminadas)
        $imagenesExistentes = $aerolinea->imagenes()->count();
        $imagenesAEliminar = $request->has('removed_images') ? count($request->input('removed_images')) : 0;
        $imagenesNuevas = $request->hasFile('imagenes') ? count($request->file('imagenes')) : 0;
        $totalImagenesFinales = $imagenesExistentes - $imagenesAEliminar + $imagenesNuevas;

        if ($totalImagenesFinales > 5) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => [
                    'imagenes' => ['El total de imágenes no puede exceder 5. Actualmente tienes ' . $imagenesExistentes . ' imágenes, intentas agregar ' . $imagenesNuevas . ' y eliminar ' . $imagenesAEliminar . '.']
                ]
            ], 422);
        }

        // Actualizar la aerolínea
        $aerolinea->update($validated);

        // Agregar nuevas imágenes
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('aerolineas', 'public');
                    $nombreArchivo = basename($path);
                    $aerolinea->imagenes()->create([
                        'nombre' => $nombreArchivo,
                        'imageable_type' => Aerolinea::class,
                        'imageable_id' => $aerolinea->id
                    ]);
                }
            }
        }

        // Eliminar imágenes seleccionadas
        if ($request->has('removed_images')) {
            foreach ($request->input('removed_images') as $imageName) {
                $imagen = $aerolinea->imagenes()->where('nombre', $imageName)->first();
                if ($imagen) {
                    // Eliminar usando Storage Laravel
                    Storage::disk('public')->delete('aerolineas/' . $imagen->nombre);
                    $imagen->forceDelete();
                }
            }
        }

        return response()->json([
            'message' => 'Aerolínea actualizada exitosamente',
            'aerolinea' => $aerolinea->load('imagenes'),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Aerolinea $aerolinea)
    {
        // Mostrar los detalles de una aerolínea específica con sus imágenes
        return response()->json($aerolinea->load('imagenes'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $aerolinea = Aerolinea::with('imagenes')->findOrFail($id);

        foreach ($aerolinea->imagenes as $imagen) {
            // Eliminar usando Storage Laravel
            Storage::disk('public')->delete('aerolineas/' . $imagen->nombre);
            $imagen->forceDelete();
        }

        $aerolinea->delete();

        return response()->json([
            'message' => 'Aerolínea eliminada exitosamente',
        ]);
    }
}
