<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Registrar policies manualmente
        Gate::policy(Ticket::class, TicketPolicy::class);

        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });

        Gate::define('support', function ($user) {
            return $user->isSupport();
        });
    }
}
