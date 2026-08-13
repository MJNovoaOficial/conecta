<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\CloseExpiredTicketsJob;
use App\Jobs\SendSlaWarningsJob;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Register custom commands here if needed
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Run every 5 minutes for local development (schedule:work) and production (cron)
        $schedule->job(new CloseExpiredTicketsJob)->everyFiveMinutes();
        $schedule->job(new SendSlaWarningsJob)->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
?>
