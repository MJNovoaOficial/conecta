<?php

namespace App\Support;

use App\Models\Notificacion;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Support\Facades\Log;

/** Avisos dirigidos a una persona del caso, nunca a un rol o departamento completo. */
final class TicketDelegationNotifier
{
    public static function send(User $recipient, Ticket $ticket, string $event, string $message): void
    {
        // No confiar en una relación cargada antes de confirmar el encargo:
        // la cuenta o sus direcciones pueden haber cambiado entre peticiones.
        try {
            $recipient = User::query()->whereKey($recipient->getKey())->where('is_active', true)->first();
            if (!$recipient) return;
        } catch (\Throwable $e) {
            Log::warning('No se pudo comprobar el destinatario del encargo del ticket '.$ticket->id.': '.$e->getMessage());
            return;
        }

        // El mensaje es un aviso genérico. No leer ni copiar instrucciones,
        // propuesta o feedback privados en los canales externos del ticket.
        try {
            Notificacion::notify($recipient->id, $event, 'Actualización del ticket '.$ticket->ticket_number, $message, $ticket->id);
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar el aviso de encargo del ticket '.$ticket->id.' al usuario '.$recipient->id.': '.$e->getMessage());
        }

        // Cada canal es independiente: un correo fallido no invalida el
        // guardado del encargo ni impide conservar el aviso de campana.
        try {
            $recipient->notify(new TicketUpdatedNotification($ticket, $message, $event));
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el aviso de encargo del ticket '.$ticket->id.' al usuario '.$recipient->id.': '.$e->getMessage());
        }
    }

    public static function notifyDelegate(Ticket $ticket, string $event, string $message, ?int $excludeId = null): void
    {
        try {
            if (!TicketDelegationAccess::available()) return;
            $delegation = TicketDelegationAccess::current($ticket);
            $recipient = $delegation?->delegate;
            if (!$recipient || $recipient->id === $excludeId || !TicketDelegationAccess::isDelegate($recipient, $ticket)) return;
            self::send($recipient, $ticket, $event, $message);
        } catch (\Throwable $e) {
            Log::warning('No se pudo resolver el destinatario del encargo del ticket '.$ticket->id.': '.$e->getMessage());
        }
    }
}
