<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\ComprobanteReintento;
use App\Services\SunatStatusService;
use Illuminate\Http\Request;

class SunatStatusController extends Controller
{
    protected $sunatStatusService;

    public function __construct(SunatStatusService $sunatStatusService)
    {
        $this->middleware('auth');
        $this->sunatStatusService = $sunatStatusService;
    }

    /**
     * Mostrar dashboard de estado de SUNAT
     */
    public function index()
    {
        // Obtener estado actual de SUNAT
        $estadoSunat = $this->sunatStatusService->verificarEstado();

        // Obtener estadísticas
        $estadisticas = $this->sunatStatusService->obtenerEstadisticas();

        // Obtener historial (últimas 24 horas)
        $historial = $this->sunatStatusService->obtenerHistorial();

        // Estadísticas de comprobantes (últimas 24 horas)
        $estadisticasComprobantes = [
            'total_24h' => Comprobante::where('created_at', '>=', now()->subDay())->count(),
            'exitosos_24h' => Comprobante::where('created_at', '>=', now()->subDay())
                ->whereIn('estado', ['ENVIADO', 'ACEPTADO'])->count(),
            'errores_24h' => Comprobante::where('created_at', '>=', now()->subDay())
                ->where('estado', 'ERROR')->count(),
            'pendientes_24h' => Comprobante::where('created_at', '>=', now()->subDay())
                ->where('estado', 'PENDIENTE')->count(),
        ];

        // Reintentos pendientes
        $reintentosPendientes = ComprobanteReintento::with('comprobante')
            ->where('estado', 'pendiente')
            ->orderBy('proximo_intento', 'asc')
            ->limit(10)
            ->get();

        // Comprobantes con errores recientes
        $comprobantesError = Comprobante::with(['cliente.persona'])
            ->where('estado', 'ERROR')
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.SunatStatus.index', compact(
            'estadoSunat',
            'estadisticas',
            'historial',
            'estadisticasComprobantes',
            'reintentosPendientes',
            'comprobantesError'
        ));
    }

    /**
     * API: Obtener estado actual de SUNAT
     */
    public function estado()
    {
        $estadoSunat = $this->sunatStatusService->verificarEstado();

        return response()->json($estadoSunat);
    }

    /**
     * API: Obtener historial
     */
    public function historial()
    {
        $historial = $this->sunatStatusService->obtenerHistorial();

        return response()->json($historial);
    }

    /**
     * API: Obtener estadísticas
     */
    public function estadisticas()
    {
        $estadisticas = $this->sunatStatusService->obtenerEstadisticas();

        return response()->json($estadisticas);
    }

    /**
     * Refrescar estado de SUNAT (limpiar caché)
     */
    public function refrescar()
    {
        $this->sunatStatusService->limpiarCache();
        $estadoSunat = $this->sunatStatusService->verificarEstado();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
            'estado' => $estadoSunat,
        ]);
    }
}
