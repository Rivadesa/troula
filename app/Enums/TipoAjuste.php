<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoAjuste: string implements HasLabel
{
    case Porcentaje = 'porcentaje';
    case Fijo = 'fijo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Porcentaje => 'Porcentaje (%)',
            self::Fijo => 'Importe fijo (€)',
        };
    }
}
