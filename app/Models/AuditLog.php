<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id', 'action', 'model', 'model_id', 'details', 'ip_address',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra una acción en el log de auditoría.
     */
    public static function record(string $action, string $model = '', ?int $modelId = null, array $details = [], ?int $userId = null): self
    {
        return static::create([
            'user_id'    => $userId ?? auth()->id(),
            'action'     => $action,
            'model'      => $model,
            'model_id'   => $modelId,
            'details'    => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
