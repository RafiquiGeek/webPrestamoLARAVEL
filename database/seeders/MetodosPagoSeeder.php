<?php

namespace Database\Seeders;

use App\Models\MetodoDePago;
use Illuminate\Database\Seeder;

class MetodosPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $metodos = [
            ['metodo_pago' => 'Efectivo', 'status' => 1],
            ['metodo_pago' => 'Transferencia', 'status' => 1],
            ['metodo_pago' => 'Yape', 'status' => 1],
        ];

        foreach ($metodos as $metodo) {
            MetodoDePago::updateOrCreate(
                ['metodo_pago' => $metodo['metodo_pago']],
                $metodo
            );
        }

        // Asegurar que solo haya 3 métodos activos
        MetodoDePago::whereNotIn('metodo_pago', ['Efectivo', 'Transferencia', 'Yape'])
            ->update(['status' => 0]);
    }
}
