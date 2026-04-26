<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Cuota;
use App\Models\Prestamo;
use App\Services\SireApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComprobantesController extends Controller
{
    protected ?SireApiService $sireApi = null;

    public function __construct()
    {
        $this->middleware('auth');

        // Intentar inicializar SIRE solo si está configurado
        try {
            $this->sireApi = app(SireApiService::class);
        } catch (\Exception $e) {
            // SIRE no está disponible, pero permitimos operaciones que no lo requieren
            Log::info('SIRE no disponible en ComprobantesController: ' . $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $query = Comprobante::with([
            'cliente.persona',
            'prestamo',
            'notasCredito' => function($q) {
                $q->where('tipo_comprobante', '07')->orderBy('created_at', 'desc');
            }
        ])->whereNotNull('prestamo_id'); // Solo comprobantes con préstamo asociado

        // Filtro por búsqueda (serie/número)
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('serie', 'LIKE', "%{$buscar}%")
                  ->orWhere('numero', 'LIKE', "%{$buscar}%")
                  ->orWhereRaw("CONCAT(serie, '-', numero) LIKE ?", ["%{$buscar}%"]);
            });
        }

        // Filtro por tipo
        if ($request->filled('tipo')) {
            $query->where('tipo_comprobante', $request->tipo);
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_emision', '>=', $request->fecha_desde);
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_emision', '<=', $request->fecha_hasta);
        }

        // Determinar cantidad de items por página
        $perPage = $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

        $comprobantes = $query->orderBy('fecha_emision', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->appends($request->except('page'));

    return view('admin.comprobantes.index', compact('comprobantes'));
    }

    /**
     * Exportar comprobantes a Excel
     */
    public function exportar(Request $request)
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ComprobantesExport($request),
            'comprobantes_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    /**
     * Exportar cuotas de préstamos seleccionados a Excel
     */
    public function exportarCuotas(Request $request)
    {
        // Log completo de la petición para debugging
        \Log::info('Exportar Cuotas - Request completo:', [
            'all' => $request->all(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'prestamo_ids' => $request->input('prestamo_ids'),
        ]);

        $prestamoIds = [];
        if ($request->has('prestamo_ids') && $request->prestamo_ids) {
            $prestamoIds = explode(',', $request->prestamo_ids);
            $prestamoIds = array_filter(array_map('intval', $prestamoIds));
        }

        // Log para debugging
        \Log::info('Exportar Cuotas - Prestamo IDs procesados:', $prestamoIds);

        // Si no hay IDs, se exportarán todas las cuotas pagadas de préstamos con factura
        if (empty($prestamoIds)) {
            \Log::info('Exportar Cuotas - No se seleccionaron IDs, exportando reporte general');
        }

        \Log::info('Exportar Cuotas - Iniciando descarga del archivo Excel');
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CuotasExport($prestamoIds),
            'cuotas_prestamos_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    public function create()
    {
        $clientes = Cliente::with('persona')->get();
        $prestamos = Prestamo::with('cliente.persona')->get();

        return view('admin.Comprobantes.create', compact('clientes', 'prestamos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_comprobante' => 'required|in:01,03', // 01=Factura, 03=Boleta
            'serie' => 'required|string|max:4',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:1',
            'items.*.valor_unitario' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $cliente = Cliente::with('persona')->findOrFail($request->cliente_id);

            // Obtener el siguiente número correlativo
            $ultimoComprobante = Comprobante::where('serie', $request->serie)
                ->where('tipo_comprobante', $request->tipo_comprobante)
                ->orderBy('numero', 'desc')
                ->first();

            $numero = $ultimoComprobante ? $ultimoComprobante->numero + 1 : 1;

            // Crear comprobante en BD
            $comprobante = Comprobante::create([
                'cliente_id' => $cliente->id,
                'prestamo_id' => $request->prestamo_id,
                'tipo_comprobante' => $request->tipo_comprobante,
                'serie' => $request->serie,
                'numero' => $numero,
                'fecha_emision' => now(),
                'moneda' => 'PEN',
                'estado' => 'PENDIENTE',
                'items' => json_encode($request->items),
                'total' => collect($request->items)->sum(function ($item) {
                    return $item['cantidad'] * $item['valor_unitario'] * 1.18; // Con IGV
                }),
            ]);

            // Preparar items
            $itemsPayload = collect($request->items)->map(function ($item) {
                $valorUnitario = $item['valor_unitario'];
                $cantidad = $item['cantidad'];
                
                return [
                    'codigo' => 'ITEM',
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $cantidad,
                    'unidad' => 'ZZ',
                    'valor_unitario' => $valorUnitario,
                    'tipo_afectacion_igv' => '10', // Gravado
                ];
            })->toArray();

            // Payload para SIRE
            $payload = [
                'cliente' => [
                    'tipo_documento' => strlen($cliente->persona->documento) == 11 ? '6' : '1',
                    'numero_documento' => $cliente->persona->documento,
                    'razon_social' => $cliente->persona->ape_pat . ' ' . $cliente->persona->ape_mat . ', ' . $cliente->persona->nombres,
                ],
                'items' => $itemsPayload,
                'serie' => $request->serie,
                'numero' => $numero,
                'tipo_comprobante' => $request->tipo_comprobante,
            ];

            // Validar que SIRE esté disponible
            if (!$this->sireApi) {
                throw new \Exception('SIRE no está habilitado. Active SIRE en la configuración SUNAT.');
            }

            // Enviar a SIRE
            $result = $this->sireApi->enviarJson($payload);

            if ($result['success']) {
                $responseData = $result['data'] ?? [];
                
                $comprobante->update([
                    'estado' => 'ENVIADO',
                    'xml_firmado' => $responseData['xml_firmado'] ?? null,
                    'xml_content' => $responseData['xml_generado'] ?? null,
                    'cdr_zip' => $responseData['cdr'] ?? null,
                    'hash' => $responseData['hash'] ?? null,
                ]);

                DB::commit();

                return redirect()->route('admin.comprobantes.index')
                    ->with('success', 'Comprobante creado y enviado exitosamente');
            } else {
                $comprobante->update([
                    'estado' => 'ERROR',
                    'mensaje_error' => $result['error'] ?? 'Error desconocido',
                ]);

                DB::commit();

                return back()->withInput()
                    ->with('error', 'Error al enviar comprobante: ' . ($result['error'] ?? 'Error desconocido'));
            }

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withInput()
                ->with('error', 'Error al procesar comprobante: '.$e->getMessage());
        }
    }

    public function show(Comprobante $comprobante)
    {
        $comprobante->load(['cliente.persona', 'prestamo', 'cuota']);

        // Obtener configuración SUNAT para datos de la empresa
        $configuracion = \App\Models\ConfiguracionSunat::obtenerActiva();

        return view('admin.comprobantes.show', compact('comprobante', 'configuracion'));
    }


public function descargarPdf(Comprobante $comprobante)
{
    try {
        $filename = $comprobante->serie.'-'.str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT).'.pdf';
        $storagePath = 'comprobantes/pdf/' . $filename;
        $fullPath = storage_path('app/public/' . $storagePath);

        // Si el PDF ya existe, devolverlo directamente
        if (file_exists($fullPath)) {
            return response()->download($fullPath, $filename);
        }

        // Si no existe, generarlo
        $comprobante->load(['cliente.persona', 'prestamo', 'cuota']);

        $configuracionSunat = \App\Models\ConfiguracionSunat::obtenerActiva();
        $empresaData = [
            'ruc' => $configuracionSunat->ruc ?? '20000000001',
            'razon_social' => $configuracionSunat->razon_social ?? 'MI EMPRESA S.A.C.',
            'direccion' => $configuracionSunat->direccion ?? 'AV. PRINCIPAL 123, LIMA',
            'web' => $configuracionSunat->web ?? 'www.miempresa.pe',
        ];

        $data = [
            'comprobante' => $comprobante,
            'empresa' => $empresaData,
        ];

        $pdf = app('dompdf.wrapper');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'dpi' => 72,
            'defaultFont' => 'Courier',
            'chroot' => realpath(base_path()),
        ]);

        $pdf->loadView('admin.comprobantes.pdf', $data);
        $pdf->setPaper([0, 0, 226.77, 1000], 'portrait');

        // Crear directorio si no existe
        $directory = dirname($fullPath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Guardar PDF en storage
        $pdf->save($fullPath);

        // Descargar el PDF
        return response()->download($fullPath, $filename);

    } catch (\Exception $e) {
        \Log::error('Error al generar PDF: ' . $e->getMessage(), [
            'comprobante_id' => $comprobante->id,
            'trace' => $e->getTraceAsString()
        ]);
        return back()->with('error', 'Error al generar PDF: '.$e->getMessage());
    }
}

    public function descargarXml(Comprobante $comprobante)
    {
        try {
            // Si ya tenemos el XML guardado, devolverlo directamente
            if ($comprobante->xml_content) {
                // Nombre del archivo
                $configuracionSunat = \App\Models\ConfiguracionSunat::obtenerActiva();
                $ruc = $configuracionSunat->ruc ?? '00000000000';
                $filename = $ruc . '-' . $comprobante->tipo_comprobante . '-' . $comprobante->serie . '-' . str_pad($comprobante->numero, 6, '0', STR_PAD_LEFT) . '.xml';

                return response($comprobante->xml_content)
                    ->header('Content-Type', 'application/xml')
                    ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }

            return back()->with('error', 'El XML no está disponible y no se puede regenerar localmente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al descargar XML: '.$e->getMessage());
        }
    }

    /**
     * Obtener el siguiente número de comprobante
     */
    public function obtenerSiguienteNumero(Request $request)
    {
        try {
            $tipoComprobante = $request->input('tipo_comprobante', '03'); // Por defecto Boleta

            // Obtener configuración SUNAT
            $configuracionSunat = \App\Models\ConfiguracionSunat::obtenerActiva();
            if (!$configuracionSunat) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración SUNAT activa',
                ], 500);
            }

            // Determinar la serie y número inicial según el tipo de comprobante
            if ($tipoComprobante == '01') {
                $serie = $configuracionSunat->serie_factura;
                $numeroInicial = $configuracionSunat->numero_inicial_factura ?? 1;
            } else {
                $serie = $configuracionSunat->serie_boleta;
                $numeroInicial = $configuracionSunat->numero_inicial_boleta ?? 1;
            }

            // Obtener el siguiente número correlativo
            $ultimoComprobante = Comprobante::where('serie', $serie)
                ->where('tipo_comprobante', $tipoComprobante)
                ->orderBy('numero', 'desc')
                ->first();

            // El número debe ser al menos el número inicial configurado
            $numero = $ultimoComprobante ? max($ultimoComprobante->numero + 1, $numeroInicial) : $numeroInicial;

            return response()->json([
                'success' => true,
                'serie' => $serie,
                'numero' => $numero,
                'numeroFormateado' => str_pad($numero, 6, '0', STR_PAD_LEFT),
                'comprobanteCompleto' => $serie . '-' . str_pad($numero, 6, '0', STR_PAD_LEFT),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener número de comprobante: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function emitirCuota(Request $request)
    {
        \Log::info('EmitirCuota - Iniciando proceso de pago de cuota', [
            'request_data' => $request->all(),
        ]);

        try {
            $request->validate([
                'cuota_id' => 'required|exists:cuotas,id',
                'monto_pagado' => 'required|numeric|min:0.01',
                'fecha_pago' => 'required|date',
                'metodo_pago_id' => 'required|exists:metodos_pago,id',
                'observaciones' => 'nullable|string|max:500',
            ]);
            \Log::info('EmitirCuota - Validación exitosa');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('EmitirCuota - Error de validación', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            \Log::info('EmitirCuota - Buscando cuota', ['cuota_id' => $request->cuota_id]);
            $cuota = Cuota::with(['prestamo.cliente.persona'])->findOrFail($request->cuota_id);
            \Log::info('EmitirCuota - Cuota encontrada', ['cuota_id' => $cuota->id]);

            // Validar que el monto no exceda el saldo pendiente
            $abonoTotal = $cuota->operaciones()->where('estado', '!=', 'anulado')->sum('abono');
            $saldoPendiente = $cuota->monto - $abonoTotal;
            
            if ($request->monto_pagado > $saldoPendiente) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto pagado (S/. {$request->monto_pagado}) excede el saldo pendiente (S/. {$saldoPendiente})",
                ], 400);
            }

            DB::beginTransaction();

            // Crear la operación de pago
            $operacion = \App\Models\Operacion::create([
                'prestamo_id' => $cuota->prestamo_id,
                'cuota_id' => $cuota->id,
                'tipo_operacion' => 'Pago de cuota',
                'fecha' => $request->fecha_pago,
                'abono' => $request->monto_pagado,
                'saldo_anterior' => $saldoPendiente + $request->monto_pagado,
                'saldo_actual' => $saldoPendiente,
                'metodo_pago_id' => $request->metodo_pago_id,
                'observaciones' => $request->observaciones,
                'estado' => 'registrado',
                'user_id' => auth()->id(),
            ]);

            // Actualizar el monto pagado en la cuota
            $nuevoMontoPagado = $abonoTotal + $request->monto_pagado;
            $cuota->update([
                'monto_pagado' => $nuevoMontoPagado,
                'fecha_ultimo_pago' => now(),
            ]);

            // Verificar si la cuota está completamente pagada
            if ($nuevoMontoPagado >= $cuota->monto) {
                $cuota->update(['estado' => 'pagado']);
                \Log::info('EmitirCuota - Cuota marcada como completamente pagada');
            }

            // Actualizar el estado del préstamo si es necesario
            $this->actualizarEstadoPrestamo($cuota->prestamo_id);

            DB::commit();

            \Log::info('EmitirCuota - Pago de cuota registrado exitosamente', [
                'operacion_id' => $operacion->id,
                'monto_pagado' => $request->monto_pagado,
                'nuevo_saldo' => $saldoPendiente - $request->monto_pagado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago de cuota registrado exitosamente',
                'operacion_id' => $operacion->id,
                'saldo_restante' => $saldoPendiente - $request->monto_pagado,
                'cuota_completada' => $nuevoMontoPagado >= $cuota->monto
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('EmitirCuota - Error capturado', [
                'error_message' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago de cuota: ' . $e->getMessage(),
            ], 500);
        }
    }
    private function actualizarEstadoPrestamo($prestamoId)
{
    try {
        $prestamo = Prestamo::with('cuotas')->find($prestamoId);
        
        if (!$prestamo) return;

        $totalCuotas = $prestamo->cuotas->count();
        $cuotasPagadas = $prestamo->cuotas->where('estado', 'pagado')->count();
        
        if ($cuotasPagadas == $totalCuotas) {
            $prestamo->update(['estado' => 'Pagado']);
        } elseif ($cuotasPagadas > 0) {
            // Verificar si hay cuotas vencidas
            $cuotasVencidas = $prestamo->cuotas->where('fecha_pago', '<', now())
                ->where('estado', '!=', 'pagado')
                ->count();
                
            if ($cuotasVencidas > 0) {
                $prestamo->update(['estado' => 'Moroso']);
            } else {
                $prestamo->update(['estado' => 'Vigente']);
            }
        }
        
    } catch (\Exception $e) {
        \Log::error('Error al actualizar estado del préstamo: ' . $e->getMessage());
    }
}

    /**
     * Previsualizar datos del comprobante antes de generarlo
     */
    public function previewCuota($cuotaId)
    {
        try {
            $cuota = Cuota::with(['prestamo.cliente.persona'])->findOrFail($cuotaId);
            $prestamo = $cuota->prestamo;
            $cliente = $prestamo->cliente;
            $persona = $cliente->persona;

            // Obtener dirección del cliente desde la tabla direcciones
            $direccionCliente = null;
            if ($persona) {
                $direccion = \App\Models\Direccion::where('persona_id', $persona->id)
                    ->where('estado', 1)
                    ->orderBy('created_at', 'desc')
                    ->first();
                $direccionCliente = $direccion ? ($direccion->direccion_completa ?? $direccion->direccion) : null;
            }

            // Obtener configuración SUNAT
            $configuracionSunat = \App\Models\ConfiguracionSunat::obtenerActiva();
            if (!$configuracionSunat) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración SUNAT activa',
                ], 400);
            }

            // Obtener serie y número
            $serie = $configuracionSunat->serie_boleta;
            $ultimoComprobante = Comprobante::where('serie', $serie)
                ->where('tipo_comprobante', '03')
                ->orderBy('numero', 'desc')
                ->first();
            $numero = $ultimoComprobante ? $ultimoComprobante->numero + 1 : 1;

            // Obtener montos desglosados desde la cuota
            // La tabla cuotas ya tiene los campos: pago_capital, interes, comision, igv
            $capital = floatval($cuota->pago_capital ?? 0);
            $interes = floatval($cuota->interes ?? 0);
            $comision = floatval($cuota->comision ?? 0);
            $igv = floatval($cuota->igv ?? 0);
            $montoTotal = floatval($cuota->monto);

            // Verificar y ajustar diferencias de redondeo
            $sumaComponentes = $capital + $interes + $comision + $igv;
            $diferencia = round($montoTotal - $sumaComponentes, 2);

            // Si hay diferencia por redondeo (menor a 1 sol), ajustar en el capital
            if (abs($diferencia) > 0.01 && abs($diferencia) < 1.00) {
                \Log::info("Ajuste de redondeo en cuota {$cuota->id}: diferencia S/ {$diferencia}");
                $capital += $diferencia;
            }

            // Preparar items para la previsualización
            $items = [];

            if ($capital > 0) {
                $items[] = [
                    'cantidad' => 1,
                    'unidad' => 'NIU',
                    'descripcion' => "Capital - Cuota #{$cuota->numero} - Préstamo {$prestamo->codigo}",
                    'valor_unitario' => round($capital, 2),
                    'valor_venta' => round($capital, 2),
                    'tipo_afectacion' => 'EXONERADO',
                ];
            }

            if ($interes > 0) {
                $items[] = [
                    'cantidad' => 1,
                    'unidad' => 'NIU',
                    'descripcion' => "Interés - Cuota #{$cuota->numero}",
                    'valor_unitario' => round($interes, 2),
                    'valor_venta' => round($interes, 2),
                    'tipo_afectacion' => 'EXONERADO',
                ];
            }

            if ($comision > 0) {
                $items[] = [
                    'cantidad' => 1,
                    'unidad' => 'NIU',
                    'descripcion' => "Comisión por Gestión - Cuota #{$cuota->numero}",
                    'valor_unitario' => round($comision, 2),
                    'valor_venta' => round($comision, 2),
                    'tipo_afectacion' => 'GRAVADO',
                ];
            }

            // Validaciones
            $validaciones = [];

            // Validar que la cuota esté pagada
            $abonoTotal = $cuota->operaciones()->where('estado', '!=', 'anulado')->sum('abono');
            if ($abonoTotal < $cuota->monto) {
                $validaciones[] = 'La cuota no está completamente pagada';
            }

            // Validar que no exista comprobante
            $comprobanteExistente = Comprobante::where('cuota_id', $cuota->id)
                ->whereIn('estado', ['ENVIADO', 'PENDIENTE', 'GENERADO_UBL', 'GENERADO_LOCAL'])
                ->exists();
            if ($comprobanteExistente) {
                $validaciones[] = 'Ya existe un comprobante emitido para esta cuota';
            }

            // Validar datos del cliente
            if (!$persona->documento) {
                $validaciones[] = 'El cliente no tiene documento registrado';
            }
            if (!$persona->nombres && !$persona->ape_pat) {
                $validaciones[] = 'El cliente no tiene nombre completo registrado';
            }

            // Preparar respuesta
            $previewData = [
                'empresa' => [
                    'razon_social' => $configuracionSunat->razon_social ?? 'EMPRESA',
                    'ruc' => $configuracionSunat->ruc ?? '',
                    'direccion' => $configuracionSunat->direccion ?? '',
                    'telefono' => $configuracionSunat->telefono ?? null,
                    'email' => $configuracionSunat->email ?? null,
                ],
                'cliente' => [
                    'tipo_documento' => strlen($persona->documento) == 11 ? '6' : '1',
                    'numero_documento' => $persona->documento ?? '',
                    'razon_social' => trim(($persona->nombres ?? '') . ' ' . ($persona->ape_pat ?? '') . ' ' . ($persona->ape_mat ?? '')),
                    'direccion' => $direccionCliente, // Dirección desde tabla direcciones
                ],
                'serie' => $serie,
                'numero' => str_pad($numero, 8, '0', STR_PAD_LEFT),
                'fecha_emision' => now()->format('d/m/Y'),
                'moneda' => 'PEN',
                'prestamo_codigo' => $prestamo->numero_prestamo, // Usar accessor numero_prestamo
                'cuota_numero' => $cuota->numero,
                // Montos desglosados (vienen de la tabla cuotas)
                'capital' => $capital,
                'interes' => $interes,
                'comision' => $comision,
                'igv' => $igv,
                'monto_total' => $montoTotal,
                'items' => $items,
                'validaciones' => $validaciones,
            ];

            return response()->json([
                'success' => true,
                'data' => $previewData,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al generar preview de comprobante', [
                'cuota_id' => $cuotaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar datos: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generarComprobanteCuota(Request $request)
    {
        \Log::info('GenerarComprobanteCuota - Iniciando proceso', [
            'request_data' => $request->all(),
        ]);

        try {
            $request->validate([
                'cuota_id' => 'required|exists:cuotas,id',
                'tipo_comprobante' => 'required|in:01,03',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $cuota = Cuota::with(['prestamo.cliente.persona'])->findOrFail($request->cuota_id);
            $prestamo = $cuota->prestamo;
            $cliente = $prestamo->cliente;

            // Validar pagos completados
            $abonoTotal = $cuota->operaciones()->where('estado', '!=', 'anulado')->sum('abono');
            if ($abonoTotal < $cuota->monto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden generar comprobantes para cuotas completamente pagadas',
                ], 400);
            }

            // Verificar existencia
            $comprobanteExistente = Comprobante::where('cuota_id', $cuota->id)
                ->whereIn('estado', ['ENVIADO', 'PENDIENTE', 'GENERADO_UBL', 'GENERADO_LOCAL'])
                ->exists();

            if ($comprobanteExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un comprobante emitido para esta cuota',
                ], 400);
            }

            DB::beginTransaction();

            $configuracionSunat = \App\Models\ConfiguracionSunat::obtenerActiva();
            if (!$configuracionSunat) {
                throw new \Exception('No hay configuración SUNAT activa');
            }

            $serie = $request->tipo_comprobante == '01' ? $configuracionSunat->serie_factura : $configuracionSunat->serie_boleta;
            
            // Obtener correlativo
            $ultimoComprobante = Comprobante::where('serie', $serie)
                ->where('tipo_comprobante', $request->tipo_comprobante)
                ->orderBy('numero', 'desc')
                ->first();

            $numero = $ultimoComprobante ? $ultimoComprobante->numero + 1 : 1;

            // Usar mapper
            $payload = \App\Services\SireJsonMapper::mapCuotaToSireJson($cuota, $prestamo, $cliente);
            
            // Ajustar datos finales
            $payload['serie'] = $serie;
            $payload['numero'] = $numero;
            $payload['tipo_comprobante'] = $request->tipo_comprobante;

            // Crear registro
            $comprobante = Comprobante::create([
                'cliente_id' => $cliente->id,
                'prestamo_id' => $prestamo->id,
                'cuota_id' => $cuota->id,
                'tipo_comprobante' => $request->tipo_comprobante,
                'serie' => $serie,
                'numero' => $numero,
                'fecha_emision' => now(),
                'moneda' => 'PEN',
                'estado' => 'PENDIENTE',
                'items' => json_encode($payload['items'] ?? []),
                'total' => $cuota->monto,
            ]);

            // Validar que SIRE esté disponible
            if (!$this->sireApi) {
                throw new \Exception('SIRE no está habilitado. Active SIRE en la configuración SUNAT.');
            }

            // Enviar a API SIRE
            $result = $this->sireApi->enviarJson($payload);

            if ($result['success']) {
                $responseData = $result['data'] ?? [];
                
                $comprobante->update([
                    'estado' => 'ENVIADO',
                    'xml_firmado' => $responseData['xml_firmado'] ?? null,
                    'xml_content' => $responseData['xml_generado'] ?? null,
                    'cdr_zip' => $responseData['cdr'] ?? null,
                    'hash' => $responseData['hash'] ?? null,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Comprobante generado exitosamente',
                    'comprobante_id' => $comprobante->id,
                    'comprobante_numero' => $serie.'-'.str_pad($numero, 6, '0', STR_PAD_LEFT),
                ]);
            } else {
                $errorMessage = $result['error'] ?? 'Error desconocido';
                
                $comprobante->update([
                    'estado' => 'ERROR',
                    'mensaje_error' => $errorMessage,
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar comprobante: ' . $errorMessage,
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al generar comprobante: '.$e->getMessage(),
            ], 500);
        }
    }

    public function reenviar(Comprobante $comprobante)
    {
        try {
            \Log::info('Reenviar comprobante', ['comprobante_id' => $comprobante->id]);

            // Si el comprobante tiene cuota_id, usar el mapper para reconstruir el payload completo
            if ($comprobante->cuota_id) {
                $cuota = Cuota::with(['prestamo.cliente'])->find($comprobante->cuota_id);
                if ($cuota) {
                    $payload = \App\Services\SireJsonMapper::mapCuotaToSireJson(
                        $cuota,
                        $cuota->prestamo,
                        $cuota->prestamo->cliente
                    );

                    // Sobrescribir serie y correlativo con los del comprobante original
                    $payload['serie'] = $comprobante->serie;
                    $payload['correlativo'] = $comprobante->numero;
                    $payload['tipoDoc'] = $comprobante->tipo_comprobante ?? '03';
                    $payload['fechaEmision'] = $comprobante->fecha_emision ?
                        date('Y-m-d', strtotime($comprobante->fecha_emision)) : now()->format('Y-m-d');
                } else {
                    throw new \Exception('No se encontró la cuota asociada al comprobante');
                }
            } else {
                // Fallback: construir payload manualmente si no hay cuota_id
                $cliente = $comprobante->cliente;
                $items = json_decode($comprobante->items, true);

                if (empty($items)) {
                    throw new \Exception('El comprobante no tiene items guardados');
                }

                // Obtener configuración SUNAT
                $config = \App\Models\ConfiguracionSunat::obtenerActiva();

                $payload = [
                    'tipoDoc' => $comprobante->tipo_comprobante ?? '03',
                    'serie' => $comprobante->serie,
                    'correlativo' => $comprobante->numero,
                    'fechaEmision' => $comprobante->fecha_emision ?
                        date('Y-m-d', strtotime($comprobante->fecha_emision)) : now()->format('Y-m-d'),
                    'tipoMoneda' => 'PEN',
                    'client' => [
                        'tipoDoc' => strlen($cliente->persona->documento) == 11 ? '6' : '1',
                        'numDoc' => $cliente->persona->documento,
                        'rznSocial' => $cliente->persona->ape_pat . ' ' . $cliente->persona->ape_mat . ', ' . $cliente->persona->nombres,
                    ],
                    'company' => [
                        'ruc' => $config->ruc,
                        'razonSocial' => $config->razon_social,
                        'nombreComercial' => $config->nombre_comercial ?? $config->razon_social,
                        'address' => [
                            'direccion' => $config->direccion,
                            'provincia' => $config->provincia,
                            'departamento' => $config->departamento,
                            'distrito' => $config->distrito,
                            'ubigeo' => $config->ubigeo,
                        ]
                    ],
                    'details' => $items,
                    'mtoOperGravadas' => $comprobante->subtotal ?? 0,
                    'mtoIGV' => $comprobante->igv ?? 0,
                    'mtoImpVenta' => $comprobante->total ?? 0,
                ];
            }

            // Validar que SIRE esté disponible
            if (!$this->sireApi) {
                throw new \Exception('SIRE no está habilitado. Active SIRE en la configuración SUNAT.');
            }

            // Usar enviarJson (mismo método que funciona para comprobantes nuevos)
            // Este método usa Greenter con credenciales SOL configuradas correctamente
            $result = $this->sireApi->enviarJson($payload);

            if ($result['success']) {
                $responseData = $result['data'] ?? [];
                
                $comprobante->update([
                    'estado' => 'ENVIADO', // O ACEPTADO si la API lo confirma
                    'xml_firmado' => $responseData['xml_firmado'] ?? $comprobante->xml_firmado,
                    'xml_content' => $responseData['xml_generado'] ?? $comprobante->xml_content,
                    'cdr_zip' => $responseData['cdr'] ?? null,
                    'hash' => $responseData['hash'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Comprobante reenviado exitosamente',
                ]);
            } else {
                 $comprobante->update([
                    'estado' => 'ERROR',
                    'mensaje_error' => $result['error'] ?? 'Error desconocido',
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al reenviar: ' . ($result['error'] ?? 'Error desconocido'),
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reenviar todos los comprobantes con error
     */
    public function reenviarTodos()
    {
        return response()->json([
            'success' => false,
            'message' => 'Funcionalidad deshabilitada temporalmente durante la migración a SIRE.',
        ]);
    }

    public function consultarEstado(Comprobante $comprobante)
    {
        try {
            $config = \App\Models\ConfiguracionSunat::first();

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay configuración de SUNAT disponible',
                ]);
            }

            // Si ya tiene CDR guardado, parsear y mostrar la información
            if ($comprobante->cdr_zip) {
                try {
                    $sireApi = new \App\Services\SireApiService($config);

                    // Extraer y parsear CDR existente
                    $tempPath = sys_get_temp_dir() . '/' . uniqid('cdr_') . '.zip';
                    file_put_contents($tempPath, $comprobante->cdr_zip);

                    $zip = new \ZipArchive();
                    $cdrXml = null;
                    if ($zip->open($tempPath) === true) {
                        // Buscar el archivo XML del CDR (puede estar en índice 0 o 1)
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $content = $zip->getFromIndex($i);
                            if ($content && strlen($content) > 0 && strpos($content, '<?xml') === 0) {
                                $cdrXml = $content;
                                break;
                            }
                        }
                        $zip->close();
                    }
                    unlink($tempPath);

                    // Parsear el CDR XML
                    $cdrInfo = $this->parsearCDRLocal($cdrXml);

                    return response()->json([
                        'success' => true,
                        'estado' => $cdrInfo['estado_sunat'] ?? 'ACEPTADO',
                        'codigo_respuesta' => $cdrInfo['codigo_respuesta'] ?? '0',
                        'mensaje' => $cdrInfo['mensaje_respuesta'] ?? 'Comprobante aceptado por SUNAT',
                        'fecha_respuesta' => $cdrInfo['fecha_respuesta'] ?? $comprobante->updated_at,
                        'tiene_cdr' => true,
                        'origen' => 'local', // CDR guardado localmente
                    ]);

                } catch (\Exception $e) {
                    // Si falla el parseo, mostrar que tiene CDR pero no se pudo leer
                    return response()->json([
                        'success' => true,
                        'estado' => $comprobante->estado,
                        'mensaje' => 'Comprobante tiene CDR de SUNAT (aceptado)',
                        'tiene_cdr' => true,
                        'origen' => 'local',
                        'observacion' => 'CDR existe pero no se pudo parsear: ' . $e->getMessage(),
                    ]);
                }
            }

            // Si no tiene CDR guardado, intentar consultar en SUNAT (solo funciona en producción)
            $sireApi = new \App\Services\SireApiService($config);
            $resultado = $sireApi->consultarCDR(
                $comprobante->tipo_comprobante,
                $comprobante->serie,
                (int) $comprobante->numero
            );

            if ($resultado['success']) {
                // Guardar el CDR obtenido
                $comprobante->cdr_zip = $resultado['cdr_zip'];
                $comprobante->save();

                return response()->json([
                    'success' => true,
                    'estado' => $resultado['estado_sunat'],
                    'codigo_respuesta' => $resultado['codigo_respuesta'],
                    'mensaje' => $resultado['mensaje_respuesta'],
                    'fecha_respuesta' => $resultado['fecha_respuesta'],
                    'tiene_cdr' => true,
                    'origen' => 'sunat', // CDR consultado en SUNAT
                ]);
            }

            // No se pudo consultar (ambiente TEST o comprobante no existe)
            $esAmbienteTest = !$config->modo_produccion;

            return response()->json([
                'success' => false,
                'message' => $esAmbienteTest
                    ? 'Consulta no disponible en ambiente de PRUEBAS. El comprobante fue enviado exitosamente pero SUNAT no permite consultar CDR en ambiente TEST.'
                    : ($resultado['mensaje'] ?? $resultado['error']),
                'estado' => $comprobante->estado,
                'tiene_cdr' => false,
                'ambiente' => $esAmbienteTest ? 'TEST' : 'PRODUCCIÓN',
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al consultar estado en SUNAT', [
                'comprobante_id' => $comprobante->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar SUNAT: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Parsear CDR XML local para extraer información
     */
    private function parsearCDRLocal(?string $cdrXml): array
    {
        if (!$cdrXml) {
            return [];
        }

        try {
            $xml = new \SimpleXMLElement($cdrXml);
            $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
            $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

            $responseCode = (string) ($xml->xpath('//cbc:ResponseCode')[0] ?? '0');
            $description = (string) ($xml->xpath('//cbc:Description')[0] ?? 'Aceptado');
            $issueDate = (string) ($xml->xpath('//cbc:IssueDate')[0] ?? '');
            $issueTime = (string) ($xml->xpath('//cbc:IssueTime')[0] ?? '');

            $estado = 'ACEPTADO';
            if (in_array($responseCode, ['0', '0001', '0002', '0003', '0004'])) {
                $estado = 'ACEPTADO';
            } elseif ($responseCode === '0100') {
                $estado = 'RECHAZADO';
            } elseif (in_array($responseCode, ['0098', '0099'])) {
                $estado = 'OBSERVADO';
            }

            return [
                'estado_sunat' => $estado,
                'codigo_respuesta' => $responseCode,
                'mensaje_respuesta' => $description,
                'fecha_respuesta' => $issueDate && $issueTime ? "$issueDate $issueTime" : null,
            ];

        } catch (\Exception $e) {
            \Log::error('Error al parsear CDR XML local', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Ver la respuesta completa de SUNAT (CDR + Logs)
     */
    public function verRespuestaSunat(Comprobante $comprobante)
    {
        try {
            $data = [
                'comprobante' => [
                    'id' => $comprobante->id,
                    'serie' => $comprobante->serie,
                    'numero' => $comprobante->numero,
                    'tipo' => $comprobante->tipo_comprobante,
                    'estado_actual' => $comprobante->estado,
                    'fecha_emision' => $comprobante->fecha_emision->format('Y-m-d H:i:s'),
                    'total' => $comprobante->total,
                    'hash' => $comprobante->hash,
                    'codigo_error' => $comprobante->codigo_error,
                    'mensaje_error' => $comprobante->mensaje_error,
                ],
                'cdr' => null,
                'cdr_xml_raw' => null,
                'logs_relacionados' => [],
            ];

            // Extraer y parsear CDR si existe
            if ($comprobante->cdr_zip) {
                try {
                    $tempPath = sys_get_temp_dir() . '/' . uniqid('cdr_') . '.zip';
                    file_put_contents($tempPath, $comprobante->cdr_zip);

                    $zip = new \ZipArchive();
                    $cdrXml = null;
                    if ($zip->open($tempPath) === true) {
                        // Buscar el archivo XML del CDR (puede estar en índice 0 o 1)
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $content = $zip->getFromIndex($i);
                            if ($content && strlen($content) > 0 && strpos($content, '<?xml') === 0) {
                                $cdrXml = $content;
                                break;
                            }
                        }
                        $zip->close();
                    }
                    unlink($tempPath);

                    if ($cdrXml) {
                        // Parsear CDR
                        $cdrInfo = $this->parsearCDRLocal($cdrXml);

                        // Extraer información adicional del XML
                        $xml = new \SimpleXMLElement($cdrXml);
                        $xml->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
                        $xml->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
                        $xml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

                        $data['cdr'] = [
                            'estado' => $cdrInfo['estado_sunat'] ?? 'DESCONOCIDO',
                            'codigo_respuesta' => $cdrInfo['codigo_respuesta'] ?? '-',
                            'mensaje_respuesta' => $cdrInfo['mensaje_respuesta'] ?? '-',
                            'fecha_respuesta' => $cdrInfo['fecha_respuesta'] ?? '-',
                            'referencia_id' => (string) ($xml->xpath('//cbc:ID')[0] ?? '-'),
                            'issue_date' => (string) ($xml->xpath('//cbc:IssueDate')[0] ?? '-'),
                            'issue_time' => (string) ($xml->xpath('//cbc:IssueTime')[0] ?? '-'),
                            'response_code' => (string) ($xml->xpath('//cbc:ResponseCode')[0] ?? '-'),
                            'response_description' => (string) ($xml->xpath('//cbc:Description')[0] ?? '-'),
                            'document_reference' => (string) ($xml->xpath('//cac:DocumentReference/cbc:ID')[0] ?? '-'),
                            'tiene_firma_digital' => count($xml->xpath('//ds:Signature')) > 0,
                        ];

                        // XML completo formateado
                        $dom = new \DOMDocument();
                        $dom->preserveWhiteSpace = false;
                        $dom->formatOutput = true;
                        $dom->loadXML($cdrXml);
                        $data['cdr_xml_raw'] = $dom->saveXML();
                    }

                } catch (\Exception $e) {
                    $data['cdr'] = ['error' => 'Error al parsear CDR: ' . $e->getMessage()];
                }
            }

            // Buscar logs relacionados en Laravel
            $logPath = storage_path('logs/laravel.log');
            if (file_exists($logPath)) {
                $searchTerms = [
                    "serie\":\"{$comprobante->serie}\"",
                    "numero\":{$comprobante->numero}",
                    "serie\"=>\"{$comprobante->serie}\"",
                    "{$comprobante->serie}-{$comprobante->numero}",
                    "comprobante_id\":{$comprobante->id}",
                    "comprobante_id\"=>{$comprobante->id}",
                ];

                $logContent = file_get_contents($logPath);
                $logLines = explode("\n", $logContent);

                // Buscar las últimas 50 líneas relacionadas
                $matchingLogs = [];
                foreach (array_reverse($logLines) as $line) {
                    if (count($matchingLogs) >= 50) break;

                    foreach ($searchTerms as $term) {
                        if (stripos($line, $term) !== false) {
                            $matchingLogs[] = $line;
                            break;
                        }
                    }
                }

                $data['logs_relacionados'] = array_reverse($matchingLogs);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener respuesta SUNAT', [
                'comprobante_id' => $comprobante->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function descargarCdr(Comprobante $comprobante)
    {
        try {
            if (!$comprobante->cdr_zip) {
                return back()->with('error', 'Este comprobante no tiene CDR disponible');
            }

            $filename = sprintf(
                'R-%s-%s-%s.zip',
                $comprobante->serie,
                str_pad($comprobante->numero, 8, '0', STR_PAD_LEFT),
                date('Ymd', strtotime($comprobante->fecha_emision))
            );

            // CRÍTICO: El CDR se guarda en base64, debe decodificarse antes de enviarlo
            $cdrBinario = base64_decode($comprobante->cdr_zip);

            if ($cdrBinario === false) {
                \Log::error('Error al decodificar CDR base64', [
                    'comprobante_id' => $comprobante->id,
                    'cdr_length' => strlen($comprobante->cdr_zip),
                ]);
                return back()->with('error', 'El CDR está corrupto (error de decodificación)');
            }

            return response($cdrBinario)
                ->header('Content-Type', 'application/zip')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            \Log::error('Error al descargar CDR', [
                'comprobante_id' => $comprobante->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al descargar CDR: ' . $e->getMessage());
        }
    }

    public function anular(Request $request, Comprobante $comprobante)
    {
        return response()->json([
            'success' => false,
            'message' => 'Anulación no disponible actualmente (Migración SIRE).',
        ], 400);
    }

    public function regularizar(Comprobante $comprobante)
    {
        return response()->json([
            'success' => false,
            'message' => 'Regularización no disponible actualmente (Migración SIRE).',
        ]);
    }

    public function consultarSunat(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Consulta masiva no disponible actualmente.',
            'total' => 0,
            'errores' => [],
        ]);
    }

    public function generarNotaCredito(Request $request, Comprobante $comprobante)
    {
        return response()->json([
            'success' => false,
            'message' => 'Emisión de Notas de Crédito no disponible actualmente (Migración SIRE).',
        ], 400);
    }

    public function generarNotaDebito(Request $request, Comprobante $comprobante)
    {
        return response()->json([
            'success' => false,
            'message' => 'Emisión de Notas de Débito no disponible actualmente (Migración SIRE).',
        ], 400);
    }
}

