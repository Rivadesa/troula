<?php

namespace App\Filament\Concerns;

/**
 * Restringe el acceso de un recurso o página de Filament a usuarios con rol
 * administrador. Los empleados no ven ni pueden abrir estos apartados.
 */
trait SoloAdministradores
{
    public static function canAccess(): bool
    {
        return auth()->user()?->esAdmin() ?? false;
    }
}
