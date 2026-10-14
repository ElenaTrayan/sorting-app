<?php

namespace App\Console\Commands;

use App\Components\CollectionColors;
use App\Models\Post;
use Illuminate\Console\Command;

/**
 * Палитра цветов (posts.palette) для уже существующих постов. Новым постам она считается сама
 * при сохранении картинок (Post::booted), эта команда нужна один раз — для старых.
 */
class BackfillPostPalettes extends Command
{
    /**
     * @var string
     */
    protected $signature = 'posts:backfill-palettes
        {--fresh : Пересчитать и у постов, где палитра уже есть}
        {--include-edited : Вместе с --fresh — пересчитать и палитры, поправленные вручную (правки пропадут)}';

    /**
     * @var string
     */
    protected $description = 'Считает палитру цветов обложки для постов, у которых её ещё нет';

    public function handle()
    {
        $query = Post::query()
            ->where(function ($q) {
                $q->whereNotNull('original_image')->where('original_image', '!=', '');
            })
            ->when(!$this->option('fresh'), function ($q) {
                $q->whereNull('palette');
            })
            // поправленную вручную палитру не трогаем (блок "Палитра" на странице редактирования поста)
            ->when(!$this->option('include-edited'), function ($q) {
                $q->where('palette_edited', false);
            });

        if (!$this->option('include-edited')) {
            $edited = Post::where('palette_edited', true)->count();
            if ($edited) {
                $this->line("Палитры, поправленные вручную, пропускаю: {$edited} (пересчитать и их — --include-edited).");
            }
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info('Все посты с картинками уже с палитрой.');
            return 0;
        }

        $this->info("Считаю палитру для {$total} постов...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $done = 0;
        $empty = 0;
        $errors = 0;

        $query->select(['id', 'small_image', 'medium_image', 'original_image'])
            ->chunkById(100, function ($posts) use ($bar, &$done, &$empty, &$errors) {
                foreach ($posts as $post) {
                    try {
                        $palette = $post->computePalette();
                        // через toBase(): Eloquent-овский update() сам проставил бы updated_at,
                        // а картинки поста ведь не менялись
                        Post::whereKey($post->id)->toBase()->update([
                            'palette' => $palette ? json_encode($palette) : null,
                            // пересчитанная палитра — снова автоматическая (важно для --include-edited)
                            'palette_edited' => false,
                        ]);
                        $palette ? $done++ : $empty++;
                    } catch (\Throwable $e) {
                        $errors++;
                        $this->newLine();
                        $this->warn("Пост #{$post->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        // палитры менялись в обход модели (toBase) — сбрасываем цвета коллекции для поиска по цвету сами
        foreach (Post::query()->distinct()->pluck('user_id') as $userId) {
            CollectionColors::forget((int)$userId);
        }
        $this->info("Палитра посчитана: {$done}. Без палитры (нет файла или его не прочитать): {$empty}. Ошибок: {$errors}.");

        return 0;
    }
}
