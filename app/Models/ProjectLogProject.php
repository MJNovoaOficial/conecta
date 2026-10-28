<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLogProject extends Model
{
    protected $fillable = ['name'];

    public function entries()
    {
        return $this->hasMany(ProjectLogEntry::class, 'project_id');
    }
}
