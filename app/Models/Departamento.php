<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    use HasFactory;

    /**
     * Lista las provincias que le pertenecen al departamento
     */
    public function provincias(): HasMany
    {
        return $this->hasMany(Provincia::class);
    }
}
