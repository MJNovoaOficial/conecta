<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $table = 'login_attempts';

    public $timestamps = false;

    protected $fillable = [
        'email', 'ip_address', 'user_agent', 'successful', 'attempted_at',
    ];

    protected $casts = [
        'successful'   => 'boolean',
        'attempted_at' => 'datetime',
    ];

    /**
     * Registra un intento de login.
     */
    public static function record(string $email, string $ip, string $userAgent = '', bool $successful = false): self
    {
        return static::create([
            'email'        => strtolower($email),
            'ip_address'   => $ip,
            'user_agent'   => substr($userAgent, 0, 500),
            'successful'   => $successful,
            'attempted_at' => now(),
        ]);
    }
}
