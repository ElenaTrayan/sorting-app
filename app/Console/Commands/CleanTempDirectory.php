<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Чистит временную папку загрузок (temp_directory/{userId} на диске local). Картинки и видео
 * лежат там с загрузки на форме до сохранения поста: сохранение переносит их в пост, удаление
 * превью на форме — удаляет. Если пост так и не сохранили (закрыли вкладку, ошибка), файлы
 * остаются навсегда — их и удаляем, когда они достаточно старые, чтобы форма с ними уже не была открыта.
 */
class CleanTempDirectory extends Command
{
    /**
     * @var string
     */
    protected $signature = 'temp:clean
        {--hours=24 : Удалять файлы, которые не менялись дольше стольких часов}
        {--dry-run : Только показать, что будет удалено}';

    /**
     * @var string
     */
    protected $description = 'Удаляет старые файлы из временной папки загрузок (temp_directory)';

    private const TEMP_DIRECTORY = 'temp_directory';

    public function handle()
    {
        $hours = (int)$this->option('hours');
        if ($hours < 1) {
            $this->error('--hours должно быть не меньше 1.');
            return 1;
        }

        $dryRun = (bool)$this->option('dry-run');
        $disk = Storage::disk('local');

        if (!$disk->exists(self::TEMP_DIRECTORY)) {
            $this->info('Временной папки нет — чистить нечего.');
            return 0;
        }

        $threshold = now()->subHours($hours)->getTimestamp();
        $deleted = 0;
        $bytes = 0;

        foreach ($disk->allFiles(self::TEMP_DIRECTORY) as $path) {
            if ($disk->lastModified($path) >= $threshold) {
                continue;
            }

            $size = $disk->size($path);

            if ($dryRun) {
                $this->line($path);
            } elseif (!$disk->delete($path)) {
                $this->warn("Не удалось удалить: {$path}");
                continue;
            }

            $deleted++;
            $bytes += $size;
        }

        $megabytes = round($bytes / 1024 / 1024, 1);
        $this->info(($dryRun ? 'Будет удалено' : 'Удалено') . " файлов: {$deleted} ({$megabytes} МБ), старше {$hours} ч.");

        return 0;
    }
}
