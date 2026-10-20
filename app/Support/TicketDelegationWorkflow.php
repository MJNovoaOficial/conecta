<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Models\TicketDelegation;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Encargos individuales, independientes de la asignación y de los roles. */
final class TicketDelegationWorkflow
{
    /** Llamar dentro de una transacción: participantes por PK, antes del ticket. */
    public static function lockContext(Ticket $snapshot, User $actor, array $extraUsers = []): array
    {
        abort_unless(TicketDelegationAccess::available(), 404);
        // No se modifica el catálogo de administradores. Bloquearlo por rol,
        // sin un índice adecuado, también retendría cuentas y altas ajenas.
        $ids = array_filter(array_unique([$actor->id, $snapshot->assigned_to, ...$extraUsers]));
        $users = User::whereKey($ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $freshActor = $users->get($actor->id);
        abort_unless($freshActor?->is_active, 403);
        $current = Ticket::whereKey($snapshot->id)->lockForUpdate()->firstOrFail();
        foreach (['assigned_to', 'status', 'department_id', 'resolved_at', 'reopened_count', 'response_deadline_at'] as $field) {
            if ($current->getRawOriginal($field) !== $snapshot->getRawOriginal($field)) self::stale();
        }
        return [$current, $freshActor, $users];
    }

    public static function grant(Ticket $ticket, User $actor, array $data): TicketDelegation
    {
        [$delegation, $previous, $current, $responsible] = DB::transaction(function () use ($ticket, $actor, $data) {
            [$current, $actor, $users] = self::lockContext($ticket, $actor, [(int) $data['delegate_id']]);
            Gate::forUser($actor)->authorize('delegate', $current);
            $latest = TicketDelegationAccess::current($current);
            if ((int) ($latest?->id ?? 0) !== (int) $data['expected_id']
                || (int) ($latest?->version ?? 0) !== (int) $data['version']) self::stale();
            $delegate = $users->get((int) $data['delegate_id']);
            $responsible = $users->get($current->assigned_to);
            if (!$delegate || !$delegate->is_active || !$delegate->isUser()
                || $delegate->id === $current->user_id || !$delegate->department_id
                || $delegate->department_id === $responsible?->department_id
                || !$delegate->department?->is_active) {
                throw ValidationException::withMessages(['delegate_id' => 'Selecciona una persona activa de otro departamento, distinta del solicitante y sin rol de soporte.']);
            }
            $previous = $latest && in_array($latest->status, TicketDelegation::VIEWABLE_STATUSES, true) ? $latest->delegate : null;
            TicketDelegationAccess::invalidate($current, $actor->id);
            self::resumeWork($current, $actor);
            $delegation = TicketDelegation::create([
                'ticket_id' => $current->id, 'delegate_id' => $delegate->id,
                'responsible_id' => $current->assigned_to, 'delegated_by' => $actor->id,
                'department_id' => $delegate->department_id, 'instructions' => $data['instructions'],
                'status' => TicketDelegation::STATUS_ACTIVE, 'version' => 1,
                'reopened_count' => (int) $current->reopened_count,
            ]);
            self::record($current, $actor, $delegation, 'created', ['delegate_id' => $delegate->id]);
            return [$delegation->setRelation('delegate', $delegate), $previous, $current, $responsible];
        });
        if ($previous && $previous->id !== $delegation->delegate_id) {
            TicketDelegationNotifier::send($previous, $current, 'delegation_revoked', 'Se retiró tu encargo de este caso. La actividad registrada se conserva.');
        }
        TicketDelegationNotifier::send($delegation->delegate, $current, 'delegation_assigned', 'Se te encargó colaborar en este caso. Puedes responder y proponer una solución para revisión de TI.');
        if ($responsible && $responsible->id !== $actor->id) {
            TicketDelegationNotifier::send($responsible, $current, 'delegation_assigned', 'Se registró un encargo de colaboración en tu ticket. Conservas su responsabilidad y revisión.');
        }
        return $delegation;
    }

    public static function revoke(Ticket $ticket, TicketDelegation $delegation, User $actor, int $version): void
    {
        [$recipient, $current] = DB::transaction(function () use ($ticket, $delegation, $actor, $version) {
            [$current, $actor] = self::lockContext($ticket, $actor, [$delegation->delegate_id]);
            Gate::forUser($actor)->authorize('revokeDelegation', $current);
            $row = self::row($current, $delegation->id, $version);
            if ($row->status === TicketDelegation::STATUS_REVOKED) self::stale();
            $recipient = $row->delegate;
            $row->update(['status' => TicketDelegation::STATUS_REVOKED, 'ended_at' => now(), 'version' => $row->version + 1]);
            self::record($current, $actor, $row, 'revoked');
            return [$recipient, $current];
        });
        if ($recipient) TicketDelegationNotifier::send($recipient, $current, 'delegation_revoked', 'Se retiró tu encargo de este caso. La actividad registrada se conserva.');
    }

    public static function propose(Ticket $ticket, TicketDelegation $delegation, User $actor, array $data): void
    {
        [$current, $responsible] = DB::transaction(function () use ($ticket, $delegation, $actor, $data) {
            [$current, $actor] = self::lockContext($ticket, $actor, [$delegation->delegate_id]);
            Gate::forUser($actor)->authorize('proposeDelegation', $current);
            $row = self::row($current, $delegation->id, (int) $data['version']);
            if ($row->status !== TicketDelegation::STATUS_ACTIVE || $row->delegate_id !== $actor->id) self::stale();
            $row->update([
                'proposal_text' => $data['solution_text'], 'status' => TicketDelegation::STATUS_SUBMITTED,
                'submitted_at' => now(), 'version' => $row->version + 1,
            ]);
            self::resumeWork($current, $actor);
            // Las revisiones anteriores siguen en auditoría; no se insertan
            // propuestas privadas como comentarios públicos ni como solución.
            self::record($current, $actor, $row, 'proposed', ['proposal_text' => $row->proposal_text]);
            return [$current, $current->assignedTo];
        });
        if ($responsible) TicketDelegationNotifier::send($responsible, $current, 'delegation_proposed', 'La persona encargada envió una propuesta de solución. Revisa el caso para aprobarla o solicitar ajustes.');
    }

    public static function requestChanges(Ticket $ticket, TicketDelegation $delegation, User $actor, array $data): void
    {
        $current = DB::transaction(function () use ($ticket, $delegation, $actor, $data) {
            [$current, $actor] = self::lockContext($ticket, $actor, [$delegation->delegate_id]);
            Gate::forUser($actor)->authorize('delegate', $current);
            $row = self::row($current, $delegation->id, (int) $data['version']);
            self::assertBound($current, $row);
            if ($row->status !== TicketDelegation::STATUS_SUBMITTED) self::stale();
            $row->update([
                'status' => TicketDelegation::STATUS_ACTIVE, 'feedback' => $data['feedback'],
                'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'version' => $row->version + 1,
            ]);
            self::resumeWork($current, $actor);
            self::record($current, $actor, $row, 'changes_requested', ['feedback' => $row->feedback]);
            return $current;
        });
        TicketDelegationNotifier::notifyDelegate($current, 'delegation_changes', 'TI solicitó ajustes en tu propuesta. Revisa las indicaciones dentro de este caso.');
    }

    /** Solo dentro de la transacción y los bloqueos de la resolución existente. */
    public static function approveLocked(Ticket $ticket, User $actor, int $id, int $version): TicketDelegation
    {
        Gate::forUser($actor)->authorize('delegate', $ticket);
        $row = self::row($ticket, $id, $version);
        self::assertBound($ticket, $row);
        if ($row->status !== TicketDelegation::STATUS_SUBMITTED || !$row->proposal_text) self::stale();
        $row->update([
            'status' => TicketDelegation::STATUS_APPROVED, 'reviewed_by' => $actor->id,
            'reviewed_at' => now(), 'ended_at' => now(), 'version' => $row->version + 1,
        ]);
        self::record($ticket, $actor, $row, 'approved');
        return $row;
    }

    public static function assertNoPendingReview(Ticket $ticket): void
    {
        $current = TicketDelegationAccess::current($ticket);
        if ($current && in_array($current->status, TicketDelegation::OPEN_STATUSES, true)
            && $current->responsible_id === (int) $ticket->assigned_to
            && $current->reopened_count === (int) $ticket->reopened_count) {
            throw ValidationException::withMessages(['delegation' => 'Aprueba la propuesta del encargo o retíralo antes de registrar una solución independiente.']);
        }
    }

    private static function row(Ticket $ticket, int $id, int $version): TicketDelegation
    {
        $row = TicketDelegation::where('ticket_id', $ticket->id)->whereKey($id)->lockForUpdate()->first();
        abort_unless($row, 404);
        if ($row->version !== $version || TicketDelegationAccess::current($ticket)?->id !== $id) self::stale();
        return $row;
    }

    private static function assertBound(Ticket $ticket, TicketDelegation $row): void
    {
        if ($row->responsible_id !== (int) $ticket->assigned_to
            || $row->reopened_count !== (int) $ticket->reopened_count) self::stale();
    }

    private static function resumeWork(Ticket $ticket, User $actor): void
    {
        $previous = $ticket->status;
        if ($previous !== Ticket::STATUS_IN_PROGRESS || $ticket->response_deadline_at !== null) {
            $ticket->update(['status' => Ticket::STATUS_IN_PROGRESS, 'response_deadline_at' => null]);
            if ($previous !== $ticket->status) TicketHistory::create([
                'ticket_id' => $ticket->id, 'user_id' => $actor->id, 'action' => 'status_change',
                'old_value' => $previous, 'new_value' => $ticket->status, 'field_name' => 'status',
            ]);
        }
    }

    private static function record(Ticket $ticket, User $actor, TicketDelegation $row, string $action, array $details = []): void
    {
        TicketHistory::create([
            'ticket_id' => $ticket->id, 'user_id' => $actor->id, 'action' => 'delegation_'.$action,
            'old_value' => null, 'new_value' => (string) $row->delegate_id, 'field_name' => 'delegation',
        ]);
        AuditLog::record('ticket.delegation.'.$action, 'Ticket', $ticket->id,
            ['delegation_id' => $row->id, 'version' => $row->version] + $details, $actor->id);
    }

    private static function stale(): never
    {
        throw ValidationException::withMessages(['delegation' => 'El ticket o el encargo cambió. Actualiza la página antes de continuar.']);
    }
}
