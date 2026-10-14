<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // брошенные загрузки во временной папке (пост так и не сохранили) — см. CleanTempDirectory
        $schedule->command('temp:clean')->dailyAt('04:00');

        // посты, пролежавшие в корзине дольше положенного, — удалить навсегда, см. PurgePostsTrash
        $schedule->command('posts:purge-trash')->dailyAt('04:30');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
