<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPago;
use App\Enums\EstadoReserva;
use App\Enums\TipoPago;
use App\Models\Configuracion;
use App\Models\Pago;
use App\Models\Reserva;
use App\Services\Pagos\RedsysPasarela;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Cobro de la señal de una reserva: transferencia o TPV de Redsys.
 *
 * La pantalla de pago es pública pero va por URL FIRMADA (`signed`): el cliente
 * no tiene cuenta, y la referencia de la reserva sola sería adivinable.
 */
class PagoController extends Controller
{
    public function __construct(private readonly RedsysPasarela $redsys) {}

    /**
     * Pantalla de pago de la señal.
     */
    public function mostrar(Reserva $reserva)
    {
        $pago = $this->senalDe($reserva);

        if ($redirigir = $this->exigirContrato($reserva)) {
            return $redirigir;
        }

        return view('pago.mostrar', [
            'reserva' => $reserva->load('experiencia', 'pack'),
            'pago' => $pago,
            'config' => Configuracion::actual(),
        ]);
    }

    /**
     * Genera el formulario autoenviado hacia el TPV.
     */
    public function redsys(Reserva $reserva)
    {
        $pago = $this->senalDe($reserva);
        $config = Configuracion::actual();

        if ($redirigir = $this->exigirContrato($reserva)) {
            return $redirigir;
        }

        abort_unless($config->cobraConTarjeta(), 404, 'El pago con tarjeta no está disponible.');

        if ($pago->estado === EstadoPago::Pagado) {
            return redirect()->route('pago.resultado', ['reserva' => $reserva->referencia, 'estado' => 'ok']);
        }

        return view('pago.redsys', [
            'reserva' => $reserva,
            'formulario' => $this->redsys->iniciar($pago),
        ]);
    }

    /**
     * Notificación servidor a servidor de Redsys. Es la ÚNICA fuente fiable del
     * resultado: el retorno del navegador puede no llegar nunca.
     *
     * Sin CSRF (lo llama Redsys) y sin sesión: se identifica por el pedido.
     */
    public function notificacion(Request $request)
    {
        $parametros = $this->redsys->decodificar((string) $request->input('Ds_MerchantParameters', ''));
        $pedido = (string) ($parametros['Ds_Order'] ?? '');

        $pago = Pago::where('referencia_pasarela', $pedido)->first();

        if ($pago === null) {
            Log::warning('Redsys: notificación de un pedido desconocido', ['pedido' => $pedido]);

            // 200 igualmente: si devolvemos error, Redsys reintenta en bucle.
            return response('OK');
        }

        // Una notificación repetida no debe volver a mover nada.
        if ($pago->estado === EstadoPago::Pagado) {
            return response('OK');
        }

        DB::transaction(function () use ($pago, $request) {
            if ($this->redsys->confirmar($pago, $request->all())) {
                $this->marcarReservaPagada($pago);
            }
        });

        return response('OK');
    }

    /**
     * Vuelta del cliente desde el TPV. Solo informa: el estado real lo fija la
     * notificación, que puede llegar antes o después que el navegador.
     */
    public function resultado(Reserva $reserva, string $estado)
    {
        return view('pago.resultado', [
            'reserva' => $reserva,
            'pago' => $this->senalDe($reserva),
            'ok' => $estado === 'ok',
        ]);
    }

    /**
     * El cliente elige pagar por transferencia: se le muestran los datos y la
     * señal queda pendiente hasta que el comercio confirme que ha llegado.
     */
    public function transferencia(Reserva $reserva)
    {
        $config = Configuracion::actual();

        abort_unless($config->pago_transferencia, 404);

        return view('pago.transferencia', [
            'reserva' => $reserva,
            'pago' => $this->senalDe($reserva),
            'config' => $config,
        ]);
    }

    /**
     * Si hay contrato configurado y la reserva no lo tiene firmado, manda a
     * firmarlo: es requisito para seguir con el proceso.
     */
    private function exigirContrato(Reserva $reserva): ?RedirectResponse
    {
        if ($reserva->contratoFirmado() || blank(Configuracion::actual()->contrato_plantilla)) {
            return null;
        }

        return redirect()->to(URL::signedRoute('contrato.mostrar', ['reserva' => $reserva->referencia]));
    }

    /**
     * Marca la reserva como confirmada cuando su señal queda pagada.
     */
    private function marcarReservaPagada(Pago $pago): void
    {
        $reserva = $pago->reserva;

        if ($reserva->estado === EstadoReserva::Solicitada) {
            $reserva->update(['estado' => EstadoReserva::Confirmada]);
        }
    }

    private function senalDe(Reserva $reserva): Pago
    {
        $pago = $reserva->pagos()
            ->where('tipo', TipoPago::Senal)
            ->latest('id')
            ->first();

        abort_if($pago === null, 404, 'Esta reserva no tiene señal pendiente.');

        return $pago;
    }
}
