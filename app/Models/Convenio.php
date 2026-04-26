<?php

namespace App\Models;

use App\Enums\ConvenioEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convenio extends Model
{
    use HasFactory;

    // Tipos de convenio
    const TIPO_CUOTAS = 'cuotas';
    const TIPO_FLEXIBLE = 'flexible';

    protected $fillable = [
        'prestamo_id',
        'tipo',
        'monto_capital',
        'monto_moras',
        'descuento_moras',
        'total_convenio',
        'numero_cuotas',
        'valor_cuota',
        'fecha_inicio',
        'fecha_firma',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'tipo' => 'string',
        'monto_capital' => 'decimal:2',
        'monto_moras' => 'decimal:2',
        'descuento_moras' => 'decimal:2',
        'total_convenio' => 'decimal:2',
        'valor_cuota' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_firma' => 'date',
        'estado' => ConvenioEstado::class,
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }

    public function cuotasConvenio(): HasMany
    {
        return $this->hasMany(CuotaConvenioModel::class, 'convenio_id');
    }

    public function cuotasPendientes(): HasMany
    {
        return $this->cuotasConvenio()->where('estado', \App\Enums\CuotaConvenio::PENDIENTE);
    }

    public function cuotasPagadas(): HasMany
    {
        return $this->cuotasConvenio()->where('estado', \App\Enums\CuotaConvenio::PAGADO);
    }

    public function cuotasVencidas(): HasMany
    {
        return $this->cuotasConvenio()->where('estado', \App\Enums\CuotaConvenio::VENCIDO);
    }

    /**
     * Relación con pagos de convenio flexible
     */
    public function pagosFlexibles(): HasMany
    {
        return $this->hasMany(PagoConvenioFlexible::class, 'convenio_id');
    }

    /**
     * Verifica si el convenio es de tipo cuotas (sistema actual)
     */
    public function esTipoCuotas(): bool
    {
        return $this->tipo === self::TIPO_CUOTAS;
    }

    /**
     * Verifica si el convenio es de tipo flexible (nuevo sistema)
     */
    public function esTipoFlexible(): bool
    {
        return $this->tipo === self::TIPO_FLEXIBLE;
    }

    /**
     * Monto total pagado (funciona para ambos tipos)
     */
    public function getMontoTotalPagadoAttribute(): float
    {
        if ($this->esTipoFlexible()) {
            return $this->pagosFlexibles->sum('monto');
        }

        // Tipo cuotas (lógica existente)
        return $this->cuotasPagadas->sum('monto_pagado');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return $this->total_convenio - $this->monto_total_pagado;
    }

    public function getPorcentajeAvanceAttribute(): float
    {
        if ($this->total_convenio == 0) {
            return 0;
        }

        return ($this->monto_total_pagado / $this->total_convenio) * 100;
    }
}
