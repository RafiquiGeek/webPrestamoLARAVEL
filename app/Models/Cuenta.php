<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    use HasFactory;

    protected $table = 'cuentas'; // Especificar nombre de tabla si es diferente de la convención

    protected $fillable = [
        'entidad_bancaria_id',
        'nro_cuenta',
        'codigo',
    ];

    /**
     * Obtiene la entidad bancaria a la que le pertenece la cuenta
     */
    public function entidadBancaria(): BelongsTo
    {
        return $this->belongsTo(EntidadBancaria::class);
    }

    /**
     * Obtiene los préstamos en los que se ha consignado la cuenta
     */
    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class); // Corregí "Prestamos" a "Prestamo"
    }

    // Opcional: Mutador para formatear el código
    public function getCodigoAttribute($value)
    {
        return strtoupper($value);
    }

    // Opcional: Scope para cuentas activas si manejas un campo de estado
    public function scopeActivas($query)
    {
        return $query->where('estado', 'activo');
    }
}
