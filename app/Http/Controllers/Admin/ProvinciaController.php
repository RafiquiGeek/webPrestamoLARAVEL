<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Provincia;

class ProvinciaController extends Controller
{
    public function distritos(Provincia $provincia)
    {
        $distritos = $provincia->distritos;

        return response()->json($distritos);
    }
}
