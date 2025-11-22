<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'producto_id',
        'pago_id',
        'referencia_wompi',
        'cantidad_reservada',
        'precio_unitario',
        'subtotal',
        'estado',
        'expira_en',
        'confirmada_en',
        'cancelada_en',
        'metadatos'
    ];

    protected $casts = [
        'expira_en' => 'datetime',
        'confirmada_en' => 'datetime',
        'cancelada_en' => 'datetime',
        'metadatos' => 'array',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    // Relaciones
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class);
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa')->where('expira_en', '>', now());
    }

    public function scopeExpiradas($query)
    {
        return $query->where(function ($q) {
            $q->where('estado', 'activa')->where('expira_en', '<=', now())
              ->orWhere('estado', 'expirada');
        });
    }

    public function scopeConfirmadas($query)
    {
        return $query->where('estado', 'confirmada');
    }

    public function scopePorReferencia($query, $referencia)
    {
        return $query->where('referencia_wompi', $referencia);
    }

    // Métodos de estado
    public function estaActiva()
    {
        return $this->estado === 'activa' && $this->expira_en > now();
    }

    public function estaExpirada()
    {
        return $this->estado === 'expirada' ||
               ($this->estado === 'activa' && $this->expira_en <= now());
    }

    public function estaConfirmada()
    {
        return $this->estado === 'confirmada';
    }

    public function estaCancelada()
    {
        return $this->estado === 'cancelada';
    }

    // Acciones
    public function confirmar()
    {
        if (!$this->estaActiva()) {
            throw new \Exception("No se puede confirmar una reserva que no está activa");
        }

        return DB::transaction(function () {
            $this->update([
                'estado' => 'confirmada',
                'confirmada_en' => now()
            ]);

            // Reducir stock real del producto
            $this->producto->decrement('stock_actual', $this->cantidad_reservada);

            Log::info('Reserva de stock confirmada', [
                'reserva_id' => $this->id,
                'producto_id' => $this->producto_id,
                'cantidad' => $this->cantidad_reservada,
                'referencia_wompi' => $this->referencia_wompi
            ]);

            return $this;
        });
    }

    public function cancelar($motivo = null)
    {
        return DB::transaction(function () use ($motivo) {
            $this->update([
                'estado' => 'cancelada',
                'cancelada_en' => now(),
                'metadatos' => array_merge($this->metadatos ?? [], [
                    'motivo_cancelacion' => $motivo,
                    'cancelada_por' => 'sistema'
                ])
            ]);

            Log::info('Reserva de stock cancelada', [
                'reserva_id' => $this->id,
                'producto_id' => $this->producto_id,
                'cantidad' => $this->cantidad_reservada,
                'motivo' => $motivo,
                'referencia_wompi' => $this->referencia_wompi
            ]);

            return $this;
        });
    }

    public function expirar()
    {
        if ($this->estado !== 'activa') {
            return $this;
        }

        return $this->update([
            'estado' => 'expirada',
            'metadatos' => array_merge($this->metadatos ?? [], [
                'expirada_automaticamente' => true,
                'fecha_expiracion_real' => now()->toISOString()
            ])
        ]);
    }

    // Métodos estáticos para gestión masiva
    public static function crearReservasParaCarrito($productos, $referenciaWompi, $minutosExpiracion = 30)
    {
        return DB::transaction(function () use ($productos, $referenciaWompi, $minutosExpiracion) {
            $reservas = collect();
            $expiraEn = now()->addMinutes($minutosExpiracion);

            foreach ($productos as $item) {
                $producto = Producto::find($item['id']);

                if (!$producto) {
                    throw new \Exception("Producto {$item['id']} no encontrado");
                }

                // Verificar stock disponible (considerando reservas activas)
                $stockDisponible = self::calcularStockDisponible($producto->id);

                if ($stockDisponible < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$producto->nombre}. Disponible: {$stockDisponible}, Solicitado: {$item['cantidad']}");
                }

                $reserva = self::create([
                    'producto_id' => $producto->id,
                    'referencia_wompi' => $referenciaWompi,
                    'cantidad_reservada' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $item['cantidad'] * $item['precio'],
                    'estado' => 'activa',
                    'expira_en' => $expiraEn,
                    'metadatos' => [
                        'producto_nombre' => $producto->nombre,
                        'stock_antes_reserva' => $producto->stock_actual,
                        'creada_desde' => 'carrito_compras'
                    ]
                ]);

                $reservas->push($reserva);
            }

            Log::info('Reservas de stock creadas para carrito', [
                'referencia_wompi' => $referenciaWompi,
                'total_reservas' => $reservas->count(),
                'expira_en' => $expiraEn->toISOString(),
                'productos' => $reservas->pluck('producto_id')->toArray()
            ]);

            return $reservas;
        });
    }

    public static function confirmarReservasPorReferencia($referenciaWompi)
    {
        return DB::transaction(function () use ($referenciaWompi) {
            $reservas = self::porReferencia($referenciaWompi)->activas()->get();

            if ($reservas->isEmpty()) {
                Log::warning('No se encontraron reservas activas para confirmar', [
                    'referencia_wompi' => $referenciaWompi
                ]);
                return collect();
            }

            foreach ($reservas as $reserva) {
                $reserva->confirmar();
            }

            Log::info('Todas las reservas confirmadas por pago exitoso', [
                'referencia_wompi' => $referenciaWompi,
                'total_confirmadas' => $reservas->count()
            ]);

            return $reservas;
        });
    }

    public static function cancelarReservasPorReferencia($referenciaWompi, $motivo = 'Pago no completado')
    {
        return DB::transaction(function () use ($referenciaWompi, $motivo) {
            $reservas = self::porReferencia($referenciaWompi)->activas()->get();

            foreach ($reservas as $reserva) {
                $reserva->cancelar($motivo);
            }

            Log::info('Reservas canceladas por referencia', [
                'referencia_wompi' => $referenciaWompi,
                'total_canceladas' => $reservas->count(),
                'motivo' => $motivo
            ]);

            return $reservas;
        });
    }

    public static function limpiarReservasExpiradas()
    {
        return DB::transaction(function () {
            $reservasExpiradas = self::where('estado', 'activa')
                                    ->where('expira_en', '<=', now())
                                    ->get();

            $count = 0;
            foreach ($reservasExpiradas as $reserva) {
                $reserva->expirar();
                $count++;
            }

            if ($count > 0) {
                Log::info('Limpieza automática de reservas expiradas', [
                    'total_expiradas' => $count
                ]);
            }

            return $count;
        });
    }

    public static function calcularStockDisponible($productoId)
    {
        $producto = Producto::find($productoId);

        if (!$producto) {
            return 0;
        }

        // Stock físico menos reservas activas
        $reservasActivas = self::where('producto_id', $productoId)
                               ->activas()
                               ->sum('cantidad_reservada');

        return max(0, $producto->stock_actual - $reservasActivas);
    }

    public static function obtenerEstadisticas()
    {
        return [
            'activas' => self::activas()->count(),
            'expiradas' => self::expiradas()->count(),
            'confirmadas' => self::confirmadas()->count(),
            'canceladas' => self::where('estado', 'cancelada')->count(),
            'valor_total_reservado' => self::activas()->sum('subtotal'),
            'productos_con_reservas' => self::activas()->distinct('producto_id')->count()
        ];
    }
}
