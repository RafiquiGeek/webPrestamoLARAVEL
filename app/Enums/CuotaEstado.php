<?php

namespace App\Enums;

enum CuotaEstado: int
{
    case PENDIENTE = 0;
    case PARCIAL = 1;
    case PAGADO = 2;
    case VENCIDO = 3;
}
