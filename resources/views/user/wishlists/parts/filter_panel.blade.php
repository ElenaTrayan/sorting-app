<div class="b-filter-panel js-wishlist-filter-panel">
    <div class="b-filter-panel__backdrop js-wishlist-filter-panel-close"></div>

    <div class="b-filter-panel__drawer">
        <div class="b-filter-panel__header">
            <div class="b-filter-panel__title">Фильтры</div>
            <button type="button" class="b-filter-panel__close js-wishlist-filter-panel-close" aria-label="Закрыть">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="b-filter-panel__body">
            <div class="b-filter-panel__group js-filter-group">
                <button type="button" class="b-filter-panel__group-head js-filter-group-toggle">
                    По категории <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($categories as $category)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-filter-category" value="{{ $category['slug'] }}">
                            {{ $category['title'] }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="b-filter-panel__group js-filter-group">
                <button type="button" class="b-filter-panel__group-head js-filter-group-toggle">
                    По статусу бронирования <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    <label class="b-filter-panel__option">
                        <input type="checkbox" class="js-filter-booking" value="0">
                        Не забронировано
                    </label>
                    <label class="b-filter-panel__option">
                        <input type="checkbox" class="js-filter-booking" value="1">
                        Забронировано
                    </label>
                </div>
            </div>

            <div class="b-filter-panel__group js-filter-group">
                <button type="button" class="b-filter-panel__group-head js-filter-group-toggle">
                    По статусу выполнения <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    <label class="b-filter-panel__option">
                        <input type="checkbox" class="js-filter-fulfilled" value="0">
                        Не выполнено
                    </label>
                    <label class="b-filter-panel__option">
                        <input type="checkbox" class="js-filter-fulfilled" value="1">
                        Выполнено
                    </label>
                </div>
            </div>

            <div class="b-filter-panel__group js-filter-group">
                <button type="button" class="b-filter-panel__group-head js-filter-group-toggle">
                    По приоритету желания <i class="fas fa-chevron-up"></i>
                </button>
                <div class="b-filter-panel__group-body">
                    @foreach ($priorities as $priority)
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-filter-priority" value="{{ $priority['slug'] }}">
                            {{ $priority['title'] }}
                        </label>
                    @endforeach
                    <label class="b-filter-panel__option">
                        <input type="checkbox" class="js-filter-priority" value="">
                        Приоритет не указан
                    </label>
                </div>
            </div>

            @if ($storeDomains->isNotEmpty())
                <div class="b-filter-panel__group js-filter-group">
                    <button type="button" class="b-filter-panel__group-head js-filter-group-toggle">
                        По магазину <i class="fas fa-chevron-up"></i>
                    </button>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option">
                            <input type="checkbox" class="js-filter-store" value="">
                            Без магазина
                        </label>
                        @foreach ($storeDomains as $domain)
                            <label class="b-filter-panel__option">
                                <input type="checkbox" class="js-filter-store" value="{{ $domain }}">
                                {{ $domain }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="b-filter-panel__footer">
            <button type="button" class="btn btn-outline-secondary btn-block js-wishlist-filter-reset">Сбросить фильтры</button>
        </div>
    </div>
</div>
