<?php

use App\Models\Configuracion;
use Illuminate\Support\Facades\DB;

it('guarda la contraseña SMTP cifrada y la lee descifrada', function () {
    $cfg = Configuracion::actual();
    $cfg->update(['mail_password' => 'secreta-123']);

    // En la BD se guarda cifrada (no en claro).
    $enBd = DB::table('configuracion')->where('id', $cfg->id)->value('mail_password');
    expect($enBd)->not->toBe('secreta-123');

    // Al leer por el modelo, se descifra.
    expect(Configuracion::actual()->fresh()->mail_password)->toBe('secreta-123');
});

it('aplica la configuración SMTP guardada a la config de correo en runtime', function () {
    Configuracion::actual()->update([
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.ejemplo.com',
        'mail_port' => 465,
        'mail_username' => 'usuario',
        'mail_password' => 'secreta',
        'mail_encryption' => 'ssl',
        'mail_from_address' => 'reservas@ejemplo.com',
        'mail_admin_address' => 'avisos@ejemplo.com',
    ]);

    Configuracion::actual()->fresh()->aplicarCorreo();

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.ejemplo.com')
        ->and(config('mail.mailers.smtp.port'))->toBe(465)
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtps')
        ->and(config('mail.from.address'))->toBe('reservas@ejemplo.com')
        ->and(config('mail.admin_address'))->toBe('avisos@ejemplo.com');
});

it('en modo log no toca el servidor SMTP', function () {
    Configuracion::actual()->update(['mail_mailer' => 'log']);

    Configuracion::actual()->fresh()->aplicarCorreo();

    expect(config('mail.default'))->toBe('log');
});
