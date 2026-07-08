@extends('layouts.app')

@section('title', 'Bienvenido a Conecta')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card text-center">
            <div class="card-body" style="padding: 60px 30px;">
                <i class="fas fa-ticket-alt" style="font-size: 60px; color: var(--secondary-color);"></i>
                <h1 class="card-title mt-4">Bienvenido a Conecta</h1>
                <p class="card-text text-muted">Sistema de Mesa de Ayuda / Ticketera de Soporte</p>
                <p class="card-text">Gesiona tus tickets de soporte de manera eficiente</p>
                
                @auth
                    <div class="mt-4">
                        <a href="/tickets" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-list"></i> Ver Mis Tickets
                        </a>
                        <a href="/tickets/create" class="btn btn-success btn-lg">
                            <i class="fas fa-plus"></i> Crear Nuevo Ticket
                        </a>
                    </div>
                @else
                    <div class="mt-4">
                        <a href="/login" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </a>
                        <a href="/register" class="btn btn-secondary btn-lg">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </a>
                    </div>
                @endauth
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-users" style="font-size: 40px; color: var(--secondary-color);"></i>
                        <h5 class="card-title mt-3">Gestión de Usuarios</h5>
                        <p class="card-text small">Administra tu perfil y equipo</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-comments" style="font-size: 40px; color: var(--secondary-color);"></i>
                        <h5 class="card-title mt-3">Soporte en Tiempo Real</h5>
                        <p class="card-text small">Comentarios y actualizaciones instantáneas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-chart-bar" style="font-size: 40px; color: var(--secondary-color);"></i>
                        <h5 class="card-title mt-3">Reportes</h5>
                        <p class="card-text small">Análisis completo de tickets</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
