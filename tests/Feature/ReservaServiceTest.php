<?php

use App\Enums\EstadoReserva;
use App\Enums\Turno;
use App\Exceptions\ExperienciaNoDisponibleException;
use App\Models\CategoriaComplemento;
use App\Models\Complemento;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->servicio = app(ReservaService::class);
});

it('crea una reserva en estado solicitada con los importes congelados', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    $categoria = CategoriaComplemento::factory()->create();
    $complemento = Complemento::factory()->create(['categoria_id' => $categoria->id, 'precio' => 50]);
    $experiencia->complementos()->attach($complemento->id, [
        'precio_override' => 40, 'obligatorio' => false, 'cantidad_maxima' => 5, 'orden' => 0,
    ]);

    $reserva = $this->servicio->crear([
        'experiencia_id' => $experiencia->id,
        'fecha_evento' => '2026-07-15',
        'concello' => 'A Coruña',
        'complementos' => [$complemento->id => 2],
        'cliente_nombre' => 'Ana López',
        'cliente_email' => 'ana@example.com',
        'cliente_telefono' => '600111222',
    ]);

    expect($reserva->estado)->toBe(EstadoReserva::Solicitada)
        ->and($reserva->referencia)->toStartWith('TR-')
        ->and((float) $reserva->subtotal)->toBe(400.0)
        ->and((float) $reserva->total_complementos)->toBe(80.0) // 40 override x 2
        ->and((float) $reserva->total)->toBe(480.0);

    // La línea de complemento queda congelada al precio efectivo.
    $linea = $reserva->complementos()->first();
    expect((int) $linea->pivot->cantidad)->toBe(2)
        ->and((float) $linea->pivot->precio_congelado)->toBe(40.0);
});

it('lanza excepción si la experiencia no tiene unidades libres', function () {
    $experiencia = Experiencia::factory()->create(['unidades' => 1, 'permite_turnos' => false]);
    Reserva::factory()->paraExperiencia($experiencia)->enFecha('2026-07-15', Turno::Completo)->create();

    $this->servicio->crear([
        'experiencia_id' => $experiencia->id,
        'fecha_evento' => '2026-07-15',
        'concello' => 'A Coruña',
        'cliente_nombre' => 'Ana',
        'cliente_email' => 'ana@example.com',
        'cliente_telefono' => '600111222',
    ]);
})->throws(ExperienciaNoDisponibleException::class);

it('rechaza crear una reserva sobre una experiencia inactiva', function () {
    $experiencia = Experiencia::factory()->inactiva()->create();

    $this->servicio->crear([
        'experiencia_id' => $experiencia->id,
        'fecha_evento' => '2026-07-15',
        'concello' => 'A Coruña',
        'cliente_nombre' => 'Ana',
        'cliente_email' => 'ana@example.com',
        'cliente_telefono' => '600111222',
        'comprobar_disponibilidad' => false,
    ]);
})->throws(ValidationException::class);

it('descarta un pack de otra experiencia y lo trata a la carta', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    $otra = Experiencia::factory()->create();
    $packAjeno = Pack::factory()->create(['experiencia_id' => $otra->id, 'precio' => 9999]);

    $reserva = $this->servicio->crear([
        'experiencia_id' => $experiencia->id,
        'pack_id' => $packAjeno->id,
        'fecha_evento' => '2026-07-15',
        'concello' => 'A Coruña',
        'cliente_nombre' => 'Ana',
        'cliente_email' => 'ana@example.com',
        'cliente_telefono' => '600111222',
        'comprobar_disponibilidad' => false,
    ]);

    // El pack ajeno se ignora: subtotal = precio base de la experiencia, no el del pack.
    expect($reserva->pack_id)->toBeNull()
        ->and((float) $reserva->subtotal)->toBe(400.0);
});
