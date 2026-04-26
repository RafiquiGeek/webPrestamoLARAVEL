<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plazo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tiempo',
        'unidad_tiempo',
    ];

    /**
     * Obtiene las relaciones de plazos por tasas asociadas a este plazo
     */
    public function tasas()
    {
        return $this->belongsToMany(Tasa::class, 'plazos_by_tasas', 'plazo_id', 'tasa_id');
    }

    public function plazosByTasa()
    {
        return $this->hasMany(PlazoByTasa::class);
    }
}
