<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Support\TicketDelegationAccess;

class TicketPolicy
{
    /**
     * La supervisión administrativa no permite intervenir en la conversación
     * de otro agente: para responder también debe tener asignado el ticket.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (in_array($ability, ['comment', 'delegate', 'revokeDelegation', 'proposeDelegation'], true)) return null;
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isSupport() || $user->id === $ticket->user_id || TicketDelegationAccess::isDelegate($user, $ticket);
    }

    /**
     * La conversación se habilita tras asignar a un agente. El solicitante
     * puede responder, pero otro soporte que solo consulta no puede intervenir.
     * Las acciones que cambian el flujo se autorizan por separado.
     */
    public function comment(User $user, Ticket $ticket): bool
    {
        return $ticket->hasAssignedSupport()
            && ($user->id === $ticket->user_id
                || (($user->isSupport() || $user->isAdmin()) && $ticket->assigned_to === $user->id)
                || TicketDelegationAccess::isDelegate($user, $ticket, true));
    }

    public function delegate(User $user, Ticket $ticket): bool
    {
        return $this->revokeDelegation($user, $ticket) && $ticket->hasAssignedSupport()
            && !in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true);
    }

    public function revokeDelegation(User $user, Ticket $ticket): bool
    {
        return $user->is_active && TicketDelegationAccess::available()
            && ($user->isAdmin() || ($user->isSupport() && $ticket->assigned_to === $user->id));
    }

    public function proposeDelegation(User $user, Ticket $ticket): bool
    {
        return TicketDelegationAccess::isDelegate($user, $ticket, true)
            && TicketDelegationAccess::current($ticket)?->status === 'active';
    }

    /**
     * Los comentarios internos y sus archivos nunca son visibles para el
     * solicitante, aunque conozca el identificador directo del adjunto.
     */
    public function viewInternal(User $user, Ticket $ticket): bool
    {
        return $user->isSupport();
    }

    /**
     * Acceso a la URL antigua, que ahora solo explica el flujo por acciones.
     * El controlador no permite cambios manuales, tampoco para administradores.
     */
    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->isSupport();
    }

    /**
     * Acciones reservadas al personal. Cada controlador conserva después sus
     * reglas originales sobre quién debe tener asignado el ticket.
     */
    public function staff(User $user, Ticket $ticket): bool
    {
        return $user->isSupport();
    }

    /**
     * Acciones que cambian el flujo desde el formulario reservado al agente
     * que tiene asignado el ticket.
     */
    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->isSupport() && $ticket->assigned_to === $user->id;
    }

    /**
     * Solo soporte puede tomar un ticket libre. Un agente no puede apropiarse
     * de uno que ya está asignado a otra persona.
     */
    public function selfAssign(User $user, Ticket $ticket): bool
    {
        return $user->isSupport();
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || $this->manage($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id || $this->manage($user, $ticket);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isSupport() || $user->id === $ticket->user_id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }
}
