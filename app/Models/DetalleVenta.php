<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    use HasFactory;
    protected $fillable = [
        'cantidad',
        'precio_unitario',
        'subtotal',
        'venta_id',
        'producto_id'
    ];
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }
}
