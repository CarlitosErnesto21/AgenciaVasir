<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ProductoWebController extends Controller
{
    /**
     * Mostrar el detalle de un producto específico para clientes
     */
    public function mostrarDetalleProducto(Request $request, $id)
    {
        try {
            // Debug: Log del ID recibido
            Log::info('ProductoWebController: Buscando producto con ID: ' . $id);

            // Obtener el producto con sus relaciones (sin filtro de stock para debugging)
            $producto = Producto::with(['categoria', 'imagenes'])
                ->where('id', $id)
                ->firstOrFail();

            // Debug: Log antes de enviar a Inertia
            Log::info('ProductoWebController: Enviando data a Inertia', [
                'producto' => [
                    'id' => $data['producto']['id'] ?? 'null',
                    'nombre' => $data['producto']['nombre'] ?? 'null',
                    'stock_actual' => $data['producto']['stock_actual'] ?? 'null',
                ]
            ]);            // Estructurar el producto para la vista
            $productoDetalle = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio' => (float) $producto->precio,
                'stock_actual' => $producto->stock_actual,
                'categoria' => $producto->categoria ? $producto->categoria->nombre : 'Sin categoría',
                'categoria_id' => $producto->categoria_id,
                'imagenes' => $producto->imagenes->map(function ($imagen) {
                    return [
                        'id' => $imagen->id,
                        'nombre' => $imagen->nombre,
                        'url' => '/storage/productos/' . $imagen->nombre
                    ];
                })
            ];

            return Inertia::render('VistasClientes/DetalleProducto', [
                'producto' => $productoDetalle
            ]);

        } catch (\Exception $e) {
            // Si el producto no existe o no está disponible, redirigir a la tienda
            return redirect()->route('tienda')->with('error', 'Producto no encontrado o no disponible');
        }
    }
}
