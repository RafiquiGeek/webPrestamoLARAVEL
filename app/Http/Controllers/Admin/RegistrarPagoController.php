<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use App\Http\Controllers\Controller;
use App\Models\AbonoMoraFavor;
use App\Models\Comprobante;
use App\Models\Cuenta;
use App\Models\Cuota;
use App\Models\MetodoDePago;
use App\Models\MoraCuota;
use App\Models\Operacion;
use App\Models\OperacionCuota;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\User;
use App\Services\EstadoPrestamoService;

use Carbon\Carbon; // Asegúrate de importar este enum
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RegistrarPagoController extends Controller
{
    protected EstadoPrestamoService $estadoService;

    public function __construct(EstadoPrestamoService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    public function create(Request $request, $prestamo_id)
    {
        $cuota_id = $request->query('cuota_id');
        Log::info("Cargando el préstamo con ID: {$prestamo_id}");

        $usuarios = User::all();

        $prestamo = Prestamo::with([
            'cliente.persona',
            'carterasAnalista.user.persona',
            'carterasJcc.user.persona',
            'carterasAsesor.user.persona',
            'cuotas.operaciones.metodoDePago',
        ])->find($prestamo_id);

        if (! $prestamo) {
            Log::warning("Préstamo no encontrado con ID: {$prestamo_id}");

            return redirect()->route('admin.prestamos.index')->with('error', 'Préstamo no encontrado');
        }

        $cuota_normal = Cuota::where('id', $cuota_id)->value('monto') ?? 0;
        Log::info("Monto de la cuota normal: S/{$cuota_normal}");

        $capital_total = $prestamo->cantidad_solicitada;

        $cuotas_pagadas = Cuota::where('prestamo_id', $prestamo_id)
            ->where('estado', 2)
            ->sum('monto');

        $saldo_prestamo = $capital_total - $cuotas_pagadas;

        $cuotasPendientes = Cuota::where('prestamo_id', $prestamo_id)
            ->whereIn('estado', [0, 1, 3]) // PENDIENTE, PARCIAL, VENCIDO - todas las cuotas que pueden recibir pagos
            ->orderBy('numero')
            ->with(['operaciones' => function ($query) {
                $query->orderBy('fecha', 'desc');
            }])
            ->get()
            ->map(function ($cuota) {
                $cuota->ultima_fecha_abono = $cuota->operaciones->first()
                    ? Carbon::parse($cuota->operaciones->first()->fecha)->format('d-m-Y')
                    : '--';
                $cuota->numero_abonos = $cuota->operaciones->count();
                $cuota->fecha_pago_formateada = Carbon::parse($cuota->fecha_pago)->format('d/m/Y');

                return $cuota;
            });

        Log::info('Cuotas pendientes/parciales: '.$cuotasPendientes->count());

        // Obtener el monto fijo diario de mora del préstamo
        $montoMoraDiario = $prestamo->mora ?? 5.00; // Monto fijo por día

        // NUEVA LÓGICA: Calcular moras dinámicamente basándose en cuotas vencidas
        $morasPendientes = collect();
        $totalMorasCalculadas = 0;

        foreach ($cuotasPendientes as $cuota) {
            $fechaVencimiento = Carbon::parse($cuota->fecha_pago);
            $fechaActual = Carbon::now();

            if ($fechaVencimiento->lt($fechaActual)) {
                $diasMoraTotales = $fechaVencimiento->diffInDays($fechaActual);
                $montoCuotaPendiente = $cuota->monto - ($cuota->monto_pagado ?? 0);

                if ($montoCuotaPendiente > 0 && $diasMoraTotales > 0) {
                    // LÓGICA NUEVA: Cada cuota solo puede acumular mora por máximo 7 días
                    $diasMora = min($diasMoraTotales, 7);

                    // Calcular mora: monto fijo diario * días de mora (máximo 7 días)
                    $montoTotalMora = $montoMoraDiario * $diasMora;

                    // CORRECCIÓN: Verificar si ya existe mora registrada para esta cuota
                    // Incluir PENDIENTES Y PARCIALES para ser consistente con show.blade.php
                    $moraExistente = MoraCuota::where('cuota_id', $cuota->id)
                        ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                        ->first();

                    if ($moraExistente) {
                        // Actualizar mora existente
                        $montoPagadoMora = 0;
                        if (Schema::hasColumn('mora_cuota', 'monto_pagado')) {
                            $montoPagadoMora = $moraExistente->monto_pagado ?? 0;
                        } else {
                            $montoPagadoMora = DB::table('operacion_mora')
                                ->join('operaciones', 'operacion_mora.operacion_id', '=', 'operaciones.id')
                                ->where('operacion_mora.mora_cuota_id', $moraExistente->id)
                                ->sum('operaciones.abono');
                        }

                        $moraExistente->dias_mora = $diasMora;
                        $moraExistente->monto = $montoTotalMora;
                        $moraExistente->fecha = $fechaActual;
                        $moraExistente->monto_pendiente = max(0, $montoTotalMora - $montoPagadoMora);
                        $moraExistente->fecha_formateada = $fechaActual->format('d-m-Y');
                        $morasPendientes->push($moraExistente);
                        $totalMorasCalculadas += $moraExistente->monto_pendiente;
                    } else {
                        // Crear objeto mora para mostrar (sin guardarlo aún en BD)
                        $moraNueva = new MoraCuota([
                            'cuota_id' => $cuota->id,
                            'fecha' => $fechaActual,
                            'dias_mora' => $diasMora,
                            'monto' => $montoTotalMora,
                            'estado' => MoraCuotaEstado::PENDIENTE->value,
                        ]);
                        $moraNueva->id = 'nueva_'.$cuota->id; // ID temporal para la vista
                        $moraNueva->monto_pendiente = $montoTotalMora;
                        $moraNueva->fecha_formateada = $fechaActual->format('d-m-Y');
                        $moraNueva->cuota_numero = $cuota->numero; // Para mostrar en la vista
                        $morasPendientes->push($moraNueva);
                        $totalMorasCalculadas += $montoTotalMora;
                    }
                }
            }
        }

        Log::info('Moras calculadas dinámicamente: '.$morasPendientes->count());
        Log::info("Total de moras calculadas: S/{$totalMorasCalculadas}");

        $totalCuotas = $cuotasPendientes->sum('monto');
        $totalMoras = $totalMorasCalculadas;

        // Calcular montos por defecto
        $montoCuotaPorDefecto = 0;
        $montoMoraPorDefecto = $totalMorasCalculadas; // Usar el total calculado para consistencia

        if ($cuota_id) {
            // Encontrar la cuota específica por ID para calcular solo el monto de cuota
            $cuotaEspecifica = Cuota::find($cuota_id);

            if ($cuotaEspecifica && $cuotaEspecifica->prestamo_id == $prestamo_id) {
                // MONTO DE CUOTA: Solo el saldo pendiente de esta cuota específica
                $montoPagadoCuota = 0;
                if (Schema::hasColumn('cuotas', 'monto_pagado')) {
                    $montoPagadoCuota = $cuotaEspecifica->monto_pagado ?? 0;
                } else {
                    $montoPagadoCuota = $cuotaEspecifica->operaciones()->sum('abono') ?? 0;
                }

                $montoCuotaPorDefecto = max(0, $cuotaEspecifica->monto - $montoPagadoCuota);

                Log::info("Cuota #{$cuotaEspecifica->numero}: Monto total S/{$cuotaEspecifica->monto}, Pagado S/{$montoPagadoCuota}, Pendiente S/{$montoCuotaPorDefecto}");
            }
        }

        // Log para debugging de consistencia
        Log::info("CONSISTENCIA - Total moras en Detalles: S/{$totalMorasCalculadas}, Monto mora por defecto: S/{$montoMoraPorDefecto}");

        $metodosDePago = MetodoDePago::where('status', 1)->get();

        return view('admin.Prestamos.RegistrarPago.create', compact(
            'prestamo',
            'saldo_prestamo',
            'cuota_normal',
            'cuota_id',
            'usuarios',
            'cuotasPendientes',
            'morasPendientes',
            'totalCuotas',
            'totalMoras',
            'metodosDePago',
            'montoCuotaPorDefecto',
            'montoMoraPorDefecto'
        ));
    }

    public function store(Request $request)
    {
        Log::info('Iniciando proceso de registro de pago');
        Log::info('Datos recibidos:', $request->all());

        $abono_cuotas = floatval($request->abono_cuotas ?? 0);
        $abono_moras = floatval($request->abono_moras ?? 0);

        // Reglas de validación ajustadas según el método de pago
        $rules = [
            'abono_cuotas' => 'required_without:abono_moras|numeric|min:0',
            'abono_moras' => 'required_without:abono_cuotas|numeric|min:0',
            'metodoPago' => 'required|integer|exists:metodos_de_pago,id',
            'prestamo_id' => 'required|integer|exists:prestamos,id',
            'cuota_id' => 'nullable|integer|exists:cuotas,id',
            'user_id' => 'required|exists:users,id',
            'comentario' => 'nullable|string',
            'entidad_bancaria' => 'nullable|string|max:100',
        ];

        // Reglas condicionales según el método de pago
        if ($request->metodoPago == 1) { // Efectivo
            $rules['codigo'] = 'nullable|string';
            $rules['fecha_codigo'] = 'required|date'; // Fecha para efectivo
            $rules['voucher'] = 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048'; // Permitir voucher en efectivo también
        } else { // Transferencia, tarjeta, etc.
            $rules['nro_operacion'] = 'required|string';
            $rules['fecha_operacion'] = 'required|date'; // Fecha para transferencias
            $rules['voucher'] = 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048';
            $rules['entidad_bancaria'] = 'required|string|max:100';
        }

        $request->validate($rules);

        // Validación adicional: verificar que no se repita número de operación por entidad bancaria
        if ($request->metodoPago != 1) { // Solo para métodos que no sean efectivo
            $nro_operacion = $request->nro_operacion;
            $entidad_bancaria = $request->entidad_bancaria;

            $operacionExistente = \App\Models\Operacion::where('codigo', $nro_operacion)
                ->where('entidad_bancaria', $entidad_bancaria)
                ->where('estado', '!=', 'anulado') // Excluir operaciones anuladas
                ->first();

            if ($operacionExistente) {
                Log::warning("Validación fallida: Número de operación {$nro_operacion} ya existe para {$entidad_bancaria}");

                return redirect()->back()
                    ->withInput()
                    ->with('error', "El número de operación {$nro_operacion} ya está registrado para {$entidad_bancaria}. Verifique los datos.");
            }
        }

        Log::info('Validación de datos completada');

        try {
            DB::beginTransaction();
            Log::info('Iniciada transacción de base de datos');

            $prestamo_id = $request->prestamo_id;
            $cuota_id = $request->cuota_id; // CAPTURAR LA CUOTA ESPECÍFICA
            $cuota_id_original = $cuota_id; // PRESERVAR PARA MORAS - No debe modificarse
            $metodoPago = $request->metodoPago;
            $user_id = $request->user_id;
            $comentario = $request->comentario;

            $cuota_info = $cuota_id ? ", Cuota específica: {$cuota_id}" : '';
            Log::info("Procesando pago - Préstamo ID: {$prestamo_id}, Abono cuotas: {$abono_cuotas}, Abono moras: {$abono_moras}, Método: {$metodoPago}, Usuario: {$user_id}{$cuota_info}");

            $codigo = null;
            $ruta_voucher = null;

            // Procesar el comprobante/voucher independientemente del método de pago
            if ($request->hasFile('voucher')) {
                $voucher = $request->file('voucher');
                $extension = strtolower($voucher->getClientOriginalExtension());
                $nombreValido = preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($voucher->getClientOriginalName(), PATHINFO_FILENAME));
                $nombreArchivo = time().'_'.substr($nombreValido, 0, 20).'.'.$extension;
                $ruta_voucher = $voucher->storeAs('vouchers', $nombreArchivo, 'public');
                Log::info("Voucher guardado en: {$ruta_voucher}");
            } else {
                Log::info('No se ha proporcionado voucher');
            }

            // Obtener los datos según el método de pago
            if ($metodoPago == 1) { // Efectivo
                $codigo = $request->codigo;
                $fecha = $request->fecha_codigo; // Usamos fecha_codigo del formulario
                Log::info("Método de pago: Efectivo, Código: {$codigo}, Fecha: {$fecha}");
            } else {
                // CORRECCIÓN: Guardar nro_operacion en el campo codigo para transferencias/depósitos/etc
                $codigo = $request->nro_operacion;
                $fecha = $request->fecha_operacion;
                Log::info("Método de pago distinto a efectivo, Nro Op (guardado como código): {$codigo}, Fecha: {$fecha}");
            }

            if ($abono_cuotas <= 0 && $abono_moras <= 0) {
                Log::warning('Validación fallida: No se ha proporcionado un monto válido para abono');

                return redirect()->back()->with('error', 'Debe registrar un monto válido para cuotas o moras.');
            }

            $prestamo = Prestamo::findOrFail($prestamo_id);
            Log::info("Préstamo encontrado - ID: {$prestamo_id}, Estado: {$prestamo->estado}, Cliente ID: {$prestamo->cliente_id}");

            if ($prestamo->estado === 'Finalizado') {
                Log::warning("Préstamo ya finalizado - ID: {$prestamo_id}");

                return redirect()->back()->with('error', 'El préstamo ya está finalizado y no puede recibir pagos.');
            }

            // Usando valores numéricos: 0 = por rendir, 1 = rendido
            $estado_rendicion = ($metodoPago == 1) ? 0 : null;
            Log::info("Estado de rendición: {$estado_rendicion}");

            Log::info('Creando operación general');

            $monto_total = $abono_cuotas + $abono_moras;
            Log::info("Monto total del abono: {$monto_total}");

            try {
                $operacionData = [
                    'cliente_id' => $prestamo->cliente_id,
                    'prestamo_id' => $prestamo_id,
                    'fecha' => $fecha,
                    'metodo_pago_id' => $metodoPago,
                    'abono' => $monto_total,
                    'tipo_operacion' => 'Pago general',
                    'codigo' => $codigo,
                    'user_id' => $user_id,
                    'comentario' => $comentario,
                    'estado_rendicion' => $estado_rendicion,
                    'entidad_bancaria' => $request->entidad_bancaria,
                ];

                // Determinar el campo correcto para el voucher
                if (Schema::hasColumn('operaciones', 'voucher_path')) {
                    $operacionData['voucher_path'] = $ruta_voucher;
                } elseif (Schema::hasColumn('operaciones', 'ruta_voucher')) {
                    $operacionData['ruta_voucher'] = $ruta_voucher;
                }

                $operacionGeneral = Operacion::create($operacionData);
                Log::info("Operación general creada - ID: {$operacionGeneral->id}");
            } catch (\Exception $e) {
                Log::error('Error al crear operación general: '.$e->getMessage());
                throw $e;
            }

            // PROCESAMIENTO DE CUOTAS - PRIORIDAD: CUOTA ESPECÍFICA SELECCIONADA
            $abono_cuotas_restante = $abono_cuotas;
            $cuotas_seleccionadas = []; // Array para recolectar IDs de cuotas afectadas

            while ($abono_cuotas_restante > 0) {
                $cuota = null;

                // PRIORIDAD 1: Si se especificó una cuota específica, usarla PRIMERO
                if (! empty($cuota_id)) {
                    $cuotaEspecifica = Cuota::where('id', $cuota_id)
                        ->where('prestamo_id', $prestamo_id)
                        ->whereIn('estado', [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL, CuotaEstado::VENCIDO])
                        ->whereRaw('(monto_pagado IS NULL OR monto_pagado < monto)')
                        ->select('id', 'numero', 'monto', 'interes', 'comision', 'igv', 'monto_pagado', 'pago_capital')
                        ->first();

                    if ($cuotaEspecifica) {
                        $cuota = $cuotaEspecifica;
                        Log::info("🎯 USANDO CUOTA ESPECÍFICA SELECCIONADA - ID: {$cuota_id}, Número: {$cuota->numero}");

                        // Limpiar cuota_id para que en la siguiente iteración (si queda saldo) use lógica secuencial
                        $cuota_id = null;
                    } else {
                        Log::warning("⚠️ Cuota específica {$cuota_id} no válida para pago (puede estar pagada o no existir)");
                        $cuota_id = null; // Limpiar para usar lógica secuencial
                    }
                }

                // PRIORIDAD 2: Si no hay cuota específica o ya fue procesada, usar lógica secuencial
                if (! $cuota) {
                    $cuota = Cuota::where('prestamo_id', $prestamo_id)
                        ->whereIn('estado', [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL, CuotaEstado::VENCIDO])
                        ->whereRaw('(monto_pagado IS NULL OR monto_pagado < monto)')
                        ->select('id', 'numero', 'monto', 'interes', 'comision', 'igv', 'monto_pagado', 'pago_capital')
                        ->orderBy('numero')
                        ->first();

                    if ($cuota) {
                        Log::info("📋 Usando lógica secuencial - Cuota #{$cuota->numero}");
                    }
                }

                if (! $cuota) {
                    Log::info("No hay más cuotas pendientes para aplicar el excedente de {$abono_cuotas_restante}.");
                    break;
                }

                $numeroCuota = $cuota->numero;
                $montoTotal = $cuota->monto;

                Log::info("Procesando cuota #{$numeroCuota} - ID: {$cuota->id}, Monto fijo: {$montoTotal}");

                $montoPagado = $cuota->monto_pagado ?? 0;
                $saldoCuota = $montoTotal - $montoPagado;

                if ($saldoCuota <= 0) {
                    Log::info("Cuota #{$numeroCuota} ya está completamente pagada. Actualizando estado y pasando a la siguiente.");
                    $cuota->estado = CuotaEstado::PAGADO->value; // 2
                    $cuota->save();

                    continue;
                }

                Log::info("Cuota #{$numeroCuota} - Monto fijo: {$montoTotal}, Ya pagado: {$montoPagado}, Saldo pendiente: {$saldoCuota}");

                $abonoCuota = min($abono_cuotas_restante, $saldoCuota);
                Log::info("Abono asignado a esta cuota: {$abonoCuota}");

                // Cálculos de distribución del pago - USANDO LA LÓGICA CORRECTA DE PrestamosController
                $capitalTotal = $cuota->pago_capital ?? 0;
                $interesBaseTotal = $cuota->interes ?? 0; // Interés base (sin IGV) - ya está correcto
                $comisionTotal = $cuota->comision ?? 0;
                $igvTotal = $cuota->igv ?? 0; // IGV del interés/comisión (ya calculado correctamente)

                // Verificar que la suma de componentes sea igual al monto total
                $sumaComponentes = $capitalTotal + $interesBaseTotal + $comisionTotal + $igvTotal;

                if (abs($sumaComponentes - $montoTotal) > 0.01) {
                    Log::warning("Inconsistencia en cuota {$cuota->id}: Capital({$capitalTotal}) + InterésBase({$interesBaseTotal}) + Comisión({$comisionTotal}) + IGV({$igvTotal}) = {$sumaComponentes} != Monto({$montoTotal})");
                }

                if ($montoTotal > 0) {
                    // Distribución proporcional exacta del pago - MISMA LÓGICA que en PrestamosController
                    $factorDistribucion = $abonoCuota / $montoTotal;

                    // Distribución proporcional de cada componente
                    $propCapital = $capitalTotal * $factorDistribucion;
                    $propInteresBase = $interesBaseTotal * $factorDistribucion; // Interés base pagado
                    $propComision = $comisionTotal * $factorDistribucion;

                    // IGV debe calcularse igual que en PrestamosController:
                    // IGV = 18% del (interés + comisión) pagado
                    $propIgv = round(($propInteresBase + $propComision) * 0.18, 2);

                    // Verificar que la suma sea exacta
                    $sumaCalculada = $propCapital + $propInteresBase + $propComision + $propIgv;
                    $diferencia = $abonoCuota - $sumaCalculada;

                    // Ajustar por diferencias de redondeo
                    if (abs($diferencia) > 0.005) {
                        // Ajustar el capital para que cuadre exactamente
                        $propCapital += $diferencia;
                        Log::info("Ajuste por redondeo aplicado al capital: {$diferencia}");
                    }

                    // Para efectos de registro
                    $propInteres = $propInteresBase; // Guardamos solo el interés base

                } else {
                    // Si no hay componentes definidos, asignar todo al capital
                    $propCapital = $abonoCuota;
                    $propInteres = 0;
                    $propComision = 0;
                    $propIgv = 0;
                }

                Log::info("Distribución del abono - Capital: {$propCapital}, Interés: {$propInteres}, Comisión: {$propComision}, IGV: {$propIgv}, Total: ".($propCapital + $propInteres + $propComision + $propIgv));

                try {
                    Log::info("Creando operación para cuota {$cuota->id}");

                    $operacionCuotaData = [
                        'cliente_id' => $prestamo->cliente_id,
                        'prestamo_id' => $prestamo_id,
                        'fecha' => $fecha,
                        'metodo_pago_id' => $metodoPago,
                        'abono' => $abonoCuota,
                        'tipo_operacion' => 'Pago de cuota',
                        'operacion_general_id' => $operacionGeneral->id,
                        'codigo' => $codigo,
                        'user_id' => $user_id,
                        'comentario' => $comentario,
                        'estado_rendicion' => $estado_rendicion,
                        'entidad_bancaria' => $request->entidad_bancaria,
                    ];

                    // Agregamos los componentes si la tabla los soporta
                    if (Schema::hasColumn('operaciones', 'pago_capital')) {
                        $operacionCuotaData['pago_capital'] = $propCapital;
                    }

                    if (Schema::hasColumn('operaciones', 'interes')) {
                        $operacionCuotaData['interes'] = $propInteres;
                    }

                    if (Schema::hasColumn('operaciones', 'comision')) {
                        $operacionCuotaData['comision'] = $propComision;
                    }

                    if (Schema::hasColumn('operaciones', 'igv')) {
                        $operacionCuotaData['igv'] = $propIgv;
                    }

                    // Determinar el campo correcto para el voucher
                    if (Schema::hasColumn('operaciones', 'voucher_path')) {
                        $operacionCuotaData['voucher_path'] = $ruta_voucher;
                    } elseif (Schema::hasColumn('operaciones', 'ruta_voucher')) {
                        $operacionCuotaData['ruta_voucher'] = $ruta_voucher;
                    }

                    $operacion = Operacion::create($operacionCuotaData);
                    Log::info("Operación creada para cuota - ID: {$operacion->id}");
                } catch (\Exception $e) {
                    Log::error('Error al crear operación para cuota: '.$e->getMessage());
                    throw $e;
                }

                try {
                    Log::info("Creando relación entre operación {$operacion->id} y cuota {$cuota->id}");
                    $tablaOperacionesCuota = 'operaciones_cuota';

                    try {
                        $relacion = OperacionCuota::create([
                            'cuota_id' => $cuota->id,
                            'operacion_id' => $operacion->id,
                            'monto_aplicado' => $abonoCuota, // IMPORTANTE: Agregar monto aplicado
                            'concepto' => 'pago_cuota',
                            'aplicado_en' => now(),
                        ]);
                        Log::info("Relación creada usando modelo OperacionCuota con monto_aplicado: {$abonoCuota}");
                    } catch (\Exception $e) {
                        Log::warning('Error al usar modelo OperacionCuota: '.$e->getMessage());
                        $resultado = DB::table($tablaOperacionesCuota)->insert([
                            'cuota_id' => $cuota->id,
                            'operacion_id' => $operacion->id,
                            'monto_aplicado' => $abonoCuota, // IMPORTANTE: Agregar monto aplicado
                            'concepto' => 'pago_cuota',
                            'aplicado_en' => now(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        Log::info('Relación creada con inserción directa: '.($resultado ? 'Éxito' : 'Fallido'));
                    }
                } catch (\Exception $e) {
                    Log::error('Error al crear relación operación-cuota: '.$e->getMessage());
                    throw $e;
                }

                // Actualizar el monto_pagado
                try {
                    $nuevoPagado = $montoPagado + $abonoCuota;

                    if ($nuevoPagado > $montoTotal) {
                        Log::warning("¡Alerta! El monto pagado ({$nuevoPagado}) excede el monto de la cuota ({$montoTotal}). Ajustando al máximo permitido.");
                        $nuevoPagado = $montoTotal;
                    }

                    $cuota->monto_pagado = $nuevoPagado;

                    if ($nuevoPagado >= $montoTotal) {
                        $cuota->estado = CuotaEstado::PAGADO->value; // 2
                        Log::info("Cuota {$cuota->id} marcada como PAGADA. Monto fijo: {$montoTotal}, Monto pagado: {$nuevoPagado}");
                    } else {
                        $cuota->estado = CuotaEstado::PARCIAL->value; // 1
                        Log::info("Cuota {$cuota->id} marcada como PARCIAL. Monto fijo: {$montoTotal}, Monto pagado: {$nuevoPagado}");
                    }

                    // REGULARIZAR MORAS SIEMPRE que se registre un pago (sea parcial o completo)
                    // Usar la fecha real de la operación recién creada
                    $this->regularizarMorasSegunFechaPago($cuota, $operacion->fecha, $user_id);

                    $resultado = $cuota->save();
                    Log::info('Estado de cuota actualizado: '.($resultado ? 'Éxito' : 'Fallido'));

                    // Agregar cuota al array de cuotas afectadas
                    $cuotas_seleccionadas[] = [
                        'id' => $cuota->id,
                        'numero' => $cuota->numero,
                        'monto_abonado' => $abonoCuota,
                        'estado_final' => $cuota->estado,
                    ];
                } catch (\Exception $e) {
                    Log::error('Error al actualizar estado de cuota: '.$e->getMessage());
                    throw $e;
                }

                $abono_cuotas_restante -= $abonoCuota;
                Log::info("Abono restante para cuotas: {$abono_cuotas_restante}");

                if ($abono_cuotas_restante <= 0) {
                    Log::info('Abono de cuotas agotado. Terminando procesamiento de cuotas.');
                    break;
                }

                Log::info("Continuando con siguiente cuota, hay excedente de: {$abono_cuotas_restante}");
            }

            // PROCESAMIENTO DE MORAS PENDIENTES: Usar moras ya generadas automáticamente
            $abono_moras_restante = $abono_moras;

            if ($abono_moras_restante > 0) {
                Log::info("Procesando pago de moras pendientes con abono: S/{$abono_moras}");

                Log::info('🔍 DEBUG CUOTA_ID: Valor actual: '.var_export($cuota_id, true).' - Original: '.var_export($cuota_id_original, true));

                // Si se especifica una cuota específica, filtrar SOLO las moras de esa cuota
                if (! empty($cuota_id_original)) {
                    Log::info("✅ Filtrando moras SOLO para cuota ID: {$cuota_id_original}");

                    // Query específico SOLO para la cuota seleccionada
                    $queryMoras = MoraCuota::where('cuota_id', $cuota_id_original)
                        ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                        ->with('cuota')
                        ->orderBy('fecha'); // Procesar moras más antiguas primero
                } else {
                    Log::info('🌐 Sin cuota específica - procesando TODAS las moras pendientes del préstamo');

                    // Query general para todo el préstamo (comportamiento original)
                    $queryMoras = MoraCuota::whereHas('cuota', function ($query) use ($prestamo_id) {
                        $query->where('prestamo_id', $prestamo_id);
                    })
                        ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                        ->with('cuota')
                        ->orderBy('fecha');
                }

                $morasPendientes = $queryMoras->get();

                Log::info("Encontradas {$morasPendientes->count()} moras pendientes para el préstamo");

                // DEBUG: Mostrar qué moras se van a procesar
                foreach ($morasPendientes as $mora) {
                    Log::info("🎯 Mora a procesar: ID {$mora->id}, Cuota #{$mora->cuota->numero} (ID: {$mora->cuota_id}), Monto: S/{$mora->monto}, Pagado: S/".($mora->monto_pagado ?? 0));
                }

                // Procesar pago de moras - NO crear operaciones individuales, solo registrar en operacion_mora
                foreach ($morasPendientes as $moraPendiente) {
                    if ($abono_moras_restante <= 0) {
                        break;
                    }

                    // Usar el campo monto_pagado de la tabla mora_cuota directamente
                    $montoPagadoAnterior = $moraPendiente->monto_pagado ?? 0;
                    $saldoMora = max(0, $moraPendiente->monto - $montoPagadoAnterior);

                    if ($saldoMora > 0) {
                        $abonoMora = min($abono_moras_restante, $saldoMora);

                        Log::info("Aplicando abono S/{$abonoMora} a mora ID {$moraPendiente->id} (cuota #{$moraPendiente->cuota->numero})");
                        Log::info("Mora {$moraPendiente->id}: Monto total S/{$moraPendiente->monto}, Pagado anterior S/{$montoPagadoAnterior}, Saldo S/{$saldoMora}");

                        try {
                            // Registrar en moras_history ANTES del cambio
                            DB::table('moras_history')->insert([
                                'mora_id' => $moraPendiente->id,
                                'monto_anterior' => $montoPagadoAnterior,
                                'status_anterior' => $moraPendiente->estado,
                                'monto_nuevo' => $montoPagadoAnterior + $abonoMora,
                                'status_nuevo' => ($montoPagadoAnterior + $abonoMora >= $moraPendiente->monto) ? MoraCuotaEstado::PAGADO->value : MoraCuotaEstado::PARCIAL->value,
                                'user_id' => $user_id,
                                'accion' => 'pago',
                                'created_at' => now(),
                            ]);

                            // Crear relación en operacion_mora (referenciar operación general, no crear nueva)
                            DB::table('operacion_mora')->insert([
                                'mora_cuota_id' => $moraPendiente->id,
                                'operacion_id' => $operacionGeneral->id, // ✅ USAR OPERACIÓN GENERAL
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            Log::info("Relación operación-mora creada: Operación general {$operacionGeneral->id} <-> Mora {$moraPendiente->id}");

                            // Actualizar monto_pagado y estado de la mora
                            $nuevoTotalPagado = $montoPagadoAnterior + $abonoMora;
                            $moraPendiente->monto_pagado = $nuevoTotalPagado;

                            if ($nuevoTotalPagado >= $moraPendiente->monto) {
                                // Mora completamente pagada
                                $moraPendiente->estado = MoraCuotaEstado::PAGADO->value;
                                Log::info("Mora {$moraPendiente->id} marcada como PAGADA completamente - Pagado S/{$nuevoTotalPagado} de S/{$moraPendiente->monto}");
                            } else {
                                // Mora parcialmente pagada
                                $moraPendiente->estado = MoraCuotaEstado::PARCIAL->value;
                                Log::info("Mora {$moraPendiente->id} marcada como PARCIAL - Pagado S/{$nuevoTotalPagado} de S/{$moraPendiente->monto}");
                            }

                            $moraPendiente->save();

                            $abono_moras_restante -= $abonoMora;
                            Log::info("Abono restante para moras: S/{$abono_moras_restante}");

                        } catch (\Exception $e) {
                            Log::error("Error al procesar pago de mora {$moraPendiente->id}: ".$e->getMessage());
                            throw $e;
                        }
                    } else {
                        Log::info("Mora {$moraPendiente->id} ya está completamente pagada, saltando...");
                    }
                }

                // CORRECCIÓN: Ya no llamamos a verificarRegularizacionMorasPorFechaAbono aquí
                // porque la regularización ya se maneja individualmente en cada cuota
                // cuando se marca como PAGADA en la línea 529

                // PROCESAMIENTO DE ABONOS A FAVOR DE MORA
                if ($abono_moras_restante > 0) {
                    Log::info("Procesando abono a favor de mora: S/{$abono_moras_restante}");

                    // Determinar cuota objetivo para el abono a favor
                    $cuotaObjetivo = null;

                    if (! empty($cuota_id_original)) {
                        // Si se especificó una cuota, aplicar el abono a favor a esa cuota
                        $cuotaObjetivo = Cuota::find($cuota_id_original);
                        Log::info("Aplicando abono a favor a cuota específica: #{$cuotaObjetivo->numero}");
                    } else {
                        // Si no se especificó cuota, aplicar a la primera cuota pendiente o parcial
                        $cuotaObjetivo = Cuota::where('prestamo_id', $prestamo_id)
                            ->whereIn('estado', [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL])
                            ->orderBy('numero')
                            ->first();

                        if ($cuotaObjetivo) {
                            Log::info("Aplicando abono a favor a primera cuota pendiente: #{$cuotaObjetivo->numero}");
                        }
                    }

                    if ($cuotaObjetivo) {
                        try {
                            // Crear el registro de abono a favor
                            $abonoFavor = AbonoMoraFavor::create([
                                'cuota_id' => $cuotaObjetivo->id,
                                'operacion_id' => $operacionGeneral->id,
                                'monto_abonado' => $abono_moras_restante,
                                'monto_utilizado' => 0,
                                'saldo_favor' => $abono_moras_restante,
                                'comentario' => "Abono anticipado a favor de mora - Cuota #{$cuotaObjetivo->numero}",
                                'estado' => AbonoMoraFavor::ESTADO_ACTIVO,
                                'fecha_abono' => $fecha,
                            ]);

                            Log::info("Abono a favor registrado: ID {$abonoFavor->id}, Cuota #{$cuotaObjetivo->numero}, Monto S/{$abono_moras_restante}");

                            $abono_moras_restante = 0; // Se consumió todo el abono restante

                        } catch (\Exception $e) {
                            Log::error('Error al registrar abono a favor: '.$e->getMessage());
                            throw $e;
                        }
                    } else {
                        Log::warning("No se encontró cuota objetivo para aplicar abono a favor de S/{$abono_moras_restante}");
                    }
                }

                if ($abono_moras_restante > 0) {
                    Log::info("Abono de mora no procesado: S/{$abono_moras_restante}");
                }
            }

            try {
                Log::info("Actualizando saldo del préstamo {$prestamo_id}");

                // Verificar si existe la columna 'pago_capital' en la tabla operaciones
                $pago_capital_exists = Schema::hasColumn('operaciones', 'pago_capital');

                if ($pago_capital_exists) {
                    // Si existe la columna, usamos el cálculo basado en pagos de capital
                    $totalCapitalPagado = Operacion::where('prestamo_id', $prestamo->id)
                        ->where('tipo_operacion', 'Pago de cuota')
                        ->sum('pago_capital');

                    $saldoRestante = $prestamo->cantidad_solicitada - $totalCapitalPagado;
                } else {
                    // Si no existe la columna, calculamos basado en cuotas pagadas
                    $cuotasPagadas = Cuota::where('prestamo_id', $prestamo_id)
                        ->where('estado', CuotaEstado::PAGADO)
                        ->sum('monto');

                    $saldoRestante = $prestamo->cantidad_solicitada - $cuotasPagadas;
                }

                Log::info("Saldo resultante: {$saldoRestante}");

                $prestamo->saldo_restante = max($saldoRestante, 0); // Aseguramos que no sea negativo

                // Verificar cuotas pendientes (TODAS las cuotas que NO estén completamente pagadas)
                $cuotasPendientes = Cuota::where('prestamo_id', $prestamo_id)
                    ->whereIn('estado', [CuotaEstado::PENDIENTE, CuotaEstado::PARCIAL, CuotaEstado::VENCIDO])
                    ->count();

                // Verificar moras pendientes (nueva condición)
                $morasPendientes = MoraCuota::whereHas('cuota', function ($query) use ($prestamo_id) {
                    $query->where('prestamo_id', $prestamo_id);
                })
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->count();

                Log::info("Verificando finalización: Saldo restante: {$saldoRestante}, Cuotas pendientes: {$cuotasPendientes}, Moras pendientes: {$morasPendientes}");

                // SOLO finalizar cuando TODAS las cuotas estén pagadas (sin importar el saldo capital)
                // Un préstamo se finaliza cuando se han completado todas sus cuotas + moras, no por saldo capital
                if ($cuotasPendientes == 0 && $morasPendientes == 0) {
                    $prestamo->estado = 'Finalizado';
                    Log::info('Préstamo marcado como Finalizado - Todas las cuotas y moras están pagadas');
                } elseif ($cuotasPendientes > 0) {
                    Log::info("Préstamo NO finalizado - Aún hay {$cuotasPendientes} cuotas sin pagar completamente (pendientes/parciales/vencidas)");
                } elseif ($morasPendientes > 0) {
                    Log::info("Préstamo NO finalizado - Aún hay {$morasPendientes} moras pendientes o parciales");
                }

                $resultado = $prestamo->save();
                Log::info('Préstamo actualizado: '.($resultado ? 'Éxito' : 'Fallido'));
            } catch (\Exception $e) {
                Log::error('Error al actualizar saldo del préstamo: '.$e->getMessage());
                throw $e;
            }

            // TEMPORALMENTE DESHABILITADO: EstadoPrestamoService resetea monto_pagado de moras
            // Actualizar usando el nuevo servicio centralizado
            // try {
            //     $resultadoRecalculo = $this->estadoService->recalcularTodo($prestamo);
            //     Log::info("Préstamo recalculado después de registrar pago usando EstadoPrestamoService", $resultadoRecalculo);
            // } catch (\Exception $e) {
            //     Log::error("Error al recalcular préstamo con EstadoPrestamoService: " . $e->getMessage());
            //     // No interrumpimos el flujo, el pago ya se registró correctamente
            // }

            // DESHABILITADO: No generar moras después de un pago porque es absurdo
            // Las moras ya se generan automáticamente cuando se carga el préstamo
            // Si alguien paga moras, no tiene sentido generar más moras inmediatamente
            // try {
            //     $this->procesarMorasPostPago($prestamo);
            // } catch (\Exception $e) {
            //     Log::error('Error al procesar moras post-pago: '.$e->getMessage());
            // }

            DB::commit();
            Log::info('Transacción completada con éxito. Pago registrado correctamente.');

            // Generar comprobante electrónico si se solicitó
            if ($request->has('generar_comprobante') && $request->generar_comprobante == '1') {
                try {
                    $this->generarComprobanteElectronico($request, $prestamo, $monto_total);
                } catch (\Exception $e) {
                    Log::error('Error al generar comprobante electrónico: '.$e->getMessage());
                    // No interrumpimos el flujo, el pago ya se registró correctamente
                }
            }

            // 🤖 REGISTRAR EVENTO AUTOMÁTICO DE PAGO
            try {
                \App\Models\EventoAutomatico::registrar(
                    $prestamo,
                    'pago_registrado',
                    'pagos',
                    [
                        'monto' => $monto_total,
                        'cuotas_afectadas' => $cuotas_seleccionadas,
                        'metodo_pago' => $metodoPago->nombre ?? 'No especificado',
                        'tiene_comprobante' => $request->has('generar_comprobante'),
                    ],
                    null,
                    [
                        'user_agent' => request()->userAgent(),
                        'ip' => request()->ip(),
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Error al registrar evento automático de pago: '.$e->getMessage());
            }

            return redirect()->route('admin.prestamos.show', ['prestamo' => $prestamo_id])
                ->with('success', 'Pago registrado correctamente. Sistema actualizado automáticamente.');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('TRANSACCIÓN FALLIDA - Error al registrar el pago: '.$e->getMessage());
            Log::error('Traza completa: '.$e->getTraceAsString());

            return redirect()->back()->with('error', 'Error al registrar el pago: '.$e->getMessage());
        }
    }

    public function show($prestamo_id)
    {
        $prestamo = Prestamo::with('cliente', 'cuotas')->findOrFail($prestamo_id);

        $operacionesGenerales = Operacion::whereNull('operacion_general_id')
            ->with('operacionesRelacionadas')
            ->get();

        return view('admin.Prestamos.show', compact('prestamo', 'operacionesGenerales'));
    }

    /**
     * Mostrar formulario para editar un pago
     */
    public function edit($operacion_id)
    {
        try {
            $operacion = Operacion::with([
                'prestamo.cliente.persona',
                'prestamo.cuotas',
                'metodoDePago',
                'cuotas',
            ])->findOrFail($operacion_id);

            // Verificar que sea una operación de pago o desembolso
            $tiposPermitidos = ['Desembolso', 'Pago de cuota', 'Pago de mora', 'Pago general'];
            if (! in_array($operacion->tipo_operacion, $tiposPermitidos)) {
                return redirect()->back()->withErrors(['error' => 'Solo se pueden editar operaciones de pago o desembolso.']);
            }

            // Verificar que no esté anulada
            if ($operacion->estado === 'anulado') {
                return redirect()->back()->withErrors(['error' => 'No se puede editar una operación anulada.']);
            }

            // Validación específica para desembolsos
            if ($operacion->tipo_operacion === 'Desembolso') {
                $prestamo = $operacion->prestamo;
                $cuotasCount = $prestamo->cuotas()->count();

                if ($cuotasCount > 0) {
                    // Verificar si alguna cuota tiene pagos
                    $cuotasConPagos = $prestamo->cuotas()
                        ->whereHas('operaciones', function ($query) {
                            $query->where('tipo_operacion', 'Pago')
                                ->where('estado', '!=', 'anulado');
                        })
                        ->count();

                    if ($cuotasConPagos > 0) {
                        return redirect()->back()
                            ->withErrors(['error' => 'No se puede editar el desembolso porque el préstamo ya tiene pagos registrados en las cuotas.']);
                    }

                    return redirect()->back()
                        ->withErrors(['error' => 'No se puede editar el desembolso porque el préstamo ya tiene cuotas generadas.']);
                }
            }

            $metodosPago = MetodoDePago::all();
            $cuentas = Cuenta::all();

            Log::info("Cargando edición de pago para operación ID: {$operacion_id}");

            return view('admin.pagos.edit', compact('operacion', 'metodosPago', 'cuentas'));

        } catch (\Exception $e) {
            Log::error('Error al cargar edición de pago: '.$e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Error al cargar el pago para edición.']);
        }
    }

    /**
     * Actualizar un pago existente
     */
    public function update(Request $request, $operacion_id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha' => 'required|date',
            'metodo_pago_id' => 'required|exists:metodos_de_pago,id',
            'cuenta_id' => 'nullable|exists:cuentas,id',
            'numero_operacion' => 'nullable|string|max:255',
            'entidad_bancaria' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'justificacion_edicion' => 'required|string|max:500',
        ]);

        // Validación adicional para evitar duplicación: número de operación por entidad bancaria
        if ($request->numero_operacion && $request->entidad_bancaria) {
            $operacionExistente = \App\Models\Operacion::where('codigo', $request->numero_operacion)
                ->where('entidad_bancaria', $request->entidad_bancaria)
                ->where('estado', '!=', 'anulado')
                ->where('id', '!=', $operacion_id) // Excluir la operación actual
                ->first();

            if ($operacionExistente) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "El número de operación {$request->numero_operacion} ya está registrado para {$request->entidad_bancaria}. Verifique los datos.");
            }
        }

        try {
            DB::beginTransaction();

            $operacion = Operacion::with(['prestamo.cuotas', 'cuotas'])->findOrFail($operacion_id);

            // Verificar permisos
            $tiposPermitidos = ['Desembolso', 'Pago de cuota', 'Pago de mora', 'Pago general'];
            if (! in_array($operacion->tipo_operacion, $tiposPermitidos)) {
                throw new \Exception('Solo se pueden editar operaciones de pago o desembolso.');
            }

            if ($operacion->estado === 'anulado') {
                throw new \Exception('No se puede editar una operación anulada.');
            }

            // Validación específica para desembolsos
            if ($operacion->tipo_operacion === 'Desembolso') {
                $prestamo = $operacion->prestamo;
                $cuotasCount = $prestamo->cuotas()->count();

                if ($cuotasCount > 0) {
                    // Verificar si alguna cuota tiene pagos
                    $cuotasConPagos = $prestamo->cuotas()
                        ->whereHas('operaciones', function ($query) {
                            $query->where('tipo_operacion', 'Pago')
                                ->where('estado', '!=', 'anulado');
                        })
                        ->count();

                    if ($cuotasConPagos > 0) {
                        throw new \Exception('No se puede editar el desembolso porque el préstamo ya tiene pagos registrados en las cuotas.');
                    }

                    throw new \Exception('No se puede editar el desembolso porque el préstamo ya tiene cuotas generadas.');
                }
            }

            // Guardar valores originales para auditoría y detección de cambios
            $valoresOriginales = [
                'abono' => $operacion->abono,
                'fecha' => $operacion->fecha,
                'metodo_pago_id' => $operacion->metodo_pago_id,
                'cuenta_id' => $operacion->cuenta_id,
                'codigo' => $operacion->codigo,
                'entidad_bancaria' => $operacion->entidad_bancaria,
                'comentario' => $operacion->comentario,
            ];

            // Actualizar la operación
            $operacion->update([
                'abono' => $request->monto,
                'fecha' => $request->fecha,
                'metodo_pago_id' => $request->metodo_pago_id,
                'cuenta_id' => $request->cuenta_id,
                'codigo' => $request->numero_operacion, // Mapear numero_operacion a codigo
                'entidad_bancaria' => $request->entidad_bancaria,
                'comentario' => $request->observaciones, // Mapear observaciones a comentario
                'justificacion_edicion' => $request->justificacion_edicion,
                'editado_por' => auth()->id(),
                'editado_en' => now(),
            ]);

            // SINCRONIZAR OPERACIONES HIJAS: Si es una operación general, actualizar operaciones específicas relacionadas
            $operacionesHijasAfectadas = [];
            if ($operacion->tipo_operacion === 'Pago general' && $valoresOriginales['abono'] != $request->monto) {
                $operacionesHijas = Operacion::where('operacion_general_id', $operacion->id)
                    ->where('estado', '!=', 'anulado')
                    ->get();

                if ($operacionesHijas->isNotEmpty()) {
                    // CORREGIDO: En lugar de aplicar diferencias, las operaciones hijas deben
                    // reflejar exactamente el monto de la operación general
                    $montoGeneral = $request->monto;

                    // Para operaciones de un solo pago (caso más común)
                    if ($operacionesHijas->count() == 1) {
                        $hija = $operacionesHijas->first();
                        $montoOriginalHija = $hija->abono;

                        $hija->update([
                            'abono' => $montoGeneral,
                            'fecha' => $request->fecha,
                            'editado_por' => auth()->id(),
                            'editado_en' => now(),
                        ]);

                        $operacionesHijasAfectadas[] = [
                            'id' => $hija->id,
                            'monto_anterior' => $montoOriginalHija,
                            'monto_nuevo' => $montoGeneral,
                        ];

                        Log::info("Operación hija {$hija->id} sincronizada directamente - Abono: {$montoOriginalHija} → {$montoGeneral}");

                    } else {
                        // Para múltiples operaciones hijas, mantener proporciones
                        $totalOriginalHijas = $operacionesHijas->sum('abono');

                        if ($totalOriginalHijas > 0) {
                            foreach ($operacionesHijas as $hija) {
                                $proporcion = $hija->abono / $totalOriginalHijas;
                                $nuevoMontoHija = round($montoGeneral * $proporcion, 2);
                                $montoOriginalHija = $hija->abono;

                                $hija->update([
                                    'abono' => $nuevoMontoHija,
                                    'fecha' => $request->fecha,
                                    'editado_por' => auth()->id(),
                                    'editado_en' => now(),
                                ]);

                                $operacionesHijasAfectadas[] = [
                                    'id' => $hija->id,
                                    'monto_anterior' => $montoOriginalHija,
                                    'monto_nuevo' => $nuevoMontoHija,
                                    'proporcion' => $proporcion,
                                ];

                                Log::info("Operación hija {$hija->id} sincronizada proporcionalmente - Abono: {$montoOriginalHija} → {$nuevoMontoHija} (proporción: {$proporcion})");
                            }
                        }
                    }
                }
            }

            // Log detallado de la edición con todos los cambios
            Log::info('Pago editado', [
                'operacion_id' => $operacion_id,
                'prestamo_id' => $operacion->prestamo_id,
                'tipo_operacion' => $operacion->tipo_operacion,
                'editado_por' => auth()->user()->name,
                'cambios' => $this->obtenerCambiosPago($valoresOriginales, $operacion),
                'justificacion' => $request->justificacion_edicion,
            ]);

            // Detectar cambios importantes para regenerar estados
            $montoChanged = $valoresOriginales['abono'] != $operacion->abono;
            $fechaChanged = $valoresOriginales['fecha'] != $operacion->fecha;

            if ($montoChanged) {
                Log::info('💰 Monto de pago cambiado - regenerando estados', [
                    'operacion_id' => $operacion->id,
                    'monto_anterior' => $valoresOriginales['abono'],
                    'monto_nuevo' => $operacion->abono,
                ]);
            }

            if ($fechaChanged) {
                Log::info('📅 Fecha de pago cambiada - regenerando estados', [
                    'operacion_id' => $operacion->id,
                    'fecha_anterior' => $valoresOriginales['fecha'],
                    'fecha_nueva' => $operacion->fecha,
                ]);
            }

            // Usar EstadoPrestamoService para recálculo completo y consistente
            if ($montoChanged || $fechaChanged) {
                try {
                    $prestamo = Prestamo::find($operacion->prestamo_id);
                    if ($prestamo) {
                        // TEMPORALMENTE DESHABILITADO: EstadoPrestamoService resetea monto_pagado de moras
                        // FORZAR recálculo inmediato de todas las cuotas del préstamo
                        // $resultadoRecalculo = $this->estadoService->recalcularTodo($prestamo);

                        // ADICIONAL: Refrescar específicamente las cuotas relacionadas con la operación editada
                        $cuotasRelacionadas = $operacion->cuotas()->get();
                        foreach ($cuotasRelacionadas as $cuota) {
                            // Forzar recarga completa con relaciones
                            $cuota->refresh();
                            $cuota->load(['operaciones', 'moras']);

                            // TEMPORALMENTE DESHABILITADO: EstadoPrestamoService resetea monto_pagado de moras
                            // Recalcular manualmente esta cuota específica para asegurar consistencia inmediata
                            // $resultadoCuota = $this->estadoService->recalcularCuota($cuota);

                            Log::info("🔄 Cuota {$cuota->id} refrescada y recalculada - Estado: {$cuota->fresh()->estado->name}, Monto pagado: {$cuota->fresh()->monto_pagado}", [
                                'resultado_recalculo_cuota' => $resultadoCuota,
                            ]);
                        }

                        Log::info('✅ Pago editado con EstadoPrestamoService', [
                            'operacion_id' => $operacion->id,
                            'monto_cambio' => $montoChanged,
                            'fecha_cambio' => $fechaChanged,
                            'cuotas_relacionadas' => $cuotasRelacionadas->pluck('id'),
                            'resultado_recalculo' => $resultadoRecalculo,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error al recalcular con EstadoPrestamoService en edición: '.$e->getMessage());
                    // No interrumpimos el flujo
                }
            }

            // Registrar auditoría de la edición
            $this->registrarAuditoriaEdicionPago($operacion, $valoresOriginales, $request->justificacion_edicion, $operacionesHijasAfectadas);

            DB::commit();

            // FORZAR actualización final después del commit para asegurar consistencia en la vista
            try {
                $prestamo = Prestamo::with(['cuotas', 'operaciones'])->find($operacion->prestamo_id);
                if ($prestamo) {
                    // Limpiar cualquier cache de Eloquent
                    $prestamo->refresh();
                    $prestamo->cuotas()->each(function ($cuota) {
                        $cuota->refresh();
                    });

                    Log::info("🔄 Vista del préstamo {$prestamo->id} refrescada post-edición para mostrar cambios actualizados");
                }
            } catch (\Exception $e) {
                Log::warning('Error al refrescar vista post-edición: '.$e->getMessage());
            }

            return redirect()->route('admin.prestamos.show', [
                'prestamo' => $operacion->prestamo_id,
                '_refresh' => time(), // Forzar refresh del cache del navegador
            ])
                ->with('success', 'Pago actualizado correctamente. Las cuotas han sido recalculadas automáticamente.')
                ->with('operacion_editada', $operacion->id); // Para destacar la operación editada

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar pago: '.$e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Error al actualizar el pago: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Anular un pago - VALIDACIÓN COMPLETA DE TODOS LOS CASOS
     */
    public function anular(Request $request, $operacion_id)
    {
        $request->validate([
            'justificacion_anulacion' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $operacion = Operacion::with([
                'prestamo.cuotas.operaciones',
                'prestamo.cuotas.moras',
                'cuotas',
                // 'moras', // Relación no existe en Operacion
                // 'prestamo.comprobantes' // Relación no existe aún
            ])->findOrFail($operacion_id);

            // ====== VALIDACIONES CRÍTICAS PREVIAS ======

            // 1. Verificar permisos básicos
            $tiposPermitidos = ['Desembolso', 'Pago de cuota', 'Pago de mora', 'Pago general'];
            if (! in_array($operacion->tipo_operacion, $tiposPermitidos)) {
                throw new \Exception('Solo se pueden anular operaciones de pago o desembolso.');
            }

            if ($operacion->estado === 'anulado') {
                throw new \Exception('La operación ya está anulada.');
            }

            // 2. VALIDAR OPERACIONES HIJAS: Si es operación general, verificar hijas
            if ($operacion->tipo_operacion === 'Pago general') {
                $operacionesHijas = Operacion::where('operacion_general_id', $operacion->id)
                    ->where('estado', '!=', 'anulado')
                    ->get();

                if ($operacionesHijas->isNotEmpty()) {
                    Log::warning("⚠️ Anulación de operación general {$operacion->id} afectará {$operacionesHijas->count()} operaciones hijas");
                }
            }

            // 3. VALIDAR COMPROBANTES FISCALES (temporalmente deshabilitado hasta implementar relación)
            $comprobantesFiscales = 0;
            /*
            // TODO: Implementar relación comprobantes en modelo Prestamo
            $comprobantesFiscales = $operacion->prestamo->comprobantes()
                ->where('estado', '!=', 'anulado')
                ->whereDate('created_at', '>=', $operacion->fecha)
                ->count();

            if ($comprobantesFiscales > 0) {
                Log::warning("⚠️ Operación {$operacion->id} puede tener comprobantes fiscales relacionados");
                // No bloquear pero advertir
            }
            */

            // 4. VALIDAR SECUENCIA TEMPORAL: Operaciones posteriores
            $operacionesPosteriores = $operacion->prestamo->operaciones()
                ->where('id', '!=', $operacion->id)
                ->where('estado', '!=', 'anulado')
                ->where('fecha', '>', $operacion->fecha)
                ->whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general'])
                ->count();

            if ($operacionesPosteriores > 0) {
                Log::warning("⚠️ Existen {$operacionesPosteriores} operaciones posteriores a la que se va a anular");
                // Permitir pero con advertencia
            }

            // 5. VALIDAR LÍMITE TEMPORAL (no anular operaciones muy antiguas)
            $diasLimite = 90; // Configurable
            $diasTranscurridos = now()->diffInDays($operacion->fecha);
            if ($diasTranscurridos > $diasLimite) {
                throw new \Exception("No se puede anular operaciones con más de {$diasLimite} días de antigüedad ({$diasTranscurridos} días transcurridos).");
            }

            // ====== LÓGICA DE ANULACIÓN POR TIPO ======

            $resultadoAnulacion = [];

            if ($operacion->tipo_operacion === 'Desembolso') {
                $resultadoAnulacion = $this->anularDesembolso($operacion);

            } elseif ($operacion->tipo_operacion === 'Pago general') {
                $resultadoAnulacion = $this->anularPagoGeneral($operacion, $request->justificacion_anulacion);

            } elseif (in_array($operacion->tipo_operacion, ['Pago de cuota', 'Pago de mora'])) {
                $resultadoAnulacion = $this->anularPagoEspecifico($operacion, $request->justificacion_anulacion);
            }

            // ====== MARCAR OPERACIÓN COMO ANULADA ======
            $valoresOriginales = [
                'estado' => $operacion->estado,
                'abono' => $operacion->abono,
                'fecha' => $operacion->fecha,
                'tipo_operacion' => $operacion->tipo_operacion,
            ];

            $operacion->update([
                'estado' => 'anulado',
                'justificacion_anulacion' => $request->justificacion_anulacion,
                'anulado_por' => auth()->id(),
                'anulado_en' => now(),
            ]);

            // ====== REGISTRAR AUDITORÍA DE ANULACIÓN ======
            \App\Models\AuditoriaOperacion::create([
                'operacion_id' => $operacion->id,
                'prestamo_id' => $operacion->prestamo_id,
                'tipo_operacion' => $operacion->tipo_operacion,
                'accion' => 'anulado',
                'usuario_id' => auth()->id(),
                'usuario_nombre' => auth()->user()->name,
                'valores_anteriores' => $valoresOriginales,
                'valores_nuevos' => [
                    'estado' => 'anulado',
                    'justificacion_anulacion' => $request->justificacion_anulacion,
                    'anulado_en' => now()->format('Y-m-d H:i:s'),
                ],
                'operaciones_hijas_afectadas' => $resultadoAnulacion['operaciones_hijas_afectadas'] ?? null,
                'justificacion' => $request->justificacion_anulacion,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Log detallado de la anulación
            Log::info('💫 Operación anulada completamente', [
                'operacion_id' => $operacion_id,
                'tipo_operacion' => $operacion->tipo_operacion,
                'monto' => $operacion->abono,
                'fecha' => $operacion->fecha,
                'anulado_por' => auth()->user()->name,
                'justificacion' => $request->justificacion_anulacion,
                'resultado_anulacion' => $resultadoAnulacion,
                'operaciones_posteriores_advertencia' => $operacionesPosteriores,
                'comprobantes_fiscales_advertencia' => $comprobantesFiscales,
            ]);

            // ====== RECÁLCULO FINAL DE ESTADOS ======
            try {
                $prestamo = Prestamo::find($operacion->prestamo_id);
                if ($prestamo) {
                    // TEMPORALMENTE DESHABILITADO: EstadoPrestamoService resetea monto_pagado de moras
                    // Recalcular todo el préstamo tras la anulación
                    // $resultadoRecalculo = $this->estadoService->recalcularTodo($prestamo);
                    // Log::info("✅ Estados recalculados tras anulación", $resultadoRecalculo);
                }
            } catch (\Exception $e) {
                Log::error('Error al recalcular tras anulación: '.$e->getMessage());
            }

            DB::commit();

            return redirect()->route('admin.prestamos.show', [
                'prestamo' => $operacion->prestamo_id,
                '_refresh' => time(),
            ])
                ->with('success', 'Operación anulada correctamente. Todos los estados han sido recalculados.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al anular operación: '.$e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Error al anular la operación: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * ANULAR DESEMBOLSO - Validaciones específicas
     */
    private function anularDesembolso(Operacion $operacion): array
    {
        $prestamo = $operacion->prestamo;
        $resultados = [
            'tipo' => 'desembolso',
            'prestamo_revertido' => false,
            'cuotas_eliminadas' => 0,
            'operaciones_hijas_afectadas' => [],
        ];

        // Verificar si existen cuotas
        $cuotasCount = $prestamo->cuotas()->count();
        if ($cuotasCount > 0) {
            // Verificar si alguna cuota tiene pagos
            $cuotasConPagos = $prestamo->cuotas()
                ->whereHas('operaciones', function ($query) {
                    $query->whereIn('tipo_operacion', ['Pago de cuota', 'Pago de mora', 'Pago general'])
                        ->where('estado', '!=', 'anulado');
                })
                ->count();

            if ($cuotasConPagos > 0) {
                throw new \Exception('No se puede anular el desembolso porque el préstamo ya tiene pagos registrados en las cuotas.');
            }

            // Verificar si hay moras generadas
            $morasCount = $prestamo->cuotas()->withCount('moras')->get()->sum('moras_count');
            if ($morasCount > 0) {
                throw new \Exception('No se puede anular el desembolso porque existen moras generadas en las cuotas.');
            }

            // Si hay cuotas pero sin pagos ni moras, también es riesgoso por defecto
            throw new \Exception('No se puede anular el desembolso porque el préstamo ya tiene cuotas generadas. Elimine primero las cuotas si es necesario.');
        }

        // Si llegamos aquí, es seguro anular el desembolso
        $estadoAnterior = $prestamo->estado;
        $prestamo->update(['estado' => 'Por Desembolsar']);

        $resultados['prestamo_revertido'] = true;
        $resultados['estado_anterior'] = $estadoAnterior;
        $resultados['estado_nuevo'] = 'Por Desembolsar';

        Log::info('✅ Desembolso anulado - Estado del préstamo revertido', $resultados);

        return $resultados;
    }

    /**
     * ANULAR PAGO GENERAL - Manejar operaciones hijas
     */
    private function anularPagoGeneral(Operacion $operacion, string $justificacion): array
    {
        $resultados = [
            'tipo' => 'pago_general',
            'operaciones_hijas_anuladas' => 0,
            'cuotas_afectadas' => [],
            'moras_afectadas' => [],
            'operaciones_hijas_afectadas' => [],
        ];

        // 1. Buscar operaciones hijas
        $operacionesHijas = Operacion::where('operacion_general_id', $operacion->id)
            ->where('estado', '!=', 'anulado')
            ->get();

        // 2. Anular operaciones hijas primero
        foreach ($operacionesHijas as $hija) {
            $hija->update([
                'estado' => 'anulado',
                'justificacion_anulacion' => "Anulada automáticamente por anulación de operación general {$operacion->id}: {$justificacion}",
                'anulado_por' => auth()->id(),
                'anulado_en' => now(),
            ]);

            $resultados['operaciones_hijas_afectadas'][] = [
                'id' => $hija->id,
                'tipo' => $hija->tipo_operacion,
                'monto' => $hija->abono,
                'cuotas_relacionadas' => $hija->cuotas ? $hija->cuotas->pluck('id')->toArray() : [],
                'moras_relacionadas' => [], // Relación no implementada aún
            ];

            $resultados['operaciones_hijas_anuladas']++;

            // Recopilar cuotas y moras afectadas
            if ($hija->cuotas) {
                $resultados['cuotas_afectadas'] = array_merge(
                    $resultados['cuotas_afectadas'],
                    $hija->cuotas->pluck('id')->toArray()
                );
            }

            // Nota: La relación 'moras' no está implementada en Operacion
            // $resultados['moras_afectadas'] permanece como array vacío
        }

        // 3. Eliminar duplicados en arrays de IDs
        $resultados['cuotas_afectadas'] = array_unique($resultados['cuotas_afectadas']);
        $resultados['moras_afectadas'] = array_unique($resultados['moras_afectadas']);

        Log::info('✅ Pago general anulado con operaciones hijas', $resultados);

        return $resultados;
    }

    /**
     * ANULAR PAGO ESPECÍFICO - Cuota o mora individual
     */
    private function anularPagoEspecifico(Operacion $operacion, string $justificacion): array
    {
        $resultados = [
            'tipo' => strtolower(str_replace(' ', '_', $operacion->tipo_operacion)),
            'cuotas_afectadas' => [],
            'moras_afectadas' => [],
            'operaciones_hijas_afectadas' => [],
        ];

        // Recopilar información de cuotas afectadas
        if ($operacion->cuotas()->exists()) {
            $resultados['cuotas_afectadas'] = $operacion->cuotas->pluck('id')->toArray();
        }

        // TODO: Implementar relación moras en modelo Operacion si es necesario
        // if ($operacion->moras()->exists()) {
        //     $resultados['moras_afectadas'] = $operacion->moras->pluck('id')->toArray();
        // }

        Log::info('✅ Pago específico anulado', $resultados);

        return $resultados;
    }

    /**
     * Recalcular cuotas después de editar un pago
     */
    private function recalcularCuotasPorEdicion($operacion, $nuevoMonto)
    {
        $diferenciaMonto = $nuevoMonto - $operacion->monto;

        foreach ($operacion->cuotas as $operacionCuota) {
            $cuota = $operacionCuota->cuota;
            $montoPagadoActual = $cuota->monto_pagado ?? 0;

            // Ajustar el monto pagado
            $nuevoMontoPagado = $montoPagadoActual + $diferenciaMonto;
            $nuevoMontoPagado = max(0, $nuevoMontoPagado); // No puede ser negativo

            $cuota->monto_pagado = $nuevoMontoPagado;

            // Recalcular estado de la cuota
            if ($nuevoMontoPagado >= $cuota->monto) {
                $cuota->estado = CuotaEstado::PAGADO->value; // 2
            } elseif ($nuevoMontoPagado > 0) {
                $cuota->estado = CuotaEstado::PARCIAL->value; // 1
            } else {
                $cuota->estado = CuotaEstado::PENDIENTE->value; // 0
            }

            $cuota->save();
        }
    }

    /**
     * Reversar el efecto de un pago en las cuotas al anularlo
     */
    // MÉTODO ELIMINADO: reversarPagoEnCuotas()
    // Reemplazado por EstadoPrestamoService->anularOperacion() para mayor consistencia y granularidad

    /**
     * Actualizar el estado del préstamo basándose en el estado de las cuotas
     */
    private function actualizarEstadoPrestamo(Prestamo $prestamo)
    {
        Log::info("Iniciando actualización de estado para préstamo {$prestamo->id}");

        // No cambiar el estado si el préstamo ya está finalizado
        if ($prestamo->estado === 'Finalizado') {
            Log::info("Préstamo {$prestamo->id} ya está finalizado, no se actualiza estado");

            return;
        }

        // Obtener todas las cuotas del préstamo
        $cuotas = $prestamo->cuotas;

        if ($cuotas->isEmpty()) {
            Log::warning("Préstamo {$prestamo->id} no tiene cuotas, no se actualiza estado");

            return;
        }

        $fechaActual = now()->format('Y-m-d');
        $totalCuotas = $cuotas->count();

        // Debug: verificar estados actuales
        Log::info('DEBUG - Estados de cuotas:', [
            'prestamo_id' => $prestamo->id,
            'cuotas_estados' => $cuotas->pluck('estado', 'numero')->toArray(),
        ]);

        $cuotasPagadas = $cuotas->filter(function ($cuota) {
            return $cuota->estado === CuotaEstado::PAGADO;
        })->count();

        $cuotasParciales = $cuotas->filter(function ($cuota) {
            return $cuota->estado === CuotaEstado::PARCIAL;
        })->count();

        $cuotasPendientes = $cuotas->filter(function ($cuota) {
            return $cuota->estado === CuotaEstado::PENDIENTE;
        })->count();

        // Verificar cuotas vencidas por fecha (cuotas no pagadas con fecha vencida)
        $cuotasVencidas = $cuotas->filter(function ($cuota) use ($fechaActual) {
            return $cuota->estado !== CuotaEstado::PAGADO &&
                   $cuota->fecha_pago < $fechaActual;
        })->count();

        Log::info("Estado de cuotas - Total: {$totalCuotas}, Pagadas: {$cuotasPagadas}, Parciales: {$cuotasParciales}, Pendientes: {$cuotasPendientes}, Vencidas: {$cuotasVencidas}");

        $estadoAnterior = $prestamo->estado;
        $nuevoEstado = $estadoAnterior;

        // Determinar el nuevo estado
        if ($cuotasPagadas === $totalCuotas) {
            // Todas las cuotas están pagadas
            $nuevoEstado = 'Finalizado';
        } elseif ($cuotasVencidas > 0) {
            // Hay cuotas vencidas
            $nuevoEstado = 'Moroso';
        } elseif ($cuotasPagadas > 0 || $cuotasParciales > 0) {
            // Hay al menos una cuota pagada o parcial y no hay vencidas
            $nuevoEstado = 'Vigente';
        } elseif ($prestamo->estado === 'Desembolsado') {
            // Si está desembolsado pero no hay pagos, mantener como Vigente
            $nuevoEstado = 'Vigente';
        } elseif ($cuotasPagadas === 0 && $cuotasVencidas === 0) {
            // Sin pagos y sin cuotas vencidas - préstamo recién creado/desembolsado
            $nuevoEstado = 'Vigente';
        }

        // Solo actualizar si el estado cambió
        if ($nuevoEstado !== $estadoAnterior) {
            $prestamo->estado = $nuevoEstado;
            $prestamo->save();

            Log::info("Estado del préstamo {$prestamo->id} actualizado de '{$estadoAnterior}' a '{$nuevoEstado}'");
        } else {
            Log::info("Estado del préstamo {$prestamo->id} se mantiene como '{$estadoAnterior}'");
        }
    }

    /**
     * Procesar moras después de registrar un pago
     * Verifica si hay cuotas vencidas que necesitan generar moras
     */
    private function procesarMorasPostPago(Prestamo $prestamo)
    {
        Log::info("Verificando moras post-pago para préstamo {$prestamo->id}");

        try {
            $moraService = new \App\Services\MoraService;

            // Obtener cuotas vencidas de este préstamo que podrían necesitar moras
            $cuotasVencidas = $prestamo->cuotas()
                ->whereIn('estado', [\App\Enums\CuotaEstado::PENDIENTE->value, \App\Enums\CuotaEstado::PARCIAL->value])
                ->where('fecha_pago', '<', now()->format('Y-m-d'))
                ->get();

            if ($cuotasVencidas->isEmpty()) {
                Log::info("No hay cuotas vencidas para procesar moras en préstamo {$prestamo->id}");

                return;
            }

            $morasGeneradas = 0;

            foreach ($cuotasVencidas as $cuota) {
                $resultado = $moraService->procesarCuotaParaMoras($cuota);
                $morasGeneradas += $resultado['generadas'];
            }

            if ($morasGeneradas > 0) {
                Log::info("Post-pago: Se generaron {$morasGeneradas} moras adicionales para préstamo {$prestamo->id}");

                // TEMPORALMENTE DESHABILITADO: EstadoPrestamoService resetea monto_pagado de moras
                // Actualizar estado del préstamo nuevamente si se generaron moras
                // $resultadoRecalculo = $this->estadoService->recalcularTodo($prestamo);
                // Log::info("Préstamo recalculado después de procesar moras post-pago usando EstadoPrestamoService", $resultadoRecalculo);
            } else {
                Log::info("Post-pago: No se generaron moras adicionales para préstamo {$prestamo->id}");
            }

        } catch (\Exception $e) {
            Log::error("Error en procesarMorasPostPago para préstamo {$prestamo->id}: ".$e->getMessage());
            throw $e;
        }
    }

    private function generarComprobanteElectronico(Request $request, Prestamo $prestamo, float $montoTotal)
    {
        Log::info("Iniciando generación de comprobante electrónico (SIRE) para préstamo {$prestamo->id}");

        try {
            $sireApi = app(\App\Services\SireApiService::class);
        } catch (\Exception $e) {
            Log::error('Error al inicializar SireApiService: ' . $e->getMessage());
            throw new \Exception('No se pudo conectar con el servicio de facturación: ' . $e->getMessage());
        }

        // Obtener datos del cliente
        $cliente = $prestamo->cliente;
        $persona = $cliente->persona;

        // Determinar tipo de documento y datos del cliente
        $tipoDocumento = strlen($persona->documento) == 11 ? '6' : '1'; // 6=RUC, 1=DNI
        $clientData = [
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $persona->documento,
            'razon_social' => trim($persona->nombres . ' ' . $persona->ape_pat . ' ' . $persona->ape_mat),
        ];

        // Obtener las operaciones de este pago con sus cuotas relacionadas
        $operacionesRecientes = Operacion::where('prestamo_id', $prestamo->id)
            ->where('tipo_operacion', 'Pago de cuota')
            ->where('created_at', '>=', now()->subMinutes(5)) // Operaciones de este pago
            ->with('cuotas') // Cargar las cuotas relacionadas
            ->get();

        // Recolectar todas las cuotas únicas pagadas en este pago
        $cuotasPagadas = [];
        foreach ($operacionesRecientes as $operacion) {
            foreach ($operacion->cuotas as $cuota) {
                if (!isset($cuotasPagadas[$cuota->id])) {
                    $cuotasPagadas[$cuota->id] = $cuota;
                }
            }
        }

        // Calcular totales desde las cuotas
        $capitalTotal = 0;
        $interesTotal = 0;
        $comisionTotal = 0;
        $gasTotal = 0;
        $igvTotal = 0;

        foreach ($cuotasPagadas as $cuota) {
            $capitalTotal += $cuota->pago_capital ?? 0;
            $interesTotal += $cuota->interes ?? 0;
            $comisionTotal += $cuota->comision ?? 0;
            $gasTotal += $cuota->gas ?? 0;
            $igvTotal += $cuota->igv ?? 0;
        }

        // Preparar items del comprobante
        $items = [];

        // Item 1: Capital (EXONERADO de IGV)
        if ($capitalTotal > 0) {
            $items[] = [
                'codigo' => 'CAPITAL',
                'descripcion' => 'Amortización de capital del préstamo',
                'cantidad' => 1,
                'valor_unitario' => round($capitalTotal, 2),
                'unidad' => 'NIU',
                'tipo_afectacion_igv' => '20', // 20 = Exonerado de IGV
            ];
        }

        // Item 2: Intereses, Comisiones y Gastos (GRAVADO con IGV)
        $interesesYComisiones = $interesTotal + $comisionTotal + $gasTotal;
        if ($interesesYComisiones > 0) {
            $items[] = [
                'codigo' => 'INTERES',
                'descripcion' => 'Intereses y comisiones del préstamo',
                'cantidad' => 1,
                'valor_unitario' => round($interesesYComisiones, 2),
                'unidad' => 'NIU',
                'tipo_afectacion_igv' => '10', // 10 = Gravado
            ];
        }

        // Validar que haya al menos un item
        if (empty($items)) {
            Log::error('No se puede generar comprobante sin items', [
                'prestamo_id' => $prestamo->id,
                'cuotas_pagadas' => count($cuotasPagadas),
                'monto_total' => $montoTotal
            ]);
            throw new \Exception('No se puede generar un comprobante sin items. Verifica que el pago tenga cuotas asociadas correctamente.');
        }

        // Obtener serie y correlativo
        $tipoComprobante = $request->input('tipo_comprobante', '03'); // 03=Boleta por defecto
        $serie = $request->input('serie_comprobante', 'B001');

        if ($tipoComprobante == '03' && $tipoDocumento == '6') {
            throw new \Exception('No se puede emitir una boleta (03) para un cliente con RUC. Debe emitir una factura (01).');
        }
        if ($tipoComprobante == '01' && $tipoDocumento == '1') {
             Log::warning('Se está intentando emitir factura para cliente con DNI.');
        }

        // Obtener siguiente número correlativo
        $ultimoComprobante = Comprobante::where('serie', $serie)
            ->where('tipo_comprobante', $tipoComprobante)
            ->orderBy('numero', 'desc')
            ->first();

        $numero = $ultimoComprobante ? $ultimoComprobante->numero + 1 : 1;

        // Payload SIRE
        $payload = [
            'cliente' => $clientData,
            'items' => $items,
            'serie' => $serie,
            'numero' => $numero,
            'tipo_comprobante' => $tipoComprobante,
        ];

        // Enviar a SIRE
        $result = $sireApi->enviarJson($payload);

        // Guardar comprobante
        $comprobante = new Comprobante;
        $comprobante->cliente_id = $cliente->id;
        $comprobante->prestamo_id = $prestamo->id;

        // Guardar cuota_id si hay una sola cuota pagada (para facilitar reenvíos)
        if (count($cuotasPagadas) === 1) {
            $cuotaUnica = reset($cuotasPagadas);
            $comprobante->cuota_id = $cuotaUnica->id;
            Log::info("Comprobante asociado a cuota única", ['cuota_id' => $cuotaUnica->id]);
        } elseif (count($cuotasPagadas) > 1) {
            Log::info("Comprobante con múltiples cuotas pagadas", ['cantidad' => count($cuotasPagadas)]);
        }

        $comprobante->tipo_comprobante = $tipoComprobante;
        $comprobante->serie = $serie;
        $comprobante->numero = $numero;
        $comprobante->fecha_emision = now();
        $comprobante->moneda = 'PEN';

        // Determinar estado según respuesta de SUNAT
        $responseData = $result['data'] ?? [];
        $mensajeError = null;

        if ($result['success'] ?? false) {
            // Analizar código de respuesta de SUNAT
            $codigoRespuesta = $responseData['cod_respuesta'] ?? '0';

            if (in_array($codigoRespuesta, ['0', '0001', '0002', '0003', '0004'])) {
                $comprobante->estado = 'ACEPTADO';
            } elseif ($codigoRespuesta === '0100') {
                $comprobante->estado = 'RECHAZADO';
                $mensajeError = $responseData['mensaje'] ?? 'Comprobante rechazado por SUNAT';
            } elseif (in_array($codigoRespuesta, ['0098', '0099'])) {
                $comprobante->estado = 'OBSERVADO';
                $mensajeError = $responseData['mensaje'] ?? 'Comprobante aceptado con observaciones';
            } else {
                // Otros códigos de respuesta
                $comprobante->estado = 'ENVIADO';
            }

            $comprobante->codigo_error = $codigoRespuesta !== '0' ? $codigoRespuesta : null;
        } else {
            // Error en el envío
            $comprobante->estado = 'ERROR';
            $mensajeError = $result['error'] ?? 'Error desconocido';
            if (is_array($mensajeError) || is_object($mensajeError)) {
                $mensajeError = json_encode($mensajeError);
            }
            $comprobante->codigo_error = $result['codigo'] ?? null;
        }

        $comprobante->items = json_encode($items);
        $comprobante->total = $montoTotal;
        $comprobante->cdr_zip = $responseData['cdr'] ?? ($result['cdr_zip'] ?? null);
        $comprobante->hash = $responseData['hash'] ?? hash('sha256', $serie.'-'.$numero.'-'.$montoTotal);
        $comprobante->mensaje_error = $mensajeError;
        $comprobante->save();

        if ($result['success'] ?? false) {
             Log::info("Comprobante generado (SIRE) - ID: {$comprobante->id}, Serie: {$serie}, Número: {$numero}");
        } else {
             Log::warning("Comprobante generado con errores (SIRE) - ID: {$comprobante->id}, Error: {$mensajeError}");
        }

        return $comprobante;
    }


    /**
     * Regulariza las moras de una cuota si el pago se realizó a tiempo
     * (fecha de abono igual o anterior a la fecha de vencimiento)
     */
    private function regularizarMorasPorPagoATiempo(Cuota $cuota, string $fechaAbono, int $userId): void
    {
        try {
            // DEBUG: Ver qué carajo tiene la cuota
            Log::info("🔍 DEBUG CUOTA {$cuota->id} - fecha_pago RAW: {$cuota->fecha_pago}");
            Log::info("🔍 DEBUG CUOTA {$cuota->id} - getAttributes: ".json_encode($cuota->getAttributes()));

            // RECARGAR LA CUOTA DIRECTAMENTE DE LA BASE DE DATOS
            $cuotaFresh = \App\Models\Cuota::find($cuota->id);
            Log::info("🔍 DEBUG CUOTA FRESH {$cuotaFresh->id} - fecha_pago: {$cuotaFresh->fecha_pago}");
            Log::info("🔍 DEBUG CUOTA FRESH {$cuotaFresh->id} - getAttributes: ".json_encode($cuotaFresh->getAttributes()));

            // OBTENER FECHAS REALES DE LA BASE DE DATOS - USAR LA CUOTA FRESH
            $fechaVencimiento = Carbon::parse($cuotaFresh->fecha_pago)->startOfDay(); // Tabla cuotas, campo fecha_pago

            // Buscar la fecha real del pago en la tabla operaciones para esta cuota - USAR CUOTA FRESH
            $operacionPago = $cuotaFresh->operaciones()
                ->where('tipo_operacion', 'Pago de cuota')
                ->orderBy('created_at', 'desc')
                ->first();

            if (! $operacionPago) {
                Log::warning("No se encontró operación de pago para cuota {$cuota->id}");

                return;
            }

            $fechaPagoReal = Carbon::parse($operacionPago->fecha)->startOfDay(); // Tabla operaciones, campo fecha

            Log::info("🔍 VERIFICANDO regularización - Cuota {$cuota->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pagada {$fechaPagoReal->format('Y-m-d')}");

            // LÓGICA CORRECTA: Comparar fecha_pago (vencimiento) vs fecha de operaciones (pago real)
            if ($fechaPagoReal->lte($fechaVencimiento)) {
                // CASO 1: Pago a tiempo o anticipado - Regularizar TODAS las moras - USAR CUOTA FRESH
                $morasParaRegularizar = $cuotaFresh->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->get();

                if ($morasParaRegularizar->count() > 0) {
                    Log::info("✅ Pago a tiempo - Regularizando {$morasParaRegularizar->count()} moras de la cuota {$cuota->id}");

                    foreach ($morasParaRegularizar as $mora) {
                        DB::table('moras_history')->insert([
                            'mora_id' => $mora->id,
                            'monto_anterior' => $mora->monto_pagado ?? 0,
                            'status_anterior' => $mora->estado->value,
                            'monto_nuevo' => $mora->monto_pagado ?? 0,
                            'status_nuevo' => MoraCuotaEstado::REGULARIZADA->value,
                            'user_id' => $userId,
                            'accion' => 'pago_ok',
                            'created_at' => now(),
                        ]);

                        $mora->estado = MoraCuotaEstado::REGULARIZADA->value;
                        $mora->save();

                        Log::info("Mora {$mora->id} regularizada por pago a tiempo");
                    }
                }

            } else {
                // CASO 2: Pago tardío - Regularizar moras HASTA la fecha de pago real
                $diasTarde = $fechaVencimiento->diffInDays($fechaPagoReal);
                Log::info("⏰ Pago tardío - Cuota {$cuota->id} pagada {$diasTarde} días después del vencimiento");

                // Obtener moras hasta la fecha de pago real (inclusive) - estas se REGULARIZAN - USAR CUOTA FRESH
                $morasHastaPago = $cuotaFresh->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->whereDate('fecha', '<=', $fechaPagoReal)
                    ->get();

                // Obtener moras posteriores a la fecha de pago real - estas quedan PENDIENTES - USAR CUOTA FRESH
                $morasPosteriores = $cuotaFresh->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->whereDate('fecha', '>', $fechaPagoReal)
                    ->get();

                if ($morasHastaPago->count() > 0) {
                    Log::info("🔄 Regularizando {$morasHastaPago->count()} moras hasta la fecha de pago real");

                    foreach ($morasHastaPago as $mora) {
                        DB::table('moras_history')->insert([
                            'mora_id' => $mora->id,
                            'monto_anterior' => $mora->monto_pagado ?? 0,
                            'status_anterior' => $mora->estado->value,
                            'monto_nuevo' => $mora->monto_pagado ?? 0,
                            'status_nuevo' => MoraCuotaEstado::REGULARIZADA->value,
                            'user_id' => $userId,
                            'accion' => 'tarde',
                            'created_at' => now(),
                        ]);

                        $mora->estado = MoraCuotaEstado::REGULARIZADA->value;
                        $mora->save();

                        Log::info("Mora {$mora->id} (fecha {$mora->fecha}) regularizada - hasta fecha pago real");
                    }
                }

                if ($morasPosteriores->count() > 0) {
                    Log::info("⚠️ Se mantienen {$morasPosteriores->count()} moras PENDIENTES posteriores a la fecha de pago real");
                }
            }

            // Actualizar cantidad_mora después de cualquier regularización - USAR CUOTA FRESH
            $totalMorasRestantes = $cuotaFresh->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                ->sum('monto');
            $cuotaFresh->update(['cantidad_mora' => $totalMorasRestantes]);

            Log::info("Cantidad de mora actualizada para cuota {$cuota->id}: S/{$totalMorasRestantes}");

        } catch (\Exception $e) {
            Log::error("Error al regularizar moras de cuota {$cuota->id}: ".$e->getMessage());
        }
    }

    /**
     * Verifica y regulariza moras cuando se registra un pago con fecha anterior al vencimiento
     * Usa la fecha real de la operación registrada en la tabla 'operaciones'
     *
     * @param  string  $fechaOperacion  - Fecha de la operación en tabla 'operaciones'
     */
    private function verificarRegularizacionMorasPorFechaAbono(int $prestamoId, string $fechaOperacion, int $userId): void
    {
        try {
            Log::info("🔍 Verificando regularización por fecha de operación - Préstamo {$prestamoId}, Fecha operación: {$fechaOperacion}");

            $prestamo = Prestamo::find($prestamoId);
            if (! $prestamo) {
                return;
            }

            $fechaPago = Carbon::parse($fechaOperacion)->startOfDay();

            // Buscar cuotas que tienen operaciones de pago recientes y moras pendientes
            $cuotasConOperacionesRecientes = $prestamo->cuotas()
                ->whereHas('operaciones', function ($query) use ($fechaPago) {
                    $query->where('tipo_operacion', 'Pago de cuota')
                        ->whereDate('fecha', '=', $fechaPago);
                })
                ->whereHas('moras', function ($query) {
                    $query->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value]);
                })
                ->with(['operaciones' => function ($query) use ($fechaPago) {
                    $query->where('tipo_operacion', 'Pago de cuota')
                        ->whereDate('fecha', '=', $fechaPago)
                        ->orderBy('created_at', 'desc');
                }])
                ->get();

            if ($cuotasConOperacionesRecientes->count() > 0) {
                Log::info("📋 Encontradas {$cuotasConOperacionesRecientes->count()} cuotas con pagos recientes que pueden necesitar regularización");

                foreach ($cuotasConOperacionesRecientes as $cuota) {
                    $fechaVencimiento = Carbon::parse($cuota->fecha_pago)->startOfDay();
                    $operacionReciente = $cuota->operaciones->first();

                    if ($operacionReciente) {
                        $fechaOperacionReal = Carbon::parse($operacionReciente->fecha)->startOfDay();

                        // Solo regularizar si la operación fue antes o en la fecha de vencimiento
                        if ($fechaOperacionReal->lte($fechaVencimiento)) {
                            Log::info("🔄 Regularizando moras de cuota {$cuota->id} - Operación {$fechaOperacionReal->format('Y-m-d')} vs Vencimiento {$fechaVencimiento->format('Y-m-d')}");
                            $this->regularizarMorasPorPagoATiempo($cuota, $operacionReciente->fecha, $userId);
                        } else {
                            Log::info("⏰ Cuota {$cuota->id} pagada tarde - Operación {$fechaOperacionReal->format('Y-m-d')} posterior a vencimiento {$fechaVencimiento->format('Y-m-d')}");
                        }
                    }
                }
            } else {
                Log::info('ℹ️ No se encontraron cuotas con pagos recientes que requieran regularización de moras');
            }

        } catch (\Exception $e) {
            Log::error('❌ Error verificando regularización de moras por fecha de operación: '.$e->getMessage());
            // No lanzar excepción para no interrumpir el proceso de pago
        }
    }

    public function showAnular($operacion_id)
    {
        try {
            $operacion = Operacion::with([
                'cliente.persona',
                'prestamo.cuotas',
                'metodoDePago',
                'user',
                'editadoPor',
                'anuladoPor',
                'cuotas',
            ])->findOrFail($operacion_id);

            // Verificar que la operación no esté ya anulada
            if ($operacion->estado === 'anulado') {
                return redirect()
                    ->route('admin.operaciones.index')
                    ->with('error', 'Esta operación ya ha sido anulada anteriormente.');
            }

            // Verificar que sea una operación que se pueda anular
            $tiposPermitidos = ['Desembolso', 'Pago de cuota', 'Pago de mora', 'Pago general'];
            if (! in_array($operacion->tipo_operacion, $tiposPermitidos)) {
                return redirect()
                    ->route('admin.operaciones.index')
                    ->with('error', 'Solo se pueden anular operaciones de pago o desembolso.');
            }

            // Validación específica para desembolsos
            if ($operacion->tipo_operacion === 'Desembolso') {
                $prestamo = $operacion->prestamo;
                $cuotasCount = $prestamo->cuotas()->count();

                if ($cuotasCount > 0) {
                    // Verificar si alguna cuota tiene pagos
                    $cuotasConPagos = $prestamo->cuotas()
                        ->whereHas('operaciones', function ($query) {
                            $query->where('tipo_operacion', 'Pago')
                                ->where('estado', '!=', 'anulado');
                        })
                        ->count();

                    if ($cuotasConPagos > 0) {
                        return redirect()
                            ->route('admin.operaciones.index')
                            ->with('error', 'No se puede anular el desembolso porque el préstamo ya tiene pagos registrados en las cuotas.');
                    }

                    return redirect()
                        ->route('admin.operaciones.index')
                        ->with('error', 'No se puede anular el desembolso porque el préstamo ya tiene cuotas generadas. Elimine primero las cuotas si es necesario.');
                }
            }

            return view('admin.pagos.anular', compact('operacion'));

        } catch (\Exception $e) {
            Log::error('Error mostrando formulario de anulación de pago: '.$e->getMessage());

            return redirect()
                ->route('admin.operaciones.index')
                ->with('error', 'Error al cargar el formulario de anulación.');
        }
    }

    /**
     * Regulariza moras basándose en la fecha real del pago registrado
     * NUEVA LÓGICA: Solo deben existir moras hasta el día del pago (inclusive)
     * Las moras posteriores a la fecha del pago deben regularizarse
     *
     * @param  string  $fechaPago  - Fecha del pago registrado en operaciones
     */
    private function regularizarMorasSegunFechaPago(Cuota $cuota, string $fechaPago, int $userId): void
    {
        try {
            // Recargar cuota fresh de la base de datos
            $cuotaFresh = Cuota::find($cuota->id);
            if (! $cuotaFresh) {
                Log::error("No se pudo recargar cuota {$cuota->id}");

                return;
            }

            $fechaVencimiento = Carbon::parse($cuotaFresh->fecha_pago)->startOfDay();
            $fechaPagoReal = Carbon::parse($fechaPago)->startOfDay();

            Log::info("🔍 REGULARIZACIÓN por fecha de pago - Cuota {$cuota->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pagado {$fechaPagoReal->format('Y-m-d')}");

            // LÓGICA PRINCIPAL:
            // - Si pago <= vencimiento: Regularizar TODAS las moras
            // - Si pago > vencimiento: Regularizar moras POSTERIORES a la fecha de pago

            if ($fechaPagoReal->lte($fechaVencimiento)) {
                // CASO 1: Pago a tiempo o anticipado - Regularizar TODAS las moras
                $morasParaRegularizar = $cuotaFresh->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->get();

                if ($morasParaRegularizar->count() > 0) {
                    Log::info("✅ Pago a tiempo - Regularizando {$morasParaRegularizar->count()} moras de la cuota {$cuota->id}");

                    foreach ($morasParaRegularizar as $mora) {
                        $this->regularizarMoraIndividual($mora, $userId, 'pago_a_tiempo');
                    }
                }

            } else {
                // CASO 2: Pago tardío - Regularizar moras posteriores al pago real
                $diasTarde = $fechaVencimiento->diffInDays($fechaPagoReal);
                Log::info("⏰ Pago tardío - Cuota {$cuota->id} pagada {$diasTarde} días después del vencimiento");

                // NUEVA LÓGICA CORREGIDA: Buscar TODAS las moras (sin importar estado) posteriores al pago
                // y regularizarlas si no están ya regularizadas
                $todasLasMoras = $cuotaFresh->moras()->orderBy('fecha')->get();
                $morasRegularizadas = 0;

                Log::info("📋 Revisando {$todasLasMoras->count()} moras para regularización por pago tardío");

                foreach ($todasLasMoras as $mora) {
                    $fechaMora = Carbon::parse($mora->fecha)->startOfDay();

                    // Si la mora es POSTERIOR a la fecha del pago, debe regularizarse
                    if ($fechaMora->gt($fechaPagoReal)) {
                        if (in_array($mora->estado->value, [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])) {
                            Log::info("   🔄 Regularizando mora {$mora->id} (fecha {$mora->fecha}) - posterior a pago {$fechaPagoReal->format('Y-m-d')}");
                            $this->regularizarMoraIndividual($mora, $userId, 'pago_tardio');
                            $morasRegularizadas++;
                        } else {
                            Log::info("   ✅ Mora {$mora->id} (fecha {$mora->fecha}) ya está regularizada");
                        }
                    } else {
                        Log::info("   📝 Mora {$mora->id} (fecha {$mora->fecha}) es válida - anterior/igual al pago");
                    }
                }

                if ($morasRegularizadas > 0) {
                    Log::info("🔄 Total moras regularizadas por pago tardío: {$morasRegularizadas}");
                } else {
                    Log::info('✅ No se encontraron moras pendientes posteriores a la fecha de pago para regularizar');
                }

                // VERIFICAR Y CREAR MORAS VÁLIDAS que deberían existir hasta la fecha de pago
                $this->verificarYCrearMorasValidas($cuotaFresh, $fechaVencimiento, $fechaPagoReal);
            }

            // Actualizar cantidad_mora de la cuota
            $totalMorasRestantes = $cuotaFresh->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                ->sum('monto');
            $cuotaFresh->update(['cantidad_mora' => $totalMorasRestantes]);

            Log::info("Cantidad de mora actualizada para cuota {$cuota->id}: S/{$totalMorasRestantes}");

        } catch (\Exception $e) {
            Log::error("Error al regularizar moras según fecha de pago para cuota {$cuota->id}: ".$e->getMessage());
        }
    }

    /**
     * Regulariza una mora individual y registra en historial
     */
    private function regularizarMoraIndividual(MoraCuota $mora, int $userId, string $motivo): void
    {
        // Registrar en historial antes del cambio
        DB::table('moras_history')->insert([
            'mora_id' => $mora->id,
            'monto_anterior' => $mora->monto_pagado ?? 0,
            'status_anterior' => $mora->estado->value,
            'monto_nuevo' => $mora->monto_pagado ?? 0,
            'status_nuevo' => MoraCuotaEstado::REGULARIZADA->value,
            'user_id' => $userId,
            'accion' => $motivo,
            'created_at' => now(),
        ]);

        // Actualizar estado de la mora
        $mora->update(['estado' => MoraCuotaEstado::REGULARIZADA->value]);

        Log::info("Mora {$mora->id} regularizada por: {$motivo}");
    }

    /**
     * Verifica y crea las moras que deberían existir hasta la fecha de pago
     * Solo crea moras que no existen y que son válidas según la fecha de pago
     */
    private function verificarYCrearMorasValidas(Cuota $cuota, Carbon $fechaVencimiento, Carbon $fechaPago): void
    {
        // Calcular los días de mora válidos (desde vencimiento hasta fecha de pago, inclusive)
        $diasMoraValidos = $fechaVencimiento->diffInDays($fechaPago);

        if ($diasMoraValidos <= 0) {
            Log::info('📝 No hay días de mora válidos para crear - pago no fue tardío');

            return;
        }

        // Limitar a máximo 7 días de mora por cuota
        $diasMoraValidos = min($diasMoraValidos, 7);

        Log::info("🔍 Verificando {$diasMoraValidos} días de mora válidos desde {$fechaVencimiento->format('Y-m-d')} hasta {$fechaPago->format('Y-m-d')}");

        // Obtener TODAS las moras existentes agrupadas por fecha (independiente del estado)
        $morasExistentes = $cuota->moras()->get()->keyBy(function ($mora) {
            return $mora->fecha->format('Y-m-d');
        });

        $morasCreadas = 0;
        $morasActivadas = 0;

        // Procesar cada día válido de mora
        for ($dia = 1; $dia <= $diasMoraValidos; $dia++) {
            $fechaMoraValida = $fechaVencimiento->copy()->addDays($dia);
            $fechaMoraKey = $fechaMoraValida->format('Y-m-d');

            // Verificar si ya existe una mora para esta fecha
            if ($morasExistentes->has($fechaMoraKey)) {
                $moraExistente = $morasExistentes->get($fechaMoraKey);

                // Si está regularizada, cambiarla a pendiente
                if ($moraExistente->estado === MoraCuotaEstado::REGULARIZADA) {
                    $moraExistente->update(['estado' => MoraCuotaEstado::PENDIENTE]);
                    $morasActivadas++;
                    Log::info("🔄 Mora reactivada: Cuota {$cuota->id}, Día {$dia}, Fecha {$fechaMoraKey} - de REGULARIZADA a PENDIENTE");
                } else {
                    Log::info("✅ Mora día {$dia} ({$fechaMoraKey}) ya está en estado correcto: {$moraExistente->estado->name}");
                }
            } else {
                // No existe, crearla (solo si realmente no existe)
                $montoMora = $cuota->prestamo->mora ?? 4.00;

                MoraCuota::create([
                    'cuota_id' => $cuota->id,
                    'fecha' => $fechaMoraValida,
                    'dias_mora' => $dia,
                    'monto' => $montoMora,
                    'estado' => MoraCuotaEstado::PENDIENTE,
                ]);

                $morasCreadas++;
                Log::info("➕ Mora creada: Cuota {$cuota->id}, Día {$dia}, Fecha {$fechaMoraKey}, Monto S/{$montoMora}");
            }
        }

        if ($morasCreadas > 0 || $morasActivadas > 0) {
            Log::info("✨ Resumen: {$morasCreadas} moras creadas, {$morasActivadas} moras reactivadas");
        } else {
            Log::info('📋 Todas las moras válidas ya estaban en estado correcto');
        }
    }

    /**
     * Registrar auditoría de edición de pago
     */
    private function registrarAuditoriaEdicionPago($operacion, $valoresOriginales, $justificacion, $operacionesHijasAfectadas = null)
    {
        $valoresNuevos = [
            'abono' => $operacion->abono,
            'fecha' => $operacion->fecha,
            'metodo_pago_id' => $operacion->metodo_pago_id,
            'cuenta_id' => $operacion->cuenta_id,
            'numero_operacion' => $operacion->numero_operacion,
            'observaciones' => $operacion->observaciones,
        ];

        // Registrar en la tabla de auditoría
        \App\Models\AuditoriaOperacion::create([
            'operacion_id' => $operacion->id,
            'prestamo_id' => $operacion->prestamo_id,
            'tipo_operacion' => $operacion->tipo_operacion,
            'accion' => 'editado',
            'usuario_id' => auth()->id(),
            'usuario_nombre' => auth()->user()->name,
            'valores_anteriores' => $valoresOriginales,
            'valores_nuevos' => $valoresNuevos,
            'operaciones_hijas_afectadas' => $operacionesHijasAfectadas,
            'justificacion' => $justificacion,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Mantener el log para compatibilidad
        Log::info('Auditoría de edición de pago', [
            'operacion_id' => $operacion->id,
            'prestamo_id' => $operacion->prestamo_id,
            'tipo_operacion' => $operacion->tipo_operacion,
            'usuario_id' => auth()->id(),
            'usuario_nombre' => auth()->user()->name,
            'valores_originales' => $valoresOriginales,
            'valores_nuevos' => $valoresNuevos,
            'operaciones_hijas_afectadas' => $operacionesHijasAfectadas,
            'justificacion' => $justificacion,
            'fecha_edicion' => now(),
        ]);
    }

    /**
     * Obtener cambios realizados en el pago
     */
    private function obtenerCambiosPago($valoresOriginales, $operacion)
    {
        $cambios = [];

        $campos = ['abono', 'fecha', 'metodo_pago_id', 'cuenta_id', 'numero_operacion', 'observaciones'];

        foreach ($campos as $campo) {
            $valorOriginal = $valoresOriginales[$campo];
            $valorNuevo = $operacion->$campo;

            if ($valorOriginal != $valorNuevo) {
                $cambios[$campo] = [
                    'anterior' => $valorOriginal,
                    'nuevo' => $valorNuevo,
                ];
            }
        }

        return $cambios;
    }
}
