@extends('layouts.app')
@section('title', 'KPIs por Agente')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content { flex:1; padding:28px 32px; background:#f5f7fa; min-width:0; }

.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-title { font-size:1.35rem; font-weight:700; color:#1a2332; display:flex; align-items:center; gap:10px; }
.page-title i { color:#4f8cff; }

.agent-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:20px; }

.agent-card {
    background:#fff; border-radius:14px;
    box-shadow:0 2px 12px rgba(0,0,0,.07);
    overflow:hidden; transition:transform .2s, box-shadow .2s;
}
.agent-card:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.11); }

.agent-card-header {
    background:linear-gradient(135deg,#1a2332,#2d4a6e);
    padding:16px 20px; display:flex; align-items:center; gap:12px;
}
.agent-avatar {
    width:42px; height:42px; border-radius:50%;
    background:rgba(255,255,255,.2);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; font-weight:700; color:#fff;
    flex-shrink:0;
}
.agent-name { color:#fff; font-weight:600; font-size:.95rem; }
.agent-role { color:#93c5fd; font-size:.75rem; }

.agent-body { padding:18px 20px; }

.kpi-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px; }
.kpi-box {
    background:#f8fafc; border-radius:8px; padding:10px 12px;
    text-align:center; border:1px solid #e2e8f0;
}
.kpi-value { font-size:1.4rem; font-weight:800; color:#1a2332; line-height:1; }
.kpi-label { font-size:.7rem; color:#64748b; font-weight:600; text-transform:uppercase; margin-top:3px; }
.kpi-null { color:#cbd5e1; font-size:1rem; }

.complexity-bar { margin:12px 0 0; }
.complexity-label { font-size:.74rem; color:#64748b; font-weight:600; margin-bottom:5px; display:flex; justify-content:space-between; }
.bar-track { height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
.bar-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,#4f8cff,#7c3aed); transition:width .5s; }

.slowest-ticket {
    margin-top:12px; padding:10px 12px;
    background:#fffbeb; border:1px solid #fde68a;
    border-radius:8px; font-size:.78rem; color:#92400e;
}
.slowest-ticket strong { color:#78350f; }

.badge-role {
    display:inline-block; padding:2px 8px; border-radius:20px;
    font-size:.68rem; font-weight:700;
}
.badge-support { background:rgba(79,140,255,.15); color:#2563eb; }
.badge-admin   { background:rgba(124,58,237,.15); color:#7c3aed; }

.empty-agents {
    grid-column:1/-1; text-align:center; padding:60px 20px; color:#94a3b8;
}
.empty-agents i { font-size:3rem; margin-bottom:12px; display:block; }
</style>

<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'agents'])

    <div class="admin-content">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-user-clock"></i>
                KPIs por Agente de Soporte
            </div>
            <a href="{{ route('admin.reports.index') }}" style="color:#64748b;font-size:.84rem;text-decoration:none;">
                <i class="fas fa-arrow-left me-1"></i> Volver a reportes
            </a>
        </div>

        <div class="agent-grid">
            @forelse($agents as $a)
            @php
                $complexityMax = 4;
                $complexityPct = $a['complexity_score'] ? ($a['complexity_score'] / $complexityMax) * 100 : 0;
            @endphp
            <div class="agent-card">
                <div class="agent-card-header">
                    <div class="agent-avatar">
                        {{ strtoupper(substr($a['agent']->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="agent-name">{{ $a['agent']->name }}</div>
                        <div class="agent-role">
                            <span class="badge-role {{ $a['agent']->role === 'admin' ? 'badge-admin' : 'badge-support' }}">
                                {{ ucfirst($a['agent']->role) }}
                            </span>
                            &nbsp;{{ $a['agent']->email }}
                        </div>
                    </div>
                </div>
                <div class="agent-body">
                    <div class="kpi-grid">
                        <div class="kpi-box">
                            <div class="kpi-value">{{ $a['attended'] }}</div>
                            <div class="kpi-label">Tickets atendidos</div>
                        </div>
                        <div class="kpi-box">
                            <div class="kpi-value">{{ $a['resolved'] }}</div>
                            <div class="kpi-label">Resueltos</div>
                        </div>
                        <div class="kpi-box">
                            @if($a['avg_first_resp_h'] !== null)
                                <div class="kpi-value">{{ $a['avg_first_resp_h'] }}<small style="font-size:.7rem;color:#64748b;">h</small></div>
                            @else
                                <div class="kpi-value kpi-null">—</div>
                            @endif
                            <div class="kpi-label">Prom. 1ª respuesta</div>
                        </div>
                        <div class="kpi-box">
                            @if($a['avg_resolution_h'] !== null)
                                <div class="kpi-value">{{ $a['avg_resolution_h'] }}<small style="font-size:.7rem;color:#64748b;">h</small></div>
                            @else
                                <div class="kpi-value kpi-null">—</div>
                            @endif
                            <div class="kpi-label">Prom. resolución</div>
                        </div>
                    </div>

                    {{-- Barra de complejidad --}}
                    @if($a['complexity_score'] !== null)
                    <div class="complexity-bar">
                        <div class="complexity-label">
                            <span>Complejidad promedio</span>
                            <span style="color:#1a2332;font-weight:700;">
                                {{ $a['complexity_score'] }}/4
                                @if($a['complexity_score'] >= 3.5) 🔴
                                @elseif($a['complexity_score'] >= 2.5) 🟠
                                @elseif($a['complexity_score'] >= 1.5) 🟡
                                @else 🟢
                                @endif
                            </span>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width:{{ $complexityPct }}%"></div>
                        </div>
                        <div style="font-size:.68rem;color:#94a3b8;margin-top:3px;">
                            Basado en prioridades: Crítica=4, Alta=3, Media=2, Baja=1
                        </div>
                    </div>
                    @endif

                    {{-- Ticket más tardado --}}
                    @if($a['slowest_ticket'])
                    <div class="slowest-ticket">
                        <i class="fas fa-hourglass-end me-1"></i>
                        Ticket más lento: <strong>{{ $a['slowest_ticket']->ticket_number }}</strong>
                        — {{ $a['slowest_hours'] }}h
                        <div style="color:#a16207;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ Str::limit($a['slowest_ticket']->title, 50) }}
                        </div>
                    </div>
                    @endif

                    @if($a['attended'] === 0)
                    <div style="text-align:center;padding:20px 0;color:#94a3b8;font-size:.82rem;">
                        <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
                        Sin tickets asignados
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="empty-agents">
                <i class="fas fa-users-slash"></i>
                <p>No hay agentes de soporte activos.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
