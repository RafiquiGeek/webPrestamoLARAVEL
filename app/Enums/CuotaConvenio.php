<?php

namespace App\Enums;

enum CuotaConvenio: int
{
    case PENDIENTE = 0;
    case PARCIAL = 1;
    case PAGADO = 2;
    case VENCIDO = 3;

    public function label(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::PARCIAL => 'Parcial',
            self::PAGADO => 'Pagado',
            self::VENCIDO => 'Vencido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDIENTE => 'warning',
            self::PARCIAL => 'info',
            self::PAGADO => 'success',
            self::VENCIDO => 'danger',
        };
    }
}
