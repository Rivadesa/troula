<?php

namespace App\Console\Commands;

use App\Enums\EstadoReserva;
use App\Models\Reserva;
use Illuminate\Console\Command;

/**
 * Marca como caducadas las reservas sin pagar cuya retención ha vencido.
 *
 * // OJO: esto es solo ORDEN, no disponibilidad. La fecha ya vuelve a estar
 * libre en cuanto pasa `reserva_expira_en`, porque el filtro va en la consulta
 * (Reserva::scopeOcupanDisponibilidad) y no depende de que esto se ejecute.
 * En este hosting no hay cron, así que el comando se lanza a mano o se engancha
 * al scheduler el día que lo haya.
 */
class CaducarReservas extends Command
{
    protected $signature = 'reservas:caducar {--dias=0 : Margen extra en días antes de caducar}';

    protected $description = 'Marca como caducadas las reservas sin pagar cuya retención ha vencido';

    public function handle(): int
    {
        $limite = now()->subDays((int) $this->option('dias'));

        $reservas = Reserva::query()
            ->where('estado', EstadoReserva::Solicitada)
            ->whereNotNull('reserva_expira_en')
            ->where('reserva_expira_en', '<', $limite)
            ->get();

        foreach ($reservas as $reserva) {
            // Defensa: si por lo que sea tiene un pago cobrado, no se toca.
            if (! $reserva->anulablePorElCliente()) {
                $this->warn("Saltada {$reserva->referencia}: tiene un pago cobrado.");

                continue;
            }

            $reserva->update(['estado' => EstadoReserva::Caducada]);
            $this->line("  {$reserva->referencia} · {$reserva->cliente_email} → caducada");
        }

        $this->info('Reservas caducadas: '.$reservas->count().'.');

        return self::SUCCESS;
    }
}
