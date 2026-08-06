<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Correo de confirmación para el usuario registrado que crea un ticket (RF-RI-11).
 * Incluye el número de ticket y el enlace para consultar su estado.
 *
 * El equivalente para invitados es App\Mail\GuestTicketCreatedMail, que usa un
 * enlace con token porque el invitado no tiene sesión.
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
        $mensaje = (new MailMessage)
            ->subject('Ticket ' . $this->ticket->ticket_number . ' creado — Conecta Soporte')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu solicitud quedó registrada. Estos son los datos:')
            ->line('Número de ticket: ' . $this->ticket->ticket_number)
            ->line('Asunto: ' . $this->ticket->title)
            ->line('Prioridad: ' . $this->ticket->getPriorityLabel())
            ->line('Estado: ' . $this->ticket->getStatusLabel());

        if ($this->ticket->sla_resolution_deadline_at) {
            $mensaje->line('Plazo de resolución: ' . $this->ticket->sla_resolution_deadline_at->format('d/m/Y H:i'));
        }

        return $mensaje
            ->action('Ver el estado de mi ticket', route('tickets.show', $this->ticket))
            ->line('Guarda el número de ticket para futuras consultas.')
            ->salutation('Saludos, equipo de soporte de Conecta.');
    }
}
