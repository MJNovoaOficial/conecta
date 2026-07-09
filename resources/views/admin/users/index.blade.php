@extends('layouts.app')

@section('title', 'Gestionar Usuarios')

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
        padding: 13px 18px;
        font-size: 0.875rem;
        color: #2d3748;
        vertical-align: middle;
    }
    .user-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .user-avatar-mini {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        color: #fff;
        flex-shrink: 0;
    }
    .avatar-admin { background: linear-gradient(135deg, #e74c3c, #c0392b); }
    .avatar-support { background: linear-gradient(135deg, #27ae60, #2ecc71); }
    .avatar-user { background: linear-gradient(135deg, #3498db, #2980b9); }
    .user-name-text strong { display: block; font-size: 0.875rem; color: #1a2332; }
    .user-name-text span { font-size: 0.78rem; color: #718096; }
    .role-badge {
        font-size: 0.8rem;
        font-weight: 500;
        color: #4a5568;
    }
    .status-dot {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .dot { width: 8px; height: 8px; border-radius: 50%; }
    .dot-active { background: #22c55e; }
    .dot-inactive { background: #94a3b8; }
    .btn-edit-user {
        background: #f0f4f8;
        border: 1px solid #e2e8f0;
        color: #4a5568;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 0.78rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.15s;
    }
    .btn-edit-user:hover {
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
        <span>Usuarios</span>
    </div>

    <div class="admin-page-header">
        <div>
            <h1><i class="fas fa-users" style="color:#3498db; margin-right:8px;"></i>Gestionar Usuarios</h1>
            <div class="sub">Administra los usuarios del sistema de soporte</div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="{{ route('admin.dashboard') }}" class="btn-back-admin">&#8592; Volver</a>
            <a href="{{ route('admin.users.create') }}" class="btn-admin-action">
                <i class="fas fa-plus"></i> Nuevo Usuario
            </a>
        </div>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Departamento</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        <div>
                            <strong style="font-size:0.875rem; color:#1a2332;">{{ $user->name }}</strong>
                            @if($user->phone)
                                <span style="display:block; font-size:0.78rem; color:#718096;">{{ $user->phone }}</span>
                            @endif
                        </div>
                    </td>
                    <td style="color:#718096; font-size:0.82rem;">{{ $user->email }}</td>
                    <td style="color:#4a5568;">{{ $user->department?->name ?? '—' }}</td>
                    <td>
                        <span class="role-badge">
                            {{ $user->role === 'admin' ? 'Administrador' : ($user->role === 'support' ? 'Soporte' : 'Usuario') }}
                        </span>
                    </td>
                    <td>
                        <span class="status-dot">
                            <span class="dot {{ $user->is_active ? 'dot-active' : 'dot-inactive' }}"></span>
                            {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td>
                        <button class="btn-edit-user" data-bs-toggle="modal"
                                data-bs-target="#editUserModal{{ $user->id }}">
                            <i class="fas fa-pen"></i> Editar
                        </button>
                    </td>
                </tr>

                {{-- Modal edición de usuario --}}
                <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-user-edit me-2" style="color:#3498db;"></i>Editar: {{ $user->name }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" action="/admin/users/{{ $user->id }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Nombre</label>
                                        <input type="text" name="name" class="form-control"
                                               value="{{ $user->name }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Email</label>
                                        <input type="email" name="email" class="form-control"
                                               value="{{ $user->email }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Departamento</label>
                                        <select name="department_id" class="form-select" required>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}"
                                                    {{ $user->department_id == $dept->id ? 'selected' : '' }}>
                                                    {{ $dept->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">Rol</label>
                                        <select name="role" class="form-select" required>
                                            <option value="user"    {{ $user->role === 'user'    ? 'selected' : '' }}>Usuario</option>
                                            <option value="support" {{ $user->role === 'support' ? 'selected' : '' }}>Soporte</option>
                                            <option value="admin"   {{ $user->role === 'admin'   ? 'selected' : '' }}>Administrador</option>
                                        </select>
                                    </div>
                                    <div class="form-check" style="margin-top: 4px;">
                                        <input type="checkbox" class="form-check-input" name="is_active"
                                               id="activeUser{{ $user->id }}" value="1"
                                               {{ $user->is_active ? 'checked' : '' }}
                                               {{ $user->id === Auth::id() ? 'disabled' : '' }}>
                                        @if($user->id === Auth::id())
                                            {{-- Si es tu propio usuario, el checkbox siempre envía true --}}
                                            <input type="hidden" name="is_active" value="1">
                                            <label class="form-check-label text-muted" for="activeUser{{ $user->id }}">
                                                Usuario activo
                                            </label>
                                            <div style="margin-top: 6px; background: #fff8e1; border: 1px solid #ffc107; border-radius: 6px; padding: 7px 11px; font-size: 0.8rem; color: #7c5a00; display: flex; align-items: center; gap: 7px;">
                                                <i class="fas fa-lock" style="font-size:0.85rem;"></i>
                                                No puedes desactivar tu propia cuenta de administrador.
                                            </div>
                                        @else
                                            <label class="form-check-label" for="activeUser{{ $user->id }}">Usuario activo</label>
                                        @endif
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

        @if($users->hasPages())
        <div style="padding: 14px 18px; border-top: 1px solid #f0f2f5;">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

