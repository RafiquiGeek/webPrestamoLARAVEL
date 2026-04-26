<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Admin\DeudasController;
use Illuminate\Http\Request;

class DeudasApiController extends DeudasController
{
    /**
     * API: Obtener datos filtrados de deudas
     * 
     * Reutiliza el método getTramosData del controlador principal
     * sin hacer ninguna modificación al código existente
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeudasFiltradas(Request $request)
    {
        try {
            // El request necesita tener el parámetro 'per_page' para que funcione bien
            if (!$request->has('per_page')) {
                $request->merge(['per_page' => 100]);
            }

            // Llamar al método existente que ya tiene toda la lógica
            // IMPORTANTE: El método getTramosData retorna JSON automáticamente
            $response = $this->getTramosData($request);

            // Retornar la respuesta tal cual
            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al obtener datos filtrados'
            ], 500);
        }
    }

    /**
     * API: Obtener datos de deudas generales (usando el método index)
     * Retorna en formato JSON para consumo por aplicaciones externas
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeudas(Request $request)
    {
        try {
            // Agregar flag para que retorne JSON en lugar de view
            $request->merge(['api' => true]);

            // Llamar al método index que también procesa filtros
            $result = $this->index($request);

            // Si el resultado es una response JSON, devolverla directamente
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                return $result;
            }

            // Si es una view, convertir a JSON
            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al obtener deudas'
            ], 500);
        }
    }

    /**
     * API: Obtener resumen de estadísticas de deudas
     * Más ligero que la consulta completa
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEstadisticasDeudas(Request $request)
    {
        try {
            // Agregar límite bajo para solo obtener estadísticas
            $request->merge(['limit' => 100, 'stats_only' => true]);

            $response = $this->getTramosData($request);

            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Exportar datos filtrados (CSV, Excel, JSON)
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function exportarDeudasFiltradas(Request $request)
    {
        try {
            // Validar formato solicitado
            $formato = $request->input('formato', 'json'); // json, csv, excel
            
            if (!in_array($formato, ['json', 'csv', 'excel'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Formato no válido. Use: json, csv, excel'
                ], 400);
            }

            // Obtener todos los datos sin paginación
            $request->merge(['export_all' => true, 'limit' => 10000]);

            $response = $this->getTramosData($request);

            // Si es JSON, retornar tal cual
            if ($formato === 'json') {
                return $response;
            }

            // Para CSV y Excel, convertir la respuesta
            // (aquí puedes agregar lógica adicional si necesitas)
            return $response;

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
