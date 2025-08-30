<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class InventarioService
{
    /**
     * Procesar venta completa - Reduce stock y registra movimientos
     */
    public function procesarVenta(Venta $venta): void
    {
        if (!$venta->estaPendiente()) {
            throw new Exception("Solo se pueden procesar ventas pendientes");
        }

        DB::transaction(function () use ($venta) {
            // Verificar stock de todos los productos ANTES de procesar
            foreach ($venta->detalleVentas as $detalle) {
                $producto = $detalle->producto;
                if (!$producto->estaDisponible($detalle->cantidad)) {
                    throw new Exception("Stock insuficiente para {$producto->nombre}. Disponible: {$producto->stock_actual}, Requerido: {$detalle->cantidad}");
                }
            }

            // Procesar cada producto
            foreach ($venta->detalleVentas as $detalle) {
                $this->registrarSalidaPorVenta($detalle->producto, $detalle->cantidad, $venta);
            }

            // Marcar venta como completada
            $venta->update(['estado' => 'completada']);
        });
    }

    /**
     * Cancelar venta - Restaura stock
     */
    public function cancelarVenta(Venta $venta): void
    {
        if ($venta->estaCancelada()) {
            throw new Exception("La venta ya está cancelada");
        }

        DB::transaction(function () use ($venta) {
            // Solo restaurar si la venta estaba completada
            if ($venta->estaCompletada()) {
                foreach ($venta->detalleVentas as $detalle) {
                    $this->registrarEntradaPorCancelacion($detalle->producto, $detalle->cantidad, $venta);
                }
            }

            // Marcar como cancelada
            $venta->update(['estado' => 'cancelada']);
        });
    }

    /**
     * Agregar stock manualmente
     */
    public function agregarStock(int $productoId, int $cantidad, string $motivo = 'entrada_manual', ?string $observacion = null): Inventario
    {
        $producto = Producto::findOrFail($productoId);

        return DB::transaction(function () use ($producto, $cantidad, $motivo, $observacion) {
            // Incrementar stock
            $producto->increment('stock_actual', $cantidad);

            // Registrar movimiento
            return $this->crearMovimiento([
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'tipo_movimiento' => 'ENTRADA',
                'motivo' => $motivo,
                'observacion' => $observacion ?? 'Entrada manual de stock',
                'referenciable_id' => null,
                'referenciable_type' => null
            ]);
        });
    }

    /**
     * Ajustar stock (positivo o negativo)
     */
    public function ajustarStock(int $productoId, int $nuevoStock, string $observacion = 'Ajuste de inventario'): void
    {
        $producto = Producto::findOrFail($productoId);
        $diferencia = $nuevoStock - $producto->stock_actual;

        if ($diferencia === 0) {
            return; // No hay cambios
        }

        DB::transaction(function () use ($producto, $diferencia, $observacion) {
            if ($diferencia > 0) {
                // Ajuste positivo (entrada)
                $producto->increment('stock_actual', $diferencia);
                $this->crearMovimiento([
                    'producto_id' => $producto->id,
                    'cantidad' => $diferencia,
                    'tipo_movimiento' => 'ENTRADA',
                    'motivo' => 'ajuste_entrada',
                    'observacion' => $observacion,
                    'referenciable_id' => null,
                    'referenciable_type' => null
                ]);
            } else {
                // Ajuste negativo (salida)
                $cantidadSalida = abs($diferencia);
                $producto->decrement('stock_actual', $cantidadSalida);
                $this->crearMovimiento([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidadSalida,
                    'tipo_movimiento' => 'SALIDA',
                    'motivo' => 'ajuste_salida',
                    'observacion' => $observacion,
                    'referenciable_id' => null,
                    'referenciable_type' => null
                ]);
            }
        });
    }

    /**
     * Registrar salida por venta (método privado)
     */
    private function registrarSalidaPorVenta(Producto $producto, int $cantidad, Venta $venta): void
    {
        // Reducir stock
        $producto->decrement('stock_actual', $cantidad);

        // Registrar movimiento
        $this->crearMovimiento([
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'tipo_movimiento' => 'SALIDA',
            'motivo' => 'venta',
            'observacion' => "Venta #{$venta->id}",
            'referenciable_id' => $venta->id,
            'referenciable_type' => Venta::class
        ]);
    }

    /**
     * Registrar entrada por cancelación (método privado)
     */
    private function registrarEntradaPorCancelacion(Producto $producto, int $cantidad, Venta $venta): void
    {
        // Restaurar stock
        $producto->increment('stock_actual', $cantidad);

        // Registrar movimiento
        $this->crearMovimiento([
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'tipo_movimiento' => 'ENTRADA',
            'motivo' => 'cancelacion_venta',
            'observacion' => "Cancelación de venta #{$venta->id}",
            'referenciable_id' => $venta->id,
            'referenciable_type' => Venta::class
        ]);
    }

    /**
     * Crear movimiento de inventario (método privado)
     */
    private function crearMovimiento(array $datos): Inventario
    {
        return Inventario::create([
            'fecha_movimiento' => now(),
            'cantidad' => $datos['cantidad'],
            'tipo_movimiento' => $datos['tipo_movimiento'],
            'motivo' => $datos['motivo'],
            'observacion' => $datos['observacion'],
            'user_id' => Auth::id() ?: 1,
            'producto_id' => $datos['producto_id'],
            'referenciable_id' => $datos['referenciable_id'],
            'referenciable_type' => $datos['referenciable_type']
        ]);
    }
}