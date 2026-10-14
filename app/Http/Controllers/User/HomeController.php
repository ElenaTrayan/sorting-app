<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\StockItem;
use App\Models\WardrobeItem;
use App\Models\Wishlist;
use Illuminate\Http\Request;

/**
 * "Главная" кабинета пользователя: разделы, которые включены ему в подписке, и сколько
 * в каждом записей. Разделы вне подписки показываются серыми — со всем остальным
 * (роуты, меню) их закрывает middleware "service:<ключ>", см. routes/web.php.
 */
class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $counters = [
            'posts' => function () use ($user) {
                return Post::where('user_id', $user->id)->count();
            },
            'hashtags' => function () use ($user) {
                return Hashtag::where('user_id', $user->id)->count();
            },
            'contacts' => function () use ($user) {
                return Contact::where('user_id', $user->id)->count();
            },
            'wishlists' => function () use ($user) {
                return Wishlist::where('user_id', $user->id)->count();
            },
            'stock' => function () use ($user) {
                return StockItem::where('user_id', $user->id)->count();
            },
            'wardrobe' => function () use ($user) {
                return WardrobeItem::where('user_id', $user->id)->count();
            },
        ];

        $active = $user->activeServiceKeys();
        $services = [];
        foreach (config('subscription.services') as $key => $service) {
            $isActive = in_array($key, $active, true);
            $services[$key] = $service + [
                'active' => $isActive,
                'count' => ($isActive && isset($counters[$key])) ? $counters[$key]() : null,
            ];
        }

        return view('user.home.index', ['services' => $services]);
    }
}
