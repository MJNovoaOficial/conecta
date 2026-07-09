@extends('layouts.app')

@section('title', 'Abrir Ticket')

@section('content')
<div class="page-wrapper">

    {{-- SIDEBAR --}}
    <aside class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-ticket-alt me-2"></i>Tickets Recientes</span>
            </div>
            @if(isset($recentTickets) && $recentTickets->count() > 0)
                @foreach($recentTickets as $rt)
                <a href="{{ route('tickets.show', $rt) }}" class="sidebar-item">
                    <div class="item-left" style="flex-direction:column; align-items:flex-start; gap:2px;">
                        <span style="font-size:0.78rem; font-weight:600; color:#2980b9;">#{{ $rt->ticket_number }}</span>
                        <span style="font-size:0.76rem; color:#718096;">{{ Str::limit($rt->title, 28) }}</span>
                    </div>
                </a>
                @endforeach
            @else
                <div style="padding:14px; font-size:0.8rem; color:#a0aec0; text-align:center;">
                    Sin tickets aún
                </div>
            @endif
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-header">
                <span><i class="fas fa-headset me-2"></i>Soporte</span>
            </div>
            <a href="{{ route('tickets.index') }}" class="sidebar-item">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-ticket-alt"></i></span>
                    Mis Tickets
                </div>
            </a>
            <a href="{{ route('tickets.create') }}" class="sidebar-item active">
                <div class="item-left">
                    <span class="item-icon"><i class="fas fa-plus-circle"></i></span>
                    Abrir Ticket
                </div>
            </a>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <div class="main-content">

        <div class="page-header" style="display:flex; align-items:center; justify-content:space-between;">
            <div>
                <h1>Abrir Ticket</h1>
                <div class="breadcrumb-bar">
                    <a href="{{ route('home') }}">Inicio</a>
                    <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                    <a href="{{ route('tickets.index') }}">Tickets de Soporte</a>
                    <i class="fas fa-chevron-right" style="font-size:0.6rem;"></i>
                    <span style="color:#a0aec0;">Enviar Ticket</span>
                </div>
            </div>
            <a href="{{ route('tickets.index') }}" style="display:inline-flex; align-items:center; gap:6px; background:#1a2332; color:#fff; font-size:0.82rem; font-weight:600; padding:8px 18px; border-radius:7px; text-decoration:none; transition:background 0.2s; white-space:nowrap; flex-shrink:0;" onmouseover="this.style.background='#2d3748'" onmouseout="this.style.background='#1a2332'">
                &#8592; Volver
            </a>
        </div>

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- SECCIÓN: Asunto y Departamento --}}
            <div class="form-section">
                <div class="form-section-header">Información del Ticket</div>
                <div class="form-section-body">

                    <div class="mb-4">
                        <label class="form-label-custom">Asunto *</label>
                        <input type="text"
                               name="title"
                               class="form-control-custom @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               placeholder="Describe brevemente el problema..."
                               required>
                        @error('title')
                            <div style="color:#e74c3c; font-size:0.78rem; margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="form-label-custom">Departamento *</label>
                            <select name="department_id" class="form-control-custom @error('department_id') is-invalid @enderror" required>
                                <option value="">Seleccionar departamento...</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div style="color:#e74c3c; font-size:0.78rem; margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label-custom">Prioridad *</label>
                            <select name="priority" class="form-control-custom @error('priority') is-invalid @enderror" required>
                                <option value="low"    {{ old('priority') == 'low'    ? 'selected' : '' }}>Baja</option>
                                <option value="medium" {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                                <option value="high"   {{ old('priority') == 'high'   ? 'selected' : '' }}>Alta</option>
                                <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Crítica</option>
                            </select>
                            @error('priority')
                                <div style="color:#e74c3c; font-size:0.78rem; margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom">Tipo de Dispositivo *</label>
                            <select name="device_type" class="form-control-custom @error('device_type') is-invalid @enderror" required>
                                <option value="">Seleccionar...</option>
                                <option value="laptop"  {{ old('device_type') == 'laptop'  ? 'selected' : '' }}>Laptop</option>
                                <option value="desktop" {{ old('device_type') == 'desktop' ? 'selected' : '' }}>Desktop</option>
                                <option value="tablet"  {{ old('device_type') == 'tablet'  ? 'selected' : '' }}>Tablet</option>
                                <option value="phone"   {{ old('device_type') == 'phone'   ? 'selected' : '' }}>Teléfono</option>
                                <option value="printer" {{ old('device_type') == 'printer' ? 'selected' : '' }}>Impresora</option>
                                <option value="other"   {{ old('device_type') == 'other'   ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('device_type')
                                <div style="color:#e74c3c; font-size:0.78rem; margin-top:4px;">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label-custom">Categoría *</label>
                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            @foreach(['hardware' => 'Hardware', 'software' => 'Software', 'network' => 'Red/Internet', 'account' => 'Cuenta/Acceso', 'other' => 'Otro'] as $val => $label)
                            <label style="display:flex; align-items:center; gap:6px; padding:7px 14px; border:1.5px solid {{ old('category') == $val ? '#3498db' : '#e2e8f0' }}; border-radius:6px; cursor:pointer; font-size:0.82rem; color:{{ old('category') == $val ? '#2980b9' : '#4a5568' }}; background:{{ old('category') == $val ? '#ebf5fb' : '#fff' }};" class="category-label">
                                <input type="radio" name="category" value="{{ $val }}" {{ old('category') == $val ? 'checked' : '' }} style="accent-color:#3498db;">
                                {{ $label }}
                            </label>
                            @endforeach
                        </div>
                        @error('category')
                            <div style="color:#e74c3c; font-size:0.78rem; margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- MENSAJE --}}
                    <div class="mb-3">
                        <label class="form-label-custom">Mensaje *</label>
                        <div style="border:1.5px solid #e2e8f0; border-radius:6px; overflow:hidden;" id="messageBox">
                            {{-- Toolbar --}}
                            <div style="background:#f7f9fc; border-bottom:1px solid #e2e8f0; padding:6px 10px; display:flex; gap:4px; align-items:center;">
                                <button type="button" onclick="fmt('bold')" class="fmt-btn" title="Negrita"><b>B</b></button>
                                <button type="button" onclick="fmt('italic')" class="fmt-btn" title="Cursiva"><i>I</i></button>
                                <button type="button" onclick="fmt('underline')" class="fmt-btn" title="Subrayado"><u>U</u></button>
                                <div style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
                                <button type="button" onclick="fmt('insertUnorderedList')" class="fmt-btn" title="Lista"><i class="fas fa-list-ul"></i></button>
                                <button type="button" onclick="fmt('insertOrderedList')" class="fmt-btn" title="Lista ordenada"><i class="fas fa-list-ol"></i></button>
                                <div style="width:1px;height:18px;background:#e2e8f0;margin:0 4px;"></div>
                                <button type="button" onclick="fmt('formatBlock', 'blockquote')" class="fmt-btn" title="Cita"><i class="fas fa-quote-right"></i></button>
                            </div>
                            <div id="editor"
                                 contenteditable="true"
                                 style="min-height:180px; padding:14px; outline:none; font-size:0.87rem; color:#2d3748; line-height:1.6;"
                                 oninput="syncEditor()">{{ old('description') }}</div>
                        </div>
                        <textarea name="description" id="descriptionField" style="display:none;">{{ old('description') }}</textarea>
                        @error('description')
                            <div style="color:#e74c3c; font-size:0.78rem; margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ADJUNTOS --}}
                    <div>
                        <label class="form-label-custom">Adjuntos <small style="font-weight:400; color:#a0aec0;">(máximo 5 archivos, 5MB c/u)</small></label>
                        <div style="border:2px dashed #e2e8f0; border-radius:8px; padding:20px; text-align:center; cursor:pointer; transition:border-color 0.2s;"
                             onclick="document.getElementById('attachments').click()"
                             onmouseenter="this.style.borderColor='#3498db'"
                             onmouseleave="this.style.borderColor='#e2e8f0'">
                            <i class="fas fa-paperclip" style="font-size:24px; color:#a0aec0; margin-bottom:8px; display:block;"></i>
                            <span style="font-size:0.82rem; color:#a0aec0;">Haz clic para adjuntar archivos</span>
                            <div id="file-names" style="margin-top:8px; font-size:0.8rem; color:#4a5568;"></div>
                        </div>
                        <input type="file" id="attachments" name="attachments[]" multiple style="display:none;"
                               onchange="showFileNames(this)">
                        @error('attachments.*')
                            <div style="color:#e74c3c; font-size:0.78rem; margin-top:4px;">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- BOTONES --}}
            <div style="display:flex; gap:12px; align-items:center;">
                <button type="submit" class="btn-submit-ticket">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Ticket
                </button>
                <a href="{{ route('tickets.index') }}" style="color:#718096; font-size:0.85rem; text-decoration:none;">
                    Cancelar
                </a>
            </div>

        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
.fmt-btn {
    background: none;
    border: 1px solid transparent;
    padding: 3px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.82rem;
    color: #4a5568;
    transition: all 0.15s;
}
.fmt-btn:hover {
    background: #e2e8f0;
    border-color: #cbd5e0;
}
#messageBox:focus-within {
    border-color: #3498db !important;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.12);
}
</style>
@endsection

@section('scripts')
<script>
function fmt(cmd, val) {
    document.getElementById('editor').focus();
    document.execCommand(cmd, false, val || null);
    syncEditor();
}

function syncEditor() {
    document.getElementById('descriptionField').value = document.getElementById('editor').innerHTML;
}

function showFileNames(input) {
    const names = Array.from(input.files).map(f => f.name).join(', ');
    document.getElementById('file-names').textContent = names || '';
}

// Sync before submit
document.querySelector('form').addEventListener('submit', function() {
    syncEditor();
    const text = document.getElementById('editor').innerText.trim();
    if (!text) {
        document.getElementById('descriptionField').value = '';
    }
});

// Highlight selected category
document.querySelectorAll('.category-label').forEach(label => {
    label.querySelector('input').addEventListener('change', () => {
        document.querySelectorAll('.category-label').forEach(l => {
            l.style.borderColor = '#e2e8f0';
            l.style.color = '#4a5568';
            l.style.background = '#fff';
        });
        label.style.borderColor = '#3498db';
        label.style.color = '#2980b9';
        label.style.background = '#ebf5fb';
    });
});
</script>
@endsection
