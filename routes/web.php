<?php

use App\Http\Controllers\ContratoController;
use App\Http\Controllers\PagoController;
use App\Livewire\Configurador;
use Illuminate\Support\Facades\Route;

// Configurador por pasos (frontend). Es la home pública.
Route::get('/', Configurador::class)->name('configurador');

// Página pública de política de privacidad (editable desde el panel).
Route::view('/privacidad', 'legal.privacidad')->name('privacidad');

/*
 * Cobro de la señal. El cliente no tiene cuenta, así que las pantallas van por
 * URL FIRMADA y la reserva se resuelve por su referencia (no por id).
 */
Route::middleware('signed')->group(function () {
    // El contrato va antes del pago: hay que firmarlo para seguir.
    Route::get('/reserva/{reserva:referencia}/contrato', [ContratoController::class, 'mostrar'])->name('contrato.mostrar');
    Route::post('/reserva/{reserva:referencia}/contrato', [ContratoController::class, 'aceptar'])->name('contrato.aceptar');
    Route::get('/reserva/{reserva:referencia}/contrato/firmado', [ContratoController::class, 'ver'])->name('contrato.ver');
    Route::get('/reserva/{reserva:referencia}/contrato/pdf', [ContratoController::class, 'pdf'])->name('contrato.pdf');

    Route::get('/reserva/{reserva:referencia}/pago', [PagoController::class, 'mostrar'])->name('pago.mostrar');
    Route::get('/reserva/{reserva:referencia}/pago/tarjeta', [PagoController::class, 'redsys'])->name('pago.redsys');
    Route::get('/reserva/{reserva:referencia}/pago/transferencia', [PagoController::class, 'transferencia'])->name('pago.transferencia');
});

// Vuelta del cliente desde el TPV: la firma no sobrevive al viaje por Redsys.
Route::get('/reserva/{reserva:referencia}/pago/{estado}', [PagoController::class, 'resultado'])
    ->whereIn('estado', ['ok', 'ko'])
    ->name('pago.resultado');

// Notificación servidor a servidor de Redsys (sin sesión ni CSRF).
Route::post('/pagos/redsys/notificacion', [PagoController::class, 'notificacion'])->name('pago.notificacion');
