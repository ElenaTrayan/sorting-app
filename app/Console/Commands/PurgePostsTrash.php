<?php

namespace App\Console\Commands;

use App\Components\PostTrash;
use Illuminate\Console\Command;

/**
 * Чистит корзину постов: посты, удалённые больше PostTrash::KEEP_DAYS дней назад, удаляются навсегда —
 * вместе с файлами картинок и видео. Запускается по расписанию (App\Console\Kernel).
 */
class PurgePostsTrash extends Command
{
    /**
     * @var string
     */
    protected $signature = 'posts:purge-trash';

    /**
     * @var string
     */
    protected $description = 'Удаляет навсегда посты, которые пролежали в корзине дольше положенного';

    public function handle()
    {
        $purged = PostTrash::purgeExpired();

        $this->info("Удалено навсегда постов из корзины: {$purged} (старше " . PostTrash::KEEP_DAYS . ' дн.).');

        return 0;
    }
}
