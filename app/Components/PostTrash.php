<?php

namespace App\Components;

use App\Http\Controllers\User\Packages\UploadImageController;
use App\Models\Post;
use App\Models\PostClothes;
use App\Models\PostFilm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Корзина постов. "Удалить" у поста — это перенос в корзину (SoftDeletes у Post: строка и файлы на месте,
 * пост просто не виден), оттуда его можно вернуть или удалить навсегда (страница "Корзина",
 * PostController::trash). Через KEEP_DAYS дней пост удаляется навсегда сам — команда posts:purge-trash.
 */
class PostTrash
{
    // сколько дней пост лежит в корзине
    public const KEEP_DAYS = 30;

    /**
     * Удаляет пост навсегда: файлы картинок и видео, данные фильма/вещи, хештеги, хеши картинок, саму запись
     *
     * @param Post $post пост из корзины
     * @return void
     * @throws \RuntimeException файлы картинок удалить не удалось — пост остаётся в корзине
     */
    public static function purge(Post $post): void
    {
        $deleted = (new UploadImageController)->deletePostImages($post);
        if ($deleted !== true) {
            $errors = $deleted['errors'] ?? '';
            throw new \RuntimeException('Ошибка при удалении изображений поста. ' . (is_array($errors) ? implode(' ', $errors) : $errors));
        }

        if (!empty($post->video_data['path'])) {
            Storage::disk('local')->delete($post->video_data['path']);
        }

        $postId = $post->id;
        $post->hashtags()->detach();
        $post->forceDelete();

        PostFilm::where('post_id', $postId)->delete();
        PostClothes::where('post_id', $postId)->delete();
        // иначе поиск дубликатов находил бы картинки несуществующего поста
        DB::table('image_hashes')->where('post_id', $postId)->delete();
    }

    /**
     * Удаляет навсегда посты, пролежавшие в корзине дольше KEEP_DAYS дней
     *
     * @return int сколько постов удалено
     */
    public static function purgeExpired(): int
    {
        $purged = 0;

        Post::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays(self::KEEP_DAYS))
            ->orderBy('id')
            ->get()
            ->each(function (Post $post) use (&$purged) {
                try {
                    self::purge($post);
                    $purged++;
                } catch (\Throwable $e) {
                    // один пост с проблемными файлами не должен останавливать очистку остальных
                    report($e);
                }
            });

        return $purged;
    }

    /**
     * Сколько дней осталось лежать в корзине посту, удалённому в $deletedAt
     *
     * @param \DateTimeInterface $deletedAt
     * @return int
     */
    public static function daysLeftSince(\DateTimeInterface $deletedAt): int
    {
        $purgeAt = Carbon::instance($deletedAt)->addDays(self::KEEP_DAYS);

        return max(0, (int)ceil(now()->diffInHours($purgeAt, false) / 24));
    }
}
