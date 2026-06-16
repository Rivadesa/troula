<?php

namespace App\Services;

use App\Enums\EstadoReserva;
use App\Enums\Turno;
use App\Exceptions\ExperienciaNoDisponibleException;
use App\Models\Cliente;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Reserva;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orquesta la creación de una reserva: valida disponibilidad, calcula el precio
 * con la CalculadoraPrecioService y congela importes y líneas de complementos.
 *
 * Reutilizado por el configurador del frontend y por los seeders. NO envía correos
 * (eso lo decide quien llama; ver el configurador en Fase 1).
 */
class ReservaService
{
    public function __construct(
        private readonly CalculadoraPrecioService $calculadora,
        private readonly DisponibilidadService $disponibilidad,
    ) {}

    /**
     * @param  array{
     *     experiencia_id:int,
     *     pack_id?:int|null,
     *     fecha_evento:string,
     *     turno?:Turno|string|null,
     *     concello:string,
     *     zona_id?:int|null,
     *     complementos?:array<int|string,int>,
     *     cliente_nombre:string,
     *     cliente_email:string,
     *     cliente_telefono:string,
     *     lugar_evento?:string|null,
     *     observaciones?:string|null,
     *     estado?:EstadoReserva,
     *     comprobar_disponibilidad?:bool
     *  }  $datos
     *
     * @throws ExperienciaNoDisponibleException
     */
    public function crear(array $datos): Reserva
    {
        $experiencia = Experiencia::findOrFail($datos['experiencia_id']);

        if (! $experiencia->activo) {
            throw ValidationException::withMessages([
                'experienciaId' => 'La experiencia seleccionada no está disponible.',
            ]);
        }

        $pack = ! empty($datos['pack_id']) ? Pack::find($datos['pack_id']) : null;
        // Descarta packs inválidos (inactivos o de otra experiencia): se trata como a la carta.
        if ($pack !== null && (! $pack->activo || $pack->experiencia_id !== $experiencia->id)) {
            $pack = null;
        }

        $turno = $this->resolverTurno($experiencia, $datos['turno'] ?? null);
        $complementos = $datos['complementos'] ?? [];

        $desglose = $this->calculadora->calcular(
            experiencia: $experiencia,
            pack: $pack,
            complementos: $complementos,
            fechaEvento: $datos['fecha_evento'],
            concello: $datos['concello'],
        );

        $zona = $this->calculadora->zonaPara($datos['concello']);

        // Lock atómico por experiencia + fecha: serializa la comprobación de disponibilidad y
        // la creación de la reserva para impedir dobles reservas en condiciones de carrera.
        $lock = Cache::lock('reserva:'.$experiencia->id.':'.$datos['fecha_evento'], 10);

        return $lock->block(5, function () use ($datos, $experiencia, $pack, $turno, $zona, $desglose): Reserva {
            if (($datos['comprobar_disponibilidad'] ?? true)
                && ! $this->disponibilidad->estaDisponible($experiencia, $datos['fecha_evento'], $turno)) {
                throw ExperienciaNoDisponibleException::paraFecha($experiencia->nombre, $datos['fecha_evento']);
            }

            return DB::transaction(function () use ($datos, $experiencia, $pack, $turno, $zona, $desglose): Reserva {
                // Registro de cliente (solo se conserva tras aceptar la LOPD).
                $cliente = $this->registrarCliente($datos);

                $reserva = Reserva::create([
                    'cliente_id' => $cliente?->id,
                    'experiencia_id' => $experiencia->id,
                    'pack_id' => $pack?->id,
                    'fecha_evento' => $datos['fecha_evento'],
                    'turno' => $turno,
                    'concello' => $datos['concello'],
                    'zona_id' => $zona?->id,
                    'cliente_nombre' => $datos['cliente_nombre'],
                    'cliente_email' => $datos['cliente_email'],
                    'cliente_telefono' => $datos['cliente_telefono'],
                    'lugar_evento' => $datos['lugar_evento'] ?? null,
                    'observaciones' => $datos['observaciones'] ?? null,
                    'estado' => $datos['estado'] ?? EstadoReserva::Solicitada,
                    ...$desglose->paraReserva(),
                ]);

                // Congela las líneas de complementos con su precio efectivo.
                foreach ($desglose->lineasComplementos as $linea) {
                    $reserva->complementos()->attach($linea['complemento_id'], [
                        'cantidad' => $linea['cantidad'],
                        'precio_congelado' => $linea['precio_unitario'],
                    ]);
                }

                return $reserva;
            });
        });
    }

    /**
     * Crea o actualiza el registro del cliente. Solo se conservan los datos si
     * el cliente ha aceptado la LOPD (el configurador lo exige como obligatorio;
     * por defecto se asume consentimiento para datos creados desde el backend).
     *
     * @param  array<string, mixed>  $datos
     */
    private function registrarCliente(array $datos): ?Cliente
    {
        if (! ($datos['acepto_lopd'] ?? true)) {
            return null;
        }

        return Cliente::updateOrCreate(
            ['email' => $datos['cliente_email']],
            [
                'nombre' => $datos['cliente_nombre'],
                'telefono' => $datos['cliente_telefono'],
                'acepto_lopd' => true,
                'consentimiento_en' => now(),
            ],
        );
    }

    private function resolverTurno(Experiencia $experiencia, Turno|string|null $turno): Turno
    {
        if (! $experiencia->permite_turnos) {
            return Turno::Completo;
        }

        if ($turno instanceof Turno) {
            return $turno;
        }

        return $turno !== null ? Turno::from($turno) : Turno::Completo;
    }
}
