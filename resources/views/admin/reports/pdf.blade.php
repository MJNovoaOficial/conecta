<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #2d3748; }

    .header {
        background: #1a2332;
        color: #fff;
        padding: 16px 20px;
        margin-bottom: 16px;
    }
    .header h1 { font-size: 16px; font-weight: 700; margin-bottom: 2px; }
    .header p { font-size: 9px; color: #90cdf4; }

    .kpi-row { display: flex; gap: 8px; margin-bottom: 14px; }
    .kpi { flex: 1; background: #f7f9fc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 10px; text-align: center; }
    .kpi-num { font-size: 18px; font-weight: 700; color: #1a2332; }
    .kpi-lbl { font-size: 8px; color: #718096; text-transform: uppercase; letter-spacing: .04em; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead tr { background: #1a2332; color: #fff; }
    thead th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    tbody tr { border-bottom: 1px solid #f0f2f5; }
    tbody tr:nth-child(even) { background: #f7f9fc; }
    tbody td { padding: 5px 8px; font-size: 9px; vertical-align: middle; }

    .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 8px; font-weight: 700; }
    .b-open     { background: #d1fae5; color: #065f46; }
    .b-progress { background: #fef3c7; color: #92400e; }
    .b-pending  { background: #ffedd5; color: #9a3412; }
    .b-resolved { background: #ede9fe; color: #5b21b6; }
    .b-closed   { background: #e2e8f0; color: #4a5568; }
    .b-low      { background: #f0fdf4; color: #166534; }
    .b-medium   { background: #fefce8; color: #854d0e; }
    .b-high     { background: #fff7ed; color: #9a3412; }
    .b-critical { background: #fef2f2; color: #991b1b; }

    .footer { margin-top: 16px; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 8px; color: #a0aec0; text-align: right; }
    .section-title { font-size: 11px; font-weight: 700; color: #1a2332; margin: 12px 0 6px; border-left: 3px solid #3498db; padding-left: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>📊 Reporte de Tickets — Conecta</h1>
    <p>Generado: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; Filtros aplicados: {{ request()->hasAny(['status','priority','agent_id','date_from','date_to']) ? 'Sí' : 'Ninguno' }}</p>
</div>

{{-- KPIs --}}
<div class="kpi-row">
    <div class="kpi"><div class="kpi-num">{{ $summary['total'] }}</div><div class="kpi-lbl">Total</div></div>
    <div class="kpi"><div class="kpi-num">{{ $summary['open'] }}</div><div class="kpi-lbl">Abiertos</div></div>
    <div class="kpi"><div class="kpi-num">{{ $summary['in_progress'] }}</div><div class="kpi-lbl">En Proceso</div></div>
    <div class="kpi"><div class="kpi-num">{{ $summary['resolved'] }}</div><div class="kpi-lbl">Resueltos</div></div>
    <div class="kpi"><div class="kpi-num">{{ $summary['closed'] }}</div><div class="kpi-lbl">Cerrados</div></div>
    @if($slaCompliance !== null)
    <div class="kpi"><div class="kpi-num">{{ $slaCompliance }}%</div><div class="kpi-lbl">SLA</div></div>
    @endif
</div>

<div class="section-title">Listado de Tickets ({{ $tickets->count() }} registros)</div>

<table>
    <thead>
        <tr>
            <th>N° Ticket</th>
            <th>Título</th>
            <th>Solicitante</th>
            <th>Estado</th>
            <th>Prioridad</th>
            <th>Clasificación</th>
            <th>Técnico</th>
            <th>Creado</th>
            <th>Cerrado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tickets as $t)
        @php
            $stCls = ['open'=>'b-open','in_progress'=>'b-progress','pending_user'=>'b-pending','forwarded'=>'b-resolved','resolved'=>'b-resolved','closed'=>'b-closed'];
            $prCls = ['low'=>'b-low','medium'=>'b-medium','high'=>'b-high','critical'=>'b-critical'];
        @endphp
        <tr>
            <td>{{ $t->ticket_number }}</td>
            <td>{{ \Illuminate\Support\Str::limit($t->title, 40) }}</td>
            <td>{{ $t->getCreatorName() }}</td>
            <td><span class="badge {{ $stCls[$t->status] ?? 'b-closed' }}">{{ $t->getStatusLabel() }}</span></td>
            <td><span class="badge {{ $prCls[$t->priority] ?? 'b-medium' }}">{{ $t->getPriorityLabel() }}</span></td>
            <td>{{ $t->subcategoria?->categoria?->name ?? $t->category ?? '—' }} {{ $t->subcategoria ? '› '.$t->subcategoria->name : '' }}</td>
            <td>{{ $t->assignedTo?->name ?? '—' }}</td>
            <td>{{ $t->created_at->format('d/m/Y') }}</td>
            <td>{{ $t->closed_at?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($byAgent->isNotEmpty())
<div class="section-title">Carga por Técnico</div>
<table>
    <thead>
        <tr><th>Técnico</th><th>Total</th><th>Abiertos</th><th>En Proceso</th><th>Resueltos/Cerrados</th></tr>
    </thead>
    <tbody>
        @foreach($byAgent as $a)
        <tr>
            <td>{{ $a->name }}</td>
            <td><strong>{{ $a->total_tickets }}</strong></td>
            <td>{{ $a->open_tickets }}</td>
            <td>{{ $a->in_progress_tickets }}</td>
            <td>{{ $a->resolved_tickets }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="footer">
    Conecta — Sistema de Mesa de Ayuda &nbsp;|&nbsp; Reporte generado automáticamente
</div>

</body>
</html>
