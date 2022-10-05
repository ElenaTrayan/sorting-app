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

}
