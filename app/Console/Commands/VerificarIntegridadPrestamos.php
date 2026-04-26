<?php

namespace App\Console\Commands;

use App\Models\Prestamo;
use App\Services\EstadoPrestamoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarIntegridadPrestamos extends Command
{
    protected $signature = 'sistema:verificar-integridad 
                            {--prestamo= : ID específico del préstamo a verificar}
                            {--reparar : Reparar automáticamente los problemas encontrados}
                            {--detallado : Mostrar información detallada}
                            {--limite=100 : Límite de préstamos a procesar}';

    protected $description = 'Verifica la integridad de estados y montos en préstamos, cuotas y moras';

    protected EstadoPrestamoService $estadoService;

    public function __construct(EstadoPrestamoService $estadoService)
    {
        parent::__construct();
        $this->estadoService = $estadoService;
    }

    public function handle()
    {
        $this->info('🔍 Iniciando verificación de integridad de préstamos...');

        $prestamoId = $this->option('prestamo');
        $reparar = $this->option('reparar');
        $detallado = $this->option('detallado');
        $limite = (int) $this->option('limite');

        $estadisticas = [
            'total_procesados' => 0,
            'con_errores' => 0,
            'con_advertencias' => 0,
            'reparados' => 0,
            'errores_criticos' => 0,
        ];

        try {
            if ($prestamoId) {
                // Verificar préstamo específico
                $prestamo = Prestamo::findOrFail($prestamoId);
                $this->verificarPrestamo($prestamo, $reparar, $detallado, $estadisticas);
            } else {
                // Verificar todos los préstamos activos
                $query = Prestamo::whereNotIn('estado', ['Finalizado'])
                    ->orderBy('id');

                if ($limite > 0) {
                    $query->limit($limite);
                }

                $prestamos = $query->get();

                $this->info("📊 Procesando {$prestamos->count()} préstamos...");
                $bar = $this->output->createProgressBar($prestamos->count());
                $bar->start();

                foreach ($prestamos as $prestamo) {
                    $this->verificarPrestamo($prestamo, $reparar, $detallado, $estadisticas);
                    $bar->advance();
                }

                $bar->finish();
                $this->newLine();
            }

            $this->mostrarResumen($estadisticas);

        } catch (\Exception $e) {
            $this->error('Error durante la verificación: '.$e->getMessage());
            Log::error('Error en verificación de integridad: '.$e->getMessage());

            return 1;
        }

        return 0;
    }

    private function verificarPrestamo(Prestamo $prestamo, bool $reparar, bool $detallado, array &$estadisticas): void
    {
        $estadisticas['total_procesados']++;

        try {
            // Verificar integridad
            $resultado = $this->estadoService->validarIntegridad($prestamo);

            if (! $resultado['valido']) {
                $estadisticas['con_errores']++;

                if ($resultado['total_errores'] > 5) {
                    $estadisticas['errores_criticos']++;
                }

                if ($detallado) {
                    $this->mostrarDetallesPrestamo($prestamo, $resultado);
                }

                // Reparar si se solicitó
                if ($reparar) {
                    $this->repararPrestamo($prestamo, $resultado, $estadisticas);
                }
            } elseif ($resultado['total_advertencias'] > 0) {
                $estadisticas['con_advertencias']++;

                if ($detallado) {
                    $this->mostrarAdvertencias($prestamo, $resultado);
                }

                if ($reparar) {
                    $this->repararPrestamo($prestamo, $resultado, $estadisticas);
                }
            }

        } catch (\Exception $e) {
            $this->error("Error verificando préstamo {$prestamo->id}: ".$e->getMessage());
            Log::error("Error verificando préstamo {$prestamo->id}: ".$e->getMessage());
        }
    }

    private function repararPrestamo(Prestamo $prestamo, array $resultado, array &$estadisticas): void
    {
        try {
            $this->warn("🔧 Reparando préstamo {$prestamo->id}...");

            $resultadoRecalculo = $this->estadoService->recalcularTodo($prestamo);

            if ($resultadoRecalculo['cuotas_actualizadas'] > 0 ||
                $resultadoRecalculo['estado_anterior'] !== $resultadoRecalculo['estado_nuevo']) {

                $estadisticas['reparados']++;

                $this->info("✅ Préstamo {$prestamo->id} reparado exitosamente:");
                $this->line("   • Cuotas actualizadas: {$resultadoRecalculo['cuotas_actualizadas']}");
                $this->line("   • Estado: {$resultadoRecalculo['estado_anterior']} → {$resultadoRecalculo['estado_nuevo']}");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error reparando préstamo {$prestamo->id}: ".$e->getMessage());
            Log::error("Error reparando préstamo {$prestamo->id}: ".$e->getMessage());
        }
    }

    private function mostrarDetallesPrestamo(Prestamo $prestamo, array $resultado): void
    {
        $clienteNombre = ($prestamo->cliente && $prestamo->cliente->persona) ? $prestamo->cliente->persona->nombres : 'N/A';
        $this->error("❌ Préstamo {$prestamo->id} - Cliente: {$clienteNombre}");

        foreach ($resultado['errores'] as $error) {
            $this->line("   🔴 $error");
        }

        foreach ($resultado['advertencias'] as $advertencia) {
            $this->line("   🟡 $advertencia");
        }

        $this->newLine();
    }

    private function mostrarAdvertencias(Prestamo $prestamo, array $resultado): void
    {
        $this->warn("⚠️  Préstamo {$prestamo->id} - Advertencias:");

        foreach ($resultado['advertencias'] as $advertencia) {
            $this->line("   🟡 $advertencia");
        }
    }

    private function mostrarResumen(array $estadisticas): void
    {
        $this->newLine();
        $this->info('📋 RESUMEN DE VERIFICACIÓN');
        $this->line('════════════════════════');

        $this->line("Total procesados: {$estadisticas['total_procesados']}");
        $this->line('Con errores: '.($estadisticas['con_errores'] > 0 ?
            "<fg=red>{$estadisticas['con_errores']}</>" :
            "<fg=green>{$estadisticas['con_errores']}</>"));

        $this->line('Con advertencias: '.($estadisticas['con_advertencias'] > 0 ?
            "<fg=yellow>{$estadisticas['con_advertencias']}</>" :
            "<fg=green>{$estadisticas['con_advertencias']}</>"));

        $this->line('Errores críticos: '.($estadisticas['errores_criticos'] > 0 ?
            "<fg=red>{$estadisticas['errores_criticos']}</>" :
            "<fg=green>{$estadisticas['errores_criticos']}</>"));

        if ($this->option('reparar')) {
            $this->line("Reparados: <fg=green>{$estadisticas['reparados']}</>");
        }

        $this->newLine();

        if ($estadisticas['con_errores'] > 0) {
            $this->warn('💡 Ejecute con --reparar para corregir automáticamente los problemas');
        }

        if ($estadisticas['errores_criticos'] > 0) {
            $this->error('🚨 Hay préstamos con errores críticos que requieren atención manual');
        }

        if ($estadisticas['con_errores'] == 0 && $estadisticas['con_advertencias'] == 0) {
            $this->info('✨ ¡Todos los préstamos están en estado consistente!');
        }
    }
}
