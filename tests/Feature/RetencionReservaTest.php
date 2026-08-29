<?php

use App\Enums\EstadoPago;
use App\Enums\EstadoReserva;
use App\Livewire\Configurador;
use App\Models\Configuracion;
use App\Models\Experiencia;
use App\Models\Reserva;
use App\Services\DisponibilidadService;
use App\Services\ReservaService;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed();

    Configuracion::actual()->update([
        'senal_tipo' => 'fijo',
        'senal_valor' => 100,
        'reserva_minutos_retencion' => 60,
        'contrato_plantilla' => null,
    ]);
});

function reservaSinPagar(string $fecha, ?Experiencia $experiencia = null): Reserva
{
    $experiencia ??= Experiencia::where('slug', 'cabana-rustica')->firstOrFail(); // 1 unidad

    return app(ReservaService::class)->crear([
        'experiencia_id' => $experiencia->id,
        'fecha_evento' => $fecha,
        'concello' => 'A Coruña',
        'cliente_nombre' => 'Cliente Retención',
        'cliente_email' => 'retencion@example.com',
        'cliente_telefono' => '600000000',
        'cliente_dni' => '11111111H',
        'cliente_direccion' => 'Calle Mayor 1',
        'acepto_lopd' => true,
    ]);
}

it('la reserva sin pagar retiene la fecha durante el plazo configurado', function () {
    $fecha = now()->addMonths(3)->toDateString();
    $reserva = reservaSinPagar($fecha);

    expect($reserva->reserva_expira_en)->not->toBeNull()
        // Mientras la retención vive, la fecha está ocupada.
        ->and(app(DisponibilidadService::class)->estaDisponible($reserva->experiencia, $fecha))->toBeFalse();
});

it('pasada la retención sin pagar, la fecha vuelve a estar libre', function () {
    $fecha = now()->addMonths(3)->toDateString();
    $reserva = reservaSinPagar($fecha);

    // El cliente abandona: la retención vence.
    $reserva->update(['reserva_expira_en' => now()->subMinute()]);

    expect(app(DisponibilidadService::class)->estaDisponible($reserva->experiencia, $fecha))->toBeTrue()
        ->and(app(DisponibilidadService::class)->unidadesLibres($reserva->experiencia, $fecha))->toBe(1)
        // Y el datepicker deja de bloquear ese día.
        ->and(app(DisponibilidadService::class)->fechasNoDisponibles(
            $reserva->experiencia, now(), now()->addMonths(6),
        ))->not->toContain($fecha);
});

it('una reserva pagada bloquea la fecha para siempre', function () {
    $fecha = now()->addMonths(3)->toDateString();
    $reserva = reservaSinPagar($fecha);

    // Se cobra la señal: la reserva pasa a confirmada y deja de caducar.
    $reserva->update(['estado' => EstadoReserva::Confirmada, 'reserva_expira_en' => null]);

    expect(app(DisponibilidadService::class)->estaDisponible($reserva->experiencia, $fecha))->toBeFalse();

    // Aunque hubiera quedado una fecha de expiración vieja, al estar confirmada ocupa igual.
    $reserva->update(['reserva_expira_en' => now()->subDay()]);

    expect(app(DisponibilidadService::class)->estaDisponible($reserva->experiencia, $fecha))->toBeFalse();
});

it('una reserva caducada no ocupa disponibilidad', function () {
    $fecha = now()->addMonths(3)->toDateString();
    $reserva = reservaSinPagar($fecha);
    $reserva->update(['estado' => EstadoReserva::Caducada]);

    expect(app(DisponibilidadService::class)->estaDisponible($reserva->experiencia, $fecha))->toBeTrue();
});

it('con retención a cero la reserva bloquea desde el primer momento', function () {
    Configuracion::actual()->update(['reserva_minutos_retencion' => 0]);

    $reserva = reservaSinPagar(now()->addMonths(3)->toDateString());

    expect($reserva->reserva_expira_en)->toBeNull()
        ->and(app(DisponibilidadService::class)->estaDisponible($reserva->experiencia, $reserva->fecha_evento))->toBeFalse();
});

it('el comando marca como caducadas las reservas vencidas sin pagar', function () {
    $reserva = reservaSinPagar(now()->addMonths(3)->toDateString());
    $reserva->update(['reserva_expira_en' => now()->subHour()]);

    // Otra que todavía está en plazo: no se debe tocar.
    $vigente = reservaSinPagar(now()->addMonths(4)->toDateString());

    $this->artisan('reservas:caducar')->assertSuccessful();

    expect($reserva->fresh()->estado)->toBe(EstadoReserva::Caducada)
        ->and($vigente->fresh()->estado)->toBe(EstadoReserva::Solicitada);
});

it('el comando no toca una reserva vencida que sí tiene un pago cobrado', function () {
    $reserva = reservaSinPagar(now()->addMonths(3)->toDateString());
    $reserva->update(['reserva_expira_en' => now()->subHour()]);
    $reserva->pagos()->first()->update(['estado' => EstadoPago::Pagado, 'pagado_en' => now()]);

    $this->artisan('reservas:caducar')->assertSuccessful();

    expect($reserva->fresh()->estado)->toBe(EstadoReserva::Solicitada);
});

it('anular desde la pantalla final borra la reserva y libera la fecha', function () {
    $fotomaton = Experiencia::where('slug', 'cabana-rustica')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('siguiente')
        ->call('siguiente')
        ->set('fecha', now()->addMonths(5)->toDateString())
        ->set('concello', 'A Coruña')
        ->call('siguiente')
        ->set('clienteNombre', 'Cliente Que Se Va')
        ->set('clienteEmail', 'sevaa@example.com')
        ->set('clienteTelefono', '600000000')
        ->set('clienteDni', '11111111H')
        ->set('clienteDireccion', 'Calle Mayor 1')
        ->set('aceptoLopd', true)
        ->call('siguiente')
        ->call('enviar');

    $referencia = $componente->get('referencia');
    expect(Reserva::where('referencia', $referencia)->exists())->toBeTrue();

    $componente->call('anularYEmpezar');

    // Desaparece de la base de datos y el wizard vuelve a empezar.
    expect(Reserva::where('referencia', $referencia)->exists())->toBeFalse()
        ->and($componente->get('paso'))->toBe(1)
        ->and($componente->get('referencia'))->toBeNull()
        ->and(app(DisponibilidadService::class)->estaDisponible($fotomaton, now()->addMonths(5)->toDateString()))->toBeTrue();
});

it('no se puede anular una reserva ya pagada', function () {
    $reserva = reservaSinPagar(now()->addMonths(3)->toDateString());
    $reserva->pagos()->first()->update(['estado' => EstadoPago::Pagado, 'pagado_en' => now()]);

    expect($reserva->fresh()->anulablePorElCliente())->toBeFalse();
});
