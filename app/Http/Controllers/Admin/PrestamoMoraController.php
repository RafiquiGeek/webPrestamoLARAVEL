<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prestamo;
use App\Models\Cuota;
use App\Models\MoraCuota;
use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PrestamoMoraController extends Controller
{
    public function verificarYGenerarMoras(Request $request, Prestamo $prestamo)
    {
        try {
            DB::beginTransaction();

            $cuotasVerificadas = 0;
            $morasGeneradas = 0;
            $cuotasOmitidasPorFavor = 0;
            $cuotasConMoras = 0;
            $detalles = [];
            $hoy = Carbon::now();

            Log::info("=== INICIANDO VERIFICACIÓN DE MORAS PARA PRÉSTAMO #{$prestamo->id} ===");

            // Obtener todas las cuotas del préstamo que no están pagadas completamente
            $cuotas = $prestamo->cuotas()
                ->with('abonosMoraFavor', 'moras')
                ->where('estado', '!=', CuotaEstado::PAGADO)
                ->orderBy('numero')
                ->get();

            Log::info("Total de cuotas a verificar: {$cuotas->count()}");

            foreach ($cuotas as $cuota) {
                $cuotasVerificadas++;

                // Verificar si la cuota está vencida
                $fechaVencimiento = Carbon::parse($cuota->fecha_pago);

                if (!$hoy->greaterThan($fechaVencimiento)) {
                    Log::info("Cuota #{$cuota->numero}: NO VENCIDA (vence {$fechaVencimiento->format('Y-m-d')})");
                    continue; // Cuota no vencida, continuar
                }

                // VERIFICACIÓN DE SALDOS A FAVOR ANTES DE GENERAR MORAS
                $diasVencidos = $fechaVencimiento->diffInDays($hoy);
                $morasPendientes = $cuota->moras()
                    ->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value])
                    ->count();
                $morasParaGenerar = min(7 - $morasPendientes, $diasVencidos - $morasPendientes);

                Log::info("Cuota #{$cuota->numero}: días vencidos={$diasVencidos}, moras pendientes={$morasPendientes}, para generar={$morasParaGenerar}");

                if ($morasParaGenerar > 0) {
                    // Calcular costo total de las moras que se generarían
                    $montoMoraDiario = $prestamo->mora ?? 4.00;
                    $costoTotalMorasNuevas = $morasParaGenerar * $montoMoraDiario;

                    // Calcular saldo a favor disponible
                    $saldoFavorDisponible = $cuota->abonosMoraFavor()
                        ->where('estado', \App\Models\AbonoMoraFavor::ESTADO_ACTIVO)
                        ->sum('saldo_favor');

                    Log::info("Cuota #{$cuota->numero}: costo moras nuevas S/{$costoTotalMorasNuevas}, saldo favor disponible S/{$saldoFavorDisponible}");

                    // Si hay suficiente saldo a favor para cubrir TODAS las moras que se generarían, no generar nuevas moras
                    if ($saldoFavorDisponible >= $costoTotalMorasNuevas) {
                        Log::info("Cuota #{$cuota->numero}: Omitida por saldo a favor suficiente");
                        $cuotasOmitidasPorFavor++;
                        $detalles[] = [
                            'cuota_numero' => $cuota->numero,
                            'accion' => 'omitida_saldo_favor',
                            'saldo_favor' => $saldoFavorDisponible,
                        ];
                        continue;
                    }
                }

                // Generar moras usando el servicio MoraService (que ya maneja abonos a favor automáticamente)
                $resultado = app(\App\Services\MoraService::class)->procesarCuotaParaMoras($cuota);

                if ($resultado['generadas'] > 0) {
                    $morasGeneradas += $resultado['generadas'];
                    $cuotasConMoras++;
                    Log::info("✓ Cuota #{$cuota->numero}: {$resultado['generadas']} moras GENERADAS y GUARDADAS");
                    
                    $detalles[] = [
                        'cuota_numero' => $cuota->numero,
                        'accion' => 'moras_generadas',
                        'cantidad' => $resultado['generadas'],
                        'dias_vencidos' => $resultado['dias_vencidos'],
                    ];
                } else {
                    Log::info("Cuota #{$cuota->numero}: No requiere nuevas moras ({$resultado['mensaje']})");
                    $detalles[] = [
                        'cuota_numero' => $cuota->numero,
                        'accion' => 'sin_cambios',
                        'motivo' => $resultado['mensaje'],
                    ];
                }
            }

            // Actualizar estado del préstamo si tiene moras pendientes
            if ($morasGeneradas > 0) {
                $tieneMoresPendientes = $prestamo->cuotas()
                    ->whereHas('moras', function ($query) {
                        $query->whereIn('estado', [MoraCuotaEstado::PENDIENTE->value, MoraCuotaEstado::PARCIAL->value]);
                    })
                    ->exists();

                if ($tieneMoresPendientes && $prestamo->estado === 'Vigente') {
                    $prestamo->update(['estado' => 'Moroso']);
                    Log::info("✓ Préstamo #{$prestamo->id} actualizado a estado 'Moroso'");
                }
            }

            DB::commit();

            Log::info("=== VERIFICACIÓN COMPLETADA: {$morasGeneradas} moras GENERADAS Y GUARDADAS ===");

            return response()->json([
                'success' => true,
                'message' => "✓ Verificación completada exitosamente.",
                'resumen' => [
                    'cuotas_verificadas' => $cuotasVerificadas,
                    'cuotas_con_moras_generadas' => $cuotasConMoras,
                    'cuotas_omitidas_saldo_favor' => $cuotasOmitidasPorFavor,
                    'total_moras_generadas' => $morasGeneradas,
                ],
                'detalles' => $detalles,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ERROR EN VERIFICACIÓN DE MORAS: " . $e->getMessage(), [
                'prestamo_id' => $prestamo->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '❌ Error al verificar moras: ' . $e->getMessage(),
            ], 500);
        }
    }
}