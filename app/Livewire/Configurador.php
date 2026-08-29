<?php

namespace App\Livewire;

use App\Enums\TipoPago;
use App\Enums\Turno;
use App\Exceptions\ExperienciaNoDisponibleException;
use App\Mail\NuevaReservaMail;
use App\Models\Complemento;
use App\Models\ConcelloZona;
use App\Models\Experiencia;
use App\Models\Pack;
use App\Models\Reserva;
use App\Services\CalculadoraPrecioService;
use App\Services\DesglosePrecio;
use App\Services\DisponibilidadService;
use App\Services\ReservaService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
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

    /** Horas adicionales sobre las que incluye la experiencia. */
    public int $horasExtra = 0;

    public ?string $fecha = null;

    public string $turno = Turno::Completo->value;

    public ?string $concello = null;

    public string $clienteNombre = '';

    public string $clienteEmail = '';

    public string $clienteTelefono = '';

    /** Datos que exige el contrato de prestación de servicios. */
    public string $clienteDni = '';

    public string $clienteDireccion = '';

    public ?string $lugarEvento = null;

    public ?string $observaciones = null;

    public bool $aceptoLopd = false;

    /** Honeypot anti-spam: campo oculto que solo rellenan los bots. */
    public string $website = '';

    // Estado final tras enviar.
    public ?string $referencia = null;

    /** URL firmada de la pantalla de pago de la señal (null si no se pide señal). */
    public ?string $urlPago = null;

    /** Importe de la señal de la reserva recién creada. */
    public ?float $importeSenal = null;

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

    /**
     * Packs que admiten la máquina elegida: los que la tienen como base por defecto
     * y los que la ofrecen como base alternativa (con suplemento).
     */
    #[Computed]
    public function packs(): Collection
    {
        if ($this->experiencia === null) {
            return collect();
        }

        $experienciaId = $this->experiencia->id;

        return Pack::query()
            ->where('activo', true)
            ->where(fn ($q) => $q
                ->where('experiencia_id', $experienciaId)
                ->orWhereHas('basesDisponibles', fn ($b) => $b->where('experiencias.id', $experienciaId)))
            ->with(['complementos', 'basesDisponibles', 'experiencia'])
            ->get();
    }

    #[Computed]
    public function pack(): ?Pack
    {
        if ($this->packId === null) {
            return null;
        }

        // Se busca por id (no dentro de `packs`) porque al cambiar la máquina base
        // el pack sigue siendo el mismo aunque cambie la experiencia seleccionada.
        $pack = Pack::query()
            ->where('activo', true)
            ->with(['complementos', 'basesDisponibles', 'experiencia'])
            ->find($this->packId);

        if ($pack === null || ! $pack->admiteBase($this->experienciaId)) {
            return null;
        }

        return $pack;
    }

    /**
     * Máquinas base que admite el pack elegido: la de serie (sin suplemento) más
     * las alternativas del pivote. Cada entrada lleva su suplemento ya resuelto.
     *
     * @return Collection<int, array{experiencia: Experiencia, suplemento: float}>
     */
    #[Computed]
    public function basesDelPack(): Collection
    {
        $pack = $this->pack;

        if ($pack === null) {
            return collect();
        }

        $bases = collect();

        if ($pack->experiencia !== null) {
            $bases->push(['experiencia' => $pack->experiencia, 'suplemento' => 0.0]);
        }

        foreach ($pack->basesDisponibles as $base) {
            if ($base->id === $pack->experiencia_id || ! $base->activo) {
                continue;
            }

            $bases->push(['experiencia' => $base, 'suplemento' => round((float) $base->pivot->suplemento, 2)]);
        }

        return $bases;
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

    /**
     * Concellos agrupados por provincia para el desplegable (313 en toda Galicia).
     *
     * @return Collection<string, Collection<int, string>>
     */
    #[Computed]
    public function concellos(): Collection
    {
        $porProvincia = ConcelloZona::query()
            ->orderBy('concello')
            ->get(['concello', 'provincia'])
            ->groupBy(fn (ConcelloZona $fila): string => $fila->provincia ?: 'Outros')
            ->map(fn (Collection $filas) => $filas->pluck('concello'));

        // Provincias en su orden natural; las que no estén en el catálogo van al final.
        return $porProvincia->sortBy(function (Collection $concellos, string $provincia): int {
            $posicion = array_search($provincia, ConcelloZona::PROVINCIAS, true);

            return $posicion === false ? PHP_INT_MAX : $posicion;
        });
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

    /**
     * Unidades libres de la máquina para la fecha elegida. null = aún no hay fecha.
     */
    #[Computed]
    public function unidadesLibres(): ?int
    {
        if ($this->experiencia === null || $this->fecha === null) {
            return null;
        }

        return app(DisponibilidadService::class)->unidadesLibres(
            $this->experiencia,
            $this->fecha,
            Turno::from($this->turno),
        );
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
            horasExtra: $this->horasExtra,
        );
    }

    /**
     * Complementos de la experiencia agrupados por categoría y, dentro de cada una,
     * separados en grupos "elige uno" (clave = nombre del grupo) y selección libre
     * (clave = cadena vacía).
     *
     * @return Collection<string, Collection<string, Collection<int, Complemento>>>
     */
    #[Computed]
    public function complementosAgrupados(): Collection
    {
        return $this->complementosPorCategoria->map(
            fn (Collection $complementos) => $complementos->groupBy(
                fn (Complemento $complemento): string => (string) ($complemento->pivot->grupo ?? ''),
            ),
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
            $this->horasExtra = 0;
            unset($this->experiencia);
            $this->preseleccionarIncluidos();
        }
    }

    /**
     * Cambia la máquina base del pack elegido conservando el pack. La fecha se
     * reinicia porque la disponibilidad se calcula por experiencia (cada máquina
     * tiene sus propias unidades).
     */
    public function cambiarBasePack(int $experienciaId): void
    {
        $pack = $this->pack;

        if ($pack === null || ! $pack->admiteBase($experienciaId) || $this->experienciaId === $experienciaId) {
            return;
        }

        $this->experienciaId = $experienciaId;
        $this->fecha = null;
        $this->turno = Turno::Completo->value;
        $this->horasExtra = 0;

        unset($this->experiencia, $this->pack, $this->basesDelPack);

        // Los extras se reinician: la máquina nueva ofrece otros complementos y
        // sus propias elecciones incluidas (tela, estructura, neón…).
        $this->preseleccionarIncluidos();
    }

    public function elegirPack(int $packId): void
    {
        $this->packId = $packId;
        // En modo pack los complementos del pack ya van incluidos: los extras
        // parten de cero, pero las elecciones de la máquina se mantienen.
        $this->preseleccionarIncluidos();

        unset($this->pack, $this->basesDelPack);
    }

    public function quitarPack(): void
    {
        $this->packId = null;
        $this->preseleccionarIncluidos();

        unset($this->pack, $this->basesDelPack);
    }

    public function alternarComplemento(int $complementoId): void
    {
        if (array_key_exists($complementoId, $this->complementos)) {
            // Una elección incluida (tela, estructura, neón del pack…) no se
            // puede dejar vacía: se cambia eligiendo otra del grupo.
            if ($this->esEleccionIncluida($complementoId)) {
                return;
            }

            unset($this->complementos[$complementoId]);

            return;
        }

        // Si pertenece a un grupo "elige uno", sustituye a sus hermanos.
        foreach ($this->hermanosDeGrupo($complementoId) as $hermanoId) {
            unset($this->complementos[$hermanoId]);
        }

        $this->complementos[$complementoId] = 1;
    }

    /**
     * Anula la reserva recién creada y devuelve el configurador a cero.
     *
     * Se borra de verdad: una reserva a medias que el cliente ha descartado no
     * debe quedar ni en el listado del comercio ni reteniendo la fecha. Solo se
     * permite mientras no haya un pago cobrado.
     */
    public function anularYEmpezar(): void
    {
        if ($this->referencia !== null) {
            $reserva = Reserva::where('referencia', $this->referencia)->first();

            if ($reserva !== null && $reserva->anulablePorElCliente()) {
                $reserva->pagos()->delete();
                $reserva->complementos()->detach();
                $reserva->delete();
            }
        }

        $this->reset();
        $this->resetValidation();
    }

    public function subirHoraExtra(): void
    {
        $this->actualizarHorasExtra($this->horasExtra + 1);
    }

    public function bajarHoraExtra(): void
    {
        $this->actualizarHorasExtra($this->horasExtra - 1);
    }

    public function actualizarHorasExtra(int $horas): void
    {
        if ($this->experiencia === null || ! $this->experiencia->admiteHorasExtra()) {
            $this->horasExtra = 0;

            return;
        }

        $this->horasExtra = max(0, min($horas, CalculadoraPrecioService::MAX_HORAS_EXTRA));
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
        unset($this->turnosDisponibles, $this->unidadesLibres);

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
        // Honeypot: si el campo trampa viene relleno, es un bot. Cortamos en silencio.
        if (filled($this->website)) {
            return;
        }

        // Rate limit por IP: máximo 5 envíos por minuto.
        $clave = 'reserva:'.request()->ip();
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            throw ValidationException::withMessages([
                'aceptoLopd' => 'Demasiados intentos. Espera un momento e inténtalo de nuevo.',
            ]);
        }
        RateLimiter::hit($clave, 60);

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
                'horas_extra' => $this->horasExtra,
                'concello' => $this->concello,
                'complementos' => $this->complementosExtras(),
                'cliente_nombre' => $this->clienteNombre,
                'cliente_email' => $this->clienteEmail,
                'cliente_telefono' => $this->clienteTelefono,
                'cliente_dni' => $this->clienteDni,
                'cliente_direccion' => $this->clienteDireccion,
                'lugar_evento' => $this->lugarEvento,
                'observaciones' => $this->observaciones,
                'acepto_lopd' => $this->aceptoLopd,
            ]);
        } catch (ExperienciaNoDisponibleException $e) {
            throw ValidationException::withMessages([
                'fecha' => $e->getMessage(),
            ]);
        }

        // Aviso al administrador (lead de Fase 1). Se encola en la BD.
        Mail::to(config('mail.admin_address'))->queue(new NuevaReservaMail($reserva));

        $this->referencia = $reserva->referencia;

        // Si la reserva lleva señal, se ofrece pagarla ya. La fecha queda retenida
        // desde este momento, así que el cliente puede pagar con calma.
        $senal = $reserva->pagos()->where('tipo', TipoPago::Senal)->first();

        if ($senal !== null) {
            $this->importeSenal = (float) $senal->importe;
            $this->urlPago = URL::signedRoute('pago.mostrar', ['reserva' => $reserva->referencia]);
        }

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

    /**
     * Deja el carrito con lo que la máquina trae de serie: los complementos
     * marcados como obligatorios y, de cada grupo "elige uno" cuya elección va
     * incluida en el precio (tela, lentejuelas, estructura, neón…), la primera
     * opción. Así el cliente nunca se queda sin una elección obligada, y puede
     * cambiarla con un clic.
     */
    private function preseleccionarIncluidos(): void
    {
        $this->complementos = [];

        if ($this->experiencia === null) {
            return;
        }

        $gruposYaElegidos = [];

        foreach ($this->experiencia->complementos as $complemento) {
            if ($complemento->pivot->obligatorio) {
                $this->complementos[$complemento->id] = 1;

                continue;
            }

            $grupo = $complemento->pivot->grupo;
            $precio = (float) ($complemento->pivot->precio_override ?? $complemento->precio);

            // Solo las elecciones sin coste: un grupo de pago (p. ej. neones
            // sueltos a 80 €) no se preselecciona, lo añade el cliente si quiere.
            if (blank($grupo) || $precio > 0 || in_array($grupo, $gruposYaElegidos, true)) {
                continue;
            }

            $this->complementos[$complemento->id] = 1;
            $gruposYaElegidos[] = $grupo;
        }
    }

    public function esObligatorio(int $complementoId): bool
    {
        $complemento = $this->experiencia?->complementos->firstWhere('id', $complementoId);

        return (bool) ($complemento?->pivot->obligatorio ?? false);
    }

    /**
     * ¿Es una opción de un grupo "elige uno" que además va incluida en el precio?
     * Esas son obligatorias de facto: hay que llevar una, sea cual sea.
     */
    public function esEleccionIncluida(int $complementoId): bool
    {
        $complemento = $this->experiencia?->complementos->firstWhere('id', $complementoId);

        if ($complemento === null || blank($complemento->pivot->grupo)) {
            return false;
        }

        return (float) ($complemento->pivot->precio_override ?? $complemento->precio) === 0.0;
    }

    /**
     * IDs de los demás complementos del mismo grupo "elige uno".
     *
     * @return array<int, int>
     */
    private function hermanosDeGrupo(int $complementoId): array
    {
        $complemento = $this->experiencia?->complementos->firstWhere('id', $complementoId);
        $grupo = $complemento?->pivot->grupo;

        if (blank($grupo)) {
            return [];
        }

        return $this->experiencia->complementos
            ->filter(fn (Complemento $otro): bool => $otro->pivot->grupo === $grupo && $otro->id !== $complementoId)
            ->pluck('id')
            ->all();
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
                'clienteNombre' => ['required', 'string', 'max:120'],
                'clienteEmail' => ['required', 'email:rfc', 'max:180'],
                'clienteTelefono' => ['required', 'string', 'max:30', 'regex:/^[0-9 +().\-]{6,30}$/'],
                // El contrato identifica al contratante: DNI y dirección son obligatorios.
                // No se comprueba la letra del DNI a propósito: hay clientes con NIE o
                // pasaporte y un formato demasiado estricto dejaría fuera reservas buenas.
                'clienteDni' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-]{5,20}$/'],
                'clienteDireccion' => ['required', 'string', 'max:200'],
                'aceptoLopd' => ['accepted'],
            ], [
                'clienteTelefono.regex' => 'Introduce un teléfono válido.',
                'clienteDni.regex' => 'Introduce un DNI, NIE o pasaporte válido (sin espacios).',
                'aceptoLopd.accepted' => 'Debes aceptar la política de privacidad para continuar.',
                // Los nombres legibles de los campos van en lang/es/validation.php.
            ]),
            default => null,
        };
    }

    private function validarEvento(): void
    {
        $this->validate([
            'fecha' => 'required|date|after_or_equal:today',
            'concello' => 'required|string|max:120|exists:concello_zona,concello',
            'lugarEvento' => 'nullable|string|max:180',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'fecha.required' => 'Selecciona la fecha del evento.',
            'fecha.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'concello.required' => 'Selecciona el concello del evento.',
            'concello.exists' => 'Selecciona un concello válido de la lista.',
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
