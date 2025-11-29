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

    /**
     * ✅ NUEVO: Relación para obtener el pago activo (más relevante) de la reserva
     * Prioriza: 1) Pago aprobado más reciente, 2) Pago pendiente más reciente, 3) Cualquier pago más reciente
     */
    public function pagoActivo()
    {
        return $this->hasOne(Pago::class, 'reserva_id')
            ->selectRaw('*, CASE
                WHEN estado = "approved" THEN 1
                WHEN estado = "pending" THEN 2
                ELSE 3
            END as prioridad')
            ->orderByRaw('prioridad ASC, updated_at DESC');
    }

    /**
     * ✅ NUEVO: Accessor para obtener el estado del pago de forma sencilla
     */
    public function getEstadoPagoAttribute()
    {
        $pagoActivo = $this->pagoActivo;

        if (!$pagoActivo) {
            return 'sin_pago';
        }

        return $pagoActivo->estado;
    }

    /**
     * ✅ NUEVO: Accessor para saber si la reserva está pagada
     */
    public function getEstaPagadaAttribute()
    {
        return $this->estado_pago === 'approved';
    }
}
