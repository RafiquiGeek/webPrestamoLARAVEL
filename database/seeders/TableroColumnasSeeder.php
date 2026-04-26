<?php

namespace Database\Seeders;

use App\Models\TableroColumna;
use Illuminate\Database\Seeder;

class TableroColumnasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $columnas = [
            [
                'nombre' => 'Pendiente',
                'color' => '#6c757d',
                'orden' => 1,
                'activo' => true,
                'es_sistema' => true,
            ],
            [
                'nombre' => 'En Progreso',
                'color' => '#007bff',
                'orden' => 2,
                'activo' => true,
                'es_sistema' => true,
            ],
            [
                'nombre' => 'En Revisión',
                'color' => '#ffc107',
                'orden' => 3,
                'activo' => true,
                'es_sistema' => false,
            ],
            [
                'nombre' => 'Completado',
                'color' => '#28a745',
                'orden' => 4,
                'activo' => true,
                'es_sistema' => true,
            ],
        ];

        foreach ($columnas as $columna) {
            TableroColumna::updateOrCreate(
                ['nombre' => $columna['nombre']],
                $columna
            );
        }
    }
}
