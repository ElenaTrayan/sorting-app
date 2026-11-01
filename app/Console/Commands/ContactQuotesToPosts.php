<?php

namespace App\Console\Commands;

use App\Http\Controllers\User\ContactController;
use App\Models\ContactQuote;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Переносит высказывания контактов, добавленные до того, как они стали постами (contact_quotes), в посты
 * с хештегом "высказывание" — так же, как их теперь создаёт поп-ап "Добавить высказывание"
 * (ContactController::createQuotePost()): скрины — картинки поста, текст, источник, ссылка, комментарий,
 * теги; дата высказывания (а без неё — дата добавления) — дата пина, дата добавления — дата поста.
 * Перенесённая запись из contact_quotes удаляется; файлы её скринов остаются на диске (у поста — свои копии)
 */
class ContactQuotesToPosts extends Command
{
    /**
     * @var string
     */
    protected $signature = 'contacts:quotes-to-posts
        {--apply : Перенести; без этого — только показать, что будет перенесено}';

    /**
     * @var string
     */
    protected $description = 'Переносит старые высказывания контактов (contact_quotes) в посты с хештегом "высказывание"';

    public function handle()
    {
        $apply = (bool)$this->option('apply');
        $quotes = ContactQuote::with(['contact.hashtags', 'hashtags', 'images'])->orderBy('id')->get();
        if ($quotes->isEmpty()) {
            $this->info('Переносить нечего.');
            return 0;
        }

        $controller = app(ContactController::class);
        $moved = 0;

        foreach ($quotes as $quote) {
            $contact = $quote->contact;
            $label = '#' . $quote->id . ' ' . ($contact->name ?? '?') . ': «' . mb_substr(preg_replace('/\s+/u', ' ', $quote->quote), 0, 50) . '»'
                . ', скринов: ' . $quote->images->count();
            if (!$contact) {
                $this->warn($label . ' — контакта нет, пропущено');
                continue;
            }

            $files = [];
            foreach ($quote->images as $image) {
                $data = $image->image_data;
                $path = !empty($data['path']) ? Storage::disk('local')->path($data['path']) : null;
                if (!$path || !is_file($path)) {
                    $this->warn($label . ' — нет файла скрина ' . ($data['path'] ?? '?'));
                    continue;
                }
                // файл не перемещается: stageFileToTempDirectory() копирует его во временную папку
                $files[] = new UploadedFile($path, basename($path), null, null, true);
            }

            if (!$apply) {
                $this->line($label);
                continue;
            }

            try {
                Auth::loginUsingId($contact->user_id);
                $post = $controller->createQuotePost($contact, [
                    'quote' => $quote->quote,
                    'platform' => $quote->platform,
                    'source_url' => $quote->source_url,
                    'occurred_at' => ($quote->occurred_at ?: $quote->created_at)->format('Y-m-d'),
                    'is_exact_date' => $quote->occurred_at ? $quote->is_exact_date : true,
                    'comment' => $quote->comment,
                    'hashtag_ids' => $quote->hashtags->pluck('id')->all(),
                ], $files);

                $post->created_at = $quote->created_at;
                $post->save();

                $quote->images()->delete();
                $quote->hashtags()->detach();
                $quote->delete();
                $moved++;
                $this->info($label . ' → пост #' . $post->id);
            } catch (\Throwable $e) {
                $this->error($label . ' — не перенесено: ' . $e->getMessage());
            }
        }

        $this->info($apply ? 'Перенесено: ' . $moved . ' из ' . $quotes->count() : 'Это проверка. Перенести: php artisan contacts:quotes-to-posts --apply');
        return 0;
    }
}
