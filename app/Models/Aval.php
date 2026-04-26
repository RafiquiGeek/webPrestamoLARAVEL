<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aval extends Model
{
    use HasFactory;

    protected $table = 'avales';

    protected $fillable = [
        'prestamo_id',
        'persona_id',
        'parentesco',
        'observaciones',
    ];

    /**
     * Obtiene el registro correspondiente a la persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /**
     * Obtiene el préstamo del cual la persona es aval
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }
}
