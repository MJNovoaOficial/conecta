<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // La auditoría se registra manualmente con AuditLog::record()
        // en cada controller. No se necesita un subscriber de eventos.
    }
}
