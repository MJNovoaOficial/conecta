@extends('layouts.app')
@section('title', 'Estados del Flujo — Conecta')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content-wrap { flex:1; background:#f5f7fa; min-width:0; overflow-x:hidden; }
.states-wrap { max-width:920px; margin:0 auto; padding:28px 24px 60px; }

.states-header { margin-bottom:28px; }
.states-header h1 { font-size:1.35rem; font-weight:700; color:#1a2332; margin:0 0 4px; }
.states-header p  { font-size:.84rem; color:#718096; margin:0; }

/* Flow diagram */
.flow-diagram { display:flex; align-items:center; flex-wrap:wrap; gap:0; margin-bottom:28px; background:#fff; border:1px solid #e8ecf0; border-radius:12px; padding:20px 24px; }
.flow-state {
    display:flex; flex-direction:column; align-items:center; padding:10px 14px;
    border-radius:8px; font-size:.75rem; font-weight:700; text-align:center; min-width:90px;
}
.flow-arrow { color:#cbd5e0; font-size:1.2rem; padding:0 4px; flex-shrink:0; }

/* State cards */
.state-grid { display:grid; gap:14px; }
.state-card {
    background:#fff; border:1.5px solid #e8ecf0; border-radius:10px;
    overflow:hidden; transition:box-shadow .15s;
}
.state-card:hover { box-shadow:0 3px 12px rgba(0,0,0,.07); }
.state-header {
    display:flex; align-items:center; gap:14px;
    padding:16px 20px; border-bottom:1px solid #f0f2f5;
}
.state-icon {
    width:40px; height:40px; border-radius:10px; display:flex;
    align-items:center; justify-content:center; font-size:1rem; flex-shrink:0;
}
.state-info { flex:1; }
.state-key  { font-size:.68rem; font-family:monospace; color:#a0aec0; font-weight:600; margin-bottom:2px; }
.state-name { font-size:.92rem; font-weight:700; color:#1a2332; }
.state-count { font-size:.78rem; color:#718096; margin-left:auto; text-align:right; }
.state-count strong { display:block; font-size:1.3rem; font-weight:700; }

.state-body { padding:14px 20px 16px; display:flex; align-items:flex-start; gap:24px; }
.state-desc { font-size:.81rem; color:#4a5568; line-height:1.55; flex:1; }

.transition-pills { display:flex; gap:5px; flex-wrap:wrap; margin-top:6px; }
.t-pill { font-size:.7rem; padding:2px 8px; border-radius:12px; background:#f0f2f5; color:#4a5568; font-weight:600; }

.label-field {
    display:flex; align-items:center; gap:8px; border:1.5px solid #e2e8f0;
    border-radius:7px; padding:6px 10px; background:#fafbfc; min-width:200px;
}
.label-field i { color:#a0aec0; font-size:.75rem; }
.label-field input {
    border:none; background:transparent; font-size:.83rem; color:#1a2332;
    font-weight:600; width:100%; outline:none;
}

.save-bar { position:sticky; bottom:0; background:#fff; border-top:1px solid #e2e8f0; padding:12px 0; margin-top:24px; display:flex; justify-content:flex-end; gap:10px; }
.btn-save { padding:9px 24px; background:#3498db; color:#fff; border:none; border-radius:7px; font-weight:600; font-size:.87rem; cursor:pointer; transition:background .15s; }
.btn-save:hover { background:#2980b9; }
.btn-cancel { padding:9px 20px; background:#e2e8f0; color:#4a5568; border:none; border-radius:7px; font-weight:600; font-size:.87rem; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; }

.info-note { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 16px; font-size:.8rem; color:#1e40af; margin-bottom:20px; }
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'states'])
<div class="admin-content-wrap">
<div class="states-wrap">

    <div class="states-header">
        <h1><i class="fas fa-sitemap me-2" style="color:#3498db;"></i>Estados del Flujo de Tickets</h1>
        <p>Visualiza el ciclo de vida de los tickets y personaliza las etiquetas de cada estado (RF-AD-08).</p>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:10px 16px;margin-bottom:16px;font-size:.83rem;color:#065f46;">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    </div>
    @endif

    <div class="info-note">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Flujo definido por el sistema:</strong> Los estados son parte del modelo de negocio y controlan el ciclo de vida del ticket.
        Puedes personalizar cómo se <em>llama</em> cada estado en la interfaz. Los cambios quedan guardados y se muestran a usuarios y técnicos.
    </div>

    {{-- Diagrama del flujo --}}
    <div class="flow-diagram">
        <span style="font-size:.72rem;font-weight:700;color:#718096;margin-right:12px;white-space:nowrap;">Flujo:</span>
        @php
            $flowOrder = ['open','in_progress','pending_user','forwarded','resolved','closed'];
            $flowColors = ['open'=>'#dcfce7','in_progress'=>'#fef9c3','pending_user'=>'#ffedd5','forwarded'=>'#dbeafe','resolved'=>'#ede9fe','closed'=>'#f1f5f9'];
            $flowText   = ['open'=>'#166534','in_progress'=>'#854d0e','pending_user'=>'#9a3412','forwarded'=>'#1e40af','resolved'=>'#5b21b6','closed'=>'#475569'];
        @endphp
        @foreach($flowOrder as $idx => $key)
            <div class="flow-state" style="background:{{ $flowColors[$key] }};color:{{ $flowText[$key] }};">
                <i class="{{ $stateDefinitions[$key]['icon'] }}" style="font-size:.9rem;margin-bottom:4px;"></i>
                {{ $customLabels[$key] }}
                <span style="font-size:.65rem;opacity:.7;margin-top:2px;">{{ $counts[$key] ?? 0 }} tickets</span>
            </div>
            @if(!$loop->last)
                <span class="flow-arrow">›</span>
            @endif
        @endforeach
    </div>

    {{-- Cards con formulario --}}
    <form method="POST" action="{{ route('admin.states.update') }}">
        @csrf
        <div class="state-grid">
            @foreach($stateDefinitions as $key => $def)
            @php
                $bgLight = ['open'=>'#f0fdf4','in_progress'=>'#fffbeb','pending_user'=>'#fff7ed','forwarded'=>'#eff6ff','resolved'=>'#f5f3ff','closed'=>'#f8fafc'];
                $countVal = $counts[$key] ?? 0;
            @endphp
            <div class="state-card" style="border-left:4px solid {{ $def['color'] }};">
                <div class="state-header">
                    <div class="state-icon" style="background:{{ $bgLight[$key] ?? '#f8fafc' }};">
                        <i class="{{ $def['icon'] }}" style="color:{{ $def['color'] }};"></i>
                    </div>
                    <div class="state-info">
                        <div class="state-key">{{ $key }}</div>
                        <div class="state-name">{{ $def['default'] }}</div>
                    </div>
                    <div class="state-count" style="color:{{ $def['color'] }};">
                        <strong>{{ $countVal }}</strong>
                        ticket{{ $countVal !== 1 ? 's' : '' }}
                    </div>
                </div>
                <div class="state-body">
                    <div style="flex:1;">
                        <div class="state-desc">{{ $def['description'] }}</div>
                        @if(!empty($def['transitions']))
                        <div class="transition-pills" style="margin-top:8px;">
                            <span style="font-size:.68rem;color:#a0aec0;align-self:center;">→</span>
                            @foreach($def['transitions'] as $t)
                                <span class="t-pill">{{ $t }}</span>
                            @endforeach
                        </div>
                        @else
                            <div style="margin-top:8px;font-size:.72rem;color:#a0aec0;font-style:italic;">Estado final — sin transiciones.</div>
                        @endif
                    </div>
                    <div>
                        <div style="font-size:.7rem;color:#718096;font-weight:600;margin-bottom:4px;">Etiqueta en la interfaz</div>
                        <div class="label-field">
                            <i class="fas fa-tag"></i>
                            <input type="text"
                                   name="labels[{{ $key }}]"
                                   value="{{ $customLabels[$key] }}"
                                   placeholder="{{ $def['default'] }}"
                                   maxlength="60"
                                   required>
                        </div>
                        @if($customLabels[$key] !== $def['default'])
                            <div style="font-size:.67rem;color:#f97316;margin-top:3px;">
                                <i class="fas fa-edit"></i> Personalizado
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="save-bar">
            <a href="{{ route('admin.dashboard') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">
                <i class="fas fa-save me-1"></i> Guardar etiquetas
            </button>
        </div>
    </form>

</div>
</div>
</div>
@endsection
