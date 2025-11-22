<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Producto;
use App\Models\Cliente;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class VentaController extends Controller
{
    protected $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /**
     * ✅ SEGURO: Listar ventas con validaciones de integridad
     */
    public function index(Request $request)
    {
        $query = Venta::with(['cliente', 'detalleVentas.producto.categoria', 'pagos']);

        // Filtros opcionales
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('con_pago_aprobado')) {
            $query->conPagoAprobado();
        }

        if ($request->has('solo_validas')) {
            $query->completadasValidas();
        }

        // Filtro de fecha para optimizar dashboard
        if ($request->has('desde')) {
            $query->where('fecha', '>=', $request->desde);
        }

        $ventas = $query->orderBy('fecha', 'desc')->get();

        // Agregar información de integridad
        $ventas->each(function ($venta) {
            $venta->es_consistente = $venta->validarConsistenciaConPagos();
            $venta->tiene_pago_aprobado = $venta->tienePagoAprobado();
        });

        // ✅ COMPATIBILIDAD: Si es una llamada desde el dashboard (con parámetro 'desde'),
        // devolver solo el array de ventas para mantener compatibilidad
        if ($request->has('desde') && !$request->has('formato_completo')) {
            return response()->json($ventas);
        }

        // ✅ FORMATO COMPLETO: Para llamadas de API administrativa
        return response()->json([
            'ventas' => $ventas,
            'resumen' => [
                'total' => $ventas->count(),
                'completadas' => $ventas->where('estado', 'completada')->count(),
                'canceladas' => $ventas->where('estado', 'cancelada')->count(),
                'inconsistentes' => $ventas->where('es_consistente', false)->count()
            ]
        ]);
    }

    /**
     * ✅ VISTA WEB: Renderizar página de ventas con Inertia
     */
    public function indexWeb(Request $request)
    {
        $query = Venta::with(['cliente.user', 'detalleVentas.producto.categoria', 'pagos']);

        // Filtros opcionales
        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        $ventas = $query->orderBy('fecha', 'desc')->get();

        // Agregar información de integridad
        $ventas->each(function ($venta) {
            $venta->es_consistente = $venta->validarConsistenciaConPagos();
            $venta->tiene_pago_aprobado = $venta->tienePagoAprobado();
        });

        // Debug: Verificar que los clientes se estén cargando
        Log::info('Ventas cargadas para vista web:', [
            'total_ventas' => $ventas->count(),
            'primer_venta' => $ventas->first() ? [
                'id' => $ventas->first()->id,
                'cliente_id' => $ventas->first()->cliente_id,
                'cliente_cargado' => $ventas->first()->cliente ? 'SÍ' : 'NO',
                'cliente_nombre' => $ventas->first()->cliente?->nombre ?? 'NULL'
            ] : null
        ]);

        return inertia('Catalogos/Ventas', [
            'ventas' => $ventas
        ]);
    }

    /**
     * ❌ INFORMATIVO: Las ventas se crean a través del carrito y Wompi
     */
    public function create()
    {
        return response()->json([
            'message' => 'Las ventas se crean a través del proceso de carrito y pago con Wompi',
            'flujo_correcto' => [
                '1. Cliente agrega productos al carrito (frontend)',
                '2. Cliente confirma pago: POST /carrito/create-venta',
                '3. Sistema crea venta completada y genera link de Wompi',
                '4. Cliente paga en Wompi',
                '5. Webhook confirma el estado del pago'
            ],
            'endpoints_disponibles' => [
                'GET /api/ventas - Consultar ventas',
                'GET /api/ventas/{id} - Ver detalle de venta',
                'POST /carrito/create-venta - Crear venta desde carrito'
            ]
        ]);
    }

    /**
     * ❌ BLOQUEADO: Las ventas solo se crean a través del proceso de pago con Wompi
     */
    public function store(Request $request)
    {
        return response()->json([
            'message' => 'Las ventas se crean únicamente a través del proceso de pago con Wompi',
            'error' => 'OPERACION_NO_PERMITIDA',
            'flujo_correcto' => [
                '1. Agregar productos al carrito',
                '2. Procesar pago con Wompi: POST /carrito/create-venta',
                '3. El webhook de Wompi confirmará la venta automáticamente'
            ]
        ], 403);
    }

    /**
     * ✅ SEGURO: Mostrar venta con información completa de pagos
     */
    public function show(Venta $venta)
    {
        $venta->load([
            'cliente',
            'detalleVentas.producto.categoria',
            'pagos' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        // Agregar información de integridad
        $venta->es_consistente = $venta->validarConsistenciaConPagos();
        $venta->tiene_pago_aprobado = $venta->tienePagoAprobado();
        $venta->pago_aprobado = $venta->getPagoAprobado();

        return response()->json($venta);
    }

    /**
     * ❌ BLOQUEADO: No se permite editar ventas por integridad con pagos
     */
    public function edit(Venta $venta)
    {
        return response()->json([
            'message' => 'No se permite editar ventas por integridad del sistema de pagos',
            'info' => 'Una vez creada la venta a través de Wompi, no se puede modificar',
            'venta_actual' => [
                'id' => $venta->id,
                'estado' => $venta->estado,
                'total' => $venta->total,
                'tiene_pago_aprobado' => $venta->tienePagoAprobado()
            ]
        ], 403);
    }

    /**
     * ❌ BLOQUEADO: Solo el sistema de pagos de Wompi puede actualizar estados de ventas
     */
    public function update(Request $request, Venta $venta)
    {
        return response()->json([
            'message' => 'Solo el sistema de pagos puede actualizar el estado de las ventas',
            'error' => 'OPERACION_NO_PERMITIDA',
            'info' => 'Los estados de venta se actualizan automáticamente a través del webhook de Wompi'
        ], 403);
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
     * ✅ NUEVO: Eliminar venta (restaura stock y elimina registro)
     */
    public function eliminar(Venta $venta)
    {
        try {
            $this->inventarioService->eliminarVenta($venta);

            return response()->json([
                'message' => 'Venta eliminada exitosamente',
                'success' => true
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar venta: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    /**
     * ❌ BLOQUEADO: No se permiten eliminaciones directas por integridad del sistema
     */
    public function destroy(Venta $venta)
    {
        return response()->json([
            'message' => 'No se permite eliminar ventas directamente por seguridad',
            'error' => 'OPERACION_NO_PERMITIDA',
            'info' => 'Las ventas se cancelan automáticamente si el pago falla o es rechazado por Wompi'
        ], 403);
    }

    /**
     * ✅ SEGURO: Obtener ventas por estado con validaciones
     */
    public function porEstado($estado)
    {
        $estadosValidos = ['pendiente', 'completada', 'cancelada'];

        if (!in_array($estado, $estadosValidos)) {
            return response()->json([
                'error' => 'Estado inválido',
                'estados_validos' => $estadosValidos
            ], 400);
        }

        $ventas = Venta::with(['cliente', 'detalleVentas.producto', 'pagos'])
            ->where('estado', $estado)
            ->orderBy('fecha', 'desc')
            ->get();

        // Agregar información de integridad
        $ventas->each(function ($venta) {
            $venta->es_consistente = $venta->validarConsistenciaConPagos();
            $venta->tiene_pago_aprobado = $venta->tienePagoAprobado();
        });

        return response()->json([
            'ventas' => $ventas,
            'estado_filtrado' => $estado,
            'total' => $ventas->count(),
            'inconsistentes' => $ventas->where('es_consistente', false)->count()
        ]);
    }

    /**
     * ✅ NUEVO: Resumen general del sistema de ventas
     */
    public function resumen()
    {
        $estadisticas = [
            'total_ventas' => Venta::count(),
            'pendientes' => Venta::where('estado', 'pendiente')->count(),
            'completadas' => Venta::where('estado', 'completada')->count(),
            'canceladas' => Venta::where('estado', 'cancelada')->count(),
            'con_pago_aprobado' => Venta::conPagoAprobado()->count(),
            'completadas_validas' => Venta::completadasValidas()->count(),
            'total_facturado' => Venta::where('estado', 'completada')->sum('total'),
            'ventas_hoy' => Venta::whereDate('created_at', today())->count(),
            'ventas_mes' => Venta::whereMonth('created_at', now()->month)
                              ->whereYear('created_at', now()->year)
                              ->count()
        ];

        // Detectar inconsistencias
        $ventasInconsistentes = Venta::with('pagos')
            ->get()
            ->filter(function ($venta) {
                return !$venta->validarConsistenciaConPagos();
            });

        $estadisticas['inconsistencias'] = [
            'total' => $ventasInconsistentes->count(),
            'ventas_ids' => $ventasInconsistentes->pluck('id')->toArray()
        ];

        // Últimas ventas
        $ultimasVentas = Venta::with(['cliente', 'pagos'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($venta) {
                return [
                    'id' => $venta->id,
                    'cliente' => $venta->cliente->nombre ?? 'N/A',
                    'total' => $venta->total,
                    'estado' => $venta->estado,
                    'fecha' => $venta->created_at->format('d/m/Y H:i'),
                    'tiene_pago_aprobado' => $venta->tienePagoAprobado()
                ];
            });

        return response()->json([
            'estadisticas' => $estadisticas,
            'ultimas_ventas' => $ultimasVentas,
            'sistema_integro' => $ventasInconsistentes->isEmpty(),
            'generado_en' => now()->format('d/m/Y H:i:s')
        ]);
    }
}
