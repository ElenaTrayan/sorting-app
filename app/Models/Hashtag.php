<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

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
        );
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

        $this->relatedHashtags()->syncWithoutDetaching([$relatedHashtagId]);

        /** @var Hashtag|null $relatedHashtag */
        $relatedHashtag = static::find($relatedHashtagId);
        if ($relatedHashtag) {
            $relatedHashtag->relatedHashtags()->syncWithoutDetaching([$this->id]);
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
