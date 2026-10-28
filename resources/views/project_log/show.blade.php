@extends('layouts.app')
@section('title', $entry->title.' — Bitácora')
@include('project_log.styles')
@section('content')
<main class="log-shell log-form-shell">
    <a href="{{ route('project-log.index', ['date_from' => $entry->performed_on->copy()->startOfMonth()->format('Y-m-d'), 'date_to' => $entry->performed_on->copy()->endOfMonth()->format('Y-m-d')]) }}" class="log-back">← Volver al período del trabajo</a>
    <article class="log-panel log-detail">
        <div class="log-entry-top"><span class="log-type log-type-{{ $entry->type }}">{{ \App\Models\ProjectLogEntry::TYPES[$entry->type] ?? $entry->type }}</span><span class="log-project">{{ $entry->project->name }}</span></div>
        <h1>{{ $entry->title }}</h1>
        <dl class="log-meta"><div><dt>Fecha del trabajo</dt><dd>{{ $entry->performed_on->format('d/m/Y') }}</dd></div><div><dt>Responsable</dt><dd>{{ $entry->responsible_name }}</dd></div><div><dt>Publicado por</dt><dd>{{ $entry->author_name }}</dd></div><div><dt>Publicación</dt><dd>{{ \App\Support\DisplayDate::format($entry->created_at) }}</dd></div></dl>
        <div class="log-description">{{ $entry->description }}</div>
        @if($entry->attachments->isNotEmpty())
        <section class="log-attachments" aria-labelledby="log-attachments-title"><h2 id="log-attachments-title">Adjuntos</h2><ul>@foreach($entry->attachments as $file)<li><a href="{{ route('project-log.download', [$entry, $file]) }}">{{ $file->original_name }}</a> <span>({{ number_format($file->size / 1024, 0, ',', '.') }} KB)</span></li>@endforeach</ul></section>
        @endif
        <footer class="log-detail-footer"><span>Última edición: {{ \App\Support\DisplayDate::format($entry->updated_at) }}</span>@if(\App\Support\ProjectLogAccess::canEdit(auth()->user(), $entry))<a href="{{ route('project-log.edit', $entry) }}" class="log-button log-secondary">Editar actualización</a>@endif</footer>
    </article>
</main>
@endsection
