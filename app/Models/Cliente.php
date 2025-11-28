<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cliente extends Model
{
    use HasFactory;
    protected $fillable=[
        'numero_identificacion',
        'fecha_nacimiento',
        'genero',
        'tipo_documento',
        'direccion',
        'telefono',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    // ✅ ACCESSOR: Obtener el nombre del usuario relacionado
    public function getNombreAttribute()
    {
        return $this->user ? $this->user->name : 'Cliente Sin Nombre';
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'cliente_id');
    }

    /**
     * Eliminar cliente con todos sus datos asociados en cascada
     * Incluye: reservas, ventas, detalles y pagos
     *
     * @param bool $eliminarCliente Si debe eliminar el registro del cliente también
     */
    public function eliminarEnCascada($eliminarCliente = true)
    {
        DB::beginTransaction();

        try {
            // 1. Eliminar todas las reservas del cliente y sus relaciones
            foreach ($this->reservas as $reserva) {
                // Obtener referencias de Wompi de los pagos de esta reserva ANTES de eliminarlos
                $referenciasWompi = $reserva->pagos()->pluck('referencia_wompi')->filter()->unique();

                // Eliminar reservas de stock relacionadas por referencia Wompi
                if ($referenciasWompi->isNotEmpty()) {
                    StockReservation::whereIn('referencia_wompi', $referenciasWompi)->delete();
                }

                // Eliminar pagos de la reserva (esto eliminará stock_reservations con pago_id por CASCADE)
                $reserva->pagos()->delete();

                // Eliminar detalles de tours de la reserva
                $reserva->detallesTours()->delete();

                // Eliminar la reserva
                $reserva->delete();
            }

            // 2. Eliminar todas las ventas del cliente y sus relaciones
            foreach ($this->ventas as $venta) {
                // Obtener referencias de Wompi de los pagos de esta venta ANTES de eliminarlos
                $referenciasWompi = $venta->pagos()->pluck('referencia_wompi')->filter()->unique();

                // Eliminar reservas de stock relacionadas por referencia Wompi
                if ($referenciasWompi->isNotEmpty()) {
                    StockReservation::whereIn('referencia_wompi', $referenciasWompi)->delete();
                }

                // Eliminar pagos de la venta (esto eliminará stock_reservations con pago_id por CASCADE)
                $venta->pagos()->delete();

                // Eliminar detalles de la venta
                $venta->detalleVentas()->delete();

                // Eliminar la venta
                $venta->delete();
            }

            // 3. Eliminar el cliente solo si se solicita
            if ($eliminarCliente) {
                $this->delete();
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de datos asociados antes de eliminar
     */
    public function getEstadisticasEliminacion()
    {
        $reservas = $this->reservas()->with('pagos')->get();
        $ventas = $this->ventas()->with('pagos')->get();

        $pagoReservasCount = $reservas->sum(function($reserva) {
            return $reserva->pagos->count();
        });

        $pagoVentasCount = $ventas->sum(function($venta) {
            return $venta->pagos->count();
        });

        // Contar reservas de stock asociadas
        $todasReferencias = collect();

        // Obtener referencias de reservas
        foreach ($reservas as $reserva) {
            $referencias = $reserva->pagos->pluck('referencia_wompi')->filter();
            $todasReferencias = $todasReferencias->merge($referencias);
        }

        // Obtener referencias de ventas
        foreach ($ventas as $venta) {
            $referencias = $venta->pagos->pluck('referencia_wompi')->filter();
            $todasReferencias = $todasReferencias->merge($referencias);
        }

        $stockReservationsCount = 0;
        if ($todasReferencias->isNotEmpty()) {
            $stockReservationsCount = StockReservation::whereIn('referencia_wompi', $todasReferencias->unique())->count();
        }

        return [
            'reservas_count' => $reservas->count(),
            'ventas_count' => $ventas->count(),
            'pagos_reservas_count' => $pagoReservasCount,
            'pagos_ventas_count' => $pagoVentasCount,
            'total_pagos_count' => $pagoReservasCount + $pagoVentasCount,
            'stock_reservations_count' => $stockReservationsCount
        ];
    }

    /**
     * Verificar si el cliente tiene datos asociados que se perderán
     */
    public function tieneDataAsociada()
    {
        return $this->reservas()->exists() || $this->ventas()->exists();
    }

    /**
     * Eliminar solo los pagos asociados a las reservas y ventas del cliente
     * Útil para operaciones de limpieza específicas
     */
    public function eliminarPagosAsociados()
    {
        DB::beginTransaction();

        try {
            // Eliminar pagos de reservas
            foreach ($this->reservas as $reserva) {
                $reserva->pagos()->delete();
            }

            // Eliminar pagos de ventas
            foreach ($this->ventas as $venta) {
                $venta->pagos()->delete();
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
