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
    | Medido sobre frases naturales, del estilo que invita la burbuja de ayuda:
    | las consultas que la base cubre dieron entre 1.0 y 5.5, y las ajenas entre
    | 0.33 y 0.75.
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
    | Por eso el umbral quedó alto: prefiere dejar sin explicación una consulta
    | válida (que igual recibe el artículo) antes que explicar con seguridad un
    | artículo que no venía al caso. Y por eso la respuesta siempre muestra de
    | qué artículo salió.
    */
    'umbral_relevancia' => env('CHATBOT_UMBRAL', 2.5),

    // Temperatura baja: interesa que repita el manual, no que sea creativo.
    'temperatura' => 0.2,

];
