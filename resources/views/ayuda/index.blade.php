@extends('layouts.app')

@section('title', 'Centro de Ayuda')

@section('content')
<style>
.ayuda-wrap { max-width:820px; margin:0 auto; padding:28px 20px 48px; }
.ayuda-hero { text-align:center; margin-bottom:26px; }
.ayuda-hero h1 { font-size:1.5rem; font-weight:700; color:#1a2332; margin:0 0 6px; }
.ayuda-hero p { color:#718096; font-size:.9rem; margin:0; }

.ayuda-buscador { display:flex; gap:9px; flex-wrap:wrap; margin-bottom:22px; }
.ayuda-buscador .campo { flex:1; min-width:220px; position:relative; }
.ayuda-buscador input {
    width:100%; padding:12px 14px 12px 40px; border:1.5px solid #e2e8f0;
    border-radius:9px; font-size:.92rem; outline:none; background:#fff;
}
.ayuda-buscador input:focus { border-color:#3498db; box-shadow:0 0 0 3px rgba(52,152,219,.1); }
.ayuda-buscador .lupa { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#a0aec0; }
.ayuda-buscador select { padding:12px 12px; border:1.5px solid #e2e8f0; border-radius:9px; font-size:.88rem; background:#fff; outline:none; }
.ayuda-buscador button {
    padding:12px 22px; background:linear-gradient(135deg,#2980b9,#3498db); color:#fff;
    border:none; border-radius:9px; font-size:.9rem; font-weight:600; cursor:pointer;
}

.ayuda-item {
    display:block; background:#fff; border:1px solid #e8ecf0; border-radius:10px;
    padding:16px 18px; margin-bottom:11px; text-decoration:none; transition:all .15s;
}
.ayuda-item:hover { border-color:#3498db; box-shadow:0 3px 12px rgba(52,152,219,.12); transform:translateY(-1px); }
.ayuda-item h2 { font-size:1rem; font-weight:600; color:#1a2332; margin:0 0 5px; }
.ayuda-item .sintomas { font-size:.82rem; color:#718096; line-height:1.5; margin:0; }
.ayuda-item .pie { display:flex; gap:12px; align-items:center; margin-top:9px; font-size:.74rem; color:#a0aec0; flex-wrap:wrap; }
.ayuda-cat { display:inline-block; padding:2px 9px; border-radius:999px; background:#ebf5fb; color:#2980b9; font-size:.72rem; font-weight:600; }
.ayuda-util { color:#166534; font-weight:600; }

.ayuda-vacio { text-align:center; padding:44px 20px; background:#fff; border:1px solid #e8ecf0; border-radius:10px; }
.ayuda-vacio p { color:#718096; font-size:.9rem; margin:0 0 16px; }
.ayuda-btn-ticket {
    display:inline-flex; align-items:center; gap:7px; padding:10px 20px;
    background:#27ae60; color:#fff; border-radius:8px; font-size:.88rem;
    font-weight:600; text-decoration:none;
}
.ayuda-btn-ticket:hover { background:#1e8449; color:#fff; }

/* ── Asistente ─────────────────────────────────────────────────── */
.asis {
    background:#fff; border:1px solid #e8ecf0; border-radius:12px;
    padding:18px 20px; margin-bottom:22px;
}
.asis-titulo {
    display:flex; align-items:center; gap:9px; margin:0 0 4px;
    font-size:.95rem; font-weight:700; color:#1a2332;
}
.asis-titulo i { color:#2980b9; }
.asis-nota { font-size:.78rem; color:#a0aec0; margin:0 0 13px; }
.asis-fila { display:flex; gap:9px; flex-wrap:wrap; }
.asis-fila input {
    flex:1; min-width:200px; padding:11px 14px; border:1.5px solid #e2e8f0;
    border-radius:9px; font-size:.9rem; outline:none;
}
.asis-fila input:focus { border-color:#2980b9; box-shadow:0 0 0 3px rgba(41,128,185,.1); }
.asis-fila button {
    padding:11px 20px; background:linear-gradient(135deg,#2980b9,#3498db);
    color:#fff; border:none; border-radius:9px; font-size:.88rem;
    font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:7px;
}
.asis-fila button:disabled { opacity:.55; cursor:default; }
.asis-caja { margin-top:14px; display:none; }
.asis-caja.visible { display:block; }
.asis-texto {
    background:#f7fafc; border-left:3px solid #2980b9; border-radius:8px;
    padding:14px 16px; font-size:.89rem; line-height:1.65; color:#2d3748;
    white-space:pre-wrap;
}
.asis-texto.aviso { border-left-color:#e0a83c; background:#fffbf2; }
.asis-fuentes { margin-top:10px; font-size:.8rem; color:#718096; }
.asis-fuentes a { color:#2980b9; text-decoration:none; font-weight:600; }
.asis-fuentes a:hover { text-decoration:underline; }
.asis-cargando { display:flex; align-items:center; gap:9px; color:#718096; font-size:.86rem; padding:12px 2px; }
.asis-punto {
    width:7px; height:7px; border-radius:50%; background:#2980b9;
    animation:asisLatido 1.1s ease-in-out infinite;
}
.asis-punto:nth-child(2) { animation-delay:.18s; }
.asis-punto:nth-child(3) { animation-delay:.36s; }
@keyframes asisLatido { 0%,100% { opacity:.25; } 50% { opacity:1; } }
@media (prefers-reduced-motion: reduce) { .asis-punto { animation:none; opacity:.6; } }
</style>

<div class="ayuda-wrap">

    <div class="ayuda-hero">
        <h1>Centro de Ayuda</h1>
        <p>Busca tu problema. Muchos se resuelven en un par de pasos, sin esperar a soporte.</p>
    </div>

    @if(config('chatbot.enabled'))
        {{-- Asistente sobre la base de conocimiento. Si el servidor de modelos
             no responde, el bloque avisa y el buscador de abajo sigue igual. --}}
        <div class="asis">
            <h2 class="asis-titulo">
                <i class="fas fa-comment-dots"></i> Cuéntame tu problema
            </h2>
            <p class="asis-nota">
                Te respondo con lo que dicen los artículos de soporte. Si no está ahí, te lo digo.
            </p>

            <form class="asis-fila" id="asisForm">
                <input type="text" id="asisPregunta" maxlength="500" autocomplete="off"
                       placeholder="Ej: me llegó un correo raro pidiendo mi contraseña">
                <button type="submit" id="asisBoton">
                    <i class="fas fa-paper-plane"></i> Preguntar
                </button>
            </form>

            <div class="asis-caja" id="asisCaja">
                <div class="asis-cargando" id="asisCargando" style="display:none;">
                    <span class="asis-punto"></span><span class="asis-punto"></span><span class="asis-punto"></span>
                    <span>Revisando los artículos de soporte...</span>
                </div>
                <div class="asis-texto" id="asisTexto" style="display:none;"></div>
                <div class="asis-fuentes" id="asisFuentes"></div>
            </div>
        </div>
    @endif

    <form method="GET" action="{{ route('ayuda.index') }}" class="ayuda-buscador">
        <div class="campo">
            <i class="fas fa-search lupa"></i>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="Describe tu problema: no se ve la pantalla, no imprime..."
                   autofocus>
        </div>
        {{-- Filtra al elegir, sin tener que presionar Buscar: es lo que la
             gente espera de un desplegable de filtro. --}}
        <select name="categoria_id" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            @foreach($categorias as $cat)
                <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->name }}
                </option>
            @endforeach
        </select>
        <button type="submit">Buscar</button>
    </form>

    @if($articulos->isEmpty())

        <div class="ayuda-vacio">
            @if(request()->filled('q'))
                <p>
                    No encontramos nada para <strong>"{{ request('q') }}"</strong>.<br>
                    Prueba con otras palabras, o cuéntanos tu problema y te ayudamos.
                </p>
            @else
                <p>Todavía no hay artículos publicados.</p>
            @endif
            <a href="{{ route('tickets.create') }}" class="ayuda-btn-ticket">
                <i class="fas fa-plus-circle"></i> Abrir un ticket de soporte
            </a>
        </div>

    @else

        @if(request()->filled('q'))
            <p style="font-size:.84rem;color:#718096;margin:0 0 12px;">
                {{ $articulos->total() }} resultado{{ $articulos->total() != 1 ? 's' : '' }}
                para <strong>"{{ request('q') }}"</strong>
            </p>
        @endif

        @foreach($articulos as $articulo)
            <a href="{{ route('ayuda.show', $articulo) }}" class="ayuda-item">
                <h2>{{ $articulo->title }}</h2>
                @if($articulo->symptoms)
                    <p class="sintomas">{{ Str::limit($articulo->symptoms, 100) }}</p>
                @endif
                <div class="pie">
                    @if($articulo->categoria)
                        <span class="ayuda-cat">{{ $articulo->categoria->name }}</span>
                    @endif
                    @if($articulo->getUtilidad() !== null)
                        <span class="ayuda-util">{{ $articulo->getUtilidad() }}% lo encontró útil</span>
                    @endif
                    @if($articulo->tickets_avoided > 0)
                        <span>Resolvió el problema a {{ $articulo->tickets_avoided }} persona{{ $articulo->tickets_avoided != 1 ? 's' : '' }}</span>
                    @endif
                </div>
            </a>
        @endforeach

        <div style="margin-top:18px;">
            {{ $articulos->links() }}
        </div>

        <div style="text-align:center;margin-top:26px;padding-top:22px;border-top:1px solid #e8ecf0;">
            <p style="font-size:.86rem;color:#718096;margin:0 0 13px;">¿No encontraste lo que buscabas?</p>
            <a href="{{ route('tickets.create') }}" class="ayuda-btn-ticket">
                <i class="fas fa-plus-circle"></i> Abrir un ticket de soporte
            </a>
        </div>

    @endif

</div>

@if(config('chatbot.enabled'))
<script>
(function () {
    const form     = document.getElementById('asisForm');
    const input    = document.getElementById('asisPregunta');
    const boton    = document.getElementById('asisBoton');
    const caja     = document.getElementById('asisCaja');
    const cargando = document.getElementById('asisCargando');
    const texto    = document.getElementById('asisTexto');
    const fuentes  = document.getElementById('asisFuentes');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const pregunta = input.value.trim();
        if (pregunta.length < 4) { return; }

        boton.disabled  = true;
        caja.classList.add('visible');
        cargando.style.display = 'flex';
        texto.style.display    = 'none';
        fuentes.textContent    = '';

        try {
            const r = await fetch('{{ route('ayuda.asistente') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ pregunta: pregunta }),
            });

            if (!r.ok) { throw new Error('respuesta ' + r.status); }
            const d = await r.json();

            // textContent, nunca innerHTML: lo que devuelve el modelo se muestra
            // como texto plano y no puede inyectar etiquetas en la página.
            texto.textContent = d.texto;
            texto.classList.toggle('aviso', d.tipo !== 'respuesta');

            if (d.fuentes && d.fuentes.length) {
                fuentes.appendChild(document.createTextNode(
                    d.tipo === 'respuesta' ? 'Fuente: ' : 'Artículos relacionados: '
                ));
                d.fuentes.forEach(function (f, i) {
                    if (i > 0) { fuentes.appendChild(document.createTextNode(' · ')); }
                    const a = document.createElement('a');
                    a.href = f.url;
                    a.textContent = f.titulo;
                    fuentes.appendChild(a);
                });
            }
        } catch (err) {
            texto.textContent = 'No se pudo consultar al asistente. Usa el buscador de abajo '
                              + 'o abre un ticket y soporte te ayuda.';
            texto.classList.add('aviso');
        } finally {
            cargando.style.display = 'none';
            texto.style.display    = 'block';
            boton.disabled         = false;
        }
    });
})();
</script>
@endif
@endsection
