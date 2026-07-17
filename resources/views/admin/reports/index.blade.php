@extends('layouts.app')
@section('title', 'Reportes')

@section('content')
<div class="page-header">
    <div class="breadcrumb-nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>Reportes</span>
    </div>
    <h1 class="page-title">Reportes de Gestión</h1>
    <p class="page-subtitle">Análisis y seguimiento del desempeño del área de soporte</p>
</div>

{{-- KPIs superiores --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="card kpi-card">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value">{{ $summary['total'] }}</div>
            <div class="kpi-label">Total Tickets</div>
        </div>
    </div>
    <div class="card kpi-card" style="border-top:3px solid #22c55e;">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value" style="color:#22c55e;">{{ $summary['open'] }}</div>
            <div class="kpi-label">Abiertos</div>
        </div>
    </div>
    <div class="card kpi-card" style="border-top:3px solid #f59e0b;">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value" style="color:#f59e0b;">{{ $summary['in_progress'] }}</div>
            <div class="kpi-label">En Proceso</div>
        </div>
    </div>
    <div class="card kpi-card" style="border-top:3px solid #f97316;">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value" style="color:#f97316;">{{ $summary['pending_user'] }}</div>
            <div class="kpi-label">Pendiente</div>
        </div>
    </div>
    <div class="card kpi-card" style="border-top:3px solid #8b5cf6;">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value" style="color:#8b5cf6;">{{ $summary['resolved'] }}</div>
            <div class="kpi-label">Resueltos</div>
        </div>
    </div>
    <div class="card kpi-card" style="border-top:3px solid #6b7280;">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value" style="color:#6b7280;">{{ $summary['closed'] }}</div>
            <div class="kpi-label">Cerrados</div>
        </div>
    </div>
    @if($avgResolution !== null)
    <div class="card kpi-card" style="border-top:3px solid #3b82f6;">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value" style="color:#3b82f6;">{{ round($avgResolution, 1) }}h</div>
            <div class="kpi-label">Prom. Resolución</div>
        </div>
    </div>
    @endif
    @if($slaCompliance !== null)
    <div class="card kpi-card" style="border-top:3px solid {{ $slaCompliance >= 80 ? '#22c55e' : ($slaCompliance >= 60 ? '#f59e0b' : '#ef4444') }};">
        <div class="card-body" style="text-align:center;padding:1rem;">
            <div class="kpi-value" style="color:{{ $slaCompliance >= 80 ? '#22c55e' : ($slaCompliance >= 60 ? '#f59e0b' : '#ef4444') }};">{{ $slaCompliance }}%</div>
            <div class="kpi-label">Cumpl. SLA</div>
        </div>
    </div>
    @endif
</div>

<div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">
    <div>
        {{-- Filtros --}}
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-body" style="padding:1rem;">
                <form method="GET" action="{{ route('admin.reports') }}" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Estado</label>
                        <select name="status" class="form-control" style="width:130px;height:36px;font-size:.85rem;">
                            <option value="">Todos</option>
                            <option value="open" {{ request('status')=='open'?'selected':'' }}>Abierto</option>
                            <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>En Proceso</option>
                            <option value="pending_user" {{ request('status')=='pending_user'?'selected':'' }}>Pendiente</option>
                            <option value="resolved" {{ request('status')=='resolved'?'selected':'' }}>Resuelto</option>
                            <option value="closed" {{ request('status')=='closed'?'selected':'' }}>Cerrado</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Prioridad</label>
                        <select name="priority" class="form-control" style="width:110px;height:36px;font-size:.85rem;">
                            <option value="">Todas</option>
                            <option value="critical" {{ request('priority')=='critical'?'selected':'' }}>Crítica</option>
                            <option value="high" {{ request('priority')=='high'?'selected':'' }}>Alta</option>
                            <option value="medium" {{ request('priority')=='medium'?'selected':'' }}>Media</option>
                            <option value="low" {{ request('priority')=='low'?'selected':'' }}>Baja</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Categoría</label>
                        <select name="categoria_id" class="form-control" style="width:150px;height:36px;font-size:.85rem;">
                            <option value="">Todas</option>
                            @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}" {{ request('categoria_id')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Técnico</label>
                        <select name="agent_id" class="form-control" style="width:150px;height:36px;font-size:.85rem;">
                            <option value="">Todos</option>
                            @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" {{ request('agent_id')==$agent->id?'selected':'' }}>{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Desde</label>
                        <input type="date" name="date_from" class="form-control" style="width:130px;height:36px;font-size:.85rem;" value="{{ request('date_from') }}">
                    </div>
                    <div>
                        <label class="form-label" style="font-size:.8rem;">Hasta</label>
                        <input type="date" name="date_to" class="form-control" style="width:130px;height:36px;font-size:.85rem;" value="{{ request('date_to') }}">
                    </div>
                    <div style="display:flex;gap:.4rem;margin-left:auto;">
                        <button type="submit" class="btn btn-primary" style="height:36px;font-size:.85rem;">
                            <i class="bi bi-funnel"></i> Filtrar
                        </button>
                        <a href="{{ route('admin.reports') }}" class="btn btn-outline" style="height:36px;font-size:.85rem;">Limpiar</a>
                        <a href="{{ route('admin.reports.export') }}?{{ http_build_query(request()->all()) }}" class="btn btn-outline" style="height:36px;font-size:.85rem;color:var(--accent);">
                            <i class="bi bi-download"></i> CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla de tickets --}}
        <div class="card">
            <div class="card-body" style="padding:0;">
                <div style="overflow-x:auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>N° Ticket</th>
                                <th>Título</th>
                                <th>Solicitante</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Categoría</th>
                                <th>Técnico</th>
                                <th>Creado</th>
                                <th>SLA</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tickets as $ticket)
                            <tr>
                                <td>
                                    <a href="{{ route('tickets.show', $ticket) }}" target="_blank"
                                       style="font-weight:600;color:var(--accent);text-decoration:none;">
                                        {{ $ticket->ticket_number }}
                                    </a>
                                </td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $ticket->title }}</td>
                                <td style="font-size:.85rem;">{{ $ticket->getCreatorName() }}</td>
                                <td><span class="{{ $ticket->getStatusBadgeClass() }}">{{ $ticket->getStatusLabel() }}</span></td>
                                <td><span class="{{ $ticket->getPriorityBadgeClass() }}">{{ $ticket->getPriorityLabel() }}</span></td>
                                <td style="font-size:.8rem;color:var(--text-muted);">{{ $ticket->getClassificationLabel() }}</td>
                                <td style="font-size:.85rem;">{{ $ticket->assignedTo?->name ?? '—' }}</td>
                                <td style="font-size:.8rem;white-space:nowrap;">{{ $ticket->created_at->format('d/m/Y') }}</td>
                                <td>
                                    @php $sla = $ticket->getSlaResolutionStatus(); @endphp
                                    @if($sla === 'exceeded')
                                        <span style="color:#ef4444;font-size:.75rem;font-weight:600;">⚠ Vencido</span>
                                    @elseif($sla === 'warning')
                                        <span style="color:#f59e0b;font-size:.75rem;font-weight:600;">⏰ Por vencer</span>
                                    @elseif($sla === 'ok')
                                        <span style="color:#22c55e;font-size:.75rem;">✓ OK</span>
                                    @else
                                        <span style="color:var(--text-muted);font-size:.75rem;">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted);">No hay tickets que coincidan con los filtros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($tickets->hasPages())
                <div style="padding:1rem;">{{ $tickets->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Panel lateral: Tickets por técnico --}}
    <div>
        <div class="card">
            <div class="card-body">
                <h3 style="font-size:.9rem;font-weight:600;margin-bottom:1rem;color:var(--text-primary);">
                    <i class="bi bi-people" style="color:var(--accent);margin-right:.3rem;"></i>
                    Tickets por Técnico
                </h3>
                @foreach($byAgent as $agent)
                @if($agent->total_tickets > 0)
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:.25rem;">
                        <span style="font-size:.85rem;font-weight:500;">{{ $agent->name }}</span>
                        <span style="font-size:.8rem;color:var(--text-muted);">{{ $agent->total_tickets }}</span>
                    </div>
                    <div style="background:var(--bg-secondary);border-radius:99px;height:6px;overflow:hidden;">
                        @php $max = $byAgent->max('total_tickets'); $pct = $max > 0 ? ($agent->total_tickets/$max)*100 : 0; @endphp
                        <div style="height:100%;width:{{ $pct }}%;background:var(--accent);border-radius:99px;"></div>
                    </div>
                    <div style="display:flex;gap:.5rem;margin-top:.25rem;font-size:.75rem;color:var(--text-muted);">
                        <span>{{ $agent->in_progress_tickets }} activos</span>
                        <span>·</span>
                        <span>{{ $agent->resolved_tickets }} resueltos</span>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.kpi-card { border-top: 3px solid var(--border-color); }
.kpi-value { font-size: 1.8rem; font-weight: 700; color: var(--text-primary); line-height: 1; }
.kpi-label { font-size: .75rem; color: var(--text-muted); margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }
</style>
@endpush
