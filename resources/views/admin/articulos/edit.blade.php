@extends('layouts.app')
@section('title', 'Editar artículo')

@section('content')
<style>
.admin-layout { display:flex; gap:0; min-height:calc(100vh - 60px); }
.admin-content { flex:1; padding:24px 28px 48px; background:#f5f7fa; min-width:0; overflow-x:hidden; }
.kb-campo { margin-bottom:1rem; }
.kb-campo label { display:block; font-size:.78rem; font-weight:600; color:#4a5568; margin-bottom:4px; }
.kb-campo input, .kb-campo select, .kb-campo textarea {
    width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px;
    font-size:.86rem; font-family:inherit; color:#2d3748; outline:none;
}
.kb-campo input:focus, .kb-campo select:focus, .kb-campo textarea:focus { border-color:#3498db; }
.kb-campo .ayuda { font-size:.72rem; color:#a0aec0; margin-top:3px; display:block; }
.kb-error { color:#e74c3c; font-size:.75rem; margin-top:3px; display:block; }
.kb-fila { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
@media (max-width:700px) { .kb-fila { grid-template-columns:1fr; } }
</style>

<div class="admin-layout">
@include('layouts.admin_sidebar', ['active' => 'articulos'])
<div class="admin-content">

    <div class="page-header" style="margin-bottom:20px;">
        <div class="breadcrumb-nav">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span class="breadcrumb-sep">›</span>
            <a href="{{ route('admin.articulos.index') }}">Base de Conocimiento</a>
            <span class="breadcrumb-sep">›</span>
            <span>Editar</span>
        </div>
        <h1 class="page-title">Editar artículo</h1>
        <p class="page-subtitle">
            Creado por {{ $articulo->autor->name ?? 'desconocido' }} ·
            {{ $articulo->views }} vista{{ $articulo->views != 1 ? 's' : '' }}
            @if($articulo->getUtilidad() !== null)
                · {{ $articulo->getUtilidad() }}% lo marcó como útil
            @endif
        </p>
    </div>

    <div class="card" style="max-width:820px;">
        <div class="card-body">

            <form method="POST" action="{{ route('admin.articulos.update', $articulo) }}">
                @csrf
                @method('PUT')

                <div class="kb-campo">
                    <label for="title">Título *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $articulo->title) }}">
                    <span class="ayuda">Redáctalo como lo diría el usuario, no en lenguaje técnico.</span>
                    @error('title') <span class="kb-error">{{ $message }}</span> @enderror
                </div>

                <div class="kb-campo">
                    <label for="symptoms">Síntomas</label>
                    <textarea id="symptoms" name="symptoms" rows="2">{{ old('symptoms', $articulo->symptoms) }}</textarea>
                    <span class="ayuda">Palabras con las que la gente buscaría esto, separadas por comas.</span>
                    @error('symptoms') <span class="kb-error">{{ $message }}</span> @enderror
                </div>

                <div class="kb-fila">
                    <div class="kb-campo">
                        <label for="categoria_id">Categoría</label>
                        <select id="categoria_id" name="categoria_id">
                            <option value="">Sin categoría</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}" {{ old('categoria_id', $articulo->categoria_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('categoria_id') <span class="kb-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="kb-campo">
                        <label for="subcategoria_id">Subcategoría</label>
                        <select id="subcategoria_id" name="subcategoria_id">
                            <option value="">Sin subcategoría</option>
                            @foreach($subcategorias as $sub)
                                <option value="{{ $sub->id }}" {{ old('subcategoria_id', $articulo->subcategoria_id) == $sub->id ? 'selected' : '' }}>
                                    {{ $sub->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subcategoria_id') <span class="kb-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="kb-campo">
                    <label for="content">Pasos a seguir *</label>
                    <textarea id="content" name="content" rows="14">{{ old('content', $articulo->content) }}</textarea>
                    <span class="ayuda">Numera los pasos. Termina indicando qué hacer si nada funcionó.</span>
                    @error('content') <span class="kb-error">{{ $message }}</span> @enderror
                </div>

                <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:1.2rem;">
                    <button type="submit" class="btn-submit-ticket" style="width:auto;">Guardar cambios</button>
                    <a href="{{ route('admin.articulos.index') }}"
                       style="padding:10px 20px;background:#fff;color:#4a5568;border:1.5px solid #cbd5e0;border-radius:7px;font-size:.9rem;font-weight:600;text-decoration:none;">
                        Cancelar
                    </a>
                </div>
            </form>

        </div>
    </div>

</div>
</div>
@endsection
