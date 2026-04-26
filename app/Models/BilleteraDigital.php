<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BilleteraDigital extends Model
{
    use HasFactory;

    protected $table = 'billeteras_digitales';

    protected $fillable = [
        'nombre',
        'status',
    ];

    /**
     * Obtiene las cuentas de la billetera digital (de parte del cliente)
     */
    public function cuentaCliente(): HasMany
    {
        return $this->hasMany(CuentaCliente::class);
    }
}
