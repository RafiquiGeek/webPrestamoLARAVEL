<?php

namespace App\Livewire\Solicitudes\Calcular;

use Livewire\Component;

class Interes extends Component
{
    public $plazo;

    public $tasaInteres;

    public function updatedPlazo($value)
    {
        switch ($value) {
            case '12 semanas':
                $this->tasaInteres = 104;
                break;
            case '15 semanas':
                $this->tasaInteres = 130; // Cambia este valor al que necesites
                break;
            case '18 semanas':
                $this->tasaInteres = 156; // Cambia este valor al que necesites
                break;
            case '20 semanas':
                $this->tasaInteres = 173; // Cambia este valor al que necesites
                break;
            default:
                $this->tasaInteres = '';
        }
    }

    public function render()
    {
        return view('livewire.solicitudes.calcular.interes');
    }
}
