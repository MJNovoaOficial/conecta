@extends('layouts.app')

@section('title', 'Nuevo Departamento — Admin')

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
.btn-submit-ticket {
    background: linear-gradient(135deg, #2980b9, #3498db);
    color: #fff; border: none; padding: 10px 20px; border-radius: 7px;
    font-weight: 700; font-size: 0.9rem; cursor: pointer;
    transition: all 0.2s; box-shadow: 0 2px 8px rgba(41,128,185,0.3); width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit-ticket:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(41,128,185,0.4); }
.field-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem; }
.field-icon-top { position: absolute; left: 11px; top: 12px; color: #a0aec0; font-size: 0.82rem; }
.field-error { color:#e74c3c; font-size:0.78rem; margin-top:4px; }
</style>
@endsection

@section('content')
<div style="min-height: calc(100vh - 52px); display: flex; align-items: center; justify-content: center; padding: 24px;">
    <div style="width: 100%; max-width: 460px;">

        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 28px;">
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #1a2332; margin: 0 0 4px;">Conecta Soporte</h1>
            <p style="color: #718096; font-size: 0.85rem; margin: 0;">Panel de Administración</p>
        </div>

        {{-- Card --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.07); overflow: hidden;">

            <div style="background: linear-gradient(90deg, #1a2332, #243447); padding: 14px 22px;">
                <h2 style="color: #fff; font-size: 0.95rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-sitemap me-2" style="color: #3498db;"></i>Nuevo Departamento
                </h2>
            </div>

            <div style="padding: 24px 22px;">
                <form method="POST" action="/admin/departments">
                    @csrf

                    {{-- Nombre --}}
                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Nombre del Departamento</label>
                        <div style="position: relative;">
                            <span class="field-icon"><i class="fas fa-building"></i></span>
                            <input type="text" name="name"
                                   class="form-control-custom @error('name') is-invalid @enderror"
                                   style="padding-left: 32px;"
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Tecnología, Recursos Humanos..."
                                   autofocus required>
                        </div>
                        @error('name')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Descripción --}}
                    <div style="margin-bottom: 20px;">
                        <label class="form-label-custom">Descripción <span style="font-weight:400; color:#a0aec0;">(opcional)</span></label>
                        <div style="position: relative;">
                            <span class="field-icon-top"><i class="fas fa-align-left"></i></span>
                            <textarea name="description"
                                      class="form-control-custom @error('description') is-invalid @enderror"
                                      style="padding-left: 32px; resize: vertical; min-height: 80px;"
                                      placeholder="Descripción breve del área o equipo...">{{ old('description') }}</textarea>
                        </div>
                        @error('description')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn-submit-ticket">
                        <i class="fas fa-plus"></i> Crear Departamento
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <div style="border-top: 1px solid #f0f2f5; background: #f7f9fc; padding: 13px 22px; text-align: center;">
                <a href="/admin/departments" style="font-size: 0.82rem; color: #718096; text-decoration: none;">
                    <i class="fas fa-arrow-left me-1"></i> Volver a Departamentos
                </a>
            </div>
        </div>

        <p style="text-align: center; color: #a0aec0; font-size: 0.75rem; margin-top: 20px;">
            Conecta © {{ date('Y') }} · Mesa de Ayuda
        </p>
    </div>
</div>
@endsection
