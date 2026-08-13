@extends('layouts.app')
@section('title', 'Panel de Administración — Conecta')

@section('content')
<style>
/* ═══ LAYOUT ═══════════════════════════════════════════════════ */
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content { flex:1; padding:28px 28px 48px; background:#f5f7fa; min-width:0; overflow-x:hidden; }

/* ═══ HEADER ═══════════════════════════════════════════════════ */
.dash-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.dash-header h1 { font-size:1.3rem; font-weight:700; color:#1a2332; margin:0 0 2px; }
.dash-header p { color:#718096; font-size:.82rem; margin:0; }

/* ═══ PERIOD PILLS ══════════════════════════════════════════════ */
.period-pills { display:flex; gap:6px; flex-wrap:wrap; }
.period-pill {
    padding:5px 14px; border-radius:20px; font-size:.77rem; font-weight:600;
    border:1.5px solid #e2e8f0; background:#fff; color:#64748b;
    text-decoration:none; transition:all .15s; cursor:pointer;
}
.period-pill:hover { border-color:#4f8cff; color:#4f8cff; text-decoration:none; }
.period-pill.active { background:#4f8cff; border-color:#4f8cff; color:#fff; }

/* ═══ KPI STRIP ══════════════════════════════════════════════════ */
.kpi-strip { display:grid; grid-template-columns:repeat(6, 1fr); gap:12px; margin-bottom:20px; }
@media(max-width:1100px) { .kpi-strip { grid-template-columns:repeat(3,1fr); } }
@media(max-width:680px)  { .kpi-strip { grid-template-columns:repeat(2,1fr); } }
.kpi-card {
    background:#fff; border-radius:12px; padding:14px 16px;
    box-shadow:0 1px 6px rgba(0,0,0,.06); border-left:3px solid transparent;
    transition:transform .2s;
}
.kpi-card:hover { transform:translateY(-1px); }
.kpi-val { font-size:1.6rem; font-weight:800; color:#1a2332; line-height:1; }
.kpi-name { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-top:4px; }
.kpi-sub  { font-size:.7rem; color:#b0bec5; margin-top:2px; }
.kpi-blue   { border-color:#3b82f6; } .kpi-blue   .kpi-val { color:#3b82f6; }
.kpi-amber  { border-color:#f59e0b; } .kpi-amber  .kpi-val { color:#f59e0b; }
.kpi-green  { border-color:#22c55e; } .kpi-green  .kpi-val { color:#22c55e; }
.kpi-red    { border-color:#ef4444; } .kpi-red    .kpi-val { color:#ef4444; }
.kpi-purple { border-color:#8b5cf6; } .kpi-purple .kpi-val { color:#8b5cf6; }
.kpi-teal   { border-color:#14b8a6; } .kpi-teal   .kpi-val { color:#14b8a6; }

/* ═══ CHARTS SECTION ════════════════════════════════════════════ */
.charts-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; }
@media(max-width:800px) { .charts-grid { grid-template-columns:1fr; } }
.chart-span2 { grid-column:span 2; }
@media(max-width:800px) { .chart-span2 { grid-column:span 1; } }

/* ═══ CARDS ══════════════════════════════════════════════════════ */
.dash-card {
    background:#fff; border-radius:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.07); overflow:hidden;
}
.dash-card-header {
    background:linear-gradient(90deg,#1a2332,#243447);
    padding:12px 18px; display:flex; align-items:center; gap:8px;
}
.dash-card-header h3 { color:#fff; font-size:.88rem; font-weight:600; margin:0; }
.dash-card-header i { color:#60a5fa; font-size:.85rem; }
.dash-card-body { padding:18px 20px; }

/* ═══ TICKETS TABLE ══════════════════════════════════════════════ */
.tickets-mini { width:100%; border-collapse:collapse; }
.tickets-mini th { font-size:.68rem; font-weight:700; color:#94a3b8; text-transform:uppercase; padding:6px 10px; text-align:left; background:#f8fafc; }
.tickets-mini td { font-size:.8rem; padding:9px 10px; border-bottom:1px solid #f1f5f9; color:#374151; }
.tickets-mini tr:last-child td { border-bottom:none; }
.tickets-mini tr:hover td { background:#fafbfc; }

/* Priority badges */
.pb { display:inline-block; padding:2px 8px; border-radius:20px; font-size:.65rem; font-weight:700; text-transform:uppercase; }
.pb-critical { background:#fef2f2; color:#dc2626; }
.pb-high     { background:#fff7ed; color:#ea580c; }
.pb-medium   { background:#fefce8; color:#ca8a04; }
.pb-low      { background:#f0fdf4; color:#16a34a; }

/* ═══ TOP REQUESTERS ════════════════════════════════════════════ */
.requester-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f1f5f9; }
.requester-row:last-child { border-bottom:none; }
.r-rank { width:22px; font-size:.72rem; font-weight:700; color:#94a3b8; text-align:center; }
.r-name { font-size:.83rem; font-weight:600; color:#1a2332; }
.r-email { font-size:.7rem; color:#94a3b8; }
.r-count { font-size:.9rem; font-weight:800; color:#3b82f6; margin-left:auto; }
.r-bar { flex:1; max-width:80px; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden; }
.r-bar-fill { height:100%; background:linear-gradient(90deg,#3b82f6,#6366f1); border-radius:3px; }
</style>

<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'dashboard'])

    <div class="admin-content">

        {{-- Header + Period Filter --}}
        <div class="dash-header">
            <div>
                <h1><i class="fas fa-chart-line" style="color:#4f8cff;margin-right:8px;"></i>Panel de Administración</h1>
                <p>{{ $periodLabel }} · {{ now()->format('d/m/Y H:i') }}</p>
            </div>
            <div class="period-pills">
                @foreach(['7d'=>'7 días','30d'=>'30 días','3m'=>'3 meses','6m'=>'6 meses','12m'=>'12 meses'] as $p => $lbl)
                <a href="{{ route('admin.dashboard', ['period' => $p]) }}"
                   class="period-pill {{ $period === $p ? 'active' : '' }}">{{ $lbl }}</a>
                @endforeach
            </div>
        </div>

        {{-- KPI Strip --}}
        <div class="kpi-strip">
            <div class="kpi-card kpi-blue">
                <div class="kpi-val">{{ $totalTickets }}</div>
                <div class="kpi-name">Total Tickets</div>
                <div class="kpi-sub">{{ $closedTickets }} cerrados</div>
            </div>
            <div class="kpi-card kpi-amber">
                <div class="kpi-val">{{ $openTickets }}</div>
                <div class="kpi-name">Abiertos</div>
                <div class="kpi-sub">{{ $inProgressTickets }} en proceso</div>
            </div>
            <div class="kpi-card kpi-green">
                <div class="kpi-val">{{ $resolvedTickets }}</div>
                <div class="kpi-name">Resueltos</div>
                <div class="kpi-sub">{{ $pendingTickets }} pend. usuario</div>
            </div>
            <div class="kpi-card kpi-purple">
                <div class="kpi-val">{{ $totalUsers }}</div>
                <div class="kpi-name">Usuarios</div>
                <div class="kpi-sub">{{ $activeUsers }} activos</div>
            </div>
            <div class="kpi-card kpi-teal">
                <div class="kpi-val">
                    @if($avgResolutionHours !== null)
                        {{ $avgResolutionHours >= 24 ? round($avgResolutionHours/24,1).'d' : round($avgResolutionHours,1).'h' }}
                    @else —
                    @endif
                </div>
                <div class="kpi-name">Prom. Resolución</div>
                <div class="kpi-sub">en el período</div>
            </div>
            <div class="kpi-card {{ $slaCompliance !== null && $slaCompliance >= 80 ? 'kpi-green' : 'kpi-red' }}">
                <div class="kpi-val">{{ $slaCompliance !== null ? $slaCompliance.'%' : '—' }}</div>
                <div class="kpi-name">Cumplimiento SLA</div>
                <div class="kpi-sub">tickets resueltos en plazo</div>
            </div>
        </div>

        {{-- Charts Grid (arriba) --}}
        <div class="charts-grid">

            {{-- Doughnut: prioridad --}}
            <div class="dash-card">
                <div class="dash-card-header">
                    <i class="fas fa-chart-pie"></i>
                    <h3>Tickets por Prioridad</h3>
                </div>
                <div class="dash-card-body" style="display:flex;align-items:center;justify-content:center;padding:20px 16px;min-height:220px;">
                    <canvas id="priorityChart" style="max-height:200px;max-width:200px;"></canvas>
                </div>
            </div>

            {{-- Line: tendencia --}}
            <div class="dash-card">
                <div class="dash-card-header">
                    <i class="fas fa-chart-line"></i>
                    <h3>Tendencia — {{ $periodLabel }}</h3>
                </div>
                <div class="dash-card-body" style="padding:20px 16px;min-height:220px;">
                    <canvas id="monthlyChart" style="max-height:200px;"></canvas>
                </div>
            </div>

            {{-- Bar: carga por técnico --}}
            <div class="dash-card chart-span2">
                <div class="dash-card-header">
                    <i class="fas fa-users"></i>
                    <h3>Carga de Trabajo por Técnico (tickets activos)</h3>
                </div>
                <div class="dash-card-body" style="padding:20px 16px;">
                    <canvas id="agentChart" style="max-height:180px;"></canvas>
                </div>
            </div>

        </div>

        {{-- Tickets Recientes --}}
        <div class="dash-card" style="margin-bottom:16px;">
            <div class="dash-card-header">
                <i class="fas fa-ticket-alt"></i>
                <h3>Tickets Recientes (por urgencia)</h3>
            </div>
            <div class="dash-card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="tickets-mini">
                        <thead>
                            <tr>
                                <th>N° Ticket</th>
                                <th>Título</th>
                                <th>Prioridad</th>
                                <th>Estado</th>
                                <th>Solicitante</th>
                                <th>Asignado a</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTickets as $t)
                            <tr>
                                <td><a href="{{ route('tickets.show', $t) }}" style="color:#3b82f6;font-weight:600;text-decoration:none;">{{ $t->ticket_number }}</a></td>
                                <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $t->title }}</td>
                                <td><span class="pb pb-{{ $t->priority }}">{{ $t->getPriorityLabel() }}</span></td>
                                <td style="font-size:.78rem;color:#64748b;">{{ $t->getStatusLabel() }}</td>
                                <td style="font-size:.78rem;">{{ $t->getCreatorName() }}</td>
                                <td style="font-size:.78rem;color:#64748b;">{{ $t->assignedTo?->name ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">Sin tickets recientes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Top Solicitantes (abajo) --}}
        <div class="dash-card">
            <div class="dash-card-header">
                <i class="fas fa-trophy"></i>
                <h3>Top Solicitantes (todos los tiempos)</h3>
            </div>
            <div class="dash-card-body">
                @if($topRequesters->isEmpty())
                    <p style="color:#94a3b8;text-align:center;font-size:.84rem;padding:16px 0;">Sin datos aún.</p>
                @else
                @php $maxR = $topRequesters->first()->ticket_count ?: 1; @endphp
                @foreach($topRequesters as $i => $req)
                <div class="requester-row">
                    <div class="r-rank">{{ $i+1 }}</div>
                    <div>
                        <div class="r-name">{{ $req->name }}</div>
                        <div class="r-email">{{ $req->email }}</div>
                    </div>
                    <div class="r-bar"><div class="r-bar-fill" style="width:{{ round(($req->ticket_count/$maxR)*100) }}%"></div></div>
                    <div class="r-count">{{ $req->ticket_count }}</div>
                </div>
                @endforeach
                @endif
            </div>
        </div>

    </div>{{-- /admin-content --}}
</div>{{-- /admin-layout --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Doughnut: prioridad ──
const priData   = @json($byPriority);
const priLabels = { low:'Baja', medium:'Media', high:'Alta', critical:'Crítica' };
const priColors = { low:'#22c55e', medium:'#3b82f6', high:'#f59e0b', critical:'#ef4444' };
new Chart(document.getElementById('priorityChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(priData).map(k => priLabels[k] || k),
        datasets: [{
            data: Object.values(priData),
            backgroundColor: Object.keys(priData).map(k => priColors[k] || '#94a3b8'),
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } } },
        cutout: '65%'
    }
});

// ── Line: tendencia ──
const monthly = @json($monthly);
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: monthly.map(m => m.month),
        datasets: [{
            label: 'Tickets', data: monthly.map(m => m.total),
            borderColor: '#4f8cff', backgroundColor: 'rgba(79,140,255,.12)',
            tension: 0.35, fill: true, pointBackgroundColor: '#4f8cff', pointRadius: 4
        }]
    },
    options: {
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});

// ── Bar horizontal: técnicos ──
const agents = @json($byAgent);
new Chart(document.getElementById('agentChart'), {
    type: 'bar',
    data: {
        labels: agents.map(a => a.name),
        datasets: [{ label: 'Tickets activos', data: agents.map(a => a.active_count), backgroundColor: '#4f8cff', borderRadius: 5 }]
    },
    options: {
        indexAxis: 'y',
        scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } }, y: { grid: { display: false } } },
        plugins: { legend: { display: false } }
    }
});
</script>
@endsection
