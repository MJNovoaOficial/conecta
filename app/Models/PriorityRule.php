<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriorityRule extends Model
{
    protected $table = 'priority_rules';

    protected $fillable = [
        'categoria_id', 'subcategoria_id', 'tipo_incidente_id', 'priority', 'description'
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function tipoIncidente()
    {
        return $this->belongsTo(TipoIncidente::class, 'tipo_incidente_id');
    }

    /**
     * Resolver la prioridad dada una subcategoría y tipo de incidente.
     * Busca la regla más específica primero (3 campos → 2 → 1).
     * Si no hay regla, retorna 'medium' por defecto.
     */
    public static function resolve(?int $subcategoriaId, ?int $tipoIncidenteId): string
    {
        if (!$subcategoriaId) {
            return 'medium';
        }

        $categoriaId = Subcategoria::find($subcategoriaId)?->categoria_id;

        // Intentar regla más específica: categoria + subcategoria + tipo
        if ($tipoIncidenteId && $subcategoriaId && $categoriaId) {
            $rule = static::where('categoria_id', $categoriaId)
                ->where('subcategoria_id', $subcategoriaId)
                ->where('tipo_incidente_id', $tipoIncidenteId)
                ->first();
            if ($rule) return $rule->priority;
        }

        // Regla: categoria + subcategoria (sin tipo)
        if ($subcategoriaId && $categoriaId) {
            $rule = static::where('categoria_id', $categoriaId)
                ->where('subcategoria_id', $subcategoriaId)
                ->whereNull('tipo_incidente_id')
                ->first();
            if ($rule) return $rule->priority;
        }

        // Regla: solo categoria
        if ($categoriaId) {
            $rule = static::where('categoria_id', $categoriaId)
                ->whereNull('subcategoria_id')
                ->whereNull('tipo_incidente_id')
                ->first();
            if ($rule) return $rule->priority;
        }

        return 'medium'; // Prioridad por defecto
    }

    public static function priorityLabels(): array
    {
        return [
            'critical' => '🔴 Crítica',
            'high'     => '🟠 Alta',
            'medium'   => '🟡 Media',
            'low'      => '🟢 Baja',
        ];
    }
}
