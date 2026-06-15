<?php

namespace Database\Factories;

use App\Models\ConcelloZona;
use App\Models\ZonaPorte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConcelloZona>
 */
class ConcelloZonaFactory extends Factory
{
    protected $model = ConcelloZona::class;

    public function definition(): array
    {
        return [
            'concello' => fake()->unique()->city(),
            'zona_id' => ZonaPorte::factory(),
        ];
    }
}
