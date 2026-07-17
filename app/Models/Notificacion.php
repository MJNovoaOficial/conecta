<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'ticket_id', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Crea una notificación para un usuario.
     */
    public static function notify(int $userId, string $type, string $title, string $body = '', ?int $ticketId = null): self
    {
        return static::create([
            'user_id'   => $userId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'ticket_id' => $ticketId,
        ]);
    }
}
