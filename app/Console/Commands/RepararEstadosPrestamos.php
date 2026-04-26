<?php

namespace App\Console\Commands;

use App\Models\MoraCuota;
use App\Models\Prestamo;
use App\Services\EstadoPrestamoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepararEstadosPrestamos extends Command
{
    protected $signature = 'sistema:reparar-estados
                            {--prestamo= : ID específico del préstamo a reparar}
                            {--forzar : Forzar reparación sin confirmación}
                            {--dry-run : Mostrar qué se haría sin aplicar cambios}
                            {--limite=50 : Límite de préstamos a procesar}';

    protected $description = 'Repara inconsistencias en estados y montos de préstamos, cuotas y moras';

    protected EstadoPrestamoService $estadoService;

    public function __construct(EstadoPrestamoService $estadoService)
    {
        parent::__construct();
        $this->estadoService = $estadoService;
    }

    public function handle()
    {
        $this->info('🔧 Iniciando reparación de estados de préstamos...');

        $prestamoId = $this->option('prestamo');
        $forzar = $this->option('forzar');
        $dryRun = $this->option('dry-run');
        $limite = (int) $this->option('limite');

        if ($dryRun) {
            $this->warn('🔍 MODO DRY-RUN: No se aplicarán cambios reales');
        }

        $estadisticas = [
            'total_procesados' => 0,
            'prestamos_reparados' => 0,
            'cuotas_actualizadas' => 0,
            'moras_actualizadas' => 0,
            'errores' => 0,
        ];

        try {
            if ($prestamoId) {
                $prestamo = Prestamo::findOrFail($prestamoId);
                $this->repararPrestamo($prestamo, $dryRun, $estadisticas);
            } else {
                if (! $forzar && ! $this->confirm('¿Está seguro de reparar todos los préstamos? Esto puede tomar tiempo.')) {
                    return 0;
                }

                $this->repararTodosPrestamos($limite, $dryRun, $estadisticas);
            }

            $this->mostrarResumenReparacion($estadisticas, $dryRun);

        } catch (\Exception $e) {
            $this->error('Error durante la reparación: '.$e->getMessage());
            Log::error('Error en reparación de estados: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    private function repararTodosPrestamos(int $limite, bool $dryRun, array &$estadisticas): void
    {
        // Obtener préstamos que potencialmente tienen problemas
        $prestamos = Prestamo::whereNotIn('estado', ['Finalizado'])
            ->whereHas('cuotas')
            ->limit($limite)
            ->get();

        $this->info("📊 Procesando {$prestamos->count()} préstamos...");

        $bar = $this->output->createProgressBar($prestamos->count());
        $bar->start();

        foreach ($prestamos as $prestamo) {
            try {
                $this->repararPrestamo($prestamo, $dryRun, $estadisticas);
                $bar->advance();
            } catch (\Exception $e) {
                $estadisticas['errores']++;
                $this->error("Error procesando préstamo {$prestamo->id}: ".$e->getMessage());
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
    }

    private function repararPrestamo(Prestamo $prestamo, bool $dryRun, array &$estadisticas): void
    {
        $estadisticas['total_procesados']++;

        if ($dryRun) {
            // En modo dry-run, solo validar y reportar
            $integridad = $this->estadoService->validarIntegridad($prestamo);

            if (! $integridad['valido'] || $integridad['total_advertencias'] > 0) {
                $this->warn("🔍 Préstamo {$prestamo->id} requiere reparación:");

                foreach ($integridad['errores'] as $error) {
                    $this->line("   🔴 $error");
                }

                foreach ($integridad['advertencias'] as $advertencia) {
                    $this->line("   🟡 $advertencia");
                }

                $estadisticas['prestamos_reparados']++; // Solo para estadísticas
            }

            return;
        }

        // Reparación real
        DB::transaction(function () use ($prestamo, &$estadisticas) {
            // 1. Primero reparar campos monto_pagado en moras
            $this->repararCamposMontoPagado($prestamo, $estadisticas);

            // 2. Luego recalcular todo usando el servicio
            $resultado = $this->estadoService->recalcularTodo($prestamo);

            if ($resultado['cuotas_actualizadas'] > 0 ||
                $resultado['estado_anterior'] !== $resultado['estado_nuevo']) {

                $estadisticas['prestamos_reparados']++;
                $estadisticas['cuotas_actualizadas'] += $resultado['cuotas_actualizadas'];

                Log::info("Préstamo {$prestamo->id} reparado", $resultado);
            }
        });
    }

    private function repararCamposMontoPagado(Prestamo $prestamo, array &$estadisticas): void
    {
        // Reparar monto_pagado en cuotas
        foreach ($prestamo->cuotas as $cuota) {
            $montoCalculado = $cuota->operaciones()
                ->where('operaciones.estado', '!=', 'anulado')
                ->sum('operaciones.abono');

            if (abs($cuota->monto_pagado - $montoCalculado) > 0.01) {
                $cuota->update(['monto_pagado' => $montoCalculado]);
                Log::info("Cuota {$cuota->id} monto_pagado actualizado: {$cuota->monto_pagado} → {$montoCalculado}");
            }
        }

        // Reparar monto_pagado en moras
        $moras = MoraCuota::whereHas('cuota', function ($q) use ($prestamo) {
            $q->where('prestamo_id', $prestamo->id);
        })->get();

        foreach ($moras as $mora) {
            $montoCalculado = $mora->operaciones()
                ->where('operaciones.estado', '!=', 'anulado')
                ->sum('operaciones.abono');

            $montoActual = $mora->monto_pagado !== null ? $mora->monto_pagado : 0;

            if (abs($montoActual - $montoCalculado) > 0.01) {
                $mora->update(['monto_pagado' => $montoCalculado]);
                $estadisticas['moras_actualizadas']++;
                Log::info("Mora {$mora->id} monto_pagado actualizado: {$montoActual} → {$montoCalculado}");
            }
        }
    }

    private function mostrarResumenReparacion(array $estadisticas, bool $dryRun): void
    {
        $this->newLine();
        $this->info($dryRun ? '📋 RESUMEN DRY-RUN' : '📋 RESUMEN DE REPARACIÓN');
        $this->line('═══════════════════════════');

        $this->line("Total procesados: {$estadisticas['total_procesados']}");

        if ($dryRun) {
            $this->line("Préstamos que requieren reparación: <fg=yellow>{$estadisticas['prestamos_reparados']}</>");
            $this->newLine();
            $this->info('💡 Ejecute sin --dry-run para aplicar las reparaciones');
        } else {
            $this->line("Préstamos reparados: <fg=green>{$estadisticas['prestamos_reparados']}</>");
            $this->line("Cuotas actualizadas: <fg=green>{$estadisticas['cuotas_actualizadas']}</>");
            $this->line("Moras actualizadas: <fg=green>{$estadisticas['moras_actualizadas']}</>");
        }

        if ($estadisticas['errores'] > 0) {
            $this->line("Errores: <fg=red>{$estadisticas['errores']}</>");
        }

        $this->newLine();

        if (! $dryRun && $estadisticas['prestamos_reparados'] > 0) {
            $this->info('✨ Reparación completada exitosamente');
            $this->warn('💡 Se recomienda ejecutar sistema:verificar-integridad para confirmar');
        }
    }
}
