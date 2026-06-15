<?php

namespace App\Livewire;

use App\Enums\Turno;
use App\Exceptions\ExperienciaNoDisponibleException;
use App\Mail\NuevaReservaMail;
use App\Models\ConcelloZona;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Services\CalculadoraPrecioService;
use App\Services\DesglosePrecio;
use App\Services\DisponibilidadService;
use App\Services\ReservaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Configurador por pasos (wizard) del frontend.
 *
 * El cálculo de precio y la disponibilidad se delegan SIEMPRE en los servicios
 * (CalculadoraPrecioService / DisponibilidadService); no se duplica lógica en JS.
 */
#[Layout('layouts.configurador')]
#[Title('Configura tu evento · Troula')]
class Configurador extends Component
{
    public const ULTIMO_PASO = 5;

    public int $paso = 1;

    public ?int $experienciaId = null;

    public ?int $packId = null;

    /** @var array<int, int> Mapa [complemento_id => cantidad] de complementos EXTRA seleccionados. */
    public array $complementos = [];

    public ?string $fecha = null;

    public string $turno = Turno::Completo->value;

    public ?string $concello = null;

    public string $clienteNombre = '';

    public string $clienteEmail = '';

    public string $clienteTelefono = '';

    public ?string $lugarEvento = null;

    public ?string $observaciones = null;

    // Estado final tras enviar.
    public ?string $referencia = null;

    // ----------------------------------------------------------------------
    // Propiedades computadas (se reevalúan en cada render → total siempre vivo)
    // ----------------------------------------------------------------------

    #[Computed]
    public function experienciasDisponibles(): Collection
    {
        return Experiencia::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->get();
    }

    #[Computed]
    public function experiencia(): ?Experiencia
    {
        if ($this->experienciaId === null) {
            return null;
        }

        return Experiencia::query()
            ->with(['complementos' => fn ($q) => $q->where('activo', true), 'complementos.categoria'])
            ->find($this->experienciaId);
    }

    #[Computed]
    public function packs(): Collection
    {
        if ($this->experiencia === null) {
            return collect();
        }

        return $this->experiencia->packs()
            ->where('activo', true)
            ->with('complementos')
            ->get();
    }

    #[Computed]
    public function pack(): ?Pack
    {
        if ($this->packId === null) {
            return null;
        }

        return $this->packs->firstWhere('id', $this->packId);
    }

    /**
     * Complementos que ofrece la experiencia, agrupados por categoría y ordenados.
     */
    #[Computed]
    public function complementosPorCategoria(): Collection
    {
        if ($this->experiencia === null) {
            return collect();
        }

        return $this->experiencia->complementos
            ->sortBy('pivot.orden')
            ->groupBy(fn ($complemento) => $complemento->categoria->nombre);
    }

    /**
     * IDs de complementos incluidos en el pack elegido (no se cobran aparte).
     *
     * @return array<int, int>
     */
    #[Computed]
    public function complementosIncluidosPack(): array
    {
        return $this->pack?->complementos->pluck('id')->all() ?? [];
    }

    #[Computed]
    public function concellos(): Collection
    {
        return ConcelloZona::query()->orderBy('concello')->pluck('concello');
    }

    /**
     * Fechas sin ninguna disponibilidad, para deshabilitarlas en el datepicker.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function fechasNoDisponibles(): array
    {
        if ($this->experiencia === null) {
            return [];
        }

        return app(DisponibilidadService::class)->fechasNoDisponibles(
            $this->experiencia,
            Carbon::today(),
            Carbon::today()->addMonths(12),
        );
    }

    /**
     * Turnos disponibles para la fecha elegida.
     *
     * @return array<int, Turno>
     */
    #[Computed]
    public function turnosDisponibles(): array
    {
        if ($this->experiencia === null || $this->fecha === null) {
            return [];
        }

        return app(DisponibilidadService::class)->turnosDisponibles($this->experiencia, $this->fecha);
    }

    #[Computed]
    public function desglose(): ?DesglosePrecio
    {
        if ($this->experiencia === null) {
            return null;
        }

        return app(CalculadoraPrecioService::class)->calcular(
            experiencia: $this->experiencia,
            pack: $this->pack,
            complementos: $this->complementosExtras(),
            fechaEvento: $this->fecha,
            concello: $this->concello,
        );
    }

    // ----------------------------------------------------------------------
    // Acciones
    // ----------------------------------------------------------------------

    public function seleccionarExperiencia(int $experienciaId): void
    {
        if ($this->experienciaId !== $experienciaId) {
            $this->experienciaId = $experienciaId;
            $this->packId = null;
            $this->fecha = null;
            $this->turno = Turno::Completo->value;
            unset($this->experiencia);
            $this->preseleccionarObligatorios();
        }
    }

    public function elegirPack(int $packId): void
    {
        $this->packId = $packId;
        // En modo pack, los complementos del pack ya van incluidos; los extras parten de cero.
        $this->complementos = [];
    }

    public function quitarPack(): void
    {
        $this->packId = null;
        $this->preseleccionarObligatorios();
    }

    public function alternarComplemento(int $complementoId): void
    {
        if (array_key_exists($complementoId, $this->complementos)) {
            unset($this->complementos[$complementoId]);
        } else {
            $this->complementos[$complementoId] = 1;
        }
    }

    public function actualizarCantidad(int $complementoId, int $cantidad): void
    {
        $maxima = $this->cantidadMaxima($complementoId);
        $cantidad = max(0, min($cantidad, $maxima));

        if ($cantidad === 0) {
            unset($this->complementos[$complementoId]);

            return;
        }

        $this->complementos[$complementoId] = $cantidad;
    }

    public function updatedFecha(): void
    {
        unset($this->turnosDisponibles);

        if ($this->experiencia?->permite_turnos) {
            $disponibles = $this->turnosDisponibles;
            $this->turno = ($disponibles[0] ?? Turno::Completo)->value;
        } else {
            $this->turno = Turno::Completo->value;
        }
    }

    public function siguiente(): void
    {
        $this->validarPaso($this->paso);

        if ($this->paso < self::ULTIMO_PASO) {
            $this->paso++;
        }
    }

    public function anterior(): void
    {
        if ($this->paso > 1) {
            $this->paso--;
        }
    }

    public function enviar(ReservaService $reservas): void
    {
        // Revalida todos los pasos antes de crear la reserva.
        foreach (range(1, self::ULTIMO_PASO) as $paso) {
            $this->validarPaso($paso);
        }

        try {
            $reserva = $reservas->crear([
                'experiencia_id' => $this->experienciaId,
                'pack_id' => $this->packId,
                'fecha_evento' => $this->fecha,
                'turno' => $this->turno,
                'concello' => $this->concello,
                'complementos' => $this->complementosExtras(),
                'cliente_nombre' => $this->clienteNombre,
                'cliente_email' => $this->clienteEmail,
                'cliente_telefono' => $this->clienteTelefono,
                'lugar_evento' => $this->lugarEvento,
                'observaciones' => $this->observaciones,
            ]);
        } catch (ExperienciaNoDisponibleException $e) {
            throw ValidationException::withMessages([
                'fecha' => $e->getMessage(),
            ]);
        }

        // Aviso al administrador (lead de Fase 1). Se encola en la BD.
        Mail::to(config('mail.admin_address'))->queue(new NuevaReservaMail($reserva));

        $this->referencia = $reserva->referencia;
        $this->paso = self::ULTIMO_PASO + 1; // pantalla de "gracias"
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    /**
     * Complementos extra que se pasan al motor de precios (excluye los incluidos en el pack).
     *
     * @return array<int, int>
     */
    private function complementosExtras(): array
    {
        $incluidos = $this->complementosIncluidosPack;

        return collect($this->complementos)
            ->reject(fn ($cantidad, $id) => in_array((int) $id, $incluidos, true) || $cantidad < 1)
            ->all();
    }

    private function preseleccionarObligatorios(): void
    {
        $this->complementos = [];

        if ($this->experiencia === null) {
            return;
        }

        foreach ($this->experiencia->complementos as $complemento) {
            if ($complemento->pivot->obligatorio) {
                $this->complementos[$complemento->id] = 1;
            }
        }
    }

    public function esObligatorio(int $complementoId): bool
    {
        $complemento = $this->experiencia?->complementos->firstWhere('id', $complementoId);

        return (bool) ($complemento?->pivot->obligatorio ?? false);
    }

    private function cantidadMaxima(int $complementoId): int
    {
        $complemento = $this->experiencia?->complementos->firstWhere('id', $complementoId);

        return (int) ($complemento?->pivot->cantidad_maxima ?? 1);
    }

    private function validarPaso(int $paso): void
    {
        match ($paso) {
            1 => $this->validate(
                ['experienciaId' => 'required|exists:experiencias,id'],
                ['experienciaId.required' => 'Elige una experiencia para continuar.'],
            ),
            3 => $this->validarEvento(),
            4 => $this->validate([
                'clienteNombre' => 'required|string|max:255',
                'clienteEmail' => 'required|email|max:255',
                'clienteTelefono' => 'required|string|max:255',
            ], [], [
                'clienteNombre' => 'nombre',
                'clienteEmail' => 'email',
                'clienteTelefono' => 'teléfono',
            ]),
            default => null,
        };
    }

    private function validarEvento(): void
    {
        $this->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'concello' => 'required|string',
        ], [
            'fecha.required' => 'Selecciona la fecha del evento.',
            'fecha.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'concello.required' => 'Selecciona el concello del evento.',
        ]);

        if ($this->experiencia !== null && $this->experiencia->permite_turnos) {
            $disponibles = array_map(fn (Turno $t) => $t->value, $this->turnosDisponibles);

            if (! in_array($this->turno, $disponibles, true)) {
                throw ValidationException::withMessages([
                    'turno' => 'Ese turno no está disponible para la fecha elegida.',
                ]);
            }
        }
    }

    public function render()
    {
        return view('livewire.configurador');
    }
}
