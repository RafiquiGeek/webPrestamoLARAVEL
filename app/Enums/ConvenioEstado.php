<?php

namespace App\Enums;

enum ConvenioEstado: int
{
    case ACTIVO = 0;
    case CUMPLIDO = 1;
    case INCUMPLIDO = 2;
    case CANCELADO = 3;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVO => 'Activo',
            self::CUMPLIDO => 'Cumplido',
            self::INCUMPLIDO => 'Incumplido',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVO => 'primary',
            self::CUMPLIDO => 'success',
            self::INCUMPLIDO => 'danger',
            self::CANCELADO => 'secondary',
        };
    }
}
