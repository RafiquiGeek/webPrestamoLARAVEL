<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laboral extends Model
{
    use HasFactory;

    protected $table = 'laborales';

    protected $fillable = [
        'cliente_id',
        'actividad_economica',
        'nombre_lugar_trabajo',
        'direccion',
        'cargo',
        'status',
    ];

    /**
     * Obtiene el cliente al que le pertenece el dato laboral
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
