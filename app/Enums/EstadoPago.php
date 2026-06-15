<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoPago: string implements HasColor, HasLabel
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Fallido = 'fallido';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Pagado => 'Pagado',
            self::Fallido => 'Fallido',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Pagado => 'success',
            self::Fallido => 'danger',
        };
    }
}
