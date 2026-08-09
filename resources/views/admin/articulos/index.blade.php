@extends('layouts.app')
@section('title', 'Base de Conocimiento')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 60px); }
.admin-content { flex:1; padding:24px 28px 48px; background:#f5f7fa; min-width:0; overflow-x:hidden; }
.kb-grid { display:grid; grid-template-columns:360px 1fr; gap:1.5rem; align-items:start; }
.kb-campo { margin-bottom:.9rem; }
.kb-campo label { display:block; font-size:.78rem; font-weight:600; color:#4a5568; margin-bottom:4px; }
.kb-campo input, .kb-campo select, .kb-campo textarea {
    width:100%; padding:8px 11px; border:1.5px solid #e2e8f0; border-radius:7px;
    font-size:.84rem; font-family:inherit; color:#2d3748; outline:none;
}
.kb-campo input:focus, .kb-campo select:focus, .kb-campo textarea:focus { border-color:#3498db; }
.kb-campo .ayuda { font-size:.72rem; color:#a0aec0; margin-top:3px; display:block; }
.kb-error { color:#e74c3c; font-size:.75rem; margin-top:3px; display:block; }
.kb-tabla { width:100%; border-collapse:collapse; }
.kb-tabla th { padding:9px 12px; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:#718096; background:#f7f9fc; text-align:left; border-bottom:2px solid #e2e8f0; }
.kb-tabla td { padding:11px 12px; font-size:.83rem; border-bottom:1px solid #f0f2f5; vertical-align:top; }
.kb-pill { display:inline-block; padding:2px 9px; border-radius:999px; font-size:.7rem; font-weight:700; }
.kb-pill-on { background:#d1fae5; color:#065f46; }
.kb-pill-off { background:#f1f5f9; color:#64748b; }
.kb-mini { font-size:.74rem; color:#a0aec0; }
.kb-acciones { display:flex; gap:6px; }
.kb-btn { padding:5px 11px; border-radius:6px; font-size:.75rem; font-weight:600; text-decoration:none; border:none; cursor:pointer; }
.kb-btn-edit { background:#fff; color:#4a5568; border:1px solid #e2e8f0; }
.kb-btn-edit:hover { background:#f7f9fc; color:#2d3748; }
.kb-btn-off { background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; }
.kb-btn-on  { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
@media (max-width:900px) { .kb-grid { grid-template-columns:1fr; } }
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'articulos'])
<div class="admin-content">

    <div class="page-header" style="margin-bottom:20px;">
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <span>Base de Conocimiento</span>
        </div>
        <h1 class="page-title">Base de Conocimiento</h1>
        <p class="page-subtitle">Instructivos de autoayuda para que los usuarios resuelvan problemas frecuentes sin abrir un ticket (RN-18)</p>
    </div>

    <div class="kb-grid">

        {{-- Crear artículo --}}
        <div>
            <div class="card" style="margin-bottom:0;">
                <div class="card-body">
                    <h3 style="font-size:.95rem;font-weight:600;margin-bottom:1rem;">Nuevo artículo</h3>

                    <form method="POST" action="{{ route('admin.articulos.store') }}">
                        @csrf

                        <div class="kb-campo">
                            <label for="title">Título *</label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}"
                                   placeholder="El monitor no enciende">
                            <span class="ayuda">Redáctalo como lo diría el usuario, no en lenguaje técnico.</span>
                            @error('title') <span class="kb-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="kb-campo">
                            <label for="symptoms">Síntomas</label>
                            <textarea id="symptoms" name="symptoms" rows="2"
                                      placeholder="pantalla negra, sin señal, no prende">{{ old('symptoms') }}</textarea>
                            <span class="ayuda">Palabras con las que la gente buscaría esto. Es lo que hace que el buscador lo encuentre.</span>
                            @error('symptoms') <span class="kb-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="kb-campo">
                            <label for="categoria_id">Categoría</label>
                            <select id="categoria_id" name="categoria_id">
                                <option value="">Sin categoría</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ old('categoria_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('categoria_id') <span class="kb-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="kb-campo">
                            <label for="content">Pasos a seguir *</label>
                            <textarea id="content" name="content" rows="8"
                                      placeholder="1. Revisa que el cable esté conectado...">{{ old('content') }}</textarea>
                            <span class="ayuda">Numera los pasos. Termina indicando qué hacer si nada funcionó.</span>
                            @error('content') <span class="kb-error">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="btn-submit-ticket" style="width:100%;">
                            <i class="fas fa-plus me-1"></i> Crear artículo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Listado --}}
        <div>
            <div class="card" style="margin-bottom:0;">
                <div class="card-body" style="padding:0;">

                    <form method="GET" action="{{ route('admin.articulos.index') }}"
                          style="display:flex;gap:9px;flex-wrap:wrap;align-items:end;padding:14px 16px;background:#f7f9fc;border-bottom:1px solid #e8ecf0;">
                        <div style="flex:1;min-width:180px;">
                            <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Buscar</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="título, síntoma o contenido..."
                                   style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                        </div>
                        <div style="min-width:150px;">
                            <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Categoría</label>
                            <select name="categoria_id" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                                <option value="">Todas</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="min-width:120px;">
                            <label style="font-size:.72rem;font-weight:600;color:#718096;display:block;margin-bottom:3px;">Estado</label>
                            <select name="estado" style="width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.83rem;">
                                <option value="">Todos</option>
                                <option value="activos"   {{ request('estado')=='activos'   ? 'selected':'' }}>Activos</option>
                                <option value="inactivos" {{ request('estado')=='inactivos' ? 'selected':'' }}>Inactivos</option>
                            </select>
                        </div>
                        <button type="submit" style="padding:7px 16px;background:#3498db;color:#fff;border:none;border-radius:6px;font-size:.83rem;font-weight:600;cursor:pointer;">
                            Filtrar
                        </button>
                        @if(request()->hasAny(['q','categoria_id','estado']))
                            <a href="{{ route('admin.articulos.index') }}" style="padding:7px 12px;background:#e2e8f0;color:#4a5568;border-radius:6px;font-size:.83rem;text-decoration:none;">Limpiar</a>
                        @endif
                        <span style="margin-left:auto;font-size:.78rem;color:#a0aec0;align-self:center;">
                            {{ $articulos->total() }} artículo{{ $articulos->total() != 1 ? 's' : '' }}
                        </span>
                    </form>

                    @if($articulos->isEmpty())
                        <p style="padding:2.5rem 1rem;text-align:center;color:#a0aec0;font-size:.86rem;">
                            No hay artículos que coincidan.
                        </p>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="kb-tabla">
                                <thead>
                                    <tr>
                                        <th>Artículo</th>
                                        <th>Categoría</th>
                                        <th>Uso</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($articulos as $articulo)
                                    <tr>
                                        <td>
                                            <div style="font-weight:600;color:#1a2332;">{{ $articulo->title }}</div>
                                            @if($articulo->symptoms)
                                                <div class="kb-mini">{{ Str::limit($articulo->symptoms, 70) }}</div>
                                            @endif
                                        </td>
                                        <td class="kb-mini">{{ $articulo->categoria->name ?? '—' }}</td>
                                        <td class="kb-mini">
                                            {{ $articulo->views }} vista{{ $articulo->views != 1 ? 's' : '' }}
                                            @if($articulo->getUtilidad() !== null)
                                                <br>{{ $articulo->getUtilidad() }}% útil
                                            @endif
                                        </td>
                                        <td>
                                            <span class="kb-pill {{ $articulo->is_active ? 'kb-pill-on' : 'kb-pill-off' }}">
                                                {{ $articulo->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="kb-acciones">
                                                <a href="{{ route('admin.articulos.edit', $articulo) }}" class="kb-btn kb-btn-edit">Editar</a>
                                                <form method="POST" action="{{ route('admin.articulos.toggle', $articulo) }}" style="margin:0;">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="kb-btn {{ $articulo->is_active ? 'kb-btn-off' : 'kb-btn-on' }}">
                                                        {{ $articulo->is_active ? 'Desactivar' : 'Activar' }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div style="padding:12px 16px;">
                            {{ $articulos->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>

</div>
</div>
@endsection
