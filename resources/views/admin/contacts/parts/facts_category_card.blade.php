{{-- $slug, $category: одна из категорий Contact::getFactSheetAttribute()['categories'] --}}
<div class="b-facts-card js-facts-card" data-category="{{ $slug }}">
    <div class="b-facts-card__header" style="background: {{ $category['bg'] }};">
        <span class="b-facts-card__icon" style="color: {{ $category['color'] }};"><i class="{{ $category['icon'] }}"></i></span>
        <h3>{{ $category['title'] }} <span class="text-muted">({{ count($category['items']) }})</span></h3>
    </div>
    <div class="b-facts-card__body">
        @foreach ($category['items'] as $item)
            <div class="b-facts-row js-fact-item" data-category="{{ $slug }}" data-source="{{ \Illuminate\Support\Str::slug($item['source']['label']) }}" data-search="{{ \Illuminate\Support\Str::lower($item['label'] . ' ' . $item['value']) }}">
                <div class="b-facts-row__icon"><i class="{{ $item['icon'] }}"></i></div>
                <div class="b-facts-row__main">
                    <div class="b-facts-row__label">{{ $item['label'] }}</div>
                    <div class="b-facts-row__value">{{ $item['value'] }}</div>
                </div>
                <div class="b-facts-row__source" title="Источник: {{ $item['source']['label'] }}, {{ $item['source']['date'] }}">
                    <span class="b-facts-row__source-icon" style="background: {{ $item['source']['color'] }};"><i class="{{ $item['source']['icon'] }}"></i></span>
                    <div>
                        <div>{{ $item['source']['label'] }}</div>
                        <div class="text-muted">{{ $item['source']['date'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
