<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLogAttachment extends Model
{
    protected $fillable = ['path', 'original_name', 'size'];

    public function isPreviewableImage(): bool
    {
        return in_array(strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function entry()
    {
        return $this->belongsTo(ProjectLogEntry::class, 'entry_id');
    }
}
