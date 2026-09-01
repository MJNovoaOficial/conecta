<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'user_id',
        'department_id',
        'title',
        'description',
        'status',
        'category',
        'subcategoria_id',
        'tipo_incidente_id',
        'device_type',
        'priority',
        'assigned_to',
        'solution_text',
        'created_at',
        'updated_at',
        'last_response_request_at',
        'response_deadline_at',
        'sla_response_deadline_at',
        'sla_resolution_deadline_at',
        'user_responded_at',
        'closed_at',
        'resolved_at',
        'guest_name',
        'guest_email',
        'guest_department',
        'guest_token',
    ];

    protected $casts = [
        'created_at'                  => 'datetime',
        'updated_at'                  => 'datetime',
        'last_response_request_at'    => 'datetime',
        'response_deadline_at'        => 'datetime',
        'sla_response_deadline_at'    => 'datetime',
        'sla_resolution_deadline_at'  => 'datetime',
        'user_responded_at'           => 'datetime',
        'closed_at'                   => 'datetime',
        'resolved_at'                 => 'datetime',
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

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function tipoIncidente()
    {
        return $this->belongsTo(TipoIncidente::class, 'tipo_incidente_id');
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


    /**
     * Tiempo de reloj desde que se creó el ticket hasta que se resolvió, o
     * hasta ahora si sigue abierto.
     *
     * Se muestra junto al tiempo de soporte y el contraste entre ambos es lo
     * informativo: cuánto esperó la persona frente a cuánto se trabajó.
     *
     * Un ticket terminado deja de sumar. Midiendo siempre contra la fecha de
     * hoy, uno cerrado en junio seguía envejeciendo en pantalla.
     */
    public function getTimeElapsedFormatted()
    {
        $hasta = $this->resolved_at ?? $this->closed_at ?? Carbon::now();
        $diff  = $this->created_at->diff($hasta);

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
     * Minutos de atención de soporte.
     *
     * Descuenta el tiempo que el ticket estuvo esperando al solicitante, y deja
     * de contar cuando el ticket se resolvió.
     *
     * Se mide en horas de reloj y no en horas hábiles a propósito: si alguien
     * resuelve algo un sábado, ese trabajo existió. Contar solo horario laboral
     * mostraría "0 minutos de atención" en un ticket que costó una tarde de
     * fin de semana.
     */
    public function getSupportMinutes(): int
    {
        // Un ticket terminado deja de acumular tiempo en el momento en que se
        // resolvió. Midiendo siempre contra la fecha de hoy, un ticket cerrado
        // en junio mostraba "72 días de atención" habiéndose resuelto en 52
        // horas, y el número seguía subiendo solo.
        $hasta = $this->resolved_at ?? $this->closed_at ?? Carbon::now();

        $minutos = $this->created_at->diffInMinutes($hasta);

        // El rato que el ticket estuvo esperando al solicitante no es tiempo de
        // atención: soporte no podía avanzar.
        if ($this->last_response_request_at && $this->user_responded_at) {
            $espera  = $this->last_response_request_at->diffInMinutes($this->user_responded_at);
            $minutos = max(0, $minutos - $espera);
        } elseif ($this->last_response_request_at && $this->status === self::STATUS_PENDING_USER) {
            // Sigue esperando: se descuenta hasta ahora.
            $espera  = $this->last_response_request_at->diffInMinutes(Carbon::now());
            $minutos = max(0, $minutos - $espera);
        }

        return (int) $minutos;
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

    /**
     * Obtiene el nombre completo de la clasificación del ticket.
     */
    public function getClassificationLabel(): string
    {
        $parts = [];
        if ($this->subcategoria) {
            if ($this->subcategoria->categoria) {
                $parts[] = $this->subcategoria->categoria->name;
            }
            $parts[] = $this->subcategoria->name;
        } elseif ($this->category) {
            $parts[] = $this->category;
        }
        if ($this->tipoIncidente) {
            $parts[] = $this->tipoIncidente->name;
        }
        return implode(' › ', $parts) ?: 'Sin clasificar';
    }

    /**
     * Tiempo que falta para la fecha límite de resolución del SLA, o el que lleva
     * vencido si ya pasó. Formato legible (Xd Yh / Xh Ymin / X min).
     *
     * Devuelve null si el ticket no tiene plazo definido o si ya está resuelto o
     * cerrado, casos en los que una cuenta regresiva no significa nada.
     */
    public function getSlaRemainingFormatted(): ?string
    {
        if (!$this->sla_resolution_deadline_at) {
            return null;
        }

        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            return null;
        }

        $minutos = (int) round(abs(Carbon::now()->diffInMinutes($this->sla_resolution_deadline_at)));

        if ($minutos >= 1440) {
            $dias  = intdiv($minutos, 1440);
            $horas = intdiv($minutos % 1440, 60);
            return $dias . 'd ' . $horas . 'h';
        }
        if ($minutos >= 60) {
            return intdiv($minutos, 60) . 'h ' . ($minutos % 60) . 'min';
        }
        return $minutos . ' min';
    }

    /**
     * Calcula el estado del SLA de resolución.
     * Retorna: 'none', 'ok', 'warning' (75% del tiempo usado) o 'exceeded'.
     *
     * Es la única fuente para esta pregunta: la vista de reportes, el listado y
     * la ficha del ticket la consultan en vez de calcularla por su cuenta.
     */
    public function getSlaResolutionStatus(): string
    {
        if (!$this->sla_resolution_deadline_at) {
            return 'none';
        }

        // Un ticket terminado se juzga por cuándo se resolvió, no por la fecha
        // de hoy: el plazo dejó de correr en ese momento. Sin esto, uno
        // atendido a tiempo aparecía vencido con solo dejar pasar los días, y
        // uno resuelto tarde quedaba como cumplido para siempre.
        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            $cierre = $this->resolved_at ?? $this->updated_at;

            return $cierre->isAfter($this->sla_resolution_deadline_at) ? 'exceeded' : 'ok';
        }

        $now = Carbon::now();
        if ($now->isAfter($this->sla_resolution_deadline_at)) {
            return 'exceeded';
        }
        // Warning si ya se usó más del 75% del tiempo
        $total = $this->created_at->diffInMinutes($this->sla_resolution_deadline_at);
        $used  = $this->created_at->diffInMinutes($now);
        if ($total > 0 && ($used / $total) >= 0.75) {
            return 'warning';
        }
        return 'ok';
    }

}

