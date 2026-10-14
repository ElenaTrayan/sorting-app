<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WardrobeCategory;
use App\Models\WardrobeItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Админка → Категории одежды: общий для всех пользователей список категорий "Моего гардероба"
 * (название, порядок, типы вещей). См. App\Models\WardrobeCategory.
 */
class WardrobeCategoryController extends Controller
{
    public function index()
    {
        return view('admin.wardrobe-categories.index', [
            'categories' => WardrobeCategory::orderBy('sort_order')->orderBy('id')->get(),
            // вещей в категории — у всех пользователей
            'itemCounts' => WardrobeItem::select('category', DB::raw('COUNT(*) as total'))->groupBy('category')->pluck('total', 'category'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $name = trim($data['name']);

        if (WardrobeCategory::where('name', $name)->exists()) {
            return back()->withInput()->withErrors(['name' => 'Категория «' . $name . '» уже есть']);
        }

        WardrobeCategory::create([
            'slug' => WardrobeCategory::makeSlug($name),
            'name' => $name,
            'kinds' => WardrobeCategory::kindsFromText($data['kinds'] ?? null),
            // новая категория — в конец списка
            'sort_order' => (int)WardrobeCategory::max('sort_order') + 1,
        ]);

        return back()->withSuccess('Категория «' . $name . '» добавлена');
    }

    public function update(Request $request, WardrobeCategory $category)
    {
        $data = $this->validateData($request);
        $name = trim($data['name']);

        if (WardrobeCategory::where('name', $name)->where('id', '!=', $category->id)->exists()) {
            return back()->withErrors(['name' => 'Категория «' . $name . '» уже есть']);
        }

        // slug не меняется — по нему категорию помнят вещи
        $category->update(['name' => $name, 'kinds' => WardrobeCategory::kindsFromText($data['kinds'] ?? null)]);

        return back()->withSuccess('Категория «' . $name . '» сохранена');
    }

    /**
     * Передвинуть категорию на одну позицию: direction = up | down
     */
    public function move(Request $request, WardrobeCategory $category)
    {
        $ordered = WardrobeCategory::orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $ordered->search(function (WardrobeCategory $candidate) use ($category) {
            return $candidate->id === $category->id;
        });
        $target = $request->input('direction') === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && isset($ordered[$target])) {
            $moved = $ordered->all();
            [$moved[$index], $moved[$target]] = [$moved[$target], $moved[$index]];
            // перенумеровываем весь список: порядок остаётся однозначным, даже если номера совпадали
            foreach ($moved as $position => $item) {
                if ($item->sort_order !== $position + 1) {
                    $item->update(['sort_order' => $position + 1]);
                }
            }
        }

        return back();
    }

    public function destroy(WardrobeCategory $category)
    {
        if ($category->isFallback()) {
            return back()->withErrors(['name' => 'Категорию «' . $category->name . '» удалить нельзя: в неё попадают вещи удалённых категорий']);
        }

        // без внешних ключей в БД (как и везде в проекте) — вещи переносим вручную
        $moved = WardrobeItem::where('category', $category->slug)->update(['category' => WardrobeCategory::FALLBACK]);
        $category->delete();

        return back()->withSuccess('Категория «' . $category->name . '» удалена'
            . ($moved ? ', вещей перенесено в «' . WardrobeCategory::label(WardrobeCategory::FALLBACK) . '»: ' . $moved : ''));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:100',
            'kinds' => 'nullable|string|max:5000',
        ]);
    }
}
