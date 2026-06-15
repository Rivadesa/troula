<?php

namespace App\Services;

use App\Enums\Turno;
use App\Models\Experiencia;
use App\Models\Reserva;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Comprueba si una experiencia está libre para una fecha y un turno.
 *
 * Reglas:
 *  - Solapamiento de turnos: ver App\Enums\Turno::solapaCon().
 *  - Si la experiencia no permite turnos, toda reserva se trata como `completo`.
 *  - Disponible si (reservas activas que solapan) < experiencia.unidades.
 *  - Cuentan como activas: solicitada, confirmada, pagada, realizada (no cancelada).
 */
class DisponibilidadService
{
    /**
     * ¿Hay al menos una unidad libre para esa fecha y turno?
     */
    public function estaDisponible(
        Experiencia $experiencia,
        CarbonInterface|string $fecha,
        Turno $turno = Turno::Completo,
        ?int $ignorarReservaId = null,
    ): bool {
        $turno = $this->normalizarTurno($experiencia, $turno);
        $reservasDia = $this->reservasDelDia($experiencia, $fecha, $ignorarReservaId);

        return $this->ocupadas($reservasDia, $turno) < $experiencia->unidades;
    }

    /**
     * Turnos que todavía admiten reserva ese día (para el configurador).
     *
     * @return array<int, Turno>
     */
    public function turnosDisponibles(
        Experiencia $experiencia,
        CarbonInterface|string $fecha,
        ?int $ignorarReservaId = null,
    ): array {
        $reservasDia = $this->reservasDelDia($experiencia, $fecha, $ignorarReservaId);

        $candidatos = $experiencia->permite_turnos
            ? [Turno::Manana, Turno::Tarde]
            : [Turno::Completo];

        return array_values(array_filter(
            $candidatos,
            fn (Turno $turno): bool => $this->ocupadas($reservasDia, $turno) < $experiencia->unidades,
        ));
    }

    /**
     * Fechas del rango que NO admiten ninguna reserva (ningún turno libre).
     * Las usa el datepicker del configurador para deshabilitar días.
     *
     * @return array<int, string> Lista de fechas 'Y-m-d' totalmente bloqueadas.
     */
    public function fechasNoDisponibles(
        Experiencia $experiencia,
        CarbonInterface|string $desde,
        CarbonInterface|string $hasta,
    ): array {
        $desde = $this->normalizarFecha($desde)->startOfDay();
        $hasta = $this->normalizarFecha($hasta)->startOfDay();

        $reservasPorDia = Reserva::query()
            ->activas()
            ->where('experiencia_id', $experiencia->id)
            ->whereBetween('fecha_evento', [$desde->toDateString(), $hasta->toDateString()])
            ->get(['fecha_evento', 'turno'])
            ->groupBy(fn (Reserva $reserva): string => $reserva->fecha_evento->toDateString());

        $candidatos = $experiencia->permite_turnos
            ? [Turno::Manana, Turno::Tarde]
            : [Turno::Completo];

        $bloqueadas = [];

        foreach ($reservasPorDia as $dia => $reservasDia) {
            $hayLibre = false;

            foreach ($candidatos as $turno) {
                if ($this->ocupadas($reservasDia, $turno) < $experiencia->unidades) {
                    $hayLibre = true;
                    break;
                }
            }

            if (! $hayLibre) {
                $bloqueadas[] = $dia;
            }
        }

        return $bloqueadas;
    }

    /**
     * Cuántas reservas del día solapan con el turno indicado.
     *
     * @param  Collection<int, Reserva>  $reservasDia
     */
    private function ocupadas(Collection $reservasDia, Turno $turno): int
    {
        return $reservasDia
            ->filter(fn (Reserva $reserva): bool => $reserva->turno->solapaCon($turno))
            ->count();
    }

    /**
     * @return Collection<int, Reserva>
     */
    private function reservasDelDia(
        Experiencia $experiencia,
        CarbonInterface|string $fecha,
        ?int $ignorarReservaId = null,
    ): Collection {
        $fecha = $this->normalizarFecha($fecha);

        return Reserva::query()
            ->activas()
            ->where('experiencia_id', $experiencia->id)
            ->whereDate('fecha_evento', $fecha->toDateString())
            ->when($ignorarReservaId !== null, fn ($query) => $query->whereKeyNot($ignorarReservaId))
            ->get(['id', 'turno']);
    }

    private function normalizarTurno(Experiencia $experiencia, Turno $turno): Turno
    {
        return $experiencia->permite_turnos ? $turno : Turno::Completo;
    }

    private function normalizarFecha(CarbonInterface|string $fecha): CarbonInterface
    {
        return $fecha instanceof CarbonInterface ? $fecha : Carbon::parse($fecha);
    }
}
