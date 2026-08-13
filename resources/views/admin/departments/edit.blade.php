@extends('layouts.app')

@section('title', 'Editar Departamento — Admin')

@section('styles')
<style>
    .form-label-custom { font-size: 0.82rem; font-weight: 600; color: #4a5568; margin-bottom: 5px; display: block; }
    .form-control-custom {
        border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 9px 12px;
        font-size: 0.875rem; width: 100%; outline: none;
        transition: border-color 0.2s, box-shadow 0.2s; color: #2d3748; background: #fff;
    }
    .form-control-custom:focus { border-color: #3498db; box-shadow: 0 0 0 3px rgba(52,152,219,0.12); }
    .form-control-custom.is-invalid { border-color: #e74c3c; }
    .btn-submit { background: linear-gradient(135deg, #2980b9, #3498db); color: #fff; border: none; padding: 10px 20px; border-radius: 7px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px rgba(41,128,185,0.3); width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(41,128,185,0.4); }
    .field-error { color:#e74c3c; font-size:0.78rem; margin-top:4px; }
</style>
@endsection

@section('content')
<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'departments'])
    <div class="admin-content-wrap">
        <div class="admin-wrapper">
            <div class="admin-page-header">
                <h1><i class="fas fa-sitemap" style="color:#3498db; margin-right:8px;"></i>Editar Departamento</h1>
                <a href="{{ route('admin.departments') }}" class="btn-back-admin">&#8592; Volver</a>
            </div>
            <div class="admin-card" style="padding:24px;">
                <form method="POST" action="{{ route('admin.departments.update', $department) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label-custom">Nombre</label>
                        <input type="text" name="name" class="form-control-custom @error('name') is-invalid @enderror" value="{{ old('name', $department->name) }}" required>
                        @error('name')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label-custom">Descripción</label>
                        <textarea name="description" class="form-control-custom @error('description') is-invalid @enderror" rows="3">{{ old('description', $department->description) }}</textarea>
                        @error('description')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="is_active" id="active" value="1" {{ $department->is_active ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Departamento activo</label>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
