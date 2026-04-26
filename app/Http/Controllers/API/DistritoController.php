<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Distrito;

class DistritoController extends Controller
{
    public function zonas(Distrito $distrito)
    {
        $zona = $distrito->zona;

        return response()->json($zona);
    }
}
