<?php

namespace Database\Factories;

use App\Enums\TipoAjuste;
use App\Models\Temporada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Temporada>
 */
class TemporadaFactory extends Factory
{
    protected $model = Temporada::class;

    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('-6 months', '+3 months');
        $fin = (clone $inicio)->modify('+'.fake()->numberBetween(15, 90).' days');

        return [
            'nombre' => fake()->randomElement(['Alta', 'Baja', 'Media']),
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin->format('Y-m-d'),
            'tipo_ajuste' => fake()->randomElement(TipoAjuste::cases()),
            'valor' => fake()->randomFloat(2, 5, 25),
            'activo' => true,
        ];
    }

    public function porcentaje(float $valor): static
    {
        return $this->state(fn () => ['tipo_ajuste' => TipoAjuste::Porcentaje, 'valor' => $valor]);
    }

    public function fijo(float $valor): static
    {
        return $this->state(fn () => ['tipo_ajuste' => TipoAjuste::Fijo, 'valor' => $valor]);
    }

    public function rango(string $inicio, string $fin): static
    {
        return $this->state(fn () => ['fecha_inicio' => $inicio, 'fecha_fin' => $fin]);
    }
}
