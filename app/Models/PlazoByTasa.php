<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlazoByTasa extends Model
{
    use HasFactory;

    protected $table = 'plazos_by_tasas';

    protected $fillable = [
        'plazo_id',
        'tasa_id',
        'estado',
    ];

    /**
     * Obtiene el plazo al que corresponde esta relación
     */
    public function plazo()
    {
        return $this->belongsTo(Plazo::class);
    }

    public function tasa()
    {
        return $this->belongsTo(Tasa::class);
    }

    /**
     * Obtiene la lista de préstamos que usan esta combinación plazo-tasa
     */
    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class, 'plazo_by_tasa_id'); // Ajusta según tu modelo Prestamo
    }
}
