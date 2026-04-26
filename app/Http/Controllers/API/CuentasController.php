<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EntidadBancaria;
use App\Models\BilleteraDigital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuentasController extends Controller
{

    //este trae los bancos para el cliente
    public function getBancos(): JsonResponse
    {
        try {
            $bancos = EntidadBancaria::where('status', 1)->orderBy('banco')->get(['id', 'banco']);
            return response()->json([
                'success' => true,
                'data' => $bancos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los bancos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //traer las billeteras digitales para el cliente
    public function getBilleterasDigitales(): JsonResponse
    {
        try {
            $billeteras = BilleteraDigital::where('status', 1)->orderBy('nombre')->get(['id', 'nombre']);
            return response()->json([
                'success' => true,
                'data' => $billeteras,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las billeteras digitales',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
