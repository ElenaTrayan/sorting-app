{{-- $slug, $category: одна из категорий Contact::getRelationsAttribute()['categories'] --}}
<div class="b-facts-card b-relations-card js-relations-card" data-category="{{ $slug }}">
    <div class="b-facts-card__header" style="background: {{ $category['bg'] }};">
        <span class="b-facts-card__icon" style="color: {{ $category['color'] }};"><i class="{{ $category['icon'] }}"></i></span>
        <h3>{{ $category['title'] }} <span class="text-muted">({{ count($category['items']) }})</span></h3>
    </div>
    <div class="b-facts-card__body">
        @foreach ($category['items'] as $person)
            <div class="b-relations-row js-relation-item" data-category="{{ $slug }}" data-search="{{ \Illuminate\Support\Str::lower($person['name'] . ' ' . $person['relation']) }}">
                <span class="b-contact-relation-list__avatar">{{ mb_substr($person['name'], 0, 1) }}</span>
                <div class="b-relations-row__main">
                    <div class="b-relations-row__name">{{ $person['name'] }}</div>
                    <div class="text-muted small">{{ $person['relation'] }}@if (!empty($person['years'])) &nbsp;·&nbsp;{{ $person['years'] }}@endif</div>
                </div>
            </div>
        @endforeach
    </div>
</div>
