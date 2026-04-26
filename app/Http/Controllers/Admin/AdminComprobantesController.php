<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SireHistorial;

class AdminComprobantesController extends Controller
{
    public function index(Request $request)
    {
        $tipo = $request->query('tipo', 'ventas');

        // Intentar cargar los últimos comprobantes desde la tabla `sire_historial`.
        try {
            $comprobantes = SireHistorial::ultimos(50);
        } catch (\Exception $e) {
            // Si no existe la tabla o hay error, devolver arreglo vacío y loggear
            logger()->warning('No se pudo leer sire_historial: ' . $e->getMessage());
            $comprobantes = [];
        }

        return view('admin.comprobantes.index', compact('tipo', 'comprobantes'));
    }

    // Métodos futuros: show, reenviar, filtrar, exportar
}
