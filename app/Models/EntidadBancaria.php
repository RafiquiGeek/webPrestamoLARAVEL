<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntidadBancaria extends Model
{
    use HasFactory;

    protected $table = 'entidades_bancarias'; // Asumiendo que tu tabla se llama 'entidades_bancarias'

    protected $fillable = [
        'banco',
        'status',
    ];

    /**
     * Obtiene las cuentas de la entidad bancaria (de parte de Santiago)
     */
    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    /**
     * Obtiene las cuentas de la entidad bancaria (de parte del cliente)
     */
    public function cuentaCliente(): HasMany
    {
        return $this->hasMany(CuentaCliente::class);
    }
}
