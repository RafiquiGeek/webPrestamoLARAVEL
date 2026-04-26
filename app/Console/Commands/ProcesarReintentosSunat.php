<?php

namespace App\Console\Commands;

use App\Jobs\ReintentarEnvioComprobante;
use App\Models\ComprobanteReintento;
use Illuminate\Console\Command;

class ProcesarReintentosSunat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sunat:procesar-reintentos
                            {--limit=10 : Número máximo de reintentos a procesar}
                            {--force : Forzar procesamiento ignorando tiempo de espera}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa la cola de reintentos de comprobantes SUNAT que fallaron por errores temporales';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando procesamiento de reintentos SUNAT...');

        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Obtener reintentos pendientes
        $query = ComprobanteReintento::with('comprobante')
            ->where('estado', 'pendiente')
            ->where('intentos', '<', \DB::raw('max_intentos'))
            ->orderBy('proximo_intento', 'asc');

        if (!$force) {
            $query->where('proximo_intento', '<=', now());
        }

        $reintentos = $query->limit($limit)->get();

        if ($reintentos->isEmpty()) {
            $this->info('✅ No hay reintentos pendientes para procesar.');
            return 0;
        }

        $this->info("📋 Encontrados {$reintentos->count()} reintentos pendientes");

        $procesados = 0;
        $despachados = 0;

        foreach ($reintentos as $reintento) {
            try {
                $comprobante = $reintento->comprobante;

                if (!$comprobante) {
                    $this->warn("⚠️  Comprobante no encontrado para reintento #{$reintento->id}");
                    $reintento->marcarCancelado('Comprobante no existe');
                    continue;
                }

                $this->line("📤 Procesando: {$comprobante->numero_completo} (Intento " . ($reintento->intentos + 1) . "/{$reintento->max_intentos})");

                // Despachar job a la cola
                ReintentarEnvioComprobante::dispatch($comprobante->id, $reintento->id);

                $despachados++;
                $procesados++;

            } catch (\Exception $e) {
                $this->error("❌ Error al procesar reintento #{$reintento->id}: " . $e->getMessage());
                \Log::error('Error en ProcesarReintentosSunat', [
                    'reintento_id' => $reintento->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("✅ Proceso completado:");
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Reintentos encontrados', $reintentos->count()],
                ['Jobs despachados', $despachados],
                ['Total procesados', $procesados],
            ]
        );

        $this->newLine();
        $this->comment('💡 Los jobs están en la cola. Ejecuta "php artisan queue:work" para procesarlos.');

        return 0;
    }
}
