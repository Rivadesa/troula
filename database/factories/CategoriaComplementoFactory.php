<?php

namespace Database\Factories;

use App\Models\CategoriaComplemento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CategoriaComplemento>
 */
class CategoriaComplementoFactory extends Factory
{
    protected $model = CategoriaComplemento::class;

    public function definition(): array
    {
        $nombre = ucfirst(fake()->unique()->words(2, true));

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(4)),
            'orden' => fake()->numberBetween(0, 10),
        ];
    }
}
