<?php

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Тип поста (Post::TYPE_*): "Текст + Изображение" / "Изображение" / "Текст" определяются по содержимому
// и пересчитываются при каждом сохранении (Post::booted), "Фильм / Сериал" и "Рецепт" выбирают вручную.
// Существующим постам тип проставляем здесь же: есть запись в post_films — фильм, иначе по содержимому
class AddTypeToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('type', 20)->default(Post::TYPE_TEXT_PICTURE)->after('category_id')->index();
        });

        $filmPostIds = DB::table('post_films')->pluck('post_id')->flip();

        // через DB, а не save(): не трогаем updated_at и не запускаем хуки модели
        Post::query()
            ->select(['id', 'content', 'small_image', 'medium_image', 'original_image', 'video'])
            ->chunkById(500, function ($posts) use ($filmPostIds) {
                foreach ($posts as $post) {
                    $type = isset($filmPostIds[$post->id]) ? Post::TYPE_FILM : $post->detectContentType();
                    DB::table('posts')->where('id', $post->id)->update(['type' => $type]);
                }
            });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
}
