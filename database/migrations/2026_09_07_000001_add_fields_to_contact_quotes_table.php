<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Поля для поп-апа "Добавить высказывание" (resources/views/admin/contacts/parts/quote_add_modal.blade.php):
 * источник, ссылка на источник, дата (когда сказано — может отличаться от created_at,
 * т.е. от даты добавления в систему), комментарий. Раньше вся эта информация на карточке
 * высказывания была подставной (см. ContactQuote::feedMeta()) — теперь, если поле
 * заполнено по-настоящему, feedMeta() отдаёт его вместо подставного значения.
 */
class AddFieldsToContactQuotesTable extends Migration
{
    public function up()
    {
        Schema::table('contact_quotes', function (Blueprint $table) {
            $table->string('platform', 100)->nullable()->after('quote');
            $table->string('source_url', 500)->nullable()->after('platform');
            $table->date('occurred_at')->nullable()->after('source_url'); // когда высказывание было сказано/опубликовано
            $table->boolean('is_exact_date')->nullable()->default(true)->after('occurred_at');
            $table->text('comment')->nullable()->after('is_exact_date'); // личная заметка к высказыванию, не само высказывание
        });
    }

    public function down()
    {
        Schema::table('contact_quotes', function (Blueprint $table) {
            $table->dropColumn(['platform', 'source_url', 'occurred_at', 'is_exact_date', 'comment']);
        });
    }
}
