<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Departamento;

class DepartamentoController extends Controller
{
    public function index()
    {
        $departamentos = Departamento::orderBy('departamento')->get(['id', 'departamento as nombre']);

        return response()->json($departamentos);
    }

    public function provincias(Departamento $departamento)
    {
        $provincias = $departamento->provincias()->orderBy('provincia')->get(['id', 'provincia as nombre']);

        return response()->json($provincias);
    }
}
