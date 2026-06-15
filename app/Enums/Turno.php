<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Turno: string implements HasLabel
{
    case Manana = 'manana';
    case Tarde = 'tarde';
    case Completo = 'completo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manana => 'Mañana',
            self::Tarde => 'Tarde',
            self::Completo => 'Día completo',
        };
    }

    /**
     * Reglas de solapamiento de turno:
     * - `completo` solapa con `manana` y con `tarde`.
     * - `manana` solo con `manana` y `completo`.
     * - `tarde` solo con `tarde` y `completo`.
     */
    public function solapaCon(Turno $otro): bool
    {
        if ($this === self::Completo || $otro === self::Completo) {
            return true;
        }

        return $this === $otro;
    }
}
