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

}
