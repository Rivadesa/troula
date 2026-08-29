<x-layouts::configurador :title="'Contrato · ' . $reserva->referencia">
    <div class="mx-auto max-w-3xl">
        <div class="overflow-hidden rounded-3xl border border-marca-100 bg-white shadow-sm">
            <div class="bg-marca-600 px-6 py-4">
                <h1 class="text-lg font-bold uppercase tracking-widest text-white">Contrato de prestación de servicios</h1>
                <p class="mt-1 text-sm text-marca-100">Reserva {{ $reserva->referencia }}</p>
            </div>

            <div class="p-6">
                <p class="mb-4 text-sm text-gray-600">
                    Lee el contrato y acéptalo para continuar con la reserva. Guardaremos una copia
                    exacta del texto que aceptas, junto con la fecha y hora.
                </p>

                {{-- Texto del contrato, en un panel con scroll --}}
                <div class="max-h-[28rem] overflow-y-auto rounded-2xl border border-gray-200 bg-gray-50 p-5">
                    <pre class="whitespace-pre-wrap font-sans text-[13px] leading-relaxed text-gray-700">{{ $texto }}</pre>
                </div>

                <form method="POST" action="{{ url()->full() }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="rounded-xl bg-marca-50/60 p-4">
                        <label class="flex items-start gap-3">
                            <input type="checkbox" name="acepto" value="1" @checked(old('acepto'))
                                   class="mt-0.5 h-5 w-5 rounded border-gray-300 text-marca-600 focus:ring-marca-500">
                            <span class="text-sm text-gray-600">
                                He leído y <strong>acepto el contrato</strong> de prestación de servicios en los
                                términos expuestos. Entiendo que esta aceptación tiene valor de firma.
                            </span>
                        </label>
                        @error('acepto') <p class="mt-1 text-sm text-acento-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit"
                            class="w-full rounded-full bg-marca-600 px-8 py-3 font-semibold uppercase tracking-wide text-white hover:bg-marca-700">
                        Aceptar y continuar al pago
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts::configurador>
