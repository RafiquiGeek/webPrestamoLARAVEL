<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MetodoDePago;
use Illuminate\Http\Request;

class MetodoPagoController extends Controller
{
    public function index()
    {
        try {
            $metodos = MetodoDePago::activos()
                ->whereNotNull('metodo_pago')
                ->orderBy('id')
                ->get()
                ->map(function($metodo) {
                    return [
                        'id' => $metodo->id,
                        'nombre' => $metodo->metodo_pago ?? 'Sin nombre',
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $metodos,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métodos de pago',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
