<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de que a un ticket le queda poco para vencer su plazo de resolución.
 *
 * Llega a quien lo tiene asignado y, si no está asignado, al equipo de soporte:
 * un ticket sin dueño cerca del vencimiento es el que más riesgo corre.
 *
 * El envío al correo alternativo lo resuelve User::routeNotificationForMail().
 */
class SlaWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Ticket $ticket,
        protected int $minutosRestantes
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $restante = $this->minutosRestantes >= 60
            ? intdiv($this->minutosRestantes, 60) . ' h ' . ($this->minutosRestantes % 60) . ' min'
            : $this->minutosRestantes . ' minutos';

        return (new MailMessage)
            ->subject('Por vencer: ticket ' . $this->ticket->ticket_number)
            ->greeting('Hola ' . $notifiable->name)
            ->line('Al ticket ' . $this->ticket->ticket_number . ' le quedan ' . $restante
                 . ' para cumplir su plazo de resolución.')
            ->line('Asunto: ' . $this->ticket->title)
            ->line('Prioridad: ' . $this->ticket->getPriorityLabel())
            ->action('Ver el ticket', route('tickets.show', $this->ticket))
            ->line('Si necesitas más tiempo, deja registrado el motivo en el ticket.');
    }
}
