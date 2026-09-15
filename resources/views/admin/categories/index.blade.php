@extends('layouts.app')
@section('title', 'Gestión de Categorías')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 52px); }
.admin-content {
    flex: 1;
    padding: 24px 28px 48px;
    min-width: 0;
    overflow-x: hidden;
    background:
        radial-gradient(circle at 15% 8%, rgba(59, 130, 246, 0.08), transparent 30%),
        radial-gradient(circle at 88% 14%, rgba(16, 185, 129, 0.06), transparent 26%),
        #f4f7fb;
}

.cat-layout {
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 1.5rem;
    align-items: start;
}

.cat-left {
    position: sticky;
    top: 82px;
}

.cat-form-card,
.cat-quick-card,
.category-card {
    border: 1px solid #dfe8f4;
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

.cat-form-card .card-body {
    padding: 1.15rem;
}

.cat-form-title {
    font-size: .96rem;
    font-weight: 700;
    margin-bottom: .95rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: .45rem;
}

.cat-form-title i {
    color: #2563eb;
}

.cat-form-card .form-control {
    border-radius: 10px;
    border: 1px solid #d5e2f1;
    background: #fbfdff;
}

.cat-form-card .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, .14);
}

.cat-form-card .btn-primary {
    border-radius: 10px;
    font-weight: 700;
}

.cat-quick-card {
    margin-top: 1rem;
}

.cat-quick-card .card-body {
    padding: .9rem 1rem;
}

.cat-quick-label {
    font-size: .74rem;
    color: #64748b;
    margin-bottom: .55rem;
    font-weight: 800;
    letter-spacing: .05em;
}

.cat-row-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: .75rem;
    gap: .65rem;
}

.cat-head-meta {
    display: flex;
    align-items: center;
    gap: .6rem;
    min-width: 0;
    flex-wrap: wrap;
}

.cat-name {
    font-weight: 700;
    color: #0f172a;
    font-size: .95rem;
}

.cat-stats {
    font-size: .75rem;
    color: #64748b;
    padding: .14rem .5rem;
    border-radius: 999px;
    background: #f1f5f9;
}

.cat-actions {
    display: flex;
    gap: .4rem;
}

.cat-action-btn {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .28rem .58rem;
    border-radius: 8px;
    border: 1px solid #d4deeb;
    background: #fff;
    color: #334155;
    font-size: .74rem;
    font-weight: 700;
    line-height: 1;
}

.cat-action-btn:hover {
    background: #f8fbff;
    border-color: #bfd0e6;
}

.cat-action-btn.cat-delete {
    color: #b91c1c;
    border-color: #f1c0c0;
    background: #fff7f7;
}

.cat-action-btn.cat-delete:hover {
    background: #feecec;
    border-color: #eaa8a8;
}

.cat-action-btn.cat-disabled,
.cat-action-btn:disabled {
    opacity: .55;
    cursor: not-allowed;
    background: #f8fafc;
    color: #64748b;
    border-color: #dbe3ef;
}

.btn-toggle-cat i {
    color: #64748b;
}

.sublist {
    display: none;
    padding-left: 1.25rem;
    border-left: 2px dashed #d9e4f3;
    margin-left: .45rem;
}

.subcat-row {
    border: 1px solid #dce7f5;
    border-radius: 10px;
    padding: .7rem .95rem;
    margin-bottom: .55rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
}

.add-sub-form {
    display: flex;
    gap: .5rem;
    margin-top: .65rem;
}

.add-sub-form .form-control {
    flex: 1;
    height: 37px;
    font-size: .84rem;
    border-radius: 9px;
}

.add-sub-form .btn {
    white-space: nowrap;
    border-radius: 9px;
}

.admin-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .48);
    backdrop-filter: blur(2px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.admin-modal .card {
    width: min(420px, calc(100vw - 24px));
    margin: 0;
    border-radius: 14px;
    border: 1px solid #dce6f4;
    box-shadow: 0 20px 45px rgba(15, 23, 42, .28);
}

@media (max-width: 1050px) {
    .cat-layout {
        grid-template-columns: 1fr;
    }

    .cat-left {
        position: static;
    }
}
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'categories'])
<div class="admin-content">

<div class="page-header" style="margin-bottom:20px;">
    <div class="breadcrumb-nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <span class="breadcrumb-sep">›</span>
        <span>Categorías</span>
    </div>
    <h1 class="page-title">Gestión de Categorías</h1>
    <p class="page-subtitle">Administra la jerarquía Categoría → Subcategoría → Tipo de Incidente</p>
</div>

<div class="cat-layout">

    {{-- Panel izquierdo: Crear categoría --}}
    <div class="cat-left">
        <div class="card cat-form-card" style="margin-bottom:0;">
            <div class="card-body">
                <h3 class="cat-form-title">
                    <i class="bi bi-plus-circle"></i>Nueva Categoría
                </h3>
                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="Ej: Software, Hardware…" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="Descripción opcional…">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="bi bi-plus-lg"></i> Crear Categoría
                    </button>
                </form>
            </div>
        </div>

        {{-- Navegación rápida --}}
        <div class="card cat-quick-card" style="margin-bottom:0;">
            <div class="card-body">
                <p class="cat-quick-label">ACCESOS RÁPIDOS</p>
                <a href="{{ route('admin.sla.index') }}" class="quick-link">
                    <i class="bi bi-clock-history"></i> Configurar SLA
                </a>
                <a href="{{ route('admin.reports.index') }}" class="quick-link">
                    <i class="bi bi-bar-chart-line"></i> Ver Reportes
                </a>
                <a href="{{ route('admin.audit.index') }}" class="quick-link">
                    <i class="bi bi-shield-check"></i> Auditoría
                </a>
            </div>
        </div>
    </div>

    {{-- Panel derecho: Lista de categorías expandible --}}
    <div>
        @if($categorias->isEmpty())
            <div class="empty-state">
                <i class="bi bi-tag" style="font-size:2rem;color:var(--text-muted);display:block;margin-bottom:.75rem;"></i>
                <p>No hay categorías. Crea la primera desde el panel izquierdo.</p>
            </div>
        @else
            @foreach($categorias as $cat)
            <div class="card category-card" style="margin-bottom:1rem;" id="cat-{{ $cat->id }}">
                <div class="card-body" style="padding:1rem 1.25rem;">
                    {{-- Cabecera de categoría --}}
                    <div class="cat-row-head">
                        <div class="cat-head-meta">
                            <button class="btn-toggle-cat" onclick="toggleCat({{ $cat->id }})" style="background:none;border:none;cursor:pointer;padding:0;">
                                <i class="bi bi-chevron-down text-muted" id="icon-cat-{{ $cat->id }}" style="transition:.2s;"></i>
                            </button>
                            <span class="cat-name">{{ $cat->name }}</span>
                            <span class="badge {{ $cat->is_active ? 'bg-success' : 'bg-secondary' }}" style="font-size:.65rem;">
                                {{ $cat->is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                            <span class="cat-stats">
                                {{ $cat->subcategorias_count }} subcategorías · {{ $cat->tickets_count }} tickets
                            </span>
                        </div>
                        <div class="cat-actions">
                            <button type="button" class="cat-action-btn" onclick="openEditCat({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ addslashes($cat->description ?? '') }}', {{ $cat->is_active ? 'true' : 'false' }})">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            @if($cat->tickets_count == 0)
                            <form method="POST" action="{{ route('admin.categories.destroy', $cat) }}" onsubmit="return confirm('¿Eliminar esta categoría y todas sus subcategorías?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="cat-action-btn cat-delete">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </form>
                            @else
                            <button type="button" class="cat-action-btn cat-disabled" disabled title="No se puede eliminar porque tiene tickets asociados">
                                <i class="bi bi-lock"></i> Eliminar
                            </button>
                            @endif
                        </div>
                    </div>

                    {{-- Subcategorías --}}
                    <div id="sublist-{{ $cat->id }}" class="sublist">
                        @foreach($cat->subcategorias as $sub)
                        <div class="subcat-row" style="border:1px solid var(--border-color);border-radius:.5rem;padding:.6rem 1rem;margin-bottom:.5rem;background:var(--bg-secondary);">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <div>
                                    <span style="font-weight:500;font-size:.88rem;">{{ $sub->name }}</span>
                                    <span style="font-size:.75rem;color:var(--text-muted);margin-left:.5rem;">{{ $sub->tiposIncidente->count() }} tipos</span>
                                </div>
                                <div style="display:flex;gap:.3rem;">
                                    <button class="btn btn-sm btn-outline" style="padding:.2rem .5rem;font-size:.75rem;"
                                            onclick="openAddTipo({{ $sub->id }}, '{{ addslashes($sub->name) }}')">
                                        <i class="bi bi-plus"></i> Tipo
                                    </button>
                                    <form method="POST" action="{{ route('admin.subcategorias.destroy', $sub) }}" onsubmit="return confirm('¿Eliminar esta subcategoría?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" style="padding:.2rem .5rem;font-size:.75rem;color:var(--danger);">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            {{-- Tipos de incidente --}}
                            @if($sub->tiposIncidente->count() > 0)
                            <div style="margin-top:.4rem;display:flex;flex-wrap:wrap;gap:.3rem;">
                                @foreach($sub->tiposIncidente as $tipo)
                                <span class="tipo-chip">
                                    {{ $tipo->name }}
                                    <form method="POST" action="{{ route('admin.tipos.destroy', $tipo) }}" style="display:inline;" onsubmit="return confirm('¿Eliminar tipo?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" style="background:none;border:none;cursor:pointer;padding:0 0 0 .25rem;color:inherit;font-size:.7rem;">✕</button>
                                    </form>
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach

                        {{-- Añadir subcategoría --}}
                        <form method="POST" action="{{ route('admin.subcategorias.store', $cat) }}" class="add-sub-form">
                            @csrf
                            <input type="text" name="name" class="form-control"
                                   placeholder="Nueva subcategoría…" required>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-lg"></i> Añadir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Modal: Editar categoría --}}
<div id="modal-edit-cat" class="admin-modal">
    <div class="card" style="width:420px;margin:0;">
        <div class="card-body">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:1rem;">Editar Categoría</h3>
            <form id="form-edit-cat" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="name" id="edit-cat-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" id="edit-cat-desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:.5rem;">
                    <input type="checkbox" name="is_active" id="edit-cat-active" value="1" style="width:16px;height:16px;">
                    <label for="edit-cat-active" class="form-label" style="margin:0;">Activa</label>
                </div>
                <div style="display:flex;gap:.5rem;margin-top:1rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Guardar</button>
                    <button type="button" class="btn btn-outline" onclick="closeEditCat()" style="flex:1;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Añadir tipo de incidente --}}
<div id="modal-add-tipo" class="admin-modal">
    <div class="card" style="width:400px;margin:0;">
        <div class="card-body">
            <h3 style="font-size:1rem;font-weight:600;margin-bottom:.25rem;">Añadir Tipo de Incidente</h3>
            <p id="tipo-modal-sub" style="font-size:.85rem;color:var(--text-muted);margin-bottom:1rem;"></p>
            <form id="form-add-tipo" method="POST">
                @csrf
                <div class="form-group">
                    <label class="form-label">Nombre del tipo *</label>
                    <input type="text" name="name" class="form-control" placeholder="Ej: Activación de licencia" required>
                </div>
                <div style="display:flex;gap:.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Crear</button>
                    <button type="button" class="btn btn-outline" onclick="closeAddTipo()" style="flex:1;">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>{{-- /modal-add-tipo --}}

</div>{{-- /admin-content --}}
</div>{{-- /admin-layout --}}
@endsection

@push('styles')
<style>
.quick-link{display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;border-radius:.4rem;color:var(--text-secondary);font-size:.85rem;text-decoration:none;transition:background .15s;}
.quick-link:hover{background:var(--bg-hover);color:var(--accent);}
.quick-link i{width:16px;}
.tipo-chip{background:var(--accent-light,#ede9fe);color:var(--accent);border-radius:99px;padding:.2rem .6rem;font-size:.75rem;display:inline-flex;align-items:center;gap:.15rem;}
.category-card{transition:box-shadow .2s;}
.category-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);}
</style>
@endpush

@push('scripts')
<script>
function toggleCat(id) {
    const list = document.getElementById('sublist-' + id);
    const icon = document.getElementById('icon-cat-' + id);
    const hidden = list.style.display === 'none';
    list.style.display = hidden ? 'block' : 'none';
    icon.style.transform = hidden ? 'rotate(-180deg)' : '';
}

function openEditCat(id, name, desc, active) {
    document.getElementById('form-edit-cat').action = '/admin/categories/' + id;
    document.getElementById('edit-cat-name').value = name;
    document.getElementById('edit-cat-desc').value = desc;
    document.getElementById('edit-cat-active').checked = active;
    document.getElementById('modal-edit-cat').style.display = 'flex';
}
function closeEditCat() {
    document.getElementById('modal-edit-cat').style.display = 'none';
}

function openAddTipo(subId, subName) {
    document.getElementById('form-add-tipo').action = '/admin/subcategorias/' + subId + '/tipos';
    document.getElementById('tipo-modal-sub').textContent = 'Subcategoría: ' + subName;
    document.getElementById('modal-add-tipo').style.display = 'flex';
}
function closeAddTipo() {
    document.getElementById('modal-add-tipo').style.display = 'none';
}

// Clic fuera cierra modales
['modal-edit-cat','modal-add-tipo'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
});
</script>
@endpush
