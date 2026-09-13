{{-- Панель фильтров для вкладки "Факты" — тот же компонент b-filter-panel и то же
     поведение открытия/закрытия, что и на странице Wishlist / на вкладке "Высказывания"
     (см. quotes_filter_panel.blade.php), просто со своими JS-хуками (js-facts-*). --}}
<div class="b-filter-panel js-facts-filter-panel">
    <div class="b-filter-panel__backdrop js-facts-filter-panel-close"></div>

    <div class="b-filter-panel__drawer">
        <div class="b-filter-panel__header">
            <div class="b-filter-panel__title">Фильтры</div>
            <button type="button" class="b-filter-panel__close js-facts-filter-panel-close" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="b-filter-panel__body">
            <div class="b-filter-panel__group js-facts-filter-group">
                <button type="button" class="b-filter-panel__group-head js-facts-filter-group-toggle">
                    Категория <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($categories as $category)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-facts-filter-category" value="{{ $category['slug'] }}">
                            {{ $category['title'] }} <span class="b-filter-panel__count">{{ $category['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="b-filter-panel__group js-facts-filter-group">
                <button type="button" class="b-filter-panel__group-head js-facts-filter-group-toggle">
                    Источник <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($sources as $source)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-facts-filter-source" value="{{ $source['slug'] }}">
                            {{ $source['title'] }} <span class="b-filter-panel__count">{{ $source['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="b-filter-panel__footer">
            <button type="button" class="btn btn-outline-secondary btn-block js-facts-filter-reset">Сбросить фильтры</button>
        </div>
    </div>
</div>
