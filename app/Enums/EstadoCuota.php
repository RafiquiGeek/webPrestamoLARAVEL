<?php

namespace App\Enums;

enum EstadoCuota: string
{
    case PAGADO = 'pagado';
    case PENDIENTE = 'pendiente';
    case VENCIDO = 'vencido';
}
