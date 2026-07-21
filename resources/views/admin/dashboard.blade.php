@extends('layouts.app')

@section('title', 'Panel de Administración — Conecta')

@section('styles')
<style>
    .dash-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        padding: 28px 24px 48px;
    }

    /* ── HEADER ── */
    .dash-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
    }
    .dash-header-left h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a2332;
        margin: 0 0 3px;
    }
    .dash-header-left p {
        color: #718096;
        font-size: 0.85rem;
        margin: 0;
    }
    .dash-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #1a2332;
        color: #fff;
        font-size: 0.82rem;
        font-weight: 600;
        padding: 8px 18px;
        border-radius: 8px;
        border: none;
        text-decoration: none;
        transition: background 0.2s;
        white-space: nowrap;
        letter-spacing: 0.01em;
    }
    .dash-back-btn:hover {
        background: #2d3748;
        color: #fff;
    }

    /* ── STAT CARDS ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e8ecf0;
        box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        padding: 20px 18px 16px;
        position: relative;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
    }
    .stat-users::before    { background: linear-gradient(90deg, #3498db, #74b9ff); }
    .stat-tickets::before  { background: linear-gradient(90deg, #e74c3c, #ff7675); }
    .stat-open::before     { background: linear-gradient(90deg, #27ae60, #55efc4); }
    .stat-resolved::before { background: linear-gradient(90deg, #8e44ad, #a29bfe); }

    .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-bottom: 12px;
    }
    .icon-users    { background: #ebf5fb; color: #2980b9; }
    .icon-tickets  { background: #fdecea; color: #c0392b; }
    .icon-open     { background: #eafaf1; color: #1e8449; }
    .icon-resolved { background: #f3eafe; color: #6c3483; }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #1a2332;
        line-height: 1;
        margin-bottom: 4px;
    }
    .stat-label {
        font-size: 0.78rem;
        color: #718096;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .stat-sub {
        font-size: 0.72rem;
        color: #a0aec0;
        margin-top: 6px;
    }

    /* ── TICKET STATUS ROW ── */
    .status-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        margin-bottom: 24px;
    }
    .status-chip {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e8ecf0;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .chip-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .chip-info { flex: 1; }
    .chip-name { font-size: 0.75rem; color: #718096; }
    .chip-num  { font-size: 1.1rem; font-weight: 700; color: #1a2332; }

    /* ── BOTTOM GRID ── */
    .bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .dash-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e8ecf0;
        box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .dash-card-header {
        background: #f7f9fc;
        border-bottom: 1px solid #e8ecf0;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .dash-card-header h3 {
        font-size: 0.85rem;
        font-weight: 700;
        color: #2d3748;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .dash-card-header i { color: #a0aec0; font-size: 0.8rem; }
    .dash-card-body { padding: 16px 20px; }

    /* Nav links */
    .nav-link-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        border-radius: 8px;
        text-decoration: none;
        color: #2d3748;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 8px;
        border: 1px solid #e8ecf0;
        transition: all 0.15s;
        background: #fff;
    }
    .nav-link-item:last-child { margin-bottom: 0; }
    .nav-link-item:hover {
        background: #ebf5fb;
        border-color: #3498db;
        color: #2980b9;
        transform: translateX(3px);
    }
    .nav-link-item .link-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .link-icon {
        width: 32px; height: 32px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
    }
    .link-icon-blue   { background: #ebf5fb; color: #2980b9; }
    .link-icon-purple { background: #f3eafe; color: #6c3483; }
    .link-icon-green  { background: #eafaf1; color: #1e8449; }
    .nav-link-item .arrow { color: #cbd5e0; font-size: 0.7rem; }

    /* Info system */
    .sys-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f2f5;
        font-size: 0.85rem;
    }
    .sys-info-row:last-child { border-bottom: none; padding-bottom: 0; }
    .sys-info-row .label { color: #718096; }
    .sys-info-row .value { font-weight: 600; color: #2d3748; }
    .sys-badge-on  { background: #eafaf1; color: #1e8449; border: 1px solid #a9dfbf; padding: 2px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
    .breadcrumb-admin {
        font-size: 0.8rem;
        color: #a0aec0;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .breadcrumb-admin a { color: #3498db; text-decoration: none; }
</style>
@endsection

@section('content')
<div class="dash-wrapper">

    <div class="breadcrumb-admin">
        <a href="{{ route('tickets.index') }}">Inicio</a>
        <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
        <span>Admin</span>
    </div>

    {{-- HEADER --}}
    <div class="dash-header">
        <div class="dash-header-left">
            <h1><i class="fas fa-shield-alt" style="color:#3498db; margin-right:8px;"></i>Panel de Administración</h1>
            <p>Vista general del sistema · {{ now()->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('tickets.index') }}" class="dash-back-btn">
            &#8592; Volver
        </a>
    </div>

    {{-- STATS PRINCIPALES --}}
    <div class="stats-grid">
        <div class="stat-card stat-users">
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-label">Usuarios</div>
            <div class="stat-sub">{{ $activeUsers }} activos en el sistema</div>
        </div>
        <div class="stat-card stat-tickets">
            <div class="stat-value">{{ $totalTickets }}</div>
            <div class="stat-label">Tickets totales</div>
            <div class="stat-sub">{{ $closedTickets }} cerrados</div>
        </div>
        <div class="stat-card stat-open">
            <div class="stat-value">{{ $openTickets }}</div>
            <div class="stat-label">Tickets abiertos</div>
            <div class="stat-sub">{{ $inProgressTickets }} en proceso</div>
        </div>
        <div class="stat-card stat-resolved">
            <div class="stat-value">{{ $resolvedTickets }}</div>
            <div class="stat-label">Resueltos</div>
            <div class="stat-sub">{{ $pendingTickets }} pendientes usuario</div>
        </div>
        {{-- Tiempo promedio resolución (RN-24) --}}
        <div class="stat-card" style="background:linear-gradient(135deg,#1a2332,#2d3e55);border-left:3px solid #2ecc71;">
            <div class="stat-value" style="color:#2ecc71;">
                @if($avgResolutionHours !== null)
                    {{ $avgResolutionHours >= 24 ? round($avgResolutionHours/24,1).'d' : round($avgResolutionHours,1).'h' }}
                @else
                    —
                @endif
            </div>
            <div class="stat-label">Tiempo Prom. Resolución</div>
            <div class="stat-sub">Promedio de tickets resueltos</div>
        </div>
        {{-- Cumplimiento SLA (RN-17, RN-24) --}}
        <div class="stat-card" style="background:linear-gradient(135deg,#1a2332,#2d3e55);border-left:3px solid {{ ($slaCompliance !== null && $slaCompliance >= 80) ? '#3498db' : '#e74c3c' }};">
            <div class="stat-value" style="color:{{ ($slaCompliance !== null && $slaCompliance >= 80) ? '#3498db' : '#e74c3c' }};">
                {{ $slaCompliance !== null ? $slaCompliance.'%' : '—' }}
            </div>
            <div class="stat-label">Cumplimiento SLA</div>
            <div class="stat-sub">Tickets resueltos dentro del plazo</div>
        </div>
    </div>

    {{-- ESTADO DE TICKETS --}}
    <div class="status-row">
        <div class="status-chip">
            <div class="chip-dot" style="background:#27ae60;"></div>
            <div class="chip-info">
                <div class="chip-name">Abierto</div>
                <div class="chip-num">{{ $openTickets }}</div>
            </div>
        </div>
        <div class="status-chip">
            <div class="chip-dot" style="background:#f39c12;"></div>
            <div class="chip-info">
                <div class="chip-name">En Proceso</div>
                <div class="chip-num">{{ $inProgressTickets }}</div>
            </div>
        </div>
        <div class="status-chip">
            <div class="chip-dot" style="background:#e67e22;"></div>
            <div class="chip-info">
                <div class="chip-name">Pend. Usuario</div>
                <div class="chip-num">{{ $pendingTickets }}</div>
            </div>
        </div>
        <div class="status-chip">
            <div class="chip-dot" style="background:#718096;"></div>
            <div class="chip-info">
                <div class="chip-name">Cerrado</div>
                <div class="chip-num">{{ $closedTickets }}</div>
            </div>
        </div>
    </div>

    {{-- GESTIÓN --}}
    <div class="dash-card" style="max-width:520px;">
        <div class="dash-card-header">
            <i class="fas fa-th-large"></i>
            <h3>Gestión del Sistema</h3>
        </div>
        <div class="dash-card-body">
            <a href="{{ route('admin.users') }}" class="nav-link-item">
                <div class="link-left">
                    <div class="link-icon link-icon-blue"><i class="fas fa-users"></i></div>
                    <div>
                        <div style="font-weight:600;">Usuarios</div>
                        <div style="font-size:0.75rem; color:#a0aec0;">{{ $totalUsers }} registrados · {{ $activeUsers }} activos</div>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            <a href="{{ route('admin.departments') }}" class="nav-link-item">
                <div class="link-left">
                    <div class="link-icon link-icon-purple"><i class="fas fa-sitemap"></i></div>
                    <div>
                        <div style="font-weight:600;">Departamentos</div>
                        <div style="font-size:0.75rem; color:#a0aec0;">{{ $totalDepts }} áreas configuradas</div>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            <a href="{{ route('tickets.index') }}" class="nav-link-item">
                <div class="link-left">
                    <div class="link-icon link-icon-green"><i class="fas fa-ticket-alt"></i></div>
                    <div>
                        <div style="font-weight:600;">Ver todos los tickets</div>
                        <div style="font-size:0.75rem; color:#a0aec0;">{{ $totalTickets }} tickets en total</div>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            <a href="{{ route('admin.reports') }}" class="nav-link-item">
                <div class="link-left">
                    <div class="link-icon" style="background:#fef3c7;color:#d97706;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div>
                        <div style="font-weight:600;">Reportes</div>
                        <div style="font-size:0.75rem; color:#a0aec0;">Exportar CSV y PDF</div>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            <a href="{{ route('admin.audit') }}" class="nav-link-item">
                <div class="link-left">
                    <div class="link-icon" style="background:#ffe4e6;color:#be123c;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <div style="font-weight:600;">Auditoría</div>
                        <div style="font-size:0.75rem; color:#a0aec0;">Historial de acciones</div>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            <a href="{{ route('admin.settings') }}" class="nav-link-item">
                <div class="link-left">
                    <div class="link-icon" style="background:#e0f2fe;color:#0369a1;width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <div style="font-weight:600;">Configuración</div>
                        <div style="font-size:0.75rem; color:#a0aec0;">General, Notificaciones, SLA, Seguridad</div>
                    </div>
                </div>
                <i class="fas fa-chevron-right arrow"></i>
            </a>
        </div>
    </div>

</div>

{{-- ── GRÁFICOS ────────────────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:24px;max-width:1100px;margin-left:auto;margin-right:auto;padding:0 24px 48px;">

    {{-- Gráfico de prioridad --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <i class="fas fa-chart-pie"></i>
            <h3>Tickets por Prioridad</h3>
        </div>
        <div class="dash-card-body" style="display:flex;align-items:center;justify-content:center;padding:20px;">
            <canvas id="priorityChart" style="max-height:220px;max-width:220px;"></canvas>
        </div>
    </div>

    {{-- Gráfico de tendencia mensual --}}
    <div class="dash-card">
        <div class="dash-card-header">
            <i class="fas fa-chart-line"></i>
            <h3>Tendencia Mensual (últimos 6 meses)</h3>
        </div>
        <div class="dash-card-body" style="padding:20px;">
            <canvas id="monthlyChart" style="max-height:220px;"></canvas>
        </div>
    </div>

    {{-- Carga por técnico --}}
    <div class="dash-card" style="grid-column:span 2;">
        <div class="dash-card-header">
            <i class="fas fa-users"></i>
            <h3>Carga de Trabajo por Técnico</h3>
        </div>
        <div class="dash-card-body" style="padding:20px;">
            <canvas id="agentChart" style="max-height:200px;"></canvas>
        </div>
    </div>

    {{-- Top Solicitantes (RN-24) --}}
    <div class="dash-card" style="grid-column:span 2;">
        <div class="dash-card-header">
            <i class="fas fa-trophy"></i>
            <h3>Usuarios con Mayor Cantidad de Solicitudes</h3>
        </div>
        <div class="dash-card-body" style="padding:16px 20px;">
            @if($topRequesters->isEmpty())
                <p style="color:#a0aec0;font-size:.85rem;text-align:center;padding:20px;">Sin datos aún.</p>
            @else
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:2px solid #f0f2f5;">
                        <th style="padding:6px 10px;font-size:.72rem;font-weight:700;color:#718096;text-align:left;width:30px;">#</th>
                        <th style="padding:6px 10px;font-size:.72rem;font-weight:700;color:#718096;text-align:left;">Usuario</th>
                        <th style="padding:6px 10px;font-size:.72rem;font-weight:700;color:#718096;text-align:left;">Departamento</th>
                        <th style="padding:6px 10px;font-size:.72rem;font-weight:700;color:#718096;text-align:left;">Tickets</th>
                        <th style="padding:6px 10px;width:40%;font-size:.72rem;font-weight:700;color:#718096;text-align:left;">Proporción</th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxCount = $topRequesters->first()->ticket_count ?: 1; @endphp
                    @foreach($topRequesters as $i => $req)
                    <tr style="border-bottom:1px solid #f7f9fc;">
                        <td style="padding:8px 10px;font-size:.8rem;font-weight:700;color:#a0aec0;">{{ $i + 1 }}</td>
                        <td style="padding:8px 10px;">
                            <div style="font-size:.84rem;font-weight:600;color:#2d3748;">{{ $req->name }}</div>
                            <div style="font-size:.72rem;color:#a0aec0;">{{ $req->email }}</div>
                        </td>
                        <td style="padding:8px 10px;font-size:.82rem;color:#718096;">{{ $req->department->name ?? '—' }}</td>
                        <td style="padding:8px 10px;">
                            <span style="font-size:.9rem;font-weight:700;color:#3498db;">{{ $req->ticket_count }}</span>
                        </td>
                        <td style="padding:8px 10px;">
                            <div style="background:#e8ecf0;border-radius:4px;height:8px;overflow:hidden;">
                                <div style="background:linear-gradient(90deg,#3498db,#2980b9);height:100%;border-radius:4px;width:{{ round(($req->ticket_count / $maxCount) * 100) }}%;"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Gráfico de prioridad (Doughnut) ──
const priData = @json($byPriority);
const priLabels = { low: 'Baja', medium: 'Media', high: 'Alta', critical: 'Crítica' };
const priColors = { low: '#22c55e', medium: '#3b82f6', high: '#f59e0b', critical: '#ef4444' };
new Chart(document.getElementById('priorityChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(priData).map(k => priLabels[k] || k),
        datasets: [{
            data: Object.values(priData),
            backgroundColor: Object.keys(priData).map(k => priColors[k] || '#94a3b8'),
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { font: { size: 12 }, padding: 12 } } },
        cutout: '65%',
    }
});

// ── Tendencia mensual (Line) ──
const monthly = @json($monthly);
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthly.map(m => m.month),
        datasets: [{
            label: 'Tickets creados',
            data: monthly.map(m => m.total),
            borderColor: '#3498db',
            backgroundColor: 'rgba(52,152,219,0.12)',
            tension: 0.35,
            fill: true,
            pointBackgroundColor: '#3498db',
            pointRadius: 4,
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { display: false } },
    }
});

// ── Carga por técnico (Bar horizontal) ──
const agents = @json($byAgent);
new Chart(document.getElementById('agentChart'), {
    type: 'bar',
    data: {
        labels: agents.map(a => a.name),
        datasets: [{
            label: 'Tickets activos',
            data: agents.map(a => a.active_count),
            backgroundColor: '#3498db',
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        scales: {
            x: { beginAtZero: true, ticks: { stepSize: 1 } },
            y: { grid: { display: false } }
        },
        plugins: { legend: { display: false } },
    }
});
</script>
@endpush

@endsection
