<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ejecutar cierre automático de tickets pendientes cada minuto
Schedule::job(new \App\Jobs\AutoCloseTicketJob)->everyMinute();
