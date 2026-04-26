<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ValidacionesController extends Controller
{
    public function validarDNI(Request $request)
    {
        $request->validate([
            'documento' => 'required|digits:8|unique:clientes',
        ]);

        return response()->json(['mensaje' => 'DNI válido']);
    }

    public function validarZona(Request $request)
    {
        $request->validate([
            'zona' => [
                'required',
                Rule::unique('zonas')->where(function ($query) use ($request) {
                    return $query->where('distrito_id', $request->distrito_id);
                }),
            ],
        ]);

        return response()->json(['mensaje' => 'Zona válida']);
    }

    public function validarTipoZona(Request $request)
    {
        $request->validate([
            'tipo' => 'unique:zonas',
        ]);

        return response()->json(['mensaje' => 'Tipo de zona válida']);
    }
}
