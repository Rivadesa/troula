<x-layouts::configurador :title="'Contrato firmado · ' . $reserva->referencia">
    <div class="mx-auto max-w-3xl">
        <div class="overflow-hidden rounded-3xl border border-marca-100 bg-white shadow-sm print:border-0 print:shadow-none">
            <div class="bg-marca-600 px-6 py-4 print:bg-white">
                <h1 class="text-lg font-bold uppercase tracking-widest text-white print:text-gray-900">Contrato firmado</h1>
                <p class="mt-1 text-sm text-marca-100 print:text-gray-500">Reserva {{ $reserva->referencia }}</p>
            </div>

            <div class="p-6">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 print:border-0 print:bg-white print:p-0">
                    <pre class="whitespace-pre-wrap font-sans text-[13px] leading-relaxed text-gray-700">{{ $reserva->contrato_texto }}</pre>
                </div>

                {{-- Registro de la aceptación: es lo que da valor probatorio --}}
                <div class="mt-6 rounded-2xl border border-marca-200 bg-marca-50/50 p-5 text-sm">
                    <p class="font-bold uppercase tracking-wide text-marca-800">Registro de aceptación</p>
                    <dl class="mt-3 space-y-1 text-gray-600">
                        <div class="flex justify-between gap-4">
                            <dt>Aceptado por</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $reserva->cliente_nombre }}
                                @if ($reserva->cliente_dni) ({{ $reserva->cliente_dni }}) @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Fecha y hora</dt>
                            <dd class="text-right font-medium text-gray-800">{{ $reserva->contrato_aceptado_en->format('d/m/Y H:i:s') }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Dirección IP</dt>
                            <dd class="text-right font-mono text-gray-800">{{ $reserva->contrato_ip }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt>Huella del documento (SHA-256)</dt>
                            <dd class="break-all text-right font-mono text-[11px] text-gray-800">{{ $reserva->contrato_hash }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs text-gray-500">
                        La huella permite comprobar que este texto es exactamente el aceptado y que no
                        se ha modificado después.
                    </p>
                </div>

                <button type="button" onclick="window.print()"
                        class="mt-6 w-full rounded-full border border-gray-200 px-6 py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50 print:hidden">
                    Imprimir o guardar como PDF
                </button>
            </div>
        </div>
    </div>
</x-layouts::configurador>
