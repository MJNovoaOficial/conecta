@extends('layouts.app')

@section('title', 'Panel de Administración')

@section('content')
<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-cog"></i> Panel de Administración</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-users" style="font-size: 30px; color: #3498db;"></i>
                <h3 class="mt-2">{{ $totalUsers }}</h3>
                <p class="text-muted mb-0">Usuarios Totales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-ticket-alt" style="font-size: 30px; color: #e74c3c;"></i>
                <h3 class="mt-2">{{ $totalTickets }}</h3>
                <p class="text-muted mb-0">Tickets Totales</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-lock-open" style="font-size: 30px; color: #f39c12;"></i>
                <h3 class="mt-2">{{ $openTickets }}</h3>
                <p class="text-muted mb-0">Tickets Abiertos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-check-circle" style="font-size: 30px; color: #27ae60;"></i>
                <h3 class="mt-2">{{ $resolvedTickets }}</h3>
                <p class="text-muted mb-0">Tickets Resueltos</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Gestión</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="/admin/users" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-users"></i> Gestionar Usuarios
                        </a>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="/admin/departments" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sitemap"></i> Gestionar Departamentos
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Sistema</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Panel de administración central para Conecta</p>
                <small>Versión 1.0.0</small>
            </div>
        </div>
    </div>
</div>
@endsection
