<?php

namespace App\Livewire\Tasas;

use App\Models\Tasa;
use Livewire\Component;

class ShowTasas extends Component
{
    public function render()
    {
        $tasas = Tasa::all();

        return view('livewire.tasas.show-tasas', compact('tasas'));
    }
}
