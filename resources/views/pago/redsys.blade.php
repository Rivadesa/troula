{{-- Formulario autoenviado al TPV de Redsys. El cliente solo lo ve un instante. --}}
<x-layouts::configurador :title="'Conectando con el banco · ' . $reserva->referencia">
    <div class="mx-auto max-w-md text-center">
        <div class="rounded-3xl border border-marca-100 bg-white p-10 shadow-sm">
            <div class="mx-auto mb-4 h-10 w-10 animate-spin rounded-full border-4 border-marca-200 border-t-marca-600"></div>
            <h1 class="text-lg font-bold text-gray-900">Te llevamos al pago seguro…</h1>
            <p class="mt-2 text-sm text-gray-500">
                Si no ocurre nada en unos segundos, pulsa el botón.
            </p>

            <form id="redsys" method="POST" action="{{ $formulario['url'] }}">
                @foreach ($formulario['campos'] as $nombre => $valor)
                    <input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">
                @endforeach
                <button type="submit"
                        class="mt-5 w-full rounded-full bg-marca-600 px-6 py-3 font-semibold uppercase tracking-wide text-white hover:bg-marca-700">
                    Ir al pago
                </button>
            </form>
        </div>
    </div>

    <script>document.getElementById('redsys').submit();</script>
</x-layouts::configurador>
