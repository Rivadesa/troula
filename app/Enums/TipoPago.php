<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipoPago: string implements HasLabel
{
    case Senal = 'senal';
    case Saldo = 'saldo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Senal => 'Señal',
            self::Saldo => 'Saldo',
        };
    }
}
