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
        'user_responded_at',
        'guest_name',
        'guest_email',
        'guest_department',
        'guest_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_response_request_at' => 'datetime',
        'response_deadline_at' => 'datetime',
        'user_responded_at' => 'datetime',
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

    // Métodos para invitados
    public function isGuestTicket()
    {
        return is_null($this->user_id);
    }

    public function getCreatorName()
    {
        if ($this->isGuestTicket()) {
            return $this->guest_name ?? 'Invitado';
        }
        return $this->user->name ?? 'Desconocido';
    }

    public function getCreatorEmail()
    {
        if ($this->isGuestTicket()) {
            return $this->guest_email;
        }
        return $this->user->email ?? '';
    }

    public function getCreatorDepartment()
    {
        if ($this->isGuestTicket()) {
            return $this->guest_department ?? 'N/A';
        }
        return $this->user->department->name ?? 'N/A';
    }

    // Colores de estado según el PDF:
    // 🟢 Abierto (verde), 🟡 En Proceso (amarillo), 🟠 Pendiente Usuario (naranja),
    // 🔵 Derivado (azul), ✅ Resuelto (verde check), ⚫ Cerrado (negro)
    public function getStatusBadgeClass()
    {
        return match($this->status) {
            self::STATUS_OPEN => 'badge bg-success',
            self::STATUS_IN_PROGRESS => 'badge bg-warning text-dark',
            self::STATUS_PENDING_USER => 'badge badge-orange',
            self::STATUS_FORWARDED => 'badge bg-primary',
            self::STATUS_RESOLVED => 'badge badge-resolved',
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

    public function getStatusIcon()
    {
        return match($this->status) {
            self::STATUS_OPEN => '🟢',
            self::STATUS_IN_PROGRESS => '🟡',
            self::STATUS_PENDING_USER => '🟠',
            self::STATUS_FORWARDED => '🔵',
            self::STATUS_RESOLVED => '✅',
            self::STATUS_CLOSED => '⚫',
            default => '⚪',
        };
    }

    public function getPriorityLabel()
    {
        return match($this->priority) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'critical' => 'Crítica',
            default => ucfirst($this->priority),
        };
    }

    public function getPriorityBadgeClass()
    {
        return match($this->priority) {
            'critical' => 'badge bg-danger',
            'high' => 'badge bg-warning text-dark',
            'medium' => 'badge bg-info',
            'low' => 'badge bg-secondary',
            default => 'badge bg-secondary',
        };
    }

    public function getTimeElapsed()
    {
        return $this->created_at->diffForHumans(Carbon::now());
    }

    public function getTimeElapsedFormatted()
    {
        $diff = $this->created_at->diff(Carbon::now());
        
        if ($diff->days > 0) {
            return $diff->days . 'd ' . $diff->h . 'h';
        }
        if ($diff->h > 0) {
            return $diff->h . 'h ' . $diff->i . 'min';
        }
        return $diff->i . ' min';
    }

    public function isResponseTimeExpired()
    {
        return $this->response_deadline_at && Carbon::now()->isAfter($this->response_deadline_at);
    }

    /**
     * Calcula el tiempo real de atención de soporte,
     * excluyendo el período en que el ticket estuvo "Pendiente Usuario".
     * Retorna los minutos de trabajo efectivo de soporte.
     */
    public function getSupportMinutes(): int
    {
        $totalMinutes = $this->created_at->diffInMinutes(Carbon::now());

        // Si el ticket estuvo en pendiente_usuario y el usuario respondió,
        // restamos el tiempo que estuvo esperando respuesta del usuario.
        if ($this->last_response_request_at && $this->user_responded_at) {
            $waitMinutes = $this->last_response_request_at->diffInMinutes($this->user_responded_at);
            $totalMinutes = max(0, $totalMinutes - $waitMinutes);
        } elseif ($this->last_response_request_at && $this->status === self::STATUS_PENDING_USER) {
            // El ticket sigue esperando respuesta: restar el tiempo desde que se solicitó hasta ahora
            $waitMinutes = $this->last_response_request_at->diffInMinutes(Carbon::now());
            $totalMinutes = max(0, $totalMinutes - $waitMinutes);
        }

        return (int) $totalMinutes;
    }

    /**
     * Devuelve el tiempo real de soporte en formato legible (Xh Ymin).
     */
    public function getSupportTimeFormatted(): string
    {
        $minutes = $this->getSupportMinutes();

        if ($minutes >= 1440) {
            $days = intdiv($minutes, 1440);
            $hours = intdiv($minutes % 1440, 60);
            return $days . 'd ' . $hours . 'h';
        }
        if ($minutes >= 60) {
            $hours = intdiv($minutes, 60);
            $mins = $minutes % 60;
            return $hours . 'h ' . $mins . 'min';
        }
        return $minutes . ' min';
    }
}
