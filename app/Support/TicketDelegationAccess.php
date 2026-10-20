<?php

namespace App\Support;

use App\Models\Ticket;
use App\Models\TicketDelegation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Las autorizaciones siempre se refieren al encargo más reciente del caso. */
final class TicketDelegationAccess
{
    private const AVAILABLE_ATTRIBUTE = 'conecta.ticket_delegation.table_available';

    public static function available(): bool
    {
        // No consultar siquiera el esquema mientras la función esté apagada.
        if (!filter_var(config('ticket_delegation.enabled', false), FILTER_VALIDATE_BOOLEAN)) return false;

        // Sin estáticos persistentes entre peticiones/tests: una migración o un
        // cambio de configuración deben comprobarse de nuevo en la próxima.
        $request = request();
        if (!$request->attributes->has(self::AVAILABLE_ATTRIBUTE)) {
            $request->attributes->set(self::AVAILABLE_ATTRIBUTE, Schema::hasTable('ticket_delegations'));
        }
        return $request->attributes->get(self::AVAILABLE_ATTRIBUTE) === true;
    }

    /** Incluye filas revocadas/inactivas para la supervisión de TI, no es una ACL. */
    public static function current(Ticket $ticket): ?TicketDelegation
    {
        if (!self::available() || !$ticket->exists) return null;
        return TicketDelegation::query()->where('ticket_id', $ticket->getKey())->latest('id')->first();
    }

    public static function isDelegate(User $user, Ticket $ticket, bool $forComment = false): bool
    {
        if (!$user->is_active || !$user->isUser() || !$user->department_id) return false;
        $delegation = self::current($ticket);
        if (!$delegation || $delegation->delegate_id !== (int) $user->getKey()
            || $delegation->responsible_id !== (int) $ticket->assigned_to
            || $delegation->reopened_count !== (int) $ticket->reopened_count
            || $delegation->department_id !== (int) $user->department_id
            || !$ticket->hasAssignedSupport()) return false;

        $finished = in_array($ticket->status, [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED], true);
        if ($finished) {
            if ($forComment || $delegation->status !== TicketDelegation::STATUS_APPROVED) return false;
        } elseif (!in_array($delegation->status, TicketDelegation::OPEN_STATUSES, true)) {
            return false;
        }

        // Compartir el predicado SQL con el listado revalida también la cuenta
        // responsable y el último encargo, sin confiar en relaciones antiguas.
        $visible = self::constrainVisible(Ticket::query()->whereKey($ticket->getKey()), $user);
        if ($forComment) {
            $visible->whereNotIn('tickets.status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
        }
        return $visible->exists();
    }

    /** Solo encargos; el llamador debe agrupar por separado el OR de casos propios. */
    public static function constrainVisible(Builder $query, User $user): Builder
    {
        if (!self::available() || !$user->is_active || !$user->isUser() || !$user->department_id) {
            return $query->whereRaw('1 = 0');
        }

        $tickets = $query->getModel()->getTable();
        return $query->whereExists(function ($delegation) use ($user, $tickets) {
            $delegation->selectRaw('1')->from('ticket_delegations as delegation_access')
                ->whereColumn('delegation_access.ticket_id', $tickets.'.id')
                ->where('delegation_access.delegate_id', $user->getKey())
                ->whereColumn('delegation_access.responsible_id', $tickets.'.assigned_to')
                ->whereColumn('delegation_access.reopened_count', $tickets.'.reopened_count')
                ->where('delegation_access.department_id', $user->department_id)
                // Una fila posterior, aunque esté revocada, invalida la anterior.
                ->whereNotExists(function ($newer) {
                    $newer->selectRaw('1')->from('ticket_delegations as delegation_newer')
                        ->whereColumn('delegation_newer.ticket_id', 'delegation_access.ticket_id')
                        ->whereColumn('delegation_newer.id', '>', 'delegation_access.id');
                })
                ->whereExists(function ($delegate) {
                    $delegate->selectRaw('1')->from('usuarios as delegation_user')
                        ->whereColumn('delegation_user.id', 'delegation_access.delegate_id')
                        ->where('delegation_user.role', 'user')->where('delegation_user.is_active', true)
                        ->whereColumn('delegation_user.department_id', 'delegation_access.department_id');
                })
                ->whereExists(function ($department) {
                    $department->selectRaw('1')->from('departamentos as delegation_department')
                        ->whereColumn('delegation_department.id', 'delegation_access.department_id')
                        ->where('delegation_department.is_active', true);
                })
                ->whereExists(function ($responsible) use ($tickets) {
                    $responsible->selectRaw('1')->from('usuarios as delegation_responsible')
                        ->whereColumn('delegation_responsible.id', $tickets.'.assigned_to')
                        ->where('delegation_responsible.is_active', true)
                        ->whereIn('delegation_responsible.role', ['support', 'admin']);
                })
                ->where(function ($states) use ($tickets) {
                    $states->where(function ($open) use ($tickets) {
                        $open->whereIn('delegation_access.status', TicketDelegation::OPEN_STATUSES)
                            ->whereNotIn($tickets.'.status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
                    })->orWhere(function ($approved) use ($tickets) {
                        $approved->where('delegation_access.status', TicketDelegation::STATUS_APPROVED)
                            ->whereIn($tickets.'.status', [Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED]);
                    });
                });
        });
    }

    /**
     * Invalidar permisos sin borrar filas, propuestas, comentarios ni archivos.
     * El actor se audita en el workflow; no sobrescribir la revisión anterior.
     */
    public static function invalidate(Ticket $ticket, ?int $actorId = null): void
    {
        if (!self::available() || !$ticket->exists) return;
        TicketDelegation::query()->where('ticket_id', $ticket->getKey())->viewable()->update([
            'status' => TicketDelegation::STATUS_REVOKED,
            'ended_at' => now(),
            'version' => DB::raw('version + 1'),
        ]);
    }
}
