@extends('layouts.app')
@section('title', 'Auditoría del Sistema')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 60px); }
.admin-content { flex:1; padding:24px 28px 48px; background:#f5f7fa; min-width:0; overflow-x:hidden; }

/* ═══ HEADER ═══════════════════════════════════════════════════ */
.audit-header {
    display:flex; align-items:flex-start; justify-content:space-between;
    margin-bottom:24px; flex-wrap:wrap; gap:12px;
}
.audit-header h1 { font-size:1.3rem; font-weight:700; color:#1a2332; margin:0 0 2px; display:flex; align-items:center; gap:9px; }
.audit-header p  { color:#718096; font-size:.82rem; margin:0; }
.audit-counter {
    background:linear-gradient(135deg,#1a2332,#2d4a6e);
    color:#fff; border-radius:10px; padding:10px 20px; text-align:center; min-width:90px;
}
.audit-counter .num  { font-size:1.5rem; font-weight:800; line-height:1; }
.audit-counter .lbl  { font-size:.65rem; text-transform:uppercase; letter-spacing:.08em; color:#93c5fd; margin-top:2px; }

/* ═══ TIMELINE FEED ════════════════════════════════════════════ */
.timeline { position:relative; padding-left:42px; }
.timeline::before {
    content:''; position:absolute; left:16px; top:0; bottom:0;
    width:2px; background:linear-gradient(to bottom,#4f8cff40,#e2e8f0);
    border-radius:1px;
}

.tl-entry {
    position:relative; margin-bottom:16px;
    background:#fff; border-radius:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
    border:1px solid #f1f5f9;
    transition:transform .15s, box-shadow .15s;
}
.tl-entry:hover { transform:translateY(-1px); box-shadow:0 4px 18px rgba(0,0,0,.1); }

/* Dot on timeline */
.tl-dot {
    position:absolute; left:-32px; top:18px;
    width:24px; height:24px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.65rem; color:#fff; flex-shrink:0;
    box-shadow:0 0 0 3px #f5f7fa;
}

/* Main row */
.tl-main {
    display:flex; align-items:center; gap:14px;
    padding:14px 18px; cursor:pointer;
    user-select:none;
}
.tl-main:hover .tl-toggle { color:#4f8cff; }

.tl-action-badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:20px;
    font-size:.72rem; font-weight:700; letter-spacing:.03em;
    white-space:nowrap; flex-shrink:0;
}
.tl-user {
    font-size:.83rem; font-weight:600; color:#1a2332;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    max-width:130px;
}
.tl-meta {
    margin-left:auto; display:flex; align-items:center; gap:14px;
    flex-shrink:0;
}
.tl-time { font-size:.72rem; color:#94a3b8; white-space:nowrap; }
.tl-ip {
    font-size:.7rem; color:#94a3b8; background:#f8fafc;
    border:1px solid #e2e8f0; border-radius:5px;
    padding:2px 7px; font-family:monospace;
    display:none; /* solo visible en hover */
}
.tl-entry:hover .tl-ip { display:inline; }
.tl-toggle { font-size:.72rem; color:#cbd5e1; transition:transform .2s; }
.tl-toggle.open { transform:rotate(180deg); color:#4f8cff; }

/* Model chip */
.tl-model {
    font-size:.7rem; background:#f1f5f9; color:#64748b;
    border-radius:5px; padding:2px 8px; font-family:monospace;
    flex-shrink:0;
}

/* Details panel */
.tl-details {
    display:none; border-top:1px solid #f1f5f9;
    padding:14px 18px; background:#fafcff; border-radius:0 0 14px 14px;
}
.tl-details.open { display:block; }
.tl-kv-grid {
    display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr));
    gap:8px;
}
.tl-kv {
    background:#fff; border:1px solid #e2e8f0; border-radius:8px;
    padding:8px 12px;
}
.tl-kv .k { font-size:.67rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; margin-bottom:2px; }
.tl-kv .v { font-size:.82rem; font-weight:600; color:#1a2332; word-break:break-all; }

/* ═══ ACTION COLOR MAP ══════════════════════════════════════════ */
/* ticket.created / ticket.updated / ticket.closed */
.act-ticket-created  { background:#dcfce7; color:#16a34a; }
.dot-ticket-created  { background:#16a34a; }
.act-ticket-updated  { background:#dbeafe; color:#2563eb; }
.dot-ticket-updated  { background:#2563eb; }
.act-ticket-closed   { background:#f1f5f9; color:#64748b; }
.dot-ticket-closed   { background:#64748b; }
.act-ticket-resolved { background:#ede9fe; color:#7c3aed; }
.dot-ticket-resolved { background:#7c3aed; }
/* priority_rule */
.act-priority        { background:#fef3c7; color:#d97706; }
.dot-priority        { background:#d97706; }
/* dept_rule */
.act-dept            { background:#e0f2fe; color:#0284c7; }
.dot-dept            { background:#0284c7; }
/* login / user */
.act-user            { background:#fce7f3; color:#be185d; }
.dot-user            { background:#be185d; }
/* default */
.act-default         { background:#f1f5f9; color:#64748b; }
.dot-default         { background:#94a3b8; }

/* ═══ EMPTY STATE ═══════════════════════════════════════════════ */
.audit-empty {
    text-align:center; padding:60px 20px; color:#94a3b8;
}
.audit-empty i { font-size:3rem; margin-bottom:12px; display:block; color:#e2e8f0; }

/* ═══ PAGINATION ════════════════════════════════════════════════ */
.audit-pag { margin-top:20px; display:flex; justify-content:center; }
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'audit'])
<div class="admin-content">

    <div class="audit-header">
        <div>
            <h1><i class="fas fa-shield-alt" style="color:#4f8cff;"></i> Auditoría del Sistema</h1>
            <p>Historial de acciones críticas realizadas en el sistema</p>
        </div>
        <div class="audit-counter">
            <div class="num">{{ $logs->total() }}</div>
            <div class="lbl">Registros</div>
        </div>
    </div>

    @php
    /**
     * Devuelve clases CSS según el tipo de acción.
     * Retorna [$badgeClass, $dotClass, $icon, $label]
     */
    function auditStyle(string $action): array {
        if (str_starts_with($action, 'ticket.created'))  return ['act-ticket-created',  'dot-ticket-created',  'fa-plus-circle',  'Ticket creado'];
        if (str_starts_with($action, 'ticket.updated'))  return ['act-ticket-updated',  'dot-ticket-updated',  'fa-pen',          'Ticket actualizado'];
        if (str_starts_with($action, 'ticket.closed'))   return ['act-ticket-closed',   'dot-ticket-closed',   'fa-lock',         'Ticket cerrado'];
        if (str_starts_with($action, 'ticket.resolved')) return ['act-ticket-resolved', 'dot-ticket-resolved', 'fa-check-circle', 'Ticket resuelto'];
        if (str_starts_with($action, 'priority_rule'))   return ['act-priority',         'dot-priority',        'fa-sliders-h',    'Regla de prioridad'];
        if (str_starts_with($action, 'dept_rule'))       return ['act-dept',             'dot-dept',            'fa-building',     'Regla de departamento'];
        if (str_contains($action, 'user') || str_contains($action, 'login')) return ['act-user', 'dot-user', 'fa-user', 'Usuario'];
        return ['act-default', 'dot-default', 'fa-circle', $action];
    }

    $keyLabels = [
        'ticket_number' => 'N° Ticket',
        'priority'      => 'Prioridad',
        'categoria_id'  => 'ID Categoría',
        'department_id' => 'ID Depto.',
        'status'        => 'Estado',
        'title'         => 'Título',
        'assigned_to'   => 'Asignado a',
        'old_status'    => 'Estado anterior',
        'new_status'    => 'Estado nuevo',
    ];
    $priorityMap = ['critical'=>'Crítica 🔴','high'=>'Alta 🟠','medium'=>'Media 🟡','low'=>'Baja 🟢'];
    @endphp

    @if($logs->isEmpty())
    <div class="audit-empty">
        <i class="fas fa-shield-alt"></i>
        <p>No hay registros de auditoría aún.</p>
    </div>
    @else
    <div class="timeline">
        @foreach($logs as $i => $log)
        @php
            [$badgeCls, $dotCls, $icon, $label] = auditStyle($log->action);
            $entryId = 'tl-' . $log->id;
        @endphp
        <div class="tl-entry">
            <div class="tl-dot {{ $dotCls }}">
                <i class="fas {{ $icon }}" style="font-size:.6rem;"></i>
            </div>

            <div class="tl-main" onclick="toggleEntry('{{ $entryId }}')">
                {{-- Badge de acción --}}
                <span class="tl-action-badge {{ $badgeCls }}">
                    <i class="fas {{ $icon }}"></i>
                    {{ $label }}
                </span>

                {{-- Usuario --}}
                <div class="tl-user" title="{{ $log->user?->name ?? 'Sistema' }}">
                    {{ $log->user?->name ?? 'Sistema' }}
                </div>

                {{-- Modelo --}}
                @if($log->model)
                <span class="tl-model">{{ $log->model }}
                    @if($log->model_id) #{{ $log->model_id }} @endif
                </span>
                @endif

                {{-- Meta: tiempo + IP + toggle --}}
                <div class="tl-meta">
                    <span class="tl-ip"><i class="fas fa-network-wired me-1"></i>{{ $log->ip_address ?? '—' }}</span>
                    <span class="tl-time">
                        <i class="fas fa-clock me-1"></i>
                        {{ $log->created_at->format('d/m/Y') }}
                        <span style="color:#64748b;font-weight:600;">{{ $log->created_at->format('H:i:s') }}</span>
                        <span style="color:#cbd5e1;margin-left:4px;">· {{ $log->created_at->diffForHumans() }}</span>
                    </span>
                    @if($log->details)
                    <i class="fas fa-chevron-down tl-toggle" id="{{ $entryId }}-icon"></i>
                    @endif
                </div>
            </div>

            {{-- Panel de detalles --}}
            @if($log->details)
            <div class="tl-details" id="{{ $entryId }}">
                <div class="tl-kv-grid">
                    @foreach($log->details as $k => $v)
                    @php
                        $displayKey = $keyLabels[$k] ?? str_replace('_', ' ', ucfirst($k));
                        $displayVal = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : ($v ?? '—');
                        if ($k === 'priority' && isset($priorityMap[$v])) $displayVal = $priorityMap[$v];
                        if (is_bool($v)) $displayVal = $v ? 'Sí' : 'No';
                    @endphp
                    <div class="tl-kv">
                        <div class="k">{{ $displayKey }}</div>
                        <div class="v">{{ $displayVal }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Paginación --}}
    @if($logs->hasPages())
    <div class="audit-pag">{{ $logs->links() }}</div>
    @endif
    @endif

</div>{{-- /admin-content --}}
</div>{{-- /admin-layout --}}

<script>
function toggleEntry(id) {
    const panel = document.getElementById(id);
    const icon  = document.getElementById(id + '-icon');
    if (!panel) return;
    const isOpen = panel.classList.toggle('open');
    if (icon) icon.classList.toggle('open', isOpen);
}
</script>
@endsection
