<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transporte extends Model
{
    use HasFactory;
    protected $table = 'transportes';
    protected $fillable = [
        'nombre',
        'capacidad',
    ];
    public function tours()
    {
        return $this->hasMany(Tour::class, 'transporte_id');
    }
}
