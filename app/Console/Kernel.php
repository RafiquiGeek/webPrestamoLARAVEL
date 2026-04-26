<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     */
    protected $commands = [
        \App\Console\Commands\GenerarMoras::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // PROCESAMIENTO DIARIO COMPLETO - Ejecutar todos los días a las 00:01
        $schedule->command('sistema:procesamiento-diario')
            ->dailyAt('00:01')
            ->appendOutputTo(storage_path('logs/procesamiento-diario.log'))
            ->withoutOverlapping()
            ->runInBackground();

        // BACKUP: Comando específico de moras como respaldo (si falla el principal)
        // $schedule->command('moras:generar-diarias')->dailyAt('00:30');

        // ACTUALIZACIÓN DE ESTADOS cada 6 horas (para casos urgentes)
        $schedule->command('prestamos:actualizar-estados', ['--all' => true])
            ->everySixHours()
            ->appendOutputTo(storage_path('logs/estados-prestamos.log'))
            ->withoutOverlapping();

        // SISTEMA DE MONITOREO - Cada 5 minutos (comando existente)
        $schedule->command('monitoreo:recolectar')
            ->everyFiveMinutes()
            ->appendOutputTo(storage_path('logs/monitoreo.log'))
            ->withoutOverlapping();

        // PROCESAMIENTO DE REINTENTOS SUNAT - Cada 5 minutos
        $schedule->command('sunat:procesar-reintentos', ['--limit' => 20])
            ->everyFiveMinutes()
            ->appendOutputTo(storage_path('logs/sunat-reintentos.log'))
            ->withoutOverlapping()
            ->runInBackground();

        // CIERRE DE SESIONES ABANDONADAS - Cada hora
        $schedule->command('sessions:close-abandoned', ['--hours' => 2, '--force' => true])
            ->hourly()
            ->appendOutputTo(storage_path('logs/sessions-cleanup.log'))
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
