<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\AuditLog;
use App\Models\Notificacion;
use App\Mail\GuestTicketAutoClosedMail;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CloseExpiredTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Cierra automáticamente los tickets cuyo deadline de respuesta del usuario ha vencido.
     * RNG-01: Si el solicitante no responde en 2h, el ticket se cierra automáticamente.
     * RF-RI-12: Notificar al solicitante por correo y/o in-app al cerrarse.
     */
    public function handle()
    {
        $now = Carbon::now();

        // Tickets en estado "pendiente del usuario" cuyo plazo de respuesta venció
        $tickets = Ticket::where('status', Ticket::STATUS_PENDING_USER)
            ->whereNotNull('response_deadline_at')
            ->where('response_deadline_at', '<', $now)
            ->whereNull('user_responded_at')
            ->get();

        foreach ($tickets as $ticket) {
            $oldStatus = $ticket->status;

            $ticket->status    = Ticket::STATUS_CLOSED;
            $ticket->closed_at = $now;
            $ticket->save();

            // Registrar en historial
            \App\Models\TicketHistory::create([
                'ticket_id'  => $ticket->id,
                'user_id'    => null, // sistema
                'action'     => 'auto_closed',
                'old_value'  => $oldStatus,
                'new_value'  => Ticket::STATUS_CLOSED,
                'field_name' => 'status',
            ]);

            // Registrar en auditoría (RNG-05 / RN-26)
            AuditLog::record('ticket.auto_closed_no_response', Ticket::class, $ticket->id, [
                'previous_status'   => $oldStatus,
                'response_deadline' => $ticket->response_deadline_at,
                'reason'            => 'Falta de respuesta del solicitante (plazo 2h vencido)',
            ]);

            // ── Notificar al invitado por correo ──────────────────────
            if (!$ticket->user_id && $ticket->guest_email) {
                try {
                    Mail::to($ticket->guest_email)->send(new GuestTicketAutoClosedMail($ticket));
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar email de cierre al invitado (ticket {$ticket->id}): " . $e->getMessage());
                }
            }

            // ── Notificar al usuario registrado (in-app + email) ──────
            if ($ticket->user_id) {
                // Notificación in-app (RF-RI-10 / RF-RI-12)
                Notificacion::notify(
                    $ticket->user_id,
                    'closed',
                    'Ticket cerrado: ' . $ticket->ticket_number,
                    'Tu ticket fue cerrado automáticamente por falta de respuesta al plazo establecido.',
                    $ticket->id
                );

                // Notificación por correo (RF-RI-11 / RF-RI-12)
                try {
                    $ticket->user->notify(
                        new TicketUpdatedNotification(
                            $ticket,
                            'Tu ticket ' . $ticket->ticket_number . ' fue cerrado automáticamente porque no recibimos tu respuesta dentro de las 2 horas establecidas.'
                        )
                    );
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar email de cierre al usuario {$ticket->user_id} (ticket {$ticket->id}): " . $e->getMessage());
                }
            }

            Log::info("Ticket {$ticket->id} auto-cerrado por falta de respuesta del solicitante.");
        }

        // Mantener compatibilidad: también cerrar tickets cuyo SLA de resolución venció
        // (lógica original del job — tickets no pendiente_usuario pero con SLA vencido)
        $slaExpired = Ticket::whereIn('status', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_FORWARDED,
            ])
            ->whereNotNull('sla_resolution_deadline_at')
            ->where('sla_resolution_deadline_at', '<', $now)
            ->get();

        foreach ($slaExpired as $ticket) {
            $oldStatus      = $ticket->status;
            $ticket->status = Ticket::STATUS_CLOSED;
            $ticket->closed_at = $now;
            $ticket->save();

            AuditLog::record('ticket.auto_closed', Ticket::class, $ticket->id, [
                'previous_status' => $oldStatus,
                'new_status'      => $ticket->status,
                'reason'          => 'SLA de resolución vencido',
            ]);

            Log::info("Ticket {$ticket->id} auto‑closed by SLA deadline.");
        }
    }
}

