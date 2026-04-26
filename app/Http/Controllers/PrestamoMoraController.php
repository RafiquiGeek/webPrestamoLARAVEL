<?php

namespace App\Http\Controllers;

use App\Models\Prestamo;
use App\Models\Cuota;
use App\Models\MoraCuota;
use App\Enums\MoraCuotaEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PrestamoMoraController extends Controller
{
    public function verificarYGenerarMoras(Request $request, $prestamoId)
    {
        try {
            DB::beginTransaction();

            $prestamo = Prestamo::with('cuotas.moras', 'cuotas.abonosMoraFavor')->findOrFail($prestamoId);
            $hoy = Carbon::now();
            $morasGeneradas = 0;
            $cuotasActualizadas = 0;
            $morasCorregidas = 0;

            Log::info("Iniciando verificación y corrección de moras para préstamo #{$prestamoId}");

            foreach ($prestamo->cuotas as $cuota) {
                $fechaVencimiento = Carbon::parse($cuota->fecha_pago);

                // Solo procesar cuotas vencidas
                if (!$fechaVencimiento->isPast()) {
                    continue;
                }

                $cuotasActualizadas++;

                // VERIFICACIÓN DE SALDOS A FAVOR ANTES DE GENERAR MORAS
                $diasVencidos = $fechaVencimiento->diffInDays($hoy);
                // CORRECCIÓN: Contar solo moras realmente pendientes (no pagadas) para cuotas parciales
                $morasRealmentePendientes = $cuota->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                    ->count();
                $morasParaGenerar = min(\App\Services\MoraService::MAX_MORAS_POR_CUOTA - $morasRealmentePendientes, $diasVencidos - $morasRealmentePendientes);

                if ($morasParaGenerar > 0) {
                    // Calcular costo total de las moras que se generarían
                    $montoMoraDiario = $prestamo->mora;
                    $costoTotalMorasNuevas = $morasParaGenerar * $montoMoraDiario;

                    // Calcular saldo a favor disponible
                    $saldoFavorDisponible = $cuota->abonosMoraFavor()
                        ->where('estado', \App\Models\AbonoMoraFavor::ESTADO_ACTIVO)
                        ->sum('saldo_favor');

                    Log::info("Cuota #{$cuota->numero}: {$morasParaGenerar} moras posibles, costo S/{$costoTotalMorasNuevas}, saldo favor S/{$saldoFavorDisponible}");

                    // Si hay suficiente saldo a favor para cubrir TODAS las moras que se generarían, no generar nuevas moras
                    if ($saldoFavorDisponible >= $costoTotalMorasNuevas) {
                        Log::info("Cuota #{$cuota->numero}: Saldo a favor suficiente (S/{$saldoFavorDisponible} >= S/{$costoTotalMorasNuevas}) - Omitiendo generación de moras");
                        continue;
                    }
                }

                // CORRECCIÓN: Usar el servicio MoraService para procesar correctamente
                $resultado = app(\App\Services\MoraService::class)->procesarCuotaParaMoras($cuota);

                if ($resultado['generadas'] > 0) {
                    $morasGeneradas += $resultado['generadas'];
                    Log::info("Cuota #{$cuota->numero}: {$resultado['generadas']} moras generadas");
                }

                // Verificar si hay moras con montos incorrectos y corregirlas
                $morasIncorrectas = $cuota->moras()
                    ->where('monto', '!=', $prestamo->mora)
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                    ->get();

                foreach ($morasIncorrectas as $mora) {
                    Log::info("Corrigiendo mora {$mora->id} de cuota #{$cuota->numero}: monto {$mora->monto} -> {$prestamo->mora}");
                    $mora->update(['monto' => $prestamo->mora]);
                    $morasCorregidas++;
                }

                // Verificar límite de 7 moras por cuota (contando solo moras no regularizadas)
                $morasNoRegularizadas = $cuota->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL, MoraCuotaEstado::PAGADO])
                    ->count();

                if ($morasNoRegularizadas > 7) {
                    $morasExtra = $cuota->moras()
                        ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL, MoraCuotaEstado::PAGADO])
                        ->orderBy('fecha', 'desc')
                        ->skip(7)
                        ->get();

                    foreach ($morasExtra as $moraExtra) {
                        Log::info("Eliminando mora extra {$moraExtra->id} de cuota #{$cuota->numero} (más de 7 moras no regularizadas)");
                        $moraExtra->delete();
                        $morasCorregidas++;
                    }
                }

                // Recalcular cantidad_mora de la cuota
                $totalMorasPendientes = $cuota->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE, MoraCuotaEstado::PARCIAL])
                    ->sum(\DB::raw('COALESCE(monto - monto_pagado, monto)'));
                $cuota->update(['cantidad_mora' => $totalMorasPendientes]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Verificación completada. Moras generadas: {$morasGeneradas}, Moras corregidas: {$morasCorregidas}, Cuotas procesadas: {$cuotasActualizadas}",
                'data' => [
                    'moras_generadas' => $morasGeneradas,
                    'moras_corregidas' => $morasCorregidas,
                    'cuotas_procesadas' => $cuotasActualizadas,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en verificación de moras para préstamo #{$prestamoId}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al verificar y generar moras: ' . $e->getMessage()
            ], 500);
        }
    }
}