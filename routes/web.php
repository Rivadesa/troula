<?php

use App\Livewire\Configurador;
use Illuminate\Support\Facades\Route;

// Configurador por pasos (frontend). Es la home pública.
Route::get('/', Configurador::class)->name('configurador');
