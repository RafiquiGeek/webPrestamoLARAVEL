<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Solicitud;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Solicitud>
 */
class SolicitudFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Solicitud::class;

    public function definition(): array
    {
        return [
            // 'cliente_id' => function() {return Cliente::all()->random()->id;},
            // 'estado' => $this->faker->randomElement(['En Analisis', 'En Espera', 'Finalizado', 'Aprobado']),
            // 'cliente' => $this->faker->name(),
            // 'tip_sol' => $this->faker->randomElement(['Nuevo', 'Antiguo']),
            // 'cta_asig' => $this->faker->randomElement(['BCP', 'Interbank', 'Scotiabank', 'BBVA']),
            // 'fech_ate' => $this->faker->date(),
            // 'plazo' => $this->faker->randomElement(['12 semanas', '15 semanas', '18 semanas', '20 semanas']),
            // 'mon_sol' => $this->faker->randomFloat(2, 1000, 10000),
            // 'tas_int' => $this->faker->randomFloat(2, 1, 20),
            // 'cap_int' => $this->faker->randomFloat(2, 1000, 10000),
            // 'tas_mor' => $this->faker->randomFloat(2, 1, 20),
            // 'fre_pag' => $this->faker->randomElement(['Semanal', 'Mensual', 'Bimestral', 'Trimestral']),
            // 'fpri_pag' => $this->faker->date(),
            // 'ana_cre' => $this->faker->name(),
            // 'observ' => $this->faker ->sentence()
        ];
    }
}
