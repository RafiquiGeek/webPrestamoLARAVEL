<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillClienteUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clientes:backfill-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill cliente.user_id using persona->user relation when available';

    public function handle()
    {
        $this->info('Iniciando backfill de cliente.user_id...');

        $clientes = Cliente::whereNull('user_id')->with('persona.user')->get();
        $total = $clientes->count();
        $updated = 0;
        $skipped = 0;

        foreach ($clientes as $cliente) {
            $this->line("Procesando cliente {$cliente->id} (persona_id: {$cliente->persona_id})...");

            if ($cliente->persona && $cliente->persona->user) {
                $userId = $cliente->persona->user->id;
                $cliente->user_id = $userId;
                $cliente->save();
                $this->info(" -> asignado user_id={$userId}");
                $updated++;
            } else {
                $this->comment(' -> no se encontró usuario relacionado en persona, se salta');
                $skipped++;
            }
        }

        $this->info("Backfill completado: procesados={$total}, actualizados={$updated}, omitidos={$skipped}");
        Log::info('clientes:backfill-user', ['processed' => $total, 'updated' => $updated, 'skipped' => $skipped]);

        return 0;
    }
}
