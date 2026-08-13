@extends('layouts.app')
@section('title', 'Dashboard Gerencial — Conecta')

@section('content')
<style>
/* ═══ LAYOUT ═══════════════════════════════════════════════════ */
.admin-layout  { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content { flex:1; padding:28px 28px 56px; background:#f0f4f8; min-width:0; overflow-x:hidden; }

/* ═══ HEADER ════════════════════════════════════════════════════ */
.ger-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:22px; flex-wrap:wrap; gap:12px; }
.ger-header-left h1 { font-size:1.4rem; font-weight:800; color:#0f172a; margin:0 0 3px; display:flex; align-items:center; gap:10px; }
.ger-header-left p  { color:#64748b; font-size:.82rem; margin:0; }
.ger-badge-period { display:inline-flex; align-items:center; gap:6px; background:#dbeafe; color:#1e40af; font-size:.74rem; font-weight:700; padding:4px 12px; border-radius:20px; }

/* ═══ PERIOD PILLS ══════════════════════════════════════════════ */
.period-pills { display:flex; gap:6px; flex-wrap:wrap; }
.period-pill  { padding:5px 14px; border-radius:20px; font-size:.77rem; font-weight:600; border:1.5px solid #e2e8f0; background:#fff; color:#64748b; text-decoration:none; transition:all .15s; }
.period-pill:hover  { border-color:#6366f1; color:#6366f1; text-decoration:none; }
.period-pill.active { background:#6366f1; border-color:#6366f1; color:#fff; }

/* ═══ ALERTA CRÍTICOS ═══════════════════════════════════════════ */
.critical-alert {
    background:linear-gradient(135deg,#fef2f2,#fff5f5);
    border:1.5px solid #fca5a5; border-radius:12px;
    padding:14px 20px; margin-bottom:20px;
    display:flex; align-items:center; gap:12px;
}
.critical-alert .ca-icon { font-size:1.4rem; color:#dc2626; }
.critical-alert .ca-text { font-size:.88rem; font-weight:700; color:#991b1b; }
.critical-alert .ca-sub  { font-size:.76rem; color:#dc2626; }

/* ═══ KPI STRIP ══════════════════════════════════════════════════ */
.kpi-strip { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:1200px) { .kpi-strip { grid-template-columns:repeat(3,1fr); } }
@media(max-width:680px)  { .kpi-strip { grid-template-columns:repeat(2,1fr); } }
.kpi-card {
    background:#fff; border-radius:14px; padding:16px 18px;
    box-shadow:0 2px 12px rgba(0,0,0,.07); border-top:3px solid transparent;
    transition:transform .2s, box-shadow .2s;
}
.kpi-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.1); }
.kpi-icon { font-size:1.1rem; margin-bottom:8px; }
.kpi-val  { font-size:2rem; font-weight:900; line-height:1; }
.kpi-name { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; margin-top:5px; }
.kpi-delta { font-size:.72rem; margin-top:4px; display:flex; align-items:center; gap:3px; }
.kpi-indigo { border-color:#6366f1; } .kpi-indigo .kpi-val { color:#6366f1; } .kpi-indigo .kpi-icon { color:#6366f1; }
.kpi-amber  { border-color:#f59e0b; } .kpi-amber  .kpi-val { color:#f59e0b; } .kpi-amber  .kpi-icon { color:#f59e0b; }
.kpi-green  { border-color:#22c55e; } .kpi-green  .kpi-val { color:#22c55e; } .kpi-green  .kpi-icon { color:#22c55e; }
.kpi-red    { border-color:#ef4444; } .kpi-red    .kpi-val { color:#ef4444; } .kpi-red    .kpi-icon { color:#ef4444; }
.kpi-blue   { border-color:#3b82f6; } .kpi-blue   .kpi-val { color:#3b82f6; } .kpi-blue   .kpi-icon { color:#3b82f6; }
.kpi-sla-ok  { border-color:#22c55e; } .kpi-sla-ok  .kpi-val { color:#22c55e; } .kpi-sla-ok  .kpi-icon { color:#22c55e; }
.kpi-sla-wa  { border-color:#f59e0b; } .kpi-sla-wa  .kpi-val { color:#f59e0b; } .kpi-sla-wa  .kpi-icon { color:#f59e0b; }
.kpi-sla-bad { border-color:#ef4444; } .kpi-sla-bad .kpi-val { color:#ef4444; } .kpi-sla-bad .kpi-icon { color:#ef4444; }

/* ═══ GRID PRINCIPAL ════════════════════════════════════════════ */
.ger-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:900px) { .ger-grid { grid-template-columns:1fr; } }
.ger-span2 { grid-column:span 2; }
@media(max-width:900px) { .ger-span2 { grid-column:span 1; } }

/* ═══ CARDS ══════════════════════════════════════════════════════ */
.ger-card { background:#fff; border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,.07); overflow:hidden; }
.ger-card-head { padding:13px 18px; display:flex; align-items:center; gap:9px; border-bottom:1px solid #f1f5f9; }
.ger-card-head i  { font-size:.85rem; }
.ger-card-head h3 { font-size:.9rem; font-weight:700; color:#1e293b; margin:0; }
.ger-card-head .ger-badge { margin-left:auto; font-size:.68rem; font-weight:700; padding:2px 10px; border-radius:20px; }
.ger-card-body { padding:16px 18px; }
.chart-wrap { position:relative; height:220px; }

/* ═══ TABLA ══════════════════════════════════════════════════════ */
table.ger-tbl { width:100%; border-collapse:collapse; }
table.ger-tbl thead th { font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; padding:8px 12px; text-align:left; border-bottom:2px solid #f1f5f9; background:#f8fafc; }
table.ger-tbl tbody td { padding:9px 12px; font-size:.82rem; color:#374151; border-bottom:1px solid #f8fafc; }
table.ger-tbl tbody tr:last-child td { border-bottom:none; }
table.ger-tbl tbody tr:hover td { background:#fafbff; }

/* ═══ PROGRESS BAR ═══════════════════════════════════════════════ */
.prog-wrap { height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden; min-width:70px; }
.prog-bar  { height:100%; border-radius:3px; transition:width .5s; }
.prog-green  { background:linear-gradient(90deg,#22c55e,#4ade80); }
.prog-amber  { background:linear-gradient(90deg,#f59e0b,#fcd34d); }
.prog-red    { background:linear-gradient(90deg,#ef4444,#f87171); }
.prog-indigo { background:linear-gradient(90deg,#6366f1,#818cf8); }

/* ═══ LISTAS ══════════════════════════════════════════════════════ */
.requester-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f8fafc; }
.requester-row:last-child { border-bottom:none; }
.req-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#6366f1,#8b5cf6); display:flex; align-items:center; justify-content:center; color:#fff; font-size:.75rem; font-weight:700; flex-shrink:0; }
.req-name  { font-size:.83rem; font-weight:600; color:#1e293b; }
.req-count { margin-left:auto; font-size:.8rem; font-weight:800; color:#6366f1; }
.cat-row   { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f8fafc; }
.cat-row:last-child { border-bottom:none; }
.cat-name  { font-size:.83rem; font-weight:600; color:#1e293b; min-width:0; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cat-count { font-size:.8rem; font-weight:700; color:#64748b; white-space:nowrap; }
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'gerencial'])
<div class="admin-content">

    {{-- Header --}}
    <div class="ger-header">
        <div class="ger-header-left">
            <h1>
                <i class="fas fa-chart-pie" style="color:#6366f1;"></i>
                Dashboard Gerencial
            </h1>
            <p>Indicadores ejecutivos de desempeño del servicio de soporte —
                <span class="ger-badge-period"><i class="fas fa-calendar-alt"></i> {{ $periodLabel }}</span>
            </p>
        </div>
        <div class="period-pills">
            @foreach(['7d'=>'7 días','30d'=>'30 días','3m'=>'3 meses','6m'=>'6 meses','12m'=>'12 meses'] as $p=>$lbl)
            <a href="?period={{ $p }}" class="period-pill {{ $period===$p?'active':'' }}">{{ $lbl }}</a>
            @endforeach
        </div>
    </div>

    {{-- Alerta críticos --}}
    @if($criticalOpen > 0)
    <div class="critical-alert">
        <i class="fas fa-exclamation-triangle ca-icon"></i>
        <div>
            <div class="ca-text">&#9888; {{ $criticalOpen }} ticket{{ $criticalOpen>1?'s':'' }} de prioridad CRÍTICA activo{{ $criticalOpen>1?'s':'' }}</div>
            <div class="ca-sub">Requieren atención inmediata del equipo de soporte.</div>
        </div>
        <a href="{{ route('tickets.index', ['priority'=>'critical','status'=>'open']) }}"
           style="margin-left:auto;background:#dc2626;color:#fff;font-size:.75rem;font-weight:700;padding:6px 14px;border-radius:8px;text-decoration:none;">
            Ver tickets críticos →
        </a>
    </div>
    @endif

    {{-- KPI Strip --}}
    <div class="kpi-strip">
        <div class="kpi-card kpi-indigo">
            <div class="kpi-icon"><i class="fas fa-ticket-alt"></i></div>
            <div class="kpi-val">{{ $totalTickets }}</div>
            <div class="kpi-name">Tickets en el período</div>
        </div>
        <div class="kpi-card kpi-amber">
            <div class="kpi-icon"><i class="fas fa-folder-open"></i></div>
            <div class="kpi-val">{{ $openTickets }}</div>
            <div class="kpi-name">Activos (global)</div>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
            <div class="kpi-val">{{ $resolvedTickets }}</div>
            <div class="kpi-name">Resueltos / Cerrados</div>
        </div>
        <div class="kpi-card {{ $criticalOpen > 0 ? 'kpi-red' : 'kpi-green' }}">
            <div class="kpi-icon"><i class="fas fa-fire"></i></div>
            <div class="kpi-val">{{ $criticalOpen }}</div>
            <div class="kpi-name">Críticos activos</div>
        </div>
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon"><i class="fas fa-clock"></i></div>
            <div class="kpi-val">{{ $avgResolutionHours !== null ? round($avgResolutionHours,1) : '—' }}<small style="font-size:.9rem;font-weight:500;"> h</small></div>
            <div class="kpi-name">Prom. resolución</div>
        </div>
        @php
            $slaCls = $slaCompliance === null ? 'kpi-blue' : ($slaCompliance >= 80 ? 'kpi-sla-ok' : ($slaCompliance >= 60 ? 'kpi-sla-wa' : 'kpi-sla-bad'));
        @endphp
        <div class="kpi-card {{ $slaCls }}">
            <div class="kpi-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="kpi-val">{{ $slaCompliance !== null ? $slaCompliance : '—' }}<small style="font-size:.9rem;font-weight:500;"> %</small></div>
            <div class="kpi-name">Cumplimiento SLA</div>
            @if($slaCompliance !== null && $slaMissed > 0)
            <div class="kpi-delta" style="color:#94a3b8;"><i class="fas fa-times-circle" style="color:#ef4444;font-size:.65rem;"></i> {{ $slaMissed }} vencidos</div>
            @endif
        </div>
    </div>

    {{-- Fila 1: Tendencia + Prioridades --}}
    <div class="ger-grid">
        <div class="ger-card">
            <div class="ger-card-head">
                <i class="fas fa-chart-line" style="color:#6366f1;"></i>
                <h3>Tendencia mensual de tickets</h3>
                <span class="ger-badge" style="background:#ede9fe;color:#6d28d9;">Últimos 6 meses</span>
            </div>
            <div class="ger-card-body">
                <div class="chart-wrap"><canvas id="trendChart"></canvas></div>
            </div>
        </div>

        <div class="ger-card">
            <div class="ger-card-head">
                <i class="fas fa-layer-group" style="color:#f59e0b;"></i>
                <h3>Tickets por prioridad</h3>
                <span class="ger-badge" style="background:#fef3c7;color:#92400e;">{{ $periodLabel }}</span>
            </div>
            <div class="ger-card-body">
                <div class="chart-wrap"><canvas id="priorityChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Fila 2: Tabla de agentes (span 2) --}}
    <div style="margin-bottom:16px;">
        <div class="ger-card">
            <div class="ger-card-head">
                <i class="fas fa-users" style="color:#3b82f6;"></i>
                <h3>Rendimiento por técnico</h3>
                <span class="ger-badge" style="background:#dbeafe;color:#1e40af;">{{ $periodLabel }}</span>
            </div>
            <div class="ger-card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="ger-tbl">
                        <thead>
                            <tr>
                                <th>Técnico</th>
                                <th>Asignados</th>
                                <th>Resueltos</th>
                                <th>Activos</th>
                                <th>% Resolución</th>
                                <th>Rendimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agentPerformance as $agent)
                            @php
                                $rate   = $agent->resolution_rate;
                                $barCls = $rate >= 70 ? 'prog-green' : ($rate >= 40 ? 'prog-amber' : 'prog-red');
                                $rateColor = $rate >= 70 ? '#22c55e' : ($rate >= 40 ? '#f59e0b' : '#ef4444');
                            @endphp
                            <tr>
                                <td>
                                    <div style="font-weight:700;color:#1e293b;font-size:.84rem;">{{ $agent->name }}</div>
                                    <div style="font-size:.72rem;color:#94a3b8;">{{ $agent->role === 'admin' ? 'Administrador' : 'Soporte' }}</div>
                                </td>
                                <td><span style="font-weight:700;color:#374151;">{{ $agent->total_assigned }}</span></td>
                                <td><span style="font-weight:700;color:#22c55e;">{{ $agent->total_resolved }}</span></td>
                                <td>
                                    <span style="background:#fef3c7;color:#92400e;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:10px;">
                                        {{ $agent->total_active }}
                                    </span>
                                </td>
                                <td style="font-weight:800;color:{{ $rateColor }};">{{ $rate }}%</td>
                                <td style="min-width:100px;">
                                    <div class="prog-wrap">
                                        <div class="prog-bar {{ $barCls }}" style="width:{{ min($rate,100) }}%;"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" style="text-align:center;padding:28px;color:#94a3b8;">Sin datos de técnicos en el período.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Fila 3: Categorías + Top solicitantes --}}
    <div class="ger-grid">
        <div class="ger-card">
            <div class="ger-card-head">
                <i class="fas fa-tags" style="color:#8b5cf6;"></i>
                <h3>Categorías más frecuentes</h3>
            </div>
            <div class="ger-card-body" style="padding:10px 18px;">
                @php $maxCat = $categoryTrend->max('ticket_count') ?: 1; @endphp
                @forelse($categoryTrend as $cat)
                @if($cat->ticket_count > 0)
                <div class="cat-row">
                    <div class="cat-name" title="{{ $cat->name }}">{{ $cat->name }}</div>
                    <div style="flex:1;max-width:90px;margin:0 8px;">
                        <div class="prog-wrap">
                            <div class="prog-bar prog-indigo" style="width:{{ round(($cat->ticket_count/$maxCat)*100) }}%;"></div>
                        </div>
                    </div>
                    <div class="cat-count">{{ $cat->ticket_count }}</div>
                </div>
                @endif
                @empty
                <p style="color:#94a3b8;font-size:.83rem;text-align:center;padding:20px 0;">Sin datos en el período.</p>
                @endforelse
            </div>
        </div>

        <div class="ger-card">
            <div class="ger-card-head">
                <i class="fas fa-user" style="color:#06b6d4;"></i>
                <h3>Usuarios con más solicitudes</h3>
            </div>
            <div class="ger-card-body" style="padding:10px 18px;">
                @forelse($topRequesters as $req)
                @if($req->ticket_count > 0)
                <div class="requester-row">
                    <div class="req-avatar">{{ mb_strtoupper(mb_substr($req->name, 0, 1)) }}</div>
                    <div style="min-width:0;flex:1;">
                        <div class="req-name">{{ $req->name }}</div>
                        <div style="font-size:.71rem;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $req->email }}</div>
                    </div>
                    <div class="req-count">{{ $req->ticket_count }}</div>
                </div>
                @endif
                @empty
                <p style="color:#94a3b8;font-size:.83rem;text-align:center;padding:20px 0;">Sin datos en el período.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Nota informativa --}}
    <div style="margin-top:20px;padding:12px 18px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:8px;">
        <i class="fas fa-info-circle" style="color:#6366f1;"></i>
        Vista de solo lectura orientada a gerencia. Para acciones operativas, use el
        <a href="{{ route('admin.dashboard') }}" style="color:#6366f1;font-weight:600;text-decoration:none;">Dashboard Administrativo</a>.
    </div>

</div>{{-- /admin-content --}}
</div>{{-- /admin-layout --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Tendencia mensual
new Chart(document.getElementById('trendChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: @json($trendLabels),
        datasets: [{
            label: 'Tickets creados',
            data: @json($trendValues),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.10)',
            borderWidth: 2.5,
            pointBackgroundColor: '#6366f1',
            pointRadius: 4,
            tension: 0.35,
            fill: true,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 11 } } },
            y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: '#f1f5f9' } }
        }
    }
});

// Distribución por prioridad
new Chart(document.getElementById('priorityChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Crítica', 'Alta', 'Media', 'Baja'],
        datasets: [{
            data: [
                {{ $byPriority['critical'] ?? 0 }},
                {{ $byPriority['high']     ?? 0 }},
                {{ $byPriority['medium']   ?? 0 }},
                {{ $byPriority['low']      ?? 0 }}
            ],
            backgroundColor: ['#ef4444','#f97316','#f59e0b','#22c55e'],
            borderWidth: 2, borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } },
            tooltip: { callbacks: {
                label: function(ctx) {
                    const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                    const pct = total > 0 ? ((ctx.raw/total)*100).toFixed(1) : 0;
                    return ' ' + ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                }
            }}
        },
        cutout: '62%',
    }
});
</script>
@endsection
