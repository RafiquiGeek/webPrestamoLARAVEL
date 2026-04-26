<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CuotaEstado;
use App\Enums\DescuentoEstado;
use App\Enums\MoraCuotaEstado;
use App\Http\Controllers\Controller;
use App\Models\AbonoMoraFavor;
use App\Models\Descuento;
use App\Models\Operacion;
use App\Models\OperacionCuota;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiquidacionController extends Controller
{
    /**
     * Calcula el monto total necesario para liquidar un préstamo
     * 
     * @param int $id ID del préstamo
     * @return \Illuminate\Http\JsonResponse
     */
    public function calcular($id)
    {
        try {
            Log::info("Calculando liquidación para préstamo ID: $id");

            $prestamo = Prestamo::with(['cuotas.operaciones', 'cuotas.moras'])
                ->findOrFail($id);

            if ($prestamo->estado === 'Finalizado' || $prestamo->estado === 'Pagado') {
                return response()->json([
                    'success' => false,
                    'error' => 'El préstamo ya está liquidado/finalizado.',
                ], 400);
            }

            Log::info('Préstamo encontrado:', ['prestamo_id' => $prestamo->id, 'estado' => $prestamo->estado]);

            // Calcular cuotas no pagadas con saldos reales
            $cuotasNoPagadas = $prestamo->cuotas()
                ->where('estado', '!=', CuotaEstado::PAGADO->value)
                ->get();

            Log::info('Cuotas no pagadas encontradas:', ['count' => $cuotasNoPagadas->count()]);

            $totalCuotasNoPagadas = 0;
            $detallesCuotas = [];

            foreach ($cuotasNoPagadas as $cuota) {
                // Para liquidación anticipada, solo cobrar el capital pendiente
                // No se cobran intereses, comisiones ni IGV de cuotas no vencidas
                $capitalCuota = $cuota->pago_capital ?? 0;
                
                // Calcular cuánto capital ya se ha pagado de esta cuota
                $capitalPagado = DB::table('operaciones_cuota')
                    ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                    ->where('operaciones_cuota.cuota_id', $cuota->id)
                    ->where('operaciones.estado', '!=', 'anulado')
                    ->sum('operaciones_cuota.monto_aplicado') ?? 0;
                
                // Capital pendiente de esta cuota
                $capitalPendiente = max($capitalCuota - $capitalPagado, 0);

                $totalCuotasNoPagadas += $capitalPendiente;

                $detallesCuotas[] = [
                    'cuota_id' => $cuota->id,
                    'numero' => $cuota->numero,
                    'capital_total' => $capitalCuota,
                    'capital_pagado' => $capitalPagado,
                    'capital_pendiente' => $capitalPendiente,
                    // Información adicional para mostrar el ahorro
                    'interes_ahorrado' => $cuota->interes ?? 0,
                    'comision_ahorrada' => $cuota->comision ?? 0,
                    'igv_ahorrado' => $cuota->igv ?? 0,
                ];
            }

            // Calcular moras no pagadas (solo PENDIENTE y PARCIAL, excluir REGULARIZADA)
            $morasNoPagadas = collect();
            $detallesMoras = [];

            foreach ($prestamo->cuotas as $cuota) {
                $moras = $cuota->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->get();

                foreach ($moras as $mora) {
                    $morasNoPagadas->push($mora);
                    $detallesMoras[] = [
                        'mora_id' => $mora->id,
                        'cuota_numero' => $cuota->numero,
                        'fecha' => $mora->fecha,
                        'dias_mora' => $mora->dias_mora,
                        'monto' => $mora->monto,
                    ];
                }
            }

            $totalMorasNoPagadas = $morasNoPagadas->sum('monto');

            Log::info('Moras no pagadas encontradas:', ['count' => $morasNoPagadas->count()]);

            // 💰 Calcular abonos a favor disponibles
            $totalAbonosMoraFavor = 0;
            $detallesAbonosFavor = [];

            foreach ($prestamo->cuotas as $cuota) {
                $saldoFavorCuota = $cuota->saldo_mora_favor ?? 0;
                if ($saldoFavorCuota > 0) {
                    $totalAbonosMoraFavor += $saldoFavorCuota;
                    $detallesAbonosFavor[] = [
                        'cuota_numero' => $cuota->numero,
                        'saldo_favor' => $saldoFavorCuota,
                    ];
                }
            }

            // Total a liquidar (con descuento automático por abonos a favor)
            $totalSinDescuentos = $totalCuotasNoPagadas + $totalMorasNoPagadas;
            $totalALiquidar = $totalSinDescuentos - $totalAbonosMoraFavor;

            return response()->json([
                'success' => true,
                'prestamo' => [
                    'id' => $prestamo->id,
                    'estado' => $prestamo->estado,
                    'saldo_actual' => $prestamo->saldo,
                ],
                'cuotas_no_pagadas' => $detallesCuotas,
                'total_cuotas_no_pagadas' => $totalCuotasNoPagadas,
                'moras_no_pagadas' => $detallesMoras,
                'total_moras_no_pagadas' => $totalMorasNoPagadas,
                'abonos_mora_favor' => $detallesAbonosFavor,
                'total_abonos_mora_favor' => $totalAbonosMoraFavor,
                'descuentos' => 0, // Descuentos manuales adicionales (se pueden aplicar desde el frontend)
                'total_sin_descuentos' => $totalSinDescuentos,
                'total_a_liquidar' => $totalALiquidar,
                'resumen' => [
                    'cuotas_pendientes' => $cuotasNoPagadas->count(),
                    'moras_pendientes' => $morasNoPagadas->count(),
                    'abonos_favor_disponibles' => $totalAbonosMoraFavor,
                    'total_cuotas' => $totalCuotasNoPagadas,
                    'total_moras' => $totalMorasNoPagadas,
                    'monto_total' => $totalALiquidar,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error en calcularLiquidacion: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al calcular la liquidación: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejecuta la liquidación total de un préstamo
     * 
     * @param Request $request
     * @param int $id ID del préstamo
     * @return \Illuminate\Http\JsonResponse
     */
    public function ejecutar(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            Log::info("Iniciando liquidación para préstamo ID: $id");

            // 1. Validar campos adicionales de la modal
            $request->validate([
                'descuento_moras' => 'numeric|min:0',
                'metodo_pago_id' => 'nullable|integer|exists:metodos_de_pago,id',
                'nro_operacion' => 'nullable|string',
                'fecha_operacion' => 'nullable|date',
                'voucher' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:2048',
                'codigo' => 'nullable|string',
                'comentario' => 'nullable|string',
            ]);

            // 2. Manejar el archivo "voucher" (si se envió)
            $voucherPath = null;
            if ($request->hasFile('voucher')) {
                $file = $request->file('voucher');
                $filename = 'voucher_liquidacion_'.time().'.'.$file->getClientOriginalExtension();
                $voucherPath = $file->storeAs('public/vouchers', $filename);
            }

            // 3. Buscar el préstamo y cargar sus cuotas/operaciones
            $prestamo = Prestamo::with(['cuotas.operaciones', 'cuotas.moras'])->findOrFail($id);

            if ($prestamo->estado === 'Finalizado' || $prestamo->estado === 'Pagado') {
                return response()->json([
                    'error' => 'El préstamo ya está liquidado/finalizado.',
                ], 400);
            }

            // 4. Calcular totales reales pendientes
            $resultado = $this->calcularMontosLiquidacion($prestamo);

            $detallesCuotas = $resultado['detalles_cuotas'];
            $totalCuotasNoPagadas = $resultado['total_cuotas'];
            $morasNoPagadas = $resultado['moras_no_pagadas'];
            $totalMorasNoPagadas = $resultado['total_moras'];
            $detallesMoras = $resultado['detalles_moras'];
            $totalAbonosMoraFavor = $resultado['total_abonos_favor'];
            $cuotasConAbonosFavor = $resultado['cuotas_con_abonos_favor'];

            Log::info('Calculando liquidación:', [
                'cuotas_pendientes' => $totalCuotasNoPagadas,
                'moras_pendientes' => $totalMorasNoPagadas,
                'total_moras' => $morasNoPagadas->count(),
            ]);

            // 5. Aplicar descuentos (manual + condicional por abonos a favor)
            $descuentoMorasAdicional = $request->input('descuento_moras', 0);
            $aplicarAbonosFavor = $request->input('aplicar_abonos_favor', false);
            $abonosFavorAplicados = $aplicarAbonosFavor ? $totalAbonosMoraFavor : 0;

            $totalDescuentos = $descuentoMorasAdicional + $abonosFavorAplicados;
            
            // Calcular total considerando descuentos
            $totalALiquidar = ($totalCuotasNoPagadas + $totalMorasNoPagadas) - $totalDescuentos;

            // Validar que el total a liquidar sea coherente
            if ($totalALiquidar < 0) {
                $totalALiquidar = 0;
            }

            Log::info('Total a liquidar calculado:', [
                'descuento_manual_moras' => $descuentoMorasAdicional,
                'descuento_abonos_favor' => $abonosFavorAplicados,
                'total_descuentos' => $totalDescuentos,
                'total_a_liquidar' => $totalALiquidar,
            ]);

            if ($totalALiquidar < 0) {
                $totalALiquidar = 0;
            }

            // 6. Crear operación general de liquidación
            $operacionGeneral = $this->crearOperacionGeneral($prestamo, $request, $totalALiquidar, $voucherPath);

            // 7. Liquidar todas las cuotas pendientes
            $this->liquidarCuotas($detallesCuotas, $prestamo, $operacionGeneral);

            // 8. Liquidar todas las moras pendientes con descuentos aplicados
            $this->liquidarMoras($morasNoPagadas, $request, $totalMorasNoPagadas, $prestamo, $operacionGeneral);

            // 9. Procesar abonos a favor según decisión del usuario
            if ($totalAbonosMoraFavor > 0) {
                $this->procesarAbonosFavor($prestamo, $aplicarAbonosFavor, $abonosFavorAplicados, $totalAbonosMoraFavor, $cuotasConAbonosFavor);
            }

            // 10. Actualizar el préstamo a estado Liquidado usando controlador centralizado
            $estadoController = new EstadoPrestamoController();
            $estadoController->calcularYActualizarEstado($prestamo, true, 'liquidacion_total');

            // 11. Registrar descuentos aplicados
            $this->registrarDescuentos($prestamo, $request, $totalAbonosMoraFavor, $aplicarAbonosFavor);

            DB::commit();

            Log::info('Liquidación completada exitosamente', [
                'operacion_id' => $operacionGeneral->id,
                'prestamo_id' => $prestamo->id,
                'monto_liquidado' => $totalALiquidar,
                'cuotas_liquidadas' => count($detallesCuotas),
                'moras_liquidadas' => $morasNoPagadas->count(),
            ]);

            $mensajeLiquidacion = 'Préstamo liquidado totalmente de forma exitosa';
            if ($aplicarAbonosFavor && $abonosFavorAplicados > 0) {
                $mensajeLiquidacion .= ". Se aplicaron S/{$abonosFavorAplicados} de abonos a favor como descuento.";
            } elseif ($totalAbonosMoraFavor > 0 && !$aplicarAbonosFavor) {
                $mensajeLiquidacion .= ". Saldo de S/{$totalAbonosMoraFavor} en abonos a favor queda disponible para caja.";
            }

            if ($request->input('descuento_moras', 0) > 0) {
                $mensajeLiquidacion .= " Descuento en moras: S/".$request->input('descuento_moras', 0);
            }

            return response()->json([
                'success' => true,
                'message' => $mensajeLiquidacion,
                'total_liquidado' => $totalALiquidar,
                'abonos_favor_aplicados' => $aplicarAbonosFavor,
                'descuento_abonos_favor' => $abonosFavorAplicados,
                'saldo_reservado_caja' => $aplicarAbonosFavor ? 0 : $totalAbonosMoraFavor,
                'descuento_manual' => $request->input('descuento_moras', 0),
                'total_descuentos' => $totalDescuentos,
                'operacion_id' => $operacionGeneral->id,
                'cuotas_liquidadas' => count($detallesCuotas),
                'moras_liquidadas' => $morasNoPagadas->count(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al liquidar Prestamo ID: $id - ".$e->getMessage());
            Log::error('Traza completa: '.$e->getTraceAsString());

            return response()->json([
                'error' => 'Error al liquidar el préstamo: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcula los montos pendientes para liquidación
     * 
     * @param Prestamo $prestamo
     * @return array
     */
    private function calcularMontosLiquidacion(Prestamo $prestamo): array
    {
        $cuotasNoPagadas = $prestamo->cuotas()
            ->where('estado', '!=', CuotaEstado::PAGADO->value)
            ->get();

        $totalCuotasNoPagadas = 0;
        $detallesCuotas = [];

        foreach ($cuotasNoPagadas as $cuota) {
            // Para liquidación anticipada, solo cobrar el capital pendiente
            $capitalOriginalCuota = $cuota->pago_capital ?? 0;

            $capitalPagadoCuota = DB::table('operaciones_cuota')
                ->join('operaciones', 'operaciones_cuota.operacion_id', '=', 'operaciones.id')
                ->where('operaciones_cuota.cuota_id', $cuota->id)
                ->where('operaciones.estado', '!=', 'anulado')
                ->sum('operaciones_cuota.monto_aplicado') ?? 0;

            $capitalPendiente = max($capitalOriginalCuota - $capitalPagadoCuota, 0);
            
            $totalCuotasNoPagadas += $capitalPendiente;

            $detallesCuotas[] = [
                'cuota_id' => $cuota->id,
                'numero' => $cuota->numero,
                'monto_original' => $capitalOriginalCuota,
                'ya_pagado' => $capitalPagadoCuota,
                'saldo_pendiente' => $capitalPendiente,
            ];
        }

        // Calcular moras no pagadas (solo PENDIENTE y PARCIAL, excluir REGULARIZADA)
        $morasNoPagadas = collect();
        $totalMorasNoPagadas = 0;
        $detallesMoras = [];

        foreach ($prestamo->cuotas as $cuota) {
            $moras = $cuota->moras()
                ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                ->get();

            foreach ($moras as $mora) {
                $morasNoPagadas->push($mora);
                $totalMorasNoPagadas += $mora->monto;
                
                $detallesMoras[] = [
                    'mora_id' => $mora->id,
                    'cuota_numero' => $cuota->numero,
                    'fecha' => $mora->fecha,
                    'monto' => $mora->monto,
                ];
            }
        }

        // Calcular abonos a favor disponibles
        $totalAbonosMoraFavor = 0;
        $cuotasConAbonosFavor = collect();

        foreach ($prestamo->cuotas as $cuota) {
            $saldoFavorCuota = $cuota->saldo_mora_favor ?? 0;
            if ($saldoFavorCuota > 0) {
                $totalAbonosMoraFavor += $saldoFavorCuota;
                $cuotasConAbonosFavor->push([
                    'cuota_id' => $cuota->id,
                    'numero' => $cuota->numero,
                    'saldo_favor' => $saldoFavorCuota,
                ]);
            }
        }

        return [
            'detalles_cuotas' => $detallesCuotas,
            'total_cuotas' => $totalCuotasNoPagadas,
            'moras_no_pagadas' => $morasNoPagadas,
            'total_moras' => $totalMorasNoPagadas,
            'detalles_moras' => $detallesMoras,
            'total_abonos_favor' => $totalAbonosMoraFavor,
            'cuotas_con_abonos_favor' => $cuotasConAbonosFavor,
        ];
    }

    /**
     * Crea la operación general de liquidación
     * 
     * @param Prestamo $prestamo
     * @param Request $request
     * @param float $totalALiquidar
     * @param string|null $voucherPath
     * @return Operacion
     */
    private function crearOperacionGeneral(Prestamo $prestamo, Request $request, float $totalALiquidar, ?string $voucherPath): Operacion
    {
        $operacionGeneral = new Operacion;
        $operacionGeneral->cliente_id = $prestamo->cliente_id;
        $operacionGeneral->prestamo_id = $prestamo->id;
        $operacionGeneral->fecha = $request->fecha_operacion
            ? Carbon::parse($request->fecha_operacion)
            : now();
        $operacionGeneral->metodo_pago_id = $request->metodo_pago_id ?? 1;
        $operacionGeneral->abono = $totalALiquidar;
        $operacionGeneral->tipo_operacion = 'Liquidación Total';
        $operacionGeneral->user_id = auth()->id();
        $operacionGeneral->codigo = $request->input('codigo', 'LIQ-'.time());
        $operacionGeneral->comentario = $request->input('comentario', 'Liquidación total del préstamo');
        $operacionGeneral->voucher_path = $voucherPath;
        $operacionGeneral->save();

        return $operacionGeneral;
    }

    /**
     * Liquida todas las cuotas pendientes
     * 
     * @param array $detallesCuotas
     * @param Prestamo $prestamo
     * @param Operacion $operacionGeneral
     * @return void
     */
    private function liquidarCuotas(array $detallesCuotas, Prestamo $prestamo, Operacion $operacionGeneral): void
    {
        foreach ($detallesCuotas as $detalleCuota) {
            $cuota = $prestamo->cuotas()->find($detalleCuota['cuota_id']);
            $saldoPendiente = $detalleCuota['saldo_pendiente'];

            if ($saldoPendiente > 0) {
                // Crear operación específica para esta cuota
                $operacionCuota = new Operacion;
                $operacionCuota->cliente_id = $prestamo->cliente_id;
                $operacionCuota->prestamo_id = $prestamo->id;
                $operacionCuota->fecha = $operacionGeneral->fecha;
                $operacionCuota->metodo_pago_id = $operacionGeneral->metodo_pago_id;
                $operacionCuota->abono = $saldoPendiente;
                $operacionCuota->tipo_operacion = 'Pago de cuota';
                $operacionCuota->user_id = auth()->id();
                $operacionCuota->operacion_general_id = $operacionGeneral->id;
                $operacionCuota->save();

                // Crear relación operación-cuota con el monto aplicado
                OperacionCuota::create([
                    'operacion_id' => $operacionCuota->id,
                    'cuota_id' => $cuota->id,
                    'monto_aplicado' => $saldoPendiente,
                    'concepto' => 'liquidacion',
                    'aplicado_en' => now(),
                ]);

                // Actualizar monto_pagado sumando lo que ya estaba pagado + lo nuevo
                $nuevoMontoPagado = $detalleCuota['ya_pagado'] + $saldoPendiente;
                $cuota->update([
                    'estado' => CuotaEstado::PAGADO->value,
                    'monto_pagado' => $nuevoMontoPagado,
                ]);

                Log::info("Cuota {$cuota->id} liquidada", [
                    'monto_original' => $detalleCuota['monto_original'],
                    'ya_pagado' => $detalleCuota['ya_pagado'],
                    'saldo_pagado' => $saldoPendiente,
                    'nuevo_monto_pagado' => $nuevoMontoPagado,
                ]);
            } else {
                // Si no hay saldo pendiente pero la cuota no está marcada como pagada
                $cuota->update([
                    'estado' => CuotaEstado::PAGADO->value,
                ]);
            }
        }
    }

    /**
     * Liquida todas las moras pendientes con descuentos aplicados
     * 
     * @param \Illuminate\Support\Collection $morasNoPagadas
     * @param Request $request
     * @param float $totalMorasNoPagadas
     * @param Prestamo $prestamo
     * @param Operacion $operacionGeneral
     * @return void
     */
    private function liquidarMoras($morasNoPagadas, Request $request, float $totalMorasNoPagadas, Prestamo $prestamo, Operacion $operacionGeneral): void
    {
        $descuentoMorasRestante = $request->input('descuento_moras', 0);
        
        foreach ($morasNoPagadas as $mora) {
            $montoMora = $mora->monto;
            $descuentoAplicado = 0;
            
            // Aplicar descuento proporcional si existe
            if ($descuentoMorasRestante > 0 && $totalMorasNoPagadas > 0) {
                $proporcion = $montoMora / $totalMorasNoPagadas;
                $descuentoAplicado = $proporcion * $request->input('descuento_moras', 0);
                $descuentoAplicado = min($descuentoAplicado, $montoMora, $descuentoMorasRestante);
            }
            
            $montoFinalMora = $montoMora - $descuentoAplicado;
            
            if ($montoFinalMora > 0) {
                // Crear operación específica para esta mora
                $operacionMora = new Operacion;
                $operacionMora->cliente_id = $prestamo->cliente_id;
                $operacionMora->prestamo_id = $prestamo->id;
                $operacionMora->fecha = $operacionGeneral->fecha;
                $operacionMora->metodo_pago_id = $operacionGeneral->metodo_pago_id;
                $operacionMora->abono = $montoFinalMora;
                $operacionMora->tipo_operacion = 'Pago de mora';
                $operacionMora->user_id = auth()->id();
                $operacionMora->operacion_general_id = $operacionGeneral->id;
                $operacionMora->save();

                // Registrar relación operación-mora
                DB::table('operacion_mora')->insert([
                    'operacion_id' => $operacionMora->id,
                    'mora_cuota_id' => $mora->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Marcar mora como pagada (incluso si fue con descuento total)
            $mora->update([
                'estado' => MoraCuotaEstado::PAGADO->value,
                'monto_pagado' => $montoFinalMora,
            ]);
            
            // Reducir el descuento aplicado del total disponible
            $descuentoMorasRestante -= $descuentoAplicado;

            Log::info("Mora {$mora->id} liquidada", [
                'monto_original' => $montoMora,
                'descuento_aplicado' => $descuentoAplicado,
                'monto_final_pagado' => $montoFinalMora,
            ]);
        }
    }

    /**
     * Procesa los abonos a favor según la decisión del usuario
     * 
     * @param Prestamo $prestamo
     * @param bool $aplicarAbonosFavor
     * @param float $abonosFavorAplicados
     * @param float $totalAbonosMoraFavor
     * @param \Illuminate\Support\Collection $cuotasConAbonosFavor
     * @return void
     */
    private function procesarAbonosFavor(Prestamo $prestamo, bool $aplicarAbonosFavor, float $abonosFavorAplicados, float $totalAbonosMoraFavor, $cuotasConAbonosFavor): void
    {
        if ($aplicarAbonosFavor) {
            foreach ($prestamo->cuotas as $cuota) {
                $abonosFavorActivos = $cuota->abonosMoraFavorActivos()->get();
                foreach ($abonosFavorActivos as $abono) {
                    $abono->update([
                        'estado' => AbonoMoraFavor::ESTADO_UTILIZADO,
                        'comentario' => ($abono->comentario ?? '').' | Aplicado como descuento en liquidación total',
                    ]);
                }
            }
            Log::info("✅ Total de S/{$abonosFavorAplicados} en abonos a favor aplicados como descuento");
        } else {
            $this->crearRegistroSaldoCaja($prestamo, $totalAbonosMoraFavor, $cuotasConAbonosFavor);
            Log::info("💳 Total de S/{$totalAbonosMoraFavor} en abonos a favor disponibles para caja");
        }
    }

    /**
     * Registra los descuentos aplicados en la liquidación
     * 
     * @param Prestamo $prestamo
     * @param Request $request
     * @param float $totalAbonosMoraFavor
     * @param bool $aplicarAbonosFavor
     * @return void
     */
    private function registrarDescuentos(Prestamo $prestamo, Request $request, float $totalAbonosMoraFavor, bool $aplicarAbonosFavor): void
    {
        // Registrar descuento manual
        if ($request->input('descuento_moras', 0) > 0) {
            $nuevoDescuento = new Descuento;
            $nuevoDescuento->prestamo_id = $prestamo->id;
            $nuevoDescuento->monto = $request->input('descuento_moras', 0);
            $nuevoDescuento->estado = DescuentoEstado::APLICADO;
            $nuevoDescuento->save();
        }

        // Registrar abonos a favor como descuento automático
        if ($totalAbonosMoraFavor > 0 && $aplicarAbonosFavor) {
            $descuentoAbonosFavor = new Descuento;
            $descuentoAbonosFavor->prestamo_id = $prestamo->id;
            $descuentoAbonosFavor->monto = $totalAbonosMoraFavor;
            $descuentoAbonosFavor->estado = DescuentoEstado::APLICADO;
            $descuentoAbonosFavor->save();
        }
    }

    /**
     * Crear registro de saldo no utilizado para caja
     * Cuando los abonos a favor no se aplican en la liquidación,
     * deben quedar disponibles para extorno o suma a ingresos
     * 
     * @param Prestamo $prestamo
     * @param float $totalAbonosFavor
     * @param \Illuminate\Support\Collection $cuotasConAbonosFavor
     * @return void
     */
    private function crearRegistroSaldoCaja(Prestamo $prestamo, float $totalAbonosFavor, $cuotasConAbonosFavor): void
    {
        try {
            // Crear un registro en operaciones como "Saldo a Favor Disponible"
            $operacionSaldo = new Operacion;
            $operacionSaldo->cliente_id = $prestamo->cliente_id;
            $operacionSaldo->prestamo_id = $prestamo->id;
            $operacionSaldo->fecha = now();
            $operacionSaldo->metodo_pago_id = 1; // Efectivo por defecto
            $operacionSaldo->abono = $totalAbonosFavor;
            $operacionSaldo->tipo_operacion = 'Saldo Mora Favor Disponible';
            $operacionSaldo->user_id = auth()->id();
            $operacionSaldo->codigo = 'SMF-'.time();
            $operacionSaldo->comentario = 'Saldo de abonos de mora a favor no aplicado en liquidación. Disponible para extorno o suma a ingresos.';
            $operacionSaldo->estado = 'pendiente'; // Estado especial para indicar que requiere acción
            $operacionSaldo->save();

            // Crear descuento con estado especial para caja
            $descuentoSaldoCaja = new Descuento;
            $descuentoSaldoCaja->prestamo_id = $prestamo->id;
            $descuentoSaldoCaja->monto = $totalAbonosFavor;
            $descuentoSaldoCaja->estado = DescuentoEstado::PENDIENTE; // Estado especial para caja
            $descuentoSaldoCaja->save();

            // Marcar abonos como "reservados" para caja (nuevo estado)
            foreach ($prestamo->cuotas as $cuota) {
                $abonosFavorActivos = $cuota->abonosMoraFavorActivos()->get();

                foreach ($abonosFavorActivos as $abono) {
                    $abono->update([
                        'estado' => AbonoMoraFavor::ESTADO_RESERVADO_CAJA,
                        'comentario' => ($abono->comentario ?? '').' | Reservado para decisión de caja tras liquidación',
                    ]);
                }
            }

            Log::info('💳 Registro de saldo para caja creado', [
                'operacion_id' => $operacionSaldo->id,
                'descuento_id' => $descuentoSaldoCaja->id,
                'monto_total' => $totalAbonosFavor,
                'cuotas_afectadas' => $cuotasConAbonosFavor->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando registro de saldo para caja: '.$e->getMessage());
            // No lanzar excepción para no interrumpir la liquidación
        }
    }
}
