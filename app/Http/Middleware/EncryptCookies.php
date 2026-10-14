<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // галочка "Свернуть группы" в ленте — cookie ставится из JS (см. Post::FEED_UNGROUPED_COOKIE)
        'posts_ungrouped',
    ];
}
