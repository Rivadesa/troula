@php
    use Illuminate\Support\Facades\URL;
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.') . ' €';
    $pagada = $pago->estado === \App\Enums\EstadoPago::Pagado;
@endphp

<x-layouts::configurador :title="'Pago de la señal · ' . $reserva->referencia">
    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-3xl border border-marca-100 bg-white shadow-sm">
            <div class="bg-marca-600 px-6 py-4">
                <h1 class="text-lg font-bold uppercase tracking-widest text-white">Reserva {{ $reserva->referencia }}</h1>
            </div>

            <div class="p-6">
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ $reserva->pack?->nombre ?? $reserva->experiencia->nombre }}</dt>
                        <dd class="font-semibold text-gray-800">{{ $eur($reserva->total) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Fecha del evento</dt>
                        <dd class="font-semibold text-gray-800">{{ $reserva->fecha_evento->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                <div class="mt-5 flex items-end justify-between border-t border-dashed border-gray-200 pt-5">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Señal a pagar ahora</span>
                    <span class="text-3xl font-black text-marca-700">{{ $eur($pago->importe) }}</span>
                </div>
                <p class="mt-1 text-right text-xs text-gray-400">
                    Resto a abonar antes del evento: {{ $eur($reserva->total - $pago->importe) }}
                </p>

                @if (filled($config->senal_texto))
                    <p class="mt-4 rounded-xl bg-marca-50 px-4 py-3 text-sm text-gray-600">{{ $config->senal_texto }}</p>
                @endif

                @if ($pagada)
                    <div class="mt-6 rounded-2xl bg-marca-100 p-5 text-center">
                        <p class="text-lg font-bold text-marca-800">Señal ya pagada ✓</p>
                        <p class="mt-1 text-sm text-marca-700">Recibida el {{ $pago->pagado_en?->format('d/m/Y H:i') }}. No hace falta hacer nada más.</p>
                    </div>
                @else
                    <p class="mt-6 text-xs font-semibold uppercase tracking-wider text-gray-400">Elige cómo pagar</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @if ($config->cobraConTarjeta())
                            <a href="{{ URL::signedRoute('pago.redsys', ['reserva' => $reserva->referencia]) }}"
                               class="rounded-2xl bg-marca-600 px-5 py-4 text-center font-semibold uppercase tracking-wide text-white transition hover:bg-marca-700">
                                Pagar con tarjeta
                                <span class="mt-1 block text-[11px] font-normal normal-case tracking-normal opacity-80">Pago seguro por TPV</span>
                            </a>
                        @endif

                        @if ($config->pago_transferencia)
                            <a href="{{ URL::signedRoute('pago.transferencia', ['reserva' => $reserva->referencia]) }}"
                               class="rounded-2xl border-2 border-marca-200 px-5 py-4 text-center font-semibold uppercase tracking-wide text-marca-700 transition hover:bg-marca-50">
                                Transferencia
                                <span class="mt-1 block text-[11px] font-normal normal-case tracking-normal text-gray-500">Te damos los datos bancarios</span>
                            </a>
                        @endif
                    </div>

                    @if (! $config->cobraConTarjeta() && ! $config->pago_transferencia)
                        <p class="mt-4 rounded-xl bg-acento-100 px-4 py-3 text-sm text-acento-600">
                            Ahora mismo no hay ningún método de pago activo. Ponte en contacto con nosotros y lo resolvemos.
                        </p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-layouts::configurador>
