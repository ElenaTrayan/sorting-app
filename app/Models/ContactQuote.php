<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactQuote extends Model
{
    protected $table = 'contact_quotes';

    protected $fillable = [
        'contact_id',
        'quote',
        'platform',
        'source_url',
        'occurred_at',
        'is_exact_date',
        'comment',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'is_exact_date' => 'boolean',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'contact_quote_hashtag', 'contact_quote_id', 'hashtag_id');
    }

    public function images()
    {
        return $this->hasMany(ContactQuoteImage::class, 'contact_quote_id')->orderBy('created_at');
    }

    /**
     * Дата, которую показываем на карточке — то, что реально указали в поп-апе добавления
     * ("Дата" высказывания, т.е. когда оно было сказано/опубликовано), а не дата добавления
     * записи в систему (created_at), если она отличается.
     */
    public function getDisplayDateAttribute()
    {
        return $this->occurred_at ?: $this->created_at;
    }

    /**
     * Метаданные для вкладки "Высказывания" (resources/views/admin/contacts/parts/quotes_tab.blade.php).
     * Начиная с поп-апа "Добавить высказывание" источник/ссылка/теги можно указать по-настоящему —
     * тогда feedMeta() отдаёт именно их. Для старых высказываний (или если поле просто не
     * заполнили) используется прежний подставной набор: платформа — из реальной соцсети контакта
     * по кругу, если они есть, иначе из фиксированного списка; тема/тональность/реакции —
     * стабильный (по id) фейк, под них до сих пор нет отдельных полей в БД.
     *
     * @param \Illuminate\Support\Collection|null $socialLinks $contact->socialLinks, чтобы не делать лишний запрос
     * @return array
     */
    public function feedMeta($socialLinks = null): array
    {
        $n = $this->id ?: 1;

        if ($this->platform) {
            $platform = $this->platform;
            $handle = null;
        } elseif ($socialLinks && $socialLinks->isNotEmpty()) {
            $social = $socialLinks[$n % $socialLinks->count()];
            $platform = $social->platform;
            $handle = $social->handle ?: $social->value;
        } else {
            $platform = ['Instagram', 'TikTok', 'Telegram', 'Facebook', 'YouTube', 'X (Twitter)'][$n % 6];
            $handle = null;
        }
        [$icon, $color] = ContactSocialLink::iconFor($platform);

        if ($this->source_url) {
            $originalUrl = $this->source_url;
        } elseif (!$this->platform && $socialLinks && $socialLinks->isNotEmpty()) {
            $social = $socialLinks[$n % $socialLinks->count()];
            $originalUrl = ($social->value && Str::startsWith(Str::lower($social->value), 'http')) ? $social->value : null;
        } else {
            $originalUrl = null;
        }

        $realImages = $this->relationLoaded('images') ? $this->images : collect();
        $realHashtags = $this->relationLoaded('hashtags') ? $this->hashtags : collect();

        $contentType = $realImages->isNotEmpty()
            ? 'screenshot'
            : ['text', 'text', 'text', 'text', 'screenshot', 'video'][$n % 6];

        return [
            'platform' => $platform,
            'handle' => $handle,
            'icon' => $icon,
            'color' => $color,
            'original_url' => $originalUrl,
            'topic' => ['Путешествия', 'Работа', 'Отношения', 'Образ жизни', 'Другое'][$n % 5],
            'sentiment' => ['positive', 'neutral', 'negative'][$n % 3],
            'hashtags' => $realHashtags->isNotEmpty()
                ? $realHashtags->pluck('title')->values()->all()
                : array_values(array_unique([
                    ['путешествия', 'жизнь', 'вдохновение', 'мотивация', 'работа', 'друзья', 'кофе', 'отношения'][$n % 8],
                    ['цитаты', 'настроение', 'планы', 'мысли'][$n % 4],
                ])),
            'likes' => 20 + ($n * 17) % 130,
            'comments' => 2 + ($n * 7) % 30,
            'content_type' => $contentType,
            'media_variant' => $n % 4,
            'video_duration' => ['0:14', '0:28', '0:45', '1:02'][$n % 4],
            'real_image_url' => $realImages->isNotEmpty() ? $realImages->first()->image_url : null,
            'real_image_count' => $realImages->count(),
        ];
    }
}
