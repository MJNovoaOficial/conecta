<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierre de tickets sin respuesta del solicitante.
//
// Este archivo es el único lugar donde Laravel 12 lee la programación de
// tareas. Antes existía también app/Console/Kernel.php con dos trabajos
// programados: ese archivo quedó de una versión anterior del framework y no se
// cargaba, así que esos trabajos nunca se ejecutaron.
//
// Cada cinco minutos alcanza de sobra para un plazo que se mide en horas.
Schedule::job(new \App\Jobs\AutoCloseTicketJob)->everyFiveMinutes();

// Aviso antes de que un ticket incumpla su plazo de resolución. Avisa una sola
// vez por ticket: la marca queda en la columna sla_warned_at.
Schedule::job(new \App\Jobs\SendSlaWarningsJob)->everyFiveMinutes();
