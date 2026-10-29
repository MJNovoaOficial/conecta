@php($images = $entry->attachments->filter(fn ($file) => $file->isPreviewableImage()))
@if($images->isNotEmpty())
<div class="log-images" aria-label="Imágenes de la actualización">
    @foreach($images as $image)
    <figure class="log-image">
        <a class="log-image-open" href="{{ route('project-log.image', [$entry, $image]) }}" target="_blank" rel="noopener" aria-label="Abrir imagen completa: {{ $image->original_name }} (nueva pestaña)">
            <img src="{{ route('project-log.image', [$entry, $image]) }}" alt="{{ $image->original_name }}" loading="lazy" decoding="async" data-log-image>
            <span class="log-image-fallback" hidden>Vista previa no disponible. Puedes intentar descargar el archivo.</span>
        </a>
        <figcaption><span>{{ $image->original_name }}</span><a href="{{ route('project-log.download', [$entry, $image]) }}">Descargar</a></figcaption>
    </figure>
    @endforeach
</div>
@once
@push('scripts')
<script src="{{ \App\Support\VersionedAsset::url('js/project-log.js') }}" defer></script>
@endpush
@endonce
@endif
