@php
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.') . ' €';
    $confirmado = $pago->estado === \App\Enums\EstadoPago::Pagado;
@endphp

<x-layouts::configurador :title="'Resultado del pago · ' . $reserva->referencia">
    <div class="mx-auto max-w-xl text-center">
        <div class="rounded-3xl border border-marca-100 bg-white p-10 shadow-sm">
            @if ($confirmado)
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-marca-100 text-3xl text-marca-700">✓</div>
                <h1 class="text-2xl font-bold text-gray-900">¡Señal pagada!</h1>
                <p class="mt-2 text-gray-600">
                    Hemos recibido {{ $eur($pago->importe) }} de la reserva
                    <span class="font-semibold text-marca-700">{{ $reserva->referencia }}</span>.
                    Te enviamos el justificante por email.
                </p>
            @elseif ($ok)
                {{-- El banco dijo que sí, pero la notificación aún no ha llegado. --}}
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-marca-100 text-3xl text-marca-700">⏳</div>
                <h1 class="text-2xl font-bold text-gray-900">Pago en proceso</h1>
                <p class="mt-2 text-gray-600">
                    El banco ha aceptado la operación y estamos terminando de confirmarla.
                    Puede tardar unos segundos; te avisamos por email en cuanto esté.
                </p>
            @else
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-full bg-acento-100 text-3xl text-acento-600">✕</div>
                <h1 class="text-2xl font-bold text-gray-900">El pago no se ha completado</h1>
                <p class="mt-2 text-gray-600">
                    No se ha cobrado nada. Tu fecha sigue reservada: puedes intentarlo otra vez
                    o pagar por transferencia.
                </p>
            @endif

            <p class="mt-6 text-xs text-gray-400">Reserva {{ $reserva->referencia }}</p>

            <a href="{{ route('configurador') }}"
               class="mt-6 inline-block rounded-full border border-gray-200 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50">
                Volver al inicio
            </a>
        </div>
    </div>
</x-layouts::configurador>
