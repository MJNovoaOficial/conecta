<?php

namespace App\Listeners;

use App\Models\Ticket;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class TicketEventSubscriber
{
    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events)
    {
        $events->listen(
            'eloquent.created: '.Ticket::class,
            [self::class, 'handleCreated']
        );
        $events->listen(
            'eloquent.updated: '.Ticket::class,
            [self::class, 'handleUpdated']
        );
        $events->listen(
            'eloquent.deleted: '.Ticket::class,
            [self::class, 'handleDeleted']
        );
        // Add other models if needed (PriorityRule, Categoria, etc.)
    }

    public function handleCreated(Ticket $ticket)
    {
        AuditLog::record('ticket.created', Ticket::class, $ticket->id, $ticket->toArray());
        Log::info("Audit: ticket {$ticket->id} created.");
    }

    public function handleUpdated(Ticket $ticket)
    {
        // Get changed attributes
        $changes = $ticket->getChanges();
        AuditLog::record('ticket.updated', Ticket::class, $ticket->id, $changes);
        Log::info("Audit: ticket {$ticket->id} updated.");
    }

    public function handleDeleted(Ticket $ticket)
    {
        AuditLog::record('ticket.deleted', Ticket::class, $ticket->id, $ticket->toArray());
        Log::info("Audit: ticket {$ticket->id} deleted.");
    }
}
?>
