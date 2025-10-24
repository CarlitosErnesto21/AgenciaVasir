<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inventario extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha_movimiento',
        'cantidad',
        'tipo_movimiento',
        'motivo',
        'observacion',
        'user_id',
        'producto_id',
        'venta_id'
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
    ];

    // ===== RELACIONES BÁSICAS =====
    
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    // ===== MÉTODOS BÁSICOS =====
    
    public function esEntrada(): bool
    {
        return $this->tipo_movimiento === 'ENTRADA';
    }

    public function esSalida(): bool
    {
        return $this->tipo_movimiento === 'SALIDA';
    }

    public function esVenta(): bool
    {
        return $this->motivo === 'venta';
    }
}