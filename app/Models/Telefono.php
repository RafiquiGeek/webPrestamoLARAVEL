<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telefono extends Model
{
    use HasFactory;

    protected $fillable = [
        'persona_id',
        'tipo_telefono',
        'numero',
        'comentario',
    ];

    /**
     * Obtiene la persona a la que le pertenece el teléfono
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }
}
