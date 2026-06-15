<?php

namespace Database\Factories;

use App\Enums\EstadoReserva;
use App\Enums\Turno;
use App\Models\Experiencia;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reserva>
 */
class ReservaFactory extends Factory
{
    protected $model = Reserva::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 250, 600);
        $complementos = fake()->randomFloat(2, 0, 200);
        $porte = fake()->randomFloat(2, 0, 100);
        $montaje = fake()->randomFloat(2, 0, 60);
        $ajuste = fake()->randomFloat(2, -50, 80);

        return [
            'referencia' => fake()->unique()->bothify('TR-2026####-?????'),
            'experiencia_id' => Experiencia::factory(),
            'pack_id' => null,
            'fecha_evento' => fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
            'turno' => Turno::Completo,
            'concello' => fake()->city(),
            'zona_id' => null,
            'cliente_nombre' => fake()->name(),
            'cliente_email' => fake()->safeEmail(),
            'cliente_telefono' => fake()->numerify('6########'),
            'lugar_evento' => fake()->optional()->company(),
            'observaciones' => fake()->optional()->sentence(),
            'subtotal' => $subtotal,
            'ajuste_temporada' => $ajuste,
            'total_complementos' => $complementos,
            'porte' => $porte,
            'montaje' => $montaje,
            'total' => round($subtotal + $ajuste + $complementos + $porte + $montaje, 2),
            'estado' => EstadoReserva::Solicitada,
        ];
    }

    public function paraExperiencia(Experiencia $experiencia): static
    {
        return $this->state(fn () => ['experiencia_id' => $experiencia->id]);
    }

    public function enFecha(string $fecha, Turno $turno = Turno::Completo): static
    {
        return $this->state(fn () => ['fecha_evento' => $fecha, 'turno' => $turno]);
    }

    public function estado(EstadoReserva $estado): static
    {
        return $this->state(fn () => ['estado' => $estado]);
    }

    public function confirmada(): static
    {
        return $this->estado(EstadoReserva::Confirmada);
    }

    public function cancelada(): static
    {
        return $this->estado(EstadoReserva::Cancelada);
    }
}
