<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CleanAllowedIps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:clean-allowed-ips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia las IPs permitidas de los usuarios eliminando espacios en blanco y valores vacíos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando limpieza de IPs permitidas...');

        $users = User::whereNotNull('allowed_ips')->get();
        $updatedCount = 0;

        foreach ($users as $user) {
            if (!empty($user->allowed_ips) && is_array($user->allowed_ips)) {
                $originalIps = $user->allowed_ips;

                // Limpiar IPs: eliminar espacios y valores vacíos
                $cleanedIps = array_map('trim', $originalIps);
                $cleanedIps = array_filter($cleanedIps);
                $cleanedIps = array_values($cleanedIps); // Reindexar

                // Solo actualizar si hay diferencias
                if ($originalIps !== $cleanedIps) {
                    $user->allowed_ips = $cleanedIps;
                    $user->save();
                    $updatedCount++;

                    $this->line("Usuario ID {$user->id}: " . count($originalIps) . " IPs → " . count($cleanedIps) . " IPs");
                }
            }
        }

        if ($updatedCount > 0) {
            $this->info("✓ Se actualizaron {$updatedCount} usuarios");
        } else {
            $this->info("✓ No se encontraron usuarios que requieran actualización");
        }

        return Command::SUCCESS;
    }
}
