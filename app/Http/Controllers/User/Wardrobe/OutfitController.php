<?php

namespace App\Http\Controllers\User\Wardrobe;

use App\Components\WardrobeImage;
use App\Http\Controllers\Controller;
use App\Models\WardrobeItem;
use App\Models\WardrobeOutfit;
use App\Models\WardrobeOutfitWear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * "Мой гардероб" → Образы: образ, из каких вещей он составлен, и куда его надевали.
 * Чужие образы в URL отсекают привязки wardrobeOutfit/wardrobeWear (RouteServiceProvider).
 */
class OutfitController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = in_array($request->get('tab'), ['ready', 'to_buy'], true) ? $request->get('tab') : 'all';
        $onlyWishlist = function ($q) {
            $q->where('wardrobe_items.status', WardrobeItem::STATUS_WISHLIST);
        };

        $query = WardrobeOutfit::where('user_id', Auth::id())->with('items')->withCount('wears');

        if ($activeTab === 'ready') {
            $query->whereDoesntHave('items', $onlyWishlist);
        } elseif ($activeTab === 'to_buy') {
            $query->whereHas('items', $onlyWishlist);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->get('search') . '%');
        }
        // seasons/body_types — JSON-массивы ключей в текстовой колонке
        foreach (['season' => 'seasons', 'body_type' => 'body_types'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, 'like', '%"' . $request->get($param) . '"%');
            }
        }
        // "на погоду": температура попадает в комфортный диапазон образа (незаданная граница — открытая)
        if ($request->filled('temp') && is_numeric($request->get('temp'))) {
            $temp = (int)$request->get('temp');
            $query->where(function ($q) {
                $q->whereNotNull('temp_min')->orWhereNotNull('temp_max');
            })->where(function ($q) use ($temp) {
                $q->whereNull('temp_min')->orWhere('temp_min', '<=', $temp);
            })->where(function ($q) use ($temp) {
                $q->whereNull('temp_max')->orWhere('temp_max', '>=', $temp);
            });
        }

        return view('user.wardrobe.outfits.index', [
            'outfits' => $query->orderByDesc('id')->get(),
            'activeTab' => $activeTab,
            'filters' => $request->only(['search', 'season', 'body_type', 'temp']),
        ]);
    }

    public function create()
    {
        return $this->formView(new WardrobeOutfit());
    }

    public function edit(WardrobeOutfit $wardrobeOutfit)
    {
        return $this->formView($wardrobeOutfit);
    }

    public function show(WardrobeOutfit $wardrobeOutfit)
    {
        $wardrobeOutfit->load(['items', 'wears']);

        return view('user.wardrobe.outfits.show', ['outfit' => $wardrobeOutfit]);
    }

    /**
     * Превью образа для галереи "Образы" (outfits/preview.blade.php): клик по обложке карточки открывает его
     * в Fancybox (data-type="ajax"), стрелки — к соседним образам в порядке сетки, как у вещей
     */
    public function preview(WardrobeOutfit $wardrobeOutfit)
    {
        $wardrobeOutfit->load(['items.brand', 'wears']);

        return view('user.wardrobe.outfits.preview', ['outfit' => $wardrobeOutfit]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $outfit = new WardrobeOutfit($this->attributesFrom($data));
        $outfit->user_id = Auth::id();
        $this->applyImage($request, $outfit);
        $outfit->save();

        $this->syncItems($outfit, $data['items']);

        return response()->json(['success' => true, 'redirect' => route('wardrobe.outfits.show', $outfit)]);
    }

    public function update(Request $request, WardrobeOutfit $wardrobeOutfit)
    {
        $data = $this->validateData($request);

        $wardrobeOutfit->fill($this->attributesFrom($data));
        $this->applyImage($request, $wardrobeOutfit);
        $wardrobeOutfit->save();

        $this->syncItems($wardrobeOutfit, $data['items']);

        return response()->json(['success' => true, 'redirect' => route('wardrobe.outfits.show', $wardrobeOutfit)]);
    }

    public function destroy(WardrobeOutfit $wardrobeOutfit)
    {
        WardrobeImage::delete($wardrobeOutfit->image_data);

        // без внешних ключей в БД (как и везде в проекте) — чистим связанные записи вручную
        $wardrobeOutfit->items()->detach();
        WardrobeOutfitWear::where('outfit_id', $wardrobeOutfit->id)->delete();
        $wardrobeOutfit->delete();

        return response()->json(['success' => true]);
    }

    // "Куда надевала" — запись на странице образа
    public function storeWear(Request $request, WardrobeOutfit $wardrobeOutfit)
    {
        $data = $request->validate([
            'place' => 'required|string|max:255',
            'worn_at' => 'nullable|date',
            'note' => 'nullable|string|max:2000',
        ]);

        WardrobeOutfitWear::create([
            'outfit_id' => $wardrobeOutfit->id,
            'place' => $data['place'],
            'worn_at' => $data['worn_at'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function destroyWear(WardrobeOutfitWear $wardrobeWear)
    {
        $wardrobeWear->delete();

        return response()->json(['success' => true]);
    }

    private function formView(WardrobeOutfit $outfit)
    {
        $items = WardrobeItem::where('user_id', Auth::id())->orderByDesc('id')->get();

        return view('user.wardrobe.outfits.form', [
            'outfit' => $outfit,
            'items' => $items,
            'selectedIds' => $outfit->exists ? $outfit->items()->pluck('wardrobe_items.id')->all() : [],
        ]);
    }

    /**
     * Вещи образа в том порядке, в каком их выбрали; чужие и несуществующие id отбрасываем
     */
    private function syncItems(WardrobeOutfit $outfit, array $itemIds): void
    {
        $ownIds = WardrobeItem::where('user_id', $outfit->user_id)->whereIn('id', $itemIds)->pluck('id')
            ->map(function ($id) { return (int)$id; })
            ->all();

        $sync = [];
        foreach (array_values(array_unique(array_map('intval', $itemIds))) as $order => $itemId) {
            if (in_array($itemId, $ownIds, true)) {
                $sync[$itemId] = ['sort_order' => $order];
            }
        }

        $outfit->items()->sync($sync);
    }

    // image_rotate / image_flip — фото (новое или уже сохранённое) повернули/отразили кнопками в форме
    private function applyImage(Request $request, WardrobeOutfit $outfit): void
    {
        $rotate = (int)$request->input('image_rotate', 0);
        $flip = $request->boolean('image_flip');

        if ($request->hasFile('image')) {
            WardrobeImage::delete($outfit->image_data);
            $image = WardrobeImage::store($request->file('image'), (int)Auth::id());
            $outfit->image = json_encode(WardrobeImage::transform($image, $rotate, $flip));
        } elseif ($request->boolean('remove_image')) {
            WardrobeImage::delete($outfit->image_data);
            $outfit->image = null;
        } elseif ($outfit->image_data) {
            $outfit->image = json_encode(WardrobeImage::transform($outfit->image_data, $rotate, $flip));
        }
    }

    private function attributesFrom(array $data): array
    {
        return [
            'name' => $data['name'],
            'seasons' => array_values($data['seasons'] ?? []),
            'temp_min' => $data['temp_min'] ?? null,
            'temp_max' => $data['temp_max'] ?? null,
            'body_types' => array_values($data['body_types'] ?? []),
            'note' => $data['note'] ?? null,
        ];
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,avif|max:20480',
            'remove_image' => 'nullable|boolean',
            'image_rotate' => 'nullable|integer|between:0,3',
            'image_flip' => 'nullable|boolean',
            'seasons' => 'nullable|array',
            'seasons.*' => [Rule::in(array_keys(WardrobeOutfit::SEASONS))],
            'temp_min' => 'nullable|integer|between:-60,60',
            'temp_max' => 'nullable|integer|between:-60,60',
            'body_types' => 'nullable|array',
            'body_types.*' => [Rule::in(array_keys(WardrobeItem::BODY_TYPES))],
            'note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*' => 'integer',
        ], [
            'items.required' => 'Добавьте в образ хотя бы одну вещь.',
        ]);

        // правило gte:temp_min не подходит — границу можно указать и одну
        if (isset($data['temp_min'], $data['temp_max']) && (int)$data['temp_max'] < (int)$data['temp_min']) {
            throw ValidationException::withMessages([
                'temp_max' => 'Верхняя граница температуры не может быть ниже нижней.',
            ]);
        }

        return $data;
    }
}
