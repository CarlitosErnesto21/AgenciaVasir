<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HotelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Obtener todos los hoteles con sus relaciones
        $hoteles = Hotel::with(['provincia', 'imagenes', 'provincia.pais'])->get();
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
            'provincia_id' => 'required|exists:provincias,id',
            'imagenes' => 'nullable|array|max:5',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Convertir nombre y descripción a mayúsculas
        $validated['nombre'] = strtoupper($validated['nombre']);
        $validated['descripcion'] = strtoupper($validated['descripcion']);

        $hotel = Hotel::create($validated);

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('hoteles', 'public');
                    $nombreArchivo = basename($path);
                    $hotel->imagenes()->create([
                        'nombre' => $nombreArchivo,
                        'imageable_type' => Hotel::class,
                        'imageable_id' => $hotel->id
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Hotel creado exitosamente',
            'hotel' => $hotel->load(['imagenes', 'provincia.pais']),
        ]);
    }

    public function update(Request $request, Hotel $hotele)
    {
        // Asegúrate de cargar las relaciones necesarias
        $validated = $request->validate([
            'nombre' => 'required|string|max:50',
            'direccion' => 'required|string|max:200',
            'descripcion' => 'required|string|max:255',
            'provincia_id' => 'required|exists:provincias,id',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'removed_images' => 'nullable|array',
        ]);

        // Convertir nombre y descripción a mayúsculas
        $validated['nombre'] = strtoupper($validated['nombre']);
        $validated['descripcion'] = strtoupper($validated['descripcion']);

        // Validar límite total de imágenes (existentes + nuevas - eliminadas)
        $imagenesExistentes = $hotele->imagenes()->count();
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

        $hotele->update($validated);

        // Agregar nuevas imágenes
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('hoteles', 'public');
                    $nombreArchivo = basename($path);
                    $hotele->imagenes()->create([
                        'nombre' => $nombreArchivo,
                        'imageable_type' => Hotel::class,
                        'imageable_id' => $hotele->id
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
            'hotel' => $hotele->load(['imagenes', 'provincia.pais']),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Hotel $hotel)
    {
        // Mostrar los detalles de un hotel específico con sus relaciones
        $hotel->load(['provincia']);
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
        $hotel->loadMissing(['imagenes', 'provincia']);

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



    /**
     * Obtener estadísticas de un hotel
     */
    public function obtenerEstadisticas($id)
    {
        $hotel = Hotel::with(['detalleReservas.reserva'])->findOrFail($id);
        $estadisticas = $this->obtenerEstadisticasHotel($hotel);

        return response()->json([
            'success' => true,
            'estadisticas' => $estadisticas
        ]);
    }

    /**
     * Método privado para calcular estadísticas del hotel
     */
    private function obtenerEstadisticasHotel($hotel)
    {
        $reservas = $hotel->detalleReservas()
            ->with('reserva')
            ->whereHas('reserva')
            ->get();

        $totalReservas = $reservas->count();
        $reservasConfirmadas = $reservas->where('reserva.estado', 'confirmado')->count();
        $reservasPendientes = $reservas->where('reserva.estado', 'pendiente')->count();
        $reservasCanceladas = $reservas->where('reserva.estado', 'cancelado')->count();

        // Calcular ingresos totales (si existe el campo)
        $ingresosTotales = $reservas->where('reserva.estado', 'confirmado')
            ->sum('reserva.total') ?? 0;

        // Reservas por mes (últimos 6 meses)
        $reservasPorMes = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $mes = $fecha->format('Y-m');
            $nombreMes = $fecha->translatedFormat('F Y');

            $count = $reservas->filter(function ($detalle) use ($mes) {
                return $detalle->reserva &&
                       Carbon::parse($detalle->reserva->created_at)->format('Y-m') === $mes;
            })->count();

            $reservasPorMes[] = [
                'mes' => $nombreMes,
                'cantidad' => $count
            ];
        }

        return [
            'total_reservas' => $totalReservas,
            'reservas_confirmadas' => $reservasConfirmadas,
            'reservas_pendientes' => $reservasPendientes,
            'reservas_canceladas' => $reservasCanceladas,
            'ingresos_totales' => $ingresosTotales,
            'reservas_por_mes' => $reservasPorMes,
            'tasa_confirmacion' => $totalReservas > 0 ? round(($reservasConfirmadas / $totalReservas) * 100, 2) : 0
        ];
    }
}
