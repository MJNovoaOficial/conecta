@extends('layouts.app')
@section('title', 'Subir Manual — Admin')

@section('styles')
<style>
.form-label-custom { font-size: 0.82rem; font-weight: 600; color: #4a5568; margin-bottom: 5px; display: block; }
.form-control-custom {
    border: 1.5px solid #e2e8f0; border-radius: 7px; padding: 9px 12px;
    font-size: 0.875rem; width: 100%; outline: none;
    transition: border-color 0.2s, box-shadow 0.2s; color: #2d3748; background: #fff;
}
.form-control-custom:focus { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.1); }
.form-control-custom.is-invalid { border-color: #e74c3c; }
.field-error { color:#e74c3c; font-size:0.78rem; margin-top:4px; }
.upload-zone {
    border: 2px dashed #fca5a5; border-radius: 10px; padding: 32px;
    text-align: center; cursor: pointer; transition: all .2s; background: #fff5f5;
}
.upload-zone:hover, .upload-zone.dragover { border-color: #ef4444; background: #fee2e2; }
</style>
@endsection

@section('content')
<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'manuales'])
    <div class="admin-content-wrap">
        <div class="admin-wrapper" style="max-width:600px;">

            <div class="admin-page-header">
                <h1><i class="fas fa-upload" style="color:#ef4444;margin-right:8px;"></i>Subir Manual</h1>
                <a href="{{ route('admin.manuales.index') }}" class="btn-back-admin">&#8592; Volver</a>
            </div>

            <div class="admin-card" style="padding:28px;">
                <form method="POST" action="{{ route('admin.manuales.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Título --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label-custom">Título del Manual *</label>
                        <input type="text" name="titulo"
                               class="form-control-custom @error('titulo') is-invalid @enderror"
                               value="{{ old('titulo') }}"
                               placeholder="Ej: Guía para configurar el correo corporativo"
                               required autofocus>
                        @error('titulo')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Descripción --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label-custom">Descripción <span style="font-weight:400;color:#a0aec0;">(opcional)</span></label>
                        <textarea name="descripcion"
                                  class="form-control-custom @error('descripcion') is-invalid @enderror"
                                  style="resize:vertical;min-height:80px;"
                                  placeholder="Breve descripción de qué cubre este manual...">{{ old('descripcion') }}</textarea>
                        @error('descripcion')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Categoría --}}
                    <div style="margin-bottom:16px;">
                        <label class="form-label-custom">Categoría <span style="font-weight:400;color:#a0aec0;">(opcional)</span></label>
                        <input type="text" name="categoria"
                               class="form-control-custom @error('categoria') is-invalid @enderror"
                               value="{{ old('categoria') }}"
                               placeholder="Ej: correo, red, sap, impresoras, windows..."
                               list="cat-suggestions">
                        <datalist id="cat-suggestions">
                            <option value="correo">
                            <option value="red">
                            <option value="sap">
                            <option value="windows">
                            <option value="impresoras">
                            <option value="general">
                        </datalist>
                        <div style="font-size:0.73rem;color:#a0aec0;margin-top:4px;">
                            <i class="fas fa-info-circle me-1"></i>
                            Permite filtrar manuales por área temática.
                        </div>
                        @error('categoria')<div class="field-error"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    {{-- Archivo PDF --}}
                    <div style="margin-bottom:24px;">
                        <label class="form-label-custom">Archivo PDF *</label>
                        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('pdfFile').click()">
                            <i class="fas fa-file-pdf" style="font-size:2.2rem;color:#ef4444;margin-bottom:10px;display:block;"></i>
                            <div id="uploadText" style="font-size:0.85rem;color:#718096;font-weight:600;">
                                Haz clic aquí o arrastra el PDF
                            </div>
                            <div style="font-size:0.73rem;color:#a0aec0;margin-top:4px;">Solo PDF · Máximo 20 MB</div>
                        </div>
                        <input type="file" name="archivo" id="pdfFile" accept=".pdf" style="display:none;" required>
                        @error('archivo')<div class="field-error" style="margin-top:6px;"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>@enderror
                    </div>

                    <button type="submit"
                            style="width:100%;padding:11px;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;
                                   border:none;border-radius:8px;font-weight:700;font-size:0.9rem;cursor:pointer;
                                   display:flex;align-items:center;justify-content:center;gap:8px;">
                        <i class="fas fa-upload"></i> Subir Manual
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const pdfFile = document.getElementById('pdfFile');
const uploadZone = document.getElementById('uploadZone');
const uploadText = document.getElementById('uploadText');

pdfFile.addEventListener('change', function () {
    if (this.files[0]) {
        uploadText.textContent = '✓ ' + this.files[0].name;
        uploadZone.style.borderColor = '#10b981';
        uploadZone.style.background = '#f0fdf4';
    }
});

uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type === 'application/pdf') {
        const dt = new DataTransfer();
        dt.items.add(file);
        pdfFile.files = dt.files;
        uploadText.textContent = '✓ ' + file.name;
        uploadZone.style.borderColor = '#10b981';
        uploadZone.style.background = '#f0fdf4';
    }
});
</script>
@endsection
