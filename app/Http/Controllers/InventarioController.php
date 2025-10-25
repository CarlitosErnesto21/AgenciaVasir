<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Producto;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Exception;

class InventarioController extends Controller
{
    protected $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * Mostrar historial de movimientos de inventario
     */
    public function index(Request $request)
    {
        $query = Inventario::with(['producto.categoria', 'user', 'venta']);

        // Filtros opcionales
        if ($request->has('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->has('tipo_movimiento')) {
            $query->where('tipo_movimiento', $request->tipo_movimiento);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('fecha_movimiento', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('fecha_movimiento', '<=', $request->fecha_hasta);
        }

        $movimientos = $query->orderBy('fecha_movimiento', 'desc')->paginate(20);
        
        // Productos para los filtros
        $productos = Producto::select('id', 'nombre', 'stock_actual', 'stock_minimo')
            ->with('categoria:id,nombre')
            ->orderBy('nombre')
            ->get();

        // Si es petición AJAX, retornar JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json($movimientos);
        }

        // Si es petición normal, retornar vista Inertia
        return Inertia::render('inventario/Index', [
            'movimientos' => $movimientos,
            'productos' => $productos,
            'filters' => $request->only(['producto_id', 'tipo_movimiento', 'fecha_desde', 'fecha_hasta'])
        ]);
    }

    /**
     * Agregar stock manualmente
     */
    public function agregarStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'cantidad' => 'required|integer|min:1',
                'motivo' => 'nullable|string|max:50',
                'observacion' => 'nullable|string|max:255',
            ]);

            $movimiento = $this->inventarioService->agregarStock(
                $validated['producto_id'],
                $validated['cantidad'],
                $validated['motivo'] ?? 'entrada_manual',
                $validated['observacion']
            );

            return response()->json([
                'message' => 'Stock agregado exitosamente',
                'movimiento' => $movimiento->load('producto.categoria'),
                'success' => true
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $e->validator->errors(),
                'error' => true
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al agregar stock: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * Ajustar stock a un valor específico
     */
    public function ajustarStock(Request $request)
    {
        try {
            $validated = $request->validate([
                'producto_id' => 'required|exists:productos,id',
                'nuevo_stock' => 'required|integer|min:0',
                'observacion' => 'nullable|string|max:255',
            ]);

            $this->inventarioService->ajustarStock(
                $validated['producto_id'],
                $validated['nuevo_stock'],
                $validated['observacion'] ?? 'Ajuste de inventario'
            );

            $producto = Producto::with('categoria')->find($validated['producto_id']);

            return response()->json([
                'message' => 'Stock ajustado exitosamente',
                'producto' => $producto,
                'success' => true
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $e->validator->errors(),
                'error' => true
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al ajustar stock: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * Obtener resumen de inventario
     */
    public function resumen()
    {
        $resumen = [
            'total_productos' => Producto::count(),
            'productos_disponibles' => Producto::where('stock_actual', '>', 0)->count(),
            'productos_agotados' => Producto::where('stock_actual', '<=', 0)->count(),
            'productos_stock_bajo' => Producto::whereColumn('stock_actual', '<=', 'stock_minimo')
                ->where('stock_actual', '>', 0)->count(),
            'movimientos_hoy' => Inventario::whereDate('fecha_movimiento', today())->count(),
            
            // Valor del inventario
            'valor_total_inventario' => Producto::selectRaw('SUM(stock_actual * precio) as total')->value('total') ?? 0,
            
            // Movimientos por tipo
            'entradas_mes' => Inventario::where('tipo_movimiento', 'ENTRADA')
                ->whereMonth('fecha_movimiento', now()->month)->count(),
            'salidas_mes' => Inventario::where('tipo_movimiento', 'SALIDA')
                ->whereMonth('fecha_movimiento', now()->month)->count(),
        ];
        
        return response()->json($resumen);
    }

    /**
     * Obtener productos con stock bajo
     */
    public function stockBajo()
    {
        $productos = Producto::with('categoria')
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->where('stock_actual', '>', 0)
            ->orderBy('stock_actual', 'asc')
            ->get();
            
        return response()->json($productos);
    }

    /**
     * Obtener productos agotados
     */
    public function agotados()
    {
        $productos = Producto::with('categoria')
            ->where('stock_actual', '<=', 0)
            ->orderBy('nombre')
            ->get();
            
        return response()->json($productos);
    }

    /**
     * Historial de movimientos de un producto específico
     */
    public function historialProducto(Producto $producto)
    {
        $movimientos = $producto->inventarios()
            ->with(['user', 'venta'])
            ->orderBy('fecha_movimiento', 'desc')
            ->paginate(15);
            
        return response()->json([
            'producto' => $producto->load('categoria'),
            'movimientos' => $movimientos,
        ]);
    }

    /**
     * Mostrar un movimiento específico
     */
    public function show(Inventario $inventario)
    {
        $inventario->load(['producto.categoria', 'user', 'venta']);
        return response()->json($inventario);
    }
}