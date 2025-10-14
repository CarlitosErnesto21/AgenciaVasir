<?php

namespace App\Http\Controllers;

use App\Models\CategoriaProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class CategoriaProductoController extends Controller
{
    public function index()
    {
        try {
            $categorias = CategoriaProducto::orderBy('created_at', 'desc')->get();
            return response()->json([
                'success' => true,
                'data' => $categorias
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|min:3|max:50|unique:categorias_productos,nombre',
            ]);

            $categoria = CategoriaProducto::create($validated);

            return response()->json([
                'message' => 'Categoría de producto creada exitosamente',
                'categoria' => $categoria,
                'success' => true
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(CategoriaProducto $categoriaProducto)
    {
        return response()->json($categoriaProducto);
    }

    public function update(Request $request, $id)
    {
        try {
            $categoriaProducto = CategoriaProducto::findOrFail($id);

            $validated = $request->validate([
                'nombre' => 'required|string|min:3|max:50|unique:categorias_productos,nombre,' . $id,
            ]);

            $categoriaProducto->nombre = $validated['nombre'];
            $categoriaProducto->save();

            return response()->json([
                'message' => 'Categoría de producto actualizada exitosamente',
                'categoria' => $categoriaProducto->fresh(),
                'success' => true
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'error' => 'La categoría que intentas actualizar no existe'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $categoriaProducto = CategoriaProducto::findOrFail($id);

            // 🔍 Verificar si la categoría tiene productos asociados
            $productosCount = DB::table('productos')
                ->where('categoria_id', $categoriaProducto->id)
                ->count();

            if ($productosCount > 0) {
                return response()->json([
                    'message' => 'No se puede eliminar la categoría',
                    'error' => "La categoría '{$categoriaProducto->nombre}' no puede ser eliminada porque tiene {$productosCount} producto(s) asociado(s).",
                    'details' => ["Tiene {$productosCount} producto(s) asociado(s)"]
                ], 422);
            }

            // 🗑️ Si no tiene productos, eliminar la categoría
            $categoriaProducto->delete();

            return response()->json([
                'message' => 'Categoría de producto eliminada exitosamente',
                'success' => true
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
                'error' => 'La categoría que intentas eliminar no existe'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría',
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }
}
