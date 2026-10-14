<?php

namespace App\Http\Controllers\Admin;

use App\Components\WardrobeImage;
use App\Http\Controllers\Controller;
use App\Models\PostClothes;
use App\Models\WardrobeColor;
use App\Models\WardrobeItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Intervention\Image\Facades\Image;

/**
 * Админка: справочник "Моего гардероба" с группами и образцами — общая часть для принтов
 * (WardrobePrintController) и материалов (WardrobeMaterialController). Общий для всех пользователей список
 * (группа, название, картинка-образец) и записи, которые пользователи добавили себе сами (их можно сделать
 * общими). Справочник выводится списком в форме вещи "Моего гардероба" и в полях пина "Одежда".
 * Модель — с трейтом App\Models\Concerns\WardrobeDirectory. Страница — admin/wardrobe-directory/index.
 */
abstract class WardrobeDirectoryController extends Controller
{
    // образец — маленький квадрат: в списке он 28 px, в админке 44 px
    private const SAMPLE_SIZE = 240;

    /** @return string класс модели справочника */
    abstract protected function modelClass(): string;

    /**
     * Настройки страницы: route — префикс имён роутов, folder — папка образцов в wardrobe/,
     * title — заголовок, noun/Noun — "принт"/"Принт" (мужской род: "… добавлен", "… общий"),
     * count — "Принтов", field — название поля у пользователя, swatchPrefix — приставка класса узора группы,
     * hex — true у цветов: вместо картинки-образца задаётся сам цвет (см. applyColor())
     *
     * @return array
     */
    abstract protected function directory(): array;

    public function index()
    {
        $model = $this->modelClass();
        $shared = $model::whereNull('user_id')->withCount(['items', 'postClothes'])->orderBy('id')->get();

        return view('admin.wardrobe-directory.index', [
            'dir' => $this->directory() + ['groups' => $model::GROUPS],
            // группы в порядке GROUPS
            'sharedByGroup' => collect($model::GROUPS)->map(function ($label, $group) use ($shared) {
                return $shared->where('group', $group)->values();
            }),
            'sharedTotal' => $shared->count(),
            'withImage' => $shared->filter(function ($entry) {
                // у цветов образец — не картинка, а сам цвет
                return $entry->image_url !== null || $entry->swatch_style !== null;
            })->count(),
            'userEntries' => $model::whereNotNull('user_id')->with('user:id,name,email')->withCount(['items', 'postClothes'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $model = $this->modelClass();
        $data = $this->validateData($request);
        $name = trim($data['name']);

        if ($model::whereNull('user_id')->where('name', $name)->exists()) {
            return back()->withInput()->withErrors(['name' => $this->message($name, 'уже есть в общем списке')]);
        }

        $entry = new $model(['name' => $name, 'group' => $data['group']]);
        if ($request->hasFile('image')) {
            $entry->image = json_encode($this->storeSample($request->file('image')));
        }
        $this->applyColor($entry, $request);
        // makeShared() заодно подберёт свои записи пользователей с таким названием
        $entry->makeShared();

        return back()->withSuccess($this->message($name, 'добавлен в общий список'));
    }

    public function update(Request $request, $entry)
    {
        $model = $this->modelClass();
        $entry = $this->find($entry);
        $data = $this->validateData($request);
        $name = trim($data['name']);

        $taken = $model::whereNull('user_id')->where('name', $name)->where('id', '!=', $entry->id)->exists();
        if ($entry->isShared() && $taken) {
            return back()->withErrors(['name' => $this->message($name, 'уже есть в общем списке')]);
        }

        $entry->name = $name;
        $entry->group = $data['group'];

        if ($request->hasFile('image')) {
            WardrobeImage::delete($entry->image_data);
            $entry->image = json_encode($this->storeSample($request->file('image')));
        } elseif ($request->boolean('remove_image')) {
            WardrobeImage::delete($entry->image_data);
            $entry->image = null;
        }
        $this->applyColor($entry, $request);
        $entry->save();

        return back()->withSuccess($this->message($name, 'сохранён'));
    }

    public function share($entry)
    {
        $entry = $this->find($entry);
        $entry->makeShared();

        return back()->withSuccess($this->message($entry->name, 'теперь общий'));
    }

    public function destroy($entry)
    {
        $model = $this->modelClass();
        $entry = $this->find($entry);

        WardrobeImage::delete($entry->image_data);

        // без внешних ключей в БД (как и везде в проекте) — у вещей и пинов значение снимаем вручную
        WardrobeItem::where($model::FOREIGN_KEY, $entry->id)->update([$model::FOREIGN_KEY => null]);
        PostClothes::where($model::FOREIGN_KEY, $entry->id)->update([$model::FOREIGN_KEY => null]);
        $entry->delete();

        return back()->withSuccess($this->message($entry->name, 'удалён'));
    }

    /**
     * @param Model|int|string $entry id из URL (или уже найденная запись)
     * @return Model
     */
    private function find($entry): Model
    {
        $model = $this->modelClass();

        return $entry instanceof Model ? $entry : $model::findOrFail($entry);
    }

    /**
     * Только у справочника цветов (directory()['hex']): сам цвет вместо картинки-образца.
     * no_hex — без образца ("Разноцветный"), has_hex2 — двухцветный ("Чёрно-белый").
     */
    private function applyColor(Model $entry, Request $request): void
    {
        if (empty($this->directory()['hex'])) {
            return;
        }

        $noHex = $request->boolean('no_hex');
        $entry->hex = $noHex ? null : WardrobeColor::normalizeHex($request->input('hex'));
        $entry->hex2 = !$noHex && $entry->hex !== null && $request->boolean('has_hex2')
            ? WardrobeColor::normalizeHex($request->input('hex2'))
            : null;
    }

    // "Принт «Тартан» добавлен в общий список"
    private function message(string $name, string $what): string
    {
        return $this->directory()['Noun'] . ' «' . $name . '» ' . $what;
    }

    /**
     * Образец: квадрат SAMPLE_SIZE из середины картинки, в wardrobe/{folder}/ (общая папка —
     * образцы общих записей не принадлежат пользователю)
     *
     * @param UploadedFile $file
     * @return array{name: string, extension: string, path: string}
     */
    private function storeSample(UploadedFile $file): array
    {
        $directory = 'wardrobe/' . $this->directory()['folder'];
        $name = Str::random(20);
        $relativePath = '/' . $directory . '/' . $name . '.jpg';

        Storage::disk('local')->makeDirectory($directory);
        Image::make($file->getRealPath())
            ->fit(self::SAMPLE_SIZE, self::SAMPLE_SIZE)
            ->encode('jpg', 85)
            ->save(Storage::disk('local')->path($relativePath));

        return ['name' => $name, 'extension' => 'jpg', 'path' => $relativePath];
    }

    private function validateData(Request $request): array
    {
        $model = $this->modelClass();

        return $request->validate([
            'name' => 'required|string|max:150',
            'group' => ['required', Rule::in(array_keys($model::GROUPS))],
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'remove_image' => 'nullable|boolean',
            'hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'hex2' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'no_hex' => 'nullable|boolean',
            'has_hex2' => 'nullable|boolean',
        ]);
    }
}
