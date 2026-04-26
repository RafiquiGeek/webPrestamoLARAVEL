<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distrito extends Model
{
    use HasFactory;

    /**
     * Obtiene la provincia a la que pertenece el distrito
     */
    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class);
    }

    /**
     * Obtiene un listado de las direcciones que hay en el distrito
     */
    public function direcciones(): HasMany
    {
        return $this->hasMany(Direccion::class);
    }
}
