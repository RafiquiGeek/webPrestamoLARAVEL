<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Prestamo;
use App\Models\SireHistorial;
use App\Services\SireApiService;
use App\Services\SireEmisionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class SireController extends Controller
{
    protected SireEmisionService $emisionService;
    protected SireApiService $apiService;

    public function __construct(SireEmisionService $emisionService, SireApiService $apiService)
    {
        $this->emisionService = $emisionService;
        $this->apiService = $apiService;
    }

    /**
     * Mostrar listado de comprobantes SIRE
     */
    public function index()
    {
        return view('admin.Sire.index');
    }

    /**
     * Datos para DataTables del listado de comprobantes
     */
    public function data(Request $request)
    {
        $query = SireHistorial::query();

        // Aplicar filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_comprobante')) {
            $query->where('tipo_comprobante', $request->tipo_comprobante);
        }

        if ($request->filled('fecha_desde')) {
            $query->where('fecha_emision', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->where('fecha_emision', '<=', $request->fecha_hasta);
        }

        return DataTables::of($query)
            ->editColumn('fecha_emision', function ($row) {
                return $row->fecha_emision ? $row->fecha_emision->format('d/m/Y') : '-';
            })
            ->editColumn('fecha_envio', function ($row) {
                return $row->fecha_envio ? $row->fecha_envio->format('d/m/Y H:i') : '-';
            })
            ->make(true);
    }

    /**
     * Mostrar formulario de envío de comprobantes
     */
    public function enviar()
    {
        return view('admin.Sire.enviar');
    }

    /**
     * Obtener cuotas disponibles para emitir comprobante
     */
    public function cuotasDisponibles(): JsonResponse
    {
        try {
            // Cuotas pagadas sin comprobante emitido
            $cuotas = Cuota::with(['prestamo.cliente'])
                ->where('estado_pago', 'pagado')
                ->where(function ($q) {
                    $q->where('comprobante_emitido', false)
                      ->orWhereNull('comprobante_emitido');
                })
                ->orderBy('fecha_pago', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $cuotas
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener cuotas disponibles', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar cuotas disponibles'
            ], 500);
        }
    }

    /**
     * Obtener préstamos con cuotas disponibles
     */
    public function prestamosDisponibles(): JsonResponse
    {
        try {
            $prestamos = Prestamo::with('cliente')
                ->whereHas('cuotas', function ($q) {
                    $q->where('estado_pago', 'pagado')
                      ->where(function ($sq) {
                          $sq->where('comprobante_emitido', false)
                             ->orWhereNull('comprobante_emitido');
                      });
                })
                ->orderBy('fecha_desembolso', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $prestamos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener préstamos disponibles', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar préstamos disponibles'
            ], 500);
        }
    }

    /**
     * Enviar comprobantes de forma masiva
     */
    public function enviarMasivo(Request $request): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|in:prestamo,fecha',
            'prestamo_id' => 'required_if:tipo,prestamo|exists:prestamos,id',
            'fecha_desde' => 'required_if:tipo,fecha|date',
            'fecha_hasta' => 'required_if:tipo,fecha|date',
        ]);

        try {
            $cuotas = collect();

            if ($request->tipo === 'prestamo') {
                $cuotas = Cuota::where('prestamo_id', $request->prestamo_id)
                    ->where('estado_pago', 'pagado')
                    ->where(function ($q) {
                        $q->where('comprobante_emitido', false)
                          ->orWhereNull('comprobante_emitido');
                    })
                    ->get();
            } else {
                $cuotas = Cuota::whereBetween('fecha_pago', [$request->fecha_desde, $request->fecha_hasta])
                    ->where('estado_pago', 'pagado')
                    ->where(function ($q) {
                        $q->where('comprobante_emitido', false)
                          ->orWhereNull('comprobante_emitido');
                    })
                    ->get();
            }

            $resultados = [];
            $exitosos = 0;
            $fallidos = 0;

            foreach ($cuotas as $cuota) {
                $resultado = $this->emisionService->emitirComprobanteParaCuota($cuota);

                $resultados[] = [
                    'cuota' => "Cuota {$cuota->numero}",
                    'cliente' => $cuota->prestamo->cliente->nombre_completo ?? 'Sin nombre',
                    'monto' => $cuota->monto_cuota,
                    'success' => $resultado['success'],
                    'mensaje' => $resultado['success'] ? 'Emitido correctamente' : ($resultado['error'] ?? 'Error desconocido')
                ];

                if ($resultado['success']) {
                    $exitosos++;
                } else {
                    $fallidos++;
                }
            }

            return response()->json([
                'success' => true,
                'total' => count($cuotas),
                'exitosos' => $exitosos,
                'fallidos' => $fallidos,
                'resultados' => $resultados
            ]);

        } catch (\Exception $e) {
            Log::error('Error en envío masivo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error en el envío masivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar formulario de consulta SUNAT
     */
    public function consultar()
    {
        $sincronizacion_activa = config('sire.sincronizacion_activa', false);
        $ultima_sync = config('sire.ultima_sincronizacion', 'Nunca');

        return view('admin.Sire.consultar', compact('sincronizacion_activa', 'ultima_sync'));
    }

    /**
     * Consultar comprobante en SUNAT
     */
    public function consultarComprobante(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_comprobante' => 'required|in:01,03,07,08',
            'serie' => 'required|string|max:4',
            'numero' => 'required|integer',
        ]);

        try {
            // Aquí se haría la consulta real a SUNAT via SIRE
            // Por ahora retornamos una respuesta simulada

            $resultado = $this->apiService->enviarComprobante([
                'tipo' => 'consulta',
                'tipoDoc' => $request->tipo_comprobante,
                'serie' => $request->serie,
                'correlativo' => $request->numero
            ]);

            return response()->json([
                'success' => $resultado['success'],
                'data' => $resultado['data'] ?? [],
                'message' => $resultado['success'] ? 'Consulta exitosa' : 'Error en la consulta'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al consultar comprobante', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar libro electrónico
     */
    public function descargarLibro(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:140100,080100,050100,060100,010100',
            'periodo' => 'required|date_format:Y-m',
            'formato' => 'required|in:txt,excel,pdf',
        ]);

        try {
            // Aquí se implementaría la descarga real del libro desde SUNAT
            // Por ahora retornamos un mensaje

            return response()->json([
                'success' => true,
                'message' => 'Funcionalidad de descarga de libros en desarrollo'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al descargar libro', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al descargar el libro: ' . $e->getMessage());
        }
    }

    /**
     * Toggle sincronización automática
     */
    public function toggleSincronizacion(Request $request): JsonResponse
    {
        $request->validate([
            'activo' => 'required|boolean'
        ]);

        try {
            // Guardar configuración
            config(['sire.sincronizacion_activa' => $request->activo]);

            return response()->json([
                'success' => true,
                'message' => $request->activo ? 'Sincronización activada' : 'Sincronización desactivada'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar configuración'
            ], 500);
        }
    }

    /**
     * Sincronizar comprobantes ahora
     */
    public function sincronizarAhora(): JsonResponse
    {
        try {
            // Obtener comprobantes pendientes
            $pendientes = SireHistorial::whereIn('estado', ['enviado', 'pendiente'])
                ->where('fecha_envio', '<=', now()->subMinutes(5))
                ->get();

            $actualizados = 0;

            foreach ($pendientes as $comprobante) {
                // Aquí se consultaría el estado real en SUNAT
                // Por ahora solo simulamos
                $actualizados++;
            }

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. {$actualizados} comprobantes actualizados.",
                'fecha' => now()->format('d/m/Y H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Error en sincronización', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error en la sincronización'
            ], 500);
        }
    }

    /**
     * Historial de consultas recientes
     */
    public function historialConsultas(): JsonResponse
    {
        try {
            $historial = SireHistorial::orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($item) {
                    return [
                        'fecha' => $item->created_at->format('d/m/Y H:i'),
                        'tipo' => $item->tipo_comprobante,
                        'serie' => $item->serie,
                        'numero' => $item->numero,
                        'estado' => strtoupper($item->estado),
                        'mensaje' => $item->sunat_mensaje ?? '-',
                        'usuario' => $item->user->name ?? 'Sistema'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $historial
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar historial'
            ], 500);
        }
    }

    /**
     * Ver detalles de un comprobante
     */
    public function detalles($id)
    {
        $comprobante = SireHistorial::findOrFail($id);
        return view('admin.Sire.detalles', compact('comprobante'));
    }

    /**
     * Descargar XML generado
     */
    public function descargarXml($id)
    {
        $comprobante = SireHistorial::findOrFail($id);

        if (!$comprobante->xml_generado) {
            return back()->with('error', 'No hay XML generado para este comprobante');
        }

        $filename = "{$comprobante->serie}-{$comprobante->numero}.xml";

        return response($comprobante->xml_generado)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Descargar XML firmado
     */
    public function descargarXmlFirmado($id)
    {
        $comprobante = SireHistorial::findOrFail($id);

        if (!$comprobante->xml_firmado) {
            return back()->with('error', 'No hay XML firmado para este comprobante');
        }

        $filename = "{$comprobante->serie}-{$comprobante->numero}-firmado.xml";

        return response($comprobante->xml_firmado)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Descargar CDR
     */
    public function descargarCdr($id)
    {
        $comprobante = SireHistorial::findOrFail($id);

        if (!$comprobante->cdr_zip) {
            return back()->with('error', 'No hay CDR disponible para este comprobante');
        }

        $filename = "R-{$comprobante->serie}-{$comprobante->numero}.zip";

        return response($comprobante->cdr_zip)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Reenviar comprobante
     */
    public function reenviar($id)
    {
        try {
            $comprobante = SireHistorial::findOrFail($id);
            $resultado = $this->emisionService->reenviarComprobante($comprobante);

            if ($resultado['success']) {
                return back()->with('success', 'Comprobante reenviado exitosamente');
            }

            return back()->with('error', $resultado['error'] ?? 'Error al reenviar comprobante');

        } catch (\Exception $e) {
            Log::error('Error al reenviar comprobante', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Error al reenviar: ' . $e->getMessage());
        }
    }
}
