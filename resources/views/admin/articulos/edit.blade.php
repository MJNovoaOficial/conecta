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

            {{-- enctype: el formulario ahora acepta imágenes de apoyo --}}
            <form method="POST" action="{{ route('admin.articulos.update', $articulo) }}" enctype="multipart/form-data">
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

                {{-- Imágenes de apoyo.
                     Los manuales que circulan por correo son pasos con una
                     captura en cada uno. Esto reproduce ese formato dentro de
                     la plataforma, donde sí se puede buscar y actualizar. --}}
                <div class="kb-campo" style="margin-top:1.6rem;padding-top:1.4rem;border-top:1px solid #e8ecf0;">
                    <label>Imágenes de apoyo</label>
                    <span class="ayuda" style="margin-bottom:12px;display:block;">
                        Una captura por paso. Se muestran en el orden que indiques, debajo de las instrucciones.
                    </span>

                    @if($articulo->imagenes->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
                            @foreach($articulo->imagenes as $img)
                                <div style="display:flex;gap:14px;align-items:flex-start;background:#f7f9fc;border:1px solid #e2e8f0;border-radius:9px;padding:12px;">
                                    <img src="{{ $img->url }}" alt="{{ $img->descripcion ?? 'Imagen del artículo' }}"
                                         style="width:110px;height:82px;object-fit:cover;border-radius:6px;border:1px solid #cbd5e0;flex-shrink:0;background:#fff;">

                                    <div style="flex:1;min-width:180px;display:flex;flex-direction:column;gap:8px;">
                                        <input type="text"
                                               name="imagen_descripcion[{{ $img->id }}]"
                                               value="{{ $img->descripcion }}"
                                               maxlength="300"
                                               placeholder="Qué muestra esta imagen (ej: pantalla de ajustes del celular)"
                                               style="width:100%;padding:8px 11px;border:1.5px solid #cbd5e0;border-radius:7px;font-size:.86rem;">

                                        <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                                            <label style="font-size:.8rem;color:#4a5568;display:flex;align-items:center;gap:6px;margin:0;">
                                                Paso
                                                <input type="number" name="imagen_orden[{{ $img->id }}]"
                                                       value="{{ $img->orden }}" min="0" max="999"
                                                       style="width:66px;padding:5px 8px;border:1.5px solid #cbd5e0;border-radius:6px;font-size:.84rem;">
                                            </label>
                                            <label style="font-size:.8rem;color:#c53030;display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;">
                                                <input type="checkbox" name="eliminar_imagen[]" value="{{ $img->id }}">
                                                Eliminar esta imagen
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <input type="file" name="imagenes[]" multiple accept="image/*"
                           style="width:100%;padding:10px;border:1.5px dashed #cbd5e0;border-radius:8px;background:#fff;font-size:.86rem;">
                    <span class="ayuda">Puedes seleccionar varias a la vez. Máximo 4 MB cada una.</span>
                    @error('imagenes.*') <span class="kb-error">{{ $message }}</span> @enderror
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
