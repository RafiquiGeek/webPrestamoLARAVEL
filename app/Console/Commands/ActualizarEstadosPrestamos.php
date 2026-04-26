<?php

namespace App\Console\Commands;

use App\Enums\CuotaEstado;
use App\Enums\MoraCuotaEstado;
use App\Models\Prestamo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActualizarEstadosPrestamos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestamos:actualizar-estados {--all : Actualizar todos los préstamos} {--prestamo= : ID específico del préstamo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los estados de los préstamos basándose en el estado de sus cuotas y fechas de vencimiento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando actualización de estados de préstamos...');

        if ($this->option('prestamo')) {
            // Actualizar un préstamo específico
            $prestamoId = $this->option('prestamo');
            $prestamo = Prestamo::find($prestamoId);

            if (! $prestamo) {
                $this->error("❌ Préstamo con ID {$prestamoId} no encontrado");

                return;
            }

            $this->actualizarEstadoPrestamo($prestamo);
            $this->info("✅ Estado del préstamo {$prestamoId} actualizado");

        } elseif ($this->option('all') || $this->confirm('¿Actualizar estados de todos los préstamos?')) {
            // Actualizar todos los préstamos
            $prestamos = Prestamo::whereNotIn('estado', ['Finalizado'])->get();

            $bar = $this->output->createProgressBar($prestamos->count());
            $bar->start();

            $actualizados = 0;

            foreach ($prestamos as $prestamo) {
                $estadoAnterior = $prestamo->estado;
                $this->actualizarEstadoPrestamo($prestamo);

                if ($prestamo->estado !== $estadoAnterior) {
                    $actualizados++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ Proceso completado: {$actualizados} préstamos actualizados de {$prestamos->count()} procesados");
        } else {
            $this->info('Operación cancelada por el usuario');
        }
    }

    /**
     * Actualizar el estado del préstamo usando el controlador centralizado
     */
    private function actualizarEstadoPrestamo(Prestamo $prestamo)
    {
        // Usar el controlador centralizado para evitar duplicación de lógica
        $estadoController = new \App\Http\Controllers\Admin\EstadoPrestamoController();
        
        $resultado = $estadoController->calcularYActualizarEstado(
            $prestamo,
            true, // Sí actualizar en BD
            'comando_artisan' // Origen: comando de consola
        );

        // Log solo si hubo cambio
        if ($resultado['fue_actualizado']) {
            Log::info("Estado del préstamo {$prestamo->id} actualizado por comando artisan", [
                'estado_anterior' => $resultado['estado_anterior'],
                'estado_nuevo' => $resultado['estado_calculado'],
                'razon' => $resultado['razon'],
            ]);
        }
    }
}
