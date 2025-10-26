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
     * Eliminar venta - Restaura stock y elimina el registro
     */
    public function eliminarVenta(Venta $venta): void
    {
        DB::transaction(function () use ($venta) {
            // Restaurar stock si la venta estaba completada
            if ($venta->estaCompletada()) {
                foreach ($venta->detalleVentas as $detalle) {
                    $this->registrarEntradaPorEliminacion($detalle->producto, $detalle->cantidad, $venta);
                }
            }

            // Eliminar movimientos de inventario relacionados
            Inventario::where('venta_id', $venta->id)->delete();

            // Eliminar detalles de venta
            $venta->detalleVentas()->delete();

            // Eliminar pagos relacionados
            $venta->pagos()->delete();

            // Eliminar la venta
            $venta->delete();
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
                'venta_id' => null
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
                // Ajuste positivo (aumento)
                $producto->increment('stock_actual', $diferencia);
                $this->crearMovimiento([
                    'producto_id' => $producto->id,
                    'cantidad' => $diferencia,
                    'tipo_movimiento' => 'AJUSTE',
                    'motivo' => 'ajuste_aumento',
                    'observacion' => $observacion,
                    'venta_id' => null
                ]);
            } else {
                // Ajuste negativo (reducción)
                $cantidadSalida = abs($diferencia);
                $producto->decrement('stock_actual', $cantidadSalida);
                $this->crearMovimiento([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidadSalida,
                    'tipo_movimiento' => 'AJUSTE',
                    'motivo' => 'ajuste_reduccion',
                    'observacion' => $observacion,
                    'venta_id' => null
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
            'venta_id' => $venta->id
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
            'venta_id' => $venta->id
        ]);
    }

    /**
     * Registrar entrada por eliminación (método privado)
     */
    private function registrarEntradaPorEliminacion(Producto $producto, int $cantidad, Venta $venta): void
    {
        // Restaurar stock
        $producto->increment('stock_actual', $cantidad);

        // Registrar movimiento antes de eliminar la venta
        $this->crearMovimiento([
            'producto_id' => $producto->id,
            'cantidad' => $cantidad,
            'tipo_movimiento' => 'ENTRADA',
            'motivo' => 'eliminacion_venta',
            'observacion' => "Eliminación de venta #{$venta->id}",
            'venta_id' => null // Se pone null porque la venta será eliminada
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
            'venta_id' => $datos['venta_id'] ?? null
        ]);
    }
}