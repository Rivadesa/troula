<?php

use App\Models\CategoriaComplemento;
use App\Models\Complemento;
use App\Models\ConcelloZona;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Temporada;
use App\Models\ZonaPorte;
use App\Services\CalculadoraPrecioService;

beforeEach(function () {
    $this->calc = new CalculadoraPrecioService;
});

/**
 * Crea una experiencia con un complemento asociado y devuelve [experiencia, complemento].
 *
 * @return array{0: Experiencia, 1: Complemento}
 */
function experienciaConComplemento(float $precioBase, float $precioComplemento, ?float $override = null): array
{
    $experiencia = Experiencia::factory()->create(['precio_base' => $precioBase]);
    $categoria = CategoriaComplemento::factory()->create();
    $complemento = Complemento::factory()->create(['categoria_id' => $categoria->id, 'precio' => $precioComplemento]);

    $experiencia->complementos()->attach($complemento->id, [
        'precio_override' => $override,
        'obligatorio' => false,
        'cantidad_maxima' => 5,
        'orden' => 0,
    ]);

    return [$experiencia, $complemento];
}

it('sin temporada, complementos ni concello el total es el precio base', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);

    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2026-07-15');

    expect($desglose->subtotal)->toBe(400.0)
        ->and($desglose->ajusteTemporada)->toBe(0.0)
        ->and($desglose->totalComplementos)->toBe(0.0)
        ->and($desglose->porte)->toBe(0.0)
        ->and($desglose->montaje)->toBe(0.0)
        ->and($desglose->total)->toBe(400.0);
});

it('aplica un recargo de temporada por porcentaje', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    Temporada::factory()->porcentaje(20)->rango('2026-06-01', '2026-09-15')->create(['nombre' => 'Alta']);

    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2026-07-15');

    expect($desglose->ajusteTemporada)->toBe(80.0)
        ->and($desglose->total)->toBe(480.0)
        ->and($desglose->temporadaNombre)->toBe('Alta');
});

it('aplica un descuento de temporada con importe fijo (valor negativo)', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    Temporada::factory()->fijo(-50)->rango('2026-01-01', '2026-03-31')->create(['nombre' => 'Baja']);

    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2026-02-10');

    expect($desglose->ajusteTemporada)->toBe(-50.0)
        ->and($desglose->total)->toBe(350.0);
});

it('localiza la temporada por mes/día aunque cambie el año', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    Temporada::factory()->porcentaje(10)->rango('2026-06-01', '2026-09-15')->create();

    // La fecha del evento es de otro año pero cae dentro del rango mensual.
    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2028-07-20');

    expect($desglose->ajusteTemporada)->toBe(40.0);
});

it('localiza temporadas cuyo rango cruza el fin de año', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    Temporada::factory()->porcentaje(15)->rango('2026-12-15', '2027-01-10')->create(['nombre' => 'Navidad']);

    expect($this->calc->calcular($experiencia, fechaEvento: '2026-12-31')->temporadaNombre)->toBe('Navidad');
    expect($this->calc->calcular($experiencia, fechaEvento: '2027-01-05')->temporadaNombre)->toBe('Navidad');
    expect($this->calc->calcular($experiencia, fechaEvento: '2026-11-30')->temporadaNombre)->toBeNull();
});

it('suma complementos usando el precio del complemento por su cantidad', function () {
    [$experiencia, $complemento] = experienciaConComplemento(400, 50);

    $desglose = $this->calc->calcular($experiencia, complementos: [$complemento->id => 2], fechaEvento: '2026-07-15');

    expect($desglose->totalComplementos)->toBe(100.0)
        ->and($desglose->total)->toBe(500.0);
});

it('usa el precio_override de la experiencia cuando existe', function () {
    [$experiencia, $complemento] = experienciaConComplemento(400, 50, override: 30);

    $desglose = $this->calc->calcular($experiencia, complementos: [$complemento->id => 2], fechaEvento: '2026-07-15');

    // 30 (override) x 2, no 50.
    expect($desglose->totalComplementos)->toBe(60.0)
        ->and($desglose->total)->toBe(460.0)
        ->and($desglose->lineasComplementos[0]['precio_unitario'])->toBe(30.0);
});

it('ignora complementos que la experiencia no ofrece (defensa anti-manipulación)', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    $categoria = CategoriaComplemento::factory()->create();
    $ajeno = Complemento::factory()->create(['categoria_id' => $categoria->id, 'precio' => 999]);

    $desglose = $this->calc->calcular($experiencia, complementos: [$ajeno->id => 1], fechaEvento: '2026-07-15');

    expect($desglose->totalComplementos)->toBe(0.0)
        ->and($desglose->total)->toBe(400.0);
});

it('acota la cantidad de un complemento a su cantidad_maxima', function () {
    [$experiencia, $complemento] = experienciaConComplemento(400, 50); // cantidad_maxima = 5

    // Petición manipulada con 99 unidades → se cobra como 5.
    $desglose = $this->calc->calcular($experiencia, complementos: [$complemento->id => 99], fechaEvento: '2026-07-15');

    expect($desglose->totalComplementos)->toBe(250.0) // 50 x 5
        ->and($desglose->lineasComplementos[0]['cantidad'])->toBe(5);
});

it('ignora complementos inactivos', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    $categoria = CategoriaComplemento::factory()->create();
    $complemento = Complemento::factory()->create(['categoria_id' => $categoria->id, 'precio' => 50, 'activo' => false]);
    $experiencia->complementos()->attach($complemento->id, ['cantidad_maxima' => 3]);

    $desglose = $this->calc->calcular($experiencia, complementos: [$complemento->id => 1], fechaEvento: '2026-07-15');

    expect($desglose->totalComplementos)->toBe(0.0);
});

it('parte del precio cerrado del pack en lugar del precio base de la experiencia', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    $pack = Pack::factory()->create(['experiencia_id' => $experiencia->id, 'precio' => 500]);

    $desglose = $this->calc->calcular($experiencia, pack: $pack, fechaEvento: '2026-07-15');

    expect($desglose->subtotal)->toBe(500.0)
        ->and($desglose->esPack)->toBeTrue()
        ->and($desglose->total)->toBe(500.0);
});

it('los complementos extra se suman aparte del precio del pack', function () {
    [$experiencia, $complemento] = experienciaConComplemento(400, 50);
    $pack = Pack::factory()->create(['experiencia_id' => $experiencia->id, 'precio' => 500]);

    $desglose = $this->calc->calcular(
        $experiencia,
        pack: $pack,
        complementos: [$complemento->id => 1],
        fechaEvento: '2026-07-15',
    );

    expect($desglose->subtotal)->toBe(500.0)
        ->and($desglose->totalComplementos)->toBe(50.0)
        ->and($desglose->total)->toBe(550.0);
});

it('añade porte y montaje según la zona del concello', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    $zona = ZonaPorte::factory()->create(['precio_porte' => 50, 'precio_montaje' => 30]);
    ConcelloZona::factory()->create(['concello' => 'Arteixo', 'zona_id' => $zona->id]);

    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2026-07-15', concello: 'Arteixo');

    expect($desglose->porte)->toBe(50.0)
        ->and($desglose->montaje)->toBe(30.0)
        ->and($desglose->total)->toBe(480.0);
});

it('si el concello no está mapeado no añade porte ni montaje', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);

    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2026-07-15', concello: 'Desconocido');

    expect($desglose->porte)->toBe(0.0)
        ->and($desglose->montaje)->toBe(0.0);
});

// ---------------------------------------------------------------------------
// Horas extra
// ---------------------------------------------------------------------------

it('suma las horas extra al total con el precio por hora de la experiencia', function () {
    $experiencia = Experiencia::factory()->conHorasExtra(70)->create(['precio_base' => 450]);

    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2026-07-15', horasExtra: 2);

    expect($desglose->horasExtra)->toBe(2)
        ->and($desglose->importeHorasExtra)->toBe(140.0)
        ->and($desglose->total)->toBe(590.0)          // 450 + 140
        ->and($desglose->subtotal)->toBe(450.0);       // la base no cambia
});

it('ignora las horas extra si la experiencia no tiene precio por hora', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 450, 'precio_hora_extra' => null]);

    $desglose = $this->calc->calcular($experiencia, fechaEvento: '2026-07-15', horasExtra: 3);

    expect($desglose->horasExtra)->toBe(0)
        ->and($desglose->importeHorasExtra)->toBe(0.0)
        ->and($desglose->total)->toBe(450.0);
});

it('acota las horas extra al máximo y descarta valores negativos', function () {
    $experiencia = Experiencia::factory()->conHorasExtra(70)->create(['precio_base' => 450]);

    $tope = CalculadoraPrecioService::MAX_HORAS_EXTRA;

    expect($this->calc->calcular($experiencia, fechaEvento: '2026-07-15', horasExtra: 99)->horasExtra)->toBe($tope)
        ->and($this->calc->calcular($experiencia, fechaEvento: '2026-07-15', horasExtra: -5)->horasExtra)->toBe(0);
});

// ---------------------------------------------------------------------------
// Complementos "a consultar"
// ---------------------------------------------------------------------------

it('los complementos a consultar se listan aparte y NO suman al total', function () {
    $experiencia = Experiencia::factory()->create(['precio_base' => 400]);
    $categoria = CategoriaComplemento::factory()->create();
    $aConsultar = Complemento::factory()->aConsultar()->create(['categoria_id' => $categoria->id]);
    $experiencia->complementos()->attach($aConsultar->id, ['cantidad_maxima' => 3]);

    $desglose = $this->calc->calcular($experiencia, complementos: [$aConsultar->id => 2], fechaEvento: '2026-07-15');

    expect($desglose->totalComplementos)->toBe(0.0)
        ->and($desglose->total)->toBe(400.0)
        ->and($desglose->lineasComplementos)->toBeEmpty()
        ->and($desglose->tieneLineasAConsultar())->toBeTrue()
        ->and($desglose->lineasAConsultar[0]['complemento_id'])->toBe($aConsultar->id)
        ->and($desglose->lineasAConsultar[0]['cantidad'])->toBe(2);
});

it('un complemento a consultar no contamina el total de los que sí suman', function () {
    [$experiencia, $complemento] = experienciaConComplemento(400, 50);
    $categoria = CategoriaComplemento::factory()->create();
    $aConsultar = Complemento::factory()->aConsultar()->create(['categoria_id' => $categoria->id]);
    $experiencia->complementos()->attach($aConsultar->id, ['cantidad_maxima' => 1]);

    $desglose = $this->calc->calcular(
        $experiencia,
        complementos: [$complemento->id => 1, $aConsultar->id => 1],
        fechaEvento: '2026-07-15',
    );

    expect($desglose->totalComplementos)->toBe(50.0)
        ->and($desglose->total)->toBe(450.0)
        ->and($desglose->lineasComplementos)->toHaveCount(1)
        ->and($desglose->lineasAConsultar)->toHaveCount(1);
});

// ---------------------------------------------------------------------------
// Packs con base intercambiable
// ---------------------------------------------------------------------------

it('suma el suplemento de la máquina base elegida al precio del pack', function () {
    $base = Experiencia::factory()->create(['precio_base' => 450]);
    $alternativa = Experiencia::factory()->create(['precio_base' => 530]);
    $pack = Pack::factory()->create(['experiencia_id' => $base->id, 'precio' => 700]);
    $pack->basesDisponibles()->attach($alternativa->id, ['suplemento' => 80]);

    $desglose = $this->calc->calcular($alternativa, pack: $pack->fresh(), fechaEvento: '2026-07-15');

    expect($desglose->subtotal)->toBe(780.0)      // 700 + 80
        ->and($desglose->suplementoBase)->toBe(80.0)
        ->and($desglose->total)->toBe(780.0);
});

it('la máquina base por defecto del pack no lleva suplemento', function () {
    $base = Experiencia::factory()->create(['precio_base' => 450]);
    $alternativa = Experiencia::factory()->create(['precio_base' => 530]);
    $pack = Pack::factory()->create(['experiencia_id' => $base->id, 'precio' => 700]);
    $pack->basesDisponibles()->attach($alternativa->id, ['suplemento' => 80]);

    $desglose = $this->calc->calcular($base, pack: $pack->fresh(), fechaEvento: '2026-07-15');

    expect($desglose->subtotal)->toBe(700.0)
        ->and($desglose->suplementoBase)->toBe(0.0);
});

it('el ajuste de temporada se aplica sobre el pack con su suplemento', function () {
    $base = Experiencia::factory()->create(['precio_base' => 450]);
    $alternativa = Experiencia::factory()->create(['precio_base' => 530]);
    $pack = Pack::factory()->create(['experiencia_id' => $base->id, 'precio' => 700]);
    $pack->basesDisponibles()->attach($alternativa->id, ['suplemento' => 100]);
    Temporada::factory()->porcentaje(10)->rango('2026-06-01', '2026-09-15')->create();

    $desglose = $this->calc->calcular($alternativa, pack: $pack->fresh(), fechaEvento: '2026-07-15');

    // 10 % sobre 800 (700 + 100), no sobre 700.
    expect($desglose->subtotal)->toBe(800.0)
        ->and($desglose->ajusteTemporada)->toBe(80.0)
        ->and($desglose->total)->toBe(880.0);
});

it('devuelve el desglose completo combinando todas las líneas', function () {
    [$experiencia, $complemento] = experienciaConComplemento(400, 50, override: 40);
    Temporada::factory()->porcentaje(20)->rango('2026-06-01', '2026-09-15')->create(['nombre' => 'Alta']);
    $zona = ZonaPorte::factory()->create(['precio_porte' => 60, 'precio_montaje' => 40]);
    ConcelloZona::factory()->create(['concello' => 'A Coruña', 'zona_id' => $zona->id]);

    $desglose = $this->calc->calcular(
        $experiencia,
        complementos: [$complemento->id => 3],
        fechaEvento: '2026-07-15',
        concello: 'A Coruña',
    );

    // base 400 + temporada 80 + complementos 120 + porte 60 + montaje 40 = 700
    expect($desglose->subtotal)->toBe(400.0)
        ->and($desglose->ajusteTemporada)->toBe(80.0)
        ->and($desglose->totalComplementos)->toBe(120.0)
        ->and($desglose->porte)->toBe(60.0)
        ->and($desglose->montaje)->toBe(40.0)
        ->and($desglose->total)->toBe(700.0);

    expect($desglose->paraReserva())->toBe([
        'subtotal' => 400.0,
        'ajuste_temporada' => 80.0,
        'total_complementos' => 120.0,
        'porte' => 60.0,
        'montaje' => 40.0,
        'total' => 700.0,
        'horas_extra' => 0,
    ]);
});
