<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hotel extends Model
{
    protected $table = 'hoteles';

    use HasFactory;
    protected $fillable = [
        'nombre',
        'direccion',
        'descripcion',
        'provincia_id'
    ];    // referencias

    public function provincia()
    {
        return $this->belongsTo(Provincia::class);
    }

    public function imagenes()
    {
        return $this->morphMany(Imagen::class, 'imageable');
    }
}
