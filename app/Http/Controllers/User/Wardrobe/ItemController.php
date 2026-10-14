<?php

namespace App\Http\Controllers\User\Wardrobe;

use App\Components\WardrobeImage;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\WardrobeBrand;
use App\Models\WardrobeCategory;
use App\Models\WardrobeColor;
use App\Models\WardrobeItem;
use App\Models\WardrobeMaterial;
use App\Models\WardrobeOutfit;
use App\Models\WardrobePrint;
use App\Models\WardrobeStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * "Мой гардероб" → Одежда: вещи, которые есть, и вещи, которые нужно купить.
 * Чужие вещи в URL отсекает привязка wardrobeItem (RouteServiceProvider).
 */
class ItemController extends Controller
{
    // фото у одной вещи (галерея в форме вещи)
    private const MAX_PHOTOS = 20;

    public function index(Request $request)
    {
        $userId = Auth::id();
        $activeStatus = array_key_exists($request->get('status'), WardrobeItem::STATUSES) ? $request->get('status') : 'all';

        WardrobeStorage::ensureDefaultsFor($userId);
        $storages = WardrobeStorage::where('user_id', $userId)->orderBy('sort_order')->orderBy('id')->get(['id', 'name']);

        $query = WardrobeItem::where('user_id', $userId)->with(['brand', 'storage', 'color', 'print', 'material', 'photos']);

        if ($activeStatus !== 'all') {
            $query->where('status', $activeStatus);
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('composition', 'like', "%{$search}%")
                    ->orWhereHas('color', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('brand', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('material', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('print', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }
        foreach (['color' => 'color_id', 'print' => 'print_id', 'material' => 'material_id'] as $param => $column) {
            if ($request->get($param) === 'none') {
                $query->whereNull($column);
            } elseif ($request->filled($param)) {
                $query->where($column, (int)$request->get($param));
            }
        }
        if ($request->get('brand') === 'none') {
            $query->whereNull('brand_id');
        } elseif ($request->filled('brand')) {
            $query->where('brand_id', (int)$request->get('brand'));
        }
        if ($request->get('storage') === 'none') {
            $query->whereNull('storage_id');
        } elseif ($request->filled('storage')) {
            $query->where('storage_id', (int)$request->get('storage'));
        }
        foreach (['category', 'wear_level'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->get($field));
            }
        }
        // styles/body_types — JSON-массивы ключей в текстовой колонке
        foreach (['style' => 'styles', 'body_type' => 'body_types'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, 'like', '%"' . $request->get($param) . '"%');
            }
        }
        if ($request->boolean('needs_repair')) {
            $query->where('needs_repair', true);
        }

        $items = $query->orderByDesc('id')->get();

        $statusCounts = WardrobeItem::where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('user.wardrobe.items', [
            'items' => $items,
            'activeStatus' => $activeStatus,
            'statusCounts' => $statusCounts,
            // фильтр — только по брендам, которые есть у вещей пользователя
            'usedBrands' => WardrobeBrand::whereIn('id', WardrobeItem::where('user_id', $userId)->whereNotNull('brand_id')->select('brand_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            // то же для цветов — в порядке справочника (по оттенкам), с образцом
            'usedColors' => WardrobeColor::whereIn('id', WardrobeItem::where('user_id', $userId)->whereNotNull('color_id')->select('color_id'))
                ->orderBy('id')
                ->get(),
            // то же для принтов (сам список принтов для формы — parts/print_select.blade.php)
            'usedPrints' => WardrobePrint::whereIn('id', WardrobeItem::where('user_id', $userId)->whereNotNull('print_id')->select('print_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'usedMaterials' => WardrobeMaterial::whereIn('id', WardrobeItem::where('user_id', $userId)->whereNotNull('material_id')->select('material_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'storages' => $storages,
            'filters' => $request->only(['search', 'category', 'brand', 'color', 'print', 'material', 'storage', 'wear_level', 'style', 'body_type', 'needs_repair']),
            'fromPost' => $this->fromPostImage($request),
            // ?edit_item=ID — сразу открыть форму этой вещи (ссылка "В гардеробе" со страницы поста)
            'editItemId' => (int)$request->get('edit_item') ?: null,
        ]);
    }

    /**
     * ?from_post=ID&image=N — кнопка "В гардероб" у пина "Одежда" (страница поста, карточка в списке постов):
     * форма новой вещи откроется сразу, со всеми картинками поста (картинка N — первой, обложкой; лишние
     * можно убрать в галерее формы) и его названием (wardrobe.js).
     * null — параметра нет, пост чужой или без картинок
     *
     * @param Request $request
     * @return array|null {post_id, index, title, thumb_url, images: [{index, thumb_url}], clothes}
     */
    private function fromPostImage(Request $request): ?array
    {
        if (!$request->filled('from_post')) {
            return null;
        }

        $post = Post::where('user_id', Auth::id())->find((int)$request->get('from_post'));

        return $post ? $this->postImportData($post, (int)$request->get('image', 0)) : null;
    }

    /**
     * Кнопка "Импорт из пина" в форме новой вещи: то же, что "В гардероб" у пина, но пин выбирают прямо
     * в гардеробе (окно parts/post_picker в режиме пинов, см. postImages()). ?post=ID&image=N →
     * {post: {...}} — как data-from-post, форму заполняет wardrobe.js (applyPostImage)
     */
    public function postImport(Request $request)
    {
        $post = Post::where('user_id', Auth::id())->find((int)$request->get('post'));
        $data = $post ? $this->postImportData($post, (int)$request->get('image', 0)) : null;

        if ($data === null) {
            return response()->json(['message' => 'Не удалось взять данные из пина — у него нет картинок.'], 422);
        }

        return response()->json(['post' => $data]);
    }

    /**
     * Название вещи из пина: название пина, а если его нет — "Свитшот Zara" (тип вещи + бренд)
     */
    private function postItemTitle(Post $post): string
    {
        $title = trim((string)$post->title);
        if ($title === '' && $post->clothes) {
            $title = trim($post->clothes->kind . ' ' . optional($post->clothes->brand)->name);
        }

        return $title;
    }

    /**
     * Данные пина для формы новой вещи: все картинки (картинка $index — первой) и поля пина "Одежда".
     * null — у поста нет картинок
     *
     * @param Post $post свой пост пользователя
     * @param int $index
     * @return array|null {post_id, index, title, thumb_url, images: [{index, thumb_url}], clothes}
     */
    private function postImportData(Post $post, int $index): ?array
    {
        $images = WardrobeImage::postImages($post);
        if (empty($images)) {
            return null;
        }

        if (!isset($images[$index])) {
            $index = array_key_first($images);
        }

        // поля пина "Одежда" (бренд, категория по типу вещи, цвет...) — сразу в форму вещи
        $clothes = $post->clothes ? $post->clothes->toWardrobeForm() : null;
        $title = $this->postItemTitle($post);

        // выбранная картинка — первой (обложка), остальные — по порядку поста
        $allImages = [['index' => $index, 'thumb_url' => '/storage' . $images[$index]['thumb']]];
        foreach ($images as $i => $image) {
            if ($i !== $index) {
                $allImages[] = ['index' => $i, 'thumb_url' => '/storage' . $image['thumb']];
            }
        }

        return [
            'post_id' => $post->id,
            'index' => $index,
            'title' => $title,
            'thumb_url' => $allImages[0]['thumb_url'],
            'images' => array_slice($allImages, 0, self::MAX_PHOTOS),
            'clothes' => $clothes,
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $userId = Auth::id();

        // фото проверяем до сохранения вещи: неподходящая картинка поста — ошибка, вещь не создаётся
        $plan = $this->photosPlan($request, null);
        if (is_string($plan)) {
            return response()->json(['message' => $plan], 422);
        }

        $item = new WardrobeItem($this->attributesFrom($data));
        $item->user_id = $userId;
        $item->save();

        $this->applyPhotos($item, $plan);
        $this->applyCompatible($request, $item);

        return response()->json(['success' => true, 'item' => $item->fresh('photos')->toFormArray()]);
    }

    public function update(Request $request, WardrobeItem $wardrobeItem)
    {
        $data = $this->validateData($request);

        $plan = $this->photosPlan($request, $wardrobeItem);
        if (is_string($plan)) {
            return response()->json(['message' => $plan], 422);
        }

        $wardrobeItem->fill($this->attributesFrom($data));
        $wardrobeItem->save();

        $this->applyPhotos($wardrobeItem, $plan);
        $this->applyCompatible($request, $wardrobeItem);

        return response()->json(['success' => true, 'item' => $wardrobeItem->fresh('photos')->toFormArray()]);
    }

    // "сочетается с" из формы вещи; без поля compatible_ids (быстрая вещь в конструкторе образа) — не трогаем
    private function applyCompatible(Request $request, WardrobeItem $item): void
    {
        if (!$request->has('compatible_ids')) {
            return;
        }

        $ids = json_decode((string)$request->input('compatible_ids'), true);
        $item->syncCompatible(is_array($ids) ? $ids : []);
    }

    public function destroy(WardrobeItem $wardrobeItem)
    {
        // обложка (колонка image) — копия первого фото, тот же файл: удаляем файлы всех фото
        foreach ($wardrobeItem->photos as $photo) {
            WardrobeImage::delete($photo->image_data);
        }
        WardrobeImage::delete($wardrobeItem->image_data);

        // без внешних ключей в БД (как и везде в проекте) — фото и связи с образами убираем вручную
        $wardrobeItem->photos()->delete();
        DB::table('wardrobe_outfit_item')->where('item_id', $wardrobeItem->id)->delete();
        $wardrobeItem->detachCompatible();
        $wardrobeItem->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Превью вещи для галереи "Мой гардероб" (parts/item_preview.blade.php): клик по фото карточки открывает
     * его в Fancybox (data-type="ajax"), стрелки — к соседним вещам в порядке сетки, как в "Все посты"
     */
    public function preview(WardrobeItem $wardrobeItem)
    {
        $wardrobeItem->load(['photos', 'brand', 'storage', 'color', 'print', 'material']);

        return view('user.wardrobe.parts.item_preview', ['item' => $wardrobeItem]);
    }

    // "Купила" — вещь из списка покупок переезжает в гардероб
    public function markBought(WardrobeItem $wardrobeItem)
    {
        $wardrobeItem->status = WardrobeItem::STATUS_OWNED;
        if (!$wardrobeItem->wear_level) {
            $wardrobeItem->wear_level = 'new';
        }
        $wardrobeItem->save();

        return response()->json(['success' => true]);
    }

    /**
     * Картинки постов пользователя для окна "Выбрать из постов" (parts/post_picker.blade.php):
     * search — по названию поста или хештегу, у карусели каждая картинка отдельно.
     * Выбранную картинку форма вещи присылает как post_id + post_image_index (см. applyImage()).
     * ?mode=pins — для "Импорт из пина": только пины типа "Одежда", по одной плитке на пин (обложка),
     * in_wardrobe — вещь из этого пина уже есть в гардеробе.
     */
    public function postImages(Request $request)
    {
        $search = trim((string)$request->get('search'));
        $pinsOnly = $request->get('mode') === 'pins';

        $posts = Post::where('user_id', Auth::id())
            ->where(function ($q) {
                $q->whereNotNull('original_image')->orWhereNotNull('medium_image')->orWhereNotNull('small_image');
            })
            ->when($pinsOnly, function ($q) {
                $q->where('type', Post::TYPE_CLOTHES)->with('clothes.brand')->withCount('wardrobeItems');
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhereHas('hashtags', function ($q) use ($search) {
                            $q->where('hashtags.title', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id')
            ->simplePaginate(30, ['id', 'title', 'original_image', 'medium_image', 'small_image']);

        $images = [];
        foreach ($posts as $post) {
            $postImages = WardrobeImage::postImages($post);

            if ($pinsOnly) {
                if ($postImages) {
                    $first = array_key_first($postImages);
                    $images[] = [
                        'post_id' => $post->id,
                        'index' => $first,
                        'title' => $this->postItemTitle($post),
                        'thumb_url' => '/storage' . $postImages[$first]['thumb'],
                        'in_wardrobe' => $post->wardrobe_items_count > 0,
                    ];
                }
                continue;
            }

            foreach ($postImages as $index => $image) {
                $images[] = [
                    'post_id' => $post->id,
                    'index' => $index,
                    'title' => (string)$post->title,
                    'thumb_url' => '/storage' . $image['thumb'],
                ];
            }
        }

        return response()->json([
            'images' => $images,
            'next_page' => $posts->hasMorePages() ? $posts->currentPage() + 1 : null,
        ]);
    }

    /**
     * Фото вещи из запроса — итоговый список по порядку, первое — обложка. Галерея формы вещи (wardrobe.js)
     * присылает photos_order — JSON-массив:
     *   "id:12"    — уже сохранённое фото этой вещи (оставить, на эту позицию);
     *   "new:0"    — новый файл photos_new[0];
     *   "post:5:1" — картинка №1 поста 5 (копия файла, WardrobeImage::copyFromPost).
     * Сохранённых фото, которых нет в списке, больше нет — их удалит applyPhotos().
     * Без photos_order (быстрая вещь "Из постов" в конструкторе образа) — у новой вещи одно фото из
     * image или post_id + post_image_index, у существующей фото не меняются.
     *
     * @param Request $request
     * @param WardrobeItem|null $item null — новая вещь
     * @return array|string|null список шагов для applyPhotos(); строка — текст ошибки; null — фото не трогать
     */
    private function photosPlan(Request $request, ?WardrobeItem $item)
    {
        $userId = (int)Auth::id();

        if ($request->has('photos_order')) {
            $tokens = json_decode((string)$request->input('photos_order'), true);
            if (!is_array($tokens)) {
                return 'Не удалось прочитать список фото — откройте форму заново.';
            }
        } elseif ($item === null && $request->hasFile('image')) {
            $tokens = ['legacy-file'];
        } elseif ($item === null && $request->filled('post_id')) {
            $tokens = ['post:' . (int)$request->input('post_id') . ':' . (int)$request->input('post_image_index', 0)];
        } elseif ($item === null) {
            $tokens = [];
        } else {
            return null;
        }

        if (count($tokens) > self::MAX_PHOTOS) {
            return 'У вещи может быть не больше ' . self::MAX_PHOTOS . ' фото.';
        }

        $existing = $item ? $item->photos()->get()->keyBy('id') : collect();
        $newFiles = $request->file('photos_new', []);
        $plan = [];

        // повороты/отражения фото (кнопки на фото в форме, wardrobe.js) — по тем же позициям, что и photos_order:
        // [{"r": 1, "f": 0}, ...], r — поворотов на 90° по часовой, f — отразить по горизонтали
        $transforms = json_decode((string)$request->input('photos_transform', '[]'), true);
        $transforms = is_array($transforms) ? array_values($transforms) : [];

        foreach (array_values($tokens) as $index => $token) {
            $parts = explode(':', (string)$token);
            $transform = [
                'rotate' => (int)($transforms[$index]['r'] ?? 0),
                'flip' => !empty($transforms[$index]['f']),
            ];

            if ($parts[0] === 'id' && isset($parts[1], $existing[(int)$parts[1]])) {
                $plan[] = ['type' => 'existing', 'photo' => $existing[(int)$parts[1]]] + $transform;
            } elseif ($parts[0] === 'new' && isset($parts[1], $newFiles[(int)$parts[1]])) {
                $plan[] = ['type' => 'file', 'file' => $newFiles[(int)$parts[1]]] + $transform;
            } elseif ($parts[0] === 'legacy-file') {
                $plan[] = ['type' => 'file', 'file' => $request->file('image')] + $transform;
            } elseif ($parts[0] === 'post' && isset($parts[1], $parts[2])) {
                $post = Post::where('user_id', $userId)->find((int)$parts[1]);
                if (!$post || !isset(WardrobeImage::postImages($post)[(int)$parts[2]])) {
                    return 'Не удалось взять картинку из поста — выберите другую.';
                }
                $plan[] = ['type' => 'post', 'post' => $post, 'index' => (int)$parts[2]] + $transform;
            }
            // чужое/неизвестное фото просто пропускаем
        }

        return $plan;
    }

    /**
     * Применить список фото (photosPlan): новые — сохранить, порядок — обновить, убранные — удалить вместе
     * с файлами; обложка — первое фото (WardrobeItem::syncCover). Вещь уже сохранена (нужен id)
     *
     * @param WardrobeItem $item
     * @param array|null $plan
     * @return void
     */
    private function applyPhotos(WardrobeItem $item, ?array $plan): void
    {
        if ($plan === null) {
            return;
        }

        $userId = (int)$item->user_id;
        $keptIds = [];
        $position = 0;

        foreach ($plan as $step) {
            if ($step['type'] === 'existing') {
                $changes = ['sort_order' => $position++];
                // фото повернули/отразили в форме — файл заменяется новым (WardrobeImage::transform)
                if (($step['rotate'] % 4 !== 0 || $step['flip']) && $step['photo']->image_data) {
                    $changes['image'] = json_encode(WardrobeImage::transform($step['photo']->image_data, $step['rotate'], $step['flip']));
                }
                $step['photo']->update($changes);
                $keptIds[] = $step['photo']->id;
                continue;
            }

            if ($step['type'] === 'file') {
                $image = WardrobeImage::store($step['file'], $userId);
            } else {
                $image = WardrobeImage::copyFromPost($step['post'], $step['index'], $userId);
                // вещь "из поста" — по нему страница поста показывает "В гардеробе"
                if ($image !== null && !$item->post_id) {
                    $item->post_id = $step['post']->id;
                }
            }

            if ($image !== null) {
                $image = WardrobeImage::transform($image, $step['rotate'], $step['flip']);
                $photo = $item->photos()->create(['image' => json_encode($image), 'sort_order' => $position++]);
                $keptIds[] = $photo->id;
            }
        }

        foreach ($item->photos()->whereNotIn('id', $keptIds)->get() as $removed) {
            WardrobeImage::delete($removed->image_data);
            $removed->delete();
        }

        $item->syncCover();
    }

    private function attributesFrom(array $data): array
    {
        $needsRepair = !empty($data['needs_repair']);

        return [
            'name' => $data['name'],
            'category' => $data['category'],
            'status' => $data['status'],
            'brand_id' => $this->brandIdFrom($data),
            'storage_id' => $this->storageIdFrom($data),
            'color_id' => WardrobeColor::resolveFromForm($data['color_id'] ?? null, $data['color_new'] ?? null, (int)Auth::id(), $data['color_hex'] ?? null),
            // список размеров (parts/size_select): размер из списка или "new" + свой размер
            'size' => (($data['size'] ?? null) === 'new' ? ($data['size_new'] ?? null) : ($data['size'] ?? null)) ?: null,
            'length' => $data['length'] ?? null,
            // в порядке списка, а не в порядке отметки
            'seasons' => array_values(array_intersect(array_keys(WardrobeOutfit::SEASONS), $data['seasons'] ?? [])),
            'temp_min' => $data['temp_min'] ?? null,
            'temp_max' => $data['temp_max'] ?? null,
            'occasions' => array_values(array_intersect(array_keys(WardrobeItem::OCCASIONS), $data['occasions'] ?? [])),
            'print_id' => WardrobePrint::resolveFromForm($data['print_id'] ?? null, $data['print_new'] ?? null, (int)Auth::id()),
            'material_id' => WardrobeMaterial::resolveFromForm($data['material_id'] ?? null, $data['material_new'] ?? null, (int)Auth::id()),
            'composition' => $data['composition'] ?? null,
            'care' => $data['care'] ?? null,
            // в порядке списка, а не в порядке отметки
            'care_options' => array_values(array_intersect(array_keys(WardrobeItem::careOptions()), $data['care_options'] ?? [])),
            'wear_level' => $data['wear_level'] ?? null,
            'needs_repair' => $needsRepair,
            'repair_note' => $needsRepair ? ($data['repair_note'] ?? null) : null,
            'styles' => array_values($data['styles'] ?? []),
            'body_types' => array_values($data['body_types'] ?? []),
            'price' => $data['price'] ?? null,
            'shop_url' => $data['shop_url'] ?? null,
            'note' => $data['note'] ?? null,
        ];
    }

    /**
     * Бренд из формы: по умолчанию "Без бренда" (null); brand_id — бренд из списка (общий или свой);
     * brand_id = "new" — свой бренд с названием из поля brand (WardrobeBrand::resolveId)
     */
    private function brandIdFrom(array $data): ?int
    {
        return WardrobeBrand::resolveFromForm($data['brand_id'] ?? null, $data['brand'] ?? null, (int)Auth::id());
    }

    /**
     * Место хранения из формы: storage_id — место из списка пользователя; storage_id = "new" —
     * новое место с названием из поля storage_name (WardrobeStorage::resolveId); пусто — не указано
     */
    private function storageIdFrom(array $data): ?int
    {
        $userId = (int)Auth::id();
        $storageId = $data['storage_id'] ?? null;

        if ($storageId === 'new') {
            return WardrobeStorage::resolveId($data['storage_name'] ?? null, $userId);
        }
        if (!$storageId) {
            return null;
        }

        return WardrobeStorage::where('user_id', $userId)->whereKey((int)$storageId)->value('id');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => ['required', Rule::in(array_keys(WardrobeCategory::options()))],
            'status' => ['required', Rule::in(array_keys(WardrobeItem::STATUSES))],
            // галерея формы вещи — см. photosPlan()
            'photos_order' => 'nullable|string|max:5000',
            'photos_transform' => 'nullable|string|max:5000',
            'photos_new' => 'nullable|array|max:' . self::MAX_PHOTOS,
            'photos_new.*' => 'file|mimes:jpeg,png,jpg,gif,webp,avif|max:20480',
            // одно фото — быстрая вещь "Из постов" в конструкторе образа
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,avif|max:20480',
            'post_id' => 'nullable|integer',
            'post_image_index' => 'nullable|integer|min:0',
            'brand_id' => ['nullable', 'regex:/^(\d+|new)$/'],
            'brand' => 'required_if:brand_id,new|nullable|string|max:150',
            'storage_id' => ['nullable', 'regex:/^(\d+|new)$/'],
            'storage_name' => 'required_if:storage_id,new|nullable|string|max:255',
            'color_id' => ['nullable', 'regex:/^(\d+|new)$/'],
            'color_new' => 'required_if:color_id,new|nullable|string|max:150',
            'color_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'size' => 'nullable|string|max:50',
            'size_new' => 'required_if:size,new|nullable|string|max:50',
            'length' => 'nullable|string|max:50',
            'seasons' => 'nullable|array',
            'seasons.*' => [Rule::in(array_keys(WardrobeOutfit::SEASONS))],
            'temp_min' => 'nullable|integer|between:-60,60',
            'temp_max' => 'nullable|integer|between:-60,60',
            'occasions' => 'nullable|array',
            'occasions.*' => [Rule::in(array_keys(WardrobeItem::OCCASIONS))],
            // "сочетается с" — JSON-массив id вещей гардероба (обувь, аксессуары, остальное — одним списком)
            'compatible_ids' => 'nullable|string|max:5000',
            'print_id' => ['nullable', 'regex:/^(\d+|new)$/'],
            'print_new' => 'required_if:print_id,new|nullable|string|max:150',
            'material_id' => ['nullable', 'regex:/^(\d+|new)$/'],
            'material_new' => 'required_if:material_id,new|nullable|string|max:150',
            'composition' => 'nullable|string|max:255',
            'care' => 'nullable|string|max:2000',
            'care_options' => 'nullable|array',
            'care_options.*' => [Rule::in(array_keys(WardrobeItem::careOptions()))],
            'wear_level' => ['nullable', Rule::in(array_keys(WardrobeItem::WEAR_LEVELS))],
            'needs_repair' => 'nullable|boolean',
            'repair_note' => 'nullable|string|max:2000',
            'styles' => 'nullable|array',
            'styles.*' => [Rule::in(array_keys(WardrobeItem::STYLES))],
            'body_types' => 'nullable|array',
            'body_types.*' => [Rule::in(array_keys(WardrobeItem::BODY_TYPES))],
            'price' => 'nullable|numeric|min:0',
            'shop_url' => 'nullable|string|max:1000',
            'note' => 'nullable|string|max:2000',
        ], [
            'brand.required_if' => 'Впишите название своего бренда или выберите «Без бренда».',
            'size_new.required_if' => 'Впишите свой размер или выберите размер из списка.',
            'color_new.required_if' => 'Впишите название своего цвета или выберите цвет из списка.',
            'print_new.required_if' => 'Впишите название своего принта или выберите принт из списка.',
            'material_new.required_if' => 'Впишите название своего материала или выберите материал из списка.',
            'storage_name.required_if' => 'Впишите название нового места хранения или выберите «Не указано».',
            'photos_new.*.mimes' => 'Фото должно быть картинкой: JPG, PNG, GIF или WebP.',
            'photos_new.*.max' => 'Фото слишком большое — не больше 20 МБ.',
            'photos_new.max' => 'За один раз можно добавить не больше ' . self::MAX_PHOTOS . ' фото.',
        ]);

        // как у образов: правило gte:temp_min не подходит — границу можно указать и одну
        if (isset($data['temp_min'], $data['temp_max']) && (int)$data['temp_max'] < (int)$data['temp_min']) {
            throw ValidationException::withMessages([
                'temp_max' => 'Верхняя граница температуры не может быть ниже нижней.',
            ]);
        }

        return $data;
    }
}
