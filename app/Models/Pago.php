<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'reserva_id',
        'monto',
        'moneda',
        'metodo_pago',
        'email_cliente',
        'referencia_wompi',
        'wompi_transaction_id',
        'wompi_reference',
        'estado',
        'mensaje_error',
        'response_data'
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'response_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ===== RELACIONES =====

    /**
     * Relación con la venta
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /**
     * Relación con la reserva
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    // ===== MÉTODOS AUXILIARES =====

    /**
     * Verificar si el pago está aprobado
     */
    public function isApproved(): bool
    {
        return $this->estado === 'approved';
    }

    /**
     * Verificar si el pago está pendiente
     */
    public function isPending(): bool
    {
        return $this->estado === 'pending';
    }

    /**
     * Verificar si el pago fue rechazado
     */
    public function isDeclined(): bool
    {
        return $this->estado === 'declined';
    }

    /**
     * Verificar si hubo error en el pago
     */
    public function hasError(): bool
    {
        return in_array($this->estado, ['error', 'failed']);
    }

    /**
     * Obtener el nombre del cliente
     */
    public function getClientNameAttribute(): string
    {
        if ($this->venta_id && $this->venta) {
            return $this->venta->cliente->nombre ?? 'N/A';
        }

        if ($this->reserva_id && $this->reserva) {
            return $this->reserva->cliente->nombre ?? 'N/A';
        }

        return 'N/A';
    }

    /**
     * Obtener el tipo de transacción (venta o reserva)
     */
    public function getTransactionTypeAttribute(): string
    {
        if ($this->venta_id) {
            return 'Venta';
        }

        if ($this->reserva_id) {
            return 'Reserva';
        }

        return 'Desconocido';
    }

    /**
     * Obtener descripción del pago
     */
    public function getDescriptionAttribute(): string
    {
        if ($this->venta_id && $this->venta) {
            $productCount = $this->venta->detalleVentas->count();
            return "Venta de {$productCount} producto(s)";
        }

        if ($this->reserva_id && $this->reserva) {
            // Aquí podrías agregar más lógica según tu modelo de Reserva
            return "Reserva #{$this->reserva->id}";
        }

        return 'Pago genérico';
    }

    /**
     * Formatear el monto para mostrar
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->monto, 2, '.', ',') . ' ' . $this->moneda;
    }

    /**
     * Obtener el estado en formato legible
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pending' => 'Pendiente',
            'approved' => 'Aprobado',
            'declined' => 'Rechazado',
            'voided' => 'Anulado',
            'error' => 'Error',
            'failed' => 'Fallido'
        ];

        return $labels[$this->estado] ?? 'Desconocido';
    }

    /**
     * Scopes para consultas comunes
     */
    public function scopeApproved($query)
    {
        return $query->where('estado', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('estado', 'pending');
    }

    public function scopeForVentas($query)
    {
        return $query->whereNotNull('venta_id');
    }

    public function scopeForReservas($query)
    {
        return $query->whereNotNull('reserva_id');
    }

    /**
     * Obtener datos de respuesta de Wompi de forma segura
     */
    public function getWompiData($key = null)
    {
        if (!$this->response_data) {
            return null;
        }

        if ($key) {
            return $this->response_data[$key] ?? null;
        }

        return $this->response_data;
    }
}
