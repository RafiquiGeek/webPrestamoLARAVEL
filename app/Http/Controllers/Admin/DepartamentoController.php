<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Departamento;

class DepartamentoController extends Controller
{
    public function provincias(Departamento $departamento)
    {
        $provincias = $departamento->provincias;

        return response()->json($provincias);
    }
}
