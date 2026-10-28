<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLogEntry extends Model
{
    public const TYPES = ['creation' => 'Creación', 'improvement' => 'Mejora', 'correction' => 'Corrección'];

    protected $fillable = ['project_id', 'author_id', 'author_name', 'responsible_id', 'responsible_name',
        'title', 'description', 'type', 'performed_on', 'submission_key', 'version'];

    protected $casts = ['performed_on' => 'date', 'version' => 'integer'];

    public function project()
    {
        return $this->belongsTo(ProjectLogProject::class, 'project_id');
    }

    public function attachments()
    {
        return $this->hasMany(ProjectLogAttachment::class, 'entry_id');
    }
}
