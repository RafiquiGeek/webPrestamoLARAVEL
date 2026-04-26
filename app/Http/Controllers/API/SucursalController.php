<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;

class SucursalController extends Controller
{
    public function getLocation($sucursal_id)
    {
        $sucursal = Sucursal::with(['provincia.departamento'])->find($sucursal_id);

        if (! $sucursal) {
            return response()->json(['error' => 'Sucursal not found'], 404);
        }

        return response()->json([
            'departamento_id' => $sucursal->provincia->departamento->id,
            'departamento_nombre' => $sucursal->provincia->departamento->departamento,
            'provincia_id' => $sucursal->provincia->id,
            'provincia_nombre' => $sucursal->provincia->provincia,
        ]);
    }
}
