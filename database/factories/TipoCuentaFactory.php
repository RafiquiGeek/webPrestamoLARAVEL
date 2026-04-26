<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TipoCuenta>
 */
class TipoCuentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->numberBetween(1, 2),
            'tipo' => $this->faker->unique()->randomElement(['Cuenta Propia', 'Cuenta de Terceros']),
            'status' => 1,
        ];
    }
}
