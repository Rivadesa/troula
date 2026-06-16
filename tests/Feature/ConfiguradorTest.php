<?php

use App\Livewire\Configurador;
use App\Mail\NuevaReservaMail;
use App\Models\Cliente;
use App\Models\Experiencia;
use App\Models\Reserva;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    $this->seed();
});

it('renderiza la home del configurador con el layout público', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Elige tu experiencia')
        ->assertSee('Troula Eventos');
});

it('preselecciona los complementos obligatorios al elegir experiencia', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-clasico')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->assertSet('experienciaId', $fotomaton->id)
        ->assertCount('complementos', 1); // "impresiones-ilimitadas" es obligatorio
});

it('solo ofrece los complementos de la experiencia elegida', function () {
    $sofa = Experiencia::where('slug', 'photocall-sofa-decorado')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $sofa->id);

    $ofrecidos = $sofa->complementos->pluck('slug');

    // El photocall NO ofrece impresiones, pero sí fondo floral.
    expect($ofrecidos)->toContain('fondo-floral')
        ->and($ofrecidos)->not->toContain('impresiones-ilimitadas');
});

it('completa el wizard y crea una reserva solicitada enviando el aviso al administrador', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-clasico')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('siguiente')                       // paso 1 -> 2
        ->assertSet('paso', 2)
        ->call('siguiente')                       // paso 2 -> 3
        ->assertSet('paso', 3)
        ->set('fecha', '2027-05-01')              // dispara updatedFecha -> turno disponible
        ->set('concello', 'A Coruña')
        ->call('siguiente')                       // paso 3 -> 4
        ->assertSet('paso', 4)
        ->set('clienteNombre', 'Cliente de Prueba')
        ->set('clienteEmail', 'prueba@example.com')
        ->set('clienteTelefono', '600000000')
        ->set('aceptoLopd', true)
        ->call('siguiente')                       // paso 4 -> 5
        ->assertSet('paso', 5)
        ->call('enviar')
        ->assertHasNoErrors();

    $reserva = Reserva::where('cliente_email', 'prueba@example.com')->first();

    expect($reserva)->not->toBeNull()
        ->and($reserva->estado->value)->toBe('solicitada')
        ->and((float) $reserva->total)->toBeGreaterThan(0);

    // El cliente queda registrado con su consentimiento LOPD.
    $cliente = Cliente::where('email', 'prueba@example.com')->first();
    expect($cliente)->not->toBeNull()
        ->and($cliente->acepto_lopd)->toBeTrue()
        ->and($reserva->cliente_id)->toBe($cliente->id);

    Mail::assertQueued(NuevaReservaMail::class, fn (NuevaReservaMail $mail) => $mail->reserva->is($reserva));
});

it('exige aceptar la política de privacidad (LOPD) para enviar', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-clasico')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('siguiente')
        ->call('siguiente')
        ->set('fecha', '2027-05-01')
        ->set('concello', 'A Coruña')
        ->call('siguiente')   // paso 3 -> 4
        ->set('clienteNombre', 'Cliente')
        ->set('clienteEmail', 'cliente@example.com')
        ->set('clienteTelefono', '600000000')
        ->call('siguiente')   // intenta avanzar sin aceptar la LOPD
        ->assertHasErrors(['aceptoLopd'])
        ->assertSet('paso', 4);
});

it('el honeypot bloquea los envíos automatizados (anti-spam)', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-clasico')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('siguiente')
        ->call('siguiente')
        ->set('fecha', '2027-05-01')
        ->set('concello', 'A Coruña')
        ->call('siguiente')
        ->set('clienteNombre', 'Bot')
        ->set('clienteEmail', 'bot@spam.test')
        ->set('clienteTelefono', '600000000')
        ->set('aceptoLopd', true)
        ->call('siguiente')
        ->set('website', 'http://spam.example')   // un bot rellena el honeypot
        ->call('enviar')
        ->assertHasNoErrors();

    expect(Reserva::where('cliente_email', 'bot@spam.test')->exists())->toBeFalse();
    Mail::assertNothingQueued();
});

it('no deja avanzar el paso del evento sin fecha ni concello', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-clasico')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('siguiente')
        ->call('siguiente')   // llega al paso 3
        ->call('siguiente')   // intenta avanzar sin datos
        ->assertHasErrors(['fecha', 'concello'])
        ->assertSet('paso', 3);
});
