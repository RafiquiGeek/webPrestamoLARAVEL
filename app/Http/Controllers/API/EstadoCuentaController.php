<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cuota;
use App\Models\Prestamo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\Admin\EstadoPrestamoController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstadoCuentaController extends Controller
{
    /**
     * Obtener estado de cuenta de un préstamo
     */
    public function estadoCuentaPrestamo($prestamoId, Request $request): JsonResponse
    {
        try {
            $prestamo = Prestamo::with([
                'cliente.persona',
                'cliente.direcciones',
                'cliente.telefonos',
                'sucursal',
                'tasa',
                'plazo',
                'user',
                'cuotas' => function ($query) {
                    $query->with('moraCuotas')->orderBy('numero_cuota');
                },
                'operaciones' => function ($query) {
                    $query->where('estado', 'activo')->orderBy('fecha', 'desc');
                },
            ])->find($prestamoId);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            // Calcular resumen financiero
            $resumen = $this->calcularResumenFinanciero($prestamo);

            // Preparar cronograma detallado
            $cronograma = $this->prepararCronograma($prestamo->cuotas);

            // Historial de pagos
            $historialPagos = $this->prepararHistorialPagos($prestamo->operaciones);

            return response()->json([
                'success' => true,
                'data' => [
                    'prestamo' => [
                        'id' => $prestamo->id,
                        'codigo' => $prestamo->codigo,
                        'estado' => $prestamo->estado,
                        'capital' => $prestamo->capital,
                        'interes' => $prestamo->interes,
                        'numero_cuotas' => $prestamo->numero_cuotas,
                        'monto_cuota' => $prestamo->monto_cuota,
                        'total_pagar' => $prestamo->total_pagar,
                        'modalidad_pago' => $prestamo->modalidad_pago,
                        'fecha_primer_pago' => $prestamo->fecha_primer_pago,
                        'fecha_desembolso' => $prestamo->fecha_desembolso,
                        'fecha_solicitud' => $prestamo->fecha_solicitud,
                    ],
                    'cliente' => [
                        'id' => $prestamo->cliente->id,
                        'persona' => $prestamo->cliente->persona,
                        'direcciones' => $prestamo->cliente->direcciones,
                        'telefonos' => $prestamo->cliente->telefonos,
                    ],
                    'sucursal' => $prestamo->sucursal,
                    'resumen_financiero' => $resumen,
                    'cronograma' => $cronograma,
                    'historial_pagos' => $historialPagos,
                    'fecha_generacion' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de cuenta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Listar préstamos de un cliente
     */
    public function prestamosCliente($clienteId, Request $request): JsonResponse
    {
        try {
            $cliente = Cliente::with('persona')->find($clienteId);

            if (! $cliente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado',
                ], 404);
            }

            $query = Prestamo::with(['sucursal', 'tasa', 'plazo'])
                ->where('cliente_id', $clienteId);

            // Filtros
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('sucursal_id')) {
                $query->where('sucursal_id', $request->sucursal_id);
            }

            if ($request->has('fecha_desde')) {
                $query->where('fecha_solicitud', '>=', $request->fecha_desde);
            }

            if ($request->has('fecha_hasta')) {
                $query->where('fecha_solicitud', '<=', $request->fecha_hasta);
            }

            $prestamos = $query->orderBy('fecha_solicitud', 'desc')->get();

            // Calcular resumen para cada préstamo
            $prestamosConResumen = $prestamos->map(function ($prestamo) {
                $resumen = $this->calcularResumenBasico($prestamo);

                return [
                    'id' => $prestamo->id,
                    'codigo' => $prestamo->codigo,
                    'estado' => $prestamo->estado,
                    'capital' => $prestamo->capital,
                    'total_pagar' => $prestamo->total_pagar,
                    'monto_cuota' => $prestamo->monto_cuota,
                    'fecha_solicitud' => $prestamo->fecha_solicitud,
                    'fecha_desembolso' => $prestamo->fecha_desembolso,
                    'sucursal' => $prestamo->sucursal->nombre ?? null,
                    'resumen' => $resumen,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'cliente' => $cliente,
                    'prestamos' => $prestamosConResumen,
                    'totales' => [
                        'total_prestamos' => $prestamos->count(),
                        'prestamos_activos' => $prestamos->whereIn('estado', ['desembolsado'])->count(),
                        'monto_total_prestado' => $prestamos->sum('capital'),
                        'monto_total_por_pagar' => $prestamos->sum('total_pagar'),
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener préstamos del cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar PDF del estado de cuenta
     */
    public function generarPDF($prestamoId): JsonResponse
    {
        try {
            $prestamo = Prestamo::with([
                'cliente.persona',
                'sucursal',
                'cuotas.moraCuotas',
                'operaciones' => function ($query) {
                    $query->where('estado', 'activo');
                },
            ])->find($prestamoId);

            if (! $prestamo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Préstamo no encontrado',
                ], 404);
            }

            $resumen = $this->calcularResumenFinanciero($prestamo);
            $cronograma = $this->prepararCronograma($prestamo->cuotas);

            $data = [
                'prestamo' => $prestamo,
                'resumen' => $resumen,
                'cronograma' => $cronograma,
                'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            ];

            $pdf = PDF::loadView('pdf.estado-cuenta', $data);

            $filename = "estado_cuenta_{$prestamo->codigo}_".now()->format('Ymd_His').'.pdf';
            $pdfPath = storage_path("app/public/estados_cuenta/{$filename}");

            // Crear directorio si no existe
            if (! file_exists(dirname($pdfPath))) {
                mkdir(dirname($pdfPath), 0755, true);
            }

            $pdf->save($pdfPath);

            return response()->json([
                'success' => true,
                'message' => 'PDF generado exitosamente',
                'data' => [
                    'filename' => $filename,
                    'url' => asset("storage/estados_cuenta/{$filename}"),
                    'path' => $pdfPath,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resumen de cartera por usuario
     */
    public function resumenCartera(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();

            $query = Prestamo::with(['cliente.persona', 'sucursal']);

            // Filtrar por sucursales del usuario si no es admin
            if (! $user->hasRole('Admin')) {
                $sucursalesIds = $user->sucursales->pluck('id');
                $query->whereIn('sucursal_id', $sucursalesIds);
            }

            // Filtros adicionales
            if ($request->has('sucursal_id')) {
                $query->where('sucursal_id', $request->sucursal_id);
            }

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $prestamos = $query->get();

            // Calcular métricas
            $metricas = [
                'total_prestamos' => $prestamos->count(),
                'prestamos_activos' => $prestamos->where('estado', 'desembolsado')->count(),
                'prestamos_pendientes' => $prestamos->where('estado', 'pendiente')->count(),
                'prestamos_vencidos' => $this->contarPrestamosVencidos($prestamos),
                'monto_cartera_total' => $prestamos->where('estado', 'desembolsado')->sum('capital'),
                'monto_por_cobrar' => $this->calcularMontoPorCobrar($prestamos),
                'monto_vencido' => $this->calcularMontoVencido($prestamos),
                'tasa_morosidad' => $this->calcularTasaMorosidad($prestamos),
            ];

            // Resumen por sucursal
            $resumenSucursales = $prestamos->groupBy('sucursal_id')->map(function ($prestamosSucursal) {
                return [
                    'sucursal' => $prestamosSucursal->first()->sucursal->nombre ?? 'Sin sucursal',
                    'total_prestamos' => $prestamosSucursal->count(),
                    'monto_cartera' => $prestamosSucursal->where('estado', 'desembolsado')->sum('capital'),
                    'prestamos_activos' => $prestamosSucursal->where('estado', 'desembolsado')->count(),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'metricas_generales' => $metricas,
                    'resumen_por_sucursal' => $resumenSucursales,
                    'fecha_corte' => now()->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener resumen de cartera',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcular resumen financiero detallado
     */
    private function calcularResumenFinanciero($prestamo): array
    {
        $cuotas = $prestamo->cuotas;
        $totalPagado = $prestamo->operaciones->where('tipo', 'pago')->sum('monto');
        $totalPorPagar = $prestamo->total_pagar - $totalPagado;

        $cuotasPagadas = $cuotas->where('estado', 2)->count();
        $cuotasPendientes = $cuotas->where('estado', '!=', 2)->count();
        $cuotasVencidas = $cuotas->filter(function ($cuota) {
            return $cuota->estado != 2 && Carbon::parse($cuota->fecha_vencimiento)->isPast();
        })->count();

        $moraPendiente = $cuotas->sum(function ($cuota) {
            return $cuota->moraCuotas->where('estado', '!=', 2)->sum('monto');
        });

        $proximasCuotas = $cuotas->filter(function ($cuota) {
            return $cuota->estado != 2 && Carbon::parse($cuota->fecha_vencimiento)->between(
                now(), now()->addDays(7)
            );
        });

        return [
            'capital_original' => $prestamo->capital,
            'total_a_pagar' => $prestamo->total_pagar,
            'total_pagado' => $totalPagado,
            'saldo_pendiente' => $totalPorPagar,
            'mora_pendiente' => $moraPendiente,
            'total_adeudado' => $totalPorPagar + $moraPendiente,
            'cuotas_pagadas' => $cuotasPagadas,
            'cuotas_pendientes' => $cuotasPendientes,
            'cuotas_vencidas' => $cuotasVencidas,
            'porcentaje_avance' => $prestamo->numero_cuotas > 0 ?
                round(($cuotasPagadas / $prestamo->numero_cuotas) * 100, 2) : 0,
            'proximas_cuotas' => $proximasCuotas->count(),
            'dias_hasta_proxima_cuota' => $proximasCuotas->isNotEmpty() ?
                Carbon::now()->diffInDays($proximasCuotas->first()->fecha_vencimiento) : null,
        ];
    }

    /**
     * Preparar cronograma de pagos
     */
    private function prepararCronograma($cuotas): array
    {
        return $cuotas->map(function ($cuota) {
            $moraPendiente = $cuota->moraCuotas->where('estado', '!=', 2)->sum('monto');
            $cuotaPendiente = $cuota->monto_cuota - $cuota->monto_pagado;

            return [
                'numero_cuota' => $cuota->numero_cuota,
                'fecha_vencimiento' => $cuota->fecha_vencimiento,
                'monto_capital' => $cuota->monto_capital,
                'monto_interes' => $cuota->monto_interes,
                'monto_cuota' => $cuota->monto_cuota,
                'monto_pagado' => $cuota->monto_pagado,
                'cuota_pendiente' => $cuotaPendiente,
                'mora_pendiente' => $moraPendiente,
                'total_pendiente' => $cuotaPendiente + $moraPendiente,
                'saldo_pendiente' => $cuota->saldo_pendiente,
                'estado' => $cuota->estado,
                'estado_texto' => $this->getEstadoTexto($cuota->estado),
                'fecha_pago' => $cuota->fecha_pago,
                'dias_vencimiento' => Carbon::now()->diffInDays($cuota->fecha_vencimiento, false),
                'esta_vencida' => $cuota->estado != 2 && Carbon::parse($cuota->fecha_vencimiento)->isPast(),
            ];
        })->toArray();
    }

    /**
     * Preparar historial de pagos
     */
    private function prepararHistorialPagos($operaciones): array
    {
        return $operaciones->where('tipo', 'pago')->map(function ($operacion) {
            return [
                'id' => $operacion->id,
                'fecha' => $operacion->fecha,
                'monto' => $operacion->monto,
                'metodo_pago' => $operacion->metodo_pago,
                'numero_operacion' => $operacion->numero_operacion,
                'cuota_numero' => $operacion->cuota->numero_cuota ?? null,
                'observaciones' => $operacion->observaciones,
                'usuario' => $operacion->user->name ?? null,
                'comprobante_pago' => $operacion->comprobante_pago,
            ];
        })->values()->toArray();
    }

    /**
     * Calcular resumen básico de préstamo
     */
    private function calcularResumenBasico($prestamo): array
    {
        $cuotas = $prestamo->cuotas ?? collect();
        $operaciones = $prestamo->operaciones ?? collect();

        $totalPagado = $operaciones->where('tipo', 'pago')->where('estado', 'activo')->sum('monto');
        $cuotasPagadas = $cuotas->where('estado', 2)->count();
        $cuotasPendientes = $cuotas->where('estado', '!=', 2)->count();

        return [
            'total_pagado' => $totalPagado,
            'saldo_pendiente' => $prestamo->total_pagar - $totalPagado,
            'cuotas_pagadas' => $cuotasPagadas,
            'cuotas_pendientes' => $cuotasPendientes,
            'porcentaje_avance' => $prestamo->numero_cuotas > 0 ?
                round(($cuotasPagadas / $prestamo->numero_cuotas) * 100, 2) : 0,
        ];
    }

    /**
     * Obtener texto del estado de cuota
     */
    private function getEstadoTexto($estado): string
    {
        return match ($estado) {
            0 => 'Pendiente',
            1 => 'Parcial',
            2 => 'Pagado',
            3 => 'Vencido',
            default => 'Desconocido'
        };
    }

    /**
     * Contar préstamos con cuotas vencidas
     */
    private function contarPrestamosVencidos($prestamos): int
    {
        return $prestamos->filter(function ($prestamo) {
            return $prestamo->cuotas()->where('estado', '!=', 2)
                ->where('fecha_vencimiento', '<', now())
                ->exists();
        })->count();
    }

    /**
     * Calcular monto total por cobrar
     */
    private function calcularMontoPorCobrar($prestamos): float
    {
        return $prestamos->sum(function ($prestamo) {
            $totalPagado = $prestamo->operaciones()->where('tipo', 'pago')
                ->where('estado', 'activo')->sum('monto');

            return max(0, $prestamo->total_pagar - $totalPagado);
        });
    }

    /**
     * Calcular monto vencido
     */
    private function calcularMontoVencido($prestamos): float
    {
        return $prestamos->sum(function ($prestamo) {
            return $prestamo->cuotas()->where('estado', '!=', 2)
                ->where('fecha_vencimiento', '<', now())
                ->sum(DB::raw('monto_cuota - monto_pagado'));
        });
    }

    /**
     * Calcular tasa de morosidad
     */
    private function calcularTasaMorosidad($prestamos): float
    {
        $prestamosActivos = $prestamos->where('estado', 'desembolsado');
        $prestamosVencidos = $this->contarPrestamosVencidos($prestamosActivos);

        if ($prestamosActivos->count() === 0) {
            return 0;
        }

        return round(($prestamosVencidos / $prestamosActivos->count()) * 100, 2);
    }

    /**
     * Generar estado de cuenta como IMAGEN para compartir por WhatsApp
     * Guarda la imagen temporalmente y retorna la URL pública
     */
    public function generarParaCompartir($prestamoId): JsonResponse
    {
        try {
            // Obtener el préstamo con todas las relaciones necesarias
            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal.zonas',
                'cuotas.operaciones.metodoDePago',
                'cuotas.moras_pendientes',
                'carterasAnalista.user',
                'carterasJcc.user',
                'carterasAsesor.user',
            ])->findOrFail($prestamoId);

            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();

            $totalCapital = $cuotas->sum('monto');
            $totalAbonos = $cuotas->sum(fn ($cuota) => $cuota->operaciones()->sum('abono'));
            $totalInteres = $cuotas->sum('interes');
            $totalComision = $cuotas->sum('comision');
            $totalIgv = $cuotas->sum('igv');
            $totalMoras = $cuotas->sum(fn ($cuota) => $cuota->moras_pendientes->sum('monto'));

            // Renderizar HTML
            $html = view('admin.PDF.estadocuenta', compact(
                'prestamo',
                'cuotas',
                'totalCapital',
                'totalAbonos',
                'totalInteres',
                'totalComision',
                'totalIgv',
                'totalMoras'
            ))->render();

            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/public/temp/estados-cuenta');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generar nombre único para el archivo
            $fileName = 'estado_cuenta_' . $prestamoId . '_' . time() . '.png';
            $filePath = $tempDir . '/' . $fileName;

            // Intentar generar imagen con Browsershot
            try {
                $browsershot = \Spatie\Browsershot\Browsershot::html($html);

                // Detectar sistema operativo y configurar rutas
                if (PHP_OS_FAMILY === 'Windows') {
                    $browsershot->setNodeBinary('C:\Program Files\nodejs\node.exe')
                               ->setNpmBinary('C:\Program Files\nodejs\npm.cmd');
                } else {
                    // Linux/Unix - usar rutas por defecto
                    $browsershot->setNodeBinary('/usr/bin/node')
                               ->setNpmBinary('/usr/bin/npm');
                }

                $browsershot->windowSize(800, 1400)
                           ->setScreenshotType('png', 100)
                           ->waitUntilNetworkIdle()
                           ->save($filePath);

            } catch (\Exception $e) {
                // Si Browsershot falla, guardar el HTML directamente
                \Log::warning('Browsershot failed, falling back to HTML: ' . $e->getMessage());

                // Guardar como HTML en lugar de imagen
                $fileName = 'estado_cuenta_' . $prestamoId . '_' . time() . '.html';
                $filePath = $tempDir . '/' . $fileName;
                file_put_contents($filePath, $html);
            }

            // Generar URL pública
            $publicUrl = url('storage/temp/estados-cuenta/' . $fileName);

            // Información del cliente para WhatsApp
            $clienteNombre = $prestamo->cliente->persona->nombres . ' ' .
                           $prestamo->cliente->persona->ape_pat . ' ' .
                           $prestamo->cliente->persona->ape_mat;

            $clienteTelefono = $prestamo->cliente->persona->telefono ?? '';

            // Mensaje personalizado para WhatsApp
            $mensaje = "🏦 *Estado de Cuenta - Préstamo #{$prestamoId}*\n\n" .
                      "👤 Cliente: {$clienteNombre}\n" .
                      "💰 Monto: S/ " . number_format($prestamo->cantidad_solicitada, 2) . "\n" .
                      "📊 Estado: {$prestamo->estado}\n\n" .
                      "📸 Ver estado de cuenta:\n{$publicUrl}\n\n" .
                      "_Generado el " . now()->format('d/m/Y H:i') . "_";

            return response()->json([
                'success' => true,
                'url' => $publicUrl,
                'whatsapp_url' => "https://wa.me/{$clienteTelefono}?text=" . urlencode($mensaje),
                'mensaje' => $mensaje,
                'telefono' => $clienteTelefono,
                'file_name' => $fileName,
                'type' => pathinfo($fileName, PATHINFO_EXTENSION), // png o html
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al generar estado de cuenta para compartir: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener HTML del cronograma para renderizar en móvil
     * Este endpoint devuelve la URL del HTML del cronograma con medidas fijas (1400px)
     */
    public function obtenerHtmlCronograma($prestamoId)
    {
        try {
            // Obtener el préstamo con todas las relaciones necesarias
            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal.zonas',
                'cliente.persona.direccion',
                'cuotas.operaciones.metodoDePago',
                'cuotas.operaciones.operacionGeneral',
                'cuotas.moras',
                'cuotas.moras_pendientes',
                'cuotas.abonosMoraFavor',
                'operaciones',
                'carterasAnalista.user.persona.telefonos',
                'carterasJcc.user.persona.telefonos',
                'carterasAsesor.user.persona.telefonos',
                'convenios',
                'cuenta.entidadBancaria',
            ])->findOrFail($prestamoId);

            // Devolver la vista HTML con medidas fijas para móvil
            return response()->view('pdf.estado_cuenta_mobile', compact('prestamo'))
                ->header('Content-Type', 'text/html')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el cronograma: ' . $e->getMessage()
            ], 500);
        }
    }

     /**
     * GET /api/estado-cuenta/{id}
     * Descargar PDF del estado de cuenta
     */
    public function descargar($id)
    {
        try {
            // Obtener el préstamo con todas las relaciones necesarias
            $prestamo = Prestamo::with([
                'cliente.persona.direcciones.sucursal.zonas',
                'cliente.persona.direccion',
                'cliente.persona.telefonos',
                'cliente.laborales',
                'cliente.conyuge.persona.telefonos',
                'cliente.cuentasCliente.entidadBancaria',
                'cliente.cuentasCliente.billeteraDigital',
                'aval.persona.direccion',
                'aval.persona.direcciones',
                'aval.persona.telefonos',
                'cuotas.operaciones.metodoDePago',
                'cuotas.operaciones.operacionGeneral',
                'cuotas.moras',
                'cuotas.moras_pendientes',
                'cuotas.abonosMoraFavor',
                'operaciones',
                'carterasAnalista.user.persona',
                'carterasJcc.user.persona',
                'carterasAsesor.user.persona',
                'convenios',
                'cuenta.entidadBancaria',
            ])->findOrFail($id);

            // Obtener las cuotas ordenadas con moras
            $cuotas = $prestamo->cuotas()->with('moras')->orderBy('numero')->get();

            // Buscar fondo provisional
            $fondo_provisional = \App\Models\FondoProvisional::where('prestamo_id', $prestamo->id)->first();

            // Calcular el estado calculado
            $estadoController = new EstadoPrestamoController();
            $resultadoEstado = $estadoController->calcularYActualizarEstado(
                $prestamo,
                false,
                'api_estado_cuenta'
            );
            
            $estadoCalculado = $resultadoEstado['estado_calculado'];
            $estadoBD = $resultadoEstado['estado_anterior'];

            // Generar el PDF
            $pdf = Pdf::loadView('pdf.estado_cuenta_detallado', compact(
                'prestamo',
                'cuotas',
                'fondo_provisional',
                'estadoCalculado',
                'estadoBD'
            ));

            $pdf->setPaper('A5', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Descargar directamente
            return $pdf->download('estado_cuenta_prestamo_'.$prestamo->id.'_'.date('Y-m-d').'.pdf');

        } catch (\Exception $e) {
            Log::error('Error al generar PDF estado de cuenta: '.$e->getMessage(), [
                'prestamo_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
