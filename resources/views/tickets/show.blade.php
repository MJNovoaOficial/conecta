@extends('layouts.app')

@section('title', 'Ticket ' . $ticket->ticket_number)

@section('styles')
<style>
    .comment-box {
        border-left: 4px solid #3498db;
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .comment-header {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .comment-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #3498db;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        margin-right: 10px;
    }
    
    .comment-meta {
        font-size: 0.9rem;
        color: #7f8c8d;
    }
    
    .ticket-header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-9">
        <!-- Encabezado del Ticket -->
        <div class="ticket-header">
            <div class="row">
                <div class="col-md-8">
                    <h2>{{ $ticket->ticket_number }}</h2>
                    <h4 class="mb-3">{{ $ticket->title }}</h4>
                    <small>Creado por: <strong>{{ $ticket->user->name }}</strong> hace {{ $ticket->created_at->diffForHumans() }}</small>
                </div>
                <div class="col-md-4 text-end">
                    <span class="{{ $ticket->getStatusBadgeClass() }}" style="font-size: 1.2rem;">
                        {{ $ticket->getStatusLabel() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Descripción Original -->
        <div class="card mb-4">
            <div class="card-body">
                <h5>Descripción del Problema</h5>
                <p>{{ $ticket->description }}</p>
                
                @if($ticket->attachments->where('comment_id', null)->count() > 0)
                    <hr>
                    <h6>Adjuntos:</h6>
                    <div class="row">
                        @foreach($ticket->attachments->where('comment_id', null) as $attachment)
                            <div class="col-md-3 mb-2">
                                <a href="{{ asset('storage/' . $attachment->file_path) }}" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-download"></i> {{ $attachment->file_name }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Detalles del Ticket -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6>Información</h6>
                        <p class="mb-1"><strong>Categoría:</strong> {{ ucfirst($ticket->category) }}</p>
                        <p class="mb-1"><strong>Dispositivo:</strong> {{ ucfirst($ticket->device_type) }}</p>
                        <p class="mb-0"><strong>Departamento:</strong> {{ $ticket->department->name }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h6>Prioridad y Asignación</h6>
                        <p class="mb-1">
                            <strong>Prioridad:</strong>
                            @if($ticket->priority === 'critical')
                                <span class="badge bg-danger">{{ ucfirst($ticket->priority) }}</span>
                            @elseif($ticket->priority === 'high')
                                <span class="badge bg-warning">{{ ucfirst($ticket->priority) }}</span>
                            @elseif($ticket->priority === 'medium')
                                <span class="badge bg-info">{{ ucfirst($ticket->priority) }}</span>
                            @else
                                <span class="badge bg-success">{{ ucfirst($ticket->priority) }}</span>
                            @endif
                        </p>
                        <p class="mb-0">
                            <strong>Asignado a:</strong>
                            @if($ticket->assignedTo)
                                {{ $ticket->assignedTo->name }}
                            @else
                                <span class="text-muted">Sin asignar</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comentarios - Estilo Foro -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-comments"></i> Conversación ({{ $ticket->comments->count() }} comentarios)
                </h5>
            </div>
            <div class="card-body">
                @if($ticket->comments->count() > 0)
                    @foreach($ticket->comments as $comment)
                        <div class="comment-box">
                            <div class="comment-header">
                                <div class="comment-avatar">
                                    {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <strong>{{ $comment->user->name }}</strong>
                                    <br>
                                    <span class="comment-meta">
                                        {{ $comment->user->department?->name ?? 'N/A' }} • {{ $comment->created_at->diffForHumans() }}
                                    </span>
                                    @if($comment->is_internal)
                                        <span class="badge bg-secondary ms-2">Interno</span>
                                    @endif
                                </div>
                            </div>
                            <p class="mb-2">{{ $comment->comment }}</p>
                            
                            @if($comment->attachments->count() > 0)
                                <div class="mt-2">
                                    <small class="text-muted">Adjuntos:</small>
                                    <div class="row mt-1">
                                        @foreach($comment->attachments as $attachment)
                                            <div class="col-md-4 mb-1">
                                                <a href="{{ asset('storage/' . $attachment->file_path) }}" 
                                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                                    <i class="fas fa-download"></i> {{ $attachment->file_name }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center">No hay comentarios aún.</p>
                @endif
            </div>
        </div>

        <!-- Agregar Comentario -->
        @auth
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-comment"></i> Agregar Comentario
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/tickets/{{ $ticket->id }}/comment" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <textarea class="form-control" name="comment" rows="4" 
                                      placeholder="Escribe tu comentario..." required></textarea>
                        </div>

                        @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_internal" name="is_internal">
                                <label class="form-check-label" for="is_internal">
                                    Comentario interno (solo para el equipo de soporte)
                                </label>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="attachments" class="form-label">Adjuntos (opcional)</label>
                            <input type="file" class="form-control" id="attachments" 
                                   name="attachments[]" multiple>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Enviar Comentario
                        </button>
                    </form>
                </div>
            </div>
        @endauth
    </div>

    <!-- Sidebar -->
    <div class="col-lg-3">
        <!-- Acciones -->
        @if(Auth::user()->isSupport() || Auth::user()->isAdmin() || Auth::user()->id === $ticket->user_id)
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Acciones</h6>
                </div>
                <div class="card-body">
                    @if(Auth::user()->isSupport() || Auth::user()->isAdmin())
                        <form method="POST" action="/tickets/{{ $ticket->id }}/status" class="mb-2">
                            @csrf
                            @method('PUT')
                            <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                <option value="">Cambiar Estado...</option>
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Abierto</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>En Proceso</option>
                                <option value="pending_user" {{ $ticket->status === 'pending_user' ? 'selected' : '' }}>Pendiente Usuario</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resuelto</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Cerrado</option>
                            </select>
                        </form>

                        <button class="btn btn-sm btn-outline-primary w-100" data-bs-toggle="modal" 
                                data-bs-target="#assignModal">
                            <i class="fas fa-user-check"></i> Asignar
                        </button>
                    @endif
                </div>
            </div>
        @endif

        <!-- Timeline -->
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0">Historial</h6>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <strong>Creado:</strong> {{ $ticket->created_at->format('d/m/Y H:i') }}<br>
                    <strong>Actualizado:</strong> {{ $ticket->updated_at->format('d/m/Y H:i') }}
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal Asignar -->
@if(Auth::user()->isSupport() || Auth::user()->isAdmin())
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="/tickets/{{ $ticket->id }}/assign">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Seleccionar miembro del soporte</label>
                    <select class="form-select" name="user_id" required>
                        <option value="">-- Selecciona --</option>
                        @foreach(\App\Models\User::where('role', 'support')->get() as $user)
                            <option value="{{ $user->id }}" 
                                    {{ $ticket->assigned_to === $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->department->name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Asignar</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
