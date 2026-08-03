<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryDepartmentRule extends Model
{
    protected $table = 'category_department_rules';

    protected $fillable = ['categoria_id', 'department_id'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Retorna el department_id configurado para una categoría, o null si no hay regla.
     */
    public static function resolve(int $categoriaId): ?int
    {
        return static::where('categoria_id', $categoriaId)->value('department_id');
    }
}
