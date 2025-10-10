<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $productos = Producto::with(['imagenes', 'categoria'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $productos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            Log::info('=== CREANDO PRODUCTO CON INVENTARIO ===');

            $validatedData = $request->validate([
                'nombre' => 'required|string|min:3|max:100',
                'descripcion' => 'required|string|min:10|max:255',
                'precio' => 'required|numeric|min:0.01|max:999.99',
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:1',
                'categoria_id' => 'required|exists:categorias_productos,id',
                'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // ️ INICIAR TRANSACCIÓN
            DB::beginTransaction();

            // Crear producto
            $producto = Producto::create($validatedData);
            Log::info('Producto creado con ID: ' . $producto->id);

            // 📊 CREAR REGISTRO DE INVENTARIO INICIAL
            if ($validatedData['stock_actual'] > 0) {
                Log::info('Creando registro inicial de inventario...');
                Inventario::create([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad' => $validatedData['stock_actual'],
                    'motivo' => 'stock_inicial',
                    'observacion' => 'Stock inicial del producto al ser creado',
                    'user_id' => Auth::id() ?? 1, // ✅ CORREGIDO
                    'fecha_movimiento' => now(),
                ]);
                Log::info('Registro de inventario creado exitosamente');
            }

            // 🖼️ Manejar imágenes
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $nombreImagen = $imagen->store('productos', 'public');

                    $producto->imagenes()->create([
                        'nombre' => $nombreImagen,
                        'imageable_type' => Producto::class,
                        'imageable_id' => $producto->id
                    ]);
                }
            }

            // ✅ CONFIRMAR TRANSACCIÓN
            DB::commit();

            $producto->load(['imagenes', 'categoria']);

            return response()->json([
                'success' => true,
                'message' => 'Producto creado correctamente con registro de inventario',
                'data' => $producto
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $producto = Producto::with(['imagenes', 'categoria'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $producto
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $producto = Producto::findOrFail($id);
            $stockAnterior = $producto->stock_actual;

            Log::info("Actualizando producto ID {$id}. Stock anterior: {$stockAnterior}");

            $validatedData = $request->validate([
                'nombre' => 'required|string|min:3|max:100',
                'descripcion' => 'required|string|min:10|max:255',
                'precio' => 'required|numeric|min:0.01|max:999.99',
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:1',
                'categoria_id' => 'required|exists:categorias_productos,id',
                'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // 🗃️ INICIAR TRANSACCIÓN
            DB::beginTransaction();

            // Actualizar producto
            $producto->update($validatedData);

            // 📊 REGISTRAR MOVIMIENTO DE INVENTARIO SI CAMBIÓ EL STOCK
            $nuevoStock = $validatedData['stock_actual'];
            if ($stockAnterior != $nuevoStock) {
                $diferencia = $nuevoStock - $stockAnterior;
                $tipoMovimiento = $diferencia > 0 ? 'ENTRADA' : 'SALIDA';
                $cantidad = abs($diferencia);

                Log::info("Cambio de stock detectado: {$stockAnterior} -> {$nuevoStock} (diferencia: {$diferencia})");

                Inventario::create([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => $tipoMovimiento,
                    'cantidad' => $cantidad,
                    'motivo' => 'ajuste_manual',
                    'observacion' => "Ajuste manual de stock. Stock anterior: {$stockAnterior}, nuevo stock: {$nuevoStock}",
                    'user_id' => Auth::id() ?? 1, // ✅ CORREGIDO
                    'fecha_movimiento' => now(),
                ]);

                Log::info("Movimiento de inventario registrado: {$tipoMovimiento} de {$cantidad} unidades");
            }

            // 🗑️ Manejar imágenes eliminadas
            if ($request->has('removed_images')) {
                foreach ($request->removed_images as $removedImage) {
                    $imagen = $producto->imagenes()->where('nombre', $removedImage)->first();

                    if ($imagen) {
                        Storage::disk('public')->delete($imagen->nombre);
                        $imagen->delete();
                    }
                }
            }

            // 🖼️ Manejar nuevas imágenes
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $nombreImagen = $imagen->store('productos', 'public');

                    $producto->imagenes()->create([
                        'nombre' => $nombreImagen,
                        'imageable_type' => Producto::class,
                        'imageable_id' => $producto->id
                    ]);
                }
            }

            // ✅ CONFIRMAR TRANSACCIÓN
            DB::commit();

            $producto->load(['imagenes', 'categoria']);

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente',
                'data' => $producto
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $producto = Producto::findOrFail($id);

            // 🔍 Verificar si el producto está siendo usado
            $verificacion = $this->verificarProductoEnUso($producto);

            if ($verificacion['usado']) {
                return response()->json([
                    'message' => 'No se puede eliminar el producto',
                    'error' => $verificacion['razon'],
                    'details' => $verificacion['detalles']
                ], 422);
            }

            // 🗃️ INICIAR TRANSACCIÓN
            DB::beginTransaction();

            // 📊 REGISTRAR MOVIMIENTO FINAL DE INVENTARIO
            if ($producto->stock_actual > 0) {
                Log::info("Registrando salida final de inventario para producto {$producto->id}");
                Inventario::create([
                    'producto_id' => $producto->id,
                    'tipo_movimiento' => 'SALIDA',
                    'cantidad' => $producto->stock_actual,
                    'motivo' => 'eliminacion_producto',
                    'observacion' => "Salida por eliminación del producto del sistema. Stock eliminado: {$producto->stock_actual}",
                    'user_id' => Auth::id() ?? 1, // ✅ CORREGIDO
                    'fecha_movimiento' => now()
                ]);
            }

            // 🗑️ Eliminar imágenes del storage
            foreach ($producto->imagenes as $imagen) {
                Storage::disk('public')->delete($imagen->nombre);
                $imagen->delete();
            }

            // 🗑️ Eliminar el producto
            $producto->delete();

            // ✅ CONFIRMAR TRANSACCIÓN
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado correctamente'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
                'error' => 'El producto que intentas eliminar no existe'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar producto: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto',
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔍 Verificar si el producto está siendo usado en otras tablas
     */
    private function verificarProductoEnUso($producto)
    {
        $restricciones = [];

        // 📦 Verificar si está en detalle_ventas
        try {
            $ventasCount = DB::table('detalle_ventas')
                ->where('producto_id', $producto->id)
                ->count();

            if ($ventasCount > 0) {
                $restricciones[] = "Ha sido vendido {$ventasCount} vez(es)";
            }
        } catch (Exception $e) {
            // La tabla no existe aún, continuar
        }

        // 📦 Verificar si tiene movimientos de inventario importantes
        try {
            $movimientosCount = DB::table('inventarios')
                ->where('producto_id', $producto->id)
                ->where('motivo', '!=', 'eliminacion_producto')
                ->count();

            if ($movimientosCount > 0) {
                $restricciones[] = "Tiene {$movimientosCount} movimiento(s) de inventario";
            }
        } catch (Exception $e) {
            // Continuar
        }

        if (!empty($restricciones)) {
            return [
                'usado' => true,
                'razon' => "El producto '{$producto->nombre}' no puede ser eliminado porque:",
                'detalles' => $restricciones
            ];
        }

        return ['usado' => false];
    }
}
