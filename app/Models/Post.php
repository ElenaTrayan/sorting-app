<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class Post
 * @package App\Models
 */
class Post extends Model
{
    protected $table = 'posts';

    protected $imagePath = '/uploads/posts/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'title',
        'alias',
        'user_id',
        'category_id',
        'status',
        'is_used',
        'content',
        'small_image',
        'medium_image',
        'large_image',
        'image_alt',
        'video',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * устанавливаем связь с таблицей posts_categories (модель PostsCategory)
     * Один ко многим по полю category_id (табл. posts) = id (posts_categories)
     */
    public function category()
    {
        return $this->belongsTo(PostsCategory::class, 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * устанавливаем связь с таблицей users (модель User)
     * Один ко многим по полю user_id (табл. posts) = id (users)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * Хэштеги поста
     */
    public function hashtags()
    {
        return $this->belongsToMany(Hashtag::class, 'hashtag_post', 'post_id', 'hashtag_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     *
     * Данные фильма/сериала (только у пинов типа "Фильм / Сериал")
     */
    public function film()
    {
        return $this->hasOne(PostFilm::class, 'post_id');
    }

    public function uploadImage()
    {
        $path = $request->file('avatar')->storeAs(
            'avatars', $request->user()->id
        );
    }

    /**
     * Декодирует поле video ({name, extension, path}) в массив.
     *
     * @return array|null
     */
    public function getVideoDataAttribute(): ?array
    {
        if (empty($this->attributes['video'])) {
            return null;
        }

        $decoded = json_decode($this->attributes['video'], true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Готовый URL видео для <video src="">, либо null, если видео нет.
     *
     * @return string|null
     */
    public function getVideoUrlAttribute(): ?string
    {
        $data = $this->video_data;

        return !empty($data['path']) ? '/storage' . $data['path'] : null;
    }

}
