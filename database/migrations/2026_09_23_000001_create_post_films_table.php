<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Данные фильма/сериала для пинов типа "Фильм / Сериал" (страница создания поста, блок #tab-film).
// Отдельной таблицей 1:1, а не колонками в posts — у остальных типов пинов эти поля всегда пустые.
class CreatePostFilmsTable extends Migration
{
    public function up()
    {
        Schema::create('post_films', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id')->unique();
            $table->string('original_title', 255)->nullable();
            $table->string('kind', 30)->nullable(); // Фильм, Сериал, Мультфильм... — PostFilm::KINDS
            $table->unsignedSmallInteger('seasons')->nullable();
            $table->string('status', 30)->nullable(); // статус просмотра — PostFilm::STATUSES
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('countries', 255)->nullable(); // как на форме: через запятую
            $table->string('genres', 255)->nullable();
            $table->string('directors', 255)->nullable();
            $table->text('actors')->nullable();
            $table->string('duration', 50)->nullable(); // "2 ч 10 мин" — как пришло с формы/парсера
            $table->decimal('imdb_rating', 3, 1)->nullable();
            $table->unsignedTinyInteger('my_rating')->nullable(); // 1..10
            $table->string('mpaa_rating', 20)->nullable();
            $table->text('description')->nullable();
            $table->json('pros')->nullable(); // ["увлекательный сюжет", ...]
            $table->json('cons')->nullable();
            $table->timestamps();

            $table->index(['kind', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('post_films');
    }
}
