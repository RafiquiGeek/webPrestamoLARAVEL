<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    /**
     * Relación muchos a muchos con Sucursal
     */
    public function sucursales()
    {
        return $this->belongsToMany(Sucursal::class, 'zona_sucursal')
            ->withPivot('created_at', 'updated_at')
            ->withTimestamps(false)
            ->distinct(); // Eliminar duplicados
    }
}
