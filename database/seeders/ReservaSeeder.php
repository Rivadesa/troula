<?php

namespace Database\Seeders;

use App\Enums\EstadoPago;
use App\Enums\EstadoReserva;
use App\Enums\TipoPago;
use App\Enums\Turno;
use App\Models\Complemento;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Reserva;
use App\Services\ReservaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservaSeeder extends Seeder
{
    public function run(ReservaService $reservas): void
    {
        /** @var array<string, int> $comp slug => id */
        $comp = Complemento::pluck('id', 'slug')->all();

        $fotomaton = Experiencia::where('slug', 'fotomaton-clasico')->firstOrFail();
        $espejo = Experiencia::where('slug', 'espejo-magico')->firstOrFail();
        $sofa = Experiencia::where('slug', 'photocall-sofa-decorado')->firstOrFail();
        $cabina = Experiencia::where('slug', 'cabina-360')->firstOrFail();
        $packBoda = Pack::where('slug', 'pack-boda-esencial')->firstOrFail();

        // 1. Fotomatón + Pack Boda Esencial, con extras. Confirmada.
        $reservas->crear([
            'experiencia_id' => $fotomaton->id,
            'pack_id' => $packBoda->id,
            'fecha_evento' => Carbon::now()->addDays(45)->toDateString(),
            'turno' => Turno::Manana,
            'concello' => 'Arteixo',
            'complementos' => [$comp['tira-fotos-extra'] => 2, $comp['fondo-lentejuelas'] => 1],
            'cliente_nombre' => 'Lucía Varela',
            'cliente_email' => 'lucia.varela@example.com',
            'cliente_telefono' => '600123456',
            'lugar_evento' => 'Pazo do Tambre',
            'observaciones' => 'Boda de tarde, montaje antes de las 13:00.',
            'estado' => EstadoReserva::Confirmada,
            'comprobar_disponibilidad' => false,
        ]);

        // 2. Espejo Mágico a la carta. Solicitada (lead reciente).
        $reservas->crear([
            'experiencia_id' => $espejo->id,
            'fecha_evento' => Carbon::now()->addDays(60)->toDateString(),
            'turno' => Turno::Tarde,
            'concello' => 'A Coruña',
            'complementos' => [
                $comp['impresiones-ilimitadas'] => 1,
                $comp['fondo-personalizado'] => 1,
                $comp['neon-personalizado'] => 1,
                $comp['album-recuerdos'] => 1,
            ],
            'cliente_nombre' => 'Marcos Pena',
            'cliente_email' => 'marcos.pena@example.com',
            'cliente_telefono' => '610987654',
            'lugar_evento' => 'Hotel Finisterre',
            'estado' => EstadoReserva::Solicitada,
            'comprobar_disponibilidad' => false,
        ]);

        // 3. Photocall Sofá. Pagada (con señal pagada + saldo pendiente).
        $reservaPagada = $reservas->crear([
            'experiencia_id' => $sofa->id,
            'fecha_evento' => Carbon::now()->addDays(30)->toDateString(),
            'concello' => 'Santiago de Compostela',
            'complementos' => [$comp['fondo-floral'] => 1, $comp['alfombra-roja'] => 1],
            'cliente_nombre' => 'Sara Iglesias',
            'cliente_email' => 'sara.iglesias@example.com',
            'cliente_telefono' => '620456789',
            'lugar_evento' => 'Finca Montesqueiro',
            'estado' => EstadoReserva::Pagada,
            'comprobar_disponibilidad' => false,
        ]);

        $senal = round((float) $reservaPagada->total * 0.3, 2);
        $reservaPagada->pagos()->createMany([
            [
                'tipo' => TipoPago::Senal,
                'importe' => $senal,
                'estado' => EstadoPago::Pagado,
                'pagado_en' => Carbon::now()->subDays(2),
            ],
            [
                'tipo' => TipoPago::Saldo,
                'importe' => round((float) $reservaPagada->total - $senal, 2),
                'estado' => EstadoPago::Pendiente,
            ],
        ]);

        // 4. Cabina 360 ya realizada (evento pasado).
        $reservas->crear([
            'experiencia_id' => $cabina->id,
            'fecha_evento' => Carbon::now()->subDays(20)->toDateString(),
            'concello' => 'Carballo',
            'complementos' => [$comp['neon-personalizado'] => 1, $comp['libro-firmas'] => 1],
            'cliente_nombre' => 'Diego Souto',
            'cliente_email' => 'diego.souto@example.com',
            'cliente_telefono' => '630111222',
            'lugar_evento' => 'Restaurante O Muíño',
            'estado' => EstadoReserva::Realizada,
            'comprobar_disponibilidad' => false,
        ]);

        // 5. Fotomatón cancelada.
        $reservas->crear([
            'experiencia_id' => $fotomaton->id,
            'fecha_evento' => Carbon::now()->addDays(90)->toDateString(),
            'turno' => Turno::Tarde,
            'concello' => 'Ferrol',
            'cliente_nombre' => 'Paula Refojo',
            'cliente_email' => 'paula.refojo@example.com',
            'cliente_telefono' => '640333444',
            'estado' => EstadoReserva::Cancelada,
            'comprobar_disponibilidad' => false,
        ]);

        // 6. Espejo Mágico, otra solicitada para llenar el calendario.
        $reservas->crear([
            'experiencia_id' => $espejo->id,
            'fecha_evento' => Carbon::now()->addDays(75)->toDateString(),
            'turno' => Turno::Manana,
            'concello' => 'Oleiros',
            'complementos' => [$comp['impresiones-ilimitadas'] => 1, $comp['alfombra-roja'] => 1],
            'cliente_nombre' => 'Carlos Failde',
            'cliente_email' => 'carlos.failde@example.com',
            'cliente_telefono' => '650555666',
            'lugar_evento' => 'Pazo de Vilaboa',
            'estado' => EstadoReserva::Confirmada,
            'comprobar_disponibilidad' => false,
        ]);

        $this->command?->info('Reservas de demo creadas: '.Reserva::count());
    }
}
