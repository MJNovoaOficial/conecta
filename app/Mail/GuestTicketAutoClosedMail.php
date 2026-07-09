<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestTicketAutoClosedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu ticket ' . $this->ticket->ticket_number . ' ha sido cerrado — Conecta Soporte',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guest_ticket_auto_closed',
        );
    }
}
