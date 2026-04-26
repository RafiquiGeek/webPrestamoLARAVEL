<?php

namespace App\Console\Commands;

use App\Models\Cuota;
use App\Models\Prestamo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalcularCuotasPrestamos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestamos:recalcular-cuotas {--all : Recalcular todos los préstamos} {--prestamo= : ID específico del préstamo} {--solo-sin-pagos : Solo recalcular cuotas sin pagos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula el interés, comisión e IGV de todas las cuotas usando el nuevo método de cálculo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando recálculo de cuotas de préstamos...');

        if ($this->option('prestamo')) {
            // Recalcular un préstamo específico
            $prestamoId = $this->option('prestamo');
            $prestamo = Prestamo::with('cuotas')->find($prestamoId);

            if (!$prestamo) {
                $this->error("❌ Préstamo con ID {$prestamoId} no encontrado");
                return 1;
            }

            $this->recalcularCuotasPrestamo($prestamo);
            $this->info("✅ Cuotas del préstamo {$prestamoId} recalculadas");

        } elseif ($this->option('all') || $this->confirm('¿Recalcular cuotas de todos los préstamos?')) {
            // Recalcular todos los préstamos con plazos válidos
            $query = Prestamo::with('cuotas')
                ->whereIn('plazo', [8, 12, 15, 18, 20])
                ->whereNotIn('estado', ['Cancelado']);

            // Si solo queremos recalcular préstamos sin pagos
            if ($this->option('solo-sin-pagos')) {
                $query->whereDoesntHave('cuotas', function ($q) {
                    $q->where('monto_pagado', '>', 0);
                });
                $this->info('📋 Filtrando solo préstamos sin pagos...');
            }

            $prestamos = $query->get();

            $bar = $this->output->createProgressBar($prestamos->count());
            $bar->start();

            $actualizados = 0;
            $errores = 0;

            foreach ($prestamos as $prestamo) {
                try {
                    $resultado = $this->recalcularCuotasPrestamo($prestamo);
                    if ($resultado) {
                        $actualizados++;
                    }
                } catch (\Exception $e) {
                    $errores++;
                    Log::error("Error recalculando préstamo {$prestamo->id}: " . $e->getMessage());
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ Proceso completado: {$actualizados} préstamos recalculados de {$prestamos->count()} procesados");

            if ($errores > 0) {
                $this->warn("⚠️  Errores: {$errores}");
            }
        } else {
            $this->info('Operación cancelada por el usuario');
        }

        return 0;
    }

    /**
     * Recalcula las cuotas de un préstamo usando el método correcto de cálculo
     * Toma cantidad_solicitada y plazo de la tabla prestamos y recalcula todas las cuotas
     * EXACTAMENTE como se hace al crear un préstamo nuevo
     */
    private function recalcularCuotasPrestamo(Prestamo $prestamo): bool
    {
        try {
            DB::beginTransaction();

            $cuotas = $prestamo->cuotas()->orderBy('numero')->get();

            if ($cuotas->isEmpty()) {
                Log::warning("Préstamo {$prestamo->id} no tiene cuotas para recalcular");
                DB::rollBack();
                return false;
            }

            // IMPORTANTE: Tomar cantidad_solicitada y plazo de la tabla prestamos
            $montoSolicitado = $prestamo->cantidad_solicitada;
            $plazo = $prestamo->plazo;

            // Validar que los datos sean válidos
            if (empty($montoSolicitado) || $montoSolicitado <= 0) {
                Log::warning("Préstamo {$prestamo->id} tiene cantidad_solicitada inválida: {$montoSolicitado}");
                DB::rollBack();
                return false;
            }

            if (!in_array($plazo, [8, 12, 15, 18, 20])) {
                Log::warning("Préstamo {$prestamo->id} tiene plazo {$plazo} no soportado");
                DB::rollBack();
                return false;
            }

            // Obtener la fecha de la primera cuota
            $fechaInicio = Carbon::parse($cuotas->first()->fecha_pago);

            Log::info("Recalculando préstamo {$prestamo->id}", [
                'cantidad_solicitada' => $montoSolicitado,
                'plazo' => $plazo,
                'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                'cuotas_existentes' => $cuotas->count(),
            ]);

            // Calcular cuotas usando el método correcto según el plazo
            // EXACTAMENTE como se hace en PrestamosController::store()
            if ($plazo == 8) {
                $resultadoCuotas = $this->calcularCuotas8Semanas(
                    $montoSolicitado,
                    $fechaInicio
                );
            } else {
                $resultadoCuotas = $this->calcularCuotasInterno(
                    $montoSolicitado,
                    $plazo,
                    $fechaInicio
                );
            }

            // Verificar que el cálculo fue exitoso
            if (!isset($resultadoCuotas['cuotas']) || empty($resultadoCuotas['cuotas'])) {
                Log::error("Error en cálculo de cuotas para préstamo {$prestamo->id}");
                DB::rollBack();
                return false;
            }

            // Actualizar cada cuota con los nuevos valores calculados
            // EXACTAMENTE como se hace en PrestamosController::store()
            $cuotasActualizadas = 0;
            foreach ($cuotas as $index => $cuota) {
                if (isset($resultadoCuotas['cuotas'][$index])) {
                    $cuotaCalculada = $resultadoCuotas['cuotas'][$index];

                    // Actualizar TODOS los campos como si estuviéramos creando el préstamo
                    // Igual que en: Cuota::create([...])
                    $cuota->update([
                        'monto' => $cuotaCalculada['cuota'],
                        'pago_capital' => $cuotaCalculada['pagoCapital'] ?? null,
                        'interes' => $cuotaCalculada['interes'] ?? null,
                        'comision' => $cuotaCalculada['comision'] ?? null,
                        'igv' => $cuotaCalculada['igv'] ?? null,
                        // NO tocar: estado, monto_pagado, cantidad_mora
                    ]);

                    $cuotasActualizadas++;
                }
            }

            DB::commit();

            Log::info("✅ Préstamo {$prestamo->id} recalculado exitosamente", [
                'cuotas_actualizadas' => $cuotasActualizadas,
                'monto_solicitado' => $montoSolicitado,
                'plazo' => $plazo,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Error recalculando préstamo {$prestamo->id}: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Calcula las cuotas para préstamos de 8 semanas
     * Copia del método en PrestamosController
     */
    private function calcularCuotas8Semanas($montoSolicitado, $fechaPrimerPago)
    {
        $tasaSemanal = 0.0138;
        $comision = 0.0424;

        $fechaInicio = Carbon::parse($fechaPrimerPago);
        $factorTotal = 1.3;
        $proporcionCuotas = 7 / 6;
        $totalPagado = $montoSolicitado * $factorTotal;
        $cuotaUltimasCuatro = $totalPagado / (4 * ($proporcionCuotas + 1));
        $cuotaPrimerasCuatro = $cuotaUltimasCuatro * $proporcionCuotas;
        $cuotaPrimerasCuatro = round($cuotaPrimerasCuatro, 2);
        $cuotaUltimasCuatro = round($cuotaUltimasCuatro, 2);
        $saldoCapital = $montoSolicitado;
        $cuotas = [];
        $totalPagoCapital = 0;
        $totalInteres = 0;
        $totalComision = 0;
        $totalIGV = 0;

        for ($i = 1; $i <= 8; $i++) {
            $cuotaActual = ($i <= 4) ? $cuotaPrimerasCuatro : $cuotaUltimasCuatro;
            $interesCuota = round($tasaSemanal * $saldoCapital, 2);
            $comisionBruta = round($saldoCapital * $comision, 2);
            $igvCuota = round($comisionBruta * (0.18 / 1.18), 2);
            $comisionNeta = round($comisionBruta - $igvCuota, 2);
            $pagoCapital = round($cuotaActual - $interesCuota - $comisionBruta, 2);

            if ($pagoCapital <= 0) {
                $pagoCapital = max(round($saldoCapital * 0.1, 2), 1);
                $cuotaActual = round($pagoCapital + $interesCuota + $comisionNeta + $igvCuota, 2);
            }

            if ($i == 8 && abs($saldoCapital - $pagoCapital) > 0.01) {
                $pagoCapital = $saldoCapital;
            }

            $saldoCapital = max(0, round($saldoCapital - $pagoCapital, 2));
            $fechaPago = $fechaInicio->copy()->addWeeks($i - 1)->format('Y-m-d');

            $cuotas[] = [
                'numero' => $i,
                'fecha_pago' => $fechaPago,
                'cuota' => $cuotaActual,
                'pagoCapital' => $pagoCapital,
                'interes' => $interesCuota,
                'comision' => $comisionNeta,
                'comision_bruta' => $comisionBruta,
                'igv' => $igvCuota,
                'saldoCapital' => $saldoCapital,
            ];

            $totalPagoCapital += $pagoCapital;
            $totalInteres += $interesCuota;
            $totalComision += $comisionNeta;
            $totalIGV += $igvCuota;
        }

        // Ajuste final para asegurar que el capital suma exactamente
        if (abs($totalPagoCapital - $montoSolicitado) > 0.01) {
            $diferenciaCapital = $montoSolicitado - $totalPagoCapital;
            $cuotasAjustables = 7;
            $ajustePorCuota = round($diferenciaCapital / $cuotasAjustables, 2);
            $ajusteAcumulado = 0;

            for ($i = 0; $i < $cuotasAjustables; $i++) {
                $ajusteActual = ($i == $cuotasAjustables - 1)
                    ? ($diferenciaCapital - $ajusteAcumulado)
                    : $ajustePorCuota;

                $cuotas[$i]['pagoCapital'] += $ajusteActual;
                $cuotas[$i]['cuota'] = round(
                    $cuotas[$i]['pagoCapital'] +
                    $cuotas[$i]['interes'] +
                    $cuotas[$i]['comision'] +
                    $cuotas[$i]['igv'],
                    2
                );

                $ajusteAcumulado += $ajusteActual;
            }

            $saldoCapital = $montoSolicitado;
            for ($i = 0; $i < count($cuotas); $i++) {
                $saldoCapital = max(0, round($saldoCapital - $cuotas[$i]['pagoCapital'], 2));
                $cuotas[$i]['saldoCapital'] = $saldoCapital;
            }

            $totalPagoCapital = $montoSolicitado;
        }

        $sumatoriaCuotas = array_sum(array_column($cuotas, 'cuota'));

        return [
            'cuotas' => $cuotas,
            'resumen' => [
                'totalPagoCapital' => round($totalPagoCapital, 2),
                'totalInteres' => round($totalInteres, 2),
                'totalComision' => round($totalComision, 2),
                'totalIGV' => round($totalIGV, 2),
                'totalPagado' => round($sumatoriaCuotas, 2),
            ],
        ];
    }

    /**
     * Calcula las cuotas para préstamos de 12, 15, 18 y 20 semanas
     * Copia del método en PrestamosController
     */
    private function calcularCuotasInterno($montoSolicitado, $plazo, Carbon $fechaInicio)
    {
        // Definir parámetros por plazo según tabla de referencia
        $parametros = [
            12 => [
                'tasa_interes' => 0.0144,    // 1.44% - Interés semanal
                'tasa_comision' => 0.0467,   // 4.67% - Comisión bruta (incluye IGV)
                'cuota_factor' => null,      // Se calcula con PMT
            ],
            15 => [
                'tasa_interes' => 0.0144,    // 1.44% - Interés semanal
                'tasa_comision' => 0.0411,   // 4.11% - Comisión bruta (incluye IGV)
                'cuota_factor' => 1.5,       // Factor fijo: Total a pagar = Monto × 1.5
            ],
            18 => [
                'tasa_interes' => 0.0144,    // 1.44% - Interés semanal
                'tasa_comision' => 0.0322,   // 3.22% - Comisión bruta (incluye IGV)
                'cuota_factor' => 1.5,       // Factor fijo: Total a pagar = Monto × 1.5
            ],
            20 => [
                'tasa_interes' => 0.0144,    // 1.44% - Interés semanal
                'tasa_comision' => 0.0277,   // 2.77% - Comisión bruta (incluye IGV)
                'cuota_factor' => 1.5,       // Factor fijo: Total a pagar = Monto × 1.5
            ],
        ];

        if (!array_key_exists($plazo, $parametros)) {
            throw new \Exception("Plazo {$plazo} no válido para recálculo.");
        }

        $params = $parametros[$plazo];
        $tasaInteres = $params['tasa_interes'];
        $tasaComision = $params['tasa_comision'];
        $cuotaFactor = $params['cuota_factor'];

        // Calcular la cuota fija con redondeo limpio
        if ($cuotaFactor !== null) {
            // Para plazos con factor fijo (ej: 15, 18, 20 semanas)
            $totalPagarExacto = $montoSolicitado * $cuotaFactor;
            $cuotaCalculada = $totalPagarExacto / $plazo;

            // Redondear a 2 decimales para que el total sea exacto
            $cuotaFija = round($cuotaCalculada, 2);
        } else {
            // Para plazos con PMT (12 semanas)
            $tasaTotalEfectiva = $tasaInteres + $tasaComision;
            $factor = $tasaTotalEfectiva * pow(1 + $tasaTotalEfectiva, $plazo) / (pow(1 + $tasaTotalEfectiva, $plazo) - 1);
            $cuotaCalculada = $montoSolicitado * $factor;
            $cuotaFija = $this->redondearCuotaLimpia($cuotaCalculada);
            $totalPagarExacto = $cuotaFija * $plazo;
        }

        // Generar tabla de amortización
        $saldoCapital = $montoSolicitado;
        $cuotas = [];
        $totalPagoCapital = 0;
        $totalInteres = 0;
        $totalComision = 0;
        $totalIGV = 0;

        for ($i = 1; $i <= $plazo; $i++) {
            // MÉTODO CORRECTO (sincronizado con PrestamosController):
            // - Interés = Saldo Capital × 1.44%
            //   * Primera cuota: Saldo Capital = Monto del préstamo
            //   * Siguientes: Saldo Capital = Saldo de la cuota anterior
            // - Comisión = (Saldo Capital × 4.67%) / 1.18
            //   * Primera cuota: Saldo Capital = Monto del préstamo
            //   * Siguientes: Saldo Capital = Saldo de la cuota anterior
            // - IGV = Comisión × 0.18
            // - Pago Capital = Cuota - Interés - Comisión - IGV
            // - Saldo Capital = Saldo anterior - Pago capital

            // Calcular INTERÉS sobre el SALDO CAPITAL:
            // Primera cuota: sobre el monto del préstamo
            // Siguientes: sobre el saldo capital de la cuota anterior
            if ($i == 1) {
                $interesCuota = round($montoSolicitado * $tasaInteres, 2);
            } else {
                $interesCuota = round($saldoCapital * $tasaInteres, 2);
            }

            // Calcular COMISIÓN (sin IGV):
            // Comisión = (Tasa Comisión × Saldo Capital) / 1.18
            // Primera cuota: sobre el monto del préstamo
            // Siguientes: sobre el saldo capital de la cuota anterior
            if ($i == 1) {
                $comisionCuota = round(($tasaComision * $montoSolicitado) / 1.18, 2);
            } else {
                $comisionCuota = round(($tasaComision * $saldoCapital) / 1.18, 2);
            }

            // Calcular IGV sobre la comisión base
            $igvCuota = round($comisionCuota * 0.18, 2);

            // Calcular PAGO DE CAPITAL = Cuota Fija - Interés - Comisión - IGV
            $pagoCapital = round($cuotaFija - $interesCuota - $comisionCuota - $igvCuota, 2);

            // Ajuste para la última cuota: el pago de capital debe ser exactamente el saldo restante
            if ($i == $plazo) {
                $pagoCapital = $saldoCapital;
            }

            // Actualizar el saldo capital para la próxima iteración
            $saldoCapital = round($saldoCapital - $pagoCapital, 2);

            // Asegurar que no sea negativo
            if ($saldoCapital < 0) {
                $saldoCapital = 0;
            }

            $fechaPago = $fechaInicio->copy()->addWeeks($i - 1)->format('Y-m-d');

            $cuotas[] = [
                'numero' => $i,
                'fecha_pago' => $fechaPago,
                'cuota' => $cuotaFija,
                'pagoCapital' => $pagoCapital,
                'interes' => $interesCuota,
                'comision' => $comisionCuota,
                'comision_bruta' => $comisionCuota + $igvCuota,
                'igv' => $igvCuota,
                'saldoCapital' => $saldoCapital,
            ];

            $totalPagoCapital += $pagoCapital;
            $totalInteres += $interesCuota;
            $totalComision += $comisionCuota;
            $totalIGV += $igvCuota;
        }

        // Suma total de todos los pagos (todas las cuotas son iguales)
        $totalPagar = $cuotaFija * $plazo;

        return [
            'cuotas' => $cuotas,
            'resumen' => [
                'totalPagoCapital' => round($totalPagoCapital, 2),
                'totalInteres' => round($totalInteres, 2),
                'totalComision' => round($totalComision, 2),
                'totalIGV' => round($totalIGV, 2),
                'totalPagado' => round($totalPagar, 2),
            ],
        ];
    }

    /**
     * Redondea la cuota a valores más limpios y presentables
     */
    private function redondearCuotaLimpia($cuota)
    {
        $cuotaRedondeada = round($cuota);
        $diferencia = abs($cuota - $cuotaRedondeada);

        if ($diferencia <= 0.50) {
            return (float) $cuotaRedondeada;
        }

        return round($cuota, 2);
    }
}

