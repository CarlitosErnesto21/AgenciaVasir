<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paquete;

use App\Models\Imagen;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PaqueteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Paquete::with(['imagenes', 'pais', 'provincia'])
            ->orderBy('fecha_salida', 'asc');

        // Filtrar por país si se proporciona
        if ($request->has('pais_id')) {
            $query->where('pais_id', $request->input('pais_id'));
        }

        $paquetes = $query->get();

        return response()->json($paquetes);
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
            'nombre' => 'required|string|max:200',
            'incluye' => 'nullable|string',
            'condiciones' => 'required|string',
            'recordatorio' => 'required|string',
            // aceptar datetimes en el futuro (now) en lugar de after:today para permitir mismo día con hora posterior
            'fecha_salida' => 'required|date|after:now',
            'fecha_regreso' => 'required|date|after:fecha_salida',
            'precio' => 'required|numeric|min:0|max:999999.99',
            'pais_id' => 'required|exists:paises,id',
            'provincia_id' => 'required|exists:provincias,id',
            'imagenes' => 'nullable|array|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $paqueteData = $validated;
        unset($paqueteData['imagenes']);

        $paquete = Paquete::create($paqueteData);

        // Guardar imágenes nuevas usando Storage::disk('public')
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    $path = $imagen->store('paquetes', 'public');
                    $fileName = basename($path);

                    if (empty($path)) {
                        Log::error('ERROR: No se pudo guardar la imagen del paquete');
                        continue;
                    }

                    $paquete->imagenes()->create([
                        'nombre' => $fileName,
                        'imageable_type' => Paquete::class,
                        'imageable_id' => $paquete->id
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Paquete creado exitosamente',
            'paquete' => $paquete->load(['imagenes', 'pais', 'provincia']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $paquete = Paquete::with(['imagenes', 'pais', 'provincia'])->findOrFail($id);

        return response()->json($paquete);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $paquete = Paquete::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'incluye' => 'nullable|string',
            'condiciones' => 'required|string',
            'recordatorio' => 'required|string',
            // validar fechas también al actualizar: fecha_salida en el futuro y fecha_regreso posterior a salida
            'fecha_salida' => 'required|date|after:now',
            'fecha_regreso' => 'required|date|after:fecha_salida',
            'precio' => 'required|numeric|min:0|max:999999.99',
            'pais_id' => 'required|exists:paises,id',
            'provincia_id' => 'required|exists:provincias,id',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'removed_images' => 'nullable|array',
            'removed_images.*' => 'string',
        ]);

        // Validar límite total de imágenes (existentes + nuevas - eliminadas)
        $imagenesExistentes = $paquete->imagenes()->count();
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

        $paqueteData = $validated;
        unset($paqueteData['imagenes']);
        unset($paqueteData['removed_images']);

        $paquete->update($paqueteData);

        // Guardar imágenes nuevas
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    $path = $imagen->store('paquetes', 'public');
                    $fileName = basename($path);

                    if (empty($path)) {
                        Log::error('ERROR: No se pudo guardar la imagen del paquete (update)');
                        continue;
                    }

                    $paquete->imagenes()->create([
                        'nombre' => $fileName,
                        'imageable_type' => Paquete::class,
                        'imageable_id' => $paquete->id
                    ]);
                }
            }
        }

        // Eliminar imágenes seleccionadas
        if ($request->has('removed_images')) {
            foreach ($request->input('removed_images') as $imageName) {
                $imagen = $paquete->imagenes()->where('nombre', $imageName)->first();
                if ($imagen) {
                    Storage::disk('public')->delete('paquetes/' . $imagen->nombre);
                    $imagen->forceDelete();
                }
            }
        }

        return response()->json([
            'message' => 'Paquete actualizado exitosamente',
            'paquete' => $paquete->load(['imagenes', 'pais', 'provincia']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $paquete = Paquete::findOrFail($id);
        $paquete->loadMissing(['imagenes', 'pais', 'provincia']);

        foreach ($paquete->imagenes as $imagen) {
            Storage::disk('public')->delete('paquetes/' . $imagen->nombre);
            $imagen->forceDelete();
        }

        $paquete->delete();

        return response()->json([
            'message' => 'Paquete eliminado exitosamente',
        ]);
    }
}
