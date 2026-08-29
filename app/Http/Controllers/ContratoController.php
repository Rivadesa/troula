<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\ContratoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

/**
 * Firma del contrato de prestación de servicios.
 *
 * Es firma electrónica SIMPLE: el cliente lee el texto y lo acepta, y se guarda
 * el texto exacto aceptado, su hash SHA-256, la fecha, la IP y el navegador.
 * Guardar el texto (y no solo la plantilla) es lo importante: si el admin
 * cambia la plantilla después, lo firmado no se mueve.
 */
class ContratoController extends Controller
{
    public function __construct(private readonly ContratoService $contratos) {}

    /**
     * Muestra el contrato para leerlo y aceptarlo.
     */
    public function mostrar(Reserva $reserva)
    {
        if ($reserva->contratoFirmado()) {
            return redirect()->to(URL::signedRoute('contrato.ver', ['reserva' => $reserva->referencia]));
        }

        $texto = $this->contratos->generar($reserva);

        abort_if(trim($texto) === '', 404, 'Todavía no hay un contrato configurado.');

        return view('contrato.mostrar', [
            'reserva' => $reserva,
            'texto' => $texto,
        ]);
    }

    /**
     * Registra la aceptación.
     */
    public function aceptar(Request $request, Reserva $reserva)
    {
        if ($reserva->contratoFirmado()) {
            return redirect()->to(URL::signedRoute('contrato.ver', ['reserva' => $reserva->referencia]));
        }

        $request->validate(
            ['acepto' => ['accepted']],
            ['acepto.accepted' => 'Debes aceptar el contrato para continuar.'],
        );

        // El DNI y la dirección ya vienen del formulario de reserva, así que el
        // texto se genera con ellos dentro.
        $texto = $this->contratos->generar($reserva);

        if (trim($texto) === '') {
            throw ValidationException::withMessages([
                'acepto' => 'No se ha podido generar el contrato. Ponte en contacto con nosotros.',
            ]);
        }

        $reserva->update([
            'contrato_texto' => $texto,
            'contrato_hash' => $this->contratos->hash($texto),
            'contrato_aceptado_en' => now(),
            'contrato_ip' => $request->ip(),
            'contrato_user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()->to(URL::signedRoute('pago.mostrar', ['reserva' => $reserva->referencia]));
    }

    /**
     * Copia del contrato ya firmado, con su registro de aceptación.
     */
    public function ver(Reserva $reserva)
    {
        abort_unless($reserva->contratoFirmado(), 404);

        return view('contrato.ver', ['reserva' => $reserva]);
    }
}
