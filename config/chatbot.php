<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Asistente de la base de conocimiento
    |--------------------------------------------------------------------------
    |
    | El asistente responde consultas usando ÚNICAMENTE los artículos de la base
    | de conocimiento. No consulta ningún servicio externo: el modelo corre en un
    | servidor de la empresa mediante Ollama, así que ninguna consulta de un
    | trabajador sale de la red interna.
    |
    */

    // Con esto en false la plataforma funciona igual, solo sin el asistente.
    // Es lo que permite desplegar sin tener todavía el servidor de Ollama.
    'enabled' => env('CHATBOT_ENABLED', false),

    'url'   => env('CHATBOT_URL', 'http://127.0.0.1:11434'),
    'model' => env('CHATBOT_MODEL', 'qwen2.5:7b'),

    // Segundos antes de rendirse. Un modelo de 7B en servidor sin tarjeta de
    // video puede tardar 15-20 s; con tarjeta baja a 2-3 s.
    'timeout' => env('CHATBOT_TIMEOUT', 60),

    /*
    | Cuánto rato Ollama mantiene el modelo cargado en memoria tras la última
    | consulta.
    |
    | Por defecto lo descarga a los 5 minutos, y volver a cargarlo desde disco
    | cuesta unos 15 segundos que paga entero quien pregunta después de una
    | pausa. Con media hora, durante la jornada el modelo queda residente y
    | solo el primero del día espera la carga.
    |
    | El costo es la memoria: el modelo ocupa unos 5 GB mientras está cargado.
    | Si el servidor los necesita para otra cosa, bajar este valor.
    */
    'keep_alive' => env('CHATBOT_KEEP_ALIVE', '30m'),

    /*
    | Cuántos artículos se le entregan al modelo como contexto.
    |
    | Medido sobre la base real: con uno solo, el modelo se niega a responder
    | las consultas de seguridad (interpreta "me piden la contraseña" como tema
    | peligroso). Con dos, responde bien. No conviene subirlo más: mientras más
    | artículos, más riesgo de que mezcle instrucciones de uno en la respuesta
    | del otro.
    */
    'articulos_contexto' => 2,

    /*
    | Relevancia mínima para mostrar artículos como sugerencia.
    |
    | Más bajo que el umbral del modelo a propósito. Equivocarse en esta
    | dirección es barato: la persona ve el título del artículo y decide sola si
    | le sirve. Equivocarse dejando que el modelo explique un artículo que no
    | viene al caso es caro, porque lo explica con seguridad.
    |
    | Medido sobre 35 consultas escritas como las escribiría una persona —no
    | copiadas de los síntomas, que ya están pensados para calzar— y 8
    | preguntas ajenas a soporte:
    |
    |   consultas válidas   de 1.67 a 6.0    pasan las 35
    |   preguntas ajenas    de 0 a 3.0       pasan 3 de 8
    |
    | Las tres ajenas que pasan son coincidencias literales: "licencia médica"
    | con la licencia de Office, "reunión mañana" con el audio de las reuniones
    | de Teams, "el sistema SAP" con los sistemas que no cargan. Para la
    | búsqueda son la misma palabra. Ningún umbral las separa sin dejar fuera
    | consultas válidas: se probó además exigir que calzara una proporción
    | mínima de las palabras de la consulta, y las coberturas de los dos grupos
    | se superponen por completo ("word se quedó pegado", válida, calza en un
    | tercio de sus palabras, igual que "el sistema SAP").
    */
    'umbral_articulos' => env('CHATBOT_UMBRAL_ARTICULOS', 0.9),

    /*
    | Relevancia mínima para que el modelo explique el artículo.
    |
    | Por debajo de este valor el artículo igual se muestra, pero como enlace:
    | la persona lee el título y decide. Solo con una coincidencia fuerte se le
    | pide al modelo que lo explique.
    |
    | Sobre el límite de esta medición, que conviene tener presente: la
    | puntuación es por palabras, así que no distingue "cómo configuro el
    | sistema SAP" de un artículo sobre sistemas que no cargan —para la
    | búsqueda ambos son "sistema"—. Esa consulta ajena puntúa 2.0, más que
    | varias consultas legítimas. No hay un valor que separe los dos grupos
    | limpiamente.
    |
    | Y hay un caso que el umbral no alcanza a frenar: "necesito una licencia
    | médica" puntúa 3.0 contra "Office pide activar la licencia", y el modelo
    | lo explica. Es el único de las 8 preguntas ajenas medidas. Separarlo
    | exige entender el significado de la frase, no contar palabras.
    |
    | Por eso el umbral quedó alto: prefiere dejar sin explicación una consulta
    | válida (que igual recibe el artículo) antes que explicar con seguridad un
    | artículo que no venía al caso. Y por eso la respuesta siempre muestra de
    | qué artículo salió.
    */
    'umbral_relevancia' => env('CHATBOT_UMBRAL', 2.5),

    /*
    | Lo mismo, pero para el asistente de la pantalla de login.
    |
    | Más bajo, y no por descuido. Con sesión iniciada, quedarse corto es
    | barato: el artículo se muestra como enlace y la persona entra a leerlo.
    | Antes de iniciar sesión no hay enlace posible —la ruta del artículo exige
    | sesión—, así que quedarse corto deja a la vista un título suelto y nada
    | más. La persona que no puede entrar se queda igual de trabada.
    |
    | El riesgo de bajarlo también es menor ahí: la búsqueda pública solo
    | alcanza los artículos marcados como públicos, que son un puñado y todos
    | del mismo tema. Lo peor que puede pasar es que explique el artículo de
    | contraseñas a alguien que preguntó otra cosa, y la respuesta siempre dice
    | de qué artículo salió.
    |
    | Medido sobre frases naturales de la pantalla de login, con la búsqueda
    | por raíz de las palabras ("recupero" calza con "recuperar"):
    |
    |   "olvide mi contrasena"                    6.00
    |   "cuenta bloqueada por intentos fallidos"  5.25
    |   "como recupero mi contrasena olvidada"    4.67
    |   "olvide mi contrasena, como la recupero"  3.33
    |   "no me acepta la contrasena al entrar"    2.00
    |   "no puedo iniciar sesion"                 1.00
    |
    | En 1.5 pasan las cinco primeras y queda fuera la última, que es
    | efectivamente vaga. Con el umbral general de 2.5 quedaría fuera también
    | "no me acepta la contraseña al entrar", que es justo lo que escribe quien
    | está apurado y molesto.
    */
    'umbral_relevancia_publico' => env('CHATBOT_UMBRAL_PUBLICO', 1.5),

    // Temperatura baja: interesa que repita el manual, no que sea creativo.
    'temperatura' => 0.2,

];
