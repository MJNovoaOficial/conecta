<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        // El creador del ticket puede verlo
        if ($user->id === $ticket->user_id) {
            return true;
        }

        // El soporte o admin pueden verlo
        if ($user->isSupport() || $user->isAdmin()) {
            return true;
        }

        return false;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        // Solo soporte, admin o el creador pueden actualizar
        return $user->isSupport() || $user->isAdmin() || $user->id === $ticket->user_id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->id === $ticket->user_id;
    }
}
