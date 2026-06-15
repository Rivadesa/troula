<?php

use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Reserva;
use App\Models\User;
use App\Models\ZonaPorte;

beforeEach(function () {
    $this->seed();
    $this->actingAs(User::where('email', 'admin@troula.test')->firstOrFail());
});

it('carga el dashboard del panel', function () {
    $this->get('/admin')->assertOk();
});

it('carga los listados de cada recurso', function () {
    $urls = [
        '/admin/experiencias',
        '/admin/categoria-complementos',
        '/admin/complementos',
        '/admin/packs',
        '/admin/temporadas',
        '/admin/zona-portes',
        '/admin/concello-zonas',
        '/admin/reservas',
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }
});

it('carga la vista de detalle de una reserva (infolist con desglose, complementos y pagos)', function () {
    $reserva = Reserva::has('complementos')->has('pagos')->firstOrFail();

    $this->get("/admin/reservas/{$reserva->id}")->assertOk();
});

it('carga la edición de una experiencia (formulario + gestor de complementos)', function () {
    $experiencia = Experiencia::firstOrFail();

    $this->get("/admin/experiencias/{$experiencia->id}/edit")->assertOk();
});

it('carga la edición de un pack y de una zona de porte', function () {
    $pack = Pack::firstOrFail();
    $zona = ZonaPorte::firstOrFail();

    $this->get("/admin/packs/{$pack->id}/edit")->assertOk();
    $this->get("/admin/zona-portes/{$zona->id}/edit")->assertOk();
});
