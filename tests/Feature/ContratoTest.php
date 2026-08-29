<?php

use App\Models\Configuracion;
use App\Models\Experiencia;
use App\Models\Reserva;
use App\Services\ContratoService;
use App\Services\ReservaService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

const PLANTILLA = <<<'TXT'
Yo, {{titular_nombre}} con DNI {{titular_dni}} me comprometo a prestar el servicio.
CLIENTE: {{cliente_nombre}}, DNI {{cliente_dni}}, Teléfono: {{cliente_telefono}}
Dirección: {{cliente_direccion}} · Email: {{cliente_email}}
FECHA: {{fecha_evento}} en {{lugar_evento}} ({{concello}})
SERVICIO:
{{servicio}}
TOTAL: {{total}} · SEÑAL: {{senal}} · RESTO: {{resto}}
Cuenta: {{iban}}

Firman:
{{titular_nombre}}
LOS INTERESADOS
{{firma_cliente}}
TXT;

beforeEach(function () {
    $this->seed();

    Configuracion::actual()->update([
        'titular_nombre' => 'Adrián Cancela Blanco',
        'titular_dni' => '78804214G',
        'pago_iban' => 'ES07 0182 1294 1002 0114 3519',
        'senal_tipo' => 'fijo',
        'senal_valor' => 100,
        'contrato_plantilla' => PLANTILLA,
    ]);
});

function reservaParaContrato(): Reserva
{
    $experiencia = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

    return app(ReservaService::class)->crear([
        'experiencia_id' => $experiencia->id,
        'fecha_evento' => now()->addMonths(4)->toDateString(),
        'concello' => 'Arteixo',
        'lugar_evento' => 'Pazo de Vilane',
        'cliente_nombre' => 'Lucía Varela',
        'cliente_email' => 'lucia@example.com',
        'cliente_telefono' => '600123456',
        'cliente_dni' => '32717262S',
        'cliente_direccion' => 'Rúa Amil 18G, Cambre 15660',
        'acepto_lopd' => true,
    ]);
}

it('rellena la plantilla con los datos de la reserva', function () {
    $reserva = reservaParaContrato();

    $texto = app(ContratoService::class)->generar($reserva);

    expect($texto)->toContain('Adrián Cancela Blanco')
        ->toContain('78804214G')
        ->toContain('Lucía Varela')
        ->toContain('600123456')
        ->toContain('lucia@example.com')
        ->toContain('Pazo de Vilane')
        ->toContain('Arteixo')
        ->toContain('Fotomatón Solo')
        ->toContain('ES07 0182 1294 1002 0114 3519')
        ->toContain('SEÑAL: 100,00 €')
        // No debe quedar ningún hueco sin sustituir.
        ->not->toContain('{{');
});

it('el desglose del contrato suma exactamente el total', function () {
    $reserva = reservaParaContrato();
    $texto = app(ContratoService::class)->generar($reserva);

    // Se extraen los importes de las líneas del desglose y deben cuadrar con el
    // total: un contrato cuyas cifras no suman es un problema con el cliente.
    $bloque = Str::between($texto, 'SERVICIO:', 'TOTAL:');
    preg_match_all('/(−|\+)?(\d+(?:\.\d{3})*,\d{2}) €/u', $bloque, $m, PREG_SET_ORDER);

    $suma = 0.0;
    foreach ($m as $linea) {
        $importe = (float) str_replace(',', '.', str_replace('.', '', $linea[2]));
        $suma += $linea[1] === '−' ? -$importe : $importe;
    }

    expect(round($suma, 2))->toBe(round((float) $reserva->total, 2));
});

it('la pantalla de pago manda a firmar el contrato primero', function () {
    $reserva = reservaParaContrato();

    $this->get(URL::signedRoute('pago.mostrar', ['reserva' => $reserva->referencia]))
        ->assertRedirectContains('/contrato');
});

it('sin plantilla configurada no se exige contrato', function () {
    Configuracion::actual()->update(['contrato_plantilla' => null]);

    $reserva = reservaParaContrato();

    $this->get(URL::signedRoute('pago.mostrar', ['reserva' => $reserva->referencia]))
        ->assertOk();
});

it('exige marcar la casilla de aceptación', function () {
    $reserva = reservaParaContrato();

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), [])
        ->assertSessionHasErrors(['acepto']);

    expect($reserva->fresh()->contratoFirmado())->toBeFalse();
});

it('al aceptar guarda el texto, su hash y el registro de la firma', function () {
    $reserva = reservaParaContrato();

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), [
        'acepto' => '1',
    ])->assertRedirectContains('/pago');

    $reserva->refresh();

    expect($reserva->contratoFirmado())->toBeTrue()
        ->and($reserva->cliente_dni)->toBe('32717262S')
        ->and($reserva->contrato_ip)->not->toBeNull()
        ->and($reserva->contrato_aceptado_en)->not->toBeNull()
        // El DNI recién dado tiene que estar DENTRO del texto firmado.
        ->and($reserva->contrato_texto)->toContain('32717262S')
        ->and($reserva->contrato_texto)->toContain('Rúa Amil 18G')
        // El hash corresponde al texto guardado.
        ->and($reserva->contrato_hash)->toBe(hash('sha256', $reserva->contrato_texto));
});

it('una vez firmado, cambiar la plantilla no altera el contrato ya aceptado', function () {
    $reserva = reservaParaContrato();

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), [
        'acepto' => '1',
    ]);

    $textoFirmado = $reserva->fresh()->contrato_texto;
    $hashFirmado = $reserva->fresh()->contrato_hash;

    // El admin reescribe la plantilla entera.
    Configuracion::actual()->update(['contrato_plantilla' => 'OTRAS CONDICIONES COMPLETAMENTE DISTINTAS']);

    expect($reserva->fresh()->contrato_texto)->toBe($textoFirmado)
        ->and($reserva->fresh()->contrato_hash)->toBe($hashFirmado);
});

it('firmado el contrato, la pantalla de pago ya es accesible', function () {
    $reserva = reservaParaContrato();

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), [
        'acepto' => '1',
    ]);

    $this->get(URL::signedRoute('pago.mostrar', ['reserva' => $reserva->referencia]))->assertOk();
});

it('no se puede firmar dos veces', function () {
    $reserva = reservaParaContrato();

    $datos = ['acepto' => '1'];

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), $datos);
    $primeraFirma = $reserva->fresh()->contrato_aceptado_en;

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), $datos)
        ->assertRedirectContains('/contrato/firmado');

    expect($reserva->fresh()->contrato_aceptado_en->eq($primeraFirma))->toBeTrue();
});

it('la copia del contrato firmado muestra el registro de aceptación', function () {
    $reserva = reservaParaContrato();

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), [
        'acepto' => '1',
    ]);

    $this->get(URL::signedRoute('contrato.ver', ['reserva' => $reserva->referencia]))
        ->assertOk()
        ->assertSee('Registro de aceptación')
        ->assertSee($reserva->fresh()->contrato_hash);
});

it('el bloque de firma queda dentro del contrato aceptado', function () {
    $reserva = reservaParaContrato();

    // Antes de aceptar, el documento avisa de que está pendiente.
    expect(app(ContratoService::class)->generar($reserva))->toContain('PENDIENTE DE ACEPTACIÓN');

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), ['acepto' => '1']);

    $texto = $reserva->fresh()->contrato_texto;

    // Y el firmado lleva dentro quién, cuándo y desde dónde.
    expect($texto)->toContain('ACEPTADO Y FIRMADO ELECTRÓNICAMENTE')
        ->toContain('Lucía Varela')
        ->toContain('32717262S')
        ->toContain($reserva->fresh()->contrato_aceptado_en->format('d/m/Y'))
        ->not->toContain('PENDIENTE DE ACEPTACIÓN');
});

it('descarga el contrato firmado en PDF', function () {
    $reserva = reservaParaContrato();

    // Sin firmar todavía no hay PDF que descargar.
    $this->get(URL::signedRoute('contrato.pdf', ['reserva' => $reserva->referencia]))->assertNotFound();

    $this->post(URL::signedRoute('contrato.aceptar', ['reserva' => $reserva->referencia]), ['acepto' => '1']);

    $respuesta = $this->get(URL::signedRoute('contrato.pdf', ['reserva' => $reserva->referencia]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($respuesta->headers->get('content-disposition'))
        ->toContain('contrato-'.$reserva->referencia.'.pdf')
        // Un PDF de verdad empieza por %PDF.
        ->and(substr($respuesta->getContent(), 0, 4))->toBe('%PDF');
});

it('la pantalla del contrato exige URL firmada', function () {
    $reserva = reservaParaContrato();

    $this->get("/reserva/{$reserva->referencia}/contrato")->assertForbidden();
});
