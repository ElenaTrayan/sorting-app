<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Админка → Пользователи: список, карточка, блокировка, сервисы подписки.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('q', ''));
        $status = in_array($request->get('status'), ['active', 'blocked'], true) ? $request->get('status') : 'all';
        $service = array_key_exists($request->get('service'), config('subscription.services')) ? $request->get('service') : null;

        $users = User::query()
            ->with(['roles', 'services'])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
                $q->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)->orWhere('email', 'like', $like);
                });
            })
            ->when($status === 'blocked', function ($q) {
                $q->whereNotNull('blocked_at');
            })
            ->when($status === 'active', function ($q) {
                $q->whereNull('blocked_at');
            })
            ->when($service, function ($q) use ($service) {
                $q->whereHas('services', function ($q) use ($service) {
                    $q->where('service', $service)
                        ->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        });
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
            'service' => $service,
            'services' => config('subscription.services'),
            'total' => User::count(),
        ]);
    }

    public function show(User $user)
    {
        $user->load(['roles', 'services']);

        $usage = [
            'Посты' => Post::where('user_id', $user->id)->count(),
            'Хештеги' => Hashtag::where('user_id', $user->id)->count(),
            'Контакты' => Contact::where('user_id', $user->id)->count(),
            'Списки подарков' => Wishlist::where('user_id', $user->id)->count(),
            'Запасы' => StockItem::where('user_id', $user->id)->count(),
        ];

        return view('admin.users.show', [
            'account' => $user,
            'usage' => $usage,
            'services' => config('subscription.services'),
            'userServices' => $user->services->keyBy('service'),
        ]);
    }

    public function block(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors('Нельзя заблокировать самого себя.');
        }
        if ($user->isAdmin()) {
            return back()->withErrors('Администратора заблокировать нельзя.');
        }

        $data = $request->validate(['reason' => 'nullable|string|max:500']);

        // blocked_at не в $fillable — админ ставит его только через этот метод
        $user->forceFill([
            'blocked_at' => now(),
            'blocked_reason' => $data['reason'] ?? null,
        ])->save();

        return redirect()->route('admin.users.show', $user)->withSuccess('Пользователь заблокирован');
    }

    public function unblock(User $user)
    {
        $user->forceFill(['blocked_at' => null, 'blocked_reason' => null])->save();

        return redirect()->route('admin.users.show', $user)->withSuccess('Пользователь разблокирован');
    }

    /**
     * Сохраняет подписку: отмеченные сервисы (с необязательной датой окончания) включены,
     * остальные выключены.
     */
    public function updateServices(Request $request, User $user)
    {
        $known = array_keys(config('subscription.services'));

        $data = $request->validate([
            'services' => 'nullable|array',
            'services.*' => 'string|in:' . implode(',', $known),
            'expires_at' => 'nullable|array',
            'expires_at.*' => 'nullable|date',
        ]);

        $services = [];
        foreach ($data['services'] ?? [] as $service) {
            $expires = $data['expires_at'][$service] ?? null;
            // срок действует весь указанный день
            $services[$service] = $expires ? Carbon::parse($expires)->endOfDay() : null;
        }

        $user->syncServices($services);

        return redirect()->route('admin.users.show', $user)->withSuccess('Подписка обновлена');
    }
}
