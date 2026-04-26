<?php

namespace App\Enums;

enum MoraConvenioEstado: string
{
    case PENDIENTE = 'pendiente';
    case PARCIAL = 'parcial';
    case PAGADO = 'pagado';
    case REGULARIZADA = 'regularizada';
    case ANULADO = 'anulado';

    /**
     * Obtener el nombre legible del estado
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::PARCIAL => 'Parcial',
            self::PAGADO => 'Pagada',
            self::REGULARIZADA => 'Regularizada',
            self::ANULADO => 'Anulada',
        };
    }

    /**
     * Obtener la clase CSS del badge
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PENDIENTE => 'bg-danger',
            self::PARCIAL => 'bg-warning',
            self::PAGADO => 'bg-success',
            self::REGULARIZADA => 'bg-primary',
            self::ANULADO => 'bg-secondary',
        };
    }

    /**
     * Verificar si la mora está activa (no anulada ni regularizada)
     */
    public function isActiva(): bool
    {
        return !in_array($this, [self::ANULADO, self::REGULARIZADA]);
    }

    /**
     * Verificar si la mora tiene saldo pendiente
     */
    public function tieneSaldo(): bool
    {
        return in_array($this, [self::PENDIENTE, self::PARCIAL]);
    }
}
