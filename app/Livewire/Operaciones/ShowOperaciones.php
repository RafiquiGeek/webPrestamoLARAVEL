<?php

namespace App\Livewire\Operaciones;

use Livewire\Component;

class ShowOperaciones extends Component
{
    public $search;

    public $sort = 'id';

    public $direction = 'desc';

    public function render()
    {
        $gestiones = Gestion::where('compromiso', 'like', '%'.$this->search.'%') // Cambia el nombre de la columna aquí
            ->orWhere('estado', 'like', '%'.$this->search.'%')
            ->orderBy($this->sort, $this->direction);

        return view('livewire.gestiones.show-gestiones', compact('gestiones'));
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
