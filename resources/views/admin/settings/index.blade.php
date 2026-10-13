@extends('layouts.app')
@section('title', 'Configuración del Sistema — Conecta')

@section('styles')
<style>
.config-wrapper { max-width: 900px; margin: 0 auto; padding: 28px 24px 60px; }
.config-header { margin-bottom: 28px; }
.config-header h1 { font-size: 1.4rem; font-weight: 700; color: #1a2332; margin: 0 0 4px; }
.config-header p  { font-size: .85rem; color: #718096; margin: 0; }

.tab-bar { display: flex; gap: 4px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 0; }
.tab-btn {
    padding: 8px 18px; border-radius: 6px 6px 0 0; font-size: .84rem; font-weight: 600;
    cursor: pointer; border: none; background: none; color: #718096; border-bottom: 2px solid transparent;
    margin-bottom: -2px; transition: all .15s;
}
.tab-btn.active { color: #3498db; border-bottom-color: #3498db; background: #f0f7ff; }
.tab-btn:hover:not(.active) { background: #f7f9fc; color: #4a5568; }

.tab-panel { display: none; }
.tab-panel.active { display: block; }

.setting-group { background: #fff; border: 1px solid #e8ecf0; border-radius: 10px; margin-bottom: 18px; overflow: hidden; }
.setting-group-header { padding: 13px 18px; background: #f7f9fc; border-bottom: 1px solid #e8ecf0; font-size: .8rem; font-weight: 700; color: #4a5568; display: flex; align-items: center; gap: 8px; }
.setting-row { padding: 14px 18px; border-bottom: 1px solid #f0f2f5; display: flex; align-items: center; gap: 16px; }
.setting-row:last-child { border-bottom: none; }
.setting-info { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.setting-label { font-size: .85rem; font-weight: 600; color: #2d3748; }
.setting-desc  { font-size: .76rem; color: #a0aec0; margin-top: 2px; }
.setting-control { min-width: 200px; }
.setting-control input[type="text"],
.setting-control input[type="number"],
.setting-control input[type="email"],
.setting-control select {
    width: 100%; padding: 7px 10px; border: 1.5px solid #e2e8f0; border-radius: 6px; font-size: .83rem; color: #2d3748;
    transition: border-color .15s;
}
.setting-control input:focus, .setting-control select:focus { outline: none; border-color: #3498db; }
.toggle-wrap { display: flex; align-items: center; gap: 8px; }
.toggle { position: relative; display: inline-block; width: 42px; height: 24px; }
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background: #cbd5e0; border-radius: 24px; transition: .2s;
}
.toggle-slider:before {
    position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
    background: #fff; border-radius: 50%; transition: .2s;
}
.toggle input:checked + .toggle-slider { background: #3498db; }
.toggle input:checked + .toggle-slider:before { transform: translateX(18px); }
.toggle-lbl { font-size: .82rem; color: #718096; }

.save-bar { position: sticky; bottom: 0; background: #fff; border-top: 1px solid #e2e8f0; padding: 12px 0; margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px; }
.btn-save { padding: 9px 24px; background: #3498db; color: #fff; border: none; border-radius: 7px; font-weight: 600; font-size: .87rem; cursor: pointer; transition: background .15s; }
.btn-save:hover { background: #2980b9; }
.btn-cancel { padding: 9px 20px; background: #e2e8f0; color: #4a5568; border: none; border-radius: 7px; font-weight: 600; font-size: .87rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
@media (max-width: 600px) {
    .setting-row { flex-direction: column; align-items: stretch; gap: 10px; }
    .setting-info { min-width: 0; }
    .setting-control { min-width: 0; width: 100%; }
    .setting-control input[type="text"], .setting-control input[type="number"],
    .setting-control input[type="email"] { font-size: 1rem; }
    .tab-bar { flex-wrap: wrap; }
    .save-bar { flex-wrap: wrap; }
    .save-bar .btn-save { flex: 1 1 180px; }
}
@media (max-width: 991.98px), (pointer: coarse) and (max-width: 1366px) {
    .tab-btn, .btn-save, .btn-cancel { min-height: 44px; }
    .btn-cancel { justify-content: center; align-items: center; }
    .setting-label { font-size: .95rem; }
    .setting-desc { font-size: .85rem; }
    .toggle-lbl { font-size: .9rem; }
    .toggle { width: 44px; height: 44px; }
    .toggle-slider { top: 10px; bottom: 10px; left: 1px; right: 1px; }
}
</style>
@endsection

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content-wrap { flex:1; background:#f5f7fa; min-width:0; overflow-x:hidden; }
.config-wrapper { max-width:900px; margin:0 auto; padding:28px 24px 60px; }
</style>
<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'settings'])
<div class="admin-content-wrap">
<div class="config-wrapper">

    <div class="config-header">
        <h1><i class="fas fa-cog me-2" style="color:#3498db;"></i>Configuración del Sistema</h1>
        <p>Configura la sesión, los avisos y los límites globales de adjuntos. Generales es informativo; SLA mantiene su propia ventana.</p>
    </div>

    <div role="note" style="padding:14px 18px;margin-bottom:18px;background:#fff7ed;border:1px solid #fde68a;border-radius:8px;color:#92400e;font-size:.85rem;">
        <strong>Opciones operativas:</strong> sesión por inactividad, avisos y límites globales de adjuntos.
        Los límites anteriores de archivos se conservan hasta activar expresamente la opción global. Los plazos de cierre y el acceso de invitados sin login se mantienen.
        Los tiempos por prioridad se gestionan exclusivamente en <a href="{{ route('admin.sla.index') }}">Configurar SLA</a>.
        Cada usuario puede elegir sus correos de tickets en <a href="{{ route('profile.index') }}">Mi Perfil</a>.
    </div>

    @if(collect($settings)->flatMap(fn ($group) => $group)->contains(fn ($setting) => !$setting->exists && in_array($setting->key, $editableKeys)))
    <div role="status" style="padding:12px 18px;margin-bottom:18px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1e40af;font-size:.85rem;">
        Las opciones editables aún no guardadas muestran sus valores iniciales. Al guardar se crearán solo las enviadas, sin restablecer opciones existentes ni modificar datos de tickets.
    </div>
    @endif

    @if($errors->any())
    <div role="alert" style="padding:12px 18px;margin-bottom:16px;border:1px solid #fecaca;border-radius:8px;color:#991b1b;">
        Revisa los valores indicados:
        <ul style="margin:6px 0 0;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" id="settingsForm">
        @csrf

        {{-- Tabs --}}
        <div class="tab-bar">
            <button type="button" class="tab-btn active" onclick="showTab('general', this)"><i class="fas fa-sliders-h me-1"></i> General</button>
            <button type="button" class="tab-btn" onclick="showTab('attachments', this)"><i class="fas fa-paperclip me-1"></i> Adjuntos</button>
            <button type="button" class="tab-btn" onclick="showTab('notifications', this)"><i class="fas fa-bell me-1"></i> Notificaciones</button>
            <button type="button" class="tab-btn" onclick="showTab('security', this)"><i class="fas fa-shield-alt me-1"></i> Seguridad</button>
        </div>

        {{-- ── GENERAL ── --}}
        <div class="tab-panel active" id="tab-general">
            <div class="setting-group">
                <div class="setting-group-header"><i class="fas fa-info-circle"></i> Parámetros Generales</div>
                @forelse($settings['general'] as $s)
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label">{{ $s->label }}</div>
                        @if($s->description)<div class="setting-desc">{{ $s->description }}</div>@endif
                    </div>
                    <div class="setting-control">
                        <div style="font-size:.95rem;overflow-wrap:anywhere;">{{ $readOnlyValues[$s->key] ?? $s->value }}</div>
                    </div>
                </div>
                @empty
                <p style="padding:14px 18px;margin:0;">No hay parámetros generales cargados. Está pendiente su inicialización puntual, sin restablecer datos existentes.</p>
                @endforelse
            </div>
        </div>

        <div class="tab-panel" id="tab-attachments">
            <div class="setting-group">
                <div class="setting-group-header">Límites globales de adjuntos</div>
                <div style="padding:14px 18px;font-size:.9rem;overflow-wrap:anywhere;">
                    <strong>Límites actuales en uso:</strong><br>
                    Crear ticket: {{ $creationLimits['files'] }} archivos, hasta {{ $creationLimits['kilobytes']/1024 }} MB por archivo.<br>
                    Responder: {{ $replyLimits['files'] }} archivos, hasta {{ $replyLimits['kilobytes']/1024 }} MB por archivo.<br>
                    Al activar los límites globales, los valores guardados siguientes reemplazan ambos límites. No se modifican archivos ya adjuntados.
                </div>
                @foreach($settings['attachments'] as $s)
                <div class="setting-row">
                    <div class="setting-info"><div class="setting-label">{{ $s->label }}</div><div class="setting-desc">{{ $s->description }}</div></div>
                    <div class="setting-control">
                        @if($s->key === 'attachments_global_enabled')
                        <div class="toggle-wrap"><label class="toggle">
                            <input type="hidden" name="settings[{{ $s->key }}]" value="0">
                            <input type="checkbox" name="settings[{{ $s->key }}]" value="1" {{ filter_var($s->value,FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                            <span class="toggle-slider"></span>
                        </label><span class="toggle-lbl">{{ filter_var($s->value,FILTER_VALIDATE_BOOLEAN) ? 'Global activo' : 'Conservar límites anteriores' }}</span></div>
                        @else
                        <input type="number" name="settings[{{ $s->key }}]" value="{{ $s->value }}" min="1" max="{{ $s->key === 'max_attachment_size_mb' ? 50 : 5 }}" step="1">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── NOTIFICACIONES ── --}}
        <div class="tab-panel" id="tab-notifications">
            <div class="setting-group">
                <div class="setting-group-header"><i class="fas fa-bell"></i> Configuración de Notificaciones (RF-AD-13)</div>
                @forelse($settings['notifications'] as $s)
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label">{{ $s->label }}</div>
                        @if($s->description)<div class="setting-desc">{{ $s->description }}</div>@endif
                    </div>
                    <div class="setting-control">
                        @if(in_array($s->key, $editableKeys) && $s->type === 'boolean')
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="hidden" name="settings[{{ $s->key }}]" value="0">
                                <input type="checkbox" name="settings[{{ $s->key }}]" value="1" {{ filter_var($s->value, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-lbl">{{ filter_var($s->value, FILTER_VALIDATE_BOOLEAN) ? 'Habilitado' : 'Deshabilitado' }}</span>
                        </div>
                        @else
                        <div style="font-size:.95rem;overflow-wrap:anywhere;">{{ $readOnlyValues[$s->key] ?? $s->value }}</div>
                        @endif
                    </div>
                </div>
                @empty
                <p style="padding:14px 18px;margin:0;">No hay parámetros de notificaciones cargados. Está pendiente su inicialización puntual.</p>
                @endforelse
            </div>
        </div>

        {{-- ── SEGURIDAD ── --}}
        <div class="tab-panel" id="tab-security">
            <div class="setting-group">
                <div class="setting-group-header"><i class="fas fa-shield-alt"></i> Parámetros de Seguridad (RNF-06 al RNF-09)</div>
                @forelse($settings['security'] as $s)
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label">{{ $s->label }}</div>
                        @if($s->description)<div class="setting-desc">{{ $s->description }}</div>@endif
                    </div>
                    <div class="setting-control">
                        @if($s->key === 'session_timeout_minutes')
                        <input type="number" name="settings[{{ $s->key }}]" value="{{ $s->value }}" min="1" max="1440" step="1">
                        @else
                        <div style="font-size:.95rem;overflow-wrap:anywhere;">{{ $readOnlyValues[$s->key] ?? $s->value }}</div>
                        @endif
                    </div>
                </div>
                @empty
                <p style="padding:14px 18px;margin:0;">No hay parámetros de seguridad cargados. Las reglas actuales del login y la sesión se mantienen.</p>
                @endforelse
            </div>

            {{-- Intentos de login recientes --}}
            @if($recentFailedLogins->isNotEmpty())
            <div class="setting-group" style="margin-top:16px;">
                <div class="setting-group-header"><i class="fas fa-exclamation-triangle" style="color:#e53e3e;"></i> Últimos intentos fallidos de login (RNF-08)</div>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#fef2f2;">
                            <th style="padding:8px 14px;font-size:.75rem;font-weight:700;color:#718096;text-align:left;">Email</th>
                            <th style="padding:8px 14px;font-size:.75rem;font-weight:700;color:#718096;text-align:left;">IP</th>
                            <th style="padding:8px 14px;font-size:.75rem;font-weight:700;color:#718096;text-align:left;">Fecha/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentFailedLogins as $attempt)
                        <tr style="border-bottom:1px solid #f0f2f5;">
                            <td style="padding:7px 14px;font-size:.82rem;">{{ $attempt->email }}</td>
                            <td style="padding:7px 14px;font-size:.82rem;font-family:monospace;">{{ $attempt->ip_address }}</td>
                            <td style="padding:7px 14px;font-size:.82rem;color:#718096;">{{ $attempt->attempted_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="save-bar">
            <a href="{{ route('admin.dashboard') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save"><i class="fas fa-save me-1"></i> Guardar configuración</button>
        </div>
    </form>
</div>{{-- /config-wrapper --}}
</div>{{-- /admin-content-wrap --}}
</div>{{-- /admin-layout --}}
@endsection

@push('scripts')
<script>
function showTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}
// Actualizar etiqueta del toggle al cambiar
document.querySelectorAll('.toggle input').forEach(inp => {
    inp.addEventListener('change', function() {
        const lbl = this.closest('.toggle-wrap').querySelector('.toggle-lbl');
        if (lbl) lbl.textContent = this.checked ? 'Activo' : 'Inactivo';
    });
});
</script>
@endpush
