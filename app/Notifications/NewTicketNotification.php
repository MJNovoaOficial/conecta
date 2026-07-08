<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;

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
            ->subject('Nuevo Ticket: ' . $this->ticket->ticket_number)
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se ha creado un nuevo ticket que requiere tu atención.')
            ->line('Número de Ticket: ' . $this->ticket->ticket_number)
            ->line('Título: ' . $this->ticket->title)
            ->line('Prioridad: ' . ucfirst($this->ticket->priority))
            ->action('Ver Ticket', route('tickets.show', $this->ticket))
            ->line('Gracias por usar Conecta');
    }
}
