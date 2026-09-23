{{-- Панель фильтров для вкладки "Высказывания" — тот же компонент и то же поведение
     открытия/закрытия, что и на странице Wishlist (b-filter-panel, см. filter_panel.blade.php
     там), просто со своими данными и своими JS-хуками (js-quotes-*), чтобы не пересекаться
     с фильтрами Wishlist на своей странице. --}}
<div class="b-filter-panel js-quotes-filter-panel">
    <div class="b-filter-panel__backdrop js-quotes-filter-panel-close"></div>

    <div class="b-filter-panel__drawer">
        <div class="b-filter-panel__header">
            <div class="b-filter-panel__title">Фильтры</div>
            <button type="button" class="b-filter-panel__close js-quotes-filter-panel-close" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="b-filter-panel__body">
            <div class="b-filter-panel__search">
                <i class="fas fa-search"></i>
                <input type="search" class="form-control form-control-sm js-quotes-search" placeholder="Поиск по высказываниям...">
            </div>

            <div class="b-filter-panel__group js-quotes-filter-group">
                <button type="button" class="b-filter-panel__group-head js-quotes-filter-group-toggle">
                    Источник <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($platforms as $platform)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-quotes-filter-platform" value="{{ $platform['slug'] }}">
                            {{ $platform['title'] }} <span class="b-filter-panel__count">{{ $platform['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="b-filter-panel__group js-quotes-filter-group">
                <button type="button" class="b-filter-panel__group-head js-quotes-filter-group-toggle">
                    Дата <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    <div class="b-filter-panel__date-row">
                        <label>От <input type="date" class="form-control form-control-sm js-quotes-filter-date-from"></label>
                        <label>До <input type="date" class="form-control form-control-sm js-quotes-filter-date-to"></label>
                    </div>
                </div>
            </div>

            <div class="b-filter-panel__group js-quotes-filter-group">
                <button type="button" class="b-filter-panel__group-head js-quotes-filter-group-toggle">
                    Тип контента <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($contentTypes as $type)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-quotes-filter-content-type" value="{{ $type['slug'] }}">
                            {{ $type['title'] }} <span class="b-filter-panel__count">{{ $type['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="b-filter-panel__group js-quotes-filter-group">
                <button type="button" class="b-filter-panel__group-head js-quotes-filter-group-toggle">
                    Тональность <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($sentiments as $sentiment)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-quotes-filter-sentiment" value="{{ $sentiment['slug'] }}">
                            {{ $sentiment['emoji'] }} {{ $sentiment['title'] }} <span class="b-filter-panel__count">{{ $sentiment['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="b-filter-panel__group js-quotes-filter-group">
                <button type="button" class="b-filter-panel__group-head js-quotes-filter-group-toggle">
                    Темы <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($topics as $topic)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-quotes-filter-topic" value="{{ $topic['slug'] }}">
                            {{ $topic['title'] }} <span class="b-filter-panel__count">{{ $topic['count'] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="b-filter-panel__footer">
            <button type="button" class="btn btn-outline-secondary btn-block js-quotes-filter-reset">Сбросить фильтры</button>
        </div>
    </div>
</div>
