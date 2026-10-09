<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestTicketStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public string $statusMessage) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Actualización de tu ticket '.$this->ticket->ticket_number.' — Conecta Soporte');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.guest_ticket_status');
    }
}
