<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notificación al usuario cuando se actualiza su ticket (respuesta, cambio de estado, etc.).
 * Envía también al correo alternativo si el usuario lo tiene configurado.
 * Sin ShouldQueue — se envía en tiempo real (Reunión 3).
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

    /**
     * Canal de notificación: mail.
     * Si el notifiable tiene correo alternativo, usamos ambos correos.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Envía al correo principal y, si existe, al correo alternativo.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Actualización de Ticket: ' . $this->ticket->ticket_number)
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->message)
            ->line('Número de Ticket: ' . $this->ticket->ticket_number)
            ->line('Estado Actual: ' . $this->ticket->getStatusLabel())
            ->action('Ver Ticket', route('tickets.show', $this->ticket))
            ->line('Gracias por usar Conecta');

        // Enviar también al correo alternativo si está configurado
        if (!empty($notifiable->alternate_email)) {
            $mail->to($notifiable->alternate_email);
        }

        return $mail;
    }
}
