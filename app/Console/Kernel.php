<?php

declare(strict_types=1);

namespace App\Console;

use App\Console\Commands\DeletePeers;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\DeleteExpiredInvitesCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DeletePeers::class)->everyFifteenMinutes();
        $schedule->command(DeleteExpiredInvitesCommand::class)->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
