<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\WishlistController;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;

/**
 * Публичная (гостевая) сторона вишлиста — доступна по ссылке /wishlist/{uuid}
 * без входа в админку. Гость видит список желаний и может резервировать их,
 * чтобы не дублировать покупку; редактировать/добавлять/удалять желания гость
 * не может — это только у владельца (см. Admin\WishlistController).
 */
class WishlistPublicController extends Controller
{
    public function show(string $uuid)
    {
        $wishlist = Wishlist::where('uuid', $uuid)->firstOrFail();
        $wishlist->load(['items' => function ($q) {
            $q->orderBy('created_at');
        }]);

        return view('wishlist.public', [
            'wishlist' => $wishlist,
            'occasions' => config('wishlist.occasions'),
        ]);
    }

    /**
     * Резерв/снятие резерва гостем. Специально без привязки к аккаунту гостя
     * (его нет) — снять резерв может кто угодно, у кого есть ссылка на вишлист,
     * это компромисс ради простоты (см. обсуждение с пользователем).
     */
    public function toggleReserve(Request $request, string $uuid, WishlistItem $item)
    {
        $wishlist = Wishlist::where('uuid', $uuid)->firstOrFail();

        abort_unless($item->wishlist_id === $wishlist->id, 404);

        $request->validate([
            'reserved_by' => 'nullable|string|max:255',
        ]);

        if ($item->is_reserved) {
            $item->update(['is_reserved' => false, 'reserved_by' => null]);
        } else {
            $item->update(['is_reserved' => true, 'reserved_by' => $request->reserved_by ?: null]);
        }

        return response()->json(['status' => true, 'item' => WishlistController::formatItemForFrontend($item)]);
    }
}
