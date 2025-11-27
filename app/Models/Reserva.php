<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    // Estados de la reserva
    const PENDIENTE = 'PENDIENTE';
    const CONFIRMADA = 'CONFIRMADA';
    const EN_CURSO = 'EN_CURSO';
    const FINALIZADA = 'FINALIZADA';
    const CANCELADA = 'CANCELADA';
    const REPROGRAMADA = 'REPROGRAMADA';

    const ESTADOS = [
        self::PENDIENTE,
        self::CONFIRMADA,
        self::EN_CURSO,
        self::FINALIZADA,
        self::CANCELADA,
        self::REPROGRAMADA
    ];

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
        'fecha' => 'datetime',
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

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'reserva_id');
    }
}
