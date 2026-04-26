<?php

namespace App\Livewire\Solicitudes;

use App\Models\Prestamo;
use Livewire\Component;
use Livewire\WithPagination;

class ShowPrestamos extends Component
{
    use WithPagination;

    public $search = '';

    public $sort = 'id';

    public $direction = 'desc';

    public $selectedOption = '';

    public function updateSearch($value)
    {
        $this->search = $value;
        $this->selectedOption = $value;
    }

    public function render()
    {
        // Contar el número de préstamos por estado
        $cant_aprobado = Prestamo::where('estado', 'Aprobado')->count();
        $cant_analisis = Prestamo::where('estado', 'En Análisis')->count();
        $cant_finalizado = Prestamo::where('estado', 'Finalizado')->count();

        // Filtrar préstamos
        $prestamos = Prestamo::where(function ($query) {
            $query->where('estado', 'like', '%'.$this->search.'%')
                ->orWhereHas('cliente.persona', function ($q) {
                    // Usar las columnas correctas de la tabla 'personas'
                    $q->where('nombres', 'like', '%'.$this->search.'%')
                        ->orWhere('ape_pat', 'like', '%'.$this->search.'%')
                        ->orWhere('documento', 'like', '%'.$this->search.'%');
                });
        })
            ->with('cliente.persona')
            ->orderBy($this->sort, $this->direction)
            ->paginate(10);

        return view('livewire.solicitudes.show-prestamos', [
            'prestamos' => $prestamos,
            'cant_aprobado' => $cant_aprobado,
            'cant_analisis' => $cant_analisis,
            'cant_finalizado' => $cant_finalizado,
        ]);
    }

    public function order($sort)
    {
        if ($this->sort === $sort) {
            $this->direction = $this->direction === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sort = $sort;
            $this->direction = 'desc';
        }
    }

    public function desembolsarPrestamo($prestamoId)
    {
        $prestamo = Prestamo::find($prestamoId);

        if ($prestamo) {
            $prestamo->estado = 'Vigente';
            $prestamo->save();
            $this->dispatchBrowserEvent('prestamoDesembolsado', [
                'icon' => 'success',
                'title' => 'Tarea Realizada',
                'text' => 'Préstamo desembolsado exitosamente',
            ]);
        } else {
            $this->dispatchBrowserEvent('prestamoDesembolsado', [
                'icon' => 'error',
                'title' => 'Tarea Fallida',
                'text' => 'No se pudo desembolsar el préstamo',
            ]);
        }
    }
}
