<?php

namespace Database\Factories;

use App\Models\Experiencia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Experiencia>
 */
class ExperienciaFactory extends Factory
{
    protected $model = Experiencia::class;

    public function definition(): array
    {
        $nombre = ucfirst(fake()->unique()->words(2, true));

        return [
            'nombre' => $nombre,
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(4)),
            'descripcion' => fake()->paragraph(),
            'precio_base' => fake()->randomFloat(2, 200, 600),
            'imagen' => null,
            'unidades' => 1,
            'permite_turnos' => false,
            'activo' => true,
            'orden' => fake()->numberBetween(0, 10),
        ];
    }

    public function permiteTurnos(): static
    {
        return $this->state(fn () => ['permite_turnos' => true]);
    }

    public function unidades(int $unidades): static
    {
        return $this->state(fn () => ['unidades' => $unidades]);
    }

    public function inactiva(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
