<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLogAttachment extends Model
{
    protected $fillable = ['path', 'original_name', 'size'];

    public function entry()
    {
        return $this->belongsTo(ProjectLogEntry::class, 'entry_id');
    }
}
