<?php

use App\Enums\EstadoReserva;
use App\Enums\Turno;
use App\Models\Experiencia;
use App\Models\Reserva;
use App\Services\DisponibilidadService;

beforeEach(function () {
    $this->servicio = new DisponibilidadService;
    $this->fecha = '2026-07-01';
});

it('una experiencia sin turnos queda ocupada el día completo con una sola reserva', function () {
    $experiencia = Experiencia::factory()->create(['permite_turnos' => false, 'unidades' => 1]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Completo)->create();

    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Completo))->toBeFalse();
    // Aunque pidan un turno, se normaliza a completo porque la experiencia no admite turnos.
    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Manana))->toBeFalse();
});

it('una experiencia con turnos admite mañana y tarde el mismo día por unidad', function () {
    $experiencia = Experiencia::factory()->permiteTurnos()->create(['unidades' => 1]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Manana)->create();

    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Manana))->toBeFalse();
    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Tarde))->toBeTrue();
    expect($this->servicio->turnosDisponibles($experiencia, $this->fecha))->toBe([Turno::Tarde]);
});

it('un turno completo solapa con mañana y con tarde', function () {
    $experiencia = Experiencia::factory()->permiteTurnos()->create(['unidades' => 1]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Completo)->create();

    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Manana))->toBeFalse();
    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Tarde))->toBeFalse();
    expect($this->servicio->turnosDisponibles($experiencia, $this->fecha))->toBe([]);
});

it('mañana y tarde no solapan entre sí', function () {
    $experiencia = Experiencia::factory()->permiteTurnos()->create(['unidades' => 1]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Manana)->create();
    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Tarde)->create();

    // Las dos reservas (mañana y tarde) conviven sobre la misma unidad sin solaparse.
    expect(Reserva::count())->toBe(2);
    expect($this->servicio->turnosDisponibles($experiencia, $this->fecha))->toBe([]);
});

it('las reservas canceladas no ocupan disponibilidad', function () {
    $experiencia = Experiencia::factory()->permiteTurnos()->create(['unidades' => 1]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Manana)
        ->estado(EstadoReserva::Cancelada)->create();

    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Manana))->toBeTrue();
});

it('respeta el número de unidades de la experiencia', function () {
    $experiencia = Experiencia::factory()->permiteTurnos()->create(['unidades' => 2]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Manana)->create();
    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Manana))->toBeTrue();

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Manana)->create();
    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Manana))->toBeFalse();
});

it('un turno completo cuenta contra las unidades de los turnos parciales', function () {
    $experiencia = Experiencia::factory()->permiteTurnos()->create(['unidades' => 2]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Completo)->create();
    Reserva::factory()->paraExperiencia($experiencia)->enFecha($this->fecha, Turno::Manana)->create();

    // 2 unidades: un completo + una mañana saturan la mañana, pero la tarde aún tiene 1 libre.
    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Manana))->toBeFalse();
    expect($this->servicio->estaDisponible($experiencia, $this->fecha, Turno::Tarde))->toBeTrue();
});

it('lista las fechas totalmente bloqueadas para el datepicker', function () {
    $experiencia = Experiencia::factory()->create(['permite_turnos' => false, 'unidades' => 1]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha('2026-07-01', Turno::Completo)->create();
    Reserva::factory()->paraExperiencia($experiencia)->enFecha('2026-07-03', Turno::Completo)->create();

    $bloqueadas = $this->servicio->fechasNoDisponibles($experiencia, '2026-07-01', '2026-07-05');

    expect($bloqueadas)->toContain('2026-07-01', '2026-07-03')
        ->not->toContain('2026-07-02', '2026-07-04', '2026-07-05');
});

it('una fecha con solo la mañana ocupada sigue disponible (la tarde queda libre)', function () {
    $experiencia = Experiencia::factory()->permiteTurnos()->create(['unidades' => 1]);

    Reserva::factory()->paraExperiencia($experiencia)->enFecha('2026-07-01', Turno::Manana)->create();

    $bloqueadas = $this->servicio->fechasNoDisponibles($experiencia, '2026-07-01', '2026-07-02');

    expect($bloqueadas)->not->toContain('2026-07-01');
});
