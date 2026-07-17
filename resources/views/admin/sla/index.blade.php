@extends('layouts.app')
@section('title', 'Configuración de SLA')

@section('content')
<div class="page-header">
    <div class="breadcrumb-nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>SLA</span>
    </div>
    <h1 class="page-title">Configuración de SLA</h1>
    <p class="page-subtitle">Define los tiempos de respuesta y resolución por nivel de prioridad</p>
</div>

<div style="max-width:700px;margin:0 auto;">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.sla.update') }}">
                @csrf @method('PUT')

                <div style="overflow-x:auto;">
                    <table class="data-table">
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
                                'critical' => ['label' => 'Crítica',  'color' => '#ef4444', 'icon' => '🔴'],
                                'high'     => ['label' => 'Alta',     'color' => '#f97316', 'icon' => '🟠'],
                                'medium'   => ['label' => 'Media',    'color' => '#3b82f6', 'icon' => '🔵'],
                                'low'      => ['label' => 'Baja',     'color' => '#6b7280', 'icon' => '⚪'],
                            ];
                            @endphp
                            @foreach($priorities as $key => $meta)
                            @php $config = $configs->firstWhere('priority', $key); @endphp
                            <tr>
                                <td>
                                    <input type="hidden" name="sla[{{ $loop->index }}][priority]" value="{{ $key }}">
                                    <span style="display:flex;align-items:center;gap:.5rem;font-weight:600;">
                                        <span>{{ $meta['icon'] }}</span>
                                        <span style="color:{{ $meta['color'] }};">{{ $meta['label'] }}</span>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.4rem;">
                                        <input type="number" name="sla[{{ $loop->index }}][response_hours]"
                                               class="form-control" style="width:80px;"
                                               value="{{ $config->response_hours ?? 8 }}" min="1" max="720" required>
                                        <span style="font-size:.8rem;color:var(--text-muted);">horas</span>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:.4rem;">
                                        <input type="number" name="sla[{{ $loop->index }}][resolution_hours]"
                                               class="form-control" style="width:80px;"
                                               value="{{ $config->resolution_hours ?? 48 }}" min="1" max="720" required>
                                        <span style="font-size:.8rem;color:var(--text-muted);">horas</span>
                                    </div>
                                </td>
                                <td style="font-size:.8rem;color:var(--text-muted);">
                                    Resp: {{ $config ? round($config->response_hours / 24, 1) : '?' }} días<br>
                                    Res: {{ $config ? round($config->resolution_hours / 24, 1) : '?' }} días
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div style="margin-top:1.5rem;display:flex;gap:.75rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle"></i> Guardar Configuración
                    </button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:1rem;border-left:3px solid var(--accent);">
        <div class="card-body" style="padding:.75rem 1rem;">
            <p style="font-size:.85rem;color:var(--text-muted);margin:0;">
                <i class="bi bi-info-circle" style="color:var(--accent);margin-right:.3rem;"></i>
                El SLA se calcula desde el momento en que el ticket es creado hasta que queda en estado Resuelto o Cerrado.
                El tiempo en que el ticket está en estado <strong>Pendiente Usuario</strong> no se descuenta del SLA de soporte.
            </p>
        </div>
    </div>
</div>
@endsection
