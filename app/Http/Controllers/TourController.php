<?php

namespace App\Http\Controllers;

use App\Models\Tour;
use App\Models\Transporte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     * Soporta filtrado por categoría: ?categoria=nacional|internacional
     */
    public function index(Request $request)
    {
        $query = Tour::with(['transporte', 'imagenes'])
            ->orderBy('fecha_salida', 'asc');

        // Filtrar por categoría si se especifica
        if ($request->has('categoria')) {
            $categoria = strtoupper($request->input('categoria'));

            if (in_array($categoria, ['NACIONAL', 'INTERNACIONAL'])) {
                $query->where('categoria', $categoria);
            }
        }

        $tours = $query->get();

        // Agregar cupos_disponibles a cada tour
        $tours->each(function ($tour) {
            $cuposReservados = $tour->detalleReservas()
                ->whereHas('reserva', function($query) {
                    $query->where('estado', '!=', 'cancelada');
                })
                ->sum('cupos_reservados');

            $cuposDisponibles = max(0, $tour->cupo_max - $cuposReservados);

            $tour->cupos_disponibles = $cuposDisponibles;
        });

        // Siempre devolver JSON para API
        return response()->json($tours);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'cupo_min' => 'required|integer|min:1',
            'cupo_max' => 'required|integer|min:1',
            'punto_salida' => 'required|string|max:255',
            'fecha_salida' => 'required|date|after:today',
            'fecha_regreso' => 'required|date|after:fecha_salida',
            'precio' => 'required|numeric|min:0|max:9999.99',
            'categoria' => 'required|in:NACIONAL,INTERNACIONAL',
            'transporte_id' => 'required|exists:transportes,id',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|max:2048',
        ]);

        // Preparar datos para crear el tour
        $tourData = $validated;
        unset($tourData['imagenes']); // Remover imagenes del array principal

        // Crear tour
        $tour = Tour::create($tourData);

        // Guardar imágenes nuevas usando Storage Laravel (persistente)
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('tours', 'public');
                    $nombreArchivo = basename($path);

                    if (empty($path)) {
                        Log::error('ERROR: No se pudo guardar la imagen');
                        continue;
                    }

                    $tour->imagenes()->create(['nombre' => $nombreArchivo]);
                }
            }
        }

        return response()->json([
            'message' => 'Tour creado exitosamente',
            'tour' => $tour->load(['imagenes', 'transporte']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $tour = Tour::with(['transporte', 'imagenes'])
            ->findOrFail($id);

        // Agregar cupos_disponibles
        $cuposReservados = $tour->detalleReservas()
            ->whereHas('reserva', function($query) {
                $query->where('estado', '!=', 'cancelada');
            })
            ->sum('cupos_reservados');

        $cuposDisponibles = max(0, $tour->cupo_max - $cuposReservados);

        $tour->cupos_disponibles = $cuposDisponibles;

        return response()->json($tour);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tour $tour)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:200',
            'incluye' => 'nullable|string',
            'no_incluye' => 'nullable|string',
            'cupo_min' => 'nullable|integer|min:1',
            'cupo_max' => 'nullable|integer|min:1',
            'punto_salida' => 'required|string|max:200',
            'fecha_salida' => 'required|date',
            'fecha_regreso' => 'required|date',
            'precio' => 'required|numeric|min:0|max:9999.99',
            'categoria' => 'required|in:NACIONAL,INTERNACIONAL',
            'transporte_id' => 'required|exists:transportes,id',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|max:2048',
            'removed_images' => 'nullable|array',
        ]);

        // Preparar datos para actualizar el tour
        $tourData = $validated;
        unset($tourData['imagenes']); // Remover imagenes del array principal
        unset($tourData['removed_images']); // Remover removed_images del array principal

        // Actualizar tour
        $tour->update($tourData);

        // Guardar imágenes nuevas
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('tours', 'public');
                    $nombreArchivo = basename($path);

                    if (empty($path)) {
                        Log::error('ERROR: No se pudo actualizar la imagen');
                        continue;
                    }

                    $tour->imagenes()->create(['nombre' => $nombreArchivo]);
                }
            }
        }

        // Eliminar imágenes seleccionadas
        if ($request->has('removed_images')) {
            foreach ($request->input('removed_images') as $imageName) {
                $imagen = $tour->imagenes()->where('nombre', $imageName)->first();
                if ($imagen) {
                    // Eliminar usando Storage Laravel
                    Storage::disk('public')->delete('tours/' . $imagen->nombre);
                    $imagen->forceDelete();
                }
            }
        }

        return response()->json([
            'message' => 'Tour actualizado exitosamente',
            'tour' => $tour->load(['imagenes', 'transporte']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tour = Tour::findOrFail($id);
        $tour->loadMissing(['imagenes', 'transporte']);

        // Eliminar imágenes físicas y registros usando Storage Laravel
        foreach ($tour->imagenes as $imagen) {
            Storage::disk('public')->delete('tours/' . $imagen->nombre);
            $imagen->forceDelete();
        }

        $tour->delete();

        return response()->json([
            'message' => 'Tour eliminado exitosamente',
        ]);
    }

    /**
     * Mostrar tours nacionales para la vista de clientes
     */
    public function toursNacionales(Request $request)
    {
        // Obtener tours nacionales directamente
        $tours = Tour::with(['transporte', 'imagenes'])
            ->where('fecha_salida', '>=', now())
            ->where('categoria', 'NACIONAL')
            ->orderBy('fecha_salida', 'asc')
            ->get();

        // Siempre devolver vista Inertia para rutas web
        return inertia('VistasClientes/ToursNacionales', [
            'tours' => $tours
        ]);
    }

    /**
     * Mostrar tours internacionales para la vista de clientes
     */
    public function toursInternacionales(Request $request)
    {
        // Obtener tours internacionales directamente
        $tours = Tour::with(['transporte', 'imagenes'])
            ->where('fecha_salida', '>=', now())
            ->where('categoria', 'INTERNACIONAL')
            ->orderBy('fecha_salida', 'asc')
            ->get();

        // Siempre devolver vista Inertia para rutas web
        return inertia('VistasClientes/ToursInternacionales', [
            'tours' => $tours
        ]);
    }

    /**
     * Mostrar vista detallada de un tour nacional
     */
    public function mostrarTourNacional($id)
    {
        $tour = Tour::with(['transporte', 'imagenes'])
            ->where('id', $id)
            ->where('categoria', 'NACIONAL')
            ->firstOrFail();

        return inertia('VistasClientes/DetalleTour', [
            'tour' => $tour,
            'tipo' => 'nacional'
        ]);
    }

    /**
     * Mostrar vista detallada de un tour internacional
     */
    public function mostrarTourInternacional($id)
    {
        $tour = Tour::with(['transporte', 'imagenes'])
            ->where('id', $id)
            ->where('categoria', 'INTERNACIONAL')
            ->firstOrFail();

        return inertia('VistasClientes/DetalleTour', [
            'tour' => $tour,
            'tipo' => 'internacional'
        ]);
    }
}
