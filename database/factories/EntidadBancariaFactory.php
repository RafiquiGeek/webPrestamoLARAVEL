<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EntidadBancaria>
 */
class EntidadBancariaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'banco' => $this->faker->randomElement(['BCP', 'BBVA', 'Interbank', 'Scotiabank']),
            'status' => '1',
        ];
    }
}
