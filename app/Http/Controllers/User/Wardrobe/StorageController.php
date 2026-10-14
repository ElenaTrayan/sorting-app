<?php

namespace App\Http\Controllers\User\Wardrobe;

use App\Components\WardrobeImage;
use App\Http\Controllers\Controller;
use App\Models\WardrobeItem;
use App\Models\WardrobeStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Мой гардероб" → Места хранения: свой список мест пользователя (добавить, переименовать, удалить).
 * Чужие места в URL отсекает привязка wardrobeStorage (RouteServiceProvider).
 */
class StorageController extends Controller
{
    public function index()
    {
        $userId = (int)Auth::id();
        WardrobeStorage::ensureDefaultsFor($userId);

        return view('user.wardrobe.storages', [
            'storages' => WardrobeStorage::where('user_id', $userId)->withCount('items')->orderBy('sort_order')->orderBy('id')->get(),
            'withoutStorage' => WardrobeItem::where('user_id', $userId)->where('status', WardrobeItem::STATUS_OWNED)->whereNull('storage_id')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $userId = (int)Auth::id();

        $storage = new WardrobeStorage([
            'user_id' => $userId,
            'name' => $data['name'],
            'note' => $data['note'] ?? null,
            'sort_order' => (int)WardrobeStorage::where('user_id', $userId)->max('sort_order') + 1,
        ]);
        $this->applyImage($request, $storage);
        $storage->save();

        return response()->json(['success' => true]);
    }

    public function update(Request $request, WardrobeStorage $wardrobeStorage)
    {
        $data = $this->validateData($request);

        $wardrobeStorage->fill([
            'name' => $data['name'],
            'note' => $data['note'] ?? null,
        ]);
        $this->applyImage($request, $wardrobeStorage);
        $wardrobeStorage->save();

        return response()->json(['success' => true]);
    }

    public function destroy(WardrobeStorage $wardrobeStorage)
    {
        WardrobeImage::delete($wardrobeStorage->image_data);

        // без внешних ключей в БД (как и везде в проекте) — у вещей место снимаем вручную
        WardrobeItem::where('storage_id', $wardrobeStorage->id)->update(['storage_id' => null]);
        $wardrobeStorage->delete();

        return response()->json(['success' => true]);
    }

    // фото места: новый файл заменяет старый, remove_image — убрать; image_rotate / image_flip — фото
    // (новое или уже сохранённое) повернули/отразили кнопками в форме
    private function applyImage(Request $request, WardrobeStorage $storage): void
    {
        $rotate = (int)$request->input('image_rotate', 0);
        $flip = $request->boolean('image_flip');

        if ($request->hasFile('image')) {
            WardrobeImage::delete($storage->image_data);
            $image = WardrobeImage::store($request->file('image'), (int)Auth::id());
            $storage->image = json_encode(WardrobeImage::transform($image, $rotate, $flip));
        } elseif ($request->boolean('remove_image')) {
            WardrobeImage::delete($storage->image_data);
            $storage->image = null;
        } elseif ($storage->image_data) {
            $storage->image = json_encode(WardrobeImage::transform($storage->image_data, $rotate, $flip));
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'note' => 'nullable|string|max:2000',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,avif|max:20480',
            'remove_image' => 'nullable|boolean',
            'image_rotate' => 'nullable|integer|between:0,3',
            'image_flip' => 'nullable|boolean',
        ]);
    }
}
