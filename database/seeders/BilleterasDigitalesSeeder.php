<?php

namespace Database\Seeders;

use App\Models\BilleteraDigital;
use Illuminate\Database\Seeder;

class BilleterasDigitalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billeteras = [
            ['nombre' => 'Yape', 'status' => 1],
            ['nombre' => 'Plin', 'status' => 1],
            ['nombre' => 'Dale', 'status' => 1],
            ['nombre' => 'Tunki', 'status' => 1],
            ['nombre' => 'Bim', 'status' => 1],
            ['nombre' => 'Lukita', 'status' => 1],
            ['nombre' => 'Agora Pay', 'status' => 1],
        ];

        foreach ($billeteras as $billetera) {
            BilleteraDigital::updateOrCreate(
                ['nombre' => $billetera['nombre']],
                $billetera
            );
        }
    }
}
