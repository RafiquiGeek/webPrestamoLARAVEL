<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoDePago extends Model
{
    use HasFactory;

    protected $table = 'metodos_de_pago';

    protected $fillable = [
        'metodo_pago',
        'status',
    ];

    /**
     * Accessor para obtener el nombre del método de pago
     */
    public function getNombreAttribute()
    {
        return $this->metodo_pago;
    }

    /**
     * Scope para obtener solo métodos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope para obtener todos los métodos (incluyendo inactivos)
     */
    public function scopeTodos($query)
    {
        return $query;
    }

    /**
     * Método estático para obtener todos los métodos de pago (debe haber 3)
     */
    public static function obtenerTodos()
    {
        return self::orderBy('id')->get();
    }

    /**
     * Método estático para obtener métodos activos (debe haber 3)
     */
    public static function obtenerActivos()
    {
        return self::where('status', 1)->orderBy('id')->get();
    }

    /**
     * Obtiene las operaciones que cuentan con un método de pago
     */
    public function operaciones(): HasMany
    {
        return $this->hasMany(Operacion::class);
    }
}
