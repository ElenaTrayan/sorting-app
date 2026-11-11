{{--
    Drawer "Фильтры" для списка одежды — та же механика, что в "Моих запасах"
    (stock/parts/inventory_filters.blade.php): обычный GET-сабмит формы, открытие/закрытие — my-stock.js.
--}}
@php use App\Models\WardrobeItem; @endphp
<div class="b-filter-panel" data-filter-panel="wardrobe-items">
    <div class="b-filter-panel__backdrop js-filter-panel-close"></div>
    <div class="b-filter-panel__drawer">
        <form method="GET" action="{{ route('wardrobe.items.index') }}">
            <input type="hidden" name="status" value="{{ $activeStatus }}">
            @if (!empty($filters['search']))<input type="hidden" name="search" value="{{ $filters['search'] }}">@endif

            <div class="b-filter-panel__header">
                <div class="b-filter-panel__title">Фильтры</div>
                <button type="button" class="b-filter-panel__close js-filter-panel-close" aria-label="Закрыть"><i class="fas fa-times"></i></button>
            </div>

            <div class="b-filter-panel__body">
                {{-- категории и типы — только те, что есть у вещей, со счётчиками ($kindCounts: category, kind, total) --}}
                @php
                    $categoryTotals = $kindCounts->groupBy('category')->map->sum('total');
                    $kindsByCategory = $kindCounts->whereNotNull('kind')->groupBy('category');
                @endphp
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Категория</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="category" value="" @if (empty($filters['category'])) checked @endif> Все</label>
                        @foreach (\App\Models\WardrobeCategory::options() as $slug => $label)
                            @if ($categoryTotals->has($slug) || ($filters['category'] ?? '') === $slug)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="category" value="{{ $slug }}" @if (($filters['category'] ?? '') === $slug) checked @endif>
                                    {{ $label }} <span class="b-filter-panel__count">{{ $categoryTotals[$slug] ?? 0 }}</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>

                @if ($kindsByCategory->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Тип вещи</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="kind" value="" @if (empty($filters['kind'])) checked @endif> Все</label>
                            @foreach (\App\Models\WardrobeCategory::options() as $slug => $label)
                                @if ($kindsByCategory->has($slug))
                                    <div class="b-filter-panel__subhead">{{ $label }}</div>
                                    @foreach ($kindsByCategory[$slug]->sortByDesc('total') as $row)
                                        <label class="b-filter-panel__option">
                                            <input type="radio" name="kind" value="{{ $row->kind }}" @if (($filters['kind'] ?? '') === $row->kind) checked @endif>
                                            {{ $row->kind }} <span class="b-filter-panel__count">{{ $row->total }}</span>
                                        </label>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @foreach ([
                    'style' => ['Стиль', WardrobeItem::STYLES],
                    'body_type' => ['Кому подходит', WardrobeItem::BODY_TYPES],
                    'wear_level' => ['Степень изношенности', WardrobeItem::WEAR_LEVELS],
                ] as $param => [$title, $options])
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">{{ $title }}</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="{{ $param }}" value="" @if (empty($filters[$param])) checked @endif> Все</label>
                            @foreach ($options as $slug => $label)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="{{ $param }}" value="{{ $slug }}" @if (($filters[$param] ?? '') === $slug) checked @endif> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if ($usedBrands->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Бренд</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="brand" value="" @if (empty($filters['brand'])) checked @endif> Все</label>
                            <label class="b-filter-panel__option"><input type="radio" name="brand" value="none" @if (($filters['brand'] ?? '') === 'none') checked @endif> Без бренда</label>
                            @foreach ($usedBrands as $brand)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="brand" value="{{ $brand->id }}" @if ((int)($filters['brand'] ?? 0) === $brand->id) checked @endif> {{ $brand->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($usedColors->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Цвет</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="color" value="" @if (empty($filters['color'])) checked @endif> Все</label>
                            <label class="b-filter-panel__option"><input type="radio" name="color" value="none" @if (($filters['color'] ?? '') === 'none') checked @endif> Не указан</label>
                            @foreach ($usedColors as $color)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="color" value="{{ $color->id }}" @if ((int)($filters['color'] ?? 0) === $color->id) checked @endif>
                                    @include('user.wardrobe.parts.color_label', ['color' => $color])
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($usedPrints->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Принт</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="print" value="" @if (empty($filters['print'])) checked @endif> Все</label>
                            <label class="b-filter-panel__option"><input type="radio" name="print" value="none" @if (($filters['print'] ?? '') === 'none') checked @endif> Не указан</label>
                            @foreach ($usedPrints as $print)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="print" value="{{ $print->id }}" @if ((int)($filters['print'] ?? 0) === $print->id) checked @endif> {{ $print->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($usedCountries->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Страна производства</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="country" value="" @if (empty($filters['country'])) checked @endif> Все</label>
                            <label class="b-filter-panel__option"><input type="radio" name="country" value="none" @if (($filters['country'] ?? '') === 'none') checked @endif> Не указана</label>
                            @foreach ($usedCountries as $country => $total)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="country" value="{{ $country }}" @if (($filters['country'] ?? '') === $country) checked @endif>
                                    {{ $country }} <span class="b-filter-panel__count">{{ $total }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($usedFits->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Фасон</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="fit" value="" @if (empty($filters['fit'])) checked @endif> Все</label>
                            <label class="b-filter-panel__option"><input type="radio" name="fit" value="none" @if (($filters['fit'] ?? '') === 'none') checked @endif> Не указан</label>
                            @foreach ($usedFits as $fit => $total)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="fit" value="{{ $fit }}" @if (($filters['fit'] ?? '') === $fit) checked @endif>
                                    {{ $fit }} <span class="b-filter-panel__count">{{ $total }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- особенности носки — только отмеченные у вещей пользователя, по разделам --}}
                @if ($usedFeatures->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Особенности</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="feature" value="" @if (empty($filters['feature'])) checked @endif> Все</label>
                            @foreach (WardrobeItem::FEATURES as $groupLabel => $options)
                                @php $usedInGroup = array_intersect_key($options, $usedFeatures->all()); @endphp
                                @if ($usedInGroup)
                                    <div class="b-filter-panel__subhead">{{ $groupLabel }}</div>
                                    @foreach ($usedInGroup as $slug => $label)
                                        <label class="b-filter-panel__option">
                                            <input type="radio" name="feature" value="{{ $slug }}" @if (($filters['feature'] ?? '') === $slug) checked @endif>
                                            {{ $label }} <span class="b-filter-panel__count">{{ $usedFeatures[$slug] }}</span>
                                        </label>
                                    @endforeach
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($usedOrigins->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Откуда вещь</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="origin" value="" @if (empty($filters['origin'])) checked @endif> Все</label>
                            <label class="b-filter-panel__option"><input type="radio" name="origin" value="none" @if (($filters['origin'] ?? '') === 'none') checked @endif> Не указано</label>
                            @foreach ($usedOrigins as $origin => $total)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="origin" value="{{ $origin }}" @if (($filters['origin'] ?? '') === $origin) checked @endif>
                                    {{ $origin }} <span class="b-filter-panel__count">{{ $total }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($usedMaterials->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Материал</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="material" value="" @if (empty($filters['material'])) checked @endif> Все</label>
                            <label class="b-filter-panel__option"><input type="radio" name="material" value="none" @if (($filters['material'] ?? '') === 'none') checked @endif> Не указан</label>
                            @foreach ($usedMaterials as $material)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="material" value="{{ $material->id }}" @if ((int)($filters['material'] ?? 0) === $material->id) checked @endif> {{ $material->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Место хранения</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="storage" value="" @if (empty($filters['storage'])) checked @endif> Все</label>
                        <label class="b-filter-panel__option"><input type="radio" name="storage" value="none" @if (($filters['storage'] ?? '') === 'none') checked @endif> Не указано</label>
                        @foreach ($storages as $storage)
                            <label class="b-filter-panel__option">
                                <input type="radio" name="storage" value="{{ $storage->id }}" @if ((int)($filters['storage'] ?? 0) === $storage->id) checked @endif> {{ $storage->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Особая стирка</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option">
                            <input type="checkbox" name="special_wash" value="1" @if (!empty($filters['special_wash'])) checked @endif>
                            Только вещи с неисправленными пятнами <span class="b-filter-panel__count">{{ $specialWashCount }}</span>
                        </label>
                    </div>
                </div>

                {{-- группа стирки — выбранная у вещи или подобранная сама (WardrobeItem::washGroup) --}}
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Группа стирки</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="wash_group" value="" @if (empty($filters['wash_group'])) checked @endif> Все</label>
                        @foreach (WardrobeItem::WASH_GROUPS as $slug => $label)
                            <label class="b-filter-panel__option">
                                <input type="radio" name="wash_group" value="{{ $slug }}" @if (($filters['wash_group'] ?? '') === $slug) checked @endif> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Ремонт</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option">
                            <input type="checkbox" name="needs_repair" value="1" @if (!empty($filters['needs_repair'])) checked @endif> Только вещи, которым нужен ремонт
                        </label>
                    </div>
                </div>
            </div>

            <div class="b-filter-panel__footer" style="display:flex;gap:10px;">
                <a href="{{ route('wardrobe.items.index', ['status' => $activeStatus]) }}" class="btn btn-outline-secondary" style="flex:1;">Сбросить</a>
                <button type="submit" class="btn btn-primary" style="flex:1;">Показать вещи</button>
            </div>
        </form>
    </div>
</div>
