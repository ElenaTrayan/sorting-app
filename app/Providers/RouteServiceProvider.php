<?php

namespace App\Providers;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostsCategory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // Посты, категории и хештеги принадлежат пользователю: чужой id в URL — 404, как будто
        // записи нет (иначе update/addImage/... с model binding работали бы с чужими данными).
        // Роуты с этими параметрами всегда под middleware "auth" (см. routes/web.php).
        Route::bind('post', function ($value) {
            return Post::where('user_id', Auth::id())->findOrFail($value);
        });
        Route::bind('posts_category', function ($value) {
            return PostsCategory::where('user_id', Auth::id())->findOrFail($value);
        });
        Route::bind('hashtag', function ($value) {
            return Hashtag::where('user_id', Auth::id())->findOrFail($value);
        });

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
