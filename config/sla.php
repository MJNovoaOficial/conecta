<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horario laboral para los plazos de SLA
    |--------------------------------------------------------------------------
    |
    | Apagado: la mesa de ayuda atiende los siete días con turnos, incluido el
    | nocturno, que resuelve lo que le corresponde y escala al turno de día lo
    | que no. Como hay alguien de guardia a cualquier hora, el plazo debe correr
    | a cualquier hora: contarlo en horas de reloj mide al equipo de verdad.
    |
    | Esto no vuelve atrás el trabajo del horario laboral, lo deja disponible.
    | La razón por la que antes estaba mal contar de noche era que no había
    | nadie trabajando de noche. Ahora sí lo hay, y la medida correcta cambia.
    |
    | Si algún día la empresa vuelve a atender solo en horario de oficina, basta
    | con poner SLA_HORARIO_LABORAL=true en el .env y ajustar las horas de abajo:
    | los plazos vuelven a saltarse noches, fines de semana y feriados sin tocar
    | una línea de código.
    |
    */

    'horario_laboral' => [

        // En false los plazos se cuentan en horas corridas, de lunes a domingo
        // y a cualquier hora. Es lo que corresponde con turnos 24/7.
        'activo' => env('SLA_HORARIO_LABORAL', false),

        // Días de la semana en formato ISO: 1 es lunes y 7 es domingo.
        // Los siete, porque los turnos cubren también el fin de semana.
        // Solo se usan cuando 'activo' está en true.
        'dias' => [1, 2, 3, 4, 5, 6, 7],

        // Solo se usan cuando 'activo' está en true. Con turnos las 24 horas
        // no hay hora de apertura ni de cierre que aplicar.
        'inicio' => env('SLA_HORA_INICIO', '00:00'),
        'fin'    => env('SLA_HORA_FIN', '23:59'),

        // Feriados en formato Y-m-d. Con el horario apagado no se aplican: los
        // turnos también cubren los feriados. Quedan por si se vuelve a activar.
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
    | Sin efecto mientras 'activo' esté en false, porque en ese caso ya se
    | cuentan así todas. Queda por si se vuelve a activar el horario y se
    | quiere que las críticas sigan corriendo de noche.
    */
    'prioridades_24_7' => [],

];
