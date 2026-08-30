<?php

namespace App\Jobs;

use App\Mail\GuestTicketAutoClosedMail;
use App\Models\AuditLog;
use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\TicketHistory;
use App\Notifications\TicketUpdatedNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Cierra los tickets que quedaron esperando una respuesta que nunca llegó.
 *
 * RNG-01: cuando soporte pide información al solicitante, este tiene un plazo
 * para contestar. Vencido ese plazo sin respuesta, el ticket se cierra.
 *
 * Cierra ÚNICAMENTE por falta de respuesta del solicitante. Un ticket que
 * soporte no alcanzó a resolver dentro del SLA no se toca: incumplir un plazo
 * de atención no significa que el problema esté resuelto, y cerrarlo lo
 * haría desaparecer de la bandeja dejando al usuario sin respuesta.
 */
class AutoCloseTicketJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $ahora = Carbon::now();

        $tickets = Ticket::where('status', Ticket::STATUS_PENDING_USER)
            ->whereNotNull('response_deadline_at')
            ->where('response_deadline_at', '<', $ahora)
            // Sin esta condición se cerrarían también los tickets de quien sí
            // contestó: le pedimos información, la entregó, y le cerramos el
            // ticket igual.
            ->whereNull('user_responded_at')
            ->get();

        foreach ($tickets as $ticket) {
            $estadoAnterior = $ticket->status;

            $ticket->update([
                'status'    => Ticket::STATUS_CLOSED,
                'closed_at' => $ahora,
            ]);

            TicketHistory::create([
                'ticket_id'  => $ticket->id,
                'user_id'    => null,   // lo cierra el sistema, no una persona
                'action'     => 'auto_closed',
                'old_value'  => $estadoAnterior,
                'new_value'  => Ticket::STATUS_CLOSED,
                'field_name' => 'status',
            ]);

            // RNG-05: queda en auditoría porque es una acción que nadie firmó.
            // El nombre corto del modelo es la convención del resto del código.
            AuditLog::record('ticket.auto_closed_no_response', 'Ticket', $ticket->id, [
                'previous_status'   => $estadoAnterior,
                'response_deadline' => $ticket->response_deadline_at,
                'reason'            => 'Falta de respuesta del solicitante',
            ]);

            $this->avisar($ticket);
        }
    }

    /**
     * Avisa a quien abrió el ticket.
     *
     * Un fallo de correo no puede dejar el cierre a medias: el ticket ya se
     * cerró y la auditoría ya quedó escrita.
     */
    private function avisar(Ticket $ticket): void
    {
        $mensaje = 'Tu ticket ' . $ticket->ticket_number . ' se cerró porque no recibimos '
                 . 'tu respuesta dentro del plazo. Si el problema sigue, abre uno nuevo.';

        try {
            if ($ticket->user_id) {
                Notificacion::notify(
                    $ticket->user_id,
                    'closed',
                    'Ticket cerrado: ' . $ticket->ticket_number,
                    $mensaje,
                    $ticket->id
                );

                $ticket->user->notify(new TicketUpdatedNotification($ticket, $mensaje));
                return;
            }

            if ($ticket->guest_email) {
                Mail::to($ticket->guest_email)->send(new GuestTicketAutoClosedMail($ticket));
            }
        } catch (\Throwable $e) {
            Log::warning(
                "No se pudo avisar del cierre automatico del ticket {$ticket->id}: " . $e->getMessage()
            );
        }
    }
}
