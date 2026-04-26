<?php

namespace App\Livewire\Solicitudes;

use App\Models\Solicitud;
use Livewire\Component;

class ShowSolicitudes extends Component
{
    public $search;

    public $sort = 'id';

    public $direction = 'desc';

    public $selectedOption = 'Aprobado';

    public function updateSearch($value)
    {
        $this->search = $value;
        $this->selectedOption = $value;
    }

    public function render()
    {
        $cant_aprobado = Solicitud::where('estado', 'Aprobado')->count();
        $cant_analisis = Solicitud::where('estado', 'En Analisis')->count();
        $cant_espera = Solicitud::where('estado', 'En Espera')->count();
        $cant_finalizado = Solicitud::where('estado', 'Finalizado')->count();

        $solicitudes = Solicitud::where('id', 'like', '%'.$this->search.'%')
            ->orWhere('nombre_cliente', 'like', '%'.$this->search.'%')
            ->orWhere('estado', 'like', '%'.$this->search.'%')
            ->orWhereHas('cliente', function ($query) {
                $query->where('documento', 'like', '%'.$this->search.'%');
            })
            ->with('cliente')
            ->orderBy($this->sort, $this->direction)
            ->paginate(10);

        return view('livewire.solicitudes.show-solicitudes', compact('solicitudes', 'cant_aprobado', 'cant_analisis', 'cant_espera', 'cant_finalizado'));
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
