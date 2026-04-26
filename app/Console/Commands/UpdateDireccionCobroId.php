<?php

namespace App\Console\Commands;

use App\Models\Prestamo;
use App\Models\Direccion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateDireccionCobroId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestamos:update-direccion-cobro 
                            {--dry-run : Ejecutar sin realizar cambios}
                            {--prestamo-id= : ID específico de préstamo a actualizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el campo direccion_cobro_id en la tabla prestamos con el ID de la dirección activa del cliente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $prestamoId = $this->option('prestamo-id');

        $this->info('==============================================');
        $this->info('Actualizando direccion_cobro_id en préstamos');
        $this->info('==============================================');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se realizarán cambios en la base de datos');
        }

        // Construir la consulta base
        $query = Prestamo::with(['cliente.persona.direcciones' => function ($query) {
            $query->where('estado', 1)->orderBy('id', 'asc');
        }]);

        // Filtrar por préstamo específico si se proporciona
        if ($prestamoId) {
            $query->where('id', $prestamoId);
            $this->info("Filtrando por préstamo ID: {$prestamoId}");
        } else {
            // Solo actualizar préstamos sin direccion_cobro_id
            $query->whereNull('direccion_cobro_id');
        }

        $prestamos = $query->get();

        $this->info("Total de préstamos a procesar: {$prestamos->count()}");
        $this->newLine();

        $actualizados = 0;
        $sinDireccion = 0;
        $errores = 0;

        $progressBar = $this->output->createProgressBar($prestamos->count());
        $progressBar->start();

        foreach ($prestamos as $prestamo) {
            try {
                // Verificar que el préstamo tenga cliente
                if (!$prestamo->cliente) {
                    $this->newLine();
                    $this->error("Préstamo ID {$prestamo->id}: No tiene cliente asociado");
                    $errores++;
                    $progressBar->advance();
                    continue;
                }

                // Verificar que el cliente tenga persona
                if (!$prestamo->cliente->persona) {
                    $this->newLine();
                    $this->error("Préstamo ID {$prestamo->id}: El cliente no tiene persona asociada");
                    $errores++;
                    $progressBar->advance();
                    continue;
                }

                // Obtener la primera dirección activa
                $direccion = $prestamo->cliente->persona->direcciones
                    ->where('estado', 1)
                    ->first();

                if (!$direccion) {
                    $this->newLine();
                    $this->warn("Préstamo ID {$prestamo->id}: El cliente no tiene direcciones activas");
                    $sinDireccion++;
                    $progressBar->advance();
                    continue;
                }

                // Actualizar el campo direccion_cobro_id
                if (!$dryRun) {
                    $prestamo->update([
                        'direccion_cobro_id' => $direccion->id
                    ]);
                }

                $actualizados++;

                if ($this->output->isVerbose()) {
                    $this->newLine();
                    $this->info("✓ Préstamo ID {$prestamo->id}: direccion_cobro_id = {$direccion->id} ({$direccion->direccion})");
                }

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error en préstamo ID {$prestamo->id}: {$e->getMessage()}");
                $errores++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Mostrar resumen
        $this->info('==============================================');
        $this->info('RESUMEN DE LA ACTUALIZACIÓN');
        $this->info('==============================================');
        $this->info("Total procesados: {$prestamos->count()}");
        $this->info("✓ Actualizados exitosamente: {$actualizados}");
        $this->warn("⚠ Sin dirección activa: {$sinDireccion}");
        $this->error("✗ Errores: {$errores}");

        if ($dryRun) {
            $this->newLine();
            $this->warn('MODO DRY-RUN: No se realizaron cambios. Ejecute sin --dry-run para aplicar los cambios.');
        }

        // Mostrar algunos ejemplos de préstamos actualizados
        if ($actualizados > 0 && !$dryRun) {
            $this->newLine();
            $this->info('Ejemplos de préstamos actualizados:');
            $this->newLine();

            $ejemplos = Prestamo::with(['cliente.persona', 'direccionCobro'])
                ->whereNotNull('direccion_cobro_id')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();

            $headers = ['Préstamo ID', 'Cliente', 'DNI', 'Dirección Cobro ID', 'Dirección'];
            $rows = [];

            foreach ($ejemplos as $ejemplo) {
                $rows[] = [
                    $ejemplo->id,
                    $ejemplo->cliente->persona->nombres . ' ' . $ejemplo->cliente->persona->ape_pat,
                    $ejemplo->cliente->persona->documento,
                    $ejemplo->direccion_cobro_id,
                    $ejemplo->direccionCobro ? substr($ejemplo->direccionCobro->direccion, 0, 40) . '...' : 'N/A'
                ];
            }

            $this->table($headers, $rows);
        }

        return Command::SUCCESS;
    }
}
