<?php

use App\Models\User;

beforeEach(function () {
    $this->seed();
});

it('un empleado no accede a los recursos de administración', function () {
    $this->actingAs(User::factory()->empleado()->create());

    $this->get('/admin/experiencias')->assertForbidden();
    $this->get('/admin/packs')->assertForbidden();
    $this->get('/admin/users')->assertForbidden();
    $this->get('/admin/clientes')->assertForbidden();
    $this->get('/admin/configuracion-empresa')->assertForbidden();
});

it('un empleado sí puede ver las reservas y el calendario', function () {
    $this->actingAs(User::factory()->empleado()->create());

    $this->get('/admin/reservas')->assertOk();
    $this->get('/admin/calendario')->assertOk();
});

it('un empleado no puede crear ni editar reservas', function () {
    $this->actingAs(User::factory()->empleado()->create());

    // La página de creación/edición de reservas queda fuera de su alcance.
    $this->get('/admin/reservas/create')->assertForbidden();
});

it('un administrador accede a todos los apartados', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin/experiencias')->assertOk();
    $this->get('/admin/users')->assertOk();
    $this->get('/admin/clientes')->assertOk();
    $this->get('/admin/reservas')->assertOk();
    $this->get('/admin/calendario')->assertOk();
    $this->get('/admin/configuracion-empresa')->assertOk();
});
