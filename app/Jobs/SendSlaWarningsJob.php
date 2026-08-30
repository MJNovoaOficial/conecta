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

/**
 * INCOMPLETO — NO PROGRAMAR TODAVÍA.
 *
 * Avisar antes de que venza un SLA es una función que falta y vale la pena
 * tener, pero este trabajo no está terminado y programarlo rompería la cola:
 *
 *   1. SlaWarningNotification y SlaWarningMail no existen. En cuanto
 *      encontrara un ticket por vencer, fallaría con "Class not found".
 *   2. config('mail.sla_warning_recipients') no está definida, así que el
 *      correo a los administradores nunca saldría.
 *   3. No registra si ya avisó de un ticket. Corriendo cada cinco minutos con
 *      una ventana de treinta, avisaría seis veces del mismo caso.
 *
 * Estuvo referenciado desde app/Console/Kernel.php, que Laravel 12 no carga,
 * así que nunca llegó a ejecutarse. Terminarlo requiere escribir las dos
 * clases que faltan y agregar una columna para no repetir el aviso.
 */
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
