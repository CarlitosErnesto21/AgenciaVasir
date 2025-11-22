<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'descripcion', 'precio', 
        'stock_actual', 'stock_minimo', 'categoria_id'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    protected $appends = [
        'primera_imagen'
    ];

    // ===== RELACIONES =====
    
    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function detalleVentas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_id');
    }

    public function reservasStock(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    // ✅ AGREGAR ESTA RELACIÓN POLIMÓRFICA:
    public function imagenes(): MorphMany
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }

    // ===== MÉTODOS BÁSICOS DE ESTADO =====
    
    public function estaDisponible(int $cantidadRequerida = 1): bool
    {
        return $this->stock_actual >= $cantidadRequerida;
    }

    public function estaAgotado(): bool
    {
        return $this->stock_actual <= 0;
    }

    public function tieneStockBajo(): bool
    {
        return $this->stock_actual <= $this->stock_minimo && $this->stock_actual > 0;
    }

    // ===== ESTADO VISUAL =====
    
    public function getEstadoInventarioAttribute(): string
    {
        if ($this->stock_actual <= 0) {
            return 'Agotado';
        } elseif ($this->stock_actual <= $this->stock_minimo) {
            return 'Stock Bajo';
        } else {
            return 'Disponible';
        }
    }
    
    public function getPrimeraImagenAttribute(): ?string
    {
        $imagen = $this->imagenes()->first();
        return $imagen ? asset('images/productos/' . $imagen->nombre) : null;
    }

    public function getStockDisponibleAttribute(): int
    {
        $reservasActivas = $this->reservasStock()
            ->where('estado', 'activa')
            ->sum('cantidad_reservada');
        
        return max(0, $this->stock_actual - $reservasActivas);
    }

    public function tieneImagenes(): bool
    {
        return $this->imagenes()->exists();
    }
}