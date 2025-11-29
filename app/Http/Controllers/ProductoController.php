<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
                'nombre' => [
                    'required',
                    'string',
                    'min:3',
                    'max:200',
                    'regex:/^[A-ZÁÉÍÓÚÑÜ\s]+$/',
                    'unique:productos,nombre'
                ],
                'descripcion' => [
                    'required',
                    'string',
                    'min:10',
                    'max:400',
                    'regex:/^[A-ZÁÉÍÓÚÑÜ0-9\s.,;:!?¡¿()\/\-_@#$%&*+=\[\]{}|\\~`"\']+$/'
                ],
                'precio' => 'required|numeric|min:0.01|max:9999.99',
                'stock_actual' => 'required|integer|min:0',
                'stock_minimo' => 'required|integer|min:1',
                'categoria_id' => 'required|exists:categorias_productos,id',
                'imagenes' => 'nullable|array|max:5',
                'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ], [
                'nombre.regex' => 'El nombre solo puede contener letras mayúsculas, acentos y espacios.',
                'nombre.unique' => 'Ya existe un producto con este nombre.',
                'nombre.max' => 'El nombre no puede exceder 200 caracteres.',
                'descripcion.max' => 'La descripción no puede exceder 400 caracteres.',
                'descripcion.regex' => 'La descripción solo puede contener letras mayúsculas, números, acentos, espacios y caracteres especiales comunes.'
            ]);

            // Convertir nombre y descripción a mayúsculas
            $validatedData['nombre'] = strtoupper($validatedData['nombre']);
            $validatedData['descripcion'] = strtoupper($validatedData['descripcion']);

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

            // 🖼️ Manejar imágenes usando Storage persistente
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('productos', 'public');
                    $nombreImagen = basename($path);

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

            Log::info("Actualizando producto ID {$id}");

            $validatedData = $request->validate([
                'nombre' => [
                    'required',
                    'string',
                    'min:3',
                    'max:200',
                    'regex:/^[A-ZÁÉÍÓÚÑÜ\s]+$/',
                    'unique:productos,nombre,' . $id
                ],
                'descripcion' => [
                    'required',
                    'string',
                    'min:10',
                    'max:400',
                    'regex:/^[A-ZÁÉÍÓÚÑÜ0-9\s.,;:!?¡¿()\/\-_@#$%&*+=\[\]{}|\\~`"\']+$/'
                ],
                'precio' => 'required|numeric|min:0.01|max:9999.99',
                'categoria_id' => 'required|exists:categorias_productos,id',
                'imagenes' => 'nullable|array|max:5',
                'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
                // Nota: Se removieron stock_actual y stock_minimo para actualizaciones
                // El stock debe actualizarse a través del endpoint específico /actualizar-stock
            ], [
                'nombre.regex' => 'El nombre solo puede contener letras mayúsculas, acentos y espacios.',
                'nombre.unique' => 'Ya existe un producto con este nombre.',
                'nombre.max' => 'El nombre no puede exceder 200 caracteres.',
                'descripcion.max' => 'La descripción no puede exceder 400 caracteres.',
                'descripcion.regex' => 'La descripción solo puede contener letras mayúsculas, números, acentos, espacios y caracteres especiales comunes.'
            ]);

            // Convertir nombre y descripción a mayúsculas
            $validatedData['nombre'] = strtoupper($validatedData['nombre']);
            $validatedData['descripcion'] = strtoupper($validatedData['descripcion']);

            // Validar límite total de imágenes (existentes + nuevas - eliminadas)
            $imagenesExistentes = $producto->imagenes()->count();
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

            // 🗃️ INICIAR TRANSACCIÓN
            DB::beginTransaction();

            // Actualizar producto (sin campos de stock)
            $producto->update($validatedData);

            // 🗑️ Manejar imágenes eliminadas
            if ($request->has('removed_images')) {
                foreach ($request->removed_images as $removedImage) {
                    $imagen = $producto->imagenes()->where('nombre', $removedImage)->first();

                    if ($imagen) {
                        // Eliminar usando Storage Laravel
                        Storage::disk('public')->delete('productos/' . $imagen->nombre);
                        $imagen->delete();
                    }
                }
            }

            // 🖼️ Manejar nuevas imágenes usando Storage persistente
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    // Usar Storage::disk('public') que es persistente en Render
                    $path = $imagen->store('productos', 'public');
                    $nombreImagen = basename($path);

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

            // 🗑️ Eliminar imágenes del storage usando Storage Laravel
            foreach ($producto->imagenes as $imagen) {
                Storage::disk('public')->delete('productos/' . $imagen->nombre);
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
     *
     * REGLAS DE NEGOCIO:
     * ❌ NO permitir eliminar si tiene registros en detalle_ventas
     * ❌ NO permitir eliminar si tiene movimientos de inventario
     */
    private function verificarProductoEnUso($producto)
    {
        $restricciones = [];

        // Verificar ventas
        try {
            $ventasCount = DB::table('detalles_ventas')
                ->where('producto_id', $producto->id)
                ->count();

            if ($ventasCount > 0) {
                $ventasDetalladas = DB::table('detalles_ventas')
                    ->join('ventas', 'detalles_ventas.venta_id', '=', 'ventas.id')
                    ->where('detalles_ventas.producto_id', $producto->id)
                    ->select('ventas.id as venta_id', 'ventas.estado', 'detalles_ventas.cantidad', 'ventas.created_at')
                    ->get();

                $totalVentas = $ventasDetalladas->unique('venta_id')->count();
                $totalVendido = $ventasDetalladas->sum('cantidad');

                $restricciones[] = "Tiene {$totalVentas} venta(s) registrada(s) ({$totalVendido} unidades total)";
            }
        } catch (Exception $e) {
            // Continúa sin agregar restricción por error técnico
        }

        // Verificar inventarios
        try {
            $inventarioCount = DB::table('inventarios')
                ->where('producto_id', $producto->id)
                ->count();

            if ($inventarioCount > 0) {
                $restricciones[] = "Tiene {$inventarioCount} movimiento(s) de inventario";
            }
        } catch (Exception $e) {
            // Continúa sin agregar restricción por error técnico
        }

        // Decisión final
        if (!empty($restricciones)) {
            return [
                'usado' => true,
                'razon' => "El producto '{$producto->nombre}' no puede ser eliminado porque:",
                'detalles' => $restricciones
            ];
        }

        return ['usado' => false];
    }

    /**
     * 📦 Actualizar stock del producto con trazabilidad completa
     */
    public function actualizarStock(Request $request, $id)
    {
        try {
            $producto = Producto::findOrFail($id);

            Log::info("=== ACTUALIZANDO STOCK PRODUCTO ID {$id} ===");
            Log::info("Stock actual: {$producto->stock_actual}");

            $validatedData = $request->validate([
                'tipo_movimiento' => 'required|in:entrada,salida,ajuste',
                'cantidad' => 'required|integer|min:1|max:9999',
                'motivo' => 'required|string|min:3|max:100',
                'stock_resultante' => 'required|integer|min:0|max:9999',
                'nuevo_stock_minimo' => 'nullable|integer|min:1|max:100'
            ]);

            // 🔍 Validaciones específicas según tipo de movimiento
            $stockAnterior = $producto->stock_actual;
            $cantidad = $validatedData['cantidad'];
            $tipoMovimiento = strtoupper($validatedData['tipo_movimiento']);

            // Validar coherencia del stock resultante
            $stockEsperado = $stockAnterior;
            switch ($validatedData['tipo_movimiento']) {
                case 'entrada':
                    $stockEsperado = $stockAnterior + $cantidad;
                    break;
                case 'salida':
                    $stockEsperado = max(0, $stockAnterior - $cantidad);
                    // Validar que no se retire más stock del disponible
                    if ($cantidad > $stockAnterior) {
                        return response()->json([
                            'message' => 'Error de validación',
                            'errors' => [
                                'cantidad' => ["No puedes retirar {$cantidad} unidades. Stock disponible: {$stockAnterior}"]
                            ]
                        ], 422);
                    }
                    break;
                case 'ajuste':
                    $stockEsperado = $cantidad;
                    break;
            }

            if ($stockEsperado != $validatedData['stock_resultante']) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => [
                        'stock_resultante' => ['El stock resultante calculado no coincide con el esperado']
                    ]
                ], 422);
            }

            // 🗃️ INICIAR TRANSACCIÓN
            DB::beginTransaction();

            // 📊 REGISTRAR MOVIMIENTO DE INVENTARIO
            $inventario = Inventario::create([
                'producto_id' => $producto->id,
                'tipo_movimiento' => $tipoMovimiento,
                'cantidad' => $cantidad,
                'motivo' => $validatedData['motivo'],
                'observacion' => $this->generarObservacionMovimiento(
                    $validatedData['tipo_movimiento'],
                    $stockAnterior,
                    $validatedData['stock_resultante'],
                    $validatedData['motivo']
                ),
                'user_id' => Auth::id(),
                'fecha_movimiento' => now(),
                'venta_id' => null // Para movimientos manuales
            ]);

            Log::info("Movimiento de inventario creado con ID: {$inventario->id}");

            // 🔄 ACTUALIZAR STOCK DEL PRODUCTO
            $datosActualizacion = ['stock_actual' => $validatedData['stock_resultante']];

            // Actualizar stock mínimo si se proporcionó
            if (isset($validatedData['nuevo_stock_minimo'])) {
                $datosActualizacion['stock_minimo'] = $validatedData['nuevo_stock_minimo'];
            }

            $producto->update($datosActualizacion);

            Log::info("Stock actualizado: {$stockAnterior} -> {$validatedData['stock_resultante']}");

            // ✅ CONFIRMAR TRANSACCIÓN
            DB::commit();

            // Recargar producto con relaciones para respuesta
            $producto->load(['categoria', 'imagenes']);

            return response()->json([
                'success' => true,
                'message' => 'Stock actualizado correctamente',
                'data' => [
                    'producto' => $producto,
                    'movimiento' => [
                        'id' => $inventario->id,
                        'tipo' => $tipoMovimiento,
                        'cantidad' => $cantidad,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $validatedData['stock_resultante'],
                        'motivo' => $validatedData['motivo'],
                        'fecha' => $inventario->fecha_movimiento->format('Y-m-d H:i:s'),
                        'usuario' => Auth::user()->name ?? 'Sistema'
                    ]
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
                'error' => 'El producto especificado no existe'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el stock',
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * 📝 Generar observación detallada para el movimiento
     */
    private function generarObservacionMovimiento($tipo, $stockAnterior, $stockNuevo, $motivo)
    {
        $usuario = Auth::user()->name ?? 'Sistema';
        $fecha = now()->format('d/m/Y H:i');

        switch ($tipo) {
            case 'entrada':
                $cantidad = $stockNuevo - $stockAnterior;
                return "Entrada de {$cantidad} unidades. Stock: {$stockAnterior} → {$stockNuevo}.";

            case 'salida':
                $cantidad = $stockAnterior - $stockNuevo;
                return "Salida de {$cantidad} unidades. Stock: {$stockAnterior} → {$stockNuevo}.";

            case 'ajuste':
                $diferencia = $stockNuevo - $stockAnterior;
                $tipoDiferencia = $diferencia > 0 ? 'aumento' : ($diferencia < 0 ? 'reducción' : 'sin cambio');
                return "Ajuste de inventario ({$tipoDiferencia}). Stock: {$stockAnterior} → {$stockNuevo}.";

            default:
                return "Movimiento de stock. Stock: {$stockAnterior} → {$stockNuevo}.";
        }
    }

    /**
     * Obtener productos disponibles (con stock > stock_minimo)
     */
    public function disponibles()
    {
        $productos = Producto::with('categoria')
            ->whereColumn('stock_actual', '>', 'stock_minimo')
            ->orderBy('nombre')
            ->get();

        return response()->json($productos);
    }
}
