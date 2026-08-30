<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horario laboral para los plazos de SLA
    |--------------------------------------------------------------------------
    |
    | Los plazos de respuesta y resolución se cuentan en horas de trabajo, no en
    | horas de reloj. Sin esto, un ticket de prioridad media creado un viernes a
    | las 17:00 tenía plazo de respuesta el sábado a la 1 de la madrugada: vencía
    | antes de que alguien llegara el lunes, por bien que trabajara soporte.
    |
    | Contar solo el horario hábil hace que el porcentaje de cumplimiento mida al
    | equipo y no al calendario.
    |
    */

    'horario_laboral' => [

        // En false los plazos vuelven a contarse en horas corridas, como antes.
        'activo' => env('SLA_HORARIO_LABORAL', true),

        // Días de la semana en formato ISO: 1 es lunes y 7 es domingo.
        'dias' => [1, 2, 3, 4, 5],

        // PENDIENTE DE CONFIRMAR con la jefatura: estos son valores de partida,
        // no el horario real de la empresa. Cambiarlos altera todos los plazos
        // de los tickets nuevos.
        'inicio' => env('SLA_HORA_INICIO', '08:30'),
        'fin'    => env('SLA_HORA_FIN', '18:30'),

        // Feriados en formato Y-m-d. Un ticket que cae en uno de estos días
        // espera al siguiente día hábil, igual que un fin de semana.
        'feriados' => [
            // '2026-09-18',
            // '2026-09-19',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Aviso antes de que venza el plazo
    |--------------------------------------------------------------------------
    |
    | Cuántos minutos antes del vencimiento se avisa al agente asignado —o al
    | equipo, si el ticket no tiene dueño—.
    |
    | Conviene que sea holgado: avisar cinco minutos antes no le da tiempo a
    | nadie de hacer nada, y el aviso pasa a ser una constatación en vez de una
    | oportunidad de reaccionar.
    |
    */
    'aviso_minutos_antes' => env('SLA_AVISO_MINUTOS', 30),

    /*
    | Prioridades que se cuentan las 24 horas, sin esperar al horario laboral.
    |
    | Vacío a propósito: hoy todas las prioridades respetan el horario. Si la
    | empresa define turnos de emergencia, agregar aquí 'critical' hace que esos
    | tickets corran su plazo también de noche y en fin de semana.
    */
    'prioridades_24_7' => [],

];
