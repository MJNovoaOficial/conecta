@extends('layouts.app')

@section('title', $articulo->title)

@section('content')
<style>
.art-wrap { max-width:760px; margin:0 auto; padding:24px 20px 48px; }
.art-migas { font-size:.8rem; color:#a0aec0; margin-bottom:14px; }
.art-migas a { color:#3498db; text-decoration:none; }
.art-migas a:hover { text-decoration:underline; }

.art-card { background:#fff; border:1px solid #e8ecf0; border-radius:12px; overflow:hidden; }
.art-head { padding:22px 24px 18px; border-bottom:1px solid #f0f2f5; }
.art-head h1 { font-size:1.3rem; font-weight:700; color:#1a2332; margin:0 0 8px; line-height:1.35; }
.art-cat { display:inline-block; padding:3px 10px; border-radius:999px; background:#ebf5fb; color:#2980b9; font-size:.74rem; font-weight:600; }

.art-cuerpo { padding:22px 24px; }
.art-pasos { font-size:.92rem; line-height:1.8; color:#2d3748; white-space:pre-wrap; }

/* Imágenes de apoyo: una por paso, a ancho completo para que se distinga
   dónde hay que tocar sin tener que ampliar. */
.art-imagenes { margin-top:26px; padding-top:22px; border-top:1px solid #e8ecf0; display:flex; flex-direction:column; gap:24px; }
.art-imagen { margin:0; }
.art-imagen img {
    width:100%; height:auto; display:block; border-radius:10px;
    border:1px solid #dbe3ed; background:#fff;
}
.art-imagen figcaption {
    margin-top:9px; font-size:.9rem; color:#4a5568; line-height:1.55;
    padding-left:12px; border-left:3px solid #3498db;
}

.art-feedback { padding:20px 24px; background:#f7f9fc; border-top:1px solid #e8ecf0; text-align:center; }
.art-feedback p { font-size:.9rem; font-weight:600; color:#4a5568; margin:0 0 14px; }
.art-botones { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; }
.art-btn { display:inline-flex; align-items:center; gap:7px; padding:11px 20px; border-radius:8px; font-size:.88rem; font-weight:600; cursor:pointer; border:none; text-decoration:none; }
.art-btn-si { background:#27ae60; color:#fff; }
.art-btn-si:hover { background:#1e8449; color:#fff; }
.art-btn-no { background:#fff; color:#4a5568; border:1.5px solid #cbd5e0; }
.art-btn-no:hover { background:#fff; border-color:#a0aec0; }
.art-nota { font-size:.76rem; color:#a0aec0; margin-top:12px; }

.art-relacionados { margin-top:26px; }
.art-relacionados h3 { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#a0aec0; margin:0 0 11px; }
.art-rel-item { display:block; background:#fff; border:1px solid #e8ecf0; border-radius:9px; padding:12px 15px; margin-bottom:8px; text-decoration:none; color:#2d3748; font-size:.88rem; transition:all .15s; }
.art-rel-item:hover { border-color:#3498db; color:#1a2332; }
</style>

<div class="art-wrap">

    <div class="art-migas">
        <a href="{{ route('ayuda.index') }}">Centro de Ayuda</a>
        @if($articulo->categoria)
            <span>›</span>
            <a href="{{ route('ayuda.index', ['categoria_id' => $articulo->categoria_id]) }}">{{ $articulo->categoria->name }}</a>
        @endif
    </div>

    <div class="art-card">

        <div class="art-head">
            <h1>{{ $articulo->title }}</h1>
            @if($articulo->categoria)
                <span class="art-cat">{{ $articulo->categoria->name }}</span>
            @endif
        </div>

        <div class="art-cuerpo">
            {{-- Se escapa siempre: es contenido escrito por una persona.
                 Los saltos de linea los conserva white-space:pre-wrap. --}}
            <div class="art-pasos">{{ $articulo->content }}</div>

            {{-- Imágenes de apoyo. Para quien no está familiarizado con la
                 tecnología, ver la pantalla explica más que leerla descrita. --}}
            @if($articulo->imagenes->isNotEmpty())
                <div class="art-imagenes">
                    @foreach($articulo->imagenes as $img)
                        <figure class="art-imagen">
                            <img src="{{ $img->url }}"
                                 alt="{{ $img->descripcion ?: 'Imagen de apoyo del instructivo' }}"
                                 loading="lazy">
                            @if($img->descripcion)
                                <figcaption>{{ $img->descripcion }}</figcaption>
                            @endif
                        </figure>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="art-feedback">
            <p>¿Esto resolvió tu problema?</p>

            <div class="art-botones">
                {{-- Si le sirvio: se registra y no se abre ningun ticket. --}}
                <form method="POST" action="{{ route('ayuda.evitado', $articulo) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="art-btn art-btn-si">
                        <i class="fas fa-check"></i> Sí, ya lo resolví
                    </button>
                </form>

                {{-- Si no le sirvio: se registra y se le lleva a abrir el ticket. --}}
                <form method="POST" action="{{ route('ayuda.util', $articulo) }}" style="margin:0;">
                    @csrf
                    <input type="hidden" name="util" value="no">
                    <button type="submit" class="art-btn art-btn-no">
                        <i class="fas fa-times"></i> No me sirvió
                    </button>
                </form>
            </div>

            <p class="art-nota">
                Si no te sirvió, lo revisaremos. Puedes
                <a href="{{ route('tickets.create') }}" style="color:#3498db;">abrir un ticket</a>
                y te ayudamos.
            </p>
        </div>

    </div>

    @if($relacionados->isNotEmpty())
        <div class="art-relacionados">
            <h3>También podría servirte</h3>
            @foreach($relacionados as $rel)
                <a href="{{ route('ayuda.show', $rel) }}" class="art-rel-item">{{ $rel->title }}</a>
            @endforeach
        </div>
    @endif

</div>
@endsection
