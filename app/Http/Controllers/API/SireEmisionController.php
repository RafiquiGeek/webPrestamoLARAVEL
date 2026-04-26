<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\SireHistorial;
use App\Services\SireEmisionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para emitir comprobantes via SIRE
 */
class SireEmisionController extends Controller
{
    protected SireEmisionService $emisionService;

    public function __construct(SireEmisionService $emisionService)
    {
        $this->emisionService = $emisionService;
    }

    /**
     * Emitir comprobante para una cuota específica
     * 
     * POST /api/sire/emitir-cuota
     */
    public function emitirCuota(Request $request): JsonResponse
    {
        $request->validate([
            'cuota_id' => 'required|exists:cuotas,id',
        ]);

        $cuota = Cuota::find($request->cuota_id);

        if (!$cuota) {
            return response()->json([
                'success' => false,
                'message' => 'Cuota no encontrada',
            ], 404);
        }

        $result = $this->emisionService->emitirComprobanteParaCuota($cuota);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprobante emitido exitosamente',
            'historial_id' => $result['historial_id'],
        ]);
    }

    /**
     * Reenviar un comprobante ya emitido
     * 
     * POST /api/sire/reenviar/{historial_id}
     */
    public function reenviarComprobante(SireHistorial $historial): JsonResponse
    {
        $result = $this->emisionService->reenviarComprobante($historial);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprobante reenviado exitosamente',
        ]);
    }

    /**
     * Obtener comprobantes de una cuota
     * 
     * GET /api/sire/cuota/{cuota_id}/comprobantes
     */
    public function obtenerComprobantesDelCuota(Cuota $cuota): JsonResponse
    {
        $comprobantes = SireHistorial::where('metadata->cuota_id', $cuota->id)->get();

        return response()->json([
            'success' => true,
            'data' => $comprobantes,
        ]);
    }

    /**
     * Listar comprobantes (con filtros)
     * 
     * GET /api/sire/comprobantes
     */
    public function listarComprobantes(Request $request): JsonResponse
    {
        $query = SireHistorial::query();

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('cliente_numero_doc')) {
            $query->where('cliente_numero_doc', $request->cliente_numero_doc);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_emision', '<=', $request->fecha_hasta);
        }

        $comprobantes = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $comprobantes,
        ]);
    }
}
