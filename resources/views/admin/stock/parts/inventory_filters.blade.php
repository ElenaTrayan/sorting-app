{{--
    Drawer "Фильтры" для "Мои запасы" — та же механика, что и .b-filter-panel на
    "База контактов" (contacts/parts/facts_filter_panel.blade.php): выезжает справа,
    открывается по data-filter-panel-open, закрывается по backdrop/крестику (my-stock.js).
    Фильтрует по-настоящему — через обычный GET-сабмит формы (перезагрузка страницы
    с query-параметрами), без отдельного AJAX-слоя для фильтрации списка.
--}}
<div class="b-filter-panel" data-filter-panel="inventory">
    <div class="b-filter-panel__backdrop js-filter-panel-close"></div>
    <div class="b-filter-panel__drawer">
        <form method="GET" action="{{ route('stock.inventory.index') }}">
            <input type="hidden" name="location" value="{{ $activeLocation }}">

            <div class="b-filter-panel__header">
                <div class="b-filter-panel__title">Фильтры</div>
                <button type="button" class="b-filter-panel__close js-filter-panel-close" aria-label="Закрыть"><i class="fas fa-times"></i></button>
            </div>

            <div class="b-filter-panel__body">
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Статус срока годности</div>
                    <div class="b-filter-panel__group-body">
                        @foreach (['' => 'Все', 'expired' => 'Просрочено', 'soon' => 'Истекает за 7 дней', 'month' => 'Истекает за месяц', 'ok' => 'Всё в порядке'] as $value => $label)
                            <label class="b-filter-panel__option">
                                <input type="radio" name="status" value="{{ $value }}" @checked(($filters['status'] ?? '') === $value)> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Категория</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="category" value="" @checked(empty($filters['category']))> Все</label>
                        @foreach ($categories as $slug => $label)
                            <label class="b-filter-panel__option">
                                <input type="radio" name="category" value="{{ $slug }}" @checked(($filters['category'] ?? '') === $slug)> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Место хранения</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="location" value="all" @checked($activeLocation === 'all')> Все</label>
                        @foreach ($locations as $location)
                            <label class="b-filter-panel__option">
                                <input type="radio" name="location" value="{{ $location->slug }}" @checked($activeLocation === $location->slug)> {{ $location->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @if ($brands->isNotEmpty())
                    <div class="b-filter-panel__group">
                        <div class="b-filter-panel__group-head">Бренд</div>
                        <div class="b-filter-panel__group-body">
                            <label class="b-filter-panel__option"><input type="radio" name="brand" value="" @checked(empty($filters['brand']))> Все</label>
                            @foreach ($brands as $brand)
                                <label class="b-filter-panel__option">
                                    <input type="radio" name="brand" value="{{ $brand }}" @checked(($filters['brand'] ?? '') === $brand)> {{ $brand }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Срок годности — диапазон</div>
                    <div class="b-filter-panel__group-body">
                        <div class="b-filter-panel__date-row">
                            <label>с <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
                            <label>по <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="b-filter-panel__footer" style="display:flex;gap:10px;">
                <a href="{{ route('stock.inventory.index') }}" class="btn btn-outline-secondary" style="flex:1;">Сбросить</a>
                <button type="submit" class="btn btn-primary" style="flex:1;">Показать товары</button>
            </div>
        </form>
    </div>
</div>
