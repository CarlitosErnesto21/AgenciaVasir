<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    protected $fillable=[
        'numero_identificacion',
        'fecha_nacimiento',
        'genero',
        'tipo_documento',
        'direccion',
        'telefono',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }

    // ✅ ACCESSOR: Obtener el nombre del usuario relacionado
    public function getNombreAttribute()
    {
        return $this->user ? $this->user->name : 'Cliente Sin Nombre';
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'cliente_id');
    }
}
