@extends('layouts.app')

@section('title', 'Ticket sin Cuenta - Conecta')

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
    color: #fff; border: none; padding: 11px 20px; border-radius: 7px;
    font-weight: 700; font-size: 0.9rem; cursor: pointer;
    transition: all 0.2s; box-shadow: 0 2px 8px rgba(41,128,185,0.3); width: 100%;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.btn-submit-ticket:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(41,128,185,0.4); }
textarea.form-control-custom { resize: vertical; min-height: 90px; }
.field-err { color:#e74c3c; font-size:0.76rem; margin-top:3px; }
</style>
@endsection

@section('content')
<div style="min-height: calc(100vh - 52px); display: flex; align-items: center; justify-content: center; padding: 24px;">
    <div style="width: 100%; max-width: 520px;">

        {{-- Header --}}
        <div style="text-align: center; margin-bottom: 28px;">
            <h1 style="font-size: 1.4rem; font-weight: 700; color: #1a2332; margin: 0 0 4px;">Conecta Soporte</h1>
            <p style="color: #718096; font-size: 0.85rem; margin: 0;">Mesa de Ayuda — Dimak</p>
        </div>

        {{-- Card --}}
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.07); overflow: hidden;">

            <div style="background: linear-gradient(90deg, #1a2332, #243447); padding: 14px 22px;">
                <h2 style="color: #fff; font-size: 0.95rem; font-weight: 600; margin: 0;">
                    <i class="fas fa-user-clock me-2" style="color: #2ecc71;"></i>Enviar Ticket sin Cuenta
                </h2>
            </div>

            {{-- Info banner --}}
            <div style="background: #ebf8ff; border-bottom: 1px solid #bee3f8; padding: 10px 22px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-info-circle" style="color: #3498db; flex-shrink:0;"></i>
                <span style="font-size: 0.8rem; color: #2c5282;">
                    No necesitas registrarte. Recibirás un <strong>enlace único</strong> para dar seguimiento a tu ticket.
                </span>
            </div>

            <div style="padding: 24px 22px;">
                <form method="POST" action="{{ route('tickets.guest.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- ── Tus datos ───────────────────────────────────── --}}
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #a0aec0; margin-bottom: 12px;">
                        <i class="fas fa-user me-1"></i> Tus datos
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label-custom">Nombre Completo *</label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem;"><i class="fas fa-user"></i></span>
                                <input type="text" name="guest_name"
                                       class="form-control-custom @error('guest_name') is-invalid @enderror"
                                       style="padding-left: 32px;"
                                       value="{{ old('guest_name') }}"
                                       placeholder="Tu nombre" required>
                            </div>
                            @error('guest_name')<div class="field-err">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label-custom">Correo Electrónico *</label>
                            <div style="position: relative;">
                                <span style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem;"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="guest_email"
                                       class="form-control-custom @error('guest_email') is-invalid @enderror"
                                       style="padding-left: 32px;"
                                       value="{{ old('guest_email') }}"
                                       placeholder="tu@correo.com" required>
                            </div>
                            @error('guest_email')<div class="field-err">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- ── Ticket ───────────────────────────────────────── --}}
                    <div style="border-top: 1px solid #f0f2f5; margin-bottom: 16px;"></div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #a0aec0; margin-bottom: 12px;">
                        <i class="fas fa-ticket-alt me-1"></i> Tu solicitud
                    </div>

                    {{-- Campo 1: Asunto --}}
                    <div style="margin-bottom: 14px;">
                        <label class="form-label-custom">
                            <i class="fas fa-question-circle me-1" style="color:#3498db;"></i>¿Cuál es tu problema o solicitud? *
                        </label>
                        <input type="text" name="title"
                               class="form-control-custom @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               placeholder="Ej: No puedo entrar a mi correo, la impresora no imprime..."
                               required>
                        @error('title')<div class="field-err">{{ $message }}</div>@enderror
                    </div>

                    {{-- Campo 2: Descripción --}}
                    <div style="margin-bottom: 14px;">
                        <label class="form-label-custom">
                            Más detalles <small style="font-weight:400;color:#a0aec0;">(opcional)</small>
                        </label>
                        <textarea name="description" rows="3"
                                  class="form-control-custom @error('description') is-invalid @enderror"
                                  placeholder="Describe el problema con más detalle...">{{ old('description') }}</textarea>
                        @error('description')<div class="field-err">{{ $message }}</div>@enderror
                    </div>

                    {{-- Campo 3: Adjuntos --}}
                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">
                            Adjuntar archivo o video <small style="font-weight:400;color:#a0aec0;">(opcional — máx. 5 archivos)</small>
                        </label>
                        <div style="border: 1.5px dashed #e2e8f0; border-radius: 7px; padding: 14px; text-align: center; cursor: pointer; transition: border-color 0.2s;"
                             onclick="document.getElementById('attachments').click()"
                             onmouseenter="this.style.borderColor='#3498db'"
                             onmouseleave="this.style.borderColor='#e2e8f0'">
                            <i class="fas fa-cloud-upload-alt" style="color: #a0aec0; margin-bottom: 4px; display:block; font-size:1.3rem;"></i>
                            <span style="font-size: 0.78rem; color: #a0aec0;">Haz clic para adjuntar imágenes, PDF o videos cortos</span>
                            <div id="fileNames" style="font-size: 0.78rem; color: #4a5568; margin-top: 4px;"></div>
                        </div>
                        <input type="file" id="attachments" name="attachments[]" multiple style="display:none;"
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.mp4,.mov,.webm"
                               onchange="showFiles(this)">
                        @error('attachments.*')<div class="field-err">{{ $message }}</div>@enderror
                    </div>

                    {{-- Acordeón: más detalles opcionales --}}
                    <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:20px;">
                        <button type="button" id="btnGuestDetails"
                                onclick="toggleGuestDetails()"
                                style="width:100%;padding:10px 14px;background:#f7f9fc;border:none;text-align:left;
                                       font-size:0.82rem;color:#718096;font-weight:600;cursor:pointer;
                                       display:flex;align-items:center;justify-content:space-between;">
                            <span><i class="fas fa-sliders-h me-2" style="color:#a0aec0;"></i>Más detalles <span style="font-weight:400;">(opcional)</span></span>
                            <i class="fas fa-chevron-down" id="guestChevron" style="transition:transform .2s;"></i>
                        </button>
                        <div id="guestDetailsSection" style="display:none;padding:16px 14px;border-top:1px solid #f0f2f5;">

                            {{-- Área / Departamento (texto libre) --}}
                            <div style="margin-bottom: 14px;">
                                <label class="form-label-custom">Área / Departamento</label>
                                <div style="position: relative;">
                                    <span style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem;"><i class="fas fa-building"></i></span>
                                    <input type="text" name="guest_department"
                                           class="form-control-custom @error('guest_department') is-invalid @enderror"
                                           style="padding-left: 32px;"
                                           value="{{ old('guest_department') }}"
                                           placeholder="Ej: Ventas, Contabilidad...">
                                </div>
                                @error('guest_department')<div class="field-err">{{ $message }}</div>@enderror
                            </div>

                            {{-- Departamento (select) --}}
                            <div style="margin-bottom: 14px;">
                                <label class="form-label-custom">Departamento del sistema</label>
                                <select name="department_id" class="form-control-custom @error('department_id') is-invalid @enderror">
                                    <option value="">No sé / No aplica</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="field-err">{{ $message }}</div>@enderror
                            </div>

                            {{-- Categoría --}}
                            <div style="margin-bottom: 10px;">
                                <label class="form-label-custom">Categoría</label>
                                <select id="guestCatSelect" class="form-control-custom mb-2" onchange="loadGuestSubcats(this.value)">
                                    <option value="">No sé / No aplica</option>
                                    @foreach(App\Models\Categoria::where('is_active', true)->orderBy('name')->get() as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <select id="guestSubcatSelect" name="subcategoria_id" class="form-control-custom mb-2"
                                        onchange="loadGuestTipos(this.value)" disabled>
                                    <option value="">Primero selecciona categoría...</option>
                                </select>
                                <select id="guestTipoSelect" name="tipo_incidente_id" class="form-control-custom" disabled>
                                    <option value="">Tipo de incidente (opcional)...</option>
                                </select>
                                @error('subcategoria_id')<div class="field-err">{{ $message }}</div>@enderror
                            </div>

                        </div>
                    </div>

                    <button type="submit" class="btn-submit-ticket">
                        <i class="fas fa-paper-plane"></i> Enviar Solicitud
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <div style="border-top: 1px solid #f0f2f5; background: #f7f9fc;">
                <div style="padding: 13px 22px; border-bottom: 1px solid #f0f2f5; text-align: center;">
                    <span style="font-size: 0.82rem; color: #718096;">
                        ¿Tienes cuenta?
                        <a href="{{ route('home') }}" style="color: #3498db; font-weight: 700; text-decoration: none;">Inicia sesión aquí</a>
                    </span>
                </div>
                <div style="padding: 12px 22px; text-align: center;">
                    <a href="{{ route('register') }}"
                       style="display: inline-flex; align-items: center; gap: 7px; font-size: 0.8rem; color: #718096; text-decoration: none; padding: 6px 16px; border: 1.5px solid #cbd5e0; border-radius: 6px; transition: all 0.2s; background: #fff;"
                       onmouseenter="this.style.borderColor='#3498db'; this.style.color='#3498db'; this.style.background='#ebf5fb';"
                       onmouseleave="this.style.borderColor='#cbd5e0'; this.style.color='#718096'; this.style.background='#fff';">
                        <i class="fas fa-user-plus"></i> Crear una cuenta
                    </a>
                </div>
            </div>

        </div>

        <p style="text-align: center; color: #a0aec0; font-size: 0.75rem; margin-top: 20px;">
            Conecta © {{ date('Y') }} · Mesa de Ayuda Dimak
        </p>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showFiles(input) {
    const names = Array.from(input.files).map(f => f.name).join(', ');
    document.getElementById('fileNames').textContent = names;
}

function toggleGuestDetails() {
    const section = document.getElementById('guestDetailsSection');
    const chevron = document.getElementById('guestChevron');
    const open = section.style.display === 'block';
    section.style.display = open ? 'none' : 'block';
    chevron.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
}

function loadGuestSubcats(catId) {
    const sub  = document.getElementById('guestSubcatSelect');
    const tipo = document.getElementById('guestTipoSelect');
    tipo.innerHTML = '<option value="">Tipo de incidente (opcional)...</option>';
    tipo.disabled  = true;
    if (!catId) {
        sub.innerHTML = '<option value="">Primero selecciona categoría...</option>';
        sub.disabled  = true;
        return;
    }
    sub.innerHTML = '<option value="">Cargando...</option>';
    sub.disabled  = true;
    fetch(`/api/categorias/${catId}/subcategorias`)
        .then(r => r.json())
        .then(data => {
            sub.innerHTML = '<option value="">Seleccionar subcategoría...</option>';
            data.forEach(s => {
                const o = document.createElement('option');
                o.value = s.id; o.textContent = s.name;
                sub.appendChild(o);
            });
            sub.disabled = false;
        })
        .catch(() => { sub.innerHTML = '<option value="">Error al cargar</option>'; });
}

function loadGuestTipos(subcatId) {
    const tipo = document.getElementById('guestTipoSelect');
    if (!subcatId) { tipo.innerHTML = '<option value="">Tipo de incidente (opcional)...</option>'; tipo.disabled = true; return; }
    tipo.innerHTML = '<option value="">Cargando...</option>';
    tipo.disabled  = true;
    fetch(`/api/subcategorias/${subcatId}/tipos`)
        .then(r => r.json())
        .then(data => {
            tipo.innerHTML = '<option value="">Sin tipo específico</option>';
            data.forEach(t => {
                const o = document.createElement('option');
                o.value = t.id; o.textContent = t.name;
                tipo.appendChild(o);
            });
            tipo.disabled = (data.length === 0);
        })
        .catch(() => { tipo.innerHTML = '<option value="">Sin tipos</option>'; });
}
</script>
@endsection
