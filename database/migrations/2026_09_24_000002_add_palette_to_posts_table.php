<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Палитра цветов обложки поста: [{"hex": "#D8C3A5", "share": 0.34}, ...] — от главного цвета к второстепенным.
// Считается сама при изменении картинок поста (Post::booted -> App\Components\ImagePalette),
// для уже существующих постов — php artisan posts:backfill-palettes
class AddPaletteToPostsTable extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('palette')->nullable()->after('video');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('palette');
        });
    }
}
