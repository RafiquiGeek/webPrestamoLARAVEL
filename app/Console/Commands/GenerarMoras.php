<?php

namespace App\Console\Commands;

use App\Models\Cuota;
use App\Models\MoraCuota;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarMoras extends Command
{
    protected $signature = 'moras:generar';

    protected $description = 'Genera y acumula moras para cuotas vencidas';

    public function handle()
    {
        // Obtener las cuotas vencidas y no pagadas
        $cuotasVencidas = Cuota::where('estado', '!=', 1) // No pagadas
            ->whereDate('fecha_pago', '<', Carbon::today()) // Fecha de pago pasada
            ->get();

        foreach ($cuotasVencidas as $cuota) {
            $mora = $cuota->moras()->where('estado', 'Pendiente')->first();

            if ($mora) {
                // Si ya tiene una mora, incrementar dias_mora
                if ($mora->dias_mora < 7) {
                    $mora->dias_mora += 1;
                    // Recalcular el monto de la mora
                    $mora->monto = $this->calcularMontoMora($cuota, $mora->dias_mora);
                    $mora->save();
                }
            } else {
                // Si no tiene mora, crear una nueva
                $nuevaMora = new MoraCuota;
                $nuevaMora->cuota_id = $cuota->id;
                $nuevaMora->fecha = Carbon::today();
                $nuevaMora->dias_mora = 1;
                $nuevaMora->monto = $this->calcularMontoMora($cuota, 1);
                $nuevaMora->estado = 'Pendiente';
                $nuevaMora->save();
            }
        }

        // Opcional: Mostrar un mensaje en la consola
        $this->info('Moras generadas correctamente.');
    }

    private function calcularMontoMora(Cuota $cuota, $diasMora)
    {
        $tasaDiaria = 0.01; // Por ejemplo, 1% diario

        return $cuota->monto * $tasaDiaria * $diasMora;
    }
}
