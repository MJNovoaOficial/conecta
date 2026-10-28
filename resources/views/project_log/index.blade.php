@extends('layouts.app')
@section('title', 'Bitácora de proyectos — Conecta')
@include('project_log.styles')
@section('content')
<main class="log-shell">
    <a href="{{ route('tickets.index') }}" class="log-back">← Volver a Conecta</a>
    <header class="log-heading">
        <div><p class="log-eyebrow">ESPACIO INTERNO</p><h1>Bitácora de proyectos</h1><p>Un lugar para compartir lo que creamos, corregimos y mejoramos.</p></div>
        <a href="{{ route('project-log.create') }}" class="log-button">+ Publicar actualización</a>
    </header>
    @include('project_log.errors')
    <section class="log-panel" aria-labelledby="log-filter-title">
        <h2 id="log-filter-title">Consultar un período</h2>
        <p class="log-muted">Se considera la fecha del trabajo, aunque se haya publicado después.</p>
        <form method="GET" action="{{ route('project-log.index') }}" class="log-filters">
            <div><label for="date_from">Desde</label><input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] }}" required></div>
            <div><label for="date_to">Hasta</label><input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] }}" required></div>
            <button class="log-button" type="submit">Consultar</button>
            <button class="log-button log-secondary" type="submit" formaction="{{ route('project-log.export', 'pdf') }}">Descargar PDF</button>
            <button class="log-button log-secondary" type="submit" formaction="{{ route('project-log.export', 'excel') }}">Descargar Excel</button>
        </form>
    </section>
    <div class="log-results"><h2>Actualizaciones</h2><span>{{ $total }} {{ $total === 1 ? 'registro' : 'registros' }}</span></div>
    <div class="log-feed">
    @forelse($entries as $entry)
        <article class="log-panel log-entry">
            <div class="log-entry-top"><span class="log-type log-type-{{ $entry->type }}">{{ \App\Models\ProjectLogEntry::TYPES[$entry->type] ?? $entry->type }}</span><span class="log-project">{{ $entry->project->name }}</span><time datetime="{{ $entry->performed_on->format('Y-m-d') }}">{{ $entry->performed_on->format('d/m/Y') }}</time></div>
            <h3><a href="{{ route('project-log.show', $entry) }}">{{ $entry->title }}</a></h3>
            <p class="log-excerpt">{{ \Illuminate\Support\Str::limit($entry->description, 280) }}</p>
            <div class="log-entry-footer"><span>Responsable: <strong>{{ $entry->responsible_name }}</strong></span><a href="{{ route('project-log.show', $entry) }}">Ver actualización{{ $entry->attachments_count ? ' · '.$entry->attachments_count.($entry->attachments_count === 1 ? ' adjunto' : ' adjuntos') : '' }} →</a></div>
        </article>
    @empty
        <div class="log-panel log-empty"><h3>No hay actualizaciones en este período</h3><p>Prueba con otras fechas o publica el primer trabajo del equipo.</p><a href="{{ route('project-log.create') }}" class="log-button">Publicar actualización</a></div>
    @endforelse
    </div>
    <div class="log-pagination">{{ $entries->links() }}</div>
</main>
@endsection
