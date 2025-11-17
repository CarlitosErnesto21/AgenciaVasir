<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    protected $table = 'provincias';
    
    use HasFactory;
    protected $fillable = ['nombre', 'pais_id'];
    
    // Normalizar el nombre automáticamente a MAYÚSCULAS
    public function setNombreAttribute($value)
    {
        // Normalizar: trim, convertir múltiples espacios a uno solo, y convertir a MAYÚSCULAS
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
        $this->attributes['nombre'] = $normalized;
    }
    
    // Relaciones pertenecen "a" o la inversa de la relación
    
    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }
}
