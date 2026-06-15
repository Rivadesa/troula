<?php

namespace Database\Factories;

use App\Enums\EstadoPago;
use App\Enums\TipoPago;
use App\Models\Pago;
use App\Models\Reserva;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'reserva_id' => Reserva::factory(),
            'tipo' => TipoPago::Senal,
            'importe' => fake()->randomFloat(2, 50, 300),
            'estado' => EstadoPago::Pendiente,
            'pasarela' => null,
            'referencia_pasarela' => null,
            'pagado_en' => null,
        ];
    }

    public function saldo(): static
    {
        return $this->state(fn () => ['tipo' => TipoPago::Saldo]);
    }

    public function pagado(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoPago::Pagado,
            'pagado_en' => now(),
        ]);
    }
}
