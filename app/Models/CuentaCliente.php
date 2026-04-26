<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaCliente extends Model
{
    use HasFactory;

    protected $table = 'cuentas_cliente';

    protected $fillable = [
        'entidad_bancaria_id',
        'billetera_digital_id',
        'cliente_id',
        'tipo_cuenta_id',
        'numero_cuenta',
        'codigo',
        'titular_cuenta',
        'status',

    ];

    /**
     * Obtiene el cliente al que le pertenece la cuenta
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Obtiene la entidad bancaria a la que pertenece la cuenta
     */
    public function entidadBancaria(): BelongsTo
    {
        return $this->belongsTo(EntidadBancaria::class, 'entidad_bancaria_id');
    }

    /**
     * Obtiene la billetera digital a la que pertenece la cuenta
     */
    public function billeteraDigital(): BelongsTo
    {
        return $this->belongsTo(BilleteraDigital::class, 'billetera_digital_id', 'id');
    }

    /**
     * Obtiene el tipo de cuenta de la cuenta
     */
    public function tipoCuenta(): BelongsTo
    {
        return $this->belongsTo(TipoCuenta::class);
    }

    /**
     * Obtiene los préstamos en los que se ha consignado la cuenta
     */
    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class);
    }
}
