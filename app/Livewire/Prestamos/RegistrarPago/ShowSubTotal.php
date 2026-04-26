<?php

namespace App\Livewire\Prestamos\RegistrarPago;

use Livewire\Attributes\On;
use Livewire\Component;

class ShowSubTotal extends Component
{
    public $subtotal;

    #[On('actualizarSubtotal')]
    public function actualizarSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;
    }

    public function render()
    {
        return view('livewire.prestamos.registrar-pago.show-sub-total');
    }
}
