<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class VentaController extends Controller
{
    protected $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // ✅ CORREGIDO: detalleVentas (plural) y sin reserva (según tu migración)
        $ventas = Venta::with(['cliente', 'detalleVentas.producto.categoria'])
            ->orderBy('fecha', 'desc')
            ->get();
        return response()->json($ventas);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Obtener datos necesarios para crear venta
        $clientes = Cliente::select('id', 'nombre', 'telefono')->orderBy('nombre')->get();
        $productos = Producto::with('categoria')
            ->where('stock_actual', '>', 0)
            ->select('id', 'nombre', 'precio', 'stock_actual', 'categoria_id')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'clientes' => $clientes,
            'productos' => $productos
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha' => 'required|date',
            'cliente_id' => 'required|exists:clientes,id',
            // ✅ CORREGIDO: productos como array (no total directo)
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        try {
            $venta = DB::transaction(function () use ($validated) {
                // ✅ CORREGIDO: estado inicial pendiente, sin reserva_id
                $venta = Venta::create([
                    'fecha' => $validated['fecha'],
                    'cliente_id' => $validated['cliente_id'],
                    'estado' => 'pendiente',  // ✅ Estado inicial
                    'total' => 0
                ]);

                $total = 0;

                // Crear detalles de venta
                foreach ($validated['productos'] as $productoData) {
                    $subtotal = $productoData['cantidad'] * $productoData['precio_unitario'];

                    // ✅ CORREGIDO: usar detalleVentas
                    $venta->detalleVentas()->create([
                        'producto_id' => $productoData['producto_id'],
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio_unitario'],
                        'subtotal' => $subtotal
                    ]);

                    $total += $subtotal;
                }

                // Actualizar total de la venta
                $venta->update(['total' => $total]);

                return $venta;
            });

            return response()->json([
                'message' => 'Venta creada exitosamente',
                'venta' => $venta->load(['cliente', 'detalleVentas.producto']),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear venta: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        // ✅ CORREGIDO: detalleVentas y sin reserva
        $venta->load(['cliente', 'detalleVentas.producto.categoria']);
        return response()->json($venta);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venta $venta)
    {
        // Solo permitir editar ventas pendientes
        if ($venta->estado !== 'pendiente') {
            return response()->json([
                'error' => 'Solo se pueden editar ventas pendientes'
            ], 422);
        }

        $venta->load(['cliente', 'detalleVentas.producto']);

        $clientes = Cliente::select('id', 'nombre')->orderBy('nombre')->get();
        $productos = Producto::with('categoria')
            ->where('stock_actual', '>', 0)
            ->select('id', 'nombre', 'precio', 'stock_actual', 'categoria_id')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'venta' => $venta,
            'clientes' => $clientes,
            'productos' => $productos
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Venta $venta)
    {
        // Solo permitir actualizar ventas pendientes
        if ($venta->estado !== 'pendiente') {
            return response()->json([
                'error' => 'Solo se pueden actualizar ventas pendientes'
            ], 422);
        }

        $validated = $request->validate([
            'fecha' => 'required|date',
            'cliente_id' => 'required|exists:clientes,id',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($validated, $venta) {
                // Actualizar datos de la venta
                $venta->update([
                    'fecha' => $validated['fecha'],
                    'cliente_id' => $validated['cliente_id'],
                ]);

                // Eliminar detalles existentes
                $venta->detalleVentas()->delete();

                $total = 0;

                // Crear nuevos detalles
                foreach ($validated['productos'] as $productoData) {
                    $subtotal = $productoData['cantidad'] * $productoData['precio_unitario'];

                    $venta->detalleVentas()->create([
                        'producto_id' => $productoData['producto_id'],
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio_unitario'],
                        'subtotal' => $subtotal
                    ]);

                    $total += $subtotal;
                }

                // Actualizar total
                $venta->update(['total' => $total]);
            });

            return response()->json([
                'message' => 'Venta actualizada exitosamente',
                'venta' => $venta->fresh()->load(['cliente', 'detalleVentas.producto']),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar venta: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Procesar venta (reduce stock automáticamente)
     */
    public function procesar(Venta $venta)
    {
        try {
            $this->inventarioService->procesarVenta($venta);

            return response()->json([
                'message' => 'Venta procesada exitosamente',
                'venta' => $venta->fresh()->load(['cliente', 'detalleVentas.producto']),
                'success' => true
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al procesar venta: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Cancelar venta (restaura stock si estaba procesada)
     */
    public function cancelar(Venta $venta)
    {
        try {
            $this->inventarioService->cancelarVenta($venta);

            return response()->json([
                'message' => 'Venta cancelada exitosamente',
                'venta' => $venta->fresh()->load(['cliente', 'detalleVentas.producto']),
                'success' => true
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al cancelar venta: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venta $venta)
    {
        try {
            // Solo permitir eliminar ventas pendientes o canceladas
            if ($venta->estado === 'completada') {
                return response()->json([
                    'error' => 'No se pueden eliminar ventas completadas. Debe cancelarla primero.'
                ], 422);
            }

            // Si está cancelada pero tenía stock procesado, verificar integridad
            if ($venta->estado === 'cancelada') {
                // Eliminar directamente ya que el stock ya fue restaurado
                $venta->detalleVentas()->delete();
                $venta->delete();
            } else {
                // Es pendiente, eliminar sin afectar stock
                $venta->detalleVentas()->delete();
                $venta->delete();
            }

            return response()->json([
                'message' => 'Venta eliminada exitosamente',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar venta: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * ✅ NUEVO: Obtener ventas por estado
     */
    public function porEstado($estado)
    {
        $ventas = Venta::with(['cliente', 'detalleVentas.producto'])
            ->where('estado', $estado)
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json($ventas);
    }
}
