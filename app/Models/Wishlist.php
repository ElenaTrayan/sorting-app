<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class Wishlist
 * @package App\Models
 */
class Wishlist extends Model
{
    protected $table = 'wishlists';

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'description',
        'occasion',
    ];

    protected static function booted()
    {
        static::creating(function (Wishlist $wishlist) {
            if (empty($wishlist->uuid)) {
                $wishlist->uuid = (string) Str::uuid();
            }
        });
    }

    public function items()
    {
        return $this->hasMany(WishlistItem::class, 'wishlist_id')->orderBy('created_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
