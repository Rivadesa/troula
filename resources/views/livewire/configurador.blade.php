@php
    use App\Enums\Turno;
    use Illuminate\Support\Facades\Storage;
    $empresa = \App\Models\Configuracion::actual();
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.') . ' €';
    $img = fn ($m) => $m && $m->imagen ? Storage::url($m->imagen) : null;
    $pasos = [1 => 'Tu experiencia', 2 => 'Complementos', 3 => 'Datos del evento', 4 => 'Tus datos', 5 => 'Tu reserva'];
    $desglose = $this->desglose;
@endphp

<div>
    {{-- Pantalla final de agradecimiento --}}
    @if ($paso > \App\Livewire\Configurador::ULTIMO_PASO)
        <div class="mx-auto max-w-xl rounded-3xl border border-marca-100 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-marca-100 text-3xl text-marca-700">✓</div>
            <h1 class="text-2xl font-bold text-gray-900">¡Solicitud enviada!</h1>
            <p class="mt-2 text-gray-600">
                Hemos recibido tu solicitud con la referencia
                <span class="font-semibold text-marca-700">{{ $referencia }}</span>.
                @if ($urlPago)
                    Tu fecha ya está reservada a tu nombre.
                @else
                    Nos pondremos en contacto contigo muy pronto para confirmar la disponibilidad.
                @endif
            </p>

            @if ($urlPago)
                <div class="mt-6 rounded-2xl bg-marca-50 p-5">
                    @php $hayContrato = filled($empresa->contrato_plantilla); @endphp
                    <p class="text-sm text-gray-600">
                        Para dejarla cerrada, solo falta
                        @if ($hayContrato) firmar el contrato y @endif
                        abonar la señal de
                        <span class="font-bold text-marca-700">{{ number_format($importeSenal, 2, ',', '.') }} €</span>.
                    </p>
                    <a href="{{ $urlPago }}"
                       class="mt-4 inline-block rounded-full bg-marca-600 px-8 py-3 font-semibold uppercase tracking-wide text-white hover:bg-marca-700">
                        {{ $hayContrato ? 'Firmar y pagar' : 'Pagar la señal' }}
                    </a>
                    <p class="mt-3 text-xs text-gray-400">
                        También te enviamos este enlace por email, por si prefieres hacerlo más tarde.
                    </p>
                </div>
            @endif

            <a href="/" class="mt-6 inline-block rounded-full border border-gray-200 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                Configurar otro evento
            </a>
        </div>
    @else
        {{-- Barra de pasos --}}
        <ol class="mb-8 flex flex-wrap items-center justify-center gap-x-2 gap-y-2 text-xs font-semibold uppercase tracking-wider">
            @foreach ($pasos as $n => $titulo)
                <li class="flex items-center gap-2">
                    <span @class([
                        'grid h-6 w-6 place-items-center rounded-full text-[11px]',
                        'bg-marca-600 text-white' => $paso >= $n,
                        'bg-gray-200 text-gray-500' => $paso < $n,
                    ])>{{ $n }}</span>
                    <span @class(['text-marca-700' => $paso === $n, 'text-gray-400' => $paso !== $n])>{{ $titulo }}</span>
                    @if (! $loop->last)
                        <span class="text-gray-300">→</span>
                    @endif
                </li>
            @endforeach
        </ol>

        <div class="grid gap-8 lg:grid-cols-[1fr_22rem]">
            {{-- Columna principal --}}
            <div class="min-w-0">
                {{-- PASO 1: EXPERIENCIA (lista vertical desplazable: muestra ~5, scroll si hay más) --}}
                @if ($paso === 1)
                    @php $exps = $this->experienciasDisponibles->values(); @endphp
                    <h2 class="mb-1 text-2xl font-bold text-gray-900">Elige tu experiencia</h2>
                    <p class="mb-1 text-sm text-gray-500">El elemento principal de tu evento. Después añadirás packs y complementos.</p>
                    @if ($exps->count() > 5)
                        <p class="mb-4 text-xs font-medium text-marca-600">↕ Desplázate para ver las {{ $exps->count() }} experiencias</p>
                    @else
                        <div class="mb-4"></div>
                    @endif

                    {{-- Alto máximo ≈ 5 filas; si hay más, la lista se desplaza (no de una en una). --}}
                    <div class="max-h-[37rem] space-y-3 overflow-y-auto pr-1">
                        @foreach ($exps as $experiencia)
                            @php $seleccionada = $experienciaId === $experiencia->id; @endphp
                            <button type="button" wire:click="seleccionarExperiencia({{ $experiencia->id }})"
                                @class([
                                    'flex w-full items-stretch gap-4 overflow-hidden rounded-2xl border-2 bg-white text-left shadow-sm transition hover:shadow-md',
                                    'border-marca-500 ring-2 ring-marca-200' => $seleccionada,
                                    'border-transparent' => ! $seleccionada,
                                ])>
                                {{-- Imagen completa a la izquierda --}}
                                <div class="relative flex h-28 w-44 shrink-0 items-center justify-center overflow-hidden bg-gradient-to-br from-marca-100 via-marca-50 to-acento-100 p-2">
                                    @if ($img($experiencia))
                                        <img src="{{ $img($experiencia) }}" alt="{{ $experiencia->nombre }}" class="max-h-full max-w-full object-contain">
                                    @else
                                        <span class="text-4xl font-black text-marca-300">{{ Str::substr($experiencia->nombre, 0, 1) }}</span>
                                    @endif
                                    @if ($experiencia->permite_turnos)
                                        <span class="absolute left-2 top-2 rounded-full bg-white/90 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-marca-700">Mañana / Tarde</span>
                                    @endif
                                </div>
                                {{-- Texto a la derecha --}}
                                <div class="flex min-w-0 flex-1 flex-col justify-center py-3 pr-4">
                                    <h3 class="text-sm font-bold uppercase tracking-wide text-gray-900">{{ $experiencia->nombre }}</h3>
                                    <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $experiencia->descripcion }}</p>
                                    <p class="mt-2 text-lg font-black text-gray-900"><span class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Desde </span>{{ $eur($experiencia->precio_base) }}</p>
                                </div>
                                {{-- Estado de selección --}}
                                <div class="flex shrink-0 items-center pr-4">
                                    @if ($seleccionada)
                                        <span class="grid h-8 w-8 place-items-center rounded-full bg-marca-600 text-white">✓</span>
                                    @else
                                        <span class="grid h-8 w-8 place-items-center rounded-full bg-gray-100 text-gray-400">+</span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                    @error('experienciaId') <p class="mt-3 text-sm text-acento-600">{{ $message }}</p> @enderror
                @endif

                {{-- PASO 2: COMPLEMENTOS / PACK --}}
                @if ($paso === 2 && $this->experiencia)
                    <h2 class="mb-1 text-2xl font-bold text-gray-900">Personaliza tu {{ $this->experiencia->nombre }}</h2>
                    <p class="mb-5 text-sm text-gray-500">Elige un pack cerrado o monta tu configuración complemento a complemento.</p>

                    @if ($this->packs->isNotEmpty())
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Packs recomendados</p>
                        <div class="mb-7 grid gap-5 sm:grid-cols-2">
                            @foreach ($this->packs as $pack)
                                <div @class([
                                    'overflow-hidden rounded-3xl border-2 bg-white shadow-sm transition',
                                    'border-marca-500 ring-2 ring-marca-200' => $packId === $pack->id,
                                    'border-transparent' => $packId !== $pack->id,
                                ])>
                                    <div class="flex h-40 items-center justify-center overflow-hidden bg-gradient-to-br from-marca-100 to-acento-100">
                                        @if ($img($pack))
                                            <img src="{{ $img($pack) }}" alt="{{ $pack->nombre }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="text-4xl">🎁</span>
                                        @endif
                                    </div>
                                    <div class="p-5">
                                        @php $suplementoPack = $pack->suplementoPara($experienciaId); @endphp
                                        <div class="flex items-start justify-between gap-2">
                                            <h4 class="text-sm font-bold uppercase tracking-wide text-gray-900">{{ $pack->nombre }}</h4>
                                            <span class="whitespace-nowrap text-xl font-black text-gray-900">{{ $eur($pack->precio + $suplementoPack) }}</span>
                                        </div>
                                        @if ($suplementoPack > 0)
                                            <p class="mt-0.5 text-right text-[11px] font-medium text-gray-400">
                                                {{ $eur($pack->precio) }} + {{ $eur($suplementoPack) }} por {{ $this->experiencia->nombre }}
                                            </p>
                                        @endif
                                        <ul class="mt-3 space-y-1 text-sm text-gray-600">
                                            @foreach ($pack->complementos as $incluido)
                                                <li class="flex items-center gap-1.5"><span class="text-marca-500">✓</span> {{ $incluido->pivot->cantidad }}× {{ $incluido->nombre }}</li>
                                            @endforeach
                                        </ul>
                                        @if ($packId === $pack->id)
                                            <button type="button" wire:click="quitarPack" class="mt-4 w-full rounded-full border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">Quitar pack ✕</button>
                                        @else
                                            <button type="button" wire:click="elegirPack({{ $pack->id }})" class="mt-4 w-full rounded-full bg-marca-600 px-3 py-2 text-sm font-semibold uppercase tracking-wide text-white hover:bg-marca-700">Elegir este pack</button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        {{-- Máquina base del pack: el cliente puede cambiarla pagando el suplemento --}}
                        @if ($this->pack && $this->basesDelPack->count() > 1)
                            <div class="mb-7 rounded-2xl border border-marca-100 bg-white p-5">
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-800">Máquina del pack</p>
                                <p class="mt-1 text-sm text-gray-500">Cambia el fotomatón incluido en el {{ $this->pack->nombre }}.</p>
                                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                    @foreach ($this->basesDelPack as $base)
                                        @php $esActual = $experienciaId === $base['experiencia']->id; @endphp
                                        <button type="button" wire:click="cambiarBasePack({{ $base['experiencia']->id }})"
                                            @class([
                                                'flex items-center justify-between gap-3 rounded-xl border-2 px-4 py-3 text-left transition',
                                                'border-marca-500 bg-marca-50' => $esActual,
                                                'border-gray-100 bg-white hover:border-gray-200' => ! $esActual,
                                            ])>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-semibold text-gray-900">{{ $base['experiencia']->nombre }}</span>
                                                <span class="block text-xs text-gray-500">
                                                    {{ $base['suplemento'] > 0 ? '+ ' . $eur($base['suplemento']) : 'Incluida en el pack' }}
                                                </span>
                                            </span>
                                            <span @class([
                                                'grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs',
                                                'bg-marca-600 text-white' => $esActual,
                                                'bg-gray-100 text-gray-400' => ! $esActual,
                                            ])>{{ $esActual ? '✓' : '+' }}</span>
                                        </button>
                                    @endforeach
                                </div>
                                <p class="mt-3 text-xs text-gray-400">Al cambiar de máquina se reinician la fecha y los complementos extra.</p>
                            </div>
                        @endif

                        <div class="mb-5 flex items-center gap-3 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            <span class="h-px flex-1 bg-gray-200"></span>
                            {{ $packId ? 'Añade complementos extra' : 'O monta el tuyo' }}
                            <span class="h-px flex-1 bg-gray-200"></span>
                        </div>
                    @endif

                    {{-- Horas extra (solo si el admin ha puesto precio por hora en la experiencia) --}}
                    @if ($this->experiencia->admiteHorasExtra())
                        <div class="mb-7 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-marca-100 bg-marca-50/50 p-5">
                            <div>
                                <p class="text-sm font-bold uppercase tracking-wide text-gray-800">¿Quieres más horas?</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    Incluye {{ $this->experiencia->duracion_horas }} h de servicio.
                                    Cada hora extra: <span class="font-semibold text-marca-700">{{ $eur($this->experiencia->precio_hora_extra) }}</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="bajarHoraExtra" @disabled($horasExtra <= 0)
                                    class="grid h-10 w-10 place-items-center rounded-full border border-gray-200 bg-white text-lg font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-40">−</button>
                                <div class="w-24 text-center">
                                    <p class="text-2xl font-black text-gray-900">{{ $horasExtra }}</p>
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">horas extra</p>
                                </div>
                                <button type="button" wire:click="subirHoraExtra" @disabled($horasExtra >= \App\Services\CalculadoraPrecioService::MAX_HORAS_EXTRA)
                                    class="grid h-10 w-10 place-items-center rounded-full border border-gray-200 bg-white text-lg font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-40">+</button>
                            </div>
                        </div>
                    @endif

                    {{-- Complementos por categoría (paneles plegables) --}}
                    @foreach ($this->complementosAgrupados as $categoria => $grupos)
                        <div x-data="{ open: true }" class="mb-4 overflow-hidden rounded-2xl border border-gray-100 bg-white">
                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between px-5 py-3 text-left">
                                <span class="text-sm font-bold uppercase tracking-wider text-gray-700">{{ $categoria }}</span>
                                <span class="text-xs font-semibold uppercase tracking-wide text-marca-600" x-text="open ? 'Ver menos' : 'Ver más'"></span>
                            </button>
                            <div x-show="open" class="space-y-4 border-t border-gray-100 p-4">
                                @foreach ($grupos as $nombreGrupo => $complementos)
                                    @if ($nombreGrupo === '')
                                        {{-- Selección libre --}}
                                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($complementos as $complemento)
                                                @include('livewire.partials.complemento-card', ['complemento' => $complemento, 'esGrupo' => false])
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- Grupo "elige uno": las opciones se excluyen entre sí --}}
                                        <div class="rounded-2xl border border-dashed border-marca-200 bg-marca-50/30 p-3">
                                            <p class="mb-3 text-[11px] font-bold uppercase tracking-wider text-marca-700">
                                                Elige una opción · {{ $nombreGrupo }}
                                            </p>
                                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                @foreach ($complementos as $complemento)
                                                    @include('livewire.partials.complemento-card', ['complemento' => $complemento, 'esGrupo' => true])
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif

                {{-- PASO 3: DATOS DEL EVENTO --}}
                @if ($paso === 3 && $this->experiencia)
                    <h2 class="mb-5 text-2xl font-bold text-gray-900">¿Cuándo y dónde?</h2>
                    <div class="space-y-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Fecha del evento</label>
                            <div wire:ignore x-data="{
                                init() {
                                    flatpickr(this.$refs.fecha, {
                                        locale: 'es',
                                        dateFormat: 'Y-m-d',
                                        altInput: true,
                                        altFormat: 'j \\d\\e F \\d\\e Y',
                                        minDate: 'today',
                                        defaultDate: @js($fecha),
                                        disable: @js($this->fechasNoDisponibles),
                                        onChange: (sel, str) => { @this.set('fecha', str); },
                                    });
                                }
                            }">
                                <input x-ref="fecha" type="text" readonly placeholder="Selecciona una fecha"
                                    class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-marca-500 focus:ring-marca-500">
                            </div>
                            <p class="mt-1 text-xs text-gray-400">Las fechas sin disponibilidad aparecen deshabilitadas.</p>

                            {{-- Aviso explícito de unidades libres: el cliente ve si queda sitio
                                 antes de llegar al pago. --}}
                            @if ($fecha && $this->unidadesLibres !== null)
                                @if ($this->unidadesLibres > 0)
                                    <p class="mt-2 rounded-lg bg-marca-50 px-3 py-2 text-sm text-marca-700">
                                        ✓ Disponible:
                                        @if ($this->unidadesLibres === 1)
                                            queda <span class="font-semibold">1 unidad</span> para ese día.
                                        @else
                                            quedan <span class="font-semibold">{{ $this->unidadesLibres }} unidades</span> para ese día.
                                        @endif
                                    </p>
                                @else
                                    <p class="mt-2 rounded-lg bg-acento-100 px-3 py-2 text-sm text-acento-600">
                                        Sin disponibilidad para esa fecha. Elige otro día para poder continuar.
                                    </p>
                                @endif
                            @endif

                            @error('fecha') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                        </div>

                        @if ($this->experiencia->permite_turnos)
                            <div>
                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Turno</label>
                                @if (! $fecha)
                                    <p class="text-sm text-gray-400">Selecciona primero una fecha.</p>
                                @else
                                    @php $turnosDisp = collect($this->turnosDisponibles)->map->value->all(); @endphp
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ([Turno::Manana, Turno::Tarde] as $t)
                                            <label @class([
                                                'cursor-pointer rounded-full border-2 px-5 py-2 text-sm font-semibold',
                                                'border-marca-500 bg-marca-50 text-marca-700' => $turno === $t->value,
                                                'border-gray-200 text-gray-600' => $turno !== $t->value,
                                                'pointer-events-none opacity-40' => ! in_array($t->value, $turnosDisp, true),
                                            ])>
                                                <input type="radio" wire:model.live="turno" value="{{ $t->value }}" class="hidden" @disabled(! in_array($t->value, $turnosDisp, true))>
                                                {{ $t->getLabel() }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @if (empty($turnosDisp))
                                        <p class="mt-1 text-sm text-acento-600">No hay turnos disponibles ese día. Elige otra fecha.</p>
                                    @endif
                                @endif
                                @error('turno') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Concello del evento</label>
                            <select wire:model.live="concello" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-marca-500 focus:ring-marca-500">
                                <option value="">— Selecciona el concello —</option>
                                @foreach ($this->concellos as $provincia => $concellosProvincia)
                                    <optgroup label="{{ $provincia }}">
                                        @foreach ($concellosProvincia as $c)
                                            <option value="{{ $c }}">{{ $c }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-400">El porte y el montaje se calculan según el concello.</p>
                            @error('concello') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Lugar del evento <span class="normal-case text-gray-400">(opcional)</span></label>
                            <input type="text" wire:model="lugarEvento" placeholder="Pazo, finca, restaurante…"
                                class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-marca-500 focus:ring-marca-500">
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Observaciones <span class="normal-case text-gray-400">(opcional)</span></label>
                            <textarea wire:model="observaciones" rows="3" placeholder="Cuéntanos cualquier detalle…"
                                class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-marca-500 focus:ring-marca-500"></textarea>
                        </div>
                    </div>
                @endif

                {{-- PASO 4: DATOS PERSONALES --}}
                @if ($paso === 4)
                    <h2 class="mb-5 text-2xl font-bold text-gray-900">Tus datos de contacto</h2>
                    <div class="space-y-5 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        {{-- Honeypot anti-spam: oculto a personas; los bots lo rellenan. --}}
                        <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
                            <label>No rellenar este campo
                                <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
                            </label>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Nombre y apellidos</label>
                            <input type="text" wire:model="clienteNombre" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-marca-500 focus:ring-marca-500">
                            @error('clienteNombre') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Email</label>
                            <input type="email" wire:model="clienteEmail" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-marca-500 focus:ring-marca-500">
                            @error('clienteEmail') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500">Teléfono</label>
                            <input type="tel" wire:model="clienteTelefono" class="w-full rounded-xl border border-gray-200 px-4 py-3 focus:border-marca-500 focus:ring-marca-500">
                            @error('clienteTelefono') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Consentimiento LOPD (obligatorio para conservar los datos del cliente) --}}
                        <div class="rounded-xl bg-marca-50/60 p-4">
                            <label class="flex items-start gap-3">
                                <input type="checkbox" wire:model.live="aceptoLopd" class="mt-0.5 h-5 w-5 rounded border-gray-300 text-marca-600 focus:ring-marca-500">
                                @php $urlPrivacidad = $empresa->politica_privacidad_url ?: (filled($empresa->politica_privacidad) ? route('privacidad') : null); @endphp
                                <span class="text-sm text-gray-600">
                                    He leído y acepto la
                                    @if ($urlPrivacidad)
                                        <a href="{{ $urlPrivacidad }}" target="_blank" rel="noopener" class="font-medium text-marca-700 underline">política de privacidad</a>
                                    @else
                                        <span class="font-medium text-marca-700">política de privacidad</span>
                                    @endif
                                    y el tratamiento de mis datos para gestionar la reserva (LOPD/RGPD).
                                </span>
                            </label>
                            @error('aceptoLopd') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endif

                {{-- PASO 5: RESUMEN --}}
                @if ($paso === 5 && $desglose)
                    <h2 class="mb-5 text-2xl font-bold text-gray-900">Revisa tu solicitud</h2>
                    <div class="space-y-4 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400">Experiencia</p>
                                <p class="font-semibold text-gray-900">{{ $this->experiencia->nombre }}</p>
                            </div>
                            @if ($this->pack)
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-gray-400">Pack</p>
                                    <p class="font-semibold text-gray-900">{{ $this->pack->nombre }}</p>
                                </div>
                            @endif
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400">Fecha</p>
                                <p class="font-semibold text-gray-900">{{ \Illuminate\Support\Carbon::parse($fecha)->translatedFormat('d/m/Y') }} · {{ Turno::from($turno)->getLabel() }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400">Lugar</p>
                                <p class="font-semibold text-gray-900">{{ $concello }}@if ($lugarEvento) · {{ $lugarEvento }} @endif</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-400">Contacto</p>
                                <p class="font-semibold text-gray-900">{{ $clienteNombre }}</p>
                                <p class="text-sm text-gray-500">{{ $clienteEmail }} · {{ $clienteTelefono }}</p>
                            </div>
                        </div>

                        @if ($desglose->horasExtra > 0)
                            <div class="border-t border-gray-100 pt-3">
                                <p class="text-xs uppercase tracking-wide text-gray-400">Duración</p>
                                <p class="text-sm text-gray-600">
                                    {{ $this->experiencia->duracion_horas }} h incluidas + {{ $desglose->horasExtra }} h extra
                                    ({{ $eur($desglose->importeHorasExtra) }})
                                </p>
                            </div>
                        @endif

                        @if (! empty($desglose->lineasComplementos))
                            <div class="border-t border-gray-100 pt-3">
                                <p class="mb-1 text-xs uppercase tracking-wide text-gray-400">Complementos</p>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    @foreach ($desglose->lineasComplementos as $linea)
                                        <li class="flex justify-between"><span>{{ $linea['cantidad'] }}× {{ $linea['nombre'] }}</span><span>{{ $eur($linea['subtotal']) }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($desglose->tieneLineasAConsultar())
                            <div class="border-t border-gray-100 pt-3">
                                <p class="mb-1 text-xs uppercase tracking-wide text-gray-400">A presupuestar</p>
                                <ul class="space-y-1 text-sm text-gray-600">
                                    @foreach ($desglose->lineasAConsultar as $linea)
                                        <li class="flex justify-between">
                                            <span>{{ $linea['cantidad'] }}× {{ $linea['nombre'] }}</span>
                                            <span class="text-xs font-semibold uppercase tracking-wide text-acento-600">A consultar</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-2 text-xs text-gray-400">No suman al total: te llamamos para concretarlos.</p>
                            </div>
                        @endif

                        @error('fecha') <p class="rounded-lg bg-acento-100 px-3 py-2 text-sm text-acento-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Navegación --}}
                <div class="mt-6 flex items-center justify-between">
                    @if ($paso > 1)
                        <button type="button" wire:click="anterior" class="rounded-full border border-gray-200 px-5 py-3 text-sm font-semibold uppercase tracking-wide text-gray-600 hover:bg-gray-50">← Atrás</button>
                    @else
                        <span></span>
                    @endif

                    @if ($paso < \App\Livewire\Configurador::ULTIMO_PASO)
                        <button type="button" wire:click="siguiente" class="rounded-full bg-marca-600 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-white hover:bg-marca-700">Siguiente →</button>
                    @else
                        <button type="button" wire:click="enviar" wire:loading.attr="disabled"
                            class="rounded-full bg-marca-600 px-8 py-3 text-sm font-semibold uppercase tracking-wide text-white hover:bg-marca-700 disabled:opacity-60">
                            <span wire:loading.remove wire:target="enviar">Confirmar solicitud</span>
                            <span wire:loading wire:target="enviar">Enviando…</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Carrito lateral · TU RESERVA --}}
            <aside class="lg:sticky lg:top-6 lg:self-start">
                <div class="overflow-hidden rounded-3xl border border-marca-100 bg-white shadow-sm">
                    <div class="bg-marca-600 px-5 py-3">
                        <h3 class="text-sm font-bold uppercase tracking-widest text-white">Tu reserva</h3>
                    </div>

                    <div class="p-5">
                        @if ($this->experiencia)
                            <div class="mb-4 flex items-center gap-3">
                                <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-2xl bg-gradient-to-br from-marca-100 to-acento-100">
                                    @if ($img($this->experiencia))
                                        <img src="{{ $img($this->experiencia) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-lg font-black text-marca-400">{{ Str::substr($this->experiencia->nombre, 0, 1) }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold uppercase tracking-wide text-gray-900">{{ $this->experiencia->nombre }}</p>
                                    @if ($this->pack)
                                        <p class="truncate text-xs font-semibold text-marca-700">Pack · {{ $this->pack->nombre }}</p>
                                    @endif
                                </div>
                            </div>

                            @if ($desglose)
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">{{ $this->pack ? 'Pack' : 'Base' }}</dt>
                                        <dd class="font-semibold text-gray-800">{{ $eur($desglose->subtotal) }}</dd>
                                    </div>
                                    @if ($desglose->ajusteTemporada != 0)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Temporada{{ $desglose->temporadaNombre ? ' · ' . $desglose->temporadaNombre : '' }}</dt>
                                            <dd class="font-semibold {{ $desglose->ajusteTemporada < 0 ? 'text-marca-600' : 'text-gray-800' }}">{{ ($desglose->ajusteTemporada > 0 ? '+' : '') . $eur($desglose->ajusteTemporada) }}</dd>
                                        </div>
                                    @endif
                                    @if ($desglose->horasExtra > 0)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">{{ $desglose->horasExtra }}× hora extra</dt>
                                            <dd class="font-semibold text-gray-800">{{ $eur($desglose->importeHorasExtra) }}</dd>
                                        </div>
                                    @endif
                                    @foreach ($desglose->lineasComplementos as $linea)
                                        <div class="flex justify-between text-gray-600">
                                            <dt class="truncate pr-2">{{ $linea['cantidad'] }}× {{ $linea['nombre'] }}</dt>
                                            {{-- Las elecciones incluidas (tela, estructura, neón…) no cuestan aparte --}}
                                            <dd class="whitespace-nowrap {{ $linea['subtotal'] == 0 ? 'text-xs uppercase tracking-wide text-gray-400' : '' }}">
                                                {{ $linea['subtotal'] == 0 ? 'Incluida' : $eur($linea['subtotal']) }}
                                            </dd>
                                        </div>
                                    @endforeach
                                    @foreach ($desglose->lineasAConsultar as $linea)
                                        <div class="flex justify-between text-gray-600">
                                            <dt class="truncate pr-2">{{ $linea['cantidad'] }}× {{ $linea['nombre'] }}</dt>
                                            <dd class="whitespace-nowrap text-xs font-semibold uppercase tracking-wide text-acento-600">A consultar</dd>
                                        </div>
                                    @endforeach
                                    @if ($desglose->porte > 0 || $desglose->montaje > 0)
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Porte{{ $desglose->zonaNombre ? ' · ' . $desglose->zonaNombre : '' }}</dt>
                                            <dd class="font-semibold text-gray-800">{{ $eur($desglose->porte) }}</dd>
                                        </div>
                                        <div class="flex justify-between">
                                            <dt class="text-gray-500">Montaje</dt>
                                            <dd class="font-semibold text-gray-800">{{ $eur($desglose->montaje) }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                <div class="mt-4 flex items-end justify-between border-t border-dashed border-gray-200 pt-4">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total</span>
                                    <span class="text-3xl font-black text-marca-700">{{ $eur($desglose->total) }}</span>
                                </div>
                                @if ($desglose->tieneLineasAConsultar())
                                    <p class="mt-2 rounded-lg bg-acento-100/70 px-3 py-2 text-xs text-acento-700">
                                        Los elementos marcados «A consultar» no están incluidos en el total: te los presupuestamos aparte.
                                    </p>
                                @endif
                                <p class="mt-2 text-xs text-gray-400">Precio orientativo. Se confirma con la disponibilidad.</p>
                            @endif
                        @else
                            <p class="text-sm text-gray-400">Elige una experiencia para empezar a configurar tu evento.</p>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    @endif
</div>
