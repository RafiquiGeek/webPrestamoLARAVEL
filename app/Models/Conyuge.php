<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conyuge extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
        'cliente_id',
        'oficio',
        'direccion_trabajo',
        'referencia_direccion',
    ];

    /**
     * Obtiene el registro correspondiente a la persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Obtiene el cliente al que pertenece el cónyuge
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
