<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al usuario cuando se actualiza su ticket (respuesta, cambio de estado, etc.).
 * Sin ShouldQueue — se envía en tiempo real (Reunión 3).
 *
 * El envío al correo alternativo lo resuelve User::routeNotificationForMail().
 */
class TicketUpdatedNotification extends Notification
{
    protected $ticket;
    protected $message;

    public function __construct(Ticket $ticket, string $message = 'El ticket ha sido actualizado')
    {
        $this->ticket  = $ticket;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Actualización de Ticket: ' . $this->ticket->ticket_number)
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->message)
            ->line('Número de Ticket: ' . $this->ticket->ticket_number)
            ->line('Estado Actual: ' . $this->ticket->getStatusLabel())
            ->action('Ver Ticket', route('tickets.show', $this->ticket))
            ->line('Gracias por usar Conecta');
    }
}
