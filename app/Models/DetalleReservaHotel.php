<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleReservaHotel extends Model
{
    use HasFactory;
    
    protected $table = 'detalles_reservas_hoteles';
    
    protected $fillable = [
        'fecha_entrada',
        'fecha_salida',
        'cantidad_persona',
        'cantidad_habitacion',
        'subtotal',
        'reserva_id',
        'hotel_id'
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }
}
