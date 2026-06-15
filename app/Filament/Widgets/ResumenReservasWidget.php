<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoReserva;
use App\Models\Reserva;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ResumenReservasWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $solicitadas = Reserva::where('estado', EstadoReserva::Solicitada)->count();

        $proximas = Reserva::query()
            ->activas()
            ->whereDate('fecha_evento', '>=', today())
            ->count();

        // Importe de reservas activas con evento futuro (cartera comprometida).
        $cartera = Reserva::query()
            ->activas()
            ->whereDate('fecha_evento', '>=', today())
            ->sum('total');

        return [
            Stat::make('Solicitudes pendientes', $solicitadas)
                ->description('Leads sin confirmar')
                ->color($solicitadas > 0 ? 'warning' : 'gray'),
            Stat::make('Próximas reservas', $proximas)
                ->description('Eventos futuros activos')
                ->color('info'),
            Stat::make('Cartera comprometida', number_format((float) $cartera, 2, ',', '.').' €')
                ->description('Total de eventos futuros activos')
                ->color('success'),
        ];
    }
}
