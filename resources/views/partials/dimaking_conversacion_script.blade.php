@once
<script>
window.crearConversacionDimaking = function (inicial, publico) {
    let historial = (inicial || []).slice(-6);
    if (publico) window.addEventListener('pagehide', function () { historial = []; });

    return {
        vaciar: function () { historial = []; },
        datos: function (pregunta) {
            const datos = { pregunta: pregunta };
            // Invitados: solo preguntas en memoria de esta página, nunca respuestas ni fuentes del cliente.
            if (publico) datos.historial = historial.map(function (turno) {
                return { pregunta: turno.pregunta };
            });
            return datos;
        },
        registrar: function (pregunta, respuesta) {
            historial.push({ pregunta: pregunta, respuesta: respuesta.slice(0, 2000) });
            historial = historial.slice(-6);
        },
        mostrar: function (contenedor) {
            contenedor.textContent = '';
            historial.forEach(function (turno) {
                const pregunta = document.createElement('p');
                pregunta.textContent = 'Tú: ' + turno.pregunta;
                const respuesta = document.createElement('p');
                respuesta.textContent = 'Dimaking: ' + turno.respuesta;
                respuesta.style.whiteSpace = 'pre-wrap';
                contenedor.appendChild(pregunta);
                contenedor.appendChild(respuesta);
            });
            contenedor.hidden = historial.length === 0;
        },
    };
};
</script>
@endonce
