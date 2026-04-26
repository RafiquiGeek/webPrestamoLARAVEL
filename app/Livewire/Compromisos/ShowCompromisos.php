<?php

namespace App\Livewire\Compromisos;

use App\Models\Gestion;
use Livewire\Component;

class ShowCompromisos extends Component
{
    public $search;

    public $sort = 'id';

    public $direction = 'desc';

    public function render()
    {
        // Filtra gestiones que tienen compromisos asociados
        $compromisos = Gestion::with('cliente', 'compromiso')
            ->whereHas('compromiso') // Solo busca gestiones con compromisos asociados
            ->where(function ($query) {
                $query->whereHas('cliente', function ($q) {
                    $q->where('nombre', 'like', '%'.$this->search.'%');
                })
                    ->orWhere('estado_id', 'like', '%'.$this->search.'%');
            })
            ->orderBy($this->sort, $this->direction); // Añadir paginación

        return view('livewire.compromisos.show-compromisos', compact('compromisos'));
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
