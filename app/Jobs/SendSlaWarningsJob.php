<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Notifications\SlaWarningNotification;
use App\Mail\SlaWarningMail;
use Illuminate\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;

class SendSlaWarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        $now = Carbon::now();
        // Define threshold for warning (e.g., 30 minutes before deadline)
        $warningThreshold = $now->copy()->addMinutes(30);

        $tickets = Ticket::whereIn('status', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_PENDING_USER,
                Ticket::STATUS_FORWARDED,
            ])
            ->whereNotNull('sla_resolution_deadline_at')
            ->whereBetween('sla_resolution_deadline_at', [$now, $warningThreshold])
            ->get();

        foreach ($tickets as $ticket) {
            // Send UI notification to assigned agent (if any)
            if ($ticket->assigned_to) {
                Notification::send($ticket->assignedTo, new SlaWarningNotification($ticket));
            }

            // Send email to admin channel (hard‑coded for now)
            $adminEmails = config('mail.sla_warning_recipients', []);
            if (!empty($adminEmails)) {
                Mail::to($adminEmails)->send(new SlaWarningMail($ticket));
            }

            Log::info("SLA warning sent for ticket {$ticket->id}");
        }
    }
}
?>
