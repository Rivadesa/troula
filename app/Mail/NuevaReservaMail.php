<?php

namespace App\Mail;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de nueva reserva al administrador (el "lead" de Fase 1).
 *
 * Se encola (ShouldQueue) y se procesa con el driver de cola `database`,
 * que en Dinahosting se vacía con el cron de `schedule:run` (ver README).
 */
class NuevaReservaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reserva $reserva) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva reserva '.$this->reserva->referencia.' — '.$this->reserva->cliente_nombre,
            replyTo: [$this->reserva->cliente_email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.nueva-reserva',
            with: [
                'reserva' => $this->reserva->loadMissing(['experiencia', 'pack', 'zona', 'complementos']),
            ],
        );
    }
}
