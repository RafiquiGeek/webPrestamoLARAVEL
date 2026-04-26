<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Direccion extends Model
{
    use HasFactory;

    protected $table = 'direcciones';

    protected $fillable = [
        'persona_id',
        'distrito_id',
        'sucursal_id',
        'zona_id',
        'direccion',
        'numero',
        'referencia',
        'material_inmueble',
        'cant_pisos',
        'tipo_residencia',
        'tiempo_residencia',
        'anios_meses',
        'nombre_propietario',
        'telefono_propietario',
        'tipo_direccion',
        'latitud',
        'longitud',
        'estado',
    ];

    /**
     * Obtiene la persona la que le pertenece la dirección
     */
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id', 'id');
    }

    /**
     * Obtiene el distrito a la que pertenece la dirección
     */
    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }

    /**
     * Obtiene la sucursal a la que pertenece la dirección
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Obtiene la zona a la que pertenece la dirección
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    // Modelo Direccion
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'persona_id');  // 'persona_id' es la clave foránea
    }
}
