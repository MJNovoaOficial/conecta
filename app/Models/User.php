<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'department_id',
        'role',
        'avatar_url',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Validación de campos
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->email = strtolower($model->email);
            $model->is_active = $model->is_active ?? true;
        });

        static::updating(function ($model) {
            $model->email = strtolower($model->email);
        });
    }

    // Relaciones
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notificaciones()->whereNull('read_at')->count();
    }

    // Métodos de roles
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isSupport()
    {
        return $this->role === 'support';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }
}
