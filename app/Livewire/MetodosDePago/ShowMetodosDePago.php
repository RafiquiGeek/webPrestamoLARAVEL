<?php

namespace App\Livewire\MetodosDePago;

use App\Models\MetodoPago;
use Livewire\Component;

class ShowMetodosDePago extends Component
{
    public $search;

    public $sort = 'id';

    public $direction = 'desc';

    public function render()
    {
        $metodos = MetodoPago::where('metodo_pago', 'like', '%'.$this->search.'%')
            ->orderBy($this->sort, $this->direction)
            ->paginate(10);

        return view('livewire.metodos-de-pago.show-metodos-de-pago', compact('metodos'));
    }

    public function order($sort)
    {
        if ($this->sort == $sort) {
            if ($this->direction == 'desc') {
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort = $sort;
            $this->direction = 'desc';
        }
    }
}
