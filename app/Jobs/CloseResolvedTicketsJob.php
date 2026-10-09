<?php

namespace App\Jobs;

use App\Mail\GuestTicketStatusMail;
use App\Models\AuditLog;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CloseResolvedTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();
        $tickets = Ticket::query()->where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('resolved_at')->where('resolved_at', '<=', $now->copy()->subHour())
            // No cerrar tickets históricos marcados Resueltos antes de este flujo.
            ->whereHas('history', fn ($query) => $query->where('action', 'solution_registered'))
            ->get();

        foreach ($tickets as $ticket) {
            $closed = DB::transaction(function () use ($ticket, $now) {
                // Reabrir o registrar una nueva solución invalida esta pasada.
                $updated = Ticket::query()->whereKey($ticket->id)
                    ->where('status', Ticket::STATUS_RESOLVED)
                    ->where('resolved_at', $ticket->resolved_at)
                    ->update(['status' => Ticket::STATUS_CLOSED, 'closed_at' => $now]);
                if (!$updated) return false;
                $ticket->refresh();
                TicketHistory::create([
                    'ticket_id' => $ticket->id, 'user_id' => null,
                    'action' => 'auto_closed_resolved', 'old_value' => Ticket::STATUS_RESOLVED,
                    'new_value' => Ticket::STATUS_CLOSED, 'field_name' => 'status',
                ]);
                AuditLog::record('ticket.auto_closed_resolved', 'Ticket', $ticket->id, [
                    'resolved_at' => $ticket->resolved_at,
                    'reason' => 'Una hora desde el registro de la solución, sin reapertura',
                ]);
                return true;
            });
            if (!$closed) continue;

            $message = 'Tu ticket se cerró automáticamente al cumplirse una hora desde la solución registrada, sin una reapertura. Si el problema continúa, abre una nueva solicitud indicando este número de ticket.';
            try {
                if ($ticket->user_id) {
                    Notificacion::notify($ticket->user_id, 'closed', 'Ticket cerrado: '.$ticket->ticket_number, $message, $ticket->id);
                    $ticket->user->notify(new TicketUpdatedNotification($ticket, $message));
                } elseif ($ticket->guest_email) {
                    Mail::to($ticket->guest_email)->send(new GuestTicketStatusMail($ticket, $message));
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo avisar del cierre del ticket resuelto '.$ticket->id.': '.$e->getMessage());
            }
        }
    }
}
