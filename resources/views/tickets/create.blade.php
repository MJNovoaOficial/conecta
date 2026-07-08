@extends('layouts.app')

@section('title', 'Crear Nuevo Ticket')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-plus"></i> Crear Nuevo Ticket
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/tickets" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label for="title" class="form-label">Título o Asunto *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción del Problema *</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="5" required>{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Categoría/Tipo *</label>
                            <select class="form-select @error('category') is-invalid @enderror" 
                                    id="category" name="category" required>
                                <option value="">Selecciona una categoría</option>
                                <option value="hardware">Hardware</option>
                                <option value="software">Software</option>
                                <option value="network">Red/Internet</option>
                                <option value="account">Cuenta/Acceso</option>
                                <option value="other">Otro</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="device_type" class="form-label">Tipo de Dispositivo *</label>
                            <select class="form-select @error('device_type') is-invalid @enderror" 
                                    id="device_type" name="device_type" required>
                                <option value="">Selecciona un dispositivo</option>
                                <option value="laptop">Laptop</option>
                                <option value="desktop">Desktop</option>
                                <option value="tablet">Tablet</option>
                                <option value="phone">Teléfono</option>
                                <option value="printer">Impresora</option>
                                <option value="other">Otro</option>
                            </select>
                            @error('device_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Prioridad *</label>
                            <select class="form-select @error('priority') is-invalid @enderror" 
                                    id="priority" name="priority" required>
                                <option value="">Selecciona prioridad</option>
                                <option value="low">Baja</option>
                                <option value="medium" selected>Media</option>
                                <option value="high">Alta</option>
                                <option value="critical">Crítica</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="department_id" class="form-label">Departamento *</label>
                            <select class="form-select @error('department_id') is-invalid @enderror" 
                                    id="department_id" name="department_id" required>
                                <option value="">Selecciona departamento</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" 
                                            {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="attachments" class="form-label">Adjuntos (Máximo 5MB por archivo)</label>
                        <input type="file" class="form-control @error('attachments.*') is-invalid @enderror" 
                               id="attachments" name="attachments[]" multiple>
                        <small class="text-muted">Puedes adjuntar imágenes, documentos, etc.</small>
                        @error('attachments.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Enviar Ticket
                        </button>
                        <a href="/tickets" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
