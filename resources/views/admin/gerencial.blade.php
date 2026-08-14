@extends('layouts.app')
@section('title', 'Dashboard Gerencial — Conecta')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content { flex:1; padding:28px 28px 48px; background:#f5f7fa; min-width:0; overflow-x:hidden; }

.gd-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.gd-header h1 { font-size:1.3rem; font-weight:700; color:#1a2332; margin:0 0 2px; }
.gd-header p { color:#718096; font-size:.82rem; margin:0; }

.period-pills { display:flex; gap:6px; flex-wrap:wrap; }
.period-pill { padding:5px 14px; border-radius:20px; font-size:.77rem; font-weight:600; border:1.5px solid #e2e8f0; background:#fff; color:#64748b; text-decoration:none; transition:all .15s; }
.period-pill:hover,.period-pill.active { background:#1a2332; border-color:#1a2332; color:#fff; }

/* KPI cards */
.kpi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:14px; margin-bottom:22px; }
.kpi-card { background:#fff; border-radius:12px; padding:18px 20px; border:1px solid #e8ecf0; position:relative; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.kpi-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; }
.kpi-card.blue::before { background:linear-gradient(90deg,#3b82f6,#60a5fa); }
.kpi-card.green::before { background:linear-gradient(90deg,#10b981,#34d399); }
.kpi-card.amber::before { background:linear-gradient(90deg,#f59e0b,#fbbf24); }
.kpi-card.red::before { background:linear-gradient(90deg,#ef4444,#f87171); }
.kpi-card.purple::before { background:linear-gradient(90deg,#8b5cf6,#a78bfa); }
.kpi-val { font-size:2rem; font-weight:800; color:#1a2332; line-height:1; margin:8px 0 4px; }
.kpi-label { font-size:.75rem; color:#718096; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
.kpi-icon { position:absolute; right:16px; top:50%; transform:translateY(-50%); font-size:2rem; opacity:.07; color:#1a2332; }
.kpi-sub { font-size:.72rem; color:#94a3b8; margin-top:3px; }

/* Sections */
.section-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:900px){.section-grid2{grid-template-columns:1fr;}}
.card-panel { background:#fff; border-radius:12px; border:1px solid #e8ecf0; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.card-header { padding:14px 18px; border-bottom:1px solid #f0f2f5; display:flex; align-items:center; gap:8px; }
.card-header h3 { font-size:.88rem; font-weight:700; color:#1a2332; margin:0; }
.card-body { padding:16px 18px; }

/* Trend chart placeholder */
.trend-bar-wrap { display:flex; align-items:flex-end; gap:6px; height:80px; }
.trend-bar { flex:1; border-radius:4px 4px 0 0; background:linear-gradient(180deg,#3b82f6,#2563eb); min-width:16px; transition:height .3s; }
.trend-label { font-size:.65rem; color:#a0aec0; text-align:center; margin-top:4px; }

/* Agent table */
.agent-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f7f9fc; }
.agent-row:last-child { border:none; }
.agent-av { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,#3b82f6,#2563eb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:700; flex-shrink:0; }
.agent-name { font-size:.83rem; font-weight:600; color:#1a2332; }
.agent-sub { font-size:.72rem; color:#718096; }
.progress-bar-wrap { flex:1; background:#f0f2f5; border-radius:4px; height:6px; overflow:hidden; }
.progress-bar-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,#3b82f6,#60a5fa); }

/* Prio pills */
.prio-list { display:flex; flex-direction:column; gap:8px; }
.prio-row { display:flex; align-items:center; gap:8px; }
.prio-label { font-size:.78rem; font-weight:600; width:90px; flex-shrink:0; }
.prio-bar-bg { flex:1; background:#f0f2f5; border-radius:4px; height:8px; overflow:hidden; }
.prio-bar-f { height:100%; border-radius:4px; }
.prio-count { font-size:.78rem; color:#64748b; font-weight:700; width:28px; text-align:right; }

/* Top requesters */
.req-row { display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px solid #f7f9fc; }
.req-row:last-child { border:none; }
.req-rank { font-size:.75rem; font-weight:800; color:#a0aec0; width:18px; }
.req-av { width:28px; height:28px; border-radius:50%; background:linear-gradient(135deg,#10b981,#059669); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; flex-shrink:0; }
.req-badge { margin-left:auto; font-size:.72rem; font-weight:700; padding:2px 8px; border-radius:10px; background:#dbeafe; color:#1d4ed8; }
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'gerencial'])
<div class="admin-content">

    {{-- Header --}}
    <div class="gd-header">
        <div>
            <h1><i class="fas fa-chart-pie" style="color:#8b5cf6;margin-right:8px;"></i>Dashboard Gerencial</h1>
            <p>Indicadores ejecutivos de alto nivel · {{ $periodLabel }} · {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="period-pills">
            @foreach(['7d'=>'7 días','30d'=>'30 días','3m'=>'3 meses','6m'=>'6 meses','12m'=>'12 meses'] as $p => $lbl)
            <a href="{{ route('admin.gerencial', ['period' => $p]) }}"
               class="period-pill {{ $period === $p ? 'active' : '' }}">{{ $lbl }}</a>
            @endforeach
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid">
        <div class="kpi-card blue">
            <div class="kpi-label">Total Tickets</div>
            <div class="kpi-val">{{ $totalTickets }}</div>
            <div class="kpi-sub">en el período</div>
            <i class="fas fa-ticket-alt kpi-icon"></i>
        </div>
        <div class="kpi-card amber">
            <div class="kpi-label">Tickets Abiertos</div>
            <div class="kpi-val">{{ $openTickets }}</div>
            <div class="kpi-sub">activos ahora</div>
            <i class="fas fa-folder-open kpi-icon"></i>
        </div>
        <div class="kpi-card green">
            <div class="kpi-label">Resueltos</div>
            <div class="kpi-val">{{ $resolvedTickets }}</div>
            <div class="kpi-sub">en el período</div>
            <i class="fas fa-check-circle kpi-icon"></i>
        </div>
        <div class="kpi-card red">
            <div class="kpi-label">Críticos Abiertos</div>
            <div class="kpi-val">{{ $criticalOpen }}</div>
            <div class="kpi-sub">requieren atención</div>
            <i class="fas fa-exclamation-circle kpi-icon"></i>
        </div>
        <div class="kpi-card purple">
            <div class="kpi-label">SLA Cumplido</div>
            <div class="kpi-val">{{ $slaCompliance !== null ? $slaCompliance . '%' : 'N/A' }}</div>
            <div class="kpi-sub">{{ $slaCompliance !== null ? "{$totalResolved} resueltos, {$slaMissed} fuera de SLA" : 'Sin datos suficientes' }}</div>
            <i class="fas fa-clock kpi-icon"></i>
        </div>
        <div class="kpi-card blue">
            <div class="kpi-label">Tiempo Promedio</div>
            <div class="kpi-val">{{ $avgResolutionHours !== null ? round($avgResolutionHours, 1) . 'h' : 'N/A' }}</div>
            <div class="kpi-sub">resolución por ticket</div>
            <i class="fas fa-stopwatch kpi-icon"></i>
        </div>
    </div>

    {{-- Tendencia + Prioridad --}}
    <div class="section-grid2">
        {{-- Tendencia mensual --}}
        <div class="card-panel">
            <div class="card-header">
                <i class="fas fa-chart-line" style="color:#3b82f6;"></i>
                <h3>Tendencia últimos 6 meses</h3>
            </div>
            <div class="card-body">
                @php $maxVal = max(array_merge($trendValues, [1])); @endphp
                <div class="trend-bar-wrap">
                    @foreach($trendValues as $idx => $val)
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;">
                        <div class="trend-bar" style="height:{{ max(6, ($val/$maxVal)*76) }}px;"
                             title="{{ $trendLabels[$idx] }}: {{ $val }} tickets"></div>
                        <div class="trend-label">{{ $trendLabels[$idx] }}</div>
                    </div>
                    @endforeach
                </div>
                <div style="margin-top:10px;font-size:.75rem;color:#94a3b8;text-align:center;">
                    Total período: {{ array_sum($trendValues) }} tickets
                </div>
            </div>
        </div>

        {{-- Por prioridad --}}
        <div class="card-panel">
            <div class="card-header">
                <i class="fas fa-layer-group" style="color:#f59e0b;"></i>
                <h3>Distribución por Prioridad</h3>
            </div>
            <div class="card-body">
                @php
                    $prioMap = [
                        'critical' => ['Crítica',   '#ef4444'],
                        'high'     => ['Alta',      '#f97316'],
                        'medium'   => ['Media',     '#f59e0b'],
                        'low'      => ['Baja',      '#3b82f6'],
                    ];
                    $prioTotal = $byPriority->sum() ?: 1;
                @endphp
                <div class="prio-list">
                    @foreach($prioMap as $key => [$label, $color])
                    @php $count = $byPriority[$key] ?? 0; $pct = round(($count/$prioTotal)*100); @endphp
                    <div class="prio-row">
                        <div class="prio-label" style="color:{{ $color }};">{{ $label }}</div>
                        <div class="prio-bar-bg">
                            <div class="prio-bar-f" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                        </div>
                        <div class="prio-count">{{ $count }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Rendimiento técnicos + Top usuarios --}}
    <div class="section-grid2">
        {{-- Rendimiento por técnico --}}
        <div class="card-panel">
            <div class="card-header">
                <i class="fas fa-users-cog" style="color:#10b981;"></i>
                <h3>Rendimiento por Técnico</h3>
            </div>
            <div class="card-body">
                @forelse($agentPerformance->take(6) as $agent)
                <div class="agent-row">
                    <div class="agent-av">{{ strtoupper(substr($agent->name, 0, 1)) }}</div>
                    <div style="flex:1;min-width:0;">
                        <div class="agent-name">{{ $agent->name }}</div>
                        <div class="agent-sub">{{ $agent->total_assigned }} asignados · {{ $agent->total_resolved }} resueltos</div>
                        <div class="progress-bar-wrap" style="margin-top:4px;">
                            <div class="progress-bar-fill" style="width:{{ $agent->resolution_rate }}%;"></div>
                        </div>
                    </div>
                    <div style="font-size:.78rem;font-weight:700;color:#10b981;flex-shrink:0;">
                        {{ $agent->resolution_rate }}%
                    </div>
                </div>
                @empty
                <p style="color:#a0aec0;font-size:.82rem;text-align:center;padding:16px 0;">Sin datos en el período.</p>
                @endforelse
            </div>
        </div>

        {{-- Top solicitantes --}}
        <div class="card-panel">
            <div class="card-header">
                <i class="fas fa-user-clock" style="color:#8b5cf6;"></i>
                <h3>Usuarios con más Solicitudes</h3>
            </div>
            <div class="card-body">
                @forelse($topRequesters as $idx => $req)
                <div class="req-row">
                    <div class="req-rank">{{ $idx + 1 }}</div>
                    <div class="req-av">{{ strtoupper(substr($req->name, 0, 1)) }}</div>
                    <div>
                        <div style="font-size:.83rem;font-weight:600;color:#1a2332;">{{ $req->name }}</div>
                        <div style="font-size:.72rem;color:#718096;">{{ $req->email }}</div>
                    </div>
                    <div class="req-badge">{{ $req->ticket_count }} tickets</div>
                </div>
                @empty
                <p style="color:#a0aec0;font-size:.82rem;text-align:center;padding:16px 0;">Sin datos en el período.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Categorías más frecuentes --}}
    <div class="card-panel" style="margin-bottom:16px;">
        <div class="card-header">
            <i class="fas fa-tags" style="color:#f59e0b;"></i>
            <h3>Categorías más Frecuentes</h3>
        </div>
        <div class="card-body" style="display:flex;gap:10px;flex-wrap:wrap;">
            @php $catMax = $categoryTrend->max('ticket_count') ?: 1; @endphp
            @forelse($categoryTrend as $cat)
            @php $pct = round(($cat->ticket_count / $catMax) * 100); @endphp
            <div style="background:#f7f9fc;border-radius:8px;padding:10px 14px;min-width:130px;flex:1;border:1px solid #e8ecf0;">
                <div style="font-size:.78rem;font-weight:700;color:#1a2332;margin-bottom:6px;">{{ $cat->name }}</div>
                <div style="background:#e8ecf0;border-radius:4px;height:6px;overflow:hidden;margin-bottom:4px;">
                    <div style="height:100%;width:{{ $pct }}%;background:linear-gradient(90deg,#8b5cf6,#a78bfa);border-radius:4px;"></div>
                </div>
                <div style="font-size:.72rem;color:#718096;">{{ $cat->ticket_count }} tickets</div>
            </div>
            @empty
            <p style="color:#a0aec0;font-size:.82rem;padding:8px 0;">Sin datos categorizados.</p>
            @endforelse
        </div>
    </div>

</div>
</div>
@endsection
