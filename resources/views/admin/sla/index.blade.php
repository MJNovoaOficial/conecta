@extends('layouts.app')
@section('title', 'Configuración de SLA')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content {
    flex: 1;
    padding: 24px 28px 48px;
    min-width: 0;
    overflow-x: hidden;
    background:
        radial-gradient(circle at 15% 8%, rgba(37, 99, 235, 0.08), transparent 34%),
        radial-gradient(circle at 85% 14%, rgba(14, 165, 233, 0.08), transparent 28%),
        #f4f7fb;
}

.sla-shell {
    max-width: 880px;
    margin: 0 auto;
}

.sla-card {
    border: 1px solid #dbe5f4;
    border-radius: 16px;
    background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.sla-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #e3eaf5;
    background: linear-gradient(90deg, #eff6ff 0%, #f8fafc 100%);
}

.sla-card-title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
}

.sla-card-kicker {
    margin: 0;
    font-size: 0.78rem;
    color: #64748b;
}

.sla-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.6rem;
    border: 1px solid #c7ddff;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #1d4ed8;
    background: #eff6ff;
}

.sla-chip i {
    font-size: 0.7rem;
}

.sla-card-body {
    padding: 16px 20px 20px;
}

.sla-table-wrap {
    overflow-x: auto;
}

.sla-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 740px;
}

.sla-table thead th {
    padding: 0.7rem 0.75rem;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    background: #f8fafc;
    border-top: 1px solid #e5ecf6;
    border-bottom: 1px solid #e5ecf6;
}

.sla-table thead th:first-child {
    border-left: 1px solid #e5ecf6;
    border-radius: 10px 0 0 0;
}

.sla-table thead th:last-child {
    border-right: 1px solid #e5ecf6;
    border-radius: 0 10px 0 0;
}

.sla-table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #edf2fa;
    background: #fff;
}

.sla-table tbody tr td:first-child {
    border-left: 1px solid #edf2fa;
}

.sla-table tbody tr td:last-child {
    border-right: 1px solid #edf2fa;
}

.sla-table tbody tr:last-child td {
    border-bottom: 1px solid #dfe7f3;
}

.priority-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    font-weight: 700;
}

.priority-dot {
    width: 11px;
    height: 11px;
    border-radius: 999px;
    display: inline-block;
    box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.12);
}

.sla-input-wrap {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.sla-hours {
    width: 92px;
    text-align: center;
    border-radius: 10px;
    border: 1px solid #cfdbec;
    background: #f8fbff;
    font-weight: 700;
    color: #1e293b;
}

.sla-hours:focus {
    outline: none;
    border-color: #3b82f6;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.sla-unit {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
}

.sla-equiv {
    font-size: 0.79rem;
    line-height: 1.35;
    color: #475569;
}

.sla-actions {
    margin-top: 1.2rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.7rem;
}

.sla-actions .btn {
    border-radius: 10px;
    font-weight: 700;
    padding: 0.58rem 1rem;
}

.sla-note {
    margin-top: 0.95rem;
    border: 1px solid #d7e7ff;
    border-left: 4px solid #2f80ed;
    border-radius: 12px;
    background: linear-gradient(180deg, #fbfdff 0%, #f3f8ff 100%);
}

.sla-note .card-body {
    padding: 0.85rem 1rem;
}

.sla-note p {
    margin: 0;
    font-size: 0.84rem;
    color: #334155;
}

.sla-note i {
    color: #2563eb;
    margin-right: 0.35rem;
}

@media (max-width: 992px) {
    .admin-content {
        padding: 20px 16px 32px;
    }
}

@media (max-width: 768px) {
    .sla-card-head {
        flex-direction: column;
        align-items: flex-start;
    }

    .sla-card-body {
        padding: 14px;
    }

    .sla-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .sla-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'sla'])
<div class="admin-content">
<div class="page-header">
    <div class="breadcrumb-nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>SLA</span>
    </div>
    <h1 class="page-title">Configuración de SLA</h1>
    <p class="page-subtitle">Define los tiempos de respuesta y resolución por nivel de prioridad</p>
</div>

<div class="sla-shell">
    <div class="sla-card">
        <div class="sla-card-head">
            <div>
                <p class="sla-card-kicker">Matriz de tiempos por prioridad</p>
                <h2 class="sla-card-title">Objetivos de respuesta y resolución</h2>
            </div>
            <span class="sla-chip"><i class="bi bi-lightning-charge-fill"></i>SLA activo</span>
        </div>

        <div class="sla-card-body">
            <form method="POST" action="{{ route('admin.sla.update') }}">
                @csrf @method('PUT')

                <div class="sla-table-wrap">
                    <table class="sla-table">
                        <thead>
                            <tr>
                                <th>Prioridad</th>
                                <th>Primera Respuesta (horas)</th>
                                <th>Resolución (horas)</th>
                                <th>Equivalencias</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                            $priorities = [
                                'critical' => ['label' => 'Crítica',  'color' => '#ef4444'],
                                'high'     => ['label' => 'Alta',     'color' => '#f97316'],
                                'medium'   => ['label' => 'Media',    'color' => '#3b82f6'],
                                'low'      => ['label' => 'Baja',     'color' => '#8b5cf6'],
                            ];
                            @endphp
                            @foreach($priorities as $key => $meta)
                            @php $config = $configs->firstWhere('priority', $key); @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="sla[{{ $loop->index }}][priority]" value="{{ $key }}">
                                    <span class="priority-pill" style="color:{{ $meta['color'] }};">
                                        <span class="priority-dot" style="background:{{ $meta['color'] }};"></span>
                                        <span>{{ $meta['label'] }}</span>
                                    </span>
                                </td>
                                <td>
                                    <div class="sla-input-wrap">
                                        <input type="number" name="sla[{{ $loop->index }}][response_hours]"
                                               class="form-control sla-hours"
                                               value="{{ $config->response_hours ?? 8 }}" min="1" max="720" required>
                                        <span class="sla-unit">horas</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="sla-input-wrap">
                                        <input type="number" name="sla[{{ $loop->index }}][resolution_hours]"
                                               class="form-control sla-hours"
                                               value="{{ $config->resolution_hours ?? 48 }}" min="1" max="720" required>
                                        <span class="sla-unit">horas</span>
                                    </div>
                                </td>
                                <td class="sla-equiv">
                                    Resp: {{ $config ? round($config->response_hours / 24, 1) : '?' }} días<br>
                                    Res: {{ $config ? round($config->resolution_hours / 24, 1) : '?' }} días
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sla-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Guardar Configuración
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="sla-note">
        <div class="card-body">
            <p>
                <i class="bi bi-info-circle-fill"></i>
                El SLA se calcula desde el momento en que el ticket es creado hasta que queda en estado Resuelto o Cerrado.
                El tiempo en que el ticket está en estado <strong>Pendiente Usuario</strong> no se descuenta del SLA de soporte.
            </p>
        </div>
    </div>
</div>

</div>{{-- /admin-content --}}
</div>{{-- /admin-layout --}}
@endsection

