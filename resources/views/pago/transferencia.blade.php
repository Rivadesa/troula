@php
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.') . ' €';
@endphp

<x-layouts::configurador :title="'Pago por transferencia · ' . $reserva->referencia">
    <div class="mx-auto max-w-2xl">
        <div class="overflow-hidden rounded-3xl border border-marca-100 bg-white shadow-sm">
            <div class="bg-marca-600 px-6 py-4">
                <h1 class="text-lg font-bold uppercase tracking-widest text-white">Pago por transferencia</h1>
            </div>

            <div class="space-y-5 p-6">
                <p class="text-sm text-gray-600">
                    Haz la transferencia por el importe de la señal e indica la referencia
                    en el concepto. En cuanto la recibamos te confirmamos la reserva por email.
                </p>

                <dl class="divide-y divide-gray-100 rounded-2xl border border-gray-100">
                    <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Importe</dt>
                        <dd class="text-xl font-black text-marca-700">{{ $eur($pago->importe) }}</dd>
                    </div>
                    <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Concepto</dt>
                        <dd class="font-mono font-semibold text-gray-800">{{ $reserva->referencia }}</dd>
                    </div>
                    @if (filled($config->pago_iban))
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">IBAN</dt>
                            <dd class="break-all text-right font-mono font-semibold text-gray-800">{{ $config->pago_iban }}</dd>
                        </div>
                    @endif
                    @if (filled($config->pago_titular))
                        <div class="flex items-center justify-between px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-400">Titular</dt>
                            <dd class="font-semibold text-gray-800">{{ $config->pago_titular }}</dd>
                        </div>
                    @endif
                </dl>

                <p class="rounded-xl bg-marca-50 px-4 py-3 text-sm text-gray-600">
                    La fecha queda reservada a tu nombre mientras tanto. Si la transferencia no
                    llega, nos pondremos en contacto contigo antes de liberarla.
                </p>
            </div>
        </div>
    </div>
</x-layouts::configurador>
