<?php

namespace App\Models;

use App\Enums\CuotaConvenio as CuotaConvenioEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuotaConvenioModel extends Model
{
    use HasFactory;

    protected $table = 'cuotas_convenio';

    protected $fillable = [
        'convenio_id',
        'numero_cuota',
        'monto_cuota',
        'fecha_vencimiento',
        'fecha_pago',
        'monto_pagado',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'monto_cuota' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'estado' => CuotaConvenioEstado::class,
    ];

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class);
    }

    public function moras(): HasMany
    {
        return $this->hasMany(MoraConvenio::class, 'cuota_convenio_id');
    }

    public function abonosMoraFavor(): HasMany
    {
        return $this->hasMany(AbonoMoraFavorConvenio::class, 'cuota_convenio_id');
    }

    /**
     * Calcula el monto pagado basándose en las operaciones activas (no anuladas)
     */
    public function getMontoPagadoCalculadoAttribute(): float
    {
        // Obtener el convenio_id desde la relación
        $convenioId = $this->convenio_id;

        // Calcular el total pagado desde las operaciones activas
        $totalPagado = \App\Models\Operacion::where('prestamo_id', $this->convenio->prestamo_id)
            ->where('tipo_operacion', 'PAGO_CONVENIO')
            ->where('estado', '!=', 'anulado')
            ->where(function($query) {
                $query->where('comentario', 'LIKE', '%cuota #' . $this->numero_cuota . ' %')
                      ->orWhere('comentario', 'LIKE', '%cuota #' . $this->numero_cuota . ')%');
            })
            ->sum('abono');

        return $totalPagado ?? 0;
    }

    public function getSaldoPendienteAttribute(): float
    {
        // Usar el monto pagado almacenado en la base de datos por defecto
        // pero si estamos en una vista donde necesitamos recalcular, usar getMontoPagadoCalculadoAttribute
        return $this->monto_cuota - ($this->monto_pagado ?? 0);
    }

    /**
     * Recalcula el monto pagado basándose en las operaciones activas
     */
    public function recalcularMontoPagado(): void
    {
        $montoPagadoReal = $this->monto_pagado_calculado;

        $this->monto_pagado = $montoPagadoReal;

        // Actualizar el estado de la cuota
        if ($montoPagadoReal >= $this->monto_cuota) {
            $this->estado = CuotaConvenioEstado::PAGADO;
        } elseif ($montoPagadoReal > 0) {
            $this->estado = CuotaConvenioEstado::PARCIAL;
        } elseif ($this->es_vencida) {
            $this->estado = CuotaConvenioEstado::VENCIDO;
        } else {
            $this->estado = CuotaConvenioEstado::PENDIENTE;
        }

        $this->saveQuietly();
    }

    public function getEsVencidaAttribute(): bool
    {
        return $this->fecha_vencimiento < now()->toDateString() &&
               $this->estado !== CuotaConvenioEstado::PAGADO;
    }

    public function marcarComoPagada(float $monto, ?string $fecha_pago = null): void
    {
        $this->update([
            'monto_pagado' => $monto,
            'fecha_pago' => $fecha_pago ?? now()->toDateString(),
            'estado' => $monto >= $this->monto_cuota ?
                       CuotaConvenioEstado::PAGADO :
                       CuotaConvenioEstado::PARCIAL,
        ]);
    }

    public function marcarComoVencida(): void
    {
        if ($this->estado === CuotaConvenioEstado::PENDIENTE && $this->es_vencida) {
            $this->update(['estado' => CuotaConvenioEstado::VENCIDO]);
        }
    }
}
