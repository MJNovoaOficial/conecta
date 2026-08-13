<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de confirmación para el usuario registrado que crea un ticket (RF-RI-11).
 * Usa una vista HTML personalizada (emails.ticket_created) para mantener
 * la identidad visual de Conecta. El equivalente para invitados es
 * App\Mail\GuestTicketCreatedMail (enlace con token porque no hay sesión).
 */
class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ticket ' . $this->ticket->ticket_number . ' creado — Conecta Soporte')
            ->view('emails.ticket_created', [
                'ticket'    => $this->ticket,
                'user'      => $notifiable,
                'ticketUrl' => route('tickets.show', $this->ticket),
            ]);
    }
}
