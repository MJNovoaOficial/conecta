<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de confirmación para el usuario registrado que crea un ticket (RF-RI-11).
 * Usa la vista HTML personalizada (emails.ticket_created).
 * Sin ShouldQueue — se envía en tiempo real (Reunión 3).
 */
class TicketCreatedNotification extends Notification
{
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
        $mail = (new MailMessage)
            ->subject('Ticket ' . $this->ticket->ticket_number . ' creado — Conecta Soporte')
            ->view('emails.ticket_created', [
                'ticket'    => $this->ticket,
                'user'      => $notifiable,
                'ticketUrl' => route('tickets.show', $this->ticket),
            ]);

        // Enviar también al correo alternativo si está configurado
        if (!empty($notifiable->alternate_email)) {
            $mail->to($notifiable->alternate_email);
        }

        return $mail;
    }
}
