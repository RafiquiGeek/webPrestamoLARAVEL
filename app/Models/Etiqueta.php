<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etiqueta extends Model
{
    use HasFactory;

    protected $fillable = [
        'etiqueta',
        'color',
        'estado',
    ];

    /**
     * Obtiene las etiquetas asignadas a un cliente
     */
    public function etiquetasCliente(): HasMany
    {
        return $this->hasMany(EtiquetaCliente::class);
    }
}
