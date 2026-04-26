<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comprobante;
use App\Models\ComprobanteReintento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FacturacionDashboardController extends Controller
{
    /**
     * Mostrar dashboard de facturación electrónica
     */
    public function index(Request $request)
    {
        // Filtro de fecha (por defecto últimos 30 días)
        $fechaInicio = $request->input('fecha_inicio', now()->subDays(30)->format('Y-m-d'));
        $fechaFin = $request->input('fecha_fin', now()->format('Y-m-d'));

        // Convertir a Carbon
        $fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $fechaFin = Carbon::parse($fechaFin)->endOfDay();

        // ESTADÍSTICAS GENERALES
        $stats = $this->obtenerEstadisticasGenerales($fechaInicio, $fechaFin);

        // COMPROBANTES POR ESTADO
        $comprobantesPorEstado = $this->obtenerComprobantesPorEstado($fechaInicio, $fechaFin);

        // COMPROBANTES POR TIPO
        $comprobantesPorTipo = $this->obtenerComprobantesPorTipo($fechaInicio, $fechaFin);

        // GRÁFICO DE EMISIÓN DIARIA (últimos 30 días)
        $emisionDiaria = $this->obtenerEmisionDiaria($fechaInicio, $fechaFin);

        // REINTENTOS EN PROGRESO
        $reintentosPendientes = $this->obtenerReintentosPendientes();

        // ÚLTIMOS COMPROBANTES CON ERROR
        $comprobantesConError = $this->obtenerComprobantesConError(10);

        // TASA DE ÉXITO POR DÍA (últimos 7 días)
        $tasaExitoPorDia = $this->obtenerTasaExitoPorDia();

        return view('admin.facturacion.dashboard', compact(
            'stats',
            'comprobantesPorEstado',
            'comprobantesPorTipo',
            'emisionDiaria',
            'reintentosPendientes',
            'comprobantesConError',
            'tasaExitoPorDia',
            'fechaInicio',
            'fechaFin'
        ));
    }

    /**
     * Obtener estadísticas generales
     */
    private function obtenerEstadisticasGenerales($fechaInicio, $fechaFin)
    {
        $query = Comprobante::whereBetween('created_at', [$fechaInicio, $fechaFin]);

        $total = $query->count();
        $aceptados = (clone $query)->where('estado', 'ACEPTADO')->count();
        $rechazados = (clone $query)->where('estado', 'RECHAZADO')->count();
        $pendientes = (clone $query)->where('estado', 'PENDIENTE')->count();
        $conError = (clone $query)->whereNotNull('codigo_error')->count();

        // Montos totales
        $montoTotal = (clone $query)->sum('total');
        $montoAceptado = (clone $query)->where('estado', 'ACEPTADO')->sum('total');

        // Tasa de éxito
        $tasaExito = $total > 0 ? round(($aceptados / $total) * 100, 2) : 0;

        // Comprobantes hoy
        $hoy = Carbon::today();
        $comprobantesHoy = Comprobante::whereBetween('created_at', [$hoy->startOfDay(), $hoy->endOfDay()])->count();
        $aceptadosHoy = Comprobante::whereBetween('created_at', [$hoy->startOfDay(), $hoy->endOfDay()])
            ->where('estado', 'ACEPTADO')
            ->count();

        return [
            'total' => $total,
            'aceptados' => $aceptados,
            'rechazados' => $rechazados,
            'pendientes' => $pendientes,
            'con_error' => $conError,
            'monto_total' => $montoTotal,
            'monto_aceptado' => $montoAceptado,
            'tasa_exito' => $tasaExito,
            'comprobantes_hoy' => $comprobantesHoy,
            'aceptados_hoy' => $aceptadosHoy,
        ];
    }

    /**
     * Obtener comprobantes agrupados por estado
     */
    private function obtenerComprobantesPorEstado($fechaInicio, $fechaFin)
    {
        return Comprobante::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->orderBy('total', 'DESC')
            ->get();
    }

    /**
     * Obtener comprobantes agrupados por tipo
     */
    private function obtenerComprobantesPorTipo($fechaInicio, $fechaFin)
    {
        return Comprobante::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->select('tipo_comprobante', DB::raw('COUNT(*) as total'), DB::raw('SUM(total) as monto_total'))
            ->groupBy('tipo_comprobante')
            ->orderBy('total', 'DESC')
            ->get()
            ->map(function ($item) {
                $tiposNombres = [
                    '01' => 'Factura',
                    '03' => 'Boleta',
                    '07' => 'Nota de Crédito',
                    '08' => 'Nota de Débito',
                ];
                $item->nombre_tipo = $tiposNombres[$item->tipo_comprobante] ?? $item->tipo_comprobante;
                return $item;
            });
    }

    /**
     * Obtener emisión diaria de comprobantes
     */
    private function obtenerEmisionDiaria($fechaInicio, $fechaFin)
    {
        return Comprobante::whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN estado = "ACEPTADO" THEN 1 ELSE 0 END) as aceptados'),
                DB::raw('SUM(CASE WHEN estado = "RECHAZADO" THEN 1 ELSE 0 END) as rechazados'),
                DB::raw('SUM(CASE WHEN estado = "PENDIENTE" THEN 1 ELSE 0 END) as pendientes')
            )
            ->groupBy('fecha')
            ->orderBy('fecha', 'ASC')
            ->get();
    }

    /**
     * Obtener reintentos pendientes
     */
    private function obtenerReintentosPendientes()
    {
        return ComprobanteReintento::with(['comprobante.cliente.persona'])
            ->whereIn('estado', ['pendiente', 'procesando'])
            ->orderBy('proximo_intento', 'ASC')
            ->limit(10)
            ->get()
            ->map(function ($reintento) {
                return [
                    'id' => $reintento->id,
                    'comprobante_numero' => $reintento->comprobante->numero_completo ?? 'N/A',
                    'cliente' => $reintento->comprobante->cliente->persona->nombre_completo ?? 'N/A',
                    'intentos' => $reintento->intentos,
                    'max_intentos' => $reintento->max_intentos,
                    'proximo_intento' => $reintento->proximo_intento,
                    'ultimo_error' => $reintento->ultimo_error_mensaje,
                    'estado' => $reintento->estado,
                ];
            });
    }

    /**
     * Obtener comprobantes con error
     */
    private function obtenerComprobantesConError($limit = 10)
    {
        return Comprobante::with(['cliente.persona'])
            ->whereNotNull('codigo_error')
            ->orWhere('estado', 'RECHAZADO')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->map(function ($comprobante) {
                return [
                    'id' => $comprobante->id,
                    'numero_completo' => $comprobante->numero_completo,
                    'tipo' => $comprobante->tipo_comprobante,
                    'cliente' => $comprobante->cliente->persona->nombre_completo ?? 'N/A',
                    'importe' => $comprobante->total,
                    'estado' => $comprobante->estado,
                    'codigo_error' => $comprobante->codigo_error,
                    'mensaje_error' => $comprobante->mensaje_error,
                    'created_at' => $comprobante->created_at,
                    'tiene_reintento' => $comprobante->reintentos()->whereIn('estado', ['pendiente', 'procesando'])->exists(),
                ];
            });
    }

    /**
     * Obtener tasa de éxito por día (últimos 7 días)
     */
    private function obtenerTasaExitoPorDia()
    {
        $hace7Dias = now()->subDays(7)->startOfDay();

        return Comprobante::where('created_at', '>=', $hace7Dias)
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN estado = "ACEPTADO" THEN 1 ELSE 0 END) as aceptados')
            )
            ->groupBy('fecha')
            ->orderBy('fecha', 'ASC')
            ->get()
            ->map(function ($item) {
                $item->tasa_exito = $item->total > 0 ? round(($item->aceptados / $item->total) * 100, 2) : 0;
                return $item;
            });
    }

    /**
     * Forzar reintento de un comprobante
     */
    public function forzarReintento(Request $request, $comprobanteId)
    {
        try {
            $comprobante = Comprobante::findOrFail($comprobanteId);

            // Verificar si ya existe un reintento en progreso
            $reintentoExistente = ComprobanteReintento::where('comprobante_id', $comprobanteId)
                ->whereIn('estado', ['pendiente', 'procesando'])
                ->first();

            if ($reintentoExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un reintento en progreso para este comprobante',
                ], 400);
            }

            // Crear nuevo reintento
            $reintento = ComprobanteReintento::create([
                'comprobante_id' => $comprobanteId,
                'intentos' => 0,
                'max_intentos' => 5,
                'proximo_intento' => now(),
                'estado' => 'pendiente',
                'observaciones' => 'Reintento forzado manualmente',
            ]);

            // Despachar Job inmediatamente
            \App\Jobs\ReintentarEnvioComprobante::dispatch($comprobanteId, $reintento->id)
                ->delay(now()->addSeconds(10));

            return response()->json([
                'success' => true,
                'message' => 'Reintento programado exitosamente. Se ejecutará en 10 segundos.',
                'reintento_id' => $reintento->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al programar reintento: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar reintento
     */
    public function cancelarReintento($reintentoId)
    {
        try {
            $reintento = ComprobanteReintento::findOrFail($reintentoId);

            if (!in_array($reintento->estado, ['pendiente', 'procesando'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El reintento no está en estado pendiente o procesando',
                ], 400);
            }

            $reintento->marcarCancelado('Cancelado manualmente desde el dashboard');

            return response()->json([
                'success' => true,
                'message' => 'Reintento cancelado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar reintento: ' . $e->getMessage(),
            ], 500);
        }
    }
}
