<?php

namespace App\Console\Commands;

use App\Http\Controllers\User\Packages\UploadImageController;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackfillImageHashes extends Command
{
    /**
     * @var string
     */
    protected $signature = 'images:backfill-hashes {--fresh : Удалить все существующие записи и посчитать заново}';

    /**
     * @var string
     */
    protected $description = 'Считает и сохраняет перцептивные хеши для уже существующих изображений постов (нужно для поиска дублей)';

    public function handle()
    {
        if ($this->option('fresh')) {
            DB::table('image_hashes')->truncate();
            $this->info('Таблица image_hashes очищена.');
        }

        $this->backfillMissingPerceptualHash();
        $this->backfillNewImages();
    }

    /**
     * Досчитывает второй хеш (PerceptualHash) для записей, у которых он ещё не посчитан —
     * например, если раньше уже был запуск этой команды до того, как второй хеш появился.
     */
    private function backfillMissingPerceptualHash(): void
    {
        $rows = DB::table('image_hashes')->whereNull('perceptual_hash')->get(['id', 'path']);

        if ($rows->isEmpty()) {
            return;
        }

        $this->info("Досчитываю PerceptualHash для {$rows->count()} уже существующих записей...");
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $updated = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $absolutePath = Storage::disk('local')->path($row->path);

            if (!file_exists($absolutePath)) {
                $bar->advance();
                continue;
            }

            try {
                $perceptualHash = UploadImageController::computePerceptualHash($absolutePath);
                DB::table('image_hashes')->where('id', $row->id)->update([
                    'perceptual_hash' => $perceptualHash,
                    'updated_at' => now(),
                ]);
                $updated++;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("Запись #{$row->id} ({$row->path}): " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("PerceptualHash досчитан: {$updated}. Ошибок: {$errors}.");
    }

    /**
     * Считает оба хеша для изображений постов, которых ещё вообще нет в image_hashes.
     */
    private function backfillNewImages(): void
    {
        $existingKeys = DB::table('image_hashes')
            ->get(['post_id', 'image_name'])
            ->map(function ($row) {
                return $row->post_id . ':' . $row->image_name;
            })
            ->flip();

        $posts = Post::whereNotNull('original_image')
            ->where('original_image', '!=', '')
            ->get(['id', 'original_image']);

        $this->info('Ищу новые (ещё не посчитанные) изображения...');
        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        $processed = 0;
        $skippedExisting = 0;
        $skippedMissingFile = 0;
        $errors = 0;

        foreach ($posts as $post) {
            $images = UploadImageController::normalizeImages($post->original_image);

            foreach ($images as $name => $image) {
                if (isset($existingKeys[$post->id . ':' . $name])) {
                    $skippedExisting++;
                    continue;
                }

                if (empty($image['path'])) {
                    continue;
                }

                $absolutePath = Storage::disk('local')->path($image['path']);

                if (!file_exists($absolutePath)) {
                    $skippedMissingFile++;
                    continue;
                }

                try {
                    $size = UploadImageController::imageSize($absolutePath);
                    $phash = UploadImageController::computeImageHash($absolutePath);
                    $perceptualHash = UploadImageController::computePerceptualHash($absolutePath);

                    UploadImageController::storeImageHash(
                        $post->id,
                        $name,
                        $image['path'],
                        $size[0] ?? 0,
                        $size[1] ?? 0,
                        $phash,
                        $perceptualHash
                    );

                    $processed++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->newLine();
                    $this->warn("Пост #{$post->id}, файл {$name}: " . $e->getMessage());
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Готово. Посчитано новых хешей: {$processed}. Уже было посчитано: {$skippedExisting}. Файл не найден на диске: {$skippedMissingFile}. Ошибок: {$errors}.");
    }
}
