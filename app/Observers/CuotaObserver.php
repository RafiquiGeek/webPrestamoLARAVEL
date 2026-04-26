<?php

namespace App\Observers;

use App\Models\Cuota;

class CuotaObserver
{
    /**
     * Handle the Cuota "created" event.
     */
    public function created(Cuota $cuota)
    {
        if ($cuota->estado == 0 && $cuota->fecha_pago < now()) {
            // Crear una alerta de tipo 'warning' para una cuota vencida
        }
    }

    // Otros métodos del Observer si es necesario
}
