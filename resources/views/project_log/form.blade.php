@extends('layouts.app')
@section('title', ($entry->exists ? 'Editar actualización' : 'Publicar actualización').' — Conecta')
@include('project_log.styles')
@section('content')
<main class="log-shell log-form-shell">
    <a href="{{ $entry->exists ? route('project-log.show', $entry) : route('project-log.index') }}" class="log-back">← Volver a la bitácora</a>
    <header class="log-heading"><div><p class="log-eyebrow">BITÁCORA DE PROYECTOS</p><h1>{{ $entry->exists ? 'Editar actualización' : 'Compartir un avance' }}</h1><p>Registra el trabajo realizado y deja sus detalles a mano para el equipo.</p></div></header>
    @include('project_log.errors')
    <form method="POST" action="{{ $entry->exists ? route('project-log.update', $entry) : route('project-log.store') }}" enctype="multipart/form-data" class="log-panel log-form" data-log-submit>
        @csrf
        @if($entry->exists)
            @method('PUT')<input type="hidden" name="version" value="{{ old('version', $entry->version) }}">
        @else
            <input type="hidden" name="submission_key" value="{{ old('submission_key', $entry->submission_key) }}">
        @endif
        <div class="log-form-grid">
            <div><label for="project_id">Proyecto o sistema</label><select id="project_id" name="project_id"><option value="">Seleccionar proyecto</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id', $entry->project_id) === (string) $project->id)>{{ $project->name }}</option>@endforeach</select><p class="log-help">Elige uno existente o escribe un nombre nuevo.</p></div>
            <div><label for="new_project">Nuevo proyecto <span>(opcional)</span></label><input id="new_project" name="new_project" maxlength="150" value="{{ old('new_project') }}" placeholder="Nombre del proyecto o sistema"><p class="log-help">Para crearlo, deja sin seleccionar el proyecto existente.</p></div>
            <div class="log-full"><label for="title">Título de la actualización</label><input id="title" name="title" maxlength="180" required value="{{ old('title', $entry->title) }}" placeholder="¿Qué se creó o mejoró?"></div>
            <div><label for="performed_on">Fecha del trabajo</label><input id="performed_on" type="date" name="performed_on" required value="{{ old('performed_on', $entry->performed_on?->format('Y-m-d')) }}"><p class="log-help">Esta fecha determina en qué reporte aparecerá.</p></div>
            <div><label for="type">Tipo de actualización</label><select id="type" name="type" required><option value="">Seleccionar tipo</option>@foreach(\App\Models\ProjectLogEntry::TYPES as $value => $label)<option value="{{ $value }}" @selected(old('type', $entry->type) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="log-full"><label for="responsible_id">Responsable del trabajo</label><select id="responsible_id" name="responsible_id" required><option value="">Seleccionar responsable</option>
                @if($entry->exists)<option value="existing" @selected(old('responsible_id', 'existing') === 'existing')>Conservar: {{ $entry->responsible_name }}</option>@endif
                @foreach($people as $person)<option value="{{ $person->id }}" @selected((string) old('responsible_id', $entry->exists ? 'existing' : $entry->responsible_id) === (string) $person->id)>{{ $person->name }}</option>@endforeach</select>
                @if($entry->exists)<p class="log-help">Puedes conservar al responsable histórico, aunque su cuenta ya no esté activa.</p>@endif
            </div>
            <div class="log-full"><label for="description">Descripción del trabajo</label><textarea id="description" name="description" rows="9" maxlength="10000" required placeholder="Explica qué se hizo, para qué sirve y cualquier detalle relevante.">{{ old('description', $entry->description) }}</textarea></div>
            <div class="log-full"><label for="attachments">Capturas o archivos <span>(opcional)</span></label><input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.txt,.docx,.xlsx,.pptx,.zip"><p class="log-help">Hasta 3 archivos en total, de 5 MB cada uno. PDF, JPG, PNG, GIF, WEBP, TXT, DOCX, XLSX, PPTX o ZIP. Si el formulario da un error, vuelve a seleccionar los archivos.</p>
            @if($entry->exists && $entry->attachments->isNotEmpty())<p class="log-help">Adjuntos conservados: {{ $entry->attachments->pluck('original_name')->implode(', ') }}.</p>@endif</div>
        </div>
        <div class="log-form-actions"><a href="{{ $entry->exists ? route('project-log.show', $entry) : route('project-log.index') }}" class="log-button log-secondary">Cancelar</a><button type="submit" class="log-button">{{ $entry->exists ? 'Guardar cambios' : 'Publicar actualización' }}</button></div>
    </form>
</main>
@endsection
@push('scripts')
<script src="{{ \App\Support\VersionedAsset::url('js/project-log.js') }}" defer></script>
@endpush
