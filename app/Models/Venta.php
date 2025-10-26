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

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'venta_id');
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

    // ⭐ VALIDACIONES DE INTEGRIDAD CON PAGOS
    public function tienePagoAprobado(): bool
    {
        return $this->pagos()->where('estado', 'approved')->exists();
    }

    public function tienePagoPendiente(): bool
    {
        return $this->pagos()->where('estado', 'pending')->exists();
    }

    public function esValidaParaCompletarse(): bool
    {
        return $this->estaPendiente() && $this->tienePagoAprobado();
    }

    public function puedeSerCancelada(): bool
    {
        return $this->estaPendiente() && !$this->tienePagoAprobado();
    }

    // ⭐ SCOPES PARA CONSULTAS SEGURAS
    public function scopeConPagoAprobado($query)
    {
        return $query->whereHas('pagos', function($q) {
            $q->where('estado', 'approved');
        });
    }

    public function scopePendientesConPago($query)
    {
        return $query->where('estado', 'pendiente')
                    ->whereHas('pagos', function($q) {
                        $q->where('estado', 'pending');
                    });
    }

    public function scopeCompletadasValidas($query)
    {
        return $query->where('estado', 'completada')
                    ->whereHas('pagos', function($q) {
                        $q->where('estado', 'approved');
                    });
    }

    // ⭐ MÉTODOS AUXILIARES
    public function getPagoAprobado()
    {
        return $this->pagos()->where('estado', 'approved')->first();
    }

    public function getMontoTotalAttribute(): float
    {
        return (float) $this->total;
    }

    public function getEstadoLegibleAttribute(): string
    {
        $estados = [
            'pendiente' => 'Pendiente de Pago',
            'completada' => 'Completada',
            'cancelada' => 'Cancelada'
        ];

        return $estados[$this->estado] ?? 'Estado Desconocido';
    }

    // ⭐ VALIDACIÓN DE CONSISTENCIA
    public function validarConsistenciaConPagos(): bool
    {
        if ($this->estaCompletada() && !$this->tienePagoAprobado()) {
            return false; // Venta completada sin pago aprobado
        }

        if ($this->estaCancelada() && $this->tienePagoAprobado()) {
            return false; // Venta cancelada con pago aprobado
        }

        return true;
    }

    /**
     * ✅ COMPATIBILIDAD: Transformar relaciones para frontend
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Asegurar compatibilidad con frontend (snake_case)
        if (isset($array['detalleVentas'])) {
            $array['detalle_ventas'] = $array['detalleVentas'];
        }

        return $array;
    }
}
