<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Encargo de un caso concreto: no otorga un rol de soporte ni mueve assigned_to. */
class TicketDelegation extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REVOKED = 'revoked';
    public const OPEN_STATUSES = [self::STATUS_ACTIVE, self::STATUS_SUBMITTED];
    public const VIEWABLE_STATUSES = [self::STATUS_ACTIVE, self::STATUS_SUBMITTED, self::STATUS_APPROVED];

    protected $table = 'ticket_delegations';

    protected $fillable = [
        'ticket_id', 'delegate_id', 'responsible_id', 'delegated_by', 'department_id',
        'instructions', 'status', 'proposal_text', 'version', 'reopened_count', 'feedback',
        'submitted_at', 'reviewed_at', 'ended_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'ticket_id' => 'integer', 'delegate_id' => 'integer', 'responsible_id' => 'integer',
            'delegated_by' => 'integer', 'department_id' => 'integer', 'reviewed_by' => 'integer',
            'version' => 'integer', 'reopened_count' => 'integer',
            'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'ended_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeViewable(Builder $query): Builder
    {
        return $query->whereIn('status', self::VIEWABLE_STATUSES);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function delegatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
