<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConvenioEstado;
use App\Enums\CuotaConvenio;
use App\Enums\MoraConvenioEstado;
use App\Http\Controllers\Controller;
use App\Models\Convenio;
use App\Models\CuotaConvenioModel;
use App\Models\Operacion;
use App\Models\Prestamo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConvenioController extends Controller
{
    public function index(Request $request)
    {
        // Calcular estadísticas sobre todos los convenios
        $todosLosConvenios = Convenio::all();
        $estadisticas = [
            'activos' => $todosLosConvenios->where('estado', ConvenioEstado::ACTIVO)->count(),
            'cumplidos' => $todosLosConvenios->where('estado', ConvenioEstado::CUMPLIDO)->count(),
            'totalMonto' => $todosLosConvenios->sum('total_convenio'),
            'totalPagado' => $todosLosConvenios->sum('monto_total_pagado'),
            'promedioAvance' => $todosLosConvenios->avg('porcentaje_avance'),
        ];

        // Obtener convenios paginados con filtros (solo con préstamo válido)
        $query = Convenio::with(['prestamo.cliente.persona'])
            ->whereHas('prestamo.cliente.persona');

        // Filtro de búsqueda (DNI, nombre o ID de convenio)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                // Buscar por ID de convenio
                $q->where('id', 'like', "%{$search}%")
                  // Buscar por nombre o DNI del cliente
                  ->orWhereHas('prestamo.cliente.persona', function ($subQ) use ($search) {
                      $subQ->where('nombres', 'like', "%{$search}%")
                           ->orWhere('ape_pat', 'like', "%{$search}%")
                           ->orWhere('ape_mat', 'like', "%{$search}%")
                           ->orWhere('documento', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $estadoFiltro = $request->input('estado');
            // El enum es int (0,1,2,3) pero el form envía el nombre del caso ('ACTIVO', etc.)
            // Intentar resolver por nombre primero, luego por valor numérico
            $estadoEnum = null;
            foreach (ConvenioEstado::cases() as $case) {
                if ($case->name === strtoupper($estadoFiltro)) {
                    $estadoEnum = $case;
                    break;
                }
            }
            // Si no coincidió por nombre, intentar por valor numérico
            if ($estadoEnum === null && is_numeric($estadoFiltro)) {
                $estadoEnum = ConvenioEstado::tryFrom((int) $estadoFiltro);
            }
            if ($estadoEnum !== null) {
                $query->where('estado', $estadoEnum);
            }
        }

        // Filtro por rango de progreso
        if ($request->filled('progreso')) {
            $progreso = $request->input('progreso');
            [$min, $max] = explode('-', $progreso);
            $query->whereBetween('porcentaje_avance', [(float)$min, (float)$max]);
        }

        $convenios = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.convenios.index', compact('convenios', 'estadisticas'));
    }

    public function create(Request $request)
    {
        $prestamo_id = $request->get('prestamo_id');
        $prestamo = null;

        if ($prestamo_id) {
            $prestamo = Prestamo::with(['cuotas', 'moras', 'cliente.persona'])
                ->findOrFail($prestamo_id);


            // Verificar que el préstamo sea moroso o vigente con moras
            // También permitir 'Con Convenio' si no tiene convenios activos (caso de convenio cancelado con estado pendiente de recálculo)
            $estadosPermitidos = ['Moroso', 'Vigente con moras'];
            if (!in_array($prestamo->estado, $estadosPermitidos)) {
                // Caso especial: si está 'Con Convenio' pero no tiene convenios activos, permitir
                $tieneConvenioActivo = $prestamo->convenios()
                    ->where('estado', ConvenioEstado::ACTIVO)
                    ->exists();

                if ($prestamo->estado === 'Con Convenio' && !$tieneConvenioActivo) {
                    // Recalcular el estado del préstamo que quedó desactualizado
                    $prestamo->load(['cuotas.moras_pendientes', 'convenios']);
                    $estadoPrestamoController = new \App\Http\Controllers\Admin\EstadoPrestamoController();
                    $estadoPrestamoController->calcularYActualizarEstado($prestamo, true, 'crear_convenio_correccion');
                    $prestamo->refresh();

                    // Re-verificar después de la corrección
                    if (!in_array($prestamo->estado, $estadosPermitidos)) {
                        return redirect()->route('admin.prestamos.show', $prestamo_id)
                            ->with('error', 'Solo se pueden crear convenios para préstamos con estado Moroso o Vigente con moras.');
                    }
                } else {
                    return redirect()->route('admin.prestamos.show', $prestamo_id)
                        ->with('error', 'Solo se pueden crear convenios para préstamos con estado Moroso o Vigente con moras.');
                }
            }

            // Verificar que no exista un convenio activo para este préstamo
            $convenioActivoExistente = Convenio::where('prestamo_id', $prestamo->id)
                ->where('estado', ConvenioEstado::ACTIVO)
                ->exists();

            if ($convenioActivoExistente) {
                return redirect()->route('admin.prestamos.show', $prestamo_id)
                    ->with('error', 'Este préstamo ya tiene un convenio activo.');
            }

        }

        $opcionesCuotas = [8, 10, 12, 15, 18, 20];

        return view('admin.convenios.create', compact('prestamo', 'opcionesCuotas'));
    }

    public function store(Request $request)
    {
        // Determinar tipo de convenio
        $tipo = $request->input('tipo_convenio', 'cuotas');

        if ($tipo === 'flexible') {
            return $this->storeConvenioFlexible($request);
        }

        // Validación para convenio tipo cuotas (existente)
        $request->validate([
            'prestamo_id' => 'required|exists:prestamos,id',
            'monto_capital' => 'required|numeric|min:0',
            'monto_moras' => 'required|numeric|min:0',
            'descuento_moras' => 'required|numeric|min:0',
            'total_convenio' => 'nullable|numeric|min:0',
            'numero_cuotas' => 'required|integer|min:1|max:20',
            'fecha_inicio' => 'required|date',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $prestamo = Prestamo::findOrFail($request->prestamo_id);

        // Verificar que no exista un convenio activo para este préstamo
        $convenioActivo = Convenio::where('prestamo_id', $prestamo->id)
            ->where('estado', ConvenioEstado::ACTIVO)
            ->exists();

        if ($convenioActivo) {
            return redirect()->route('admin.prestamos.show', $prestamo->id)
                ->with('error', 'Este préstamo ya tiene un convenio activo. Debe cancelarlo primero para crear uno nuevo.');
        }

        // Usar el total editado si existe, sino calcularlo
        $totalConvenio = $request->has('total_convenio') && $request->total_convenio > 0
            ? $request->total_convenio
            : $request->monto_capital + ($request->monto_moras - $request->descuento_moras);

        $valorCuota = round($totalConvenio / $request->numero_cuotas, 2);

        // Crear el convenio
        $convenio = Convenio::create([
            'prestamo_id' => $request->prestamo_id,
            'tipo' => Convenio::TIPO_CUOTAS,
            'monto_capital' => $request->monto_capital,
            'monto_moras' => $request->monto_moras,
            'descuento_moras' => $request->descuento_moras,
            'total_convenio' => $totalConvenio,
            'numero_cuotas' => $request->numero_cuotas,
            'valor_cuota' => $valorCuota,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_firma' => now()->toDateString(),
            'estado' => ConvenioEstado::ACTIVO,
            'observaciones' => $request->observaciones,
        ]);

        // Crear las cuotas del convenio
        $fechaInicio = Carbon::parse($request->fecha_inicio);

        for ($i = 1; $i <= $request->numero_cuotas; $i++) {
            $fechaVencimiento = $fechaInicio->copy()->addWeeks($i - 1);

            CuotaConvenioModel::create([
                'convenio_id' => $convenio->id,
                'numero_cuota' => $i,
                'monto_cuota' => $valorCuota,
                'fecha_vencimiento' => $fechaVencimiento,
                'estado' => CuotaConvenio::PENDIENTE,
            ]);
        }

        // Crear moras diarias para cuotas vencidas si corresponde
        $montoMoraDiaria = $request->input('monto_mora_diaria', 0);
        if ($montoMoraDiaria > 0) {
            foreach ($convenio->cuotasConvenio as $cuota) {
                // Si la cuota ya venció y no está pagada
                if ($cuota->es_vencida) {
                    // Calcular días vencidos (máximo 7)
                    $diasVencidos = min(7, now()->diffInDays($cuota->fecha_vencimiento));
                    $fechaBase = $cuota->fecha_vencimiento->copy()->addDay();
                    // Crear una mora por cada día vencido
                    for ($i = 0; $i < $diasVencidos; $i++) {
                        $fechaMora = $fechaBase->copy()->addDays($i);
                        // Evitar duplicados
                        $existe = $cuota->moras()->whereDate('fecha', $fechaMora->format('Y-m-d'))->exists();
                        if (!$existe) {
                            $mora = $cuota->moras()->create([
                                'fecha' => $fechaMora,
                                'dias_mora' => $i + 1,
                                'monto' => $montoMoraDiaria,
                                'monto_pagado' => 0,
                                'estado' => \App\Enums\MoraCuotaEstado::PENDIENTE,
                            ]);

                            // Aplicar automáticamente abonos a favor si existen
                            $this->aplicarAbonosFavorAMora($mora);
                        }
                    }
                }
            }
        }

        return redirect()->route('admin.convenios.show', $convenio->id)
            ->with('success', 'Convenio de pago creado exitosamente.');
    }

    public function show(Convenio $convenio)
    {
        $convenio->load([
            'prestamo.cliente.persona',
            'cuotasConvenio' => function ($query) {
                $query->orderBy('numero_cuota');
            },
            'pagosFlexibles' => function ($query) {
                $query->orderBy('fecha_pago', 'desc');
            },
        ]);

        // Solo recalcular para convenios tipo cuotas
        if ($convenio->esTipoCuotas()) {
            // Recalcular los montos pagados de todas las cuotas basándose en operaciones activas
            foreach ($convenio->cuotasConvenio as $cuota) {
                $cuota->recalcularMontoPagado();
            }

            // Recargar las cuotas para obtener los valores actualizados
            $convenio->load('cuotasConvenio');
        }

        return view('admin.convenios.show', compact('convenio'));
    }

    public function edit(Convenio $convenio)
    {
        if ($convenio->estado !== ConvenioEstado::ACTIVO) {
            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('error', 'Solo se pueden editar convenios activos.');
        }

        $convenio->load('prestamo.cliente.persona');
        $opcionesCuotas = [8, 10, 12, 15, 18, 20];

        return view('admin.convenios.edit', compact('convenio', 'opcionesCuotas'));
    }

    public function update(Request $request, Convenio $convenio)
    {
        if ($convenio->estado !== ConvenioEstado::ACTIVO) {
            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('error', 'Solo se pueden editar convenios activos.');
        }

        $request->validate([
            'numero_cuotas' => 'required|integer|in:8,10,12,15,18,20',
            'fecha_inicio' => 'required|date',
            'fecha_firma' => 'required|date|before_or_equal:today',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        try {
            \DB::beginTransaction();

            // Verificar si hay cambios que requieran regenerar cuotas
            $regenerarCuotas = $convenio->numero_cuotas != $request->numero_cuotas ||
                              $convenio->fecha_inicio->format('Y-m-d') != $request->fecha_inicio;

            // Guardar pagos realizados antes de regenerar cuotas
            $pagosRealizados = [];
            if ($regenerarCuotas) {
                $cuotasConPagos = $convenio->cuotasConvenio()
                    ->where(function ($query) {
                        $query->where('monto_pagado', '>', 0)
                            ->orWhereIn('estado', [CuotaConvenio::PAGADO, CuotaConvenio::PARCIAL]);
                    })
                    ->get();

                foreach ($cuotasConPagos as $cuota) {
                    $pagosRealizados[] = [
                        'numero_cuota' => $cuota->numero_cuota,
                        'monto_pagado' => $cuota->monto_pagado,
                        'fecha_pago' => $cuota->fecha_pago,
                        'estado' => $cuota->estado,
                        'observaciones' => $cuota->observaciones,
                    ];
                }
            }

            // Actualizar datos del convenio
            $nuevoValorCuota = round($convenio->total_convenio / $request->numero_cuotas, 2);

            $convenio->update([
                'numero_cuotas' => $request->numero_cuotas,
                'valor_cuota' => $nuevoValorCuota,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_firma' => $request->fecha_firma,
                'observaciones' => $request->observaciones,
            ]);

            // Regenerar cuotas si es necesario
            if ($regenerarCuotas) {
                // Eliminar cuotas existentes
                $convenio->cuotasConvenio()->delete();

                // Crear nuevas cuotas
                $fechaInicio = Carbon::parse($request->fecha_inicio);

                for ($i = 1; $i <= $request->numero_cuotas; $i++) {
                    $fechaVencimiento = $fechaInicio->copy()->addWeeks($i - 1);

                    CuotaConvenioModel::create([
                        'convenio_id' => $convenio->id,
                        'numero_cuota' => $i,
                        'monto_cuota' => $nuevoValorCuota,
                        'fecha_vencimiento' => $fechaVencimiento,
                        'estado' => CuotaConvenio::PENDIENTE,
                    ]);
                }

                // Redistribuir pagos realizados
                $this->redistribuirPagos($convenio, $pagosRealizados);
            } else {
                // Solo actualizar observaciones sin regenerar cuotas
                $convenio->update(['observaciones' => $request->observaciones]);
            }

            // Crear moras diarias para cuotas vencidas si corresponde (al editar)
            $montoMoraDiaria = $request->input('monto_mora_diaria', 0);
            if ($montoMoraDiaria > 0) {
                foreach ($convenio->cuotasConvenio as $cuota) {
                    if ($cuota->es_vencida) {
                        $diasVencidos = min(7, now()->diffInDays($cuota->fecha_vencimiento));
                        $fechaBase = $cuota->fecha_vencimiento->copy()->addDay();
                        for ($i = 0; $i < $diasVencidos; $i++) {
                            $fechaMora = $fechaBase->copy()->addDays($i);
                            $existe = $cuota->moras()->whereDate('fecha', $fechaMora->format('Y-m-d'))->exists();
                            if (!$existe) {
                                $mora = $cuota->moras()->create([
                                    'fecha' => $fechaMora,
                                    'dias_mora' => $i + 1,
                                    'monto' => $montoMoraDiaria,
                                    'monto_pagado' => 0,
                                    'estado' => \App\Enums\MoraCuotaEstado::PENDIENTE,
                                ]);

                                // Aplicar automáticamente abonos a favor si existen
                                $this->aplicarAbonosFavorAMora($mora);
                            }
                        }
                    }
                }
            }

            \DB::commit();

            $mensaje = $regenerarCuotas ?
                'Convenio actualizado exitosamente. Las cuotas han sido regeneradas y los pagos redistribuidos.' :
                'Convenio actualizado exitosamente.';

            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            \DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el convenio: '.$e->getMessage());
        }
    }

    /**
     * Redistribuir pagos realizados en las nuevas cuotas
     */
    private function redistribuirPagos(Convenio $convenio, array $pagosRealizados)
    {
        if (empty($pagosRealizados)) {
            return;
        }

        // Calcular el total pagado
        $totalPagado = collect($pagosRealizados)->sum('monto_pagado');

        // Obtener las nuevas cuotas ordenadas
        $nuevasCuotas = $convenio->cuotasConvenio()
            ->orderBy('numero_cuota')
            ->get();

        $montoRestante = $totalPagado;

        foreach ($nuevasCuotas as $cuota) {
            if ($montoRestante <= 0) {
                break;
            }

            if ($montoRestante >= $cuota->monto_cuota) {
                // Pago completo de la cuota
                $cuota->update([
                    'monto_pagado' => $cuota->monto_cuota,
                    'estado' => CuotaConvenio::PAGADO,
                    'fecha_pago' => now(),
                    'observaciones' => 'Redistribuido automáticamente por edición del convenio',
                ]);
                $montoRestante -= $cuota->monto_cuota;
            } else {
                // Pago parcial de la cuota
                $cuota->update([
                    'monto_pagado' => $montoRestante,
                    'estado' => CuotaConvenio::PARCIAL,
                    'fecha_pago' => now(),
                    'observaciones' => 'Redistribuido automáticamente por edición del convenio',
                ]);
                $montoRestante = 0;
            }
        }
    }

    public function destroy(Convenio $convenio)
    {
        if ($convenio->estado === ConvenioEstado::CUMPLIDO) {
            return redirect()->route('admin.convenios.index')
                ->with('error', 'No se puede eliminar un convenio cumplido.');
        }

        $convenio->delete();

        return redirect()->route('admin.convenios.index')
            ->with('success', 'Convenio eliminado exitosamente.');
    }

    public function cancelar(Convenio $convenio)
    {
        if ($convenio->estado !== ConvenioEstado::ACTIVO) {
            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('error', 'Solo se pueden cancelar convenios activos.');
        }

        $convenio->update(['estado' => ConvenioEstado::CANCELADO]);

        // Recalcular el estado del préstamo para que se libere y pueda tener un nuevo convenio
        $prestamo = $convenio->prestamo;
        if ($prestamo) {
            $prestamo->load(['cuotas.moras_pendientes', 'convenios']);
            $estadoPrestamoController = new \App\Http\Controllers\Admin\EstadoPrestamoController();
            $estadoPrestamoController->calcularYActualizarEstado($prestamo, true, 'cancelar_convenio');
        }

        return redirect()->route('admin.convenios.show', $convenio->id)
            ->with('success', 'Convenio cancelado exitosamente. El préstamo ha sido liberado.');
    }

    public function calcularConvenio(Request $request)
    {
        $prestamo = Prestamo::with(['cuotas', 'moras'])
            ->findOrFail($request->prestamo_id);

        // Calcular deuda capital (cuotas impagas)
        $montoCapital = $prestamo->cuotas->whereIn('estado', [0, 1, 3])->sum('monto');

        // Calcular saldo real de moras (monto - monto_pagado) solo para estados pendiente y parcial
        $montoMoras = \App\Models\MoraCuota::whereHas('cuota', function($query) use ($prestamo) {
            $query->where('prestamo_id', $prestamo->id);
        })->whereIn('estado', [0, 1])
        ->selectRaw('COALESCE(SUM(monto - COALESCE(monto_pagado, 0)), 0) as saldo_moras')
        ->first()
        ->saldo_moras ?? 0;

        // Aplicar descuento si se proporciona
        $descuentoMoras = $request->get('descuento_moras', 0);
        $totalConvenio = $montoCapital + ($montoMoras - $descuentoMoras);

        $opcionesCuotas = [8, 10, 12, 15, 18, 20];
        $calculosCuotas = [];

        foreach ($opcionesCuotas as $numCuotas) {
            $calculosCuotas[$numCuotas] = round($totalConvenio / $numCuotas, 2);
        }

        return response()->json([
            'monto_capital' => $montoCapital,
            'monto_moras' => $montoMoras,
            'descuento_moras' => $descuentoMoras,
            'total_convenio' => $totalConvenio,
            'calculos_cuotas' => $calculosCuotas,
        ]);
    }

    public function mostrarFormularioPago(CuotaConvenioModel $cuotaConvenio)
    {
        // Verificar si hay deuda pendiente (cuota o moras)
        $saldoCuota = $cuotaConvenio->saldo_pendiente;

        // Calcular moras pendientes (excluyendo regularizadas)
        $morasPendientes = $cuotaConvenio->moras()
            ->whereNotIn('estado', ['pagado', 'regularizada'])
            ->get();
        $totalMorasPendientes = $morasPendientes->sum(function($mora) {
            return max(0, $mora->monto - $mora->monto_pagado);
        });

        // Solo bloquear si NO hay deuda en cuota NI en moras
        if ($saldoCuota <= 0 && $totalMorasPendientes <= 0) {
            return redirect()->route('admin.convenios.show', $cuotaConvenio->convenio_id)
                ->with('error', 'Esta cuota y sus moras ya han sido pagadas completamente.');
        }

        // Verificar que el convenio esté activo
        if ($cuotaConvenio->convenio->estado !== ConvenioEstado::ACTIVO) {
            return redirect()->route('admin.convenios.show', $cuotaConvenio->convenio_id)
                ->with('error', 'No se pueden registrar pagos en un convenio que no está activo.');
        }

        $cuotaConvenio->load(['convenio.prestamo.cliente.persona']);

        // Obtener datos necesarios para el formulario
        $usuarios = \App\Models\User::select('id', 'codigo')->orderBy('codigo')->get();
        $metodosDePago = \App\Models\MetodoDePago::all();

        return view('admin.convenios.pagar-cuota', compact('cuotaConvenio', 'usuarios', 'metodosDePago'));
    }

    public function procesarPago(Request $request, CuotaConvenioModel $cuotaConvenio)
    {
        $request->validate([
            'abono_cuotas' => 'required|numeric|min:0',
            'abono_moras' => 'required|numeric|min:0',
            'user_id' => 'required|exists:users,id',
            'metodoPago' => 'required|exists:metodos_de_pago,id',
            'observaciones' => 'nullable|string|max:1000',
            'nro_operacion' => 'nullable|string|max:100',
            'fecha_operacion' => 'nullable|date',
            'codigo' => 'nullable|string|max:100',
            'fecha_codigo' => 'nullable|date',
            'voucher' => 'nullable|file|image|max:2048',
        ]);

        // Validar que al menos uno de los montos sea mayor a 0
        if ($request->abono_cuotas == 0 && $request->abono_moras == 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Debe ingresar un monto mayor a cero en cuotas o moras.');
        }

        // Verificar que el convenio esté activo
        if ($cuotaConvenio->convenio->estado !== ConvenioEstado::ACTIVO) {
            return redirect()->route('admin.convenios.show', $cuotaConvenio->convenio_id)
                ->with('error', 'No se pueden registrar pagos en un convenio que no está activo.');
        }

        try {
            \DB::beginTransaction();

            $abonoCuotas = $request->abono_cuotas;
            $abonoMoras = $request->abono_moras;
            $montoPago = $abonoCuotas + $abonoMoras;
            $convenio = $cuotaConvenio->convenio;

            // Obtener todas las cuotas pendientes ordenadas por número de cuota
            $cuotasPendientes = $convenio->cuotasConvenio()
                ->whereIn('estado', [CuotaConvenio::PENDIENTE, CuotaConvenio::PARCIAL, CuotaConvenio::VENCIDO])
                ->orderBy('numero_cuota')
                ->get();

            $montoRestante = $montoPago;
            $cuotasProcesadas = [];
            $metodoPago = \App\Models\MetodoDePago::find($request->metodoPago);

            // Determinar la fecha del pago según el método de pago usado
            // Si es EFECTIVO, usar fecha_codigo, sino usar fecha_operacion
            if ($metodoPago && strtoupper($metodoPago->metodo_pago) === 'EFECTIVO') {
                $fechaPago = $request->fecha_codigo ?? now();
            } else {
                $fechaPago = $request->fecha_operacion ?? now();
            }

            // Log para debugging
            \Log::info('Fecha pago convenio recibida', [
                'metodo_pago' => $metodoPago->metodo_pago ?? 'N/A',
                'fecha_operacion' => $request->fecha_operacion,
                'fecha_codigo' => $request->fecha_codigo,
                'fecha_pago_final' => $fechaPago
            ]);

            // IMPORTANTE: Crear primero la operación general (padre)
            $operacionGeneral = \App\Models\Operacion::create([
                'cliente_id' => $convenio->prestamo->cliente_id,
                'prestamo_id' => $convenio->prestamo_id,
                'user_id' => $request->user_id,
                'tipo_operacion' => 'PAGO_CONVENIO',
                'abono' => $montoPago,
                'fecha' => $fechaPago,
                'metodo_pago_id' => $request->metodoPago,
                'comentario' => 'Pago de convenio #'.$convenio->id.' - '.count($cuotasPendientes).' cuotas',
                'estado_rendicion' => 'pendiente',
            ]);

            // Agregar campos específicos según el método de pago en la operación general
            $updateData = [];
            if ($metodoPago->metodo_pago === 'TRANSFERENCIA' || $metodoPago->metodo_pago === 'TARJETA') {
                $updateData['nro_operacion'] = $request->nro_operacion;
                $updateData['fecha_operacion'] = $request->fecha_operacion;
            } elseif ($metodoPago->metodo_pago === 'EFECTIVO') {
                $updateData['codigo'] = $request->codigo;
                $updateData['fecha_codigo'] = $request->fecha_codigo;
            }

            // Manejar el voucher
            if ($request->hasFile('voucher')) {
                $file = $request->file('voucher');
                $filename = 'voucher_convenio_'.$convenio->id.'_'.time().'.'.$file->getClientOriginalExtension();
                $file->storeAs('public/vouchers', $filename);
                $updateData['voucher_path'] = $filename;
            }

            if (!empty($updateData)) {
                $operacionGeneral->update($updateData);
            }

            // Procesar moras si hay monto destinado
            $morasProcesadas = [];
            if ($abonoMoras > 0) {
                // NUEVO FLUJO: Primero verificar si el pago es a tiempo para regularizar moras ANTES de procesarlas
                $fechaVencimiento = \Carbon\Carbon::parse($cuotaConvenio->fecha_vencimiento)->startOfDay();
                $fechaPagoReal = \Carbon\Carbon::parse($fechaPago)->startOfDay();
                $pagoATiempo = $fechaPagoReal->lte($fechaVencimiento);

                \Log::info("🔍 PROCESANDO MORAS - Cuota {$cuotaConvenio->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pago {$fechaPagoReal->format('Y-m-d')}, ¿A tiempo? ".($pagoATiempo ? 'SÍ' : 'NO'));

                if ($pagoATiempo) {
                    // CASO 1: PAGO A TIEMPO - Regularizar moras ANTES y TODO el monto es abono a favor
                    \Log::info("✅ Pago A TIEMPO - Regularizando todas las moras y convirtiendo S/{$abonoMoras} en abono a favor");

                    // Regularizar TODAS las moras pendientes/parciales
                    $morasParaRegularizar = $cuotaConvenio->moras()
                        ->whereIn('estado', [MoraConvenioEstado::PENDIENTE->value, MoraConvenioEstado::PARCIAL->value])
                        ->get();

                    if ($morasParaRegularizar->count() > 0) {
                        \Log::info("🔄 Regularizando {$morasParaRegularizar->count()} moras por pago a tiempo");
                        foreach ($morasParaRegularizar as $mora) {
                            $mora->update(['estado' => MoraConvenioEstado::REGULARIZADA->value]);
                            \Log::info("   ✅ Mora {$mora->id} (fecha {$mora->fecha}) regularizada");
                        }
                    }

                    // TODO el monto de moras se convierte en abono a favor
                    try {
                        $abonoFavor = \App\Models\AbonoMoraFavorConvenio::create([
                            'cuota_convenio_id' => $cuotaConvenio->id,
                            'operacion_id' => $operacionGeneral->id,
                            'monto_abonado' => $abonoMoras,
                            'monto_utilizado' => 0,
                            'saldo_favor' => $abonoMoras,
                            'comentario' => "Abono a favor por pago a tiempo - Cuota #{$cuotaConvenio->numero_cuota} del convenio #{$convenio->id}",
                            'estado' => \App\Models\AbonoMoraFavorConvenio::ESTADO_ACTIVO,
                            'fecha_abono' => $fechaPago,
                        ]);

                        \Log::info("✅ Mora a favor registrada para convenio", [
                            'abono_favor_id' => $abonoFavor->id,
                            'cuota_convenio_id' => $cuotaConvenio->id,
                            'monto' => $abonoMoras,
                            'convenio_id' => $convenio->id,
                            'motivo' => 'pago_a_tiempo'
                        ]);

                        $morasProcesadas[] = [
                            'id' => 'favor',
                            'dias' => 0,
                            'monto_pagado' => $abonoMoras,
                            'estado' => 'a_favor',
                        ];

                    } catch (\Exception $e) {
                        \Log::error('Error al registrar mora a favor en convenio: '.$e->getMessage());
                    }

                } else {
                    // CASO 2: PAGO TARDÍO - Regularizar moras posteriores y procesar normalmente
                    \Log::info("⏰ Pago TARDÍO - Regularizando moras posteriores y procesando");

                    // IMPORTANTE: Regularizar moras posteriores a la fecha de pago
                    // Este método maneja tanto pagos a tiempo como tardíos con fecha retroactiva
                    $this->regularizarMorasConvenioSegunFechaPago($cuotaConvenio, $fechaPago, $request->user_id);

                    // Obtener moras pendientes de la cuota actual (excluir pagadas y regularizadas)
                    $morasPendientes = $cuotaConvenio->moras()
                        ->whereNotIn('estado', [MoraConvenioEstado::PAGADO->value, MoraConvenioEstado::REGULARIZADA->value])
                        ->orderBy('fecha', 'asc')
                        ->get();

                    $montoMoras = $abonoMoras;

                    foreach ($morasPendientes as $mora) {
                        if ($montoMoras <= 0) {
                            break;
                        }

                        $saldoMora = max(0, $mora->monto - $mora->monto_pagado);

                        if ($saldoMora > 0) {
                            if ($montoMoras >= $saldoMora) {
                                // Pago completo de la mora
                                $montoPagoMora = $saldoMora;
                                $nuevoMontoPagadoMora = $mora->monto;
                                $nuevoEstadoMora = MoraConvenioEstado::PAGADO->value;
                            } else {
                                // Pago parcial de la mora
                                $montoPagoMora = $montoMoras;
                                $nuevoMontoPagadoMora = $mora->monto_pagado + $montoMoras;
                                $nuevoEstadoMora = MoraConvenioEstado::PARCIAL->value;
                            }

                            // Actualizar la mora
                            $mora->update([
                                'monto_pagado' => $nuevoMontoPagadoMora,
                                'estado' => $nuevoEstadoMora,
                            ]);

                            // Crear operación relacionada para la mora
                            $operacionMora = \App\Models\Operacion::create([
                                'operacion_general_id' => $operacionGeneral->id,
                                'cliente_id' => $convenio->prestamo->cliente_id,
                                'prestamo_id' => $convenio->prestamo_id,
                                'user_id' => $request->user_id,
                                'tipo_operacion' => 'PAGO_MORA_CONVENIO',
                                'abono' => $montoPagoMora,
                                'fecha' => $fechaPago,
                                'metodo_pago_id' => $request->metodoPago,
                                'comentario' => 'Pago mora #'.$mora->id.' de cuota #'.$cuotaConvenio->numero_cuota.' del convenio #'.$convenio->id.
                                             ($nuevoEstadoMora === 'pagado' ? ' (Completa)' : ' (Parcial)'),
                                'estado_rendicion' => 'pendiente',
                            ]);

                            $morasProcesadas[] = [
                                'id' => $mora->id,
                                'dias' => $mora->dias_mora,
                                'monto_pagado' => $montoPagoMora,
                                'estado' => $nuevoEstadoMora,
                            ];

                            $montoMoras -= $montoPagoMora;
                        }
                    }

                    // Si queda dinero sobrante en moras, registrarlo como "mora a favor"
                    if ($montoMoras > 0) {
                        try {
                            $abonoFavor = \App\Models\AbonoMoraFavorConvenio::create([
                                'cuota_convenio_id' => $cuotaConvenio->id,
                                'operacion_id' => $operacionGeneral->id,
                                'monto_abonado' => $montoMoras,
                                'monto_utilizado' => 0,
                                'saldo_favor' => $montoMoras,
                                'comentario' => "Abono anticipado a favor de mora - Cuota #{$cuotaConvenio->numero_cuota} del convenio #{$convenio->id}",
                                'estado' => \App\Models\AbonoMoraFavorConvenio::ESTADO_ACTIVO,
                                'fecha_abono' => $fechaPago,
                            ]);

                            \Log::info("✅ Mora a favor registrada para convenio", [
                                'abono_favor_id' => $abonoFavor->id,
                                'cuota_convenio_id' => $cuotaConvenio->id,
                                'monto' => $montoMoras,
                                'convenio_id' => $convenio->id,
                                'motivo' => 'excedente'
                            ]);

                            // Agregar a las moras procesadas para el mensaje
                            $morasProcesadas[] = [
                                'id' => 'favor',
                                'dias' => 0,
                                'monto_pagado' => $montoMoras,
                                'estado' => 'a_favor',
                            ];

                        } catch (\Exception $e) {
                            \Log::error('Error al registrar mora a favor en convenio: '.$e->getMessage());
                            // No interrumpimos el flujo, pero registramos el error
                        }
                    }
                }
            }

            // Procesar cuotas si hay monto destinado
            $montoRestante = $abonoCuotas;
            foreach ($cuotasPendientes as $cuota) {
                if ($montoRestante <= 0) {
                    break;
                }

                $saldoCuota = $cuota->monto_cuota - ($cuota->monto_pagado ?? 0);

                if ($montoRestante >= $saldoCuota) {
                    // Pago completo de la cuota
                    $montoPagoCuota = $saldoCuota;
                    $nuevoMontoPagado = $cuota->monto_cuota;
                    $nuevoEstado = CuotaConvenio::PAGADO;
                } else {
                    // Pago parcial de la cuota
                    $montoPagoCuota = $montoRestante;
                    $nuevoMontoPagado = ($cuota->monto_pagado ?? 0) + $montoRestante;
                    $nuevoEstado = CuotaConvenio::PARCIAL;
                }

                // Actualizar la cuota del convenio
                $cuota->update([
                    'monto_pagado' => $nuevoMontoPagado,
                    'fecha_pago' => $fechaPago,
                    'estado' => $nuevoEstado,
                    'observaciones' => $request->observaciones,
                ]);

                // REGULARIZAR MORAS de cada cuota procesada (para cuotas que no sean la principal)
                // La cuota principal ya fue regularizada arriba si el pago fue a tiempo
                if ($cuota->id !== $cuotaConvenio->id) {
                    $this->regularizarMorasConvenioSegunFechaPago($cuota, $fechaPago, $request->user_id);
                }

                // Crear operación relacionada (hija) para cada cuota afectada
                $operacionRelacionada = \App\Models\Operacion::create([
                    'operacion_general_id' => $operacionGeneral->id, // ← CLAVE: Vincular con la operación padre
                    'cliente_id' => $convenio->prestamo->cliente_id,
                    'prestamo_id' => $convenio->prestamo_id,
                    'user_id' => $request->user_id,
                    'tipo_operacion' => 'PAGO_CONVENIO',
                    'abono' => $montoPagoCuota,
                    'fecha' => $fechaPago,
                    'metodo_pago_id' => $request->metodoPago,
                    'comentario' => 'Pago cuota #'.$cuota->numero_cuota.' del convenio #'.$convenio->id.
                                 ($nuevoEstado === CuotaConvenio::PAGADO ? ' (Completa)' : ' (Parcial)'),
                    'estado_rendicion' => 'pendiente',
                ]);

                $cuotasProcesadas[] = [
                    'numero' => $cuota->numero_cuota,
                    'monto_pagado' => $montoPagoCuota,
                    'estado' => $nuevoEstado->label(),
                ];

                $montoRestante -= $montoPagoCuota;
            }

            // Verificar si todas las cuotas del convenio están pagadas
            $cuotasPendientesRestantes = $convenio->cuotasConvenio()
                ->whereIn('estado', [CuotaConvenio::PENDIENTE, CuotaConvenio::PARCIAL, CuotaConvenio::VENCIDO])
                ->count();

            if ($cuotasPendientesRestantes === 0) {
                // Todas las cuotas están pagadas, marcar convenio como cumplido
                $convenio->update(['estado' => ConvenioEstado::CUMPLIDO]);

                // Actualizar el estado del préstamo a Finalizado
                $prestamo = $convenio->prestamo;
                if ($prestamo) {
                    $prestamo->update(['estado' => 'Finalizado']);
                }
            }

            \DB::commit();

            // Construir mensaje de éxito detallado
            $mensaje = 'Pago de S/ '.number_format($montoPago, 2).' registrado exitosamente. ';

            // Agregar información de moras si se procesaron
            if (count($morasProcesadas) > 0) {
                $totalMorasPagadas = array_sum(array_column($morasProcesadas, 'monto_pagado'));
                $morasNormales = array_filter($morasProcesadas, fn($m) => $m['estado'] !== 'a_favor');
                $morasAFavor = array_filter($morasProcesadas, fn($m) => $m['estado'] === 'a_favor');

                if (count($morasNormales) > 0) {
                    $mensaje .= 'Moras: S/ '.number_format(array_sum(array_column($morasNormales, 'monto_pagado')), 2).' ('.count($morasNormales).' mora(s)). ';
                }

                if (count($morasAFavor) > 0) {
                    $montoFavor = array_sum(array_column($morasAFavor, 'monto_pagado'));
                    $mensaje .= 'Se registró S/ '.number_format($montoFavor, 2).' como abono a favor para futuras moras. ';
                }
            }

            // Agregar información de cuotas
            if (count($cuotasProcesadas) > 1) {
                $mensaje .= 'Se procesaron '.count($cuotasProcesadas).' cuotas: ';
                $detalles = array_map(function ($cuota) {
                    return "Cuota #{$cuota['numero']} - S/ ".number_format($cuota['monto_pagado'], 2)." ({$cuota['estado']})";
                }, $cuotasProcesadas);
                $mensaje .= implode(', ', $detalles);
            } elseif (count($cuotasProcesadas) == 1) {
                $cuota = $cuotasProcesadas[0];
                $mensaje .= "Cuota #{$cuota['numero']} - S/ ".number_format($cuota['monto_pagado'], 2)." ({$cuota['estado']})";
            }

            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            \DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar el pago: '.$e->getMessage());
        }
    }

    /**
     * Mostrar formulario de liquidación de convenio
     */
    public function mostrarFormularioLiquidacion($id)
    {
        $convenio = Convenio::with(['prestamo.cliente.persona', 'cuotasConvenio'])->findOrFail($id);

        // Obtener cuotas pendientes
        $cuotasPendientes = $convenio->cuotasConvenio()
            ->whereIn('estado', [CuotaConvenio::PENDIENTE, CuotaConvenio::PARCIAL, CuotaConvenio::VENCIDO])
            ->get();

        // Calcular total de cuotas pendientes
        $totalCuotas = $cuotasPendientes->sum('saldo_pendiente');

        // Obtener moras pendientes de las cuotas del convenio
        $todasLasMoras = collect();
        $totalMoras = 0;
        $morasPendientes = 0;

        foreach ($cuotasPendientes as $cuota) {
            $moras = $cuota->moras()->whereNotIn('estado', ['pagado', 'regularizada'])->get();
            foreach ($moras as $mora) {
                $saldoMora = $mora->monto - $mora->monto_pagado;
                if ($saldoMora > 0) {
                    $todasLasMoras->push($mora);
                    $totalMoras += $saldoMora;
                    $morasPendientes++;
                }
            }
        }

        // Obtener métodos de pago
        $metodosDePago = \App\Models\MetodoDePago::all();

        return view('admin.convenios.liquidacion-convenio', compact(
            'convenio',
            'cuotasPendientes',
            'totalCuotas',
            'todasLasMoras',
            'totalMoras',
            'morasPendientes',
            'metodosDePago'
        ));
    }

    /**
     * Ejecutar liquidación de convenio
     */
    public function ejecutarLiquidacion(Request $request, $id)
    {
        \DB::beginTransaction();

        try {
            // Validar request
            $request->validate([
                'descuento_cuotas' => 'numeric|min:0',
                'descuento_moras' => 'numeric|min:0',
                'metodo_pago_id' => 'required|integer|exists:metodos_de_pago,id',
                'nro_operacion' => 'nullable|string',
                'fecha_operacion' => 'nullable|date',
                'fecha_codigo' => 'nullable|date',
                'voucher' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
                'codigo' => 'nullable|string',
                'comentario' => 'nullable|string',
            ]);

            // Buscar convenio
            $convenio = Convenio::with(['prestamo', 'cuotasConvenio'])->findOrFail($id);

            // Verificar que el convenio esté activo
            if ($convenio->estado !== ConvenioEstado::ACTIVO) {
                return response()->json([
                    'success' => false,
                    'message' => 'El convenio no está activo.',
                ], 400);
            }

            // Manejar archivo voucher
            $voucherPath = null;
            if ($request->hasFile('voucher')) {
                $file = $request->file('voucher');
                $filename = 'voucher_liquidacion_convenio_'.time().'.'.$file->getClientOriginalExtension();
                $voucherPath = $file->storeAs('public/vouchers', $filename);
            }

            // Obtener cuotas pendientes
            $cuotasPendientes = $convenio->cuotasConvenio()
                ->whereIn('estado', [CuotaConvenio::PENDIENTE, CuotaConvenio::PARCIAL, CuotaConvenio::VENCIDO])
                ->get();

            $totalCuotas = $cuotasPendientes->sum('saldo_pendiente');

            // Obtener moras pendientes (excluir regularizadas)
            $todasLasMoras = collect();
            $totalMoras = 0;

            foreach ($cuotasPendientes as $cuota) {
                $moras = $cuota->moras()->whereNotIn('estado', [MoraConvenioEstado::PAGADO, MoraConvenioEstado::REGULARIZADA])->get();
                foreach ($moras as $mora) {
                    $saldoMora = $mora->monto - $mora->monto_pagado;
                    if ($saldoMora > 0) {
                        $todasLasMoras->push($mora);
                        $totalMoras += $saldoMora;
                    }
                }
            }

            // Aplicar descuentos
            $descuentoCuotas = $request->input('descuento_cuotas', 0);
            $descuentoMoras = $request->input('descuento_moras', 0);

            $totalDescuentos = $descuentoCuotas + $descuentoMoras;

            // Calcular total a liquidar
            $totalALiquidar = ($totalCuotas + $totalMoras) - $totalDescuentos;

            if ($totalALiquidar < 0) {
                $totalALiquidar = 0;
            }

            // Crear operación general de liquidación
            $operacionGeneral = new Operacion();
            $operacionGeneral->cliente_id = $convenio->prestamo->cliente_id;
            $operacionGeneral->prestamo_id = $convenio->prestamo_id;
            $operacionGeneral->convenio_id = $convenio->id;
            $operacionGeneral->fecha = $request->fecha_operacion
                ? Carbon::parse($request->fecha_operacion)
                : ($request->fecha_codigo ? Carbon::parse($request->fecha_codigo) : now());
            $operacionGeneral->metodo_pago_id = $request->metodo_pago_id;
            $operacionGeneral->abono = $totalALiquidar;
            $operacionGeneral->tipo_operacion = 'Liquidación Convenio';
            $operacionGeneral->user_id = auth()->id();
            $operacionGeneral->codigo = $request->input('codigo', 'LIQCONV-'.time());
            $operacionGeneral->comentario = $request->input('comentario', 'Liquidación total del convenio');
            $operacionGeneral->voucher_path = $voucherPath;
            $operacionGeneral->save();

            // Liquidar cuotas
            foreach ($cuotasPendientes as $cuota) {
                $saldoPendiente = $cuota->saldo_pendiente;

                if ($saldoPendiente > 0) {
                    // Crear operación específica para esta cuota
                    $operacionCuota = new Operacion();
                    $operacionCuota->cliente_id = $convenio->prestamo->cliente_id;
                    $operacionCuota->prestamo_id = $convenio->prestamo_id;
                    $operacionCuota->convenio_id = $convenio->id;
                    $operacionCuota->fecha = $operacionGeneral->fecha;
                    $operacionCuota->metodo_pago_id = $operacionGeneral->metodo_pago_id;
                    $operacionCuota->abono = $saldoPendiente;
                    $operacionCuota->tipo_operacion = 'Pago cuota convenio';
                    $operacionCuota->user_id = auth()->id();
                    $operacionCuota->operacion_general_id = $operacionGeneral->id;
                    $operacionCuota->save();

                    // Actualizar cuota
                    $nuevoMontoPagado = $cuota->monto_pagado + $saldoPendiente;
                    $cuota->update([
                        'estado' => CuotaConvenio::PAGADO,
                        'monto_pagado' => $nuevoMontoPagado,
                        'saldo_pendiente' => 0,
                    ]);

                    // REGULARIZAR MORAS después de liquidar la cuota
                    $this->regularizarMorasConvenioSegunFechaPago($cuota, $operacionGeneral->fecha, auth()->id());
                }
            }

            // Liquidar moras con descuento proporcional
            if ($todasLasMoras->count() > 0 && $descuentoMoras > 0) {
                $descuentoMorasRestante = $descuentoMoras;

                foreach ($todasLasMoras as $mora) {
                    $montoMora = $mora->monto - $mora->monto_pagado;
                    $descuentoAplicado = 0;

                    // Aplicar descuento proporcional
                    if ($descuentoMorasRestante > 0 && $totalMoras > 0) {
                        $proporcion = $montoMora / $totalMoras;
                        $descuentoAplicado = $proporcion * $descuentoMoras;
                        $descuentoAplicado = min($descuentoAplicado, $montoMora, $descuentoMorasRestante);
                    }

                    $montoFinalMora = $montoMora - $descuentoAplicado;

                    if ($montoFinalMora > 0) {
                        // Crear operación para mora
                        $operacionMora = new Operacion();
                        $operacionMora->cliente_id = $convenio->prestamo->cliente_id;
                        $operacionMora->prestamo_id = $convenio->prestamo_id;
                        $operacionMora->convenio_id = $convenio->id;
                        $operacionMora->fecha = $operacionGeneral->fecha;
                        $operacionMora->metodo_pago_id = $operacionGeneral->metodo_pago_id;
                        $operacionMora->abono = $montoFinalMora;
                        $operacionMora->tipo_operacion = 'Pago mora convenio';
                        $operacionMora->user_id = auth()->id();
                        $operacionMora->operacion_general_id = $operacionGeneral->id;
                        $operacionMora->save();
                    }

                    // Marcar mora como pagada
                    $mora->update([
                        'estado' => MoraConvenioEstado::PAGADO,
                        'monto_pagado' => $mora->monto,
                    ]);

                    $descuentoMorasRestante -= $descuentoAplicado;
                }
            } else {
                // Liquidar moras sin descuento
                foreach ($todasLasMoras as $mora) {
                    $montoMora = $mora->monto - $mora->monto_pagado;

                    if ($montoMora > 0) {
                        // Crear operación para mora
                        $operacionMora = new Operacion();
                        $operacionMora->cliente_id = $convenio->prestamo->cliente_id;
                        $operacionMora->prestamo_id = $convenio->prestamo_id;
                        $operacionMora->convenio_id = $convenio->id;
                        $operacionMora->fecha = $operacionGeneral->fecha;
                        $operacionMora->metodo_pago_id = $operacionGeneral->metodo_pago_id;
                        $operacionMora->abono = $montoMora;
                        $operacionMora->tipo_operacion = 'Pago mora convenio';
                        $operacionMora->user_id = auth()->id();
                        $operacionMora->operacion_general_id = $operacionGeneral->id;
                        $operacionMora->save();
                    }

                    // Marcar mora como pagada
                    $mora->update([
                        'estado' => MoraConvenioEstado::PAGADO,
                        'monto_pagado' => $mora->monto,
                    ]);
                }
            }

            // Actualizar estado del convenio a CUMPLIDO
            $convenio->update(['estado' => ConvenioEstado::CUMPLIDO]);

            // Actualizar estado del préstamo a Finalizado
            $prestamo = $convenio->prestamo;
            if ($prestamo) {
                $prestamo->update(['estado' => 'Finalizado']);
            }

            \DB::commit();

            $mensaje = 'Convenio liquidado totalmente de forma exitosa';
            if ($descuentoCuotas > 0) {
                $mensaje .= '. Descuento en cuotas: S/'.number_format($descuentoCuotas, 2);
            }
            if ($descuentoMoras > 0) {
                $mensaje .= '. Descuento en moras: S/'.number_format($descuentoMoras, 2);
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'total_liquidado' => $totalALiquidar,
                'descuento_cuotas' => $descuentoCuotas,
                'descuento_moras' => $descuentoMoras,
                'total_descuentos' => $totalDescuentos,
                'operacion_id' => $operacionGeneral->id,
                'cuotas_liquidadas' => $cuotasPendientes->count(),
                'moras_liquidadas' => $todasLasMoras->count(),
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al liquidar el convenio: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar formulario de liquidación para convenio flexible
     */
    public function mostrarFormularioLiquidacionFlexible($id)
    {
        $convenio = Convenio::with(['prestamo.cliente.persona', 'pagosFlexibles'])->findOrFail($id);

        if (!$convenio->esTipoFlexible()) {
            return redirect()->route('admin.convenios.liquidar.form', $convenio->id);
        }

        $saldoPendiente = $convenio->saldo_pendiente;
        $totalPagado = $convenio->monto_total_pagado;
        $metodosDePago = \App\Models\MetodoDePago::all();

        return view('admin.convenios.liquidacion-convenio-flexible', compact(
            'convenio',
            'saldoPendiente',
            'totalPagado',
            'metodosDePago'
        ));
    }

    /**
     * Ejecutar liquidación de convenio flexible
     */
    public function ejecutarLiquidacionFlexible(Request $request, $id)
    {
        \DB::beginTransaction();

        try {
            $request->validate([
                'descuento' => 'numeric|min:0',
                'metodo_pago_id' => 'required|integer|exists:metodos_de_pago,id',
                'nro_operacion' => 'nullable|string',
                'fecha_operacion' => 'nullable|date',
                'fecha_codigo' => 'nullable|date',
                'voucher' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
                'comentario' => 'nullable|string',
            ]);

            $convenio = Convenio::with(['prestamo', 'pagosFlexibles'])->findOrFail($id);

            if ($convenio->estado !== ConvenioEstado::ACTIVO) {
                return response()->json([
                    'success' => false,
                    'message' => 'El convenio no está activo.',
                ], 400);
            }

            if (!$convenio->esTipoFlexible()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este convenio no es de tipo flexible.',
                ], 400);
            }

            $saldoPendiente = $convenio->saldo_pendiente;
            $descuento = min($request->input('descuento', 0), $saldoPendiente);
            $totalALiquidar = $saldoPendiente - $descuento;

            if ($totalALiquidar < 0) {
                $totalALiquidar = 0;
            }

            // Manejar voucher
            $voucherPath = null;
            if ($request->hasFile('voucher')) {
                $file = $request->file('voucher');
                $filename = 'voucher_liq_flex_conv_'.time().'.'.$file->getClientOriginalExtension();
                $voucherPath = $file->storeAs('public/vouchers', $filename);
            }

            $fechaOperacion = $request->fecha_operacion
                ? Carbon::parse($request->fecha_operacion)
                : ($request->fecha_codigo ? Carbon::parse($request->fecha_codigo) : now());

            // Crear operación de liquidación
            $operacion = new Operacion();
            $operacion->cliente_id = $convenio->prestamo->cliente_id;
            $operacion->prestamo_id = $convenio->prestamo_id;
            $operacion->convenio_id = $convenio->id;
            $operacion->fecha = $fechaOperacion;
            $operacion->metodo_pago_id = $request->metodo_pago_id;
            $operacion->abono = $totalALiquidar;
            $operacion->tipo_operacion = 'Liquidación Convenio Flexible';
            $operacion->user_id = auth()->id();
            $operacion->codigo = $request->input('codigo', 'LIQFLEX-'.time());
            $operacion->comentario = $request->input('comentario', 'Liquidación total del convenio flexible');
            $operacion->voucher_path = $voucherPath;
            $operacion->save();

            // Registrar como pago flexible
            if ($totalALiquidar > 0) {
                \App\Models\PagoConvenioFlexible::create([
                    'convenio_id' => $convenio->id,
                    'operacion_id' => $operacion->id,
                    'monto' => $totalALiquidar,
                    'fecha_pago' => $fechaOperacion,
                    'user_id' => auth()->id(),
                    'metodo_pago' => \App\Models\MetodoDePago::find($request->metodo_pago_id)->metodo_pago ?? 'N/A',
                    'observaciones' => 'Liquidación total' . ($descuento > 0 ? ' (descuento: S/' . number_format($descuento, 2) . ')' : ''),
                ]);
            }

            // Marcar convenio como CUMPLIDO
            $convenio->update(['estado' => ConvenioEstado::CUMPLIDO]);

            // Finalizar préstamo
            $prestamo = $convenio->prestamo;
            if ($prestamo) {
                $prestamo->update(['estado' => 'Finalizado']);
            }

            \DB::commit();

            $mensaje = 'Convenio flexible liquidado exitosamente.';
            if ($descuento > 0) {
                $mensaje .= ' Descuento aplicado: S/' . number_format($descuento, 2);
            }

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'total_liquidado' => $totalALiquidar,
                'descuento' => $descuento,
                'operacion_id' => $operacion->id,
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al liquidar el convenio: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regulariza moras de convenio basándose en la fecha real del pago registrado
     * Similar a la lógica de préstamos:
     * - Si pago <= vencimiento: Regularizar TODAS las moras
     * - Si pago > vencimiento: Regularizar moras POSTERIORES a la fecha de pago
     */
    private function regularizarMorasConvenioSegunFechaPago(CuotaConvenioModel $cuota, $fechaPago, int $userId): void
    {
        try {
            // Recargar cuota fresh de la base de datos
            $cuotaFresh = CuotaConvenioModel::find($cuota->id);
            if (!$cuotaFresh) {
                \Log::error("No se pudo recargar cuota convenio {$cuota->id}");
                return;
            }

            $fechaVencimiento = Carbon::parse($cuotaFresh->fecha_vencimiento)->startOfDay();
            $fechaPagoReal = Carbon::parse($fechaPago)->startOfDay();

            \Log::info("🔍 REGULARIZACIÓN CONVENIO por fecha de pago - Cuota {$cuota->id}: Vence {$fechaVencimiento->format('Y-m-d')}, Pagado {$fechaPagoReal->format('Y-m-d')}");

            if ($fechaPagoReal->lte($fechaVencimiento)) {
                // CASO 1: Pago a tiempo o anticipado - Regularizar TODAS las moras
                $morasParaRegularizar = $cuotaFresh->moras()
                    ->whereIn('estado', [MoraConvenioEstado::PENDIENTE, MoraConvenioEstado::PARCIAL])
                    ->get();

                if ($morasParaRegularizar->count() > 0) {
                    \Log::info("✅ Pago a tiempo - Regularizando {$morasParaRegularizar->count()} moras de la cuota convenio {$cuota->id}");

                    foreach ($morasParaRegularizar as $mora) {
                        $this->regularizarMoraConvenioIndividual($mora, $userId, 'pago_a_tiempo');
                    }
                }

            } else {
                // CASO 2: Pago tardío - Regularizar moras posteriores al pago real
                $diasTarde = $fechaVencimiento->diffInDays($fechaPagoReal);
                \Log::info("⏰ Pago tardío - Cuota convenio {$cuota->id} pagada {$diasTarde} días después del vencimiento");

                $todasLasMoras = $cuotaFresh->moras()->orderBy('fecha')->get();
                $morasRegularizadas = 0;

                \Log::info("📋 Revisando {$todasLasMoras->count()} moras para regularización por pago tardío");

                foreach ($todasLasMoras as $mora) {
                    $fechaMora = Carbon::parse($mora->fecha)->startOfDay();

                    // Si la mora es POSTERIOR a la fecha del pago, debe regularizarse
                    if ($fechaMora->gt($fechaPagoReal)) {
                        // Comparar estado (ahora es string en BD)
                        if (in_array($mora->estado, [MoraConvenioEstado::PENDIENTE->value, MoraConvenioEstado::PARCIAL->value])) {
                            \Log::info("   🔄 Regularizando mora convenio {$mora->id} (fecha {$mora->fecha}) - posterior a pago {$fechaPagoReal->format('Y-m-d')}");
                            $this->regularizarMoraConvenioIndividual($mora, $userId, 'pago_tardio');
                            $morasRegularizadas++;
                        } else {
                            \Log::info("   ✅ Mora convenio {$mora->id} (fecha {$mora->fecha}) - Estado: {$mora->estado}");
                        }
                    } else {
                        \Log::info("   📝 Mora convenio {$mora->id} (fecha {$mora->fecha}) es válida - anterior/igual al pago");
                    }
                }

                if ($morasRegularizadas > 0) {
                    \Log::info("🔄 Total moras convenio regularizadas por pago tardío: {$morasRegularizadas}");
                } else {
                    \Log::info('✅ No se encontraron moras convenio pendientes posteriores a la fecha de pago para regularizar');
                }
            }

        } catch (\Exception $e) {
            \Log::error("Error al regularizar moras de convenio para cuota {$cuota->id}: ".$e->getMessage());
        }
    }

    /**
     * Regulariza una mora de convenio individual
     */
    private function regularizarMoraConvenioIndividual($mora, int $userId, string $motivo): void
    {
        // Actualizar estado de la mora a regularizada
        $mora->update(['estado' => 'regularizada']);

        \Log::info("Mora convenio {$mora->id} regularizada por: {$motivo}");
    }

    /**
     * Aplica automáticamente abonos a favor disponibles a una mora recién creada
     *
     * @param \App\Models\MoraConvenio $mora La mora recién creada
     * @return float Monto aplicado desde abonos a favor
     */
    private function aplicarAbonosFavorAMora($mora): float
    {
        try {
            // Obtener abonos a favor activos de la cuota con saldo disponible
            $abonosFavor = $mora->cuotaConvenio->abonosMoraFavor()
                ->where('estado', \App\Models\AbonoMoraFavorConvenio::ESTADO_ACTIVO)
                ->where('saldo_favor', '>', 0)
                ->orderBy('fecha_abono', 'asc')
                ->get();

            if ($abonosFavor->isEmpty()) {
                return 0;
            }

            $saldoMora = $mora->monto - $mora->monto_pagado;
            $totalAplicado = 0;

            foreach ($abonosFavor as $abono) {
                if ($saldoMora <= 0) {
                    break;
                }

                // Utilizar el saldo a favor para pagar la mora
                $montoUtilizado = $abono->utilizarSaldoFavor($saldoMora);

                if ($montoUtilizado > 0) {
                    // Actualizar la mora
                    $mora->monto_pagado += $montoUtilizado;

                    if ($mora->monto_pagado >= $mora->monto) {
                        $mora->estado = 'pagado';
                    } elseif ($mora->monto_pagado > 0) {
                        $mora->estado = 'parcial';
                    }

                    $mora->save();

                    $saldoMora -= $montoUtilizado;
                    $totalAplicado += $montoUtilizado;

                    \Log::info("✅ Abono a favor aplicado automáticamente", [
                        'mora_id' => $mora->id,
                        'abono_favor_id' => $abono->id,
                        'monto_aplicado' => $montoUtilizado,
                        'saldo_favor_restante' => $abono->saldo_favor
                    ]);
                }
            }

            return $totalAplicado;

        } catch (\Exception $e) {
            \Log::error("Error al aplicar abonos a favor a mora {$mora->id}: ".$e->getMessage());
            return 0;
        }
    }

    /**
     * Generar PDF del Estado de Cuenta del Convenio
     */
    public function estadoCuentaPDF($id)
    {
        try {
            // Obtener el convenio con todas las relaciones necesarias
            $convenio = Convenio::with([
                'prestamo.cliente.persona.direcciones.sucursal.zonas',
                'prestamo.cliente.persona.direccion',
                'prestamo.cliente.persona.telefonos',
                'prestamo.cliente.laborales',
                'prestamo.cliente.conyuge.persona.telefonos',
                'prestamo.cliente.cuentasCliente.entidadBancaria',
                'prestamo.cliente.cuentasCliente.billeteraDigital',
                'prestamo.aval.persona.direccion',
                'prestamo.aval.persona.telefonos',
                'prestamo.cuotas.operaciones.metodoDePago',
                'prestamo.cuotas.moras',
                'prestamo.carterasAnalista.user',
                'prestamo.carterasJcc.user',
                'prestamo.carterasAsesor.user',
                'prestamo.cuenta.entidadBancaria',
                'cuotasConvenio.moras',
                'cuotasConvenio.abonosMoraFavor',
            ])->findOrFail($id);

            // Obtener el préstamo asociado
            $prestamo = $convenio->prestamo;

            // Obtener las cuotas del préstamo ordenadas
            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();

            // Obtener las cuotas del convenio ordenadas
            $cuotasConvenio = $convenio->cuotasConvenio()->orderBy('numero_cuota')->get();

            // Calcular fondo provisional
            $fondo_provisional = (object) [
                'monto_fondo' => 0,
                'estado' => 'pendiente'
            ];

            // Generar el PDF usando la vista estado_cuenta_convenio
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.estado_cuenta_convenio', [
                'prestamo' => $prestamo,
                'convenio' => $convenio,
                'cuotas' => $cuotas,
                'cuotasConvenio' => $cuotasConvenio,
                'fondo_provisional' => $fondo_provisional,
            ]);

            $pdf->setPaper('A5', 'portrait');
            $pdf->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Retornar el PDF para descarga/impresión
            return $pdf->stream('estado_cuenta_convenio_'.$convenio->id.'_'.date('Y-m-d').'.pdf');

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF estado de cuenta convenio: '.$e->getMessage(), [
                'convenio_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error al generar el PDF: '.$e->getMessage());
        }
    }

    /**
     * Preview del PDF del Estado de Cuenta del Convenio
     */
    public function estadoCuentaPreview($id)
    {
        try {
            $soloConvenio = request()->boolean('solo_convenio');

            // Obtener el convenio con todas las relaciones necesarias
            $convenio = Convenio::with([
                'prestamo.cliente.persona.direcciones.sucursal.zonas',
                'prestamo.cliente.persona.telefonos',
                'prestamo.cuotas.operaciones.metodoDePago',
                'prestamo.cuotas.moras',
                'prestamo.carterasAnalista.user',
                'prestamo.carterasJcc.user',
                'prestamo.carterasAsesor.user',
                'cuotasConvenio.moras',
                'cuotasConvenio.abonosMoraFavor',
            ])->findOrFail($id);

            $prestamo = $convenio->prestamo;
            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();
            $cuotasConvenio = $convenio->cuotasConvenio()->orderBy('numero_cuota')->get();

            $fondo_provisional = (object) [
                'monto_fondo' => 0,
                'estado' => 'pendiente'
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.estado_cuenta_convenio', [
                'prestamo' => $prestamo,
                'convenio' => $convenio,
                'cuotas' => $cuotas,
                'cuotasConvenio' => $cuotasConvenio,
                'fondo_provisional' => $fondo_provisional,
                'soloConvenio' => $soloConvenio,
            ]);

            $pdf->setPaper('A5', 'portrait');
            $pdf->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Stream el PDF para visualización en el navegador
            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="estado_cuenta_convenio_'.$id.'.pdf"')
                ->header('X-Frame-Options', 'SAMEORIGIN')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');

        } catch (\Exception $e) {
            \Log::error('Error al generar preview estado de cuenta convenio: '.$e->getMessage());
            return response()->json(['error' => 'Error al generar el documento'], 500);
        }
    }

    /**
     * Descargar PDF del Estado de Cuenta del Convenio
     */
    public function descargarEstadoCuenta($id)
    {
        try {
            $convenio = Convenio::with([
                'prestamo.cliente.persona.direcciones.sucursal.zonas',
                'prestamo.cliente.persona.telefonos',
                'prestamo.cuotas.operaciones.metodoDePago',
                'prestamo.cuotas.moras',
                'prestamo.carterasAnalista.user',
                'prestamo.carterasJcc.user',
                'prestamo.carterasAsesor.user',
                'cuotasConvenio.moras',
                'cuotasConvenio.abonosMoraFavor',
            ])->findOrFail($id);

            $prestamo = $convenio->prestamo;
            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();
            $cuotasConvenio = $convenio->cuotasConvenio()->orderBy('numero_cuota')->get();

            $fondo_provisional = (object) [
                'monto_fondo' => 0,
                'estado' => 'pendiente'
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.estado_cuenta_convenio', [
                'prestamo' => $prestamo,
                'convenio' => $convenio,
                'cuotas' => $cuotas,
                'cuotasConvenio' => $cuotasConvenio,
                'fondo_provisional' => $fondo_provisional,
            ]);

            $pdf->setPaper('A5', 'portrait');
            $pdf->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'enable_php' => true,
            ]);

            // Descargar el PDF
            return $pdf->download("estado_cuenta_convenio_{$id}.pdf");

        } catch (\Exception $e) {
            \Log::error('Error al descargar estado de cuenta convenio: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error al generar el documento');
        }
    }

    /**
     * Crear convenio de tipo flexible (sin cuotas predefinidas)
     */
    private function storeConvenioFlexible(Request $request)
    {
        $request->validate([
            'prestamo_id' => 'required|exists:prestamos,id',
            'monto_capital_flexible' => 'required|numeric|min:0',
            'monto_moras_flexible' => 'required|numeric|min:0',
            'descuento_moras_flexible' => 'required|numeric|min:0',
            'total_convenio_flexible' => 'required|numeric|min:0',
            'observaciones_flexible' => 'nullable|string|max:1000',
        ]);

        $prestamo = Prestamo::findOrFail($request->prestamo_id);

        // Verificar que no exista un convenio activo para este préstamo
        $convenioActivo = Convenio::where('prestamo_id', $prestamo->id)
            ->where('estado', ConvenioEstado::ACTIVO)
            ->exists();

        if ($convenioActivo) {
            return redirect()->route('admin.prestamos.show', $prestamo->id)
                ->with('error', 'Este préstamo ya tiene un convenio activo. Debe cancelarlo primero para crear uno nuevo.');
        }

        // Calcular total
        $totalConvenio = $request->total_convenio_flexible;

        // Crear el convenio flexible
        $convenio = Convenio::create([
            'prestamo_id' => $request->prestamo_id,
            'tipo' => Convenio::TIPO_FLEXIBLE,
            'monto_capital' => $request->monto_capital_flexible,
            'monto_moras' => $request->monto_moras_flexible,
            'descuento_moras' => $request->descuento_moras_flexible,
            'total_convenio' => $totalConvenio,
            'numero_cuotas' => null,  // No aplica
            'valor_cuota' => null,     // No aplica
            'fecha_inicio' => null,    // No aplica
            'fecha_firma' => now()->toDateString(),
            'estado' => ConvenioEstado::ACTIVO,
            'observaciones' => $request->observaciones_flexible,
        ]);

        // NO se crean cuotas ni moras para convenios flexibles

        return redirect()->route('admin.convenios.show', $convenio->id)
            ->with('success', 'Convenio flexible creado exitosamente. El cliente puede realizar pagos cuando lo desee.');
    }

    /**
     * Mostrar formulario para registrar pago en convenio flexible
     */
    public function mostrarFormularioPagoFlexible(Convenio $convenio)
    {
        // Verificar que sea convenio flexible
        if (!$convenio->esTipoFlexible()) {
            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('error', 'Este no es un convenio flexible.');
        }

        // Verificar que el convenio esté activo
        if ($convenio->estado !== ConvenioEstado::ACTIVO) {
            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('error', 'No se pueden registrar pagos en un convenio que no está activo.');
        }

        // Verificar que aún haya saldo pendiente
        if ($convenio->saldo_pendiente <= 0) {
            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('error', 'Este convenio ya ha sido pagado completamente.');
        }

        $convenio->load(['prestamo.cliente.persona']);

        // Obtener datos necesarios
        $usuarios = \App\Models\User::select('id', 'codigo')->orderBy('codigo')->get();
        $metodosDePago = \App\Models\MetodoDePago::all();
        $saldoPendiente = $convenio->saldo_pendiente;

        return view('admin.convenios.pagar-flexible', compact(
            'convenio',
            'usuarios',
            'metodosDePago',
            'saldoPendiente'
        ));
    }

    /**
     * Procesar pago de convenio flexible
     */
    public function procesarPagoFlexible(Request $request, Convenio $convenio)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'fecha_pago' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'metodoPago' => 'required|exists:metodos_de_pago,id',
            'observaciones' => 'nullable|string|max:1000',
            'nro_operacion' => 'nullable|string|max:100',
            'fecha_operacion' => 'nullable|date',
            'codigo' => 'nullable|string|max:100',
            'fecha_codigo' => 'nullable|date',
            'voucher' => 'nullable|file|image|max:2048',
        ]);

        // Verificar que sea convenio flexible
        if (!$convenio->esTipoFlexible()) {
            return redirect()->back()->with('error', 'Este no es un convenio flexible.');
        }

        // Verificar que el convenio esté activo
        if ($convenio->estado !== ConvenioEstado::ACTIVO) {
            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('error', 'No se pueden registrar pagos en un convenio que no está activo.');
        }

        try {
            \DB::beginTransaction();

            $monto = $request->monto;
            $saldoPendiente = $convenio->saldo_pendiente;

            // Validar que no exceda el saldo
            if ($monto > $saldoPendiente) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "El monto (S/. {$monto}) excede el saldo pendiente (S/. {$saldoPendiente}).");
            }

            $metodoPago = \App\Models\MetodoDePago::find($request->metodoPago);

            // La fecha del pago siempre es la seleccionada por el usuario en el campo fecha_pago
            $fechaPago = $request->fecha_pago;

            // Crear operación
            $operacion = \App\Models\Operacion::create([
                'cliente_id' => $convenio->prestamo->cliente_id,
                'prestamo_id' => $convenio->prestamo_id,
                'convenio_id' => $convenio->id,
                'user_id' => $request->user_id,
                'tipo_operacion' => 'PAGO_CONVENIO_FLEXIBLE',
                'abono' => $monto,
                'fecha' => $fechaPago,
                'metodo_pago_id' => $request->metodoPago,
                'comentario' => $request->observaciones ?? 'Pago parcial convenio flexible #'.$convenio->id,
                'estado_rendicion' => 'pendiente',
            ]);

            // Agregar campos específicos según método de pago
            $updateData = [];
            if ($metodoPago->metodo_pago === 'TRANSFERENCIA' || $metodoPago->metodo_pago === 'TARJETA' ||
                $metodoPago->metodo_pago === 'YAPE' || $metodoPago->metodo_pago === 'PLIN' ||
                $metodoPago->metodo_pago === 'DEPOSITO') {
                $updateData['nro_operacion'] = $request->nro_operacion;
                $updateData['fecha_operacion'] = $request->fecha_operacion;
            }
            // EFECTIVO no requiere campos adicionales, solo usa fecha_pago

            // Manejar voucher
            if ($request->hasFile('voucher')) {
                $file = $request->file('voucher');
                $filename = 'voucher_convenio_flexible_'.$convenio->id.'_'.time().'.'.$file->getClientOriginalExtension();
                $file->storeAs('public/vouchers', $filename);
                $updateData['voucher_path'] = $filename;
            }

            if (! empty($updateData)) {
                $operacion->update($updateData);
            }

            // Registrar pago en tabla de pagos flexibles
            $pagoFlexible = \App\Models\PagoConvenioFlexible::create([
                'convenio_id' => $convenio->id,
                'operacion_id' => $operacion->id,
                'monto' => $monto,
                'fecha_pago' => $fechaPago,
                'user_id' => $request->user_id,
                'metodo_pago' => $metodoPago->metodo_pago,
                'observaciones' => $request->observaciones,
            ]);

            // Verificar si el convenio está completamente pagado
            $convenio->load('pagosFlexibles'); // Recargar relación
            $nuevoSaldo = $saldoPendiente - $monto;
            if ($nuevoSaldo <= 0.01) { // Tolerancia de 1 céntimo
                $convenio->update(['estado' => ConvenioEstado::CUMPLIDO]);

                // Actualizar estado del préstamo
                $prestamo = $convenio->prestamo;
                if ($prestamo) {
                    $prestamo->update(['estado' => 'Finalizado']);
                }
            }

            \DB::commit();

            $mensaje = 'Pago de S/. '.number_format($monto, 2).' registrado exitosamente.';
            if ($nuevoSaldo <= 0.01) {
                $mensaje .= ' El convenio ha sido CUMPLIDO completamente.';
            } else {
                $mensaje .= ' Saldo restante: S/. '.number_format($nuevoSaldo, 2);
            }

            return redirect()->route('admin.convenios.show', $convenio->id)
                ->with('success', $mensaje);

        } catch (\Exception $e) {
            \DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar el pago: '.$e->getMessage());
        }
    }
}
