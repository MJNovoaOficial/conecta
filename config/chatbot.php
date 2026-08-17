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
    | Relevancia mínima para molestar al modelo.
    |
    | La búsqueda puntúa cada artículo (título vale 3, síntomas 2, cuerpo 1) y
    | ese puntaje se divide por la cantidad de palabras buscadas. Por debajo de
    | este valor la consulta no tiene que ver con la base y se responde de
    | inmediato ofreciendo abrir un ticket, sin gastar una llamada al modelo.
    |
    | Medido: las consultas que la base sí cubre dieron entre 2.0 y 5.5; las
    | ajenas (vacaciones, arriendos, sistemas que no administra soporte),
    | entre 0.25 y 1.5.
    */
    'umbral_relevancia' => env('CHATBOT_UMBRAL', 1.8),

    // Temperatura baja: interesa que repita el manual, no que sea creativo.
    'temperatura' => 0.2,

];
