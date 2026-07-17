<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['name', 'description', 'is_active'];

    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class, 'categoria_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'categoria_id');
    }
}
