

@extends('layouts.app')

@section('title', 'Editar Regla de Prioridad')

@section('content')
<style>
/* Reuse admin layout styles */
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content { flex:1; padding:28px 32px; background:#f5f7fa; min-width:0; }
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.page-title { font-size:1.35rem; font-weight:700; color:#1a2332; display:flex; align-items:center; gap:10px; }
.page-title i { color:#4f8cff; }
.admin-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:24px; overflow:hidden; }
.admin-card-header { background:linear-gradient(90deg,#1a2332,#243447); padding:14px 20px; display:flex; align-items:center; gap:10px; }
.admin-card-header h5 { color:#fff; font-size:.95rem; font-weight:600; margin:0; }
.admin-card-body { padding:20px 24px; }
.form-group label { font-size:.76rem; font-weight:600; color:#64748b; display:block; margin-bottom:4px; }
.form-group select, .form-group input { width:100%; border:1.5px solid #e2e8f0; border-radius:8px; padding:8px 10px; font-size:.84rem; background:#fff; color:#1a2332; transition:border-color .2s; }
.form-group select:focus, .form-group input:focus { border-color:#4f8cff; outline:none; box-shadow:0 0 0 3px rgba(79,140,255,.15); }
.btn-save { background:linear-gradient(135deg,#4f8cff,#3b6fd4); color:#fff; border:none; border-radius:8px; padding:9px 18px; font-size:.85rem; font-weight:600; cursor:pointer; transition:opacity .2s; }
.btn-save:hover { opacity:.88; }
</style>

<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'priority_rules'])

    <div class="admin-content">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-edit"></i>
                Editar Regla de Prioridad
            </div>
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

        <div class="admin-card">
            <div class="admin-card-header">
                <i class="fas fa-pencil-alt" style="color:#60a5fa;"></i>
                <h5>Actualizar Regla</h5>
            </div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.priority-rules.update', $priorityRule) }}">
                    @csrf
                    @method('PUT')
                    <div class="rule-form">
                        <div class="form-group">
                            <label>Categoría *</label>
                            <select name="categoria_id" id="editRuleCategoria" required onchange="filterSubcatsEdit(this.value)">
                                <option value="">— Selecciona —</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ $priorityRule->categoria_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subcategoría <small style="color:#94a3b8;">(opcional)</small></label>
                            <select name="subcategoria_id" id="editRuleSubcat" onchange="filterTiposEdit(this.value)">
                                <option value="">— Todas —</option>
                                @foreach($subcategorias as $s)
                                    <option value="{{ $s->id }}" data-cat="{{ $s->categoria_id }}" {{ $priorityRule->subcategoria_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de Incidente <small style="color:#94a3b8;">(opcional)</small></label>
                            <select name="tipo_incidente_id" id="editRuleTipo">
                                <option value="">— Todas —</option>
                                @foreach($tipos as $t)
                                    <option value="{{ $t->id }}" data-sub="{{ $t->subcategoria_id }}" {{ $priorityRule->tipo_incidente_id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Prioridad *</label>
                            <select name="priority" required>
                                <option value="critical" {{ $priorityRule->priority == 'critical' ? 'selected' : '' }}>🔴 Crítica</option>
                                <option value="high" {{ $priorityRule->priority == 'high' ? 'selected' : '' }}>🟠 Alta</option>
                                <option value="medium" {{ $priorityRule->priority == 'medium' ? 'selected' : '' }}>🟡 Media</option>
                                <option value="low" {{ $priorityRule->priority == 'low' ? 'selected' : '' }}>🟢 Baja</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nota <small style="color:#94a3b8;">(opcional)</small></label>
                            <input type="text" name="description" placeholder="Ej: Impacta en producción" maxlength="200" value="{{ $priorityRule->description }}">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-save">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function filterSubcatsEdit(catId) {
    const subSel = document.getElementById('editRuleSubcat');
    const tipoSel = document.getElementById('editRuleTipo');
    Array.from(subSel.options).forEach(o => {
        o.hidden = catId && o.dataset.cat && o.dataset.cat !== catId;
    });
    subSel.value = '';
    tipoSel.value = '';
}
function filterTiposEdit(subId) {
    const tipoSel = document.getElementById('editRuleTipo');
    Array.from(tipoSel.options).forEach(o => {
        o.hidden = subId && o.dataset.sub && o.dataset.sub !== subId;
    });
    tipoSel.value = '';
}
</script>
@endsection
