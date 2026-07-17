<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaConfig extends Model
{
    protected $table = 'sla_configs';

    protected $fillable = ['priority', 'response_hours', 'resolution_hours'];

    /**
     * Obtiene la configuración SLA para una prioridad dada.
     * Si no existe, retorna valores por defecto.
     */
    public static function forPriority(string $priority): self
    {
        return static::firstOrNew(
            ['priority' => $priority],
            ['response_hours' => match($priority) {
                'critical' => 1,
                'high'     => 4,
                'medium'   => 8,
                'low'      => 24,
                default    => 24,
            },
            'resolution_hours' => match($priority) {
                'critical' => 4,
                'high'     => 24,
                'medium'   => 48,
                'low'      => 72,
                default    => 72,
            }]
        );
    }
}
