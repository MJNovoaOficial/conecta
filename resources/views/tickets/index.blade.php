@extends('layouts.app')

@section('title', 'Mis Tickets')

@section('content')
<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-ticket-alt"></i> Mis Tickets</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="/tickets/create" class="btn btn-success">
            <i class="fas fa-plus"></i> Nuevo Ticket
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($tickets->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Número</th>
                            <th>Título</th>
                            <th>Departamento</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Creado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tickets as $ticket)
                            <tr>
                                <td><strong>{{ $ticket->ticket_number }}</strong></td>
                                <td>{{ $ticket->title }}</td>
                                <td>{{ $ticket->department->name }}</td>
                                <td>
                                    @if($ticket->priority === 'critical')
                                        <span class="badge bg-danger">{{ ucfirst($ticket->priority) }}</span>
                                    @elseif($ticket->priority === 'high')
                                        <span class="badge bg-warning">{{ ucfirst($ticket->priority) }}</span>
                                    @elseif($ticket->priority === 'medium')
                                        <span class="badge bg-info">{{ ucfirst($ticket->priority) }}</span>
                                    @else
                                        <span class="badge bg-success">{{ ucfirst($ticket->priority) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $ticket->getStatusBadgeClass() }}">
                                        {{ $ticket->getStatusLabel() }}
                                    </span>
                                </td>
                                <td><small>{{ $ticket->created_at->diffForHumans() }}</small></td>
                                <td>
                                    <a href="/tickets/{{ $ticket->id }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $tickets->links() }}
            </div>
        @else
            <div class="alert alert-info text-center">
                <i class="fas fa-inbox"></i> No tienes tickets aún.
                <a href="/tickets/create" class="alert-link">Crea uno ahora</a>
            </div>
        @endif
    </div>
</div>
@endsection
