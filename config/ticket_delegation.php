<?php

return [
    // La tabla se incorpora de forma aditiva; no habilitar antes de migrar.
    // Sin esta opción, las funciones anteriores no consultan encargos.
    'enabled' => env('TICKET_DELEGATION_ENABLED', false),
];
