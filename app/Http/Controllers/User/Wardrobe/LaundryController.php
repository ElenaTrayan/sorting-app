<?php

namespace App\Http\Controllers\User\Wardrobe;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WardrobeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Мой гардероб" → Стирка: вещи по группам стирки (что стирать вместе), сначала давно не стиранные;
 * отметка "постирала" — у выбранных вещей группы разом; особая стирка (пятна) — отдельным блоком.
 * Только вещи, которые есть (не "нужно купить"). "Сегодня" — по часовому поясу пользователя (User::today).
 */
class LaundryController extends Controller
{
    public function index()
    {
        $items = WardrobeItem::where('user_id', Auth::id())
            ->where('status', WardrobeItem::STATUS_OWNED)
            ->with(['color', 'photos'])
            ->get();

        // давно не стиранные первыми: без даты, потом по дате стирки
        $items = $items->sortBy(function (WardrobeItem $item) {
            return $item->washed_at ? $item->washed_at->format('Y-m-d') : '0000-00-00';
        })->values();

        $groups = [];
        foreach (WardrobeItem::WASH_GROUPS as $key => $label) {
            $groups[$key] = ['label' => $label, 'items' => collect()];
        }
        $noGroup = collect();
        foreach ($items as $item) {
            $group = $item->washGroup();
            if ($group !== null && isset($groups[$group])) {
                $groups[$group]['items']->push($item);
            } else {
                $noGroup->push($item);
            }
        }

        return view('user.wardrobe.laundry', [
            'groups' => array_filter($groups, function ($group) {
                return $group['items']->isNotEmpty();
            }),
            'noGroup' => $noGroup,
            'specialWash' => $items->filter(function (WardrobeItem $item) {
                return $item->needsSpecialWash();
            })->values(),
        ]);
    }

    /**
     * "Постирала": сегодняшняя дата стирки у выбранных вещей (ids[]) — своих
     */
    public function markWashed(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|max:500',
            'ids.*' => 'integer',
        ], [
            'ids.required' => 'Выберите вещи, которые постирали.',
        ]);

        $count = WardrobeItem::where('user_id', Auth::id())
            ->whereIn('id', $data['ids'])
            ->update(['washed_at' => User::today()->toDateString()]);

        return response()->json(['success' => true, 'count' => $count]);
    }

    // "Постирала сегодня" у одной вещи (меню карточки)
    public function markItemWashed(WardrobeItem $wardrobeItem)
    {
        $wardrobeItem->update(['washed_at' => User::today()->toDateString()]);

        return response()->json(['success' => true]);
    }

    // особая стирка помогла — пятно исправлено (сама отметка о пятне остаётся в вещи)
    public function markSpecialWashFixed(WardrobeItem $wardrobeItem)
    {
        $wardrobeItem->update(['special_wash_fixed' => true]);

        return response()->json(['success' => true]);
    }
}
