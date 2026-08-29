<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // La notificación de Redsys es un POST servidor a servidor: no lleva
        // token CSRF. Su autenticidad se comprueba con la firma HMAC del propio
        // mensaje (ver RedsysPasarela::firmaValida).
        $middleware->validateCsrfTokens(except: [
            'pagos/redsys/notificacion',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
