<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCuenta extends Model
{
    use HasFactory;

    protected $table = 'tipos_cuenta';

    protected $fillable = [
        'tipo_cuenta',
        'status',
    ];

    /**
     * Obtiene la cuenta de cliente a la que pertenece el tipo de cuenta
     */
    public function cuentasCliente(): HasMany
    {
        return $this->hasMany(CuentaCliente::class);
    }
}
