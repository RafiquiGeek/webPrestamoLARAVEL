<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoGestion extends Model
{
    use HasFactory;

    protected $table = 'estados_gestion';

    protected $fillable = [
        'estado',
        'calificacion',
    ];

    /**
     * Relación con el modelo Gestion.
     */
    public function gestiones()
    {
        return $this->hasMany(Gestion::class, 'estado_id');
    }
}
