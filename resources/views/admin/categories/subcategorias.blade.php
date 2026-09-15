@extends('layouts.app')

@section('title', 'Subcategorías')

@section('content')
<style>
.admin-layout { display:flex; min-height:calc(100vh - 52px); }
.admin-content { flex:1; padding:28px 32px; background:#f5f7fa; min-width:0; }
.page-header { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.page-title { font-size:1.35rem; font-weight:700; color:#1a2332; margin:0; }
.page-subtitle { color:#64748b; font-size:.88rem; margin:.35rem 0 0; }
.admin-card { background:#fff; border-radius:14px; box-shadow:0 2px 12px rgba(0,0,0,.07); margin-bottom:20px; overflow:hidden; }
.admin-card-head { padding:14px 20px; background:#1a2332; color:#fff; font-size:.95rem; font-weight:600; }
.admin-card-body { padding:20px; }
.create-grid { display:grid; grid-template-columns:1.2fr 1fr 1.5fr auto; gap:10px; align-items:end; }
.sub-grid { display:grid; grid-template-columns:1.2fr 1fr 1.5fr auto; gap:10px; align-items:end; }
.field label { display:block; color:#64748b; font-size:.76rem; font-weight:600; margin-bottom:4px; }
.field input, .field select, .field textarea { width:100%; border:1px solid #dbe2ea; border-radius:7px; padding:8px 10px; font-size:.85rem; box-sizing:border-box; }
.field textarea { min-height:38px; resize:vertical; }
.sub-card { border:1px solid #e5e7eb; border-radius:10px; padding:16px; margin-bottom:12px; }
.sub-head { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-bottom:12px; }
.category-pill, .type-pill { display:inline-flex; align-items:center; border-radius:999px; font-size:.75rem; padding:3px 9px; }
.category-pill { background:#e0ecff; color:#2458a6; }
.type-pill { background:#eef2f7; color:#475569; margin:2px; }
.status { font-size:.75rem; margin-left:6px; color:#64748b; }
.type-form { display:grid; grid-template-columns:1fr 1.4fr auto; gap:8px; align-items:end; margin-top:12px; padding-top:12px; border-top:1px solid #edf0f3; }
.btn-primary, .btn-outline { border-radius:7px; padding:8px 12px; font-size:.82rem; cursor:pointer; text-decoration:none; white-space:nowrap; }
.btn-primary { background:#3b82f6; color:#fff; border:1px solid #3b82f6; }
.btn-outline { background:#fff; color:#334155; border:1px solid #cbd5e1; }
.notice { padding:12px 15px; border-radius:8px; margin-bottom:16px; font-size:.84rem; }
.notice-ok { background:#f0fdf4; color:#15803d; border:1px solid #86efac; }
.notice-error { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
@media(max-width:900px) { .create-grid, .sub-grid, .type-form { grid-template-columns:1fr; } .admin-content { padding:20px; } }
</style>

<div class="admin-layout">
    @include('layouts.admin_sidebar', ['active' => 'subcategories'])

    <div class="admin-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Subcategorías</h1>
                <p class="page-subtitle">Configura subcategorías y sus tipos de incidente dentro de cada categoría.</p>
            </div>
            <a class="btn-outline" href="{{ route('admin.priority-rules.index') }}">
                <i class="fas fa-sliders-h"></i> Reglas de prioridad
            </a>
        </div>

        @if(session('success'))
            <div class="notice notice-ok">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="notice notice-error">{{ $errors->first() }}</div>
        @endif

        <div class="admin-card">
            <div class="admin-card-head"><i class="fas fa-plus-circle"></i> Nueva subcategoría</div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.subcategorias.store-direct') }}" class="create-grid">
                    @csrf
                    <div class="field">
                        <label for="categoria_id">Categoría *</label>
                        <select id="categoria_id" name="categoria_id" required>
                            <option value="">Selecciona una categoría</option>
                            @foreach($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected(old('categoria_id') == $categoria->id)>{{ $categoria->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="name">Nombre *</label>
                        <input id="name" name="name" value="{{ old('name') }}" required maxlength="100">
                    </div>
                    <div class="field">
                        <label for="description">Descripción</label>
                        <input id="description" name="description" value="{{ old('description') }}" maxlength="500">
                    </div>
                    <button class="btn-primary" type="submit">Crear</button>
                </form>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><i class="fas fa-list"></i> Subcategorías configuradas ({{ $subcategorias->count() }})</div>
            <div class="admin-card-body">
                @forelse($subcategorias as $subcategoria)
                    <div class="sub-card">
                        <div class="sub-head">
                            <div>
                                <strong>{{ $subcategoria->name }}</strong>
                                <span class="category-pill">{{ $subcategoria->categoria->name }}</span>
                                <span class="status">{{ $subcategoria->is_active ? 'Activa' : 'Inactiva' }}</span>
                            </div>
                            <span class="status">{{ $subcategoria->tipos_incidente_count }} tipos de incidente</span>
                        </div>

                        <form method="POST" action="{{ route('admin.subcategorias.update', $subcategoria) }}" class="sub-grid">
                            @csrf @method('PUT')
                            <div class="field">
                                <label>Nombre *</label>
                                <input name="name" value="{{ $subcategoria->name }}" required maxlength="100">
                            </div>
                            <div class="field">
                                <label>Estado</label>
                                <select name="is_active">
                                    <option value="1" @selected($subcategoria->is_active)>Activa</option>
                                    <option value="0" @selected(! $subcategoria->is_active)>Inactiva</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>Descripción</label>
                                <input name="description" value="{{ $subcategoria->description }}" maxlength="500">
                            </div>
                            <button class="btn-outline" type="submit">Guardar</button>
                        </form>

                        <div style="margin-top:12px;">
                            <span class="status">Tipos de incidente:</span>
                            @forelse($subcategoria->tiposIncidente as $tipo)
                                <span class="type-pill">{{ $tipo->name }}{{ $tipo->is_active ? '' : ' · Inactivo' }}</span>
                            @empty
                                <span class="status">Aún no hay tipos configurados.</span>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('admin.tipos.store', $subcategoria) }}" class="type-form">
                            @csrf
                            <div class="field">
                                <label>Nuevo tipo de incidente *</label>
                                <input name="name" required maxlength="150" placeholder="Ej.: No imprime">
                            </div>
                            <div class="field">
                                <label>Descripción</label>
                                <input name="description" maxlength="500">
                            </div>
                            <button class="btn-primary" type="submit">Agregar tipo</button>
                        </form>
                    </div>
                @empty
                    <p class="page-subtitle">No hay subcategorías configuradas todavía.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
