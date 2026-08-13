@extends('layouts.app')
@section('title', 'Reportes de Gestión')

@section('content')
<style>
/* ═══ LAYOUT ═══════════════════════════════════════════════════ */
.admin-layout  { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content { flex:1; padding:24px 24px 48px; background:#f5f7fa; min-width:0; overflow-x:hidden; }

/* ═══ HEADER ════════════════════════════════════════════════════ */
.rp-header { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.rp-header h1 { font-size:1.3rem; font-weight:700; color:#1a2332; margin:0 0 2px; display:flex; align-items:center; gap:9px; }
.rp-header p  { color:#718096; font-size:.82rem; margin:0; }

/* ═══ KPI STRIP ═════════════════════════════════════════════════ */
.kpi-strip { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:10px; margin-bottom:20px; }
.kpi-box {
    background:#fff; border-radius:12px; padding:14px 16px;
    box-shadow:0 1px 6px rgba(0,0,0,.06); border-left:3px solid transparent;
    display:flex; flex-direction:column; gap:4px;
    transition:transform .15s;
}
.kpi-box:hover { transform:translateY(-2px); }
.kpi-box .knum { font-size:1.7rem; font-weight:800; line-height:1; }
.kpi-box .klbl { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; }
.kpi-box .kico { font-size:.75rem; margin-bottom:2px; }
.kpi-total  { border-color:#1a2332; } .kpi-total  .knum { color:#1a2332; }
.kpi-open   { border-color:#22c55e; } .kpi-open   .knum { color:#22c55e; }
.kpi-prog   { border-color:#f59e0b; } .kpi-prog   .knum { color:#f59e0b; }
.kpi-pend   { border-color:#f97316; } .kpi-pend   .knum { color:#f97316; }
.kpi-res    { border-color:#8b5cf6; } .kpi-res    .knum { color:#8b5cf6; }
.kpi-closed { border-color:#94a3b8; } .kpi-closed .knum { color:#94a3b8; }
.kpi-avgh   { border-color:#3b82f6; } .kpi-avgh   .knum { color:#3b82f6; }
.kpi-sla-ok { border-color:#22c55e; } .kpi-sla-ok .knum { color:#22c55e; }
.kpi-sla-wa { border-color:#f59e0b; } .kpi-sla-wa .knum { color:#f59e0b; }
.kpi-sla-ba { border-color:#ef4444; } .kpi-sla-ba .knum { color:#ef4444; }

/* ═══ FILTER BAR ════════════════════════════════════════════════ */
.filter-bar {
    background:#fff; border-radius:14px; padding:16px 20px;
    box-shadow:0 1px 6px rgba(0,0,0,.06); margin-bottom:16px;
}
.filter-bar form { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; }
.filter-group label { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; display:block; margin-bottom:4px; }
.filter-group select,
.filter-group input[type=date] {
    height:36px; border:1.5px solid #e2e8f0; border-radius:8px;
    font-size:.83rem; padding:0 10px; background:#fff;
    color:#374151; outline:none; transition:border-color .15s;
    min-width:0;
}
.filter-group select:focus,
.filter-group input:focus { border-color:#4f8cff; }

.btn-filter   { height:36px; padding:0 16px; border:none; border-radius:8px; font-size:.83rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.btn-f-apply  { background:#4f8cff; color:#fff; }   .btn-f-apply:hover  { background:#3b7de8; }
.btn-f-clear  { background:#f1f5f9; color:#64748b; } .btn-f-clear:hover  { background:#e2e8f0; text-decoration:none; color:#64748b; }
.btn-f-csv    { background:#d1fae5; color:#065f46; } .btn-f-csv:hover    { background:#a7f3d0; }
.btn-f-excel  { background:#dcfce7; color:#15803d; } .btn-f-excel:hover  { background:#bbf7d0; }
.btn-f-pdf    { background:#fee2e2; color:#991b1b; } .btn-f-pdf:hover    { background:#fecaca; }

/* ═══ MAIN 2-COL GRID ═══════════════════════════════════════════ */
.reports-grid { display:grid; grid-template-columns:1fr 280px; gap:16px; align-items:start; }
@media(max-width:900px) { .reports-grid { grid-template-columns:1fr; } }

/* ═══ TABLE ══════════════════════════════════════════════════════ */
.rp-table-card { background:#fff; border-radius:14px; box-shadow:0 1px 6px rgba(0,0,0,.06); overflow:hidden; }
.rp-table-head { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #f1f5f9; }
.rp-table-head h3 { font-size:.9rem; font-weight:700; color:#1a2332; margin:0; }
.rp-table-count { font-size:.75rem; color:#94a3b8; background:#f8fafc; border:1px solid #e2e8f0; border-radius:20px; padding:2px 10px; }

table.rp-tbl { width:100%; border-collapse:collapse; }
table.rp-tbl thead th {
    font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em;
    color:#94a3b8; background:#f8fafc; padding:10px 14px; text-align:left;
    border-bottom:2px solid #f1f5f9;
}
table.rp-tbl tbody td { padding:10px 14px; font-size:.82rem; color:#374151; border-bottom:1px solid #f8fafc; }
table.rp-tbl tbody tr:last-child td { border-bottom:none; }
table.rp-tbl tbody tr:hover td { background:#fafbff; }

.ticket-link { font-weight:700; color:#3b82f6; text-decoration:none; font-size:.8rem; }
.ticket-link:hover { color:#2563eb; text-decoration:underline; }
.ticket-title { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* Priority/Status badges (override global) */
.pb { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.pb-critical { background:#fef2f2; color:#dc2626; }
.pb-high     { background:#fff7ed; color:#ea580c; }
.pb-medium   { background:#fefce8; color:#ca8a04; }
.pb-low      { background:#f0fdf4; color:#16a34a; }
.pb-open     { background:#dcfce7; color:#16a34a; }
.pb-in_progress { background:#dbeafe; color:#2563eb; }
.pb-pending_user { background:#fff7ed; color:#ea580c; }
.pb-resolved { background:#ede9fe; color:#7c3aed; }
.pb-closed   { background:#f1f5f9; color:#64748b; }
.pb-forwarded{ background:#e0f2fe; color:#0284c7; }

.sla-ok    { color:#22c55e; font-weight:700; font-size:.75rem; }
.sla-warn  { color:#f59e0b; font-weight:700; font-size:.75rem; }
.sla-over  { color:#ef4444; font-weight:700; font-size:.75rem; }
.sla-none  { color:#cbd5e1; font-size:.75rem; }

/* ═══ SIDEBAR CARD ═══════════════════════════════════════════════ */
.agent-sidebar-card {
    background:#fff; border-radius:14px; box-shadow:0 1px 6px rgba(0,0,0,.06); overflow:hidden;
}
.agent-sidebar-head {
    background:linear-gradient(135deg,#1a2332,#2d4a6e);
    padding:14px 18px;
    display:flex; align-items:center; gap:8px;
}
.agent-sidebar-head h3 { color:#fff; font-size:.88rem; font-weight:600; margin:0; }
.agent-sidebar-head i  { color:#60a5fa; font-size:.85rem; }
.agent-sidebar-body { padding:16px 18px; }

.agent-row { margin-bottom:16px; }
.agent-row:last-child { margin-bottom:0; }
.agent-row-top { display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; }
.agent-name { font-size:.83rem; font-weight:600; color:#1a2332; }
.agent-count { font-size:.82rem; font-weight:800; color:#3b82f6; }
.agent-bar { height:6px; background:#f1f5f9; border-radius:3px; overflow:hidden; }
.agent-bar-fill { height:100%; background:linear-gradient(90deg,#4f8cff,#6366f1); border-radius:3px; transition:width .5s; }
.agent-meta { display:flex; gap:8px; margin-top:4px; font-size:.7rem; color:#94a3b8; }
.agent-meta span { display:flex; align-items:center; gap:3px; }
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'reports'])
<div class="admin-content">

    {{-- Header --}}
    <div class="rp-header">
        <div>
            <h1><i class="fas fa-chart-bar" style="color:#4f8cff;"></i> Reportes de Gestión</h1>
            <p>Análisis y seguimiento del desempeño del área de soporte</p>
        </div>
        <a href="{{ route('admin.reports.agents') }}" style="font-size:.8rem;color:#64748b;text-decoration:none;display:flex;align-items:center;gap:5px;">
            <i class="fas fa-user-clock"></i> Ver KPIs por Agente
        </a>
    </div>

    {{-- KPI Strip --}}
    <div class="kpi-strip">
        <div class="kpi-box kpi-total">
            <span class="kico"><i class="fas fa-ticket-alt" style="color:#1a2332;"></i></span>
            <span class="knum">{{ $summary['total'] }}</span>
            <span class="klbl">Total Tickets</span>
        </div>
        <div class="kpi-box kpi-open">
            <span class="kico"><i class="fas fa-folder-open" style="color:#22c55e;"></i></span>
            <span class="knum">{{ $summary['open'] }}</span>
            <span class="klbl">Abiertos</span>
        </div>
        <div class="kpi-box kpi-prog">
            <span class="kico"><i class="fas fa-spinner" style="color:#f59e0b;"></i></span>
            <span class="knum">{{ $summary['in_progress'] }}</span>
            <span class="klbl">En Proceso</span>
        </div>
        <div class="kpi-box kpi-pend">
            <span class="kico"><i class="fas fa-hourglass-half" style="color:#f97316;"></i></span>
            <span class="knum">{{ $summary['pending_user'] }}</span>
            <span class="klbl">Pendiente</span>
        </div>
        <div class="kpi-box kpi-res">
            <span class="kico"><i class="fas fa-check-circle" style="color:#8b5cf6;"></i></span>
            <span class="knum">{{ $summary['resolved'] }}</span>
            <span class="klbl">Resueltos</span>
        </div>
        <div class="kpi-box kpi-closed">
            <span class="kico"><i class="fas fa-archive" style="color:#94a3b8;"></i></span>
            <span class="knum">{{ $summary['closed'] }}</span>
            <span class="klbl">Cerrados</span>
        </div>
        @if($avgResolution !== null)
        <div class="kpi-box kpi-avgh">
            <span class="kico"><i class="fas fa-clock" style="color:#3b82f6;"></i></span>
            <span class="knum">{{ round($avgResolution,1) }}<small style="font-size:.9rem;font-weight:500;">h</small></span>
            <span class="klbl">Prom. Resolución</span>
        </div>
        @endif
        @if($slaCompliance !== null)
        @php $slaCls = $slaCompliance >= 80 ? 'kpi-sla-ok' : ($slaCompliance >= 60 ? 'kpi-sla-wa' : 'kpi-sla-ba'); @endphp
        <div class="kpi-box {{ $slaCls }}">
            <span class="kico"><i class="fas fa-shield-alt" style="opacity:.7;"></i></span>
            <span class="knum">{{ $slaCompliance }}<small style="font-size:.9rem;font-weight:500;">%</small></span>
            <span class="klbl">Cumpl. SLA</span>
        </div>
        @endif
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.reports.index') }}">
            <div class="filter-group">
                <label>Estado</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="open"         {{ request('status')=='open'         ?'selected':'' }}>Abierto</option>
                    <option value="in_progress"  {{ request('status')=='in_progress'  ?'selected':'' }}>En Proceso</option>
                    <option value="pending_user" {{ request('status')=='pending_user' ?'selected':'' }}>Pendiente</option>
                    <option value="resolved"     {{ request('status')=='resolved'     ?'selected':'' }}>Resuelto</option>
                    <option value="closed"       {{ request('status')=='closed'       ?'selected':'' }}>Cerrado</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Prioridad</label>
                <select name="priority" style="width:110px;">
                    <option value="">Todas</option>
                    <option value="critical" {{ request('priority')=='critical'?'selected':'' }}>🔴 Crítica</option>
                    <option value="high"     {{ request('priority')=='high'    ?'selected':'' }}>🟠 Alta</option>
                    <option value="medium"   {{ request('priority')=='medium'  ?'selected':'' }}>🟡 Media</option>
                    <option value="low"      {{ request('priority')=='low'     ?'selected':'' }}>🟢 Baja</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Categoría</label>
                <select name="categoria_id" style="width:150px;">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ request('categoria_id')==$cat->id?'selected':'' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Técnico</label>
                <select name="agent_id" style="width:140px;">
                    <option value="">Todos</option>
                    @foreach($agents as $agent)
                    <option value="{{ $agent->id }}" {{ request('agent_id')==$agent->id?'selected':'' }}>{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Solicitante</label>
                <select name="user_id" style="width:140px;">
                    <option value="">Todos</option>
                    @foreach($requesters as $req)
                    <option value="{{ $req->id }}" {{ request('user_id')==$req->id?'selected':'' }}>{{ $req->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Departamento</label>
                <select name="department_id" style="width:150px;">
                    <option value="">Todos</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id')==$dept->id?'selected':'' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label>Desde</label>
                <input type="date" name="date_from" style="width:130px;" value="{{ request('date_from') }}">
            </div>
            <div class="filter-group">
                <label>Hasta</label>
                <input type="date" name="date_to" style="width:130px;" value="{{ request('date_to') }}">
            </div>
            <div style="display:flex;gap:6px;margin-left:auto;flex-wrap:wrap;align-items:flex-end;">
                <button type="submit" class="btn-filter btn-f-apply"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="{{ route('admin.reports.index') }}" class="btn-filter btn-f-clear"><i class="fas fa-times"></i> Limpiar</a>
                @php
                    $dlDate   = now()->format('Y-m-d');
                    $dlParams = http_build_query(request()->except('page'));
                    $dlPrefix = 'reporte_tickets_' . $dlDate;
                    // Usar URL relativa para garantizar same-origin (requerido por atributo download en Chrome/Edge)
                    $dlBase   = '/admin/reports/download/' . $dlPrefix;
                @endphp
                <a href="{{ $dlBase }}.csv?{{ $dlParams }}"
                   download="{{ $dlPrefix }}.csv"
                   class="btn-filter btn-f-csv" title="Exportar CSV">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
                <a href="{{ $dlBase }}.xlsx?{{ $dlParams }}"
                   download="{{ $dlPrefix }}.xlsx"
                   class="btn-filter btn-f-excel" title="Exportar Excel">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ $dlBase }}.pdf?{{ $dlParams }}"
                   download="{{ $dlPrefix }}.pdf"
                   class="btn-filter btn-f-pdf" title="Exportar PDF">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>

        </form>
    </div>

    {{-- 2-col: tabla + sidebar --}}
    <div class="reports-grid">

        {{-- Tabla --}}
        <div class="rp-table-card">
            <div class="rp-table-head">
                <h3><i class="fas fa-list-ul" style="color:#4f8cff;margin-right:7px;"></i>Listado de Tickets</h3>
                <span class="rp-table-count">{{ $tickets->total() }} resultado{{ $tickets->total() !== 1 ? 's' : '' }}</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="rp-tbl">
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
                        @php
                            $sla = $ticket->getSlaResolutionStatus();
                            $pri = $ticket->priority;
                            $sts = $ticket->status;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('tickets.show', $ticket) }}" target="_blank" class="ticket-link">
                                    {{ $ticket->ticket_number }}
                                </a>
                            </td>
                            <td class="ticket-title" title="{{ $ticket->title }}">{{ $ticket->title }}</td>
                            <td style="font-size:.8rem;color:#64748b;">{{ $ticket->getCreatorName() }}</td>
                            <td><span class="pb pb-{{ $sts }}">{{ $ticket->getStatusLabel() }}</span></td>
                            <td><span class="pb pb-{{ $pri }}">{{ $ticket->getPriorityLabel() }}</span></td>
                            <td style="font-size:.78rem;color:#64748b;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $ticket->getClassificationLabel() }}
                            </td>
                            <td style="font-size:.8rem;font-weight:500;color:#374151;">{{ $ticket->assignedTo?->name ?? '—' }}</td>
                            <td style="font-size:.78rem;white-space:nowrap;color:#94a3b8;">{{ $ticket->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if($sla === 'exceeded')
                                    <span class="sla-over"><i class="fas fa-exclamation-triangle me-1"></i>Vencido</span>
                                @elseif($sla === 'warning')
                                    <span class="sla-warn"><i class="fas fa-clock me-1"></i>Por vencer</span>
                                @elseif($sla === 'ok')
                                    <span class="sla-ok"><i class="fas fa-check me-1"></i>OK</span>
                                @else
                                    <span class="sla-none">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align:center;padding:40px 20px;color:#94a3b8;">
                                <i class="fas fa-search" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                                No hay tickets que coincidan con los filtros seleccionados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tickets->hasPages())
            <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">{{ $tickets->links() }}</div>
            @endif
        </div>

        {{-- Panel lateral: técnicos --}}
        <div>
            <div class="agent-sidebar-card">
                <div class="agent-sidebar-head">
                    <i class="fas fa-users"></i>
                    <h3>Carga por Técnico</h3>
                </div>
                <div class="agent-sidebar-body">
                    @php $maxTickets = $byAgent->max('total_tickets') ?: 1; @endphp
                    @forelse($byAgent as $agent)
                    @if($agent->total_tickets > 0)
                    <div class="agent-row">
                        <div class="agent-row-top">
                            <span class="agent-name">{{ $agent->name }}</span>
                            <span class="agent-count">{{ $agent->total_tickets }}</span>
                        </div>
                        <div class="agent-bar">
                            <div class="agent-bar-fill" style="width:{{ round(($agent->total_tickets/$maxTickets)*100) }}%"></div>
                        </div>
                        <div class="agent-meta">
                            <span><i class="fas fa-spinner"></i> {{ $agent->in_progress_tickets }} activos</span>
                            <span style="color:#e2e8f0;">·</span>
                            <span><i class="fas fa-check-circle" style="color:#22c55e;"></i> {{ $agent->resolved_tickets }} resueltos</span>
                        </div>
                    </div>
                    @endif
                    @empty
                    <p style="color:#94a3b8;font-size:.83rem;text-align:center;padding:16px 0;">Sin datos de técnicos.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>{{-- /admin-content --}}
</div>{{-- /admin-layout --}}

<script>
/**
 * Descarga un archivo via fetch + Blob para garantizar nombre correcto
 * en Edge, Chrome y Firefox (no depende de Content-Disposition HTTP).
 */
async function downloadReport(url, filename, btnId) {
    const btn = document.getElementById(btnId);
    const originalHTML = btn.innerHTML;

    // Mostrar estado de carga
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';

    try {
        const resp = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin'
        });

        if (!resp.ok) {
            throw new Error('Error del servidor: ' + resp.status);
        }

        const blob = await resp.blob();

        // Verificar que no sea HTML (redirección a login)
        if (blob.type && blob.type.includes('text/html')) {
            throw new Error('Sesión expirada. Recarga la página e inicia sesión.');
        }

        // Disparar descarga con nombre correcto
        const blobUrl = URL.createObjectURL(blob);
        const a       = document.createElement('a');
        a.href        = blobUrl;
        a.download    = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(blobUrl), 5000);

    } catch (err) {
        console.error('[downloadReport]', err);
        alert('No se pudo descargar el archivo:\n' + err.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = originalHTML;
    }
}
</script>
@endsection
