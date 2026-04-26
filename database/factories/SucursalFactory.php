<?php

namespace Database\Factories;

use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sucursal>
 */
class SucursalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'sucursal' => $this->faker->randomElement(['Puente Piedra I', 'Puente Piedra II', 'Lima Centro', 'Lima Norte']),
            // 'departamento_id' => Departamento::where('departamento', '')->first()->id,
            // 'provincia_id' => Provincia::where('provincia', 'nombre_de_la_provincia')->first()->id,
            // 'distrito_id' => Distrito::where('distrito', 'nombre_del_distrito')->first()->id
        ];
    }
}
