<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>Reporte de bitácora</title>
<style>
@page { margin: 35px 38px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #253247; word-wrap: break-word; }
h1 { font-size: 22px; margin-bottom: 5px; } h2 { font-size: 14px; margin-bottom: 6px; }
.intro { border-bottom: 2px solid #3498db; padding-bottom: 12px; margin-bottom: 20px; }
.meta { color: #526176; font-size: 9px; }
.entry { margin-bottom: 20px; border-bottom: 1px solid #dce3eb; padding-bottom: 14px; }
.description { white-space: pre-wrap; word-wrap: break-word; line-height: 1.5; }
</style></head><body>
<div class="intro"><h1>Bitácora de proyectos</h1><p>Trabajos del {{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</p><p class="meta">Conecta · {{ $entries->count() }} actualizaciones · Selección por fecha del trabajo</p></div>
@forelse($entries as $entry)
<div class="entry"><p class="meta">{{ $entry->performed_on->format('d/m/Y') }} · {{ $entry->project->name }} · {{ \App\Models\ProjectLogEntry::TYPES[$entry->type] ?? $entry->type }}</p><h2>{{ $entry->title }}</h2><p><strong>Responsable:</strong> {{ $entry->responsible_name }}</p><div class="description">{{ $entry->description }}</div><p class="meta">Publicado por {{ $entry->author_name }} · {{ \App\Support\DisplayDate::format($entry->created_at) }}</p></div>
@empty<p>No hay actualizaciones en el período seleccionado.</p>@endforelse
</body></html>
