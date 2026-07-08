<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'department_id',
        'title',
        'description',
        'status',
        'category',
        'device_type',
        'priority',
        'assigned_to',
        'created_at',
        'updated_at',
        'last_response_request_at',
        'response_deadline_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_response_request_at' => 'datetime',
        'response_deadline_at' => 'datetime',
    ];

    // Estados posibles
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_USER = 'pending_user';
    const STATUS_FORWARDED = 'forwarded';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignments()
    {
        return $this->belongsToMany(User::class, 'ticket_assignments', 'ticket_id', 'user_id')
                    ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'asc');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function history()
    {
        return $this->hasMany(TicketHistory::class)->orderBy('created_at', 'desc');
    }

    // Métodos útiles
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            self::STATUS_OPEN => 'badge bg-primary',
            self::STATUS_IN_PROGRESS => 'badge bg-info',
            self::STATUS_PENDING_USER => 'badge bg-warning',
            self::STATUS_FORWARDED => 'badge bg-secondary',
            self::STATUS_RESOLVED => 'badge bg-success',
            self::STATUS_CLOSED => 'badge bg-dark',
            default => 'badge bg-secondary',
        };
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            self::STATUS_OPEN => 'Abierto',
            self::STATUS_IN_PROGRESS => 'En Proceso',
            self::STATUS_PENDING_USER => 'Pendiente Usuario',
            self::STATUS_FORWARDED => 'Derivado',
            self::STATUS_RESOLVED => 'Resuelto',
            self::STATUS_CLOSED => 'Cerrado',
            default => 'Desconocido',
        };
    }

    public function getTimeElapsed()
    {
        return $this->created_at->diffForHumans(Carbon::now());
    }

    public function isResponseTimeExpired()
    {
        return $this->response_deadline_at && Carbon::now()->isAfter($this->response_deadline_at);
    }
}
