<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hotel;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class HotelWebController extends Controller
{
    /**
     * Mostrar el detalle de un hotel específico para clientes
     */
    public function mostrarDetalleHotel(Request $request, $id)
    {
        try {
            // Obtener el hotel con sus relaciones
            $hotel = Hotel::with(['categoriaHotel', 'provincia', 'provincia.pais', 'imagenes'])
                ->where('id', $id)
                ->firstOrFail();

            // Estructurar el hotel para la vista
            $hotelDetalle = [
                'id' => $hotel->id,
                'nombre' => $hotel->nombre,
                'descripcion' => $hotel->descripcion,
                'direccion' => $hotel->direccion,
                'telefono' => $hotel->telefono,
                'email' => $hotel->email,
                'estado' => $hotel->estado,
                'categoria_hotel' => $hotel->categoriaHotel ? [
                    'id' => $hotel->categoriaHotel->id,
                    'nombre' => $hotel->categoriaHotel->nombre
                ] : null,
                'provincia' => $hotel->provincia ? [
                    'id' => $hotel->provincia->id,
                    'nombre' => $hotel->provincia->nombre
                ] : null,
                'pais' => $hotel->provincia && $hotel->provincia->pais ? [
                    'id' => $hotel->provincia->pais->id,
                    'nombre' => $hotel->provincia->pais->nombre
                ] : null,
                'imagenes' => $hotel->imagenes->map(function ($imagen) {
                    return [
                        'id' => $imagen->id,
                        'nombre' => $imagen->nombre,
                        'url' => '/storage/hoteles/' . $imagen->nombre
                    ];
                })
            ];

            return Inertia::render('VistasClientes/DetalleHotel', [
                'hotel' => $hotelDetalle
            ]);

        } catch (\Exception $e) {
            // Si el hotel no existe, redirigir a la lista de hoteles
            Log::error('HotelWebController: Error al obtener hotel', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            return redirect()->route('hoteles-clientes')->with('error', 'Hotel no encontrado');
        }
    }
}
