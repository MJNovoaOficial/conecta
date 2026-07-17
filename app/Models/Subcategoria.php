<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    protected $table = 'subcategorias';

    protected $fillable = ['categoria_id', 'name', 'description', 'is_active'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function tiposIncidente()
    {
        return $this->hasMany(TipoIncidente::class, 'subcategoria_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'subcategoria_id');
    }
}
