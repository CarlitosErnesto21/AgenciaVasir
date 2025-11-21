<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;
    protected $fillable = [
        'fecha',
        'estado',
        'mayores_edad',
        'menores_edad',
        'total',
        'cliente_id',
        'empleado_id'
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2'
    ];
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detallesTours()
    {
        return $this->hasMany(DetalleReservaTour::class, 'reserva_id');
    }
}
