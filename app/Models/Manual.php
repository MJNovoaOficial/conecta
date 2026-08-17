<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Manual extends Model
{
    protected $table = 'manuales';

    protected $fillable = [
        'titulo',
        'descripcion',
        'categoria',
        'archivo_path',
        'archivo_nombre_original',
        'archivo_size',
        'downloads_count',
        'is_active',
        'uploaded_by',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'downloads_count' => 'integer',
        'archivo_size'    => 'integer',
    ];

    // ── Relaciones ────────────────────────────────────────────────

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * URL pública del PDF.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->archivo_path);
    }

    /**
     * Tamaño formateado (ej: "1.2 MB").
     */
    public function getTamanoFormateadoAttribute(): string
    {
        $bytes = $this->archivo_size;
        if ($bytes < 1024)       return $bytes . ' B';
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }
}
