<?php

namespace App\Models;

use App\Enums\MoraCuotaEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MoraCuota extends Model
{
    use HasFactory;

    protected $table = 'mora_cuota';

    protected $fillable = [
        'cuota_id',
        'fecha',
        'dias_mora',
        'monto',
        'monto_pagado',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'estado' => MoraCuotaEstado::class,
    ];

    /**
     * Accesor para obtener el nombre legible del estado.
     */
    public function getEstadoNombreAttribute(): string
    {
        return match ($this->estado) {
            MoraCuotaEstado::PENDIENTE => 'Pendiente',
            MoraCuotaEstado::PARCIAL => 'Parcial',
            MoraCuotaEstado::PAGADO => 'Pagada',
            MoraCuotaEstado::REGULARIZADA => 'Regularizada',
        };
    }

    /**
     * Accesor para obtener la clase de color del badge.
     */
    public function getEstadoClassAttribute(): string
    {
        return match ($this->estado) {
            MoraCuotaEstado::PENDIENTE => 'bg-danger',
            MoraCuotaEstado::PARCIAL => 'bg-warning text-dark',
            MoraCuotaEstado::PAGADO => 'bg-success',
            MoraCuotaEstado::REGULARIZADA => 'bg-info',
            default => 'bg-secondary'
        };
    }

    /**
     * Accesor para obtener el monto total pagado de esta mora.
     * UNIFICADO: Siempre usa el campo 'monto_pagado' de la base de datos
     * que debe mantenerse actualizado por el EstadoPrestamoService
     */
    public function getMontoPagadoAttribute(): float
    {
        // Usar siempre el campo de la base de datos
        return (float) (isset($this->attributes['monto_pagado']) ? $this->attributes['monto_pagado'] : 0.00);
    }

    /**
     * Método para recalcular y actualizar el monto pagado desde operaciones
     * Solo debe ser usado por EstadoPrestamoService
     */
    public function recalcularMontoPagado(): float
    {
        $montoCalculado = $this->operaciones()
            ->where('estado', '!=', 'anulado')
            ->sum('abono');

        $this->update(['monto_pagado' => $montoCalculado]);

        return $montoCalculado;
    }

    /**
     * Accesor para obtener el saldo pendiente de esta mora.
     */
    public function getSaldoAttribute(): float
    {
        return max(0, $this->monto - $this->monto_pagado);
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class);
    }

    public function operaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            Operacion::class,
            'operacion_mora',
            'mora_cuota_id',
            'operacion_id'
        );
    }

    public function prestamo()
    {
        return $this->cuota ? $this->cuota->prestamo : null;
    }

    /**
     * Verifica si esta mora tiene operaciones que han sido editadas
     */
    public function tieneOperacionesEditadas(): bool
    {
        return $this->operaciones()
            ->whereNotNull('editado_en')
            ->exists();
    }

    /**
     * Verifica si esta mora tiene operaciones que han sido anuladas
     */
    public function tieneOperacionesAnuladas(): bool
    {
        return $this->operaciones()
            ->where('estado', 'anulado')
            ->exists();
    }

    /**
     * Obtiene las etiquetas de estado para mostrar en la interfaz
     */
    public function getEtiquetasEstado(): array
    {
        $etiquetas = [];

        if ($this->tieneOperacionesEditadas()) {
            $etiquetas[] = [
                'texto' => 'EDITADA',
                'clase' => 'badge badge-warning badge-sm',
                'titulo' => 'Esta mora tiene operaciones que han sido editadas',
            ];
        }

        if ($this->tieneOperacionesAnuladas()) {
            $etiquetas[] = [
                'texto' => 'ANULADA',
                'clase' => 'badge badge-danger badge-sm',
                'titulo' => 'Esta mora tiene operaciones que han sido anuladas',
            ];
        }

        return $etiquetas;
    }
}
