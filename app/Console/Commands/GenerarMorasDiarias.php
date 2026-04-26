<?php

namespace App\Console\Commands;

use App\Services\MoraService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarMorasDiarias extends Command
{
    protected $signature = 'moras:generar-diarias';

    protected $description = 'Genera moras diarias para las cuotas vencidas';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('🚀 Iniciando generación automática diaria de moras...');

        $inicioTiempo = Carbon::now();

        try {
            $moraService = new MoraService;
            $resultados = $moraService->generarMorasDiarias();

            $this->newLine();
            $this->info('✅ Proceso completado exitosamente:');
            $this->info("   📊 Cuotas procesadas: {$resultados['procesadas']}");
            $this->info("   🔥 Moras generadas: {$resultados['generadas']}");
            $this->info("   ⏭️  Cuotas omitidas: {$resultados['omitidas']}");

            if ($resultados['errores'] > 0) {
                $this->warn("   ⚠️  Errores encontrados: {$resultados['errores']}");
            }

            $tiempoTranscurrido = $inicioTiempo->diffForHumans(Carbon::now());
            $this->info("   ⏱️  Tiempo de ejecución: {$tiempoTranscurrido}");

            // Mostrar algunas estadísticas adicionales si hay moras generadas
            if ($resultados['generadas'] > 0) {
                $this->newLine();
                $this->info('📈 Estadísticas adicionales:');
                $estadisticas = $moraService->obtenerEstadisticasMoras();
                $this->info("   - Total moras pendientes en el sistema: {$estadisticas['total_moras_pendientes']}");
                $this->info("   - Préstamos morosos: {$estadisticas['prestamos_morosos']}");
                $this->info('   - Monto total moras pendientes: S/. '.number_format($estadisticas['monto_total_moras_pendientes'], 2));
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error en la generación de moras: '.$e->getMessage());
            $this->error('   Línea: '.$e->getLine());
            $this->error('   Archivo: '.$e->getFile());

            return self::FAILURE;
        }
    }
}
