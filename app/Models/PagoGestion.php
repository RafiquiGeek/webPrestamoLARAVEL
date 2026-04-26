<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoGestion extends Model
{
    use HasFactory;

    protected $table = 'pagos_gestiones';

    protected $fillable = [
        'gestion_id',
        'prestamo_id',
        'monto_pagado',
        'tipo_pago',
        'detalle_cuotas',
        'detalle_moras',
        'metodo_pago',
        'observaciones',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'detalle_cuotas' => 'array',
        'detalle_moras' => 'array',
    ];

    // Constantes para tipos de pago
    const TIPO_CUOTA = 'cuota';

    const TIPO_MORA = 'mora';

    const TIPO_MIXTO = 'mixto';

    // Constantes para métodos de pago
    const METODO_EFECTIVO = 'efectivo';

    const METODO_TRANSFERENCIA = 'transferencia';

    const METODO_DEPOSITO = 'deposito';

    const METODO_OTRO = 'otro';

    /**
     * Relación con la gestión que registró el pago
     */
    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    /**
     * Relación con el préstamo al que se aplica el pago
     */
    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class);
    }
    /**
     * Obtiene el texto del tipo de pago
     */
    public function getTipoPagoTextoAttribute(): string
    {
        return match ($this->tipo_pago) {
            self::TIPO_CUOTA => 'Pago de Cuotas',
            self::TIPO_MORA => 'Pago de Moras',
            self::TIPO_MIXTO => 'Pago Mixto (Cuotas + Moras)',
            default => 'Desconocido'
        };
    }

    /**
     * Obtiene el texto del método de pago
     */
    public function getMetodoPagoTextoAttribute(): string
    {
        return match ($this->metodo_pago) {
            self::METODO_EFECTIVO => 'Efectivo',
            self::METODO_TRANSFERENCIA => 'Transferencia',
            self::METODO_DEPOSITO => 'Depósito',
            self::METODO_OTRO => 'Otro',
            default => 'Desconocido'
        };
    }

    /**
     * Obtiene el total de cuotas pagadas (si aplica)
     */
    public function getMontoCuotasAttribute(): float
    {
        if (! $this->detalle_cuotas || ! is_array($this->detalle_cuotas)) {
            return 0;
        }

        return collect($this->detalle_cuotas)->sum('monto_pagado') ?? 0;
    }

    /**
     * Obtiene el total de moras pagadas (si aplica)
     */
    public function getMontoMorasAttribute(): float
    {
        if (! $this->detalle_moras || ! is_array($this->detalle_moras)) {
            return 0;
        }

        return collect($this->detalle_moras)->sum('monto_pagado') ?? 0;
    }

    /**
     * Scope para pagos de cuotas únicamente
     */
    public function scopeSoloCuotas($query)
    {
        return $query->where('tipo_pago', self::TIPO_CUOTA);
    }

    /**
     * Scope para pagos de moras únicamente
     */
    public function scopeSoloMoras($query)
    {
        return $query->where('tipo_pago', self::TIPO_MORA);
    }

    /**
     * Scope para pagos mixtos
     */
    public function scopeMixtos($query)
    {
        return $query->where('tipo_pago', self::TIPO_MIXTO);
    }

    /**
     * Scope para pagos por método
     */
    public function scopePorMetodo($query, $metodo)
    {
        return $query->where('metodo_pago', $metodo);
    }
}
