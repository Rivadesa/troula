<?php

use App\Livewire\Configurador;
use App\Mail\NuevaReservaMail;
use App\Models\Cliente;
use App\Models\Complemento;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Reserva;
use App\Services\CalculadoraPrecioService;
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
        ->assertSee('Retrátate Eventos', escape: false);
});

it('preselecciona los complementos obligatorios al elegir experiencia', function () {
    // El Fotomatón con Estructura y Neón los lleva incluidos (obligatorios, precio 0).
    $estructura = Experiencia::where('slug', 'fotomaton-estructura-neon')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $estructura->id)
        ->assertSet('experienciaId', $estructura->id)
        ->assertCount('complementos', 2); // estructura + neón

    // El Fotomatón Solo no lleva nada incluido.
    $solo = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $solo->id)
        ->assertCount('complementos', 0);
});

it('los complementos incluidos en la máquina no se vuelven a cobrar', function () {
    $estructura = Experiencia::where('slug', 'fotomaton-estructura-neon')->firstOrFail();

    $desglose = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $estructura->id)
        ->instance()
        ->desglose();

    // 600 € incluye estructura y neón: los obligatorios llevan precio_override 0.
    expect($desglose->subtotal)->toBe(600.0)
        ->and($desglose->totalComplementos)->toBe(0.0)
        ->and($desglose->total)->toBe(600.0);
});

it('completa el wizard y crea una reserva solicitada enviando el aviso al administrador', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

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
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

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
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

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

// ---------------------------------------------------------------------------
// Catálogo real: grupos "elige uno", horas extra, a consultar y base de pack
// ---------------------------------------------------------------------------

it('en un grupo "elige uno" solo puede quedar seleccionada una opción', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();
    $packA = Complemento::where('slug', 'hora-loca-a')->firstOrFail();
    $packB = Complemento::where('slug', 'hora-loca-b')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('alternarComplemento', $packA->id)
        ->assertSet("complementos.{$packA->id}", 1)
        ->call('alternarComplemento', $packB->id);

    // Al elegir el Pack B, el A deja de estar seleccionado.
    expect($componente->get('complementos'))->toHaveKey($packB->id)
        ->and($componente->get('complementos'))->not->toHaveKey($packA->id);

    // Y solo cuenta una línea en el desglose.
    expect($componente->instance()->desglose()->lineasComplementos)->toHaveCount(1);
});

it('los complementos sin grupo se pueden seleccionar a la vez', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();
    $neon = Complemento::where('slug', 'neon')->firstOrFail();
    $alfombra = Complemento::where('slug', 'alfombra')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('alternarComplemento', $neon->id)
        ->call('alternarComplemento', $alfombra->id);

    expect($componente->get('complementos'))->toHaveKeys([$neon->id, $alfombra->id]);
});

it('el stepper de horas extra recalcula el total y respeta el tope', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail(); // 450 €, hora extra 70 €

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('subirHoraExtra')
        ->call('subirHoraExtra')
        ->assertSet('horasExtra', 2);

    expect($componente->instance()->desglose()->total)->toBe(590.0); // 450 + 2 × 70

    $componente->call('bajarHoraExtra')->assertSet('horasExtra', 1);

    // No baja de cero.
    $componente->call('bajarHoraExtra')->call('bajarHoraExtra')->assertSet('horasExtra', 0);

    // Ni sube del tope.
    $componente->call('actualizarHorasExtra', 99)
        ->assertSet('horasExtra', CalculadoraPrecioService::MAX_HORAS_EXTRA);
});

it('cambiar la máquina base del pack conserva el pack y recalcula con el suplemento', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();
    $espejo = Experiencia::where('slug', 'espejo-magico')->firstOrFail();
    $pack = Pack::where('slug', 'pack-bronce')->firstOrFail(); // 700 €, espejo +80 €

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('elegirPack', $pack->id)
        ->assertSet('packId', $pack->id);

    expect($componente->instance()->desglose()->total)->toBe(700.0);

    $componente->call('cambiarBasePack', $espejo->id)
        ->assertSet('packId', $pack->id)          // el pack se mantiene
        ->assertSet('experienciaId', $espejo->id) // la máquina cambia
        ->assertSet('horasExtra', 0);

    expect($componente->instance()->desglose()->total)->toBe(780.0)   // 700 + 80
        ->and($componente->instance()->desglose()->suplementoBase)->toBe(80.0);
});

it('no acepta como base del pack una máquina que no admite', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();
    $pack = Pack::where('slug', 'pack-360-loco')->firstOrFail(); // base 360, sin alternativas
    $plataforma = Experiencia::where('slug', 'plataforma-360')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $plataforma->id)
        ->call('elegirPack', $pack->id)
        ->call('cambiarBasePack', $fotomaton->id)
        ->assertSet('experienciaId', $plataforma->id);  // se ignora
});

it('un complemento a consultar se guarda en la reserva pero no suma al total', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();
    $portafoto = Complemento::where('slug', 'portafoto-iman')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('alternarComplemento', $portafoto->id)
        ->call('siguiente')
        ->call('siguiente')
        ->set('fecha', '2027-05-01')
        ->set('concello', 'A Coruña')
        ->call('siguiente')
        ->set('clienteNombre', 'Cliente Consulta')
        ->set('clienteEmail', 'consulta@example.com')
        ->set('clienteTelefono', '600000000')
        ->set('aceptoLopd', true)
        ->call('siguiente')
        ->call('enviar')
        ->assertHasNoErrors();

    $reserva = Reserva::where('cliente_email', 'consulta@example.com')->firstOrFail();

    // 450 base + 30 de montaje de la zona de A Coruña; el portafoto no suma.
    expect((float) $reserva->total_complementos)->toBe(0.0)
        ->and((float) $reserva->total)->toBe(480.0)
        ->and($reserva->complementos->pluck('slug'))->toContain('portafoto-iman')
        ->and((float) $reserva->complementos->firstWhere('slug', 'portafoto-iman')->pivot->precio_congelado)->toBe(0.0);
});

it('congela las horas extra contratadas en la reserva', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('subirHoraExtra')
        ->call('siguiente')
        ->call('siguiente')
        ->set('fecha', '2027-05-02')
        ->set('concello', 'A Coruña')
        ->call('siguiente')
        ->set('clienteNombre', 'Cliente Horas')
        ->set('clienteEmail', 'horas@example.com')
        ->set('clienteTelefono', '600000000')
        ->set('aceptoLopd', true)
        ->call('siguiente')
        ->call('enviar')
        ->assertHasNoErrors();

    $reserva = Reserva::where('cliente_email', 'horas@example.com')->firstOrFail();

    // 450 base + 70 de la hora extra + 30 de montaje de la zona de A Coruña.
    expect($reserva->horas_extra)->toBe(1)
        ->and((float) $reserva->total)->toBe(550.0);
});

// ---------------------------------------------------------------------------
// Modelos a elegir dentro de una máquina (telas, lentejuelas, estructuras, neones)
// ---------------------------------------------------------------------------

it('el fotomatón de photocall preselecciona una tela sin coste', function () {
    $tela = Experiencia::where('slug', 'fotomaton-photocall-tela')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $tela->id);

    $elegidos = Complemento::whereIn('id', array_keys($componente->get('complementos')))->pluck('slug');

    expect($elegidos)->toHaveCount(1)
        ->and($elegidos->first())->toStartWith('tela-')
        ->and($componente->instance()->desglose()->total)->toBe(480.0); // la tela no suma
});

it('elegir otra tela sustituye a la anterior y no cambia el precio', function () {
    $tela = Experiencia::where('slug', 'fotomaton-photocall-tela')->firstOrFail();
    $londres = Complemento::where('slug', 'tela-londres')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $tela->id)
        ->call('alternarComplemento', $londres->id);

    expect($componente->get('complementos'))->toHaveCount(1)
        ->and($componente->get('complementos'))->toHaveKey($londres->id)
        ->and($componente->instance()->desglose()->total)->toBe(480.0);
});

it('no se puede quedar sin tela: deseleccionar la elegida no hace nada', function () {
    $tela = Experiencia::where('slug', 'fotomaton-photocall-tela')->firstOrFail();
    $londres = Complemento::where('slug', 'tela-londres')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $tela->id)
        ->call('alternarComplemento', $londres->id)
        ->call('alternarComplemento', $londres->id);   // intenta quitarla

    expect($componente->get('complementos'))->toHaveKey($londres->id);
});

it('el fotomatón de estructura y neón trae estructura y neón incluidos, y el sofá lo sube a 700', function () {
    $maquina = Experiencia::where('slug', 'fotomaton-estructura-neon')->firstOrFail();
    $sofa = Complemento::where('slug', 'sofa-chester-marron')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $maquina->id);

    $elegidos = Complemento::whereIn('id', array_keys($componente->get('complementos')))->pluck('slug');

    // Una estructura y un neón preseleccionados, ninguno con coste.
    expect($elegidos)->toHaveCount(2)
        ->and($componente->instance()->desglose()->total)->toBe(600.0);

    // El sofá sí se cobra: es exactamente la diferencia del catálogo (600 -> 700).
    $componente->call('alternarComplemento', $sofa->id);

    expect($componente->instance()->desglose()->total)->toBe(700.0);
});

it('los neones cuestan 80 € en una máquina que no los lleva incluidos', function () {
    $solo = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail(); // 450 €
    $neon = Complemento::where('slug', 'neon-si-quiero')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $solo->id);

    // Sin elecciones incluidas: aquí el neón es un extra de pago, no se preselecciona.
    expect($componente->get('complementos'))->toBeEmpty();

    $componente->call('alternarComplemento', $neon->id);

    expect($componente->instance()->desglose()->total)->toBe(530.0); // 450 + 80
});

it('solo se puede llevar un neón: elegir otro sustituye al anterior', function () {
    $solo = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();
    $uno = Complemento::where('slug', 'neon-si-quiero')->firstOrFail();
    $otro = Complemento::where('slug', 'neon-querote')->firstOrFail();

    $componente = Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $solo->id)
        ->call('alternarComplemento', $uno->id)
        ->call('alternarComplemento', $otro->id);

    expect($componente->get('complementos'))->toHaveCount(1)
        ->and($componente->get('complementos'))->toHaveKey($otro->id)
        ->and($componente->instance()->desglose()->total)->toBe(530.0);
});

it('ofrece los 313 concellos de Galicia agrupados por provincia', function () {
    $concellos = Livewire::test(Configurador::class)->instance()->concellos();

    expect($concellos->keys()->all())->toBe(['A Coruña', 'Lugo', 'Ourense', 'Pontevedra'])
        ->and($concellos['A Coruña'])->toHaveCount(93)
        ->and($concellos['Lugo'])->toHaveCount(67)
        ->and($concellos['Ourense'])->toHaveCount(92)
        ->and($concellos['Pontevedra'])->toHaveCount(61)
        ->and($concellos->flatten())->toHaveCount(313)
        ->and($concellos['Pontevedra'])->toContain('Vigo')
        ->and($concellos['Lugo'])->toContain('Ribadeo');
});

it('no deja avanzar el paso del evento sin fecha ni concello', function () {
    $fotomaton = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

    Livewire::test(Configurador::class)
        ->call('seleccionarExperiencia', $fotomaton->id)
        ->call('siguiente')
        ->call('siguiente')   // llega al paso 3
        ->call('siguiente')   // intenta avanzar sin datos
        ->assertHasErrors(['fecha', 'concello'])
        ->assertSet('paso', 3);
});
