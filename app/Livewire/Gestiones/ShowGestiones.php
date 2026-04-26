<?php

namespace App\Livewire\Gestiones;

use App\Models\Gestion;
use Livewire\Component;

class ShowGestiones extends Component
{
    public $search;

    public $sort = 'id';

    public $direction = 'desc';

    public function render()
    {
        // Consulta ajustada para buscar el nombre del cliente a través de la relación 'cliente'
        $gestiones = Gestion::with('cliente')
            ->whereHas('cliente', function ($query) {
                $query->where('nombre', 'like', '%'.$this->search.'%'); // Ajusta el campo 'nombre' según la estructura real de la tabla clientes
            })
            ->orWhere('estado', 'like', '%'.$this->search.'%')
            ->orderBy($this->sort, $this->direction); // Paginación

        return view('livewire.gestiones.show-gestiones', compact('gestiones'));
    }

    public function order($sort)
    {
        if ($this->sort == $sort) {
            $this->direction = $this->direction == 'desc' ? 'asc' : 'desc';
        } else {
            $this->sort = $sort;
            $this->direction = 'desc';
        }
    }
}
