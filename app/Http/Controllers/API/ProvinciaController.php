<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Provincia;

class ProvinciaController extends Controller
{
    public function distritos(Provincia $provincia)
    {
        $distritos = $provincia->distritos()->orderBy('distrito')->get(['id', 'distrito as nombre']);

        return response()->json($distritos);
    }
}
