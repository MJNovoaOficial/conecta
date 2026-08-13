<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Listeners\TicketEventSubscriber;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // No bindings needed currently
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Register the subscriber for ticket events
        $this->app->events->subscribe(TicketEventSubscriber::class);
    }
}
?>
