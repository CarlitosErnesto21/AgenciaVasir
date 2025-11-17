<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    use HasFactory;

    protected $table = 'paises';
    
    protected $fillable = ['nombre'];

    // Normalizar el nombre automáticamente a MAYÚSCULAS
    public function setNombreAttribute($value)
    {
        // Normalizar: trim, convertir múltiples espacios a uno solo, y convertir a MAYÚSCULAS
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
        $this->attributes['nombre'] = $normalized;
    }

    public function provincias()
    {
        return $this->hasMany(Provincia::class, 'pais_id');
    }
}