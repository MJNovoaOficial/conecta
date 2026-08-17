@extends('layouts.app')
@section('title', 'Editar Manual — Admin')

@section('styles')
<style>
.form-label-custom { font-size: 0.82rem; font-weight: 600; color: #4a5568; margin-bottom: 5px; display: block; }
.form-control-custom {
    border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 9px 12px;
    font-size: 0.875rem; width: 100%; outline: none;
    transition: border-color 0.2s, box-shadow 0.2s; color: #2d3748; background: #fff;
}
.form-control-custom:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
.field-error { color:#e74c3c; font-size:0.78rem; margin-top:4px; }
</style>
@endsection

@section('content')
<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'manuales'])
    <div class="admin-content-wrap">
        <div class="admin-wrapper" style="max-width:600px;">

            <div class="admin-page-header">
                <h1><i class="fas fa-edit" style="color:#ef4444;margin-right:8px;"></i>Editar Manual</h1>
                <a href="{{ route('admin.manuales.index') }}" class="btn-back-admin">&#8592; Volver</a>
            </div>

            <div class="admin-card" style="padding:28px;">
                <form method="POST" action="{{ route('admin.manuales.update', $manual) }}" enctype="multipart/form-data">
                    @csrf @method('PUT')

                    {{-- Título --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label-custom">Título *</label>
                        <input type="text" name="titulo"
                               class="form-control-custom @error('titulo') is-invalid @enderror"
                               value="{{ old('titulo', $manual->titulo) }}" required>
                        @error('titulo')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Descripción --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label-custom">Descripción <span style="font-weight:400;color:#a0aec0;">(opcional)</span></label>
                        <textarea name="descripcion"
                                  class="form-control-custom @error('descripcion') is-invalid @enderror"
                                  style="resize:vertical;min-height:80px;">{{ old('descripcion', $manual->descripcion) }}</textarea>
                        @error('descripcion')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Categoría --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label-custom">Categoría <span style="font-weight:400;color:#a0aec0;">(opcional)</span></label>
                        <input type="text" name="categoria"
                               class="form-control-custom @error('categoria') is-invalid @enderror"
                               value="{{ old('categoria', $manual->categoria) }}"
                               list="cat-suggestions2">
                        <datalist id="cat-suggestions2">
                            <option value="correo"><option value="red"><option value="sap">
                            <option value="windows"><option value="impresoras"><option value="general">
                        </datalist>
                        @error('categoria')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    {{-- Estado activo --}}
                    <div style="margin-bottom:16px;">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" {{ $manual->is_active ? 'checked' : '' }}
                                   style="width:16px;height:16px;accent-color:#ef4444;">
                            <span class="form-label-custom" style="margin:0;">Manual activo (visible para usuarios)</span>
                        </label>
                    </div>

                    {{-- Archivo actual --}}
                    <div style="margin-bottom:16px;background:#f7f9fc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;">
                        <div style="font-size:0.78rem;color:#718096;margin-bottom:4px;font-weight:600;">Archivo actual:</div>
                        <div style="font-size:0.85rem;color:#1a2332;display:flex;align-items:center;gap:8px;">
                            <i class="fas fa-file-pdf" style="color:#ef4444;"></i>
                            {{ $manual->archivo_nombre_original }}
                            <span style="color:#a0aec0;font-size:0.75rem;">({{ $manual->tamano_formateado }})</span>
                        </div>
                    </div>

                    {{-- Reemplazar PDF --}}
                    <div style="margin-bottom:24px;">
                        <label class="form-label-custom">Reemplazar PDF <span style="font-weight:400;color:#a0aec0;">(opcional — deja vacío para mantener el actual)</span></label>
                        <input type="file" name="archivo" accept=".pdf"
                               class="form-control-custom @error('archivo') is-invalid @enderror">
                        @error('archivo')<div class="field-error">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit"
                            style="width:100%;padding:11px;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;
                                   border:none;border-radius:8px;font-weight:700;font-size:0.9rem;cursor:pointer;
                                   display:flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
