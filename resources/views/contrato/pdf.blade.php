{{-- Contrato firmado en PDF (dompdf). CSS deliberadamente simple: dompdf no
     soporta flexbox ni grid, y la fuente DejaVu Sans es la que trae acentos. --}}
@php
    $empresa = \App\Models\Configuracion::actual();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Contrato {{ $reserva->referencia }}</title>
    <style>
        @page { margin: 2.2cm 2cm 2.6cm 2cm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; line-height: 1.45; color: #1f2937; }
        .cabecera { border-bottom: 1.5pt solid #0d9488; padding-bottom: 6pt; margin-bottom: 14pt; }
        .cabecera h1 { font-size: 12pt; margin: 0; color: #0f766e; }
        .cabecera .ref { font-size: 8.5pt; color: #6b7280; margin-top: 2pt; }
        /* Sin justificar: con white-space pre-wrap, dompdf justifica también las
           líneas terminadas en salto y las estira de lado a lado (la fecha, el
           bloque de firma...). Alineado a la izquierda se lee bien. */
        .contrato { white-space: pre-wrap; text-align: left; }
        .registro { margin-top: 18pt; border: 1pt solid #99f6e4; background: #f0fdfa; padding: 9pt; }
        .registro h2 { font-size: 9.5pt; margin: 0 0 5pt; color: #0f766e; text-transform: uppercase; }
        .registro table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .registro td { padding: 1.5pt 0; vertical-align: top; }
        .registro td.et { color: #6b7280; width: 32%; }
        .hash { font-family: 'DejaVu Sans Mono', monospace; font-size: 7pt; word-wrap: break-word; }
        .pie { position: fixed; bottom: -1.6cm; left: 0; right: 0; text-align: center;
               font-size: 7.5pt; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="pie">
        {{ $empresa->nombre }} · Contrato {{ $reserva->referencia }} ·
        Aceptado el {{ $reserva->contrato_aceptado_en->format('d/m/Y H:i') }}
    </div>

    <div class="cabecera">
        <h1>{{ $empresa->nombre }}</h1>
        <div class="ref">Contrato de prestación de servicios · Reserva {{ $reserva->referencia }}</div>
    </div>

    <div class="contrato">{{ $reserva->contrato_texto }}</div>

    <div class="registro">
        <h2>Registro de aceptación</h2>
        <table>
            <tr>
                <td class="et">Aceptado por</td>
                <td>{{ $reserva->cliente_nombre }}@if ($reserva->cliente_dni), DNI {{ $reserva->cliente_dni }}@endif</td>
            </tr>
            <tr>
                <td class="et">Fecha y hora</td>
                <td>{{ $reserva->contrato_aceptado_en->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td class="et">Dirección IP</td>
                <td>{{ $reserva->contrato_ip ?: '—' }}</td>
            </tr>
            <tr>
                <td class="et">Huella SHA-256</td>
                <td class="hash">{{ $reserva->contrato_hash }}</td>
            </tr>
        </table>
        <p style="margin: 6pt 0 0; font-size: 7.5pt; color: #6b7280;">
            La huella permite comprobar que el texto de este documento es exactamente
            el que se aceptó y que no ha sido modificado posteriormente.
        </p>
    </div>
</body>
</html>
