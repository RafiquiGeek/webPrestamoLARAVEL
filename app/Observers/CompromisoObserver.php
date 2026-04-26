<?php

namespace App\Observers;

use App\Models\Compromiso;

class CompromisoObserver
{
    /**
     * Handle the Compromiso "created" event.
     */
    public function created(Compromiso $compromiso)
    {
        // Crear una alerta de tipo 'info' para un nuevo compromiso
    }

    // Otros métodos del Observer si es necesario
}
