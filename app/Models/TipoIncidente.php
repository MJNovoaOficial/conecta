<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoIncidente extends Model
{
    protected $table = 'tipos_incidente';

    protected $fillable = ['subcategoria_id', 'name', 'description', 'is_active'];

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'tipo_incidente_id');
    }
}
