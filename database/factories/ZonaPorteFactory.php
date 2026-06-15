<?php

namespace Database\Factories;

use App\Models\ZonaPorte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ZonaPorte>
 */
class ZonaPorteFactory extends Factory
{
    protected $model = ZonaPorte::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Zona '.ucfirst(fake()->unique()->word()),
            'precio_porte' => fake()->randomFloat(2, 0, 120),
            'precio_montaje' => fake()->randomFloat(2, 0, 80),
        ];
    }
}
