<?php

namespace Database\Factories;

use App\Models\Experiencia;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pack>
 */
class PackFactory extends Factory
{
    protected $model = Pack::class;

    public function definition(): array
    {
        $nombre = 'Pack '.ucfirst(fake()->unique()->word());

        return [
            'experiencia_id' => Experiencia::factory(),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(4)),
            'descripcion' => fake()->optional()->sentence(),
            'precio' => fake()->randomFloat(2, 300, 800),
            'activo' => true,
        ];
    }
}
