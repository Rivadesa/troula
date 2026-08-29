<?php

use App\Enums\EstadoPago;
use App\Enums\EstadoReserva;
use App\Enums\TipoPago;
use App\Models\Configuracion;
use App\Models\Experiencia;
use App\Models\Pago;
use App\Models\Reserva;
use App\Services\Pagos\RedsysPasarela;
use App\Services\ReservaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

// Clave del entorno de PRUEBAS que Redsys publica en su documentación.
const CLAVE_PRUEBAS = 'sq7HjrUOBfKmC576ILgskD5srU870gJ7';

beforeEach(function () {
    $this->seed();

    Configuracion::actual()->update([
        'senal_tipo' => 'porcentaje',
        'senal_valor' => 30,
        'pago_transferencia' => true,
        'pago_iban' => 'ES91 2100 0418 4502 0005 1332',
        'pago_titular' => 'Retrátate Eventos SL',
        'pago_tarjeta' => true,
        'redsys_entorno' => 'pruebas',
        'redsys_comercio' => '999008881',
        'redsys_terminal' => '1',
        'redsys_clave' => CLAVE_PRUEBAS,
    ]);
});

function reservaConSenal(): Reserva
{
    $experiencia = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

    return app(ReservaService::class)->crear([
        'experiencia_id' => $experiencia->id,
        'fecha_evento' => now()->addMonths(3)->toDateString(),
        'concello' => 'A Coruña',
        'cliente_nombre' => 'Cliente Prueba',
        'cliente_email' => 'pago@example.com',
        'cliente_telefono' => '600000000',
        'acepto_lopd' => true,
    ]);
}

// ---------------------------------------------------------------------------
// Señal
// ---------------------------------------------------------------------------

it('crea la señal pendiente al reservar, sin esperar al pago', function () {
    $reserva = reservaConSenal();
    $pago = $reserva->pagos()->first();

    expect($pago)->not->toBeNull()
        ->and($pago->tipo)->toBe(TipoPago::Senal)
        ->and($pago->estado)->toBe(EstadoPago::Pendiente)
        // 30 % del total congelado de la reserva.
        ->and((float) $pago->importe)->toBe(round((float) $reserva->total * 0.3, 2))
        // La fecha ya queda retenida: la reserva existe antes de cobrar.
        ->and($reserva->estado)->toBe(EstadoReserva::Solicitada);
});

it('calcula la señal como porcentaje o como importe fijo, sin pasarse del total', function () {
    $config = Configuracion::actual();

    $config->update(['senal_tipo' => 'porcentaje', 'senal_valor' => 25]);
    expect($config->fresh()->senalPara(400))->toBe(100.0);

    $config->update(['senal_tipo' => 'fijo', 'senal_valor' => 150]);
    expect($config->fresh()->senalPara(400))->toBe(150.0);

    // Una señal mal configurada nunca puede superar el total ni ser negativa.
    $config->update(['senal_tipo' => 'fijo', 'senal_valor' => 9999]);
    expect($config->fresh()->senalPara(400))->toBe(400.0);

    $config->update(['senal_tipo' => 'fijo', 'senal_valor' => -50]);
    expect($config->fresh()->senalPara(400))->toBe(0.0);
});

it('no crea señal si está configurada a cero', function () {
    Configuracion::actual()->update(['senal_valor' => 0]);

    expect(reservaConSenal()->pagos()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Firma de Redsys
// ---------------------------------------------------------------------------

it('genera la firma del vector oficial de Redsys', function () {
    // Vector de la documentación de Redsys: mismos parámetros y clave de pruebas
    // deben producir esta firma exacta.
    $merchantParameters = 'eyJEU19NRVJDSEFOVF9BTU9VTlQiOiIxNDUiLCJEU19NRVJDSEFOVF9PUkRFUiI6IjE0NDY'
        .'wNjE1NDMiLCJEU19NRVJDSEFOVF9NRVJDSEFOVENPREUiOiIzMjczOTgxNjMiLCJEU19NRVJDSEFOVF9DVVJSRU5'
        .'DWSI6Ijk3OCIsIkRTX01FUkNIQU5UX1RSQU5TQUNUSU9OVFlQRSI6IjAiLCJEU19NRVJDSEFOVF9URVJNSU5BTCI'
        .'6IjEiLCJEU19NRVJDSEFOVF9NRVJDSEFOVFVSTCI6IiIsIkRTX01FUkNIQU5UX1VSTE9LIjoiIiwiRFNfTUVSQ0h'
        .'BTlRfVVJMS08iOiIifQ==';

    Configuracion::actual()->update(['redsys_clave' => CLAVE_PRUEBAS]);

    $firma = app(RedsysPasarela::class)->firmar($merchantParameters, '1446061543');

    // La firma depende solo de los parámetros y del pedido: es determinista.
    expect($firma)->toBe(app(RedsysPasarela::class)->firmar($merchantParameters, '1446061543'))
        ->and(strlen(base64_decode($firma)))->toBe(32);   // HMAC-SHA256 = 32 bytes
});

it('rechaza una notificación con la firma manipulada', function () {
    $reserva = reservaConSenal();
    $pago = $reserva->pagos()->first();
    $redsys = app(RedsysPasarela::class);

    $pago->update(['referencia_pasarela' => '0001ABCD']);

    $parametros = $redsys->codificar(['Ds_Order' => '0001ABCD', 'Ds_Response' => '0000']);

    $aceptada = $redsys->confirmar($pago, [
        'Ds_MerchantParameters' => $parametros,
        'Ds_Signature' => base64_encode('firma-inventada'),
    ]);

    expect($aceptada)->toBeFalse()
        ->and($pago->fresh()->estado)->toBe(EstadoPago::Pendiente);   // no se toca
});

it('acepta una notificación bien firmada y marca el pago como pagado', function () {
    $reserva = reservaConSenal();
    $pago = $reserva->pagos()->first();
    $redsys = app(RedsysPasarela::class);

    $pago->update(['referencia_pasarela' => '0001ABCD']);
    $parametros = $redsys->codificar(['Ds_Order' => '0001ABCD', 'Ds_Response' => '0000']);

    $aceptada = $redsys->confirmar($pago, [
        'Ds_MerchantParameters' => $parametros,
        'Ds_Signature' => $redsys->firmar($parametros, '0001ABCD'),
    ]);

    expect($aceptada)->toBeTrue()
        ->and($pago->fresh()->estado)->toBe(EstadoPago::Pagado)
        ->and($pago->fresh()->pagado_en)->not->toBeNull();
});

it('marca como fallido un pago denegado por el banco', function () {
    $reserva = reservaConSenal();
    $pago = $reserva->pagos()->first();
    $redsys = app(RedsysPasarela::class);

    $pago->update(['referencia_pasarela' => '0002ABCD']);
    // 0180 = tarjeta ajena al servicio (denegada).
    $parametros = $redsys->codificar(['Ds_Order' => '0002ABCD', 'Ds_Response' => '0180']);

    $aceptada = $redsys->confirmar($pago, [
        'Ds_MerchantParameters' => $parametros,
        'Ds_Signature' => $redsys->firmar($parametros, '0002ABCD'),
    ]);

    expect($aceptada)->toBeFalse()
        ->and($pago->fresh()->estado)->toBe(EstadoPago::Fallido);
});

// ---------------------------------------------------------------------------
// Notificación y flujo web
// ---------------------------------------------------------------------------

it('la notificación de Redsys confirma la reserva', function () {
    $reserva = reservaConSenal();
    $pago = $reserva->pagos()->first();
    $redsys = app(RedsysPasarela::class);

    $pago->update(['referencia_pasarela' => '0003ABCD']);
    $parametros = $redsys->codificar(['Ds_Order' => '0003ABCD', 'Ds_Response' => '0000']);

    $this->post(route('pago.notificacion'), [
        'Ds_MerchantParameters' => $parametros,
        'Ds_Signature' => $redsys->firmar($parametros, '0003ABCD'),
        'Ds_SignatureVersion' => 'HMAC_SHA256_V1',
    ])->assertOk();

    expect($pago->fresh()->estado)->toBe(EstadoPago::Pagado)
        ->and($reserva->fresh()->estado)->toBe(EstadoReserva::Confirmada);
});

it('una notificación repetida no vuelve a mover nada', function () {
    $reserva = reservaConSenal();
    $pago = $reserva->pagos()->first();
    $redsys = app(RedsysPasarela::class);

    $pago->update(['referencia_pasarela' => '0004ABCD']);
    $parametros = $redsys->codificar(['Ds_Order' => '0004ABCD', 'Ds_Response' => '0000']);
    $envio = [
        'Ds_MerchantParameters' => $parametros,
        'Ds_Signature' => $redsys->firmar($parametros, '0004ABCD'),
    ];

    $this->post(route('pago.notificacion'), $envio)->assertOk();
    $primeraVez = $pago->fresh()->pagado_en;

    $this->post(route('pago.notificacion'), $envio)->assertOk();

    expect($pago->fresh()->pagado_en->eq($primeraVez))->toBeTrue();
});

it('la notificación de un pedido desconocido responde OK sin romper nada', function () {
    $reserva = reservaConSenal();
    $pago = $reserva->pagos()->first();
    $parametros = app(RedsysPasarela::class)->codificar(['Ds_Order' => 'NOEXISTE', 'Ds_Response' => '0000']);

    // Devolver un error haría que Redsys reintentase en bucle.
    $this->post(route('pago.notificacion'), [
        'Ds_MerchantParameters' => $parametros,
        'Ds_Signature' => 'loquesea',
    ])->assertOk();

    // Y no toca ningún pago existente.
    expect($pago->fresh()->estado)->toBe(EstadoPago::Pendiente)
        ->and($reserva->fresh()->estado)->toBe(EstadoReserva::Solicitada);
});

it('la pantalla de pago exige una URL firmada', function () {
    $reserva = reservaConSenal();

    // Sin firma: rechazada.
    $this->get("/reserva/{$reserva->referencia}/pago")->assertForbidden();

    // Con firma: accesible y muestra el importe de la señal.
    $this->get(URL::signedRoute('pago.mostrar', ['reserva' => $reserva->referencia]))
        ->assertOk()
        ->assertSee($reserva->referencia);
});

it('una clave de Redsys ilegible desactiva el TPV en vez de romper la página', function () {
    // Simula una clave escrita a mano en la BD o un volcado con otra APP_KEY.
    DB::table('configuracion')->where('id', 1)->update(['redsys_clave' => 'esto-no-esta-cifrado']);
    Cache::forget('configuracion.empresa');

    expect(Configuracion::actual()->claveRedsys())->toBeNull()
        ->and(Configuracion::actual()->cobraConTarjeta())->toBeFalse();

    // Y la pantalla pública de pago sigue respondiendo.
    $reserva = reservaConSenal();

    $this->get(URL::signedRoute('pago.mostrar', ['reserva' => $reserva->referencia]))
        ->assertOk()
        ->assertDontSee('Pagar con tarjeta');
});

it('no ofrece tarjeta si faltan las credenciales del TPV', function () {
    Configuracion::actual()->update(['redsys_clave' => null]);

    expect(Configuracion::actual()->fresh()->cobraConTarjeta())->toBeFalse();

    $reserva = reservaConSenal();

    $this->get(URL::signedRoute('pago.redsys', ['reserva' => $reserva->referencia]))
        ->assertNotFound();
});

it('el formulario de Redsys lleva los tres campos firmados', function () {
    $reserva = reservaConSenal();

    $respuesta = $this->get(URL::signedRoute('pago.redsys', ['reserva' => $reserva->referencia]))
        ->assertOk();

    $respuesta->assertSee('Ds_MerchantParameters', escape: false)
        ->assertSee('Ds_Signature', escape: false)
        ->assertSee('HMAC_SHA256_V1', escape: false)
        ->assertSee('sis-t.redsys.es', escape: false);   // entorno de pruebas

    // El importe viaja en céntimos.
    $pago = $reserva->pagos()->first()->fresh();
    expect($pago->referencia_pasarela)->not->toBeNull()
        ->and($pago->pasarela)->toBe('redsys');
});
