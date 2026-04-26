<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Zona;

class ZonaController extends Controller
{
    public function index()
    {
        $zonas = Zona::all(['id', 'nombre']);

        return response()->json($zonas);
    }

    public function sucursales($zona_id)
    {
        $zona = Zona::find($zona_id);

        if (!$zona) {
            return response()->json([]);
        }

        // Obtener sucursales y eliminar duplicados por ID
        $sucursales = $zona->sucursales
            ->unique('id') // Eliminar duplicados por ID
            ->sortBy('sucursal') // Ordenar por nombre
            ->values() // Reindexar array
            ->map(function ($sucursal) {
                return [
                    'id' => $sucursal->id,
                    'sucursal' => $sucursal->sucursal,
                    'nombre' => $sucursal->sucursal, // Alias para compatibilidad
                ];
            });

        return response()->json($sucursales);
    }

    //traer zonas con sucursales
    public function zonasConSucursales()
    {
        $zonas = Zona::with([
            'sucursales' => function ($query) {
                $query->select('sucursales.id', 'sucursales.sucursal');
            }
        ])->get(['id', 'nombre']);

        // Eliminar datos pivot
        $zonas->each(function ($zona) {
            $zona->sucursales->makeHidden('pivot');
        });

        return response()->json($zonas);
    }
}