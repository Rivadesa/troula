<?php

namespace Database\Factories;

use App\Models\CategoriaComplemento;
use App\Models\Complemento;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Complemento>
 */
class ComplementoFactory extends Factory
{
    protected $model = Complemento::class;

    public function definition(): array
    {
        $nombre = ucfirst(fake()->unique()->words(2, true));

        return [
            'categoria_id' => CategoriaComplemento::factory(),
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(4)),
            'descripcion' => fake()->optional()->sentence(),
            'precio' => fake()->randomFloat(2, 10, 150),
            'imagen' => null,
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
