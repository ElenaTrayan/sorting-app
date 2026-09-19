<div class="b-filter-panel" data-filter-panel="history">
    <div class="b-filter-panel__backdrop js-filter-panel-close"></div>
    <div class="b-filter-panel__drawer">
        <form method="GET" action="{{ route('stock.history.index') }}">
            <div class="b-filter-panel__header">
                <div class="b-filter-panel__title">Фильтры</div>
                <button type="button" class="b-filter-panel__close js-filter-panel-close" aria-label="Закрыть"><i class="fas fa-times"></i></button>
            </div>
            <div class="b-filter-panel__body">
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Период</div>
                    <div class="b-filter-panel__group-body">
                        <div class="b-filter-panel__date-row">
                            <label>с <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></label>
                            <label>по <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></label>
                        </div>
                    </div>
                </div>
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Магазин</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="store_id" value="" @checked(empty($filters['store_id']))> Все</label>
                        @foreach ($stores as $store)
                            <label class="b-filter-panel__option"><input type="radio" name="store_id" value="{{ $store->id }}" @checked(($filters['store_id'] ?? '') == $store->id)> {{ $store->name }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Способ оплаты</div>
                    <div class="b-filter-panel__group-body">
                        <input type="text" class="form-control" name="payment_method" placeholder="Напр. Картой" value="{{ $filters['payment_method'] ?? '' }}">
                    </div>
                </div>
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Товар</div>
                    <div class="b-filter-panel__group-body">
                        <input type="text" class="form-control" name="product" placeholder="Поиск по товару" value="{{ $filters['product'] ?? '' }}">
                    </div>
                </div>
            </div>
            <div class="b-filter-panel__footer" style="display:flex;gap:10px;">
                <a href="{{ route('stock.history.index') }}" class="btn btn-outline-secondary" style="flex:1;">Сбросить</a>
                <button type="submit" class="btn btn-primary" style="flex:1;">Показать</button>
            </div>
        </form>
    </div>
</div>
