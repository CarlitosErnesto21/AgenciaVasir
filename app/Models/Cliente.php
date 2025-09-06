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
        'direccion',
        'telefono',
        'user_id', 
        'tipo_documento_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }
    public function ventas()
    {
        return $this->hasMany(Venta::class, 'cliente_id');
    }
}
