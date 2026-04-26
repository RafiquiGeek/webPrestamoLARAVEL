<?php

namespace App\Console\Commands;

use App\Models\AccessCode;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanExpiredAccessCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'access-codes:cleanup {--dry-run : Show what would be cleaned without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired and exhausted access codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando limpieza de códigos de acceso...');

        // Códigos expirados por fecha
        $expiredByDate = AccessCode::where('expires_at', '<', Carbon::now())
            ->where('is_active', true)
            ->get();

        // Códigos agotados por uso máximo
        $exhaustedByUsage = AccessCode::whereNotNull('max_usage')
            ->whereRaw('usage_count >= max_usage')
            ->where('is_active', true)
            ->get();

        $totalExpired = $expiredByDate->count();
        $totalExhausted = $exhaustedByUsage->count();
        $totalToClean = $totalExpired + $totalExhausted;

        if ($totalToClean === 0) {
            $this->info('✅ No se encontraron códigos de acceso para limpiar.');

            return 0;
        }

        $this->warn('📊 Resumen de códigos a limpiar:');
        $this->line("   • Expirados por fecha: {$totalExpired}");
        $this->line("   • Agotados por uso: {$totalExhausted}");
        $this->line("   • Total a desactivar: {$totalToClean}");

        if ($this->option('dry-run')) {
            $this->comment('🔍 Modo dry-run activado. Mostrando códigos que serían desactivados:');

            if ($totalExpired > 0) {
                $this->line("\n📅 Códigos expirados por fecha:");
                foreach ($expiredByDate as $code) {
                    $this->line("   • {$code->code} - Expiró: {$code->expires_at->format('d/m/Y H:i')}");
                }
            }

            if ($totalExhausted > 0) {
                $this->line("\n🔢 Códigos agotados por uso:");
                foreach ($exhaustedByUsage as $code) {
                    $this->line("   • {$code->code} - Usos: {$code->usage_count}/{$code->max_usage}");
                }
            }

            $this->info("\n💡 Para ejecutar la limpieza real, ejecuta el comando sin --dry-run");

            return 0;
        }

        if (! $this->confirm('¿Estás seguro de que quieres desactivar estos códigos?', true)) {
            $this->comment('❌ Operación cancelada.');

            return 1;
        }

        $deactivatedCount = 0;

        // Desactivar códigos expirados
        if ($totalExpired > 0) {
            $this->info("\n📅 Desactivando códigos expirados...");
            foreach ($expiredByDate as $code) {
                $code->update(['is_active' => false]);
                $this->line("   ✓ {$code->code} desactivado (expirado)");
                $deactivatedCount++;
            }
        }

        // Desactivar códigos agotados
        if ($totalExhausted > 0) {
            $this->info("\n🔢 Desactivando códigos agotados...");
            foreach ($exhaustedByUsage as $code) {
                $code->update(['is_active' => false]);
                $this->line("   ✓ {$code->code} desactivado (agotado)");
                $deactivatedCount++;
            }
        }

        $this->info("\n✅ Limpieza completada.");
        $this->line("   📊 Total de códigos desactivados: {$deactivatedCount}");

        return 0;
    }
}
