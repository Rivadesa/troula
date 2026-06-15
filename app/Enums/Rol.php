<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Rol: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Empleado = 'empleado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Empleado => 'Empleado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'success',
            self::Empleado => 'info',
        };
    }
}
