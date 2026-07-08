<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Configurar el idioma por defecto
        $this->loadTranslationsFrom(resource_path('lang'), 'app');
    }
}
