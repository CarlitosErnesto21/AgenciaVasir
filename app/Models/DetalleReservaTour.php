<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleReservaTour extends Model
{
    use HasFactory;
    
    protected $table = 'detalles_reservas_tours';
    
    protected $fillable = [
        'cupos_reservados',
        'precio_unitario',
        'precio_total',
        'reserva_id',
        'tour_id'
    ];
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }
    public function tour()
    {
        return $this->belongsTo(Tour::class, 'tour_id');
    }
}
