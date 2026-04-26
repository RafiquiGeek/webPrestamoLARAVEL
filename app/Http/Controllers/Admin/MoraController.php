<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MoraCuotaEstado;
use App\Http\Controllers\Controller;
use App\Models\Cuota;
use App\Models\Mora;
use App\Models\MoraCuota;
use App\Models\MoraHistory;
use App\Services\EstadoPrestamoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MoraController extends Controller
{
    protected EstadoPrestamoService $estadoService;

    public function __construct(EstadoPrestamoService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $moras = Mora::latest()->paginate(10);

        return view('admin.Moras.index', compact('moras'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.Moras.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0|max:100',
            'status' => 'required|boolean',
        ]);

        $mora = Mora::create($request->all());

        // Registrar en histórico
        MoraHistory::create([
            'mora_id' => $mora->id,
            'monto_nuevo' => $mora->monto,
            'status_nuevo' => $mora->status,
            'user_id' => auth()->id(),
            'accion' => 'creado',
        ]);

        return redirect()->route('admin.Moras.index')
            ->with('success', 'Mora creada exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mora $mora)
    {
        return view('admin.Moras.show', compact('mora'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Intentar buscar primero en MoraCuota, luego en Mora
        $moraCuota = MoraCuota::find($id);
        if ($moraCuota) {
            // Cargar las operaciones asociadas a esta mora
            $moraCuota->load('operaciones');

            return view('admin.Moras.edit', compact('moraCuota'));
        }

        $mora = Mora::findOrFail($id);

        return view('admin.Moras.edit', compact('mora'));
    }

    /*
     * MÉTODO DE DEBUG - COMENTADO PARA PRODUCCIÓN
     * Descomenta solo si necesitas hacer debug de moras
     *
    public function editDebug($id)
    {
        // Cargar con todas las relaciones para debug
        $moraCuota = MoraCuota::with([
            'cuota',
            'operaciones',
            'operaciones.morasCuota',
            'operaciones.morasCuota.cuota'
        ])->find($id);

        if ($moraCuota) {
            Log::info("🐛 DEBUG: MoraCuota {$id} cargada para debug", [
                'mora_id' => $moraCuota->id,
                'monto' => $moraCuota->monto,
                'operaciones_count' => $moraCuota->operaciones->count(),
                'cuota_id' => $moraCuota->cuota->id ?? 'NULL'
            ]);
            return view('admin.Moras.edit-debug', compact('moraCuota'));
        }

        // Si no es MoraCuota, buscar en Mora
        $mora = Mora::find($id);
        if ($mora) {
            Log::info("🐛 DEBUG: Mora config {$id} cargada para debug", [
                'mora_id' => $mora->id,
                'monto' => $mora->monto,
                'status' => $mora->status
            ]);
            return view('admin.Moras.edit-debug', compact('mora'));
        }

        Log::error("🐛 DEBUG: No se encontró mora con ID {$id}");
        abort(404, "Mora no encontrada");
    }
    */

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mora $mora)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0|max:100',
            'status' => 'required|boolean',
        ]);

        // Guardar valores anteriores
        $valoresAnteriores = [
            'monto' => $mora->monto,
            'status' => $mora->status,
        ];

        $mora->update($request->all());

        // Registrar en histórico
        MoraHistory::create([
            'mora_id' => $mora->id,
            'monto_anterior' => $valoresAnteriores['monto'],
            'status_anterior' => $valoresAnteriores['status'],
            'monto_nuevo' => $mora->monto,
            'status_nuevo' => $mora->status,
            'user_id' => auth()->id(),
            'accion' => 'actualizado',
        ]);

        return redirect()->route('admin.Moras.index')
            ->with('success', 'Mora actualizada exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mora $mora)
    {
        // Registrar en histórico antes de eliminar
        MoraHistory::create([
            'mora_id' => $mora->id,
            'monto_anterior' => $mora->monto,
            'status_anterior' => $mora->status,
            'user_id' => auth()->id(),
            'accion' => 'eliminado',
        ]);

        $mora->delete();

        return redirect()->route('admin.Moras.index')
            ->with('success', 'Mora eliminada exitosamente');
    }

    /**
     * Show history of changes for specific mora
     */
    public function history(Mora $mora)
    {
        $historial = $mora->historial()->with('user')->latest()->paginate(10);

        return view('admin.Moras.history', compact('mora', 'historial'));
    }

    /**
     * Genera moras para las cuotas vencidas que no tengan moras generadas.
     */
    public function generarMoras()
    {
        // Obtener la fecha de hoy
        $hoy = Carbon::today();

        // Obtener todas las cuotas que están vencidas y no tienen moras generadas
        $cuotasVencidas = Cuota::where('estado', 0)
            ->where('fecha_pago', '<', $hoy)
            ->get();

        foreach ($cuotasVencidas as $cuota) {
            $this->verificarYGenerarMoras($cuota, $hoy);
        }

        return response()->json(['message' => 'Moras generadas correctamente para cuotas sin moras previas.']);
    }

    /**
     * Verifica si una cuota ya tiene moras pendientes, y si no, las genera.
     */
    private function verificarYGenerarMoras(Cuota $cuota, Carbon $hoy)
    {
        // Verificar si la cuota ya tiene moras pendientes
        if ($cuota->moras_pendientes->isNotEmpty()) {
            Log::info("La cuota {$cuota->id} ya tiene moras registradas.");

            return;
        }

        // Si no existen moras, generarlas
        $this->registrarMorasPorCuota($cuota, $hoy);
    }

    /**
     * Registra las moras para una cuota específica.
     */
    private function registrarMorasPorCuota(Cuota $cuota, Carbon $hoy)
    {
        $fechaVencimiento = Carbon::parse($cuota->fecha_pago);
        $fechaLimite = $fechaVencimiento->copy()->addDays(7);
        $diasMora = 0;

        // Comienza a contar moras desde el día siguiente al vencimiento
        $fechaMora = $fechaVencimiento->copy()->addDay();
        Log::info("Registrando moras para la cuota {$cuota->id} desde $fechaMora hasta $fechaLimite.");

        // Obtener la mora activa configurada
        $moraActiva = Mora::where('status', true)->first();
        $montoMora = $moraActiva ? $moraActiva->monto : 5.00; // Valor por defecto si no hay mora configurada

        while ($fechaMora->lessThanOrEqualTo($fechaLimite) && $fechaMora->lessThanOrEqualTo($hoy)) {
            $diasMora++;

            // Registrar la mora - MODIFICAR ESTA LÍNEA
            MoraCuota::create([
                'cuota_id' => $cuota->id,
                'fecha' => $fechaMora->toDateString(),
                'dias_mora' => $diasMora,
                'monto' => $montoMora,
                'estado' => 0, // Usar el valor numérico directamente (0 = PENDIENTE)
            ]);

            Log::info("Mora creada para la cuota {$cuota->id} en fecha $fechaMora con monto de S/ $montoMora.");
            $fechaMora->addDay();
        }

        $cuota->cantidad_mora += $diasMora * $montoMora;
        $cuota->save();
        Log::info("Total acumulado de moras para la cuota {$cuota->id}: S/ ".($diasMora * $montoMora));
    }

    /**
     * Actualiza el préstamo generando moras para cuotas vencidas.
     */
    public function actualizarPrestamo($prestamoId)
    {
        Log::info("Iniciando actualización del préstamo con ID: {$prestamoId}");

        try {
            $hoy = Carbon::today();
            Log::info("Fecha actual: {$hoy}");

            $cuotasVencidas = Cuota::where('prestamo_id', $prestamoId)
                ->where('estado', 0)
                ->where('fecha_pago', '<', $hoy)
                ->get();

            if ($cuotasVencidas->isEmpty()) {
                Log::info("No hay cuotas vencidas para el préstamo ID: {$prestamoId}");

                return response()->json(['message' => 'No hay cuotas vencidas para generar moras.']);
            }

            Log::info('Cuotas vencidas encontradas: '.$cuotasVencidas->count());

            foreach ($cuotasVencidas as $cuota) {
                Log::info("Procesando cuota ID: {$cuota->id}");
                $this->verificarYGenerarMoras($cuota, $hoy);
            }

            Log::info("Préstamo ID {$prestamoId} actualizado correctamente.");

            return response()->json(['message' => 'Préstamo actualizado y moras generadas correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar préstamo: '.$e->getMessage());

            return response()->json(['message' => 'Error en el servidor: '.$e->getMessage()], 500);
        }
    }

    /**
     * Pagar una mora específica.
     */
    public function pagarMora($moraId)
    {
        try {
            $mora = MoraCuota::findOrFail($moraId);
            $mora->estado = MoraCuotaEstado::PAGADO->value;
            $mora->save();

            Log::info("Mora ID {$moraId} marcada como pagada.");

            return response()->json([
                'success' => true,
                'message' => 'Mora pagada correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al pagar mora ID {$moraId}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al pagar la mora: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver moras pendientes.
     */
    public function verMorasPendientes()
    {
        try {
            $morasPendientes = MoraCuota::with(['cuota.prestamo.cliente.persona'])
                ->where('estado', MoraCuotaEstado::PENDIENTE->value)
                ->orderBy('fecha', 'desc')
                ->paginate(20);

            return view('admin.Moras.pendientes', compact('morasPendientes'));
        } catch (\Exception $e) {
            Log::error('Error al cargar moras pendientes: '.$e->getMessage());

            return redirect()->back()->with('error', 'Error al cargar moras pendientes.');
        }
    }

    /**
     * Generar mora individual para una cuota específica.
     */
    public function generarMoraIndividual(Cuota $cuota)
    {
        try {
            $hoy = Carbon::today();

            // Verificar si la cuota está vencida
            $fechaVencimiento = Carbon::parse($cuota->fecha_pago);
            if ($fechaVencimiento->gte($hoy)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La cuota no está vencida.',
                ], 400);
            }

            $this->verificarYGenerarMoras($cuota, $hoy);

            return response()->json([
                'success' => true,
                'message' => 'Mora generada correctamente para la cuota.',
            ]);
        } catch (\Exception $e) {
            Log::error("Error al generar mora individual para cuota {$cuota->id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al generar la mora: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Editar una mora específica - INTEGRADO CON ESTADO PRESTAMO SERVICE
     */
    public function editarMora(Request $request, $moraId)
    {
        try {
            $request->validate([
                'monto_total_pago' => 'required|numeric|min:0',
                'fecha_pago' => 'required|date',
                'justificacion' => 'required|string|min:10',
            ]);

            $mora = MoraCuota::with(['operaciones', 'cuota.prestamo'])->findOrFail($moraId);

            $operacion = $mora->operaciones->first();
            if (! $operacion) {
                throw new \Exception("No se encontró operación asociada a la mora {$moraId}");
            }

            $prestamo = $mora->cuota->prestamo;
            if (! $prestamo) {
                throw new \Exception("No se encontró préstamo asociado a la mora {$moraId}");
            }

            Log::info('🔧 Iniciando edición de mora usando EstadoPrestamoService', [
                'mora_id' => $moraId,
                'operacion_id' => $operacion->id,
                'prestamo_id' => $prestamo->id,
                'monto_anterior' => $operacion->abono,
                'monto_nuevo' => $request->monto_total_pago,
                'fecha_anterior' => $operacion->fecha,
                'fecha_nueva' => $request->fecha_pago,
            ]);

            // Actualizar la operación
            $operacion->update([
                'abono' => $request->monto_total_pago,
                'fecha' => $request->fecha_pago,
                'justificacion_edicion' => $request->justificacion,
                'editado_por' => auth()->id(),
                'editado_en' => now(),
                'updated_at' => now(),
            ]);

            // 💰 VERIFICAR ABONOS A FAVOR ANTES DEL RECÁLCULO
            $this->procesarAbonosFavorDespuesEdicionMora($mora, $request->monto_total_pago);

            // Usar EstadoPrestamoService para recalcular todo el préstamo
            $resultadoRecalculo = $this->estadoService->recalcularTodo($prestamo);

            Log::info('✅ Mora editada con EstadoPrestamoService', [
                'operacion_id' => $operacion->id,
                'mora_editada' => $moraId,
                'usuario' => auth()->id(),
                'resultado_recalculo' => $resultadoRecalculo,
                'justificacion' => $request->justificacion,
            ]);

            return redirect()->back()->with('success', 'Mora editada correctamente. Estados actualizados automáticamente.');

        } catch (\Exception $e) {
            Log::error("❌ Error al editar mora {$moraId}: ".$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Error al editar la mora: '.$e->getMessage());
        }
    }

    /**
     * Mostrar formulario para anular una mora específica
     */
    public function showAnularMora($moraId)
    {
        try {
            $mora = MoraCuota::with([
                'cuota.prestamo.cliente.persona',
                'operaciones.metodoDePago',
                'operaciones.user',
            ])->findOrFail($moraId);

            return view('admin.Moras.anular', compact('mora'));
        } catch (\Exception $e) {
            Log::error("Error al mostrar formulario de anulación de mora {$moraId}: ".$e->getMessage());

            return redirect()->back()->with('error', 'No se pudo cargar el formulario de anulación de mora.');
        }
    }

    /**
     * Anular una mora específica - INTEGRADO CON ESTADO PRESTAMO SERVICE
     */
    public function anularMora(Request $request, $moraId)
    {
        $request->validate([
            'justificacion' => 'required|string|min:10',
        ]);

        try {
            $mora = MoraCuota::with('cuota.prestamo', 'operaciones')->findOrFail($moraId);
            $montoOriginal = $mora->monto;
            $prestamo = $mora->cuota->prestamo;

            Log::info("🚫 Iniciando anulación de mora {$moraId} usando EstadoPrestamoService", [
                'mora_id' => $moraId,
                'prestamo_id' => $prestamo->id,
                'monto_original' => $montoOriginal,
                'estado_original' => $mora->estado,
                'operaciones_relacionadas' => $mora->operaciones->count(),
            ]);

            // Si la mora tiene operaciones relacionadas, anular esas operaciones
            if ($mora->operaciones->isNotEmpty()) {
                foreach ($mora->operaciones as $operacion) {
                    // Anular cada operación que pagaba esta mora
                    $resultadoAnulacion = $this->estadoService->anularOperacion(
                        $operacion,
                        "Anulación de mora {$moraId}: ".$request->justificacion,
                        auth()->id()
                    );

                    Log::info("Operación {$operacion->id} anulada como parte de anulación de mora {$moraId}");
                }

                // 💰 PROCESAR ABONOS A FAVOR TRAS ANULACIÓN CON OPERACIONES
                $this->procesarAbonosFavorDespuesAnulacionMora($mora);
            } else {
                // Si no tiene operaciones, es una mora sin pagos - solo marcarla como regularizada
                $mora->update([
                    'estado' => MoraCuotaEstado::REGULARIZADA,
                    'monto_pagado' => 0,
                    'updated_at' => now(),
                ]);

                // 💰 PROCESAR ABONOS A FAVOR TRAS ANULACIÓN
                $this->procesarAbonosFavorDespuesAnulacionMora($mora);

                // Recalcular el préstamo completo para ajustar estados
                $resultadoRecalculo = $this->estadoService->recalcularTodo($prestamo);

                Log::info("Mora {$moraId} sin operaciones marcada como regularizada", $resultadoRecalculo);
            }

            Log::info("✅ Mora {$moraId} anulada correctamente", [
                'mora_id' => $moraId,
                'usuario' => auth()->id(),
                'monto_anulado' => $montoOriginal,
                'justificacion' => $request->justificacion,
            ]);

            return redirect()->back()->with('success', "Mora anulada correctamente. Monto: S/ {$montoOriginal}. Estados actualizados automáticamente.");

        } catch (\Exception $e) {
            Log::error("Error al anular mora {$moraId}: ".$e->getMessage());

            return redirect()->back()->with('error', 'Error al anular la mora: '.$e->getMessage());
        }
    }

    /**
     * Procesar abonos a favor después de editar una mora
     * Cuando se edita una mora que fue pagada parcialmente con abonos a favor,
     * necesitamos ajustar los saldos de abonos a favor
     */
    private function procesarAbonosFavorDespuesEdicionMora($mora, $nuevoMontoPago)
    {
        $cuota = $mora->cuota;

        // Verificar si hay abonos a favor en esta cuota
        $abonosFavor = $cuota->abonosMoraFavor()->get();

        if ($abonosFavor->isEmpty()) {
            Log::info("💰 Cuota {$cuota->id} no tiene abonos a favor - no se requiere ajuste tras edición de mora");

            return;
        }

        Log::info("💰 Verificando impacto de edición de mora {$mora->id} en {$abonosFavor->count()} abonos a favor", [
            'mora_id' => $mora->id,
            'nuevo_monto_pago' => $nuevoMontoPago,
            'monto_mora' => $mora->monto,
        ]);

        // Si la edición reduce el pago de la mora, podría liberarse saldo de abonos a favor
        // Si aumenta el pago, podría necesitar más abonos a favor

        try {
            // Reaplicar abonos a favor a todas las moras pendientes de la cuota
            // para asegurar distribución correcta
            $morasPendientes = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                ->where('id', '!=', $mora->id) // Excluir la mora que se está editando
                ->orderBy('fecha', 'asc')
                ->get();

            $totalAplicado = 0;
            foreach ($morasPendientes as $moraPendiente) {
                $montoAplicado = $cuota->aplicarAbonosFavorAMora($moraPendiente);
                $totalAplicado += $montoAplicado;

                if ($montoAplicado > 0) {
                    Log::info("💰 Reaplicado S/{$montoAplicado} a mora {$moraPendiente->id} tras edición");
                }
            }

            if ($totalAplicado > 0) {
                Log::info("💰 Total reaplicado tras edición de mora: S/{$totalAplicado}");
            }

        } catch (\Exception $e) {
            Log::error("Error procesando abonos a favor tras edición de mora {$mora->id}: ".$e->getMessage());
        }
    }

    /**
     * Procesar abonos a favor después de anular una mora
     * Cuando se anula una mora que tenía abonos a favor aplicados,
     * esos saldos quedan liberados para otras moras
     */
    private function procesarAbonosFavorDespuesAnulacionMora($mora)
    {
        $cuota = $mora->cuota;

        // Verificar si hay abonos a favor en esta cuota
        $abonosFavor = $cuota->abonosMoraFavor()->where('monto_utilizado', '>', 0)->get();

        if ($abonosFavor->isEmpty()) {
            return;
        }

        Log::info("💰 Procesando liberación de abonos a favor tras anulación de mora {$mora->id}");

        try {
            // Cuando se anula una mora, los abonos a favor utilizados quedan liberados
            // Intentar reaplicarlos a otras moras pendientes de la misma cuota
            $morasPendientes = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                ->where('id', '!=', $mora->id)
                ->orderBy('fecha', 'asc')
                ->get();

            $totalReasignado = 0;
            foreach ($morasPendientes as $moraPendiente) {
                $montoAplicado = $cuota->aplicarAbonosFavorAMora($moraPendiente);
                $totalReasignado += $montoAplicado;

                if ($montoAplicado > 0) {
                    Log::info("💰 Reasignado S/{$montoAplicado} a mora {$moraPendiente->id} tras anulación");
                }
            }

            if ($totalReasignado > 0) {
                Log::info("💰 Total reasignado tras anulación de mora: S/{$totalReasignado}");
            }

        } catch (\Exception $e) {
            Log::error("Error procesando abonos a favor tras anulación de mora {$mora->id}: ".$e->getMessage());
        }
    }
}
