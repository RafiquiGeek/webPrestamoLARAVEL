<?php

namespace App\Livewire\Cuentas;

use App\Models\Cuenta;
use Livewire\Component;

class ShowCuentas extends Component
{
    public function render()
    {
        $cuentas = Cuenta::with('entidadBancaria')->get();

        return view('livewire.cuentas.show-cuentas', compact('cuentas'));
    }
}
