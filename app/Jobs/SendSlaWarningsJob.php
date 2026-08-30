<?php

namespace App\Jobs;

use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\SlaWarningNotification;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Avisa cuando a un ticket le queda poco para incumplir su plazo de resolución.
 *
 * Hasta ahora el semáforo del listado solo avisaba a quien estuviera mirando la
 * pantalla: un ticket crítico podía vencer sin que nadie se enterara.
 *
 * Avisa una sola vez por ticket. La marca queda en sla_warned_at y se vuelve a
 * armar si el plazo cambia, para que un ticket al que le corrieron la fecha
 * reciba su aviso nuevo.
 */
class SendSlaWarningsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $minutos = (int) config('sla.aviso_minutos_antes', 30);
        $ahora   = Carbon::now();
        $limite  = $ahora->copy()->addMinutes($minutos);

        $tickets = Ticket::with(['assignedTo', 'user'])
            // Se excluye "Pendiente Usuario" a propósito: ahí la siguiente
            // acción es del solicitante, no de soporte, y para ese caso ya
            // existe el cierre automático por falta de respuesta.
            ->whereIn('status', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_IN_PROGRESS,
                Ticket::STATUS_FORWARDED,
            ])
            ->whereNotNull('sla_resolution_deadline_at')
            ->whereBetween('sla_resolution_deadline_at', [$ahora, $limite])
            // Sin avisar todavía, o avisado sobre un plazo que ya cambió.
            ->where(function ($q) {
                $q->whereNull('sla_warned_for')
                  ->orWhereColumn('sla_warned_for', '!=', 'sla_resolution_deadline_at');
            })
            ->get();

        foreach ($tickets as $ticket) {
            $restantes = (int) round($ahora->diffInMinutes($ticket->sla_resolution_deadline_at, false));

            foreach ($this->destinatarios($ticket) as $persona) {
                $this->avisar($persona, $ticket, $restantes);
            }

            // Se guarda el plazo del que se avisó, no el momento del aviso: si
            // después le mueven la fecha, la marca deja de coincidir y el
            // ticket vuelve a entrar en la próxima pasada.
            //
            // Se marca aunque no hubiera a quién avisar: si no hay nadie que
            // pueda recibirlo, repetir la búsqueda cada cinco minutos no cambia
            // nada y solo llena el log.
            $ticket->forceFill([
                'sla_warned_for' => $ticket->sla_resolution_deadline_at,
            ])->saveQuietly();
        }
    }

    /**
     * A quién avisar.
     *
     * Al agente asignado. Si el ticket no tiene dueño, a todo el equipo de
     * soporte: un ticket sin asignar cerca del vencimiento es el que más
     * riesgo corre, justamente porque nadie lo está mirando.
     */
    private function destinatarios(Ticket $ticket)
    {
        if ($ticket->assignedTo) {
            return collect([$ticket->assignedTo]);
        }

        return User::whereIn('role', ['support', 'admin'])
            ->where('is_active', true)
            ->get();
    }

    /**
     * Un fallo de correo no puede impedir el aviso en pantalla, ni dejar el
     * ticket sin marcar y provocar que se repita en la pasada siguiente.
     */
    private function avisar(User $persona, Ticket $ticket, int $restantes): void
    {
        try {
            Notificacion::notify(
                $persona->id,
                'sla_warning',
                'Por vencer: ' . $ticket->ticket_number,
                'Quedan ' . $restantes . ' minutos para el plazo de resolución.',
                $ticket->id
            );
        } catch (\Throwable $e) {
            Log::warning("Aviso de SLA: no se pudo notificar en pantalla al usuario {$persona->id}: " . $e->getMessage());
        }

        try {
            $persona->notify(new SlaWarningNotification($ticket, $restantes));
        } catch (\Throwable $e) {
            Log::warning("Aviso de SLA: no se pudo enviar el correo al usuario {$persona->id}: " . $e->getMessage());
        }
    }
}
