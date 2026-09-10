<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * El administrador puede realizar cualquier operación sobre un ticket.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isSupport() || $user->id === $ticket->user_id;
    }

    /**
     * El solicitante y soporte pueden conversar en un ticket que pueden ver.
     * Las acciones que cambian el flujo se autorizan por separado.
     */
    public function comment(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
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
     * Conserva el flujo actual: cualquier agente de soporte puede cambiar el
     * estado desde el panel. El solicitante no puede hacerlo por URL directa.
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
