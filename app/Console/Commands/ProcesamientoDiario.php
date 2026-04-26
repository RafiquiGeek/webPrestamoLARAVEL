<?php

namespace App\Console\Commands;

use App\Enums\CuotaEstado;
use App\Models\Cuota;
use App\Models\Prestamo;
use App\Services\MoraService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcesamientoDiario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sistema:procesamiento-diario {--dry-run : Ejecutar en modo prueba sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesamiento automático diario: moras, cuotas vencidas y estados de préstamos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $fechaEjecucion = Carbon::now();

        $this->info('🚀 Iniciando procesamiento diario del sistema financiero');
        $this->info("📅 Fecha de ejecución: {$fechaEjecucion->format('d/m/Y H:i:s')}");

        if ($isDryRun) {
            $this->warn('⚠️  MODO PRUEBA - No se realizarán cambios en la base de datos');
        }

        $resultadosGenerales = [
            'inicio' => $fechaEjecucion->format('Y-m-d H:i:s'),
            'moras' => [],
            'cuotas_vencidas' => [],
            'estados_prestamos' => [],
            'errores' => [],
        ];

        try {
            // 1. GENERAR MORAS DIARIAS
            $this->newLine();
            $this->info('📊 PASO 1: Generando moras diarias...');
            $resultadosMoras = $this->generarMorasDiarias($isDryRun);
            $resultadosGenerales['moras'] = $resultadosMoras;

            // 2. ACTUALIZAR ESTADOS DE CUOTAS VENCIDAS
            $this->newLine();
            $this->info('⏰ PASO 2: Actualizando estados de cuotas vencidas...');
            $resultadosCuotas = $this->actualizarCuotasVencidas($isDryRun);
            $resultadosGenerales['cuotas_vencidas'] = $resultadosCuotas;

            // 3. ACTUALIZAR ESTADOS DE PRÉSTAMOS
            $this->newLine();
            $this->info('🔄 PASO 3: Actualizando estados de préstamos...');
            $resultadosEstados = $this->actualizarEstadosPrestamos($isDryRun);
            $resultadosGenerales['estados_prestamos'] = $resultadosEstados;

            // 4. RESUMEN FINAL
            $this->mostrarResumenFinal($resultadosGenerales);

            // 5. LOGGING
            $this->registrarEjecucion($resultadosGenerales, $isDryRun);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error crítico en procesamiento diario: '.$e->getMessage());
            Log::error('Error en procesamiento diario', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $resultadosGenerales['errores'][] = [
                'tipo' => 'critico',
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ];

            $this->registrarEjecucion($resultadosGenerales, $isDryRun, 'error');

            return self::FAILURE;
        }
    }

    /**
     * Generar moras diarias usando el servicio existente
     */
    private function generarMorasDiarias(bool $isDryRun): array
    {
        try {
            if ($isDryRun) {
                $this->info('   🔍 Simulando generación de moras...');

                // En modo prueba, solo contar cuotas que tendrían moras
                $cuotasVencidas = Cuota::whereIn('estado', [CuotaEstado::PENDIENTE->value, CuotaEstado::PARCIAL->value])
                    ->where('fecha_pago', '<', Carbon::today())
                    ->count();

                return [
                    'modo' => 'simulacion',
                    'cuotas_candidatas' => $cuotasVencidas,
                    'procesadas' => 0,
                    'generadas' => 0,
                    'omitidas' => 0,
                    'errores' => 0,
                ];
            }

            $moraService = new MoraService;
            $resultados = $moraService->generarMorasDiarias();

            $this->info("   ✅ Moras procesadas: {$resultados['procesadas']}");
            $this->info("   🔥 Moras generadas: {$resultados['generadas']}");

            if ($resultados['errores'] > 0) {
                $this->warn("   ⚠️  Errores encontrados: {$resultados['errores']}");
            }

            return $resultados;

        } catch (\Exception $e) {
            $this->error('   ❌ Error en generación de moras: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar estados de cuotas vencidas a VENCIDO
     */
    private function actualizarCuotasVencidas(bool $isDryRun): array
    {
        try {
            $hoy = Carbon::today();

            // Obtener cuotas que deberían estar vencidas pero aún no están marcadas como tal
            $cuotasParaVencer = Cuota::whereIn('estado', [CuotaEstado::PENDIENTE->value, CuotaEstado::PARCIAL->value])
                ->where('fecha_pago', '<', $hoy)
                ->get();

            $this->info("   📋 Cuotas candidatas para marcar como vencidas: {$cuotasParaVencer->count()}");

            if ($isDryRun) {
                return [
                    'modo' => 'simulacion',
                    'candidatas' => $cuotasParaVencer->count(),
                    'actualizadas' => 0,
                ];
            }

            $actualizadas = 0;
            $errores = 0;

            foreach ($cuotasParaVencer as $cuota) {
                try {
                    // Solo actualizar si no está ya como VENCIDO
                    if ($cuota->estado !== CuotaEstado::VENCIDO) {
                        $cuota->update(['estado' => CuotaEstado::VENCIDO->value]);
                        $actualizadas++;

                        Log::info("Cuota {$cuota->id} marcada como vencida (fecha: {$cuota->fecha_pago})");
                    }
                } catch (\Exception $e) {
                    $errores++;
                    Log::error("Error actualizando cuota {$cuota->id}: ".$e->getMessage());
                }
            }

            $this->info("   ✅ Cuotas actualizadas: {$actualizadas}");

            if ($errores > 0) {
                $this->warn("   ⚠️  Errores: {$errores}");
            }

            return [
                'candidatas' => $cuotasParaVencer->count(),
                'actualizadas' => $actualizadas,
                'errores' => $errores,
            ];

        } catch (\Exception $e) {
            $this->error('   ❌ Error actualizando cuotas vencidas: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualizar estados de préstamos basándose en cuotas
     */
    private function actualizarEstadosPrestamos(bool $isDryRun): array
    {
        try {
            if ($isDryRun) {
                $prestamosActivos = Prestamo::whereNotIn('estado', ['Finalizado'])->count();

                return [
                    'modo' => 'simulacion',
                    'candidatos' => $prestamosActivos,
                    'actualizados' => 0,
                ];
            }

            // Usar el comando existente
            $this->call('prestamos:actualizar-estados', ['--all' => true]);

            // Obtener estadísticas post-actualización
            $estadisticas = [
                'vigentes' => Prestamo::where('estado', 'Vigente')->count(),
                'morosos' => Prestamo::where('estado', 'Moroso')->count(),
                'finalizados' => Prestamo::where('estado', 'Finalizado')->count(),
                'otros' => Prestamo::whereNotIn('estado', ['Vigente', 'Moroso', 'Finalizado'])->count(),
            ];

            $this->info('   📊 Estados actuales:');
            $this->info("      - Vigentes: {$estadisticas['vigentes']}");
            $this->info("      - Morosos: {$estadisticas['morosos']}");
            $this->info("      - Finalizados: {$estadisticas['finalizados']}");
            $this->info("      - Otros: {$estadisticas['otros']}");

            return $estadisticas;

        } catch (\Exception $e) {
            $this->error('   ❌ Error actualizando estados de préstamos: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Mostrar resumen final del procesamiento
     */
    private function mostrarResumenFinal(array $resultados): void
    {
        $this->newLine();
        $this->info('📈 RESUMEN FINAL DEL PROCESAMIENTO DIARIO');
        $this->info('================================================');

        // Moras
        if (isset($resultados['moras']['generadas'])) {
            $this->info("🔥 Moras generadas: {$resultados['moras']['generadas']}");
        }

        // Cuotas vencidas
        if (isset($resultados['cuotas_vencidas']['actualizadas'])) {
            $this->info("⏰ Cuotas marcadas como vencidas: {$resultados['cuotas_vencidas']['actualizadas']}");
        }

        // Estados de préstamos
        if (isset($resultados['estados_prestamos']['morosos'])) {
            $this->info("💰 Préstamos morosos actuales: {$resultados['estados_prestamos']['morosos']}");
        }

        // Errores
        $totalErrores = count($resultados['errores']);
        if ($totalErrores > 0) {
            $this->warn("⚠️  Total de errores: {$totalErrores}");
        } else {
            $this->info('✅ Procesamiento completado sin errores');
        }

        $tiempoEjecucion = Carbon::parse($resultados['inicio'])->diffForHumans();
        $this->info("⏱️  Tiempo de ejecución: {$tiempoEjecucion}");
    }

    /**
     * Registrar la ejecución en logs
     */
    private function registrarEjecucion(array $resultados, bool $isDryRun, string $estado = 'exitoso'): void
    {
        Log::info("Procesamiento diario {$estado}", [
            'fecha' => $resultados['inicio'],
            'modo' => $isDryRun ? 'prueba' : 'produccion',
            'resultados' => $resultados,
            'estado' => $estado,
        ]);
    }
}
