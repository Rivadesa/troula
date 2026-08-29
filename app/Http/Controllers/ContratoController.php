<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Services\ContratoService;
use Barryvdh\DomPDF\Facade\Pdf;
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

        // La aceptación se registra ANTES de generar el texto para que el bloque
        // de firma ("aceptado por X el día Y desde la IP Z") quede DENTRO del
        // documento y, por tanto, dentro de su hash.
        $reserva->update([
            'contrato_aceptado_en' => now(),
            'contrato_ip' => $request->ip(),
            'contrato_user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        // El DNI y la dirección ya vienen del formulario de reserva.
        $texto = $this->contratos->generar($reserva->fresh());

        if (trim($texto) === '') {
            // Sin texto no hay nada firmado: se deshace el registro.
            $reserva->update(['contrato_aceptado_en' => null, 'contrato_ip' => null, 'contrato_user_agent' => null]);

            throw ValidationException::withMessages([
                'acepto' => 'No se ha podido generar el contrato. Ponte en contacto con nosotros.',
            ]);
        }

        $reserva->update([
            'contrato_texto' => $texto,
            'contrato_hash' => $this->contratos->hash($texto),
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

    /**
     * Descarga del contrato firmado en PDF.
     */
    public function pdf(Reserva $reserva)
    {
        abort_unless($reserva->contratoFirmado(), 404);

        $pdf = Pdf::loadView('contrato.pdf', ['reserva' => $reserva])
            ->setPaper('a4');

        return $pdf->download('contrato-'.$reserva->referencia.'.pdf');
    }
}
