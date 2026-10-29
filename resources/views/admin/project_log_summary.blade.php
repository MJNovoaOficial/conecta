@push('styles')
<link rel="stylesheet" href="{{ \App\Support\VersionedAsset::url('css/project-log-dashboard.css') }}">
@endpush
<section class="pl-summary" aria-labelledby="pl-summary-title">
    <header class="pl-summary-heading">
        <div><h2 id="pl-summary-title">Actividad de proyectos</h2><p>Mes y año actuales, según la fecha del trabajo registrada en la bitácora.</p></div>
        <a class="pl-summary-link" href="{{ route('project-log.index', $summary['month_range']) }}">Ver bitácora →</a>
    </header>
    <div class="pl-summary-metrics">
        @foreach(['month' => ['del mes', $summary['month_label'], $summary['month_range']], 'year' => ['del año', $summary['year_label'], $summary['year_range']]] as $key => [$label, $period, $range])
        <a class="pl-summary-metric" href="{{ route('project-log.index', $range) }}">
            <span>Proyectos {{ $label }}</span><strong>{{ $summary[$key]['projects'] }}</strong><small>{{ $period }} · proyectos con trabajos registrados</small>
        </a>
        <a class="pl-summary-metric" href="{{ route('project-log.index', $range) }}">
            <span>Actualizaciones {{ $label }}</span><strong>{{ $summary[$key]['updates'] }}</strong><small>{{ $period }} · Creaciones: {{ $summary[$key]['creation'] }} · Mejoras: {{ $summary[$key]['improvement'] }} · Correcciones: {{ $summary[$key]['correction'] }}</small>
        </a>
        @endforeach
    </div>
    <div class="pl-summary-detail">
        <div class="pl-summary-projects">
            <h3>Proyectos con más actividad este mes</h3>
            @forelse($summary['projects'] as $project)
            <div class="pl-summary-project"><span>{{ $project->name }}</span><strong>{{ $project->updates }} {{ (int) $project->updates === 1 ? 'actualización' : 'actualizaciones' }}</strong></div>
            @empty
            <p class="pl-summary-empty">Aún no hay trabajos registrados para {{ $summary['month_label'] }}.</p>
            @endforelse
            @if($summary['month']['projects'] > 5)<p class="pl-summary-note">Se muestran los 5 con más actualizaciones. El total incluye todos los proyectos del mes.</p>@endif
        </div>
        <div class="pl-summary-types">
            <h3>Actualizaciones del mes por tipo</h3>
            @foreach(\App\Models\ProjectLogEntry::TYPES as $type => $label)
            <div class="pl-summary-type"><span>{{ $label }}</span><strong>{{ $summary['month'][$type] }}</strong></div>
            @endforeach
            <p class="pl-summary-note">Un mismo proyecto cuenta una sola vez en cada período, aunque tenga varias actualizaciones.</p>
        </div>
    </div>
</section>
