<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Venta extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'fecha',
        'total',
        'estado',
        'cliente_id',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    // Relaciones existentes    
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
    
    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function movimientosInventario(): MorphMany
    {
        return $this->morphMany(Inventario::class, 'referenciable');
    }

    // ⭐ MÉTODOS DE ESTADO
    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaCompletada(): bool
    {
        return $this->estado === 'completada';
    }

    public function estaCancelada(): bool
    {
        return $this->estado === 'cancelada';
    }
}