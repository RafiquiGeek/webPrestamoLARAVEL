<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provincia extends Model
{
    use HasFactory;

    /**
     * Obtiene el departamento al que pertenece la provincia
     */
    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    /**
     * Obtiene los distritos que pertenecen a la provincia
     */
    public function distritos(): HasMany
    {
        return $this->hasMany(Distrito::class);
    }

    /**
     * Obtiene las sucursales que pertenecen a la provincia
     */
    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }
}
