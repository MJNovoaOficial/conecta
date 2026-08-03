@extends('layouts.app')

@section('title', 'Reglas de Prioridad Automática')

@section('content')
<style>
/* ═══════════ Admin Shared Layout ═══════════ */
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 60px); }
.admin-content { flex:1; padding:28px 32px; background:#f5f7fa; min-width:0; }

/* ═══════════ Page Header ═══════════ */
.page-header {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:24px; flex-wrap:wrap; gap:12px;
}
.page-title { font-size:1.35rem; font-weight:700; color:#1a2332; display:flex; align-items:center; gap:10px; }
.page-title i { color:#4f8cff; }

/* ═══════════ Cards ═══════════ */
.admin-card {
    background:#fff; border-radius:14px;
    box-shadow:0 2px 12px rgba(0,0,0,.07);
    margin-bottom:24px; overflow:hidden;
}
.admin-card-header {
    background:linear-gradient(90deg,#1a2332,#243447);
    padding:14px 20px; display:flex; align-items:center; gap:10px;
}
.admin-card-header h5 { color:#fff; font-size:.95rem; font-weight:600; margin:0; }
.admin-card-body { padding:20px 24px; }

/* ═══════════ Form ═══════════ */
.rule-form { display:grid; grid-template-columns:1fr 1fr 1fr 1fr auto auto; gap:10px; align-items:end; }
@media(max-width:900px) { .rule-form { grid-template-columns:1fr 1fr; } }
.form-group label { font-size:.76rem; font-weight:600; color:#64748b; display:block; margin-bottom:4px; }
.form-group select, .form-group input {
    width:100%; border:1.5px solid #e2e8f0; border-radius:8px;
    padding:8px 10px; font-size:.84rem; background:#fff; color:#1a2332;
    transition:border-color .2s;
}
.form-group select:focus, .form-group input:focus {
    border-color:#4f8cff; outline:none; box-shadow:0 0 0 3px rgba(79,140,255,.15);
}
.btn-add {
    background:linear-gradient(135deg,#4f8cff,#3b6fd4);
    color:#fff; border:none; border-radius:8px;
    padding:9px 18px; font-size:.85rem; font-weight:600;
    cursor:pointer; white-space:nowrap; transition:opacity .2s;
    display:flex; align-items:center; gap:6px;
}
.btn-add:hover { opacity:.88; }

/* ═══════════ Rules Table ═══════════ */
.rules-table { width:100%; border-collapse:collapse; }
.rules-table th {
    background:#f1f5f9; font-size:.74rem; font-weight:700;
    color:#64748b; padding:10px 14px; text-align:left;
    text-transform:uppercase; letter-spacing:.05em;
}
.rules-table td { padding:11px 14px; font-size:.84rem; color:#374151; border-bottom:1px solid #f0f2f5; }
.rules-table tr:last-child td { border-bottom:none; }
.rules-table tr:hover td { background:#f9fafc; }

/* Priority badges */
.prio-badge {
    display:inline-block; padding:3px 10px; border-radius:20px;
    font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
}
.prio-critical { background:#fef2f2; color:#dc2626; }
.prio-high     { background:#fff7ed; color:#ea580c; }
.prio-medium   { background:#fefce8; color:#ca8a04; }
.prio-low      { background:#f0fdf4; color:#16a34a; }

.btn-del {
    background:#fef2f2; border:1px solid #fecaca; color:#dc2626;
    border-radius:6px; padding:5px 10px; font-size:.78rem;
    cursor:pointer; transition:background .2s;
}
.btn-del:hover { background:#fee2e2; }

/* ═══════════ Empty state ═══════════ */
.empty-state { text-align:center; padding:40px 20px; color:#94a3b8; }
.empty-state i { font-size:2.5rem; margin-bottom:10px; display:block; }

/* ═══════════ Info box ═══════════ */
.info-box {
    background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px;
    padding:14px 18px; font-size:.82rem; color:#1e40af; margin-bottom:20px;
}
.info-box i { margin-right:6px; }
</style>

<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'priority_rules'])

    <div class="admin-content">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-sliders-h"></i>
                Reglas de Prioridad Automática
            </div>
        </div>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            El sistema asigna prioridad automáticamente al crear un ticket según la categoría, subcategoría y tipo de incidente.
            La regla <strong>más específica</strong> tiene precedencia (Categoría + Subcategoría + Tipo > Categoría + Subcategoría > solo Categoría).
            Si no hay regla coincidente, se asigna prioridad <strong>Media</strong> por defecto.
        </div>

        @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#15803d;font-size:.84rem;">
            <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#dc2626;font-size:.84rem;">
            <i class="fas fa-exclamation-circle me-1"></i>{{ $errors->first() }}
        </div>
        @endif

        {{-- Formulario nueva regla --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <i class="fas fa-plus-circle" style="color:#60a5fa;"></i>
                <h5>Agregar Nueva Regla</h5>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.priority-rules.store') }}">
                    @csrf
                    <div class="rule-form">
                        <div class="form-group">
                            <label>Categoría *</label>
                            <select name="categoria_id" id="ruleCategoria" required onchange="filterSubcats(this.value)">
                                <option value="">— Selecciona —</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subcategoría <small style="color:#94a3b8;">(opcional)</small></label>
                            <select name="subcategoria_id" id="ruleSubcat" onchange="filterTipos(this.value)">
                                <option value="">— Todas —</option>
                                @foreach($subcategorias as $s)
                                    <option value="{{ $s->id }}" data-cat="{{ $s->categoria_id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Incidente <small style="color:#94a3b8;">(opcional)</small></label>
                            <select name="tipo_incidente_id" id="ruleTipo">
                                <option value="">— Todos —</option>
                                @foreach($tipos as $t)
                                    <option value="{{ $t->id }}" data-sub="{{ $t->subcategoria_id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prioridad *</label>
                            <select name="priority" required>
                                <option value="critical">🔴 Crítica</option>
                                <option value="high">🟠 Alta</option>
                                <option value="medium" selected>🟡 Media</option>
                                <option value="low">🟢 Baja</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nota <small style="color:#94a3b8;">(opcional)</small></label>
                            <input type="text" name="description" placeholder="Ej: Impacta en producción" maxlength="200">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-add">
                                <i class="fas fa-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla de reglas --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <i class="fas fa-list" style="color:#60a5fa;"></i>
                <h5>Reglas Configuradas ({{ $rules->count() }})</h5>
            </div>
            <div class="admin-card-body" style="padding:0;">
                @if($rules->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-sliders-h"></i>
                    <p>No hay reglas configuradas.<br>Agrega una regla arriba para que el sistema asigne prioridades automáticamente.</p>
                </div>
                @else
                <div style="overflow-x:auto;">
                    <table class="rules-table">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th>Subcategoría</th>
                                <th>Tipo de Incidente</th>
                                <th>Prioridad</th>
                                <th>Nota</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rules as $rule)
                            <tr>
                                <td><strong>{{ $rule->categoria?->name ?? '—' }}</strong></td>
                                <td style="color:{{ $rule->subcategoria ? '#374151' : '#94a3b8' }};">{{ $rule->subcategoria?->name ?? 'Todas' }}</td>
                                <td style="color:{{ $rule->tipoIncidente ? '#374151' : '#94a3b8' }};">{{ $rule->tipoIncidente?->name ?? 'Todos' }}</td>
                                <td>
                                    <span class="prio-badge prio-{{ $rule->priority }}">
                                        {{ ['critical'=>'Crítica','high'=>'Alta','medium'=>'Media','low'=>'Baja'][$rule->priority] }}
                                    </span>
                                </td>
                                <td style="color:#64748b;">{{ $rule->description ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.priority-rules.destroy', $rule) }}" onsubmit="return confirm('¿Eliminar esta regla?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-del" type="submit"><i class="fas fa-trash"></i> Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

    </div>{{-- /admin-content --}}
</div>{{-- /admin-layout --}}

<script>
function filterSubcats(catId) {
    const subSel = document.getElementById('ruleSubcat');
    const tipoSel = document.getElementById('ruleTipo');
    Array.from(subSel.options).forEach(o => {
        o.hidden = catId && o.dataset.cat && o.dataset.cat !== catId;
    });
    subSel.value = '';
    tipoSel.value = '';
}
function filterTipos(subId) {
    const tipoSel = document.getElementById('ruleTipo');
    Array.from(tipoSel.options).forEach(o => {
        o.hidden = subId && o.dataset.sub && o.dataset.sub !== subId;
    });
    tipoSel.value = '';
}
</script>
@endsection
