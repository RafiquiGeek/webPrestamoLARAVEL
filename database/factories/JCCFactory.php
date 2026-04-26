<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JCC>
 */
class JCCFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => $this->faker->name,
            'ape_pat' => $this->faker->lastName,
            'ape_mat' => $this->faker->lastName,
            'dni' => $this->faker->unique()->numerify('########'),
            'celular' => $this->faker->phoneNumber,
            'email' => $this->faker->unique()->safeEmail,
            'codigo' => $this->faker->unique()->numerify('JCC####'),
        ];
    }
}
