<?php

namespace App\Livewire\Prestamos\EstadoCuenta;

use App\Models\Gestion;
use App\Models\Prestamo;
use Livewire\Component;

class TablaDinamica extends Component
{
    public $tabla = '1';

    public $activeButton = '1';

    public $gestiones = []; // Declarar la propiedad gestiones

    public $cuotas = []; // Declarar la propiedad cuotas

    public $id;

    // Método para mostrar la tabla según el botón activo
    public function mostrarTabla($tabla)
    {
        $this->tabla = $tabla; // Cambiar la tabla activa
        $this->activeButton = $tabla; // Cambiar el botón activo
    }

    // Método para asignar el ID del préstamo
    public function prestamo($id)
    {
        $this->id = $id;
    }

    // Método que renderiza la vista
    public function render()
    {
        // Verificar si se ha establecido un ID de préstamo
        if ($this->id) {
            // Encuentra el préstamo por ID
            $prestamo = Prestamo::find($this->id);

            // Verificar que el préstamo existe
            if ($prestamo) {
                // Cargar las cuotas relacionadas al préstamo
                $this->cuotas = $prestamo->cuotas;

                // Cargar las gestiones relacionadas con el préstamo
                $this->gestiones = Gestion::where('prestamo_id', $this->id)->with('cliente')->get();
            } else {
                // Si no hay préstamo, inicializamos las cuotas y gestiones como arrays vacíos
                $this->cuotas = [];
                $this->gestiones = [];
            }
        }

        // Pasar los datos a la vista
        return view('livewire.prestamos.estado-cuenta.tabla-dinamica', [
            'prestamo' => $prestamo ?? null,
            'gestiones' => $this->gestiones,
            'cuotas' => $this->cuotas,
        ]);
    }
}
