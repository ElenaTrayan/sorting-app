<div class="b-filter-panel" data-filter-panel="promotions">
    <div class="b-filter-panel__backdrop js-filter-panel-close"></div>
    <div class="b-filter-panel__drawer">
        <form method="GET" action="{{ route('stock.promotions.index') }}">
            <div class="b-filter-panel__header">
                <div class="b-filter-panel__title">Фильтры</div>
                <button type="button" class="b-filter-panel__close js-filter-panel-close" aria-label="Закрыть"><i class="fas fa-times"></i></button>
            </div>
            <div class="b-filter-panel__body">
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
                    <div class="b-filter-panel__group-head">Категория</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="category" value="" @checked(empty($filters['category']))> Все</label>
                        @foreach ($categories as $slug => $label)
                            <label class="b-filter-panel__option"><input type="radio" name="category" value="{{ $slug }}" @checked(($filters['category'] ?? '') === $slug)> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Размер скидки</div>
                    <div class="b-filter-panel__group-body">
                        @foreach (['' => 'Любой', '10' => 'от 10%', '20' => 'от 20%', '30' => 'от 30%'] as $value => $label)
                            <label class="b-filter-panel__option"><input type="radio" name="min_discount" value="{{ $value }}" @checked(($filters['min_discount'] ?? '') == $value)> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Наличие</div>
                    <div class="b-filter-panel__group-body">
                        <label class="b-filter-panel__option"><input type="radio" name="availability" value="" @checked(empty($filters['availability']))> Любое</label>
                        @foreach (\App\Models\Promotion::AVAILABILITY as $value => $label)
                            <label class="b-filter-panel__option"><input type="radio" name="availability" value="{{ $value }}" @checked(($filters['availability'] ?? '') === $value)> {{ $label }}</label>
                        @endforeach
                    </div>
                </div>
                <div class="b-filter-panel__group">
                    <div class="b-filter-panel__group-head">Цена, до</div>
                    <div class="b-filter-panel__group-body">
                        <input type="number" class="form-control" name="max_price" placeholder="Без ограничения" value="{{ $filters['max_price'] ?? '' }}">
                    </div>
                </div>
            </div>
            <div class="b-filter-panel__footer" style="display:flex;gap:10px;">
                <a href="{{ route('stock.promotions.index') }}" class="btn btn-outline-secondary" style="flex:1;">Сбросить</a>
                <button type="submit" class="btn btn-primary" style="flex:1;">Показать акции</button>
            </div>
        </form>
    </div>
</div>
