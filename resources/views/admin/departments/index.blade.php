@extends('layouts.app')

@section('title', 'Gestionar Departamentos')

@section('styles')
<style>
    .admin-wrapper {
        max-width: 1100px;
        margin: 0 auto;
        padding: 24px;
    }
    .admin-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    .admin-page-header h1 {
        font-size: 1.45rem;
        font-weight: 700;
        color: #1a2332;
        margin: 0;
    }
    .admin-page-header .sub {
        font-size: 0.85rem;
        color: #718096;
        margin-top: 2px;
    }
    .btn-admin-action {
        background: linear-gradient(135deg, #27ae60, #2ecc71);
        color: #fff;
        border: none;
        padding: 9px 20px;
        border-radius: 7px;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(39,174,96,0.25);
    }
    .btn-admin-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(39,174,96,0.35);
        color: #fff;
    }
    .admin-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e8ecf0;
        box-shadow: 0 1px 6px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }
    .admin-table thead th {
        background: #f7f9fc;
        padding: 12px 18px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #718096;
        border-bottom: 1px solid #e8ecf0;
        white-space: nowrap;
    }
    .admin-table tbody tr {
        border-bottom: 1px solid #f0f2f5;
        transition: background 0.15s;
    }
    .admin-table tbody tr:last-child { border-bottom: none; }
    .admin-table tbody tr:hover { background: #f7f9fc; }
    .admin-table tbody td {
        padding: 14px 18px;
        font-size: 0.875rem;
        color: #2d3748;
        vertical-align: middle;
    }
    .dept-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: linear-gradient(135deg, #3498db, #2980b9);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        flex-shrink: 0;
    }
    .dept-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .dept-name-text strong { display: block; font-size: 0.875rem; color: #1a2332; }
    .count-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 22px;
        padding: 0 8px;
        border-radius: 11px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .chip-users   { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .chip-tickets { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .btn-edit-dept {
        background: #f0f4f8;
        border: 1px solid #e2e8f0;
        color: #4a5568;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.15s;
    }
    .btn-edit-dept:hover {
        background: #3498db;
        border-color: #3498db;
        color: #fff;
    }
    .breadcrumb-admin {
        font-size: 0.8rem;
        color: #a0aec0;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .breadcrumb-admin a { color: #3498db; text-decoration: none; }
    .desc-text { color: #718096; font-size: 0.82rem; }
    .btn-back-admin {
        background: #1a2332;
        color: #fff;
        border: none;
        padding: 9px 18px;
        border-radius: 7px;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }
    .btn-back-admin:hover { background: #2d3748; color: #fff; }
</style>
@endsection

@section('content')
<div class="admin-wrapper">

    <div class="breadcrumb-admin">
        <a href="{{ route('tickets.index') }}">Inicio</a>
        <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
        <a href="{{ route('admin.dashboard') }}">Admin</a>
        <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
        <span>Departamentos</span>
    </div>

    <div class="admin-page-header">
        <div>
            <h1><i class="fas fa-sitemap" style="color:#3498db; margin-right:8px;"></i>Gestionar Departamentos</h1>
            <div class="sub">Organiza los equipos y áreas de soporte</div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="{{ route('admin.dashboard') }}" class="btn-back-admin">&#8592; Volver</a>
            <a href="/admin/departments/create" class="btn-admin-action">
                <i class="fas fa-plus"></i> Nuevo Departamento
            </a>
        </div>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Departamento</th>
                    <th>Descripción</th>
                    <th style="text-align:center;">Usuarios</th>
                    <th style="text-align:center;">Tickets</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($departments as $dept)
                <tr>
                    <td>
                        <strong style="font-size:0.875rem; color:#1a2332;">{{ $dept->name }}</strong>
                    </td>
                    <td class="desc-text">{{ $dept->description ?? '—' }}</td>
                    <td style="text-align:center;">
                        <span class="count-chip chip-users">{{ $dept->users_count }}</span>
                    </td>
                    <td style="text-align:center;">
                        <span class="count-chip chip-tickets">{{ $dept->tickets_count }}</span>
                    </td>
                    <td>
                        <button class="btn-edit-dept" data-bs-toggle="modal"
                                data-bs-target="#editModal{{ $dept->id }}">
                            <i class="fas fa-pen"></i> Editar
                        </button>
                    </td>
                </tr>

                {{-- Modal de edición inline --}}
                <div class="modal fade" id="editModal{{ $dept->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="fas fa-building me-2"></i>Editar: {{ $dept->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="/admin/departments/{{ $dept->id }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nombre</label>
                                        <input type="text" name="name" class="form-control" value="{{ $dept->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Descripción</label>
                                        <textarea name="description" class="form-control" rows="3">{{ $dept->description }}</textarea>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="is_active"
                                               id="active{{ $dept->id }}" value="1" {{ $dept->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active{{ $dept->id }}">Departamento activo</label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>

        @if($departments->hasPages())
        <div style="padding: 14px 18px; border-top: 1px solid #f0f2f5;">
            {{ $departments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
