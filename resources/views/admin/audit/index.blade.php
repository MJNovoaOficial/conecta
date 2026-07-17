@extends('layouts.app')
@section('title', 'Auditoría del Sistema')

@section('content')
<div class="page-header">
    <div class="breadcrumb-nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>Auditoría</span>
    </div>
    <h1 class="page-title">Log de Auditoría</h1>
    <p class="page-subtitle">Historial de acciones críticas realizadas en el sistema</p>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Modelo</th>
                        <th>ID</th>
                        <th>IP</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="white-space:nowrap;font-size:.82rem;">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td style="font-size:.85rem;">{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td>
                            <span style="font-family:monospace;font-size:.8rem;background:var(--bg-secondary);padding:.1rem .4rem;border-radius:.25rem;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td style="font-size:.82rem;color:var(--text-muted);">{{ $log->model ?: '—' }}</td>
                        <td style="font-size:.82rem;color:var(--text-muted);">{{ $log->model_id ?: '—' }}</td>
                        <td style="font-size:.8rem;color:var(--text-muted);">{{ $log->ip_address ?: '—' }}</td>
                        <td style="font-size:.78rem;max-width:250px;">
                            @if($log->details)
                                <details>
                                    <summary style="cursor:pointer;color:var(--accent);">Ver detalles</summary>
                                    <pre style="font-size:.72rem;margin-top:.25rem;white-space:pre-wrap;word-break:break-all;">{{ json_encode($log->details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted);">
                            No hay registros de auditoría aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div style="padding:1rem;">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
