<?php

use App\Filament\Resources\ConcelloZonaResource\Pages\ListConcelloZonas;
use App\Filament\Resources\ExperienciaResource\Pages\EditExperiencia;
use App\Filament\Resources\ExperienciaResource\RelationManagers\ComplementosRelationManager;
use App\Filament\Resources\PackResource\Pages\EditPack;
use App\Filament\Resources\PackResource\RelationManagers\BasesRelationManager;
use App\Models\ConcelloZona;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Reserva;
use App\Models\User;
use App\Models\ZonaPorte;
use Livewire\Livewire;

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

it('carga la página de configuración de empresa', function () {
    $this->get('/admin/configuracion-empresa')->assertOk();
});

it('carga la página de configuración de correo', function () {
    $this->get('/admin/configuracion-correo')->assertOk();
});

it('carga la página de política de privacidad del panel', function () {
    $this->get('/admin/pagina-privacidad')->assertOk();
});

it('carga la edición de un pack y de una zona de porte', function () {
    $pack = Pack::firstOrFail();
    $zona = ZonaPorte::firstOrFail();

    $this->get("/admin/packs/{$pack->id}/edit")->assertOk();
    $this->get("/admin/zona-portes/{$zona->id}/edit")->assertOk();
});

it('el gestor de máquinas base del pack lista las alternativas con su suplemento', function () {
    $pack = Pack::where('slug', 'pack-bronce')->firstOrFail();
    $espejo = Experiencia::where('slug', 'espejo-magico')->firstOrFail();

    Livewire::test(BasesRelationManager::class, [
        'ownerRecord' => $pack,
        'pageClass' => EditPack::class,
    ])
        ->assertOk()
        ->assertCanSeeTableRecords($pack->basesDisponibles)
        ->assertSee($espejo->nombre);
});

it('el gestor de complementos de la experiencia muestra la columna de grupo', function () {
    $experiencia = Experiencia::where('slug', 'fotomaton-solo')->firstOrFail();

    Livewire::test(ComplementosRelationManager::class, [
        'ownerRecord' => $experiencia,
        'pageClass' => EditExperiencia::class,
    ])
        ->assertOk()
        ->assertTableColumnExists('grupo');
});

it('los concellos se listan agrupados por provincia en el panel', function () {
    $this->get('/admin/concello-zonas')->assertOk();

    expect(ConcelloZona::whereNull('provincia')->count())->toBe(0)
        ->and(ConcelloZona::count())->toBe(313);
});

it('la acción masiva reasigna la zona de porte de varios concellos a la vez', function () {
    $zonaDestino = ZonaPorte::where('nombre', 'like', '%Ourense%')->firstOrFail();
    $lugo = ConcelloZona::where('provincia', 'Lugo')->limit(3)->get();

    Livewire::test(ListConcelloZonas::class)
        ->callTableBulkAction('asignar_zona', $lugo, data: ['zona_id' => $zonaDestino->id])
        ->assertHasNoTableBulkActionErrors();

    foreach ($lugo as $concello) {
        expect($concello->fresh()->zona_id)->toBe($zonaDestino->id);
    }
});
