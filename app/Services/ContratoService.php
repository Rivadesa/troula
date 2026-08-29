<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Reserva;
use Illuminate\Support\Carbon;

/**
 * Rellena la plantilla del contrato con los datos de una reserva.
 *
 * La plantilla vive en `configuracion.contrato_plantilla` y la edita el admin.
 * Los huecos se escriben como {{clave}}; los que no existan se dejan vacíos
 * para que una plantilla mal escrita no muestre basura al cliente.
 */
class ContratoService
{
    /**
     * Campos disponibles en la plantilla, con su explicación (se muestran como
     * ayuda en el panel).
     *
     * @return array<string, string>
     */
    public static function campos(): array
    {
        return [
            'empresa' => 'Nombre comercial de la empresa',
            'titular_nombre' => 'Persona que firma por la empresa',
            'titular_dni' => 'DNI de esa persona',
            'empresa_direccion' => 'Dirección postal completa de la empresa',
            'empresa_email' => 'Email de contacto de la empresa',
            'cliente_nombre' => 'Nombre y apellidos del cliente',
            'cliente_dni' => 'DNI del cliente',
            'cliente_telefono' => 'Teléfono del cliente',
            'cliente_email' => 'Email del cliente',
            'cliente_direccion' => 'Dirección del cliente',
            'fecha_evento' => 'Fecha del evento',
            'concello' => 'Concello del evento',
            'lugar_evento' => 'Lugar concreto del evento',
            'servicio' => 'Detalle de lo contratado (se genera solo)',
            'total' => 'Importe total',
            'senal' => 'Importe de la señal',
            'resto' => 'Importe pendiente tras la señal',
            'iban' => 'IBAN para la transferencia',
            'titular_cuenta' => 'Titular de la cuenta bancaria',
            'referencia' => 'Referencia de la reserva',
            'fecha_hoy' => 'Fecha en que se firma',
        ];
    }

    /**
     * Texto del contrato para esta reserva.
     */
    public function generar(Reserva $reserva): string
    {
        $plantilla = (string) Configuracion::actual()->contrato_plantilla;

        if (trim($plantilla) === '') {
            return '';
        }

        $valores = $this->valores($reserva);

        return preg_replace_callback(
            '/\{\{\s*([a-z_]+)\s*\}\}/i',
            fn (array $c): string => (string) ($valores[strtolower($c[1])] ?? ''),
            $plantilla,
        ) ?? '';
    }

    /**
     * Huella del texto, para poder demostrar más adelante que el documento
     * aceptado es exactamente este y no se ha tocado.
     */
    public function hash(string $texto): string
    {
        return hash('sha256', $texto);
    }

    /**
     * @return array<string, string>
     */
    private function valores(Reserva $reserva): array
    {
        $config = Configuracion::actual();
        $reserva->loadMissing('experiencia', 'pack', 'complementos');

        $senal = (float) ($reserva->pagos()->where('tipo', \App\Enums\TipoPago::Senal)->value('importe') ?? 0);
        $total = (float) $reserva->total;

        return [
            'empresa' => (string) $config->nombre,
            'titular_nombre' => (string) $config->titular_nombre,
            'titular_dni' => (string) $config->titular_dni,
            'empresa_direccion' => $this->direccionEmpresa($config),
            'empresa_email' => (string) $config->email,

            'cliente_nombre' => (string) $reserva->cliente_nombre,
            'cliente_dni' => (string) $reserva->cliente_dni,
            'cliente_telefono' => (string) $reserva->cliente_telefono,
            'cliente_email' => (string) $reserva->cliente_email,
            'cliente_direccion' => (string) $reserva->cliente_direccion,

            'fecha_evento' => $reserva->fecha_evento->translatedFormat('j \d\e F \d\e Y'),
            'concello' => (string) $reserva->concello,
            // El lugar concreto es opcional en el formulario: si no lo han puesto,
            // el contrato indica al menos el concello en vez de dejar un hueco.
            'lugar_evento' => filled($reserva->lugar_evento)
                ? $reserva->lugar_evento.' ('.$reserva->concello.')'
                : (string) $reserva->concello,
            'servicio' => $this->detalleServicio($reserva),

            'total' => $this->eur($total),
            'senal' => $this->eur($senal),
            'resto' => $this->eur(max(0, $total - $senal)),
            'iban' => (string) $config->pago_iban,
            'titular_cuenta' => (string) $config->pago_titular,

            'referencia' => (string) $reserva->referencia,
            'fecha_hoy' => Carbon::now()->translatedFormat('j \d\e F \d\e Y'),
        ];
    }

    /**
     * Desglose de lo contratado. Incluye TODAS las partidas que componen el
     * total (también el ajuste de temporada): si alguna faltara, las cifras del
     * contrato no cuadrarían al sumarlas y eso es justo lo que no puede pasar
     * en un documento que el cliente firma.
     */
    private function detalleServicio(Reserva $reserva): string
    {
        $lineas = [];

        $lineas[] = $reserva->pack !== null
            ? '- '.$reserva->pack->nombre.' (incluye '.$reserva->experiencia->nombre.') — '.$this->eur((float) $reserva->subtotal)
            : '- '.$reserva->experiencia->nombre.' — '.$this->eur((float) $reserva->subtotal);

        if ($reserva->horas_extra > 0) {
            $lineas[] = '- '.$reserva->horas_extra.' hora(s) extra de servicio';
        }

        foreach ($reserva->complementos as $complemento) {
            $cantidad = (int) $complemento->pivot->cantidad;
            $precio = (float) $complemento->pivot->precio_congelado;

            $lineas[] = '- '.($cantidad > 1 ? $cantidad.'x ' : '').$complemento->nombre
                .($precio > 0 ? ' — '.$this->eur($precio * $cantidad) : ' — incluido');
        }

        $ajuste = (float) $reserva->ajuste_temporada;
        if ($ajuste != 0.0) {
            $lineas[] = '- '.($ajuste < 0 ? 'Descuento' : 'Recargo').' de temporada — '
                .($ajuste > 0 ? '+' : '−').$this->eur(abs($ajuste));
        }

        if ((float) $reserva->porte > 0 || (float) $reserva->montaje > 0) {
            $lineas[] = '- Porte y montaje — '.$this->eur((float) $reserva->porte + (float) $reserva->montaje);
        }

        return implode("\n", $lineas);
    }

    private function direccionEmpresa(Configuracion $config): string
    {
        return trim(implode(', ', array_filter([
            $config->direccion,
            trim(($config->codigo_postal ?? '').' '.($config->ciudad ?? '')),
        ])), ' ,');
    }

    private function eur(float $importe): string
    {
        return number_format($importe, 2, ',', '.').' €';
    }
}
