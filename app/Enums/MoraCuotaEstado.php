<?php

namespace App\Enums;

enum MoraCuotaEstado: int
{
    case PENDIENTE = 0;
    case PARCIAL = 1;
    case PAGADO = 2;
    case REGULARIZADA = 3;
}
