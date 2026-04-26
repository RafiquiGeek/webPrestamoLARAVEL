<?php

namespace App\Models;

use App\Enums\MoraConvenioEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoraConvenio extends Model
{
    use HasFactory;

    protected $table = 'moras_convenio';

    protected $fillable = [
        'cuota_convenio_id',
        'fecha',
        'dias_mora',
        'monto',
        'monto_pagado',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'dias_mora' => 'integer',
        'estado' => MoraConvenioEstado::class,
    ];
    
    /**
     * Mutador para normalizar valores legacy de estado antes de guardar
     */
    public function setEstadoAttribute($value)
    {
        // Convertir valores numéricos legacy a strings del enum
        $estadoMap = [
            0 => 'pendiente',
            1 => 'parcial',
            2 => 'pagado',
            3 => 'regularizada',
            4 => 'anulado',
            '0' => 'pendiente',
            '1' => 'parcial',
            '2' => 'pagado',
            '3' => 'regularizada',
            '4' => 'anulado',
        ];

        // Si es un valor numérico legacy, convertirlo
        if (isset($estadoMap[$value])) {
            $value = $estadoMap[$value];
        }

        // Si es un enum, obtener su valor
        if ($value instanceof MoraConvenioEstado) {
            $value = $value->value;
        }

        $this->attributes['estado'] = $value;
    }
    
    /**
     * Accesor para manejar valores legacy al leer de BD
     */
    public function getEstadoAttribute($value)
    {
        // Convertir valores numéricos legacy a strings del enum
        $estadoMap = [
            0 => 'pendiente',
            1 => 'parcial',
            2 => 'pagado',
            3 => 'regularizada',
            4 => 'anulado',
            '0' => 'pendiente',
            '1' => 'parcial',
            '2' => 'pagado',
            '3' => 'regularizada',
            '4' => 'anulado',
        ];

        // Si es un valor numérico legacy, convertirlo
        if (isset($estadoMap[$value])) {
            $value = $estadoMap[$value];
        }

        try {
            return MoraConvenioEstado::from($value);
        } catch (\ValueError $e) {
            // Si falla, devolver PENDIENTE por defecto
            return MoraConvenioEstado::PENDIENTE;
        }
    }

    /**
     * Relación con la cuota de convenio
     */
    public function cuotaConvenio(): BelongsTo
    {
        return $this->belongsTo(CuotaConvenioModel::class, 'cuota_convenio_id');
    }

    /**
     * Accesor para obtener el saldo pendiente de esta mora
     */
    public function getSaldoAttribute(): float
    {
        return max(0, $this->monto - $this->monto_pagado);
    }
}
