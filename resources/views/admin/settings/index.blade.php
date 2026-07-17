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
.setting-info { flex: 1; }
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
</style>
@endsection

@section('content')
<div class="config-wrapper">

    <div class="config-header">
        <h1><i class="fas fa-cog me-2" style="color:#3498db;"></i>Configuración del Sistema</h1>
        <p>Administra los parámetros generales, notificaciones, SLA y seguridad de la plataforma.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" id="settingsForm">
        @csrf

        {{-- Tabs --}}
        <div class="tab-bar">
            <button type="button" class="tab-btn active" onclick="showTab('general', this)"><i class="fas fa-sliders-h me-1"></i> General</button>
            <button type="button" class="tab-btn" onclick="showTab('notifications', this)"><i class="fas fa-bell me-1"></i> Notificaciones</button>
            <button type="button" class="tab-btn" onclick="showTab('sla', this)"><i class="fas fa-clock me-1"></i> SLA</button>
            <button type="button" class="tab-btn" onclick="showTab('security', this)"><i class="fas fa-shield-alt me-1"></i> Seguridad</button>
        </div>

        {{-- ── GENERAL ── --}}
        <div class="tab-panel active" id="tab-general">
            <div class="setting-group">
                <div class="setting-group-header"><i class="fas fa-info-circle"></i> Parámetros Generales</div>
                @foreach($settings['general'] as $s)
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label">{{ $s->label }}</div>
                        @if($s->description)<div class="setting-desc">{{ $s->description }}</div>@endif
                    </div>
                    <div class="setting-control">
                        @if($s->type === 'boolean')
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox" name="settings[{{ $s->key }}]" value="1" {{ $s->value ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-lbl">{{ $s->value ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        @elseif($s->type === 'integer')
                        <input type="number" name="settings[{{ $s->key }}]" value="{{ $s->value }}" min="1">
                        @else
                        <input type="text" name="settings[{{ $s->key }}]" value="{{ $s->value }}">
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
                @foreach($settings['notifications'] as $s)
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label">{{ $s->label }}</div>
                        @if($s->description)<div class="setting-desc">{{ $s->description }}</div>@endif
                    </div>
                    <div class="setting-control">
                        @if($s->type === 'boolean')
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox" name="settings[{{ $s->key }}]" value="1" {{ $s->value ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-lbl">{{ $s->value ? 'Habilitado' : 'Deshabilitado' }}</span>
                        </div>
                        @else
                        <input type="{{ $s->type === 'email' ? 'email' : 'text' }}" name="settings[{{ $s->key }}]" value="{{ $s->value }}">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── SLA ── --}}
        <div class="tab-panel" id="tab-sla">
            <div class="setting-group">
                <div class="setting-group-header"><i class="fas fa-clock"></i> Parámetros de SLA y Tiempos</div>
                @foreach($settings['sla'] as $s)
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label">{{ $s->label }}</div>
                        @if($s->description)<div class="setting-desc">{{ $s->description }}</div>@endif
                    </div>
                    <div class="setting-control">
                        @if($s->type === 'boolean')
                        <div class="toggle-wrap">
                            <label class="toggle">
                                <input type="checkbox" name="settings[{{ $s->key }}]" value="1" {{ $s->value ? 'checked' : '' }}>
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-lbl">{{ $s->value ? 'Activo' : 'Inactivo' }}</span>
                        </div>
                        @else
                        <input type="number" name="settings[{{ $s->key }}]" value="{{ $s->value }}" min="1">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <div style="padding:12px 18px;background:#fff7ed;border:1px solid #fde68a;border-radius:8px;font-size:.82rem;color:#92400e;">
                <i class="fas fa-info-circle me-1"></i>
                Los tiempos de atención y resolución por prioridad se gestionan en <a href="{{ route('admin.sla') }}" style="color:#92400e;font-weight:600;">Configuración de SLA</a>.
            </div>
        </div>

        {{-- ── SEGURIDAD ── --}}
        <div class="tab-panel" id="tab-security">
            <div class="setting-group">
                <div class="setting-group-header"><i class="fas fa-shield-alt"></i> Parámetros de Seguridad (RNF-06 al RNF-09)</div>
                @foreach($settings['security'] as $s)
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label">{{ $s->label }}</div>
                        @if($s->description)<div class="setting-desc">{{ $s->description }}</div>@endif
                    </div>
                    <div class="setting-control">
                        <input type="number" name="settings[{{ $s->key }}]" value="{{ $s->value }}" min="1">
                    </div>
                </div>
                @endforeach
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
</div>
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
