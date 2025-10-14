<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HotelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtener todos los hoteles con sus relaciones
        $hoteles = Hotel::with(['provincia', 'categoriaHotel', 'imagenes', 'provincia.pais'])->get();
        return response()->json($hoteles);
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
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'direccion' => 'required|string|max:200',
            'descripcion' => 'required|string|max:255',
            'estado' => 'required|string|max:20',
            'provincia_id' => 'required|exists:provincias,id',
            'categoria_id' => 'required|exists:categorias_hoteles,id',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|max:2048',
        ]);

        $hotel = Hotel::create($validated);

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('hoteles', 'public');
                    $nombreArchivo = basename($path);
                    $hotel->imagenes()->create([
                        'nombre' => $nombreArchivo
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Hotel creado exitosamente',
            'hotel' => $hotel->load(['imagenes', 'categoriaHotel', 'provincia.pais']),
        ]);
    }

    public function update(Request $request, Hotel $hotele)
    {
        // Asegúrate de cargar las relaciones necesarias
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'direccion' => 'required|string|max:200',
            'descripcion' => 'required|string|max:500',
            'estado' => 'required|string|max:20',
            'provincia_id' => 'required|exists:provincias,id',
            'categoria_id' => 'required|exists:categorias_hoteles,id',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|max:2048',
        ]);

        $hotele->update($validated);

        // Agregar nuevas imágenes
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('hoteles', 'public');
                    $nombreArchivo = basename($path);
                    $hotele->imagenes()->create([
                        'nombre' => $nombreArchivo
                    ]);
                }
            }
        }

        // Eliminar imágenes seleccionadas
        if ($request->has('removed_images')) {
            foreach ($request->input('removed_images') as $imageName) {
                $imagen = $hotele->imagenes()->where('nombre', $imageName)->first();
                if ($imagen) {
                    // Eliminar usando Storage Laravel
                    Storage::disk('public')->delete('hoteles/' . $imagen->nombre);
                    $imagen->forceDelete(); // Cambiado de delete() a forceDelete()
                }
            }
        }

        return response()->json([
            'message' => 'Hotel actualizado exitosamente',
            'hotel' => $hotele->load(['imagenes', 'categoriaHotel', 'provincia.pais']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Hotel $hotel)
    {
        // Mostrar los detalles de un hotel específico con sus relaciones
        $hotel->load(['provincia', 'categoriaHotel']);
        return response()->json($hotel);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hotel $hotel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->loadMissing(['imagenes', 'provincia', 'categoriaHotel']);

        foreach ($hotel->imagenes as $imagen) {
            // Eliminar usando Storage Laravel
            Storage::disk('public')->delete('hoteles/' . $imagen->nombre);
            $imagen->forceDelete();
        }

        $hotel->delete();

        return response()->json([
            'message' => 'Hotel eliminado exitosamente',
        ]);
    }
}
