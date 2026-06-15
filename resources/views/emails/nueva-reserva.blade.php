@php
    /** @var \App\Models\Reserva $reserva */
    $eur = fn ($n) => number_format((float) $n, 2, ',', '.').' €';
@endphp

<x-mail::message>
# Nueva reserva: {{ $reserva->referencia }}

Se ha recibido una nueva solicitud de reserva desde la web.

**Cliente:** {{ $reserva->cliente_nombre }}
**Email:** {{ $reserva->cliente_email }}
**Teléfono:** {{ $reserva->cliente_telefono }}

## Evento

- **Experiencia:** {{ $reserva->experiencia->nombre }}
@if ($reserva->pack)
- **Pack:** {{ $reserva->pack->nombre }}
@endif
- **Fecha:** {{ $reserva->fecha_evento->format('d/m/Y') }}
- **Turno:** {{ $reserva->turno->getLabel() }}
- **Concello:** {{ $reserva->concello }}
@if ($reserva->lugar_evento)
- **Lugar:** {{ $reserva->lugar_evento }}
@endif
@if ($reserva->observaciones)
- **Observaciones:** {{ $reserva->observaciones }}
@endif

@if ($reserva->complementos->isNotEmpty())
## Complementos

<x-mail::table>
| Complemento | Cantidad | Precio |
|:------------|:--------:|-------:|
@foreach ($reserva->complementos as $complemento)
| {{ $complemento->nombre }} | {{ $complemento->pivot->cantidad }} | {{ $eur($complemento->pivot->precio_congelado * $complemento->pivot->cantidad) }} |
@endforeach
</x-mail::table>
@endif

## Desglose (importes congelados)

<x-mail::table>
| Concepto | Importe |
|:---------|--------:|
| {{ $reserva->pack ? 'Pack' : 'Experiencia' }} | {{ $eur($reserva->subtotal) }} |
| Ajuste temporada | {{ $eur($reserva->ajuste_temporada) }} |
| Complementos | {{ $eur($reserva->total_complementos) }} |
| Porte | {{ $eur($reserva->porte) }} |
| Montaje | {{ $eur($reserva->montaje) }} |
| **Total** | **{{ $eur($reserva->total) }}** |
</x-mail::table>

<x-mail::button :url="config('app.url').'/admin/reservas/'.$reserva->id">
Ver reserva en el panel
</x-mail::button>

Estado actual: **{{ $reserva->estado->getLabel() }}**.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
