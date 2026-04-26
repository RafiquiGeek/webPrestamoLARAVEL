<?php

namespace App\Livewire\Sucursales;

use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;
use App\Models\Sucursal;
use Livewire\Component;

class ShowSucursales extends Component
{
    public function render()
    {
        $sucursales = Sucursal::with('departamento')->with('provincia')->with('distrito')->get();
        // $sucursal = Sucursal::find(1);
        // $departamento = $sucursal->departamento;;
        // echo $departamento;

        //echo $sucursales->departamento;

        // $departamentos = Departamento::all();
        // $provincias = Provincia::all();
        // $distritos = Distrito::all();

        return view('livewire.sucursales.show-sucursales', compact('sucursales'));
    }
}
