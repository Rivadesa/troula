<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dinahosting no tiene worker permanente: la cola (driver `database`) se vacía
// desde el cron de `schedule:run`. Procesa los trabajos pendientes (p. ej. el email
// de aviso de nueva reserva) cada minuto y sale cuando no quedan.
Schedule::command('queue:work --stop-when-empty --max-time=55')
    ->everyMinute()
    ->withoutOverlapping();
