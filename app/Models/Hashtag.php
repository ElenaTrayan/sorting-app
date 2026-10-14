<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Class Post
 * @package App\Models
 */
class Hashtag extends Model
{
    protected $table = 'hashtags';

    public $timestamps = false;

    const MAX_LEVEL = 4; // 4 уровня

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'title',
        'parent_id',
        'count_posts',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Hashtag::class, 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(Hashtag::class, 'parent_id');
    }

    /**
     * Посты с этим хэштегом
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class, 'hashtag_post', 'hashtag_id', 'post_id');
    }

    /**
     * Контакты с этим хэштегом
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_hashtag', 'hashtag_id', 'contact_id');
    }

    /**
     * Связанные хэштеги (симметричная связь many-to-many сама на себя).
     * Строки в hashtag_relations всегда хранятся в обе стороны (см. attachRelated()),
     * поэтому этот relation отдаёт полный список независимо от того,
     * с какой стороны связь была изначально добавлена.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function relatedHashtags()
    {
        return $this->belongsToMany(
            Hashtag::class,
            'hashtag_relations',
            'hashtag_id',
            'related_hashtag_id'
        )->withPivot('auto_add');
    }

    /**
     * Направление связи по умолчанию (для новой связи и для старых — см. миграцию add_auto_add): выбрали
     * $hashtagId — добавлять ли $relatedHashtagId к посту сразу. Связанный, который используется не реже
     * выбранного, — общее ("Самара Уивинг" → "актриса": добавлять), реже — конкретнее ("актриса" →
     * "Самара Уивинг": только предлагать). На странице хештега направление можно поменять
     *
     * @param int $hashtagId
     * @param int $relatedHashtagId
     * @return bool
     */
    public static function defaultAutoAdd(int $hashtagId, int $relatedHashtagId): bool
    {
        $counts = DB::table('hashtag_post')
            ->whereIn('hashtag_id', [$hashtagId, $relatedHashtagId])
            ->select('hashtag_id', DB::raw('count(*) as c'))
            ->groupBy('hashtag_id')
            ->pluck('c', 'hashtag_id');

        return (int)($counts[$relatedHashtagId] ?? 0) >= (int)($counts[$hashtagId] ?? 0);
    }

    /**
     * Поменять направление связи: выбрали этот хештег — добавлять ли $relatedHashtagId к посту сразу
     * (обратная строка пары не меняется)
     *
     * @param int $relatedHashtagId
     * @param bool $autoAdd
     * @return void
     */
    public function setRelatedAutoAdd(int $relatedHashtagId, bool $autoAdd)
    {
        $this->relatedHashtags()->updateExistingPivot($relatedHashtagId, ['auto_add' => $autoAdd]);
    }

    /**
     * Добавить связанный хэштег. Пишет сразу обе строки (в обе стороны),
     * чтобы связь была видна и у текущего хэштега, и у связанного.
     *
     * @param int $relatedHashtagId
     * @return void
     */
    public function attachRelated(int $relatedHashtagId)
    {
        if ($relatedHashtagId === $this->id) {
            return;
        }

        // направление по умолчанию — только у новой строки: у уже существующей не сбрасываем то,
        // что выбрали на странице хештега
        if (!$this->relatedHashtags()->where('related_hashtag_id', $relatedHashtagId)->exists()) {
            $this->relatedHashtags()->attach($relatedHashtagId, [
                'auto_add' => static::defaultAutoAdd($this->id, $relatedHashtagId),
            ]);
        }

        /** @var Hashtag|null $relatedHashtag */
        $relatedHashtag = static::find($relatedHashtagId);
        if ($relatedHashtag && !$relatedHashtag->relatedHashtags()->where('related_hashtag_id', $this->id)->exists()) {
            $relatedHashtag->relatedHashtags()->attach($this->id, [
                'auto_add' => static::defaultAutoAdd($relatedHashtagId, $this->id),
            ]);
        }
    }

    /**
     * Убрать связанный хэштег. Удаляет обе строки связи.
     *
     * @param int $relatedHashtagId
     * @return void
     */
    public function detachRelated(int $relatedHashtagId)
    {
        $this->relatedHashtags()->detach($relatedHashtagId);

        /** @var Hashtag|null $relatedHashtag */
        $relatedHashtag = static::find($relatedHashtagId);
        if ($relatedHashtag) {
            $relatedHashtag->relatedHashtags()->detach($this->id);
        }
    }

}
