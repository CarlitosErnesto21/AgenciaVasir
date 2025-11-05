<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaqueteVisa extends Model
{
    use HasFactory;

    protected $table = 'paquetes_visas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'incluye',
        'no_incluye',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación polimórfica con imágenes
    public function imagenes()
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }
}
