<?php

namespace App\Jobs;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoCloseTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Obtener tickets en estado "Pendiente Usuario" con deadline expirado
        $expiredTickets = Ticket::where('status', Ticket::STATUS_PENDING_USER)
            ->where('response_deadline_at', '<', Carbon::now())
            ->get();

        foreach ($expiredTickets as $ticket) {
            $ticket->update([
                'status' => Ticket::STATUS_CLOSED,
            ]);

            // Registrar en historial
            \App\Models\TicketHistory::create([
                'ticket_id' => $ticket->id,
                'user_id' => null,
                'action' => 'auto_closed',
                'old_value' => Ticket::STATUS_PENDING_USER,
                'new_value' => Ticket::STATUS_CLOSED,
                'field_name' => 'status',
            ]);

            // Notificar al usuario
            $ticket->user->notify(
                new \App\Notifications\TicketUpdatedNotification(
                    $ticket,
                    'Tu ticket ha sido cerrado por falta de respuesta.'
                )
            );
        }
    }
}
