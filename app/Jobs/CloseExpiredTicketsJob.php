<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\AuditLog;
use Illuminate\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CloseExpiredTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        $now = Carbon::now();
        // Tickets that are not closed/resolved and have SLA deadline passed
        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_PENDING_USER,
                Ticket::STATUS_FORWARDED,
            ])
            ->whereNotNull('sla_resolution_deadline_at')
            ->where('sla_resolution_deadline_at', '<', $now)
            ->get();

        foreach ($tickets as $ticket) {
            $oldStatus = $ticket->status;
            $ticket->status = Ticket::STATUS_CLOSED;
            $ticket->closed_at = $now;
            $ticket->save();

            // Record audit
            AuditLog::record('ticket.auto_closed', Ticket::class, $ticket->id, [
                'previous_status' => $oldStatus,
                'new_status' => $ticket->status,
            ]);

            Log::info("Ticket {$ticket->id} auto‑closed by SLA.");
        }
    }
}
?>
