<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'imagen' => $this->faker->imageUrl(),
            'sucursal' => $this->faker->company(),
            'jcc' => $this->faker->randomNumber(),
            'asesor' => $this->faker->name(),
            'documento' => $this->faker->numerify('########'),
            'nombres' => $this->faker->firstName(),
            'ape_pat' => $this->faker->lastName(),
            'ape_mat' => $this->faker->lastName(),
            'telefono' => $this->faker->phoneNumber(),
            'departamento' => $this->faker->state(),
            'provincia' => $this->faker->city(),
            'distrito' => $this->faker->citySuffix(),
            'zona' => $this->faker->streetName(),
            'nlote' => $this->faker->buildingNumber(),
            'direccion' => $this->faker->address(),
            'referencia' => $this->faker->secondaryAddress(),
            'tipoCuenta' => $this->faker->randomElement(['1', '2']),
            'entidad' => $this->faker->company(),
            'cuentafi' => $this->faker->bankAccountNumber(),
            'entidadter' => $this->faker->companySuffix(),
            'cuentater' => $this->faker->bankAccountNumber(),
            'titularter' => $this->faker->name(),
            'aval' => $this->faker->randomElement(['1', '2']),
            'documentoav' => $this->faker->numerify('########'),
            'nombresav' => $this->faker->firstName(),
            'ape_patav' => $this->faker->lastName(),
            'ape_matav' => $this->faker->lastName(),
            'direccionav' => $this->faker->address(),
            'celularav' => $this->faker->phoneNumber(),
            'observ' => $this->faker->sentence(),
        ];
    }
}
