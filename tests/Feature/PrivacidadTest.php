<?php

use App\Models\Configuracion;

beforeEach(function () {
    $this->seed();
});

it('muestra la política de privacidad pública con su contenido', function () {
    Configuracion::actual()->update([
        'politica_privacidad' => '<h2>Mi política</h2><p>Tratamos tus datos con cariño.</p>',
    ]);

    $this->get('/privacidad')
        ->assertOk()
        ->assertSee('Mi política')
        ->assertSee('Tratamos tus datos con cariño.');
});

it('muestra un aviso cuando la política está vacía', function () {
    Configuracion::actual()->update(['politica_privacidad' => null]);

    $this->get('/privacidad')
        ->assertOk()
        ->assertSee('aún no tiene contenido');
});
