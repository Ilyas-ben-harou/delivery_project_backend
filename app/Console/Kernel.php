<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\AutoAssignOrders::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Run auto-assignment every hour
        $schedule->command('orders:auto-assign')
            ->hourly()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/auto-assign.log'));

        // Run auto-assignment at specific times: 8 AM, 12 PM, 5 PM
        $schedule->command('orders:auto-assign')
            ->at('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/auto-assign-morning.log'));

        $schedule->command('orders:auto-assign')
            ->at('12:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/auto-assign-noon.log'));

        $schedule->command('orders:auto-assign')
            ->at('17:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/auto-assign-evening.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
