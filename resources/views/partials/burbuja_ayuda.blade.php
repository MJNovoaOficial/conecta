{{--
    Burbuja de ayuda flotante.

    Aparece en todas las páginas para quien no sabe dónde buscar. La plataforma
    la usan personas mayores y gente poco habituada a sistemas, así que el botón
    es grande, el texto pide lo mínimo ("cuéntame qué te pasa") y siempre queda
    a la vista la salida de emergencia: hablar con una persona.

    No depende del servidor de modelos. Si está apagado, igual entrega el
    artículo que corresponde; si está encendido, además lo explica.
--}}
<style>
.bur-lanzador {
    position: fixed; right: 22px; bottom: 22px; z-index: 1040;
    display: inline-flex; align-items: center; gap: 10px;
    padding: 15px 22px; border: none; border-radius: 999px;
    background: #2563eb; color: #fff; cursor: pointer;
    font-size: 1.02rem; font-weight: 600; font-family: inherit;
    box-shadow: 0 6px 22px rgba(37,99,235,.38);
}
.bur-lanzador:hover  { background: #1d4ed8; }
.bur-lanzador:focus-visible { outline: 3px solid #93c5fd; outline-offset: 3px; }
.bur-lanzador i { font-size: 1.2rem; }
.bur-lanzador.oculto { display: none; }

.bur-panel {
    position: fixed; right: 22px; bottom: 22px; z-index: 1041;
    width: min(400px, calc(100vw - 32px));
    max-height: min(640px, calc(100vh - 44px));
    background: #fff; border-radius: 16px;
    box-shadow: 0 18px 50px rgba(15,23,42,.28);
    display: none; flex-direction: column; overflow: hidden;
}
.bur-panel.abierto { display: flex; }

.bur-cabecera {
    background: #2563eb; color: #fff; padding: 16px 18px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.bur-cabecera h2 { margin: 0; font-size: 1.05rem; font-weight: 700; }
.bur-cabecera p  { margin: 3px 0 0; font-size: .84rem; opacity: .92; }
.bur-cerrar {
    background: rgba(255,255,255,.18); border: none; color: #fff;
    width: 34px; height: 34px; border-radius: 50%; cursor: pointer;
    font-size: 1.05rem; flex-shrink: 0; line-height: 1;
}
.bur-cerrar:hover { background: rgba(255,255,255,.3); }
.bur-cerrar:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }

.bur-cuerpo { padding: 16px 18px; overflow-y: auto; flex: 1; }

.bur-ejemplos { font-size: .87rem; color: #475569; line-height: 1.6; margin: 0 0 14px; }
.bur-ejemplos b { color: #1e293b; }

.bur-forma { display: flex; flex-direction: column; gap: 10px; }
.bur-forma label { font-size: .9rem; font-weight: 600; color: #1e293b; }
.bur-forma textarea {
    width: 100%; min-height: 88px; resize: vertical;
    padding: 12px 14px; border: 2px solid #cbd5e1; border-radius: 10px;
    font-size: 1rem; font-family: inherit; line-height: 1.5; color: #0f172a;
}
.bur-forma textarea:focus { outline: none; border-color: #2563eb; }
.bur-enviar {
    padding: 14px 18px; border: none; border-radius: 10px;
    background: #2563eb; color: #fff; font-size: 1rem; font-weight: 700;
    cursor: pointer; font-family: inherit;
    display: inline-flex; align-items: center; justify-content: center; gap: 9px;
}
.bur-enviar:hover:not(:disabled) { background: #1d4ed8; }
.bur-enviar:disabled { opacity: .6; cursor: default; }
.bur-enviar:focus-visible { outline: 3px solid #93c5fd; outline-offset: 2px; }

.bur-resultado { margin-top: 16px; display: none; }
.bur-resultado.visible { display: block; }
.bur-espera { display: flex; align-items: center; gap: 10px; color: #475569; font-size: .92rem; padding: 8px 0; }
.bur-punto { width: 8px; height: 8px; border-radius: 50%; background: #2563eb; animation: burLatido 1.1s ease-in-out infinite; }
.bur-punto:nth-child(2) { animation-delay: .18s; }
.bur-punto:nth-child(3) { animation-delay: .36s; }
@keyframes burLatido { 0%,100% { opacity: .25; } 50% { opacity: 1; } }
@media (prefers-reduced-motion: reduce) { .bur-punto { animation: none; opacity: .6; } }

.bur-texto {
    background: #f1f5f9; border-left: 4px solid #2563eb; border-radius: 10px;
    padding: 14px 16px; font-size: .96rem; line-height: 1.62; color: #1e293b;
    white-space: pre-wrap;
}
.bur-texto.aviso { border-left-color: #d97706; background: #fffbeb; }

.bur-articulos { margin-top: 12px; display: flex; flex-direction: column; gap: 14px; }
.bur-guia {
    border: 2px solid #dbeafe; border-radius: 12px;
    background: #eff6ff; overflow: hidden;
}
.bur-guia > a {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 15px; color: #1d4ed8; text-decoration: none;
    font-size: .95rem; font-weight: 700; line-height: 1.4;
}
.bur-guia > a:hover { background: #dbeafe; color: #1e40af; }
.bur-guia > a i { flex-shrink: 0; }

/* Las capturas se muestran aquí mismo: hacer clic para verlas ya es una
   barrera para quien no está habituado a navegar. */
.bur-capturas { padding: 0 12px 12px; display: flex; flex-direction: column; gap: 12px; }
.bur-captura figure { margin: 0; }
.bur-captura img {
    width: 100%; height: auto; display: block; border-radius: 8px;
    border: 1px solid #bfdbfe; background: #fff;
}
.bur-captura figcaption {
    margin-top: 6px; font-size: .85rem; color: #334155; line-height: 1.5;
}

.bur-pie {
    border-top: 1px solid #e2e8f0; padding: 14px 18px;
    background: #f8fafc; text-align: center;
}
.bur-pie p { margin: 0 0 10px; font-size: .86rem; color: #475569; }
.bur-ticket {
    display: inline-flex; align-items: center; justify-content: center; gap: 9px;
    width: 100%; padding: 13px 18px; border-radius: 10px;
    background: #16a34a; color: #fff; font-size: .96rem; font-weight: 700;
    text-decoration: none;
}
.bur-ticket:hover { background: #15803d; color: #fff; }

@media (max-width: 480px) {
    .bur-lanzador { right: 14px; bottom: 14px; padding: 14px 18px; font-size: .96rem; }
    .bur-panel { right: 8px; left: 8px; bottom: 8px; width: auto; max-height: calc(100vh - 20px); }
}
</style>

<button type="button" class="bur-lanzador" id="burLanzador"
        aria-haspopup="dialog" aria-expanded="false" aria-controls="burPanel">
    <i class="fas fa-comments" aria-hidden="true"></i>
    ¿Necesitas ayuda?
</button>

<div class="bur-panel" id="burPanel" role="dialog" aria-modal="false" aria-labelledby="burTitulo">
    <div class="bur-cabecera">
        <div>
            <h2 id="burTitulo">¿Necesitas ayuda?</h2>
            <p>Cuéntame qué te pasa y te oriento.</p>
        </div>
        <button type="button" class="bur-cerrar" id="burCerrar" aria-label="Cerrar la ayuda">✕</button>
    </div>

    <div class="bur-cuerpo">
        <p class="bur-ejemplos">
            Escríbelo con tus palabras, como se lo contarías a un compañero.<br>
            Por ejemplo: <b>“no se ve nada en la pantalla”</b> o <b>“no puedo imprimir”</b>.
        </p>

        <form class="bur-forma" id="burForma">
            <label for="burPregunta">¿Qué problema tienes?</label>
            <textarea id="burPregunta" maxlength="500" autocomplete="off"
                      placeholder="Escribe aquí tu problema..."></textarea>
            <button type="submit" class="bur-enviar" id="burEnviar">
                <i class="fas fa-paper-plane" aria-hidden="true"></i> Buscar ayuda
            </button>
        </form>

        <div class="bur-resultado" id="burResultado" aria-live="polite">
            <div class="bur-espera" id="burEspera" style="display:none;">
                <span class="bur-punto"></span><span class="bur-punto"></span><span class="bur-punto"></span>
                <span>Buscando en las guías de soporte...</span>
            </div>
            <div class="bur-texto" id="burTexto" style="display:none;"></div>
            <div class="bur-articulos" id="burArticulos"></div>
        </div>
    </div>

    <div class="bur-pie">
        <p>¿Prefieres que te ayude una persona?</p>
        <a href="{{ route('tickets.create') }}" class="bur-ticket">
            <i class="fas fa-headset" aria-hidden="true"></i> Pedir ayuda a soporte
        </a>
    </div>
</div>

<script>
(function () {
    const lanzador  = document.getElementById('burLanzador');
    const panel     = document.getElementById('burPanel');
    const cerrar    = document.getElementById('burCerrar');
    const forma     = document.getElementById('burForma');
    const campo     = document.getElementById('burPregunta');
    const enviar    = document.getElementById('burEnviar');
    const resultado = document.getElementById('burResultado');
    const espera    = document.getElementById('burEspera');
    const texto     = document.getElementById('burTexto');
    const articulos = document.getElementById('burArticulos');

    function abrir() {
        panel.classList.add('abierto');
        lanzador.classList.add('oculto');
        lanzador.setAttribute('aria-expanded', 'true');
        campo.focus();
    }

    function ocultar() {
        panel.classList.remove('abierto');
        lanzador.classList.remove('oculto');
        lanzador.setAttribute('aria-expanded', 'false');
        lanzador.focus();
    }

    lanzador.addEventListener('click', abrir);
    cerrar.addEventListener('click', ocultar);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('abierto')) { ocultar(); }
    });

    forma.addEventListener('submit', async function (e) {
        e.preventDefault();

        const pregunta = campo.value.trim();
        if (pregunta.length < 4) {
            campo.focus();
            return;
        }

        enviar.disabled = true;
        resultado.classList.add('visible');
        espera.style.display = 'flex';
        texto.style.display  = 'none';
        articulos.textContent = '';

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

            // textContent, nunca innerHTML: lo que vuelve del servidor se
            // muestra como texto y no puede inyectar etiquetas en la página.
            texto.textContent = d.texto;
            texto.classList.toggle('aviso', d.tipo !== 'respuesta' && d.tipo !== 'solo_articulos');

            (d.fuentes || []).forEach(function (f) {
                const caja = document.createElement('div');
                caja.className = 'bur-guia';

                const a = document.createElement('a');
                a.href = f.url;
                const icono = document.createElement('i');
                icono.className = 'fas fa-book-open';
                icono.setAttribute('aria-hidden', 'true');
                a.appendChild(icono);
                a.appendChild(document.createTextNode(
                    (f.imagenes && f.imagenes.length ? 'Ver la guía con imágenes: ' : 'Ver la guía: ') + f.titulo
                ));
                caja.appendChild(a);

                // Las capturas se muestran dentro de la respuesta. Pedirle a
                // alguien que haga clic para verlas es perder justamente a
                // quien más las necesita.
                if (f.imagenes && f.imagenes.length) {
                    const cont = document.createElement('div');
                    cont.className = 'bur-capturas';

                    f.imagenes.forEach(function (im) {
                        const fig = document.createElement('figure');
                        const img = document.createElement('img');
                        img.src = im.url;
                        img.alt = im.descripcion || 'Imagen de apoyo de la guía';
                        img.loading = 'lazy';
                        fig.appendChild(img);

                        if (im.descripcion) {
                            const pie = document.createElement('figcaption');
                            pie.textContent = im.descripcion;
                            fig.appendChild(pie);
                        }

                        const envoltorio = document.createElement('div');
                        envoltorio.className = 'bur-captura';
                        envoltorio.appendChild(fig);
                        cont.appendChild(envoltorio);
                    });

                    caja.appendChild(cont);
                }

                articulos.appendChild(caja);
            });
        } catch (err) {
            texto.textContent = 'No pude buscar en este momento. Puedes pedir ayuda a soporte '
                              + 'con el botón verde de abajo.';
            texto.classList.add('aviso');
        } finally {
            espera.style.display = 'none';
            texto.style.display  = 'block';
            enviar.disabled      = false;
        }
    });
})();
</script>
