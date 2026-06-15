<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EstadoReserva: string implements HasColor, HasLabel
{
    case Solicitada = 'solicitada';
    case Confirmada = 'confirmada';
    case Pagada = 'pagada';
    case Realizada = 'realizada';
    case Cancelada = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Solicitada => 'Solicitada',
            self::Confirmada => 'Confirmada',
            self::Pagada => 'Pagada',
            self::Realizada => 'Realizada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Solicitada => 'warning',
            self::Confirmada => 'info',
            self::Pagada => 'success',
            self::Realizada => 'gray',
            self::Cancelada => 'danger',
        };
    }

    /**
     * Estados que ocupan disponibilidad (cuentan como reserva activa).
     * `cancelada` no cuenta.
     *
     * @return array<int, self>
     */
    public static function activas(): array
    {
        return [
            self::Solicitada,
            self::Confirmada,
            self::Pagada,
            self::Realizada,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function valoresActivos(): array
    {
        return array_map(fn (self $estado) => $estado->value, self::activas());
    }
}
