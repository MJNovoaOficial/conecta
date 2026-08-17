{{--
    Sugerencias de la base de conocimiento en el formulario de ticket (RN-18).

    Mientras el usuario escribe el asunto, se consulta al servidor si hay
    artículos que coincidan. Si los hay, se muestran debajo del campo para que
    pueda resolverlo sin abrir el ticket.

    Requiere que en la página existan los elementos #modalTitleField,
    #kbSugerencias y #kbLista.

    Vive en un parcial porque el modal de "Abrir Ticket" está duplicado en
    tickets/index.blade.php y en partials/create_ticket_modal.blade.php. El
    HTML está repetido (ver nota en el PR), pero esta lógica no.
--}}
<script>
(function () {
    const campo = document.getElementById('modalTitleField');
    const caja  = document.getElementById('kbSugerencias');
    const lista = document.getElementById('kbLista');

    if (!campo || !caja || !lista) return;

    let temporizador = null;

    campo.addEventListener('input', function () {
        // Se espera a que el usuario deje de escribir 350ms antes de preguntar.
        // Sin esto se enviaria una consulta por cada tecla presionada.
        clearTimeout(temporizador);
        temporizador = setTimeout(buscar, 350);
    });

    function buscar() {
        const termino = campo.value.trim();

        if (termino.length < 4) {
            caja.style.display = 'none';
            return;
        }

        fetch('{{ route('ayuda.sugerencias') }}?q=' + encodeURIComponent(termino), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : [])
        .then(mostrar)
        .catch(() => { caja.style.display = 'none'; });
    }

    function mostrar(articulos) {
        if (!articulos.length) {
            caja.style.display = 'none';
            return;
        }

        lista.innerHTML = '';

        articulos.forEach(function (art) {
            const enlace = document.createElement('a');

            // Se abre en otra pestana a proposito: si navegara en la misma,
            // el usuario perderia todo lo que ya escribio en el formulario.
            enlace.href   = art.url;
            enlace.target = '_blank';
            enlace.rel    = 'noopener';
            enlace.style.cssText = 'display:block;padding:7px 10px;margin-bottom:5px;background:#fff;border:1px solid #d6ebfa;border-radius:6px;color:#2d3748;text-decoration:none;font-size:0.83rem;';

            // textContent y no innerHTML: el titulo viene de la base de datos
            // y asignarlo como HTML permitiria inyectar etiquetas.
            enlace.textContent = '📄 ' + art.title;

            enlace.addEventListener('mouseenter', () => enlace.style.borderColor = '#3498db');
            enlace.addEventListener('mouseleave', () => enlace.style.borderColor = '#d6ebfa');

            lista.appendChild(enlace);
        });

        const nota = document.createElement('div');
        nota.style.cssText = 'font-size:0.74rem;color:#5a86a8;margin-top:6px;';
        nota.textContent = 'Si alguno resuelve tu problema, ábrelo y marca "Sí, ya lo resolví". No hace falta enviar este ticket.';
        lista.appendChild(nota);

        caja.style.display = 'block';
    }
})();
</script>
