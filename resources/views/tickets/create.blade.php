@extends('layouts.app')

@section('title', 'Abrir Ticket')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4">Abrir Ticket</h1>
        <a href="{{ route('tickets.index') }}" class="btn btn-secondary">Volver a mis tickets</a>
    </div>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newTicketModal">
        Abrir formulario de solicitud
    </button>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    bootstrap.Modal.getOrCreateInstance(document.getElementById('newTicketModal')).show();
});
</script>
@endsection
