<?php

namespace App\Filament\Pages;

use App\Models\Reserva;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Calendario extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Reservas';

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?string $title = 'Calendario de reservas';

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.pages.calendario';

    public int $anyo;

    public int $mes;

    public function mount(): void
    {
        $hoy = Carbon::today();
        $this->anyo = $hoy->year;
        $this->mes = $hoy->month;
    }

    public function mesAnterior(): void
    {
        $this->moverMes(-1);
    }

    public function mesSiguiente(): void
    {
        $this->moverMes(1);
    }

    public function irHoy(): void
    {
        $hoy = Carbon::today();
        $this->anyo = $hoy->year;
        $this->mes = $hoy->month;
    }

    private function moverMes(int $delta): void
    {
        $fecha = Carbon::create($this->anyo, $this->mes, 1)->addMonths($delta);
        $this->anyo = $fecha->year;
        $this->mes = $fecha->month;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $inicioMes = Carbon::create($this->anyo, $this->mes, 1)->startOfDay();
        $finMes = $inicioMes->copy()->endOfMonth();

        /** @var Collection<string, Collection<int, Reserva>> $reservas */
        $reservas = Reserva::query()
            ->activas()
            ->with(['experiencia', 'pack'])
            ->whereBetween('fecha_evento', [$inicioMes->toDateString(), $finMes->toDateString()])
            ->orderBy('fecha_evento')
            ->get()
            ->groupBy(fn (Reserva $reserva): string => $reserva->fecha_evento->toDateString());

        // Rejilla de lunes a domingo que cubre el mes completo.
        $cursor = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $fin = $finMes->copy()->endOfWeek(Carbon::SUNDAY);

        $dias = [];
        while ($cursor->lte($fin)) {
            $dias[] = $cursor->copy();
            $cursor->addDay();
        }

        return [
            'tituloMes' => ucfirst($inicioMes->translatedFormat('F Y')),
            'mesActual' => $this->mes,
            'dias' => $dias,
            'reservas' => $reservas,
            'hoy' => Carbon::today()->toDateString(),
        ];
    }
}
