<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email de confirmación de creación de ticket para invitados (RF-RI-11).
 * Se envía inmediatamente después de que un invitado abre un ticket,
 * incluyendo el número de ticket y el enlace de seguimiento con token.
 */
class GuestTicketCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Ticket $ticket;
    public string $trackingUrl;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
        $this->trackingUrl = route('tickets.guest.show', $ticket->guest_token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket ' . $this->ticket->ticket_number . ' creado — Conecta Soporte',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guest_ticket_created',
        );
    }
}
