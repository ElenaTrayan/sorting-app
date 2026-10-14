<?php

namespace App\Http\Controllers\Admin;

use App\Components\WardrobeImage;
use App\Http\Controllers\Controller;
use App\Models\PostClothes;
use App\Models\WardrobeBrand;
use App\Models\WardrobeItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Админка → Бренды одежды: общий для всех пользователей список брендов "Моего гардероба" (название и логотип)
 * и бренды, которые пользователи добавили себе сами (их можно сделать общими).
 */
class WardrobeBrandController extends Controller
{
    // логотип вписывается в квадрат целиком, без обрезки: на карточке вещи он высотой 14–22 px
    private const LOGO_SIZE = 400;

    public function index()
    {
        $sharedBrands = WardrobeBrand::whereNull('user_id')->withCount('items')->orderBy('name')->get();

        return view('admin.wardrobe-brands.index', [
            'sharedBrands' => $sharedBrands,
            'withLogo' => $sharedBrands->filter(function (WardrobeBrand $brand) {
                return $brand->image_url !== null;
            })->count(),
            'userBrands' => WardrobeBrand::whereNotNull('user_id')->with('user:id,name,email')->withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $name = trim($data['name']);

        if (WardrobeBrand::whereNull('user_id')->where('name', $name)->exists()) {
            return back()->withInput()->withErrors(['name' => 'Бренд «' . $name . '» уже есть в общем списке']);
        }

        $brand = new WardrobeBrand(['name' => $name]);
        if ($request->hasFile('image')) {
            $brand->image = json_encode($this->storeLogo($request->file('image')));
        }
        // makeShared() заодно подберёт свои бренды пользователей с таким названием
        $brand->makeShared();

        return back()->withSuccess('Бренд «' . $name . '» добавлен в общий список');
    }

    public function update(Request $request, WardrobeBrand $brand)
    {
        $data = $this->validateData($request);
        $name = trim($data['name']);

        if (WardrobeBrand::whereNull('user_id')->where('name', $name)->where('id', '!=', $brand->id)->exists()) {
            return back()->withErrors(['name' => 'Бренд «' . $name . '» уже есть в общем списке']);
        }

        $brand->name = $name;
        if ($request->hasFile('image')) {
            WardrobeImage::delete($brand->image_data);
            $brand->image = json_encode($this->storeLogo($request->file('image')));
        } elseif ($request->boolean('remove_image')) {
            WardrobeImage::delete($brand->image_data);
            $brand->image = null;
        }
        $brand->save();

        return back()->withSuccess('Бренд «' . $name . '» сохранён');
    }

    public function share(WardrobeBrand $brand)
    {
        $brand->makeShared();

        return back()->withSuccess('Бренд «' . $brand->name . '» теперь общий');
    }

    public function destroy(WardrobeBrand $brand)
    {
        WardrobeImage::delete($brand->image_data);

        // без внешних ключей в БД (как и везде в проекте) — у вещей и пинов бренд снимаем вручную
        WardrobeItem::where('brand_id', $brand->id)->update(['brand_id' => null]);
        PostClothes::where('brand_id', $brand->id)->update(['brand_id' => null]);
        $brand->delete();

        return back()->withSuccess('Бренд «' . $brand->name . '» удалён');
    }

    /**
     * Логотип в wardrobe/brands/ (общая папка — логотипы общих брендов не принадлежат пользователю).
     * SVG сохраняется как есть (вектор, масштабируется браузером); растровая картинка вписывается в квадрат
     * LOGO_SIZE без обрезки и сохраняется в PNG — прозрачный фон остаётся.
     *
     * @param UploadedFile $file
     * @return array{name: string, extension: string, path: string}
     */
    private function storeLogo(UploadedFile $file): array
    {
        $directory = 'wardrobe/brands';
        $name = Str::random(20);

        Storage::disk('local')->makeDirectory($directory);

        if ($this->isSvg($file)) {
            $file->storeAs($directory, $name . '.svg', 'local');

            return ['name' => $name, 'extension' => 'svg', 'path' => '/' . $directory . '/' . $name . '.svg'];
        }

        $relativePath = '/' . $directory . '/' . $name . '.png';
        Image::make($file->getRealPath())
            ->resize(self::LOGO_SIZE, self::LOGO_SIZE, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('png')
            ->save(Storage::disk('local')->path($relativePath));

        return ['name' => $name, 'extension' => 'png', 'path' => $relativePath];
    }

    private function isSvg(UploadedFile $file): bool
    {
        return strtolower($file->getClientOriginalExtension()) === 'svg'
            && stripos((string)file_get_contents($file->getRealPath()), '<svg') !== false;
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:150',
            'image' => ['bail', 'nullable', 'file', 'max:10240', function ($attribute, $file, $fail) {
                if ($this->isSvg($file)) {
                    // SVG — это разметка: со скриптами внутри его нельзя отдавать с сайта
                    if (preg_match('~<script|<foreignObject|<iframe|<embed|<object|\son\w+\s*=|javascript:~i', file_get_contents($file->getRealPath()))) {
                        $fail('В этом SVG есть скрипты или встроенные объекты — сохраните логотип как простой SVG без них.');
                    }
                    return;
                }
                if (!in_array(strtolower((string)$file->guessExtension()), ['jpeg', 'jpg', 'png', 'gif', 'webp'], true)) {
                    $fail('Логотип должен быть картинкой: SVG, PNG, JPG, GIF или WebP.');
                }
            }],
            'remove_image' => 'nullable|boolean',
        ], [
            'image.max' => 'Логотип слишком большой — не больше 10 МБ.',
        ]);
    }
}
