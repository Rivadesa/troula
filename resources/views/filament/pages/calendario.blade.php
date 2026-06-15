@php
    use App\Enums\Turno;
    use App\Filament\Resources\ReservaResource;
    use Illuminate\Support\Str;

    $abreviaturaTurno = fn (Turno $t) => match ($t) {
        Turno::Manana => 'M',
        Turno::Tarde => 'T',
        Turno::Completo => 'D',
    };
@endphp

<x-filament-panels::page>
    {{-- Cabecera con navegación de meses --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-bold">{{ $tituloMes }}</h2>
        <div class="flex items-center gap-2">
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-left" wire:click="mesAnterior">
                Anterior
            </x-filament::button>
            <x-filament::button color="gray" size="sm" wire:click="irHoy">
                Hoy
            </x-filament::button>
            <x-filament::button color="gray" size="sm" icon="heroicon-m-chevron-right" icon-position="after" wire:click="mesSiguiente">
                Siguiente
            </x-filament::button>
        </div>
    </div>

    {{-- Cabecera de días de la semana --}}
    <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.25rem" class="mt-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-400">
        @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $dia)
            <div>{{ $dia }}</div>
        @endforeach
    </div>

    {{-- Rejilla del mes --}}
    <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.25rem" class="mt-1">
        @foreach ($dias as $dia)
            @php
                $clave = $dia->toDateString();
                $delMes = $dia->month === $mesActual;
                $esHoy = $clave === $hoy;
                $reservasDia = $reservas->get($clave);
            @endphp
            <div
                @style([
                    'min-height:7rem',
                    'padding:.375rem',
                    'border-radius:.5rem',
                    'border:1px solid ' . ($esHoy ? 'rgb(13 148 136)' : 'rgb(229 231 235)'),
                    'background:' . ($esHoy ? 'rgba(13,148,136,.06)' : '#fff'),
                    'opacity:.45' => ! $delMes,
                ])
            >
                <div class="mb-1 text-right text-xs {{ $esHoy ? 'font-bold text-primary-600' : 'text-gray-400' }}">{{ $dia->day }}</div>

                <div class="space-y-1">
                    @forelse ($reservasDia ?? [] as $reserva)
                        <a href="{{ ReservaResource::getUrl('view', ['record' => $reserva]) }}"
                           class="block"
                           title="{{ $reserva->referencia }} · {{ $reserva->turno->getLabel() }} · {{ $reserva->cliente_nombre }}">
                            <x-filament::badge :color="$reserva->estado->getColor()" size="sm">
                                <span class="font-bold">{{ $abreviaturaTurno($reserva->turno) }}</span>
                                {{ Str::limit($reserva->experiencia->nombre, 12) }}
                            </x-filament::badge>
                        </a>
                    @empty
                        {{-- sin reservas --}}
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Leyenda --}}
    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-gray-500">
        <span class="font-semibold uppercase tracking-wide">Estados:</span>
        @foreach (\App\Enums\EstadoReserva::cases() as $estado)
            <x-filament::badge :color="$estado->getColor()" size="sm">{{ $estado->getLabel() }}</x-filament::badge>
        @endforeach
        <span class="ml-auto">M = mañana · T = tarde · D = día completo</span>
    </div>
</x-filament-panels::page>
