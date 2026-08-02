{{--
    Tarjeta de un complemento en el paso 2 del configurador.

    Variables esperadas:
      $complemento  Complemento con el pivote de la experiencia cargado.
      $esGrupo      true si forma parte de un grupo "elige uno" (selección excluyente).
      $eur, $img    helpers definidos en la vista del configurador.
--}}
@php
    $esGrupo ??= false;
    $incluidoEnPack = in_array($complemento->id, $this->complementosIncluidosPack, true);
    $aConsultar = (bool) $complemento->a_consultar;
    $precioEfectivo = $complemento->pivot->precio_override ?? $complemento->precio;
    $estaSeleccionado = array_key_exists($complemento->id, $this->complementos);
    $obligatorio = (bool) $complemento->pivot->obligatorio;
    $maxima = (int) $complemento->pivot->cantidad_maxima;
@endphp

<div @class([
    'relative flex flex-col overflow-hidden rounded-2xl border-2 transition',
    'border-marca-500 ring-1 ring-marca-200' => $estaSeleccionado && ! $incluidoEnPack,
    'border-marca-200 bg-marca-50/40' => $incluidoEnPack,
    'border-gray-100 bg-white' => ! $estaSeleccionado && ! $incluidoEnPack,
])>
    <div class="flex h-24 items-center justify-center overflow-hidden bg-gradient-to-br from-gray-50 to-marca-50">
        @if ($img($complemento))
            <img src="{{ $img($complemento) }}" alt="{{ $complemento->nombre }}" class="h-full w-full object-cover">
        @else
            <span class="text-2xl text-marca-300">✦</span>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-3">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-800">{{ $complemento->nombre }}</p>

        @if ($incluidoEnPack)
            <p class="mt-1 text-sm font-semibold text-marca-700">Incluido</p>
        @elseif ($aConsultar)
            <p class="mt-1 text-sm font-semibold text-acento-600">A consultar</p>
        @else
            <p class="mt-1 text-sm font-semibold text-marca-700">+ {{ $eur($precioEfectivo) }}</p>
        @endif

        <div class="mt-auto pt-3">
            @if ($incluidoEnPack)
                <span class="block rounded-full bg-marca-100 py-1.5 text-center text-[11px] font-semibold uppercase tracking-wide text-marca-700">En el pack ✓</span>
            @elseif ($obligatorio)
                <span class="block rounded-full bg-marca-100 py-1.5 text-center text-[11px] font-semibold uppercase tracking-wide text-marca-700">Incluido ✓</span>
            @elseif (! $esGrupo && ! $aConsultar && $estaSeleccionado && $maxima > 1)
                <div class="flex items-center justify-center gap-2">
                    <input type="number" min="1" max="{{ $maxima }}" value="{{ $this->complementos[$complemento->id] }}"
                        wire:change="actualizarCantidad({{ $complemento->id }}, $event.target.value)"
                        class="w-14 rounded-lg border border-gray-200 px-2 py-1 text-center text-sm">
                    <button type="button" wire:click="alternarComplemento({{ $complemento->id }})" class="text-xs font-semibold text-acento-500 hover:text-acento-600">Quitar</button>
                </div>
            @else
                <button type="button" wire:click="alternarComplemento({{ $complemento->id }})"
                    @class([
                        'block w-full rounded-full py-1.5 text-[11px] font-semibold uppercase tracking-wide transition',
                        'bg-marca-600 text-white hover:bg-marca-700' => $estaSeleccionado,
                        'bg-gray-100 text-gray-600 hover:bg-gray-200' => ! $estaSeleccionado,
                    ])>
                    @if ($esGrupo)
                        {{ $estaSeleccionado ? 'Elegida ✓' : 'Elegir' }}
                    @else
                        {{ $estaSeleccionado ? 'Quitar ✓' : 'Añadir +' }}
                    @endif
                </button>
            @endif
        </div>
    </div>
</div>
