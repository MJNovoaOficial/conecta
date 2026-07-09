@extends('layouts.app')

@section('title', 'Ticket sin Cuenta - Conecta')

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

                    {{-- Sección: tus datos --}}
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
                            @error('guest_name')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
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
                            @error('guest_email')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label class="form-label-custom">Área / Departamento *</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #a0aec0; font-size: 0.82rem;"><i class="fas fa-building"></i></span>
                            <input type="text" name="guest_department"
                                   class="form-control-custom @error('guest_department') is-invalid @enderror"
                                   style="padding-left: 32px;"
                                   value="{{ old('guest_department') }}"
                                   placeholder="Ej: Ventas, Contabilidad..." required>
                        </div>
                        @error('guest_department')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                    </div>

                    {{-- Divisor --}}
                    <div style="border-top: 1px solid #f0f2f5; margin-bottom: 20px;"></div>
                    <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #a0aec0; margin-bottom: 12px;">
                        <i class="fas fa-ticket-alt me-1"></i> Datos del ticket
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Asunto *</label>
                        <input type="text" name="title"
                               class="form-control-custom @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
                               placeholder="Describe brevemente el problema..." required>
                        @error('title')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label-custom">Departamento *</label>
                            <select name="department_id" class="form-control-custom @error('department_id') is-invalid @enderror" required>
                                <option value="">Seleccionar...</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label-custom">Categoría *</label>
                            <select name="category" class="form-control-custom @error('category') is-invalid @enderror" required>
                                <option value="">Seleccionar...</option>
                                <option value="hardware"  {{ old('category') == 'hardware'  ? 'selected' : '' }}>Hardware</option>
                                <option value="software"  {{ old('category') == 'software'  ? 'selected' : '' }}>Software</option>
                                <option value="network"   {{ old('category') == 'network'   ? 'selected' : '' }}>Red/Internet</option>
                                <option value="account"   {{ old('category') == 'account'   ? 'selected' : '' }}>Cuenta/Acceso</option>
                                <option value="other"     {{ old('category') == 'other'     ? 'selected' : '' }}>Otro</option>
                            </select>
                            @error('category')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label-custom">Prioridad *</label>
                            <select name="priority" class="form-control-custom @error('priority') is-invalid @enderror" required>
                                <option value="low"      {{ old('priority') == 'low'      ? 'selected' : '' }}>Baja</option>
                                <option value="medium"   {{ old('priority', 'medium') == 'medium' ? 'selected' : '' }}>Media</option>
                                <option value="high"     {{ old('priority') == 'high'     ? 'selected' : '' }}>Alta</option>
                                <option value="critical" {{ old('priority') == 'critical' ? 'selected' : '' }}>Crítica</option>
                            </select>
                            @error('priority')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <label class="form-label-custom">Descripción *</label>
                        <textarea name="description" rows="4"
                                  class="form-control-custom @error('description') is-invalid @enderror"
                                  placeholder="Describe el problema con el mayor detalle posible..." required>{{ old('description') }}</textarea>
                        @error('description')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label class="form-label-custom">Adjuntos <small style="font-weight:400;color:#a0aec0;">(opcional, máx. 5MB c/u)</small></label>
                        <div style="border: 1.5px dashed #e2e8f0; border-radius: 7px; padding: 14px; text-align: center; cursor: pointer; transition: border-color 0.2s;"
                             onclick="document.getElementById('attachments').click()"
                             onmouseenter="this.style.borderColor='#3498db'"
                             onmouseleave="this.style.borderColor='#e2e8f0'">
                            <i class="fas fa-paperclip" style="color: #a0aec0; margin-bottom: 4px; display:block;"></i>
                            <span style="font-size: 0.78rem; color: #a0aec0;">Clic para adjuntar archivos</span>
                            <div id="fileNames" style="font-size: 0.78rem; color: #4a5568; margin-top: 4px;"></div>
                        </div>
                        <input type="file" id="attachments" name="attachments[]" multiple style="display:none;" onchange="showFiles(this)">
                        @error('attachments.*')<div style="color:#e74c3c;font-size:0.76rem;margin-top:3px;">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn-submit-ticket">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Ticket
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
textarea.form-control-custom { resize: vertical; min-height: 90px; }
@media (max-width: 480px) {
    div[style*="grid-template-columns: 1fr 1fr 1fr"] { grid-template-columns: 1fr !important; }
    div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>
@endsection

@section('scripts')
<script>
function showFiles(input) {
    const names = Array.from(input.files).map(f => f.name).join(', ');
    document.getElementById('fileNames').textContent = names;
}
</script>
@endsection
