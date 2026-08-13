<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Services\PriorityResolverService;
use Carbon\Carbon;

class TicketObserver
{
    /**
     * Handle the Ticket "creating" event.
     */
    public function creating(Ticket $ticket)
    {
        // Resolve priority
        (new PriorityResolverService())->resolve($ticket);
        // Set SLA deadline based on categoria SLA hours if defined
        if (method_exists($ticket, 'getCategoria') && $ticket->categoria && $ticket->categoria->sla_hours) {
            $ticket->sla_resolution_deadline_at = Carbon::now()->addHours($ticket->categoria->sla_hours);
        }
    }

    /**
     * Handle the Ticket "updating" event.
     */
    public function updating(Ticket $ticket)
    {
        // Re‑resolve priority if relevant fields changed
        if ($ticket->isDirty(['categoria_id', 'subcategoria_id', 'tipo_incidente_id'])) {
            (new PriorityResolverService())->resolve($ticket);
        }
    }
}
?>
