<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Class PostsCategory
 * @package App\Models
 */

class PostsCategory extends Model
{
    protected $table = 'posts_categories';

    public $timestamps = false;

    const MAX_LEVEL = 4; // 4 уровня

    protected $fillable = [
        'id',
        'title',
        'alias',
        'parent_id',
        'user_id',
        'status',
        'sort',
        'short_description',
    ];

    public function parent()
    {
        return $this->belongsTo(PostsCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PostsCategory::class, 'parent_id');
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'id');
    }

}
