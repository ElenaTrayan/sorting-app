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

        // Каждый час, а не раз в сутки в заданное время: компьютер включают не каждый день и не ночью — в
        // назначенный час он мог быть выключен, и очистка не шла. Обе команды удаляют только то, что старше своего
        // срока, так что лишний запуск ничего не трогает

        // брошенные загрузки во временной папке (пост так и не сохранили, старше 24 ч) — см. CleanTempDirectory
        $schedule->command('temp:clean')->hourly();

        // посты, пролежавшие в корзине дольше положенного, — удалить навсегда, см. PurgePostsTrash
        $schedule->command('posts:purge-trash')->hourlyAt(30);
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
