<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Tour;
use App\Models\DetalleReservaTour;
use App\Models\Reserva;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InformePDFController extends Controller
{
    public function descargarInforme(Request $request)
    {
        // Establecer el locale de Carbon a español ANTES de cualquier uso de translatedFormat
        Carbon::setLocale('es');

        $meses = $request->input('meses', []);
        if (empty($meses)) {
            $meses = [date('Y-m')];
        }

        $mesesData = [];
        $mesesSinDatos = [];

        foreach ($meses as $mes) {
            // Convertir el mes a formato Carbon
            $fechaInicio = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
            $fechaFin = Carbon::createFromFormat('Y-m', $mes)->endOfMonth();

            // Obtener los detalles de reservas de tours FINALIZADOS para el mes especificado
            $detallesReservas = DetalleReservaTour::with(['tour', 'reserva'])
                ->whereHas('tour', function($query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('fecha_salida', [$fechaInicio, $fechaFin])
                          ->where('estado', 'FINALIZADO'); // Solo tours finalizados
                })
                ->whereHas('reserva', function($query) {
                    $query->where('estado', 'FINALIZADA'); // Solo reservas finalizadas de tours finalizados
                })
                ->get();

            $tours = [];
            foreach ($detallesReservas as $detalle) {
                $tour = $detalle->tour;
                $reserva = $detalle->reserva;

                $tours[] = [
                    'fecha' => Carbon::parse($tour->fecha_salida)->format('d/m/Y'),
                    'nombre' => $tour->nombre,
                    'cupos_vendidos' => $detalle->cupos_reservados,
                    'menores' => $reserva->menores_edad,
                    'mayores' => $reserva->mayores_edad,
                    'precio' => $detalle->precio_unitario,
                    'subtotal' => $detalle->precio_total,
                ];
            }

            // Si no hay tours finalizados con reservas, agregar a meses sin datos
            if (empty($tours)) {
                $mesesSinDatos[] = Carbon::createFromFormat('Y-m', $mes)->translatedFormat('F Y');
                continue;
            }

            // Solo agregar el mes si tiene datos
            if (!empty($tours)) {
                $total = array_sum(array_column($tours, 'subtotal'));
                $mesesData[] = [
                    'mes' => $mes,
                    'tours' => $tours,
                    'total' => $total,
                ];
            }
        }

        // Si es un solo mes y no tiene datos, retornar error JSON
        if (count($meses) === 1 && empty($mesesData)) {
            $mesNombre = Carbon::createFromFormat('Y-m', $meses[0])->translatedFormat('F Y');
            return response()->json([
                'error' => true,
                'message' => "No se encontraron registros de ventas para el mes de {$mesNombre}."
            ], 404);
        }

        // Si es un rango y no hay datos en ningún mes
        if (count($meses) > 1 && empty($mesesData)) {
            return response()->json([
                'error' => true,
                'message' => 'No se encontraron registros de ventas para el rango de fechas seleccionado.'
            ], 404);
        }

        $fecha_emision = Carbon::now('America/El_Salvador')->format('d/m/Y H:i');
        $fecha_hora = Carbon::now('America/El_Salvador')->format('Ymd_His');

        $data = [
            'titulo' => 'Informe de Cupos Vendidos',
            'mesesData' => $mesesData,
            'mesesSinDatos' => $mesesSinDatos,
            'fecha_emision' => $fecha_emision,
            'direccion' => 'Chalatenango, El Salvador',
            'usuario_descarga' => [
                'nombre' => auth()->user()->name ?? 'Usuario no identificado',
                'email' => auth()->user()->email ?? 'Email no disponible'
            ],
        ];

        $nombreArchivo = "informe_cupos_mas_vendidos_{$fecha_hora}.pdf";

        $pdf = Pdf::loadView('informes.informe', $data)
            ->setPaper('letter', 'portrait');
        // Si se recibe el parámetro preview=1, mostrar en navegador (inline), si no, descargar (attachment)
        $dispositionType = $request->input('preview') == '1' ? 'inline' : 'attachment';
        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $dispositionType . '; filename="' . $nombreArchivo . '"');
    }

    public function descargarInformeInventario(Request $request)
    {
        try {
            Carbon::setLocale('es');

            // Obtener productos con información de inventario
            $productos = Producto::with(['categoria', 'inventarios' => function($query) {
                $query->orderBy('fecha_movimiento', 'desc');
            }])
            ->select('id', 'nombre', 'precio', 'stock_actual', 'stock_minimo', 'categoria_id')
            ->orderBy('nombre')
            ->get();

            if ($productos->isEmpty()) {
                return response()->json([
                    'error' => true,
                    'message' => 'No se encontraron productos en el inventario.'
                ], 404);
            }

            // Clasificar productos
            $productosDisponibles = [];
            $productosStockBajo = [];
            $productosAgotados = [];
            $valorTotalInventario = 0;

            foreach ($productos as $producto) {
                $valorProducto = $producto->stock_actual * $producto->precio;
                $valorTotalInventario += $valorProducto;

                $productoData = [
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria->nombre ?? 'Sin categoría',
                    'stock_actual' => $producto->stock_actual,
                    'stock_minimo' => $producto->stock_minimo,
                    'precio' => $producto->precio,
                    'valor_total' => $valorProducto,
                    'estado' => $producto->estado_inventario,
                ];

                if ($producto->stock_actual <= 0) {
                    $productosAgotados[] = $productoData;
                } elseif ($producto->stock_actual <= $producto->stock_minimo) {
                    $productosStockBajo[] = $productoData;
                } else {
                    $productosDisponibles[] = $productoData;
                }
            }

            // Obtener movimientos recientes de inventario (últimos 30 días)
            $fechaLimite = Carbon::now()->subDays(30);
            $movimientosRecientes = Inventario::with(['producto', 'user'])
                ->where('fecha_movimiento', '>=', $fechaLimite)
                ->orderBy('fecha_movimiento', 'desc')
                ->limit(50)
                ->get()
                ->map(function($movimiento) {
                    return [
                        'fecha' => Carbon::parse($movimiento->fecha_movimiento)->format('d/m/Y H:i'),
                        'producto' => $movimiento->producto->nombre ?? 'Producto eliminado',
                        'tipo' => $movimiento->tipo_movimiento,
                        'cantidad' => $movimiento->cantidad,
                        'motivo' => $movimiento->motivo ?? 'Sin motivo especificado',
                        'usuario' => $movimiento->user->name ?? 'Usuario eliminado',
                    ];
                });

            // Estadísticas resumidas por categoría
            $resumenPorCategoria = CategoriaProducto::with(['productos'])
                ->get()
                ->map(function($categoria) {
                    $productos = $categoria->productos;
                    $stockTotal = $productos->sum('stock_actual');
                    $valorTotal = $productos->sum(function($p) {
                        return $p->stock_actual * $p->precio;
                    });

                    return [
                        'categoria' => $categoria->nombre,
                        'total_productos' => $productos->count(),
                        'stock_total' => $stockTotal,
                        'valor_total' => $valorTotal,
                        'productos_agotados' => $productos->where('stock_actual', '<=', 0)->count(),
                        'productos_stock_bajo' => $productos->filter(function($p) {
                                                                 return $p->stock_actual > 0 && $p->stock_actual <= $p->stock_minimo;
                                                             })->count(),
                    ];
                })
                ->sortByDesc('valor_total');

            $fecha_emision = Carbon::now('America/El_Salvador')->format('d/m/Y H:i');
            $fecha_hora = Carbon::now('America/El_Salvador')->format('Ymd_His');

            $data = [
                'titulo' => 'Informe Detallado de Inventario',
                'productos_disponibles' => $productosDisponibles,
                'productos_stock_bajo' => $productosStockBajo,
                'productos_agotados' => $productosAgotados,
                'movimientos_recientes' => $movimientosRecientes,
                'resumen_por_categoria' => $resumenPorCategoria,
                'estadisticas' => [
                    'total_productos' => $productos->count(),
                    'productos_disponibles' => count($productosDisponibles),
                    'productos_stock_bajo' => count($productosStockBajo),
                    'productos_agotados' => count($productosAgotados),
                    'valor_total_inventario' => $valorTotalInventario,
                    'productos_requieren_reabastecimiento' => count($productosStockBajo) + count($productosAgotados),
                ],
                'fecha_emision' => $fecha_emision,
                'usuario_descarga' => [
                    'nombre' => auth()->user()->name ?? 'Usuario no identificado',
                    'email' => auth()->user()->email ?? 'Email no disponible'
                ],
            ];

            $nombreArchivo = "informe_inventario_{$fecha_hora}.pdf";

            $pdf = Pdf::loadView('informes.inventario', $data)
                ->setPaper('letter', 'portrait');

            $dispositionType = $request->input('preview') == '1' ? 'inline' : 'attachment';
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', $dispositionType . '; filename="' . $nombreArchivo . '"');

        } catch (\Exception $e) {
            Log::error('Error generando informe de inventario: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error interno del servidor al generar el informe de inventario.'
            ], 500);
        }
    }

    /**
     * Generar informe PDF de reservas de un cliente específico
     */
    public function descargarInformeReservasCliente(Request $request)
    {
        try {
            Carbon::setLocale('es');

            $clienteId = $request->input('cliente_id');
            if (!$clienteId) {
                return response()->json([
                    'error' => true,
                    'message' => 'ID de cliente requerido.'
                ], 400);
            }

            // Buscar el cliente (puede ser por ID de usuario o ID de cliente)
            $cliente = null;
            $user = null;

            // Intentar buscar por ID de cliente primero
            $clienteModel = Cliente::with(['user'])->find($clienteId);
            if ($clienteModel) {
                $cliente = $clienteModel;
                $user = $clienteModel->user;
            } else {
                // Si no se encuentra, buscar por ID de usuario con rol cliente
                $user = User::whereHas('roles', function($query) {
                    $query->where('name', 'Cliente');
                })->with(['cliente'])->find($clienteId);

                if ($user) {
                    $cliente = $user->cliente;
                }
            }

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'Cliente no encontrado.'
                ], 404);
            }

            // Obtener reservas del cliente
            $reservas = collect();
            if ($cliente) {
                $reservas = $cliente->reservas()->with(['empleado.user', 'detallesTours.tour'])->orderBy('fecha', 'desc')->get();
            }

            // Procesar datos de reservas
            $reservasData = $reservas->map(function($reserva) {
                // Obtener tours de la reserva
                $tours = $reserva->detallesTours->map(function($detalle) {
                    return $detalle->tour ? $detalle->tour->nombre : 'Tour no disponible';
                })->implode(', ');

                return [
                    'id' => $reserva->id,
                    'fecha' => Carbon::parse($reserva->fecha)->format('d/m/Y'),
                    'personas' => $reserva->mayores_edad + ($reserva->menores_edad ?? 0),
                    'estado' => $reserva->estado,
                    'total' => $reserva->total,
                    'tours' => $tours ?: 'Sin tours asignados',
                    'fecha_creacion' => Carbon::parse($reserva->created_at)->format('d/m/Y H:i'),
                ];
            });

            // Calcular estadísticas
            $totalReservas = $reservas->count();
            $totalMonto = $reservas->sum('total');
            $pendientes = $reservas->where('estado', 'PENDIENTE')->count();
            $confirmadas = $reservas->where('estado', 'CONFIRMADA')->count();

            // Resumen por estados
            $estadosCount = $reservas->groupBy('estado');
            $resumenEstados = [];
            foreach ($estadosCount as $estado => $reservasEstado) {
                $cantidad = $reservasEstado->count();
                $monto = $reservasEstado->sum('total');
                $porcentaje = $totalReservas > 0 ? ($cantidad / $totalReservas) * 100 : 0;

                $resumenEstados[$estado] = [
                    'cantidad' => $cantidad,
                    'monto' => $monto,
                    'porcentaje' => $porcentaje
                ];
            }

            $fecha_emision = Carbon::now('America/El_Salvador')->format('d/m/Y H:i');
            $fecha_hora = Carbon::now('America/El_Salvador')->format('Ymd_His');

            $data = [
                'titulo' => 'Informe de Reservas del Cliente',
                'cliente' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'numero_identificacion' => $cliente->numero_identificacion ?? 'N/A',
                    'telefono' => $cliente->telefono ?? 'N/A',
                ],
                'reservas' => $reservasData,
                'estadisticas' => [
                    'total_reservas' => $totalReservas,
                    'total_monto' => $totalMonto,
                    'pendientes' => $pendientes,
                    'confirmadas' => $confirmadas,
                ],
                'resumen_estados' => $resumenEstados,
                'fecha_emision' => $fecha_emision,
                'usuario_descarga' => [
                    'nombre' => auth()->user()->name ?? 'Usuario no identificado',
                    'email' => auth()->user()->email ?? 'Email no disponible'
                ],
            ];

            $nombreArchivo = "informe_reservas_cliente_{$user->name}_{$fecha_hora}.pdf";
            $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $nombreArchivo); // Limpiar caracteres especiales

            $pdf = Pdf::loadView('informes.reservas-cliente', $data)
                ->setPaper('letter', 'portrait');

            $dispositionType = $request->input('preview') == '1' ? 'inline' : 'attachment';
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', $dispositionType . '; filename="' . $nombreArchivo . '"');

        } catch (\Exception $e) {
            Log::error('Error generando informe de reservas del cliente: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error interno del servidor al generar el informe de reservas del cliente.'
            ], 500);
        }
    }

    /**
     * Generar informe PDF de ventas de un cliente específico
     */
    public function descargarInformeVentasCliente(Request $request)
    {
        try {
            Carbon::setLocale('es');

            $clienteId = $request->input('cliente_id');
            if (!$clienteId) {
                return response()->json([
                    'error' => true,
                    'message' => 'ID de cliente requerido.'
                ], 400);
            }

            // Buscar el cliente (puede ser por ID de usuario o ID de cliente)
            $cliente = null;
            $user = null;

            // Intentar buscar por ID de cliente primero
            $clienteModel = Cliente::with(['user'])->find($clienteId);
            if ($clienteModel) {
                $cliente = $clienteModel;
                $user = $clienteModel->user;
            } else {
                // Si no se encuentra, buscar por ID de usuario con rol cliente
                $user = User::whereHas('roles', function($query) {
                    $query->where('name', 'Cliente');
                })->with(['cliente'])->find($clienteId);

                if ($user) {
                    $cliente = $user->cliente;
                }
            }

            if (!$user) {
                return response()->json([
                    'error' => true,
                    'message' => 'Cliente no encontrado.'
                ], 404);
            }

            // Obtener ventas del cliente
            $ventas = collect();
            if ($cliente) {
                $ventas = $cliente->ventas()->with(['detalleVentas.producto'])->orderBy('fecha', 'desc')->get();
            }

            // Procesar datos de ventas
            $ventasData = $ventas->map(function($venta) {
                $productos = $venta->detalleVentas->map(function($detalle) {
                    return [
                        'nombre' => $detalle->producto->nombre ?? 'Producto eliminado',
                        'cantidad' => $detalle->cantidad,
                        'precio' => $detalle->precio_unitario,
                    ];
                });

                return [
                    'id' => $venta->id,
                    'fecha' => Carbon::parse($venta->fecha)->format('d/m/Y'),
                    'estado' => $venta->estado,
                    'estado_label' => $this->getEstadoVentaLabel($venta->estado),
                    'total' => $venta->total,
                    'productos' => $productos,
                ];
            });

            // Calcular estadísticas
            $totalVentas = $ventas->count();
            $totalMonto = $ventas->sum('total');
            $completadas = $ventas->where('estado', 'completada')->count();

            // Resumen por estados
            $estadosCount = $ventas->groupBy('estado');
            $resumenEstados = [];
            foreach ($estadosCount as $estado => $ventasEstado) {
                $cantidad = $ventasEstado->count();
                $monto = $ventasEstado->sum('total');
                $porcentaje = $totalVentas > 0 ? ($cantidad / $totalVentas) * 100 : 0;

                $resumenEstados[$estado] = [
                    'cantidad' => $cantidad,
                    'monto' => $monto,
                    'porcentaje' => $porcentaje,
                    'estado_label' => $this->getEstadoVentaLabel($estado)
                ];
            }

            // Productos más comprados
            $productosPopulares = [];
            if ($cliente && $ventas->isNotEmpty()) {
                $detallesVentas = DetalleVenta::whereIn('venta_id', $ventas->pluck('id'))
                    ->with('producto')
                    ->get()
                    ->groupBy('producto_id');

                foreach ($detallesVentas as $productoId => $detalles) {
                    $producto = $detalles->first()->producto;
                    if ($producto) {
                        $cantidadTotal = $detalles->sum('cantidad');
                        $montoTotal = $detalles->sum(function($detalle) {
                            return $detalle->cantidad * $detalle->precio_unitario;
                        });

                        $productosPopulares[] = [
                            'nombre' => $producto->nombre,
                            'cantidad_total' => $cantidadTotal,
                            'monto_total' => $montoTotal
                        ];
                    }
                }

                // Ordenar por cantidad total descendente
                usort($productosPopulares, function($a, $b) {
                    return $b['cantidad_total'] <=> $a['cantidad_total'];
                });

                // Tomar solo los primeros 10
                $productosPopulares = array_slice($productosPopulares, 0, 10);
            }

            $fecha_emision = Carbon::now('America/El_Salvador')->format('d/m/Y H:i');
            $fecha_hora = Carbon::now('America/El_Salvador')->format('Ymd_His');

            $data = [
                'titulo' => 'Informe de Ventas del Cliente',
                'cliente' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'numero_identificacion' => $cliente->numero_identificacion ?? 'N/A',
                    'telefono' => $cliente->telefono ?? 'N/A',
                ],
                'ventas' => $ventasData,
                'estadisticas' => [
                    'total_ventas' => $totalVentas,
                    'total_monto' => $totalMonto,
                    'completadas' => $completadas,
                ],
                'resumen_estados' => $resumenEstados,
                'productos_populares' => $productosPopulares,
                'fecha_emision' => $fecha_emision,
                'usuario_descarga' => [
                    'nombre' => auth()->user()->name ?? 'Usuario no identificado',
                    'email' => auth()->user()->email ?? 'Email no disponible'
                ],
            ];

            $nombreArchivo = "informe_ventas_cliente_{$user->name}_{$fecha_hora}.pdf";
            $nombreArchivo = preg_replace('/[^A-Za-z0-9_\-.]/', '_', $nombreArchivo); // Limpiar caracteres especiales

            $pdf = Pdf::loadView('informes.ventas-cliente', $data)
                ->setPaper('letter', 'portrait');

            $dispositionType = $request->input('preview') == '1' ? 'inline' : 'attachment';
            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', $dispositionType . '; filename="' . $nombreArchivo . '"');

        } catch (\Exception $e) {
            Log::error('Error generando informe de ventas del cliente: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error interno del servidor al generar el informe de ventas del cliente.'
            ], 500);
        }
    }

    /**
     * Helper para obtener etiquetas de estado de venta
     */
    private function getEstadoVentaLabel($estado)
    {
        switch ($estado) {
            case 'pendiente':
                return 'Pendiente de Pago';
            case 'completada':
                return 'Completada';
            case 'cancelada':
                return 'Cancelada';
            default:
                return ucfirst($estado);
        }
    }
}
