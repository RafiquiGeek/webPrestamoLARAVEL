<?php

namespace App\Console\Commands;

use App\Models\ModuleTimeTracking;
use App\Models\UserSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseAbandonedSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:close-abandoned
                            {--hours=2 : Número de horas de inactividad para considerar una sesión abandonada}
                            {--force : Forzar cierre sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra automáticamente sesiones abandonadas (sin logout explícito) después de un período de inactividad';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $force = $this->option('force');

        $this->info("🔍 Buscando sesiones abandonadas (inactivas por más de {$hours} horas)...");

        // Buscar sesiones activas (sin logout_time) que no tengan actividad reciente
        $abandonedSessions = UserSession::whereNull('logout_time')
            ->where('login_time', '<', now()->subHours($hours))
            ->get();

        if ($abandonedSessions->isEmpty()) {
            $this->info('✅ No se encontraron sesiones abandonadas.');
            return 0;
        }

        $this->warn("⚠️  Se encontraron {$abandonedSessions->count()} sesiones abandonadas:");

        // Mostrar tabla de sesiones a cerrar
        $tableData = $abandonedSessions->map(function ($session) {
            return [
                'ID' => $session->id,
                'Usuario' => $session->user->name ?? 'N/A',
                'Login' => $session->login_time->format('d/m/Y H:i:s'),
                'Horas inactiva' => $session->login_time->diffInHours(now()),
                'IP' => $session->ip_address,
            ];
        })->toArray();

        $this->table(['ID', 'Usuario', 'Login', 'Horas inactiva', 'IP'], $tableData);

        if (!$force && !$this->confirm('¿Deseas cerrar estas sesiones?', true)) {
            $this->info('❌ Operación cancelada.');
            return 0;
        }

        $this->info('🔄 Cerrando sesiones abandonadas...');

        $closedCount = 0;
        $bar = $this->output->createProgressBar($abandonedSessions->count());
        $bar->start();

        DB::beginTransaction();
        try {
            foreach ($abandonedSessions as $session) {
                // Buscar el último tracking de módulo para determinar el último momento de actividad
                $lastTracking = ModuleTimeTracking::where('user_session_id', $session->id)
                    ->whereNotNull('end_time')
                    ->orderBy('end_time', 'desc')
                    ->first();

                // Determinar logout_time: usar el end_time del último tracking o login_time + 1 hora
                $logoutTime = $lastTracking && $lastTracking->end_time
                    ? $lastTracking->end_time
                    : $session->login_time->addHour();

                $totalDuration = $logoutTime->diffInSeconds($session->login_time);

                // Actualizar sesión
                $session->update([
                    'logout_time' => $logoutTime,
                    'total_duration' => $totalDuration,
                    'forced_logout' => true, // Marcar como cierre forzado
                ]);

                // Cerrar trackings de módulos activos
                $this->closeActiveModuleTracking($session->id);

                $closedCount++;
                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);
            $this->info("✅ Se cerraron exitosamente {$closedCount} sesiones abandonadas.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error al cerrar sesiones: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Cierra trackings de módulos activos para una sesión
     */
    private function closeActiveModuleTracking(int $userSessionId): void
    {
        $activeTracking = ModuleTimeTracking::where('user_session_id', $userSessionId)
            ->whereNull('end_time')
            ->get();

        foreach ($activeTracking as $tracking) {
            $endTime = now();
            $duration = $endTime->diffInSeconds($tracking->start_time);

            // Limitar duración máxima a 2 horas para trackings abandonados
            $duration = min($duration, 7200);

            $tracking->update([
                'end_time' => $tracking->start_time->addSeconds($duration),
                'duration' => $duration,
            ]);
        }
    }
}
