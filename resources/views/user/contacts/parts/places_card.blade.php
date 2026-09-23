{{-- $item: одна запись из Contact::getPlacesAttribute()['categories'][slug]['items'], $extra: скрыта ли изначально (см. "Показать ещё места") --}}
@php
    $mapQuery = urlencode($item['name'] . ' ' . ($item['street'] ?: '') . ' ' . $item['city'] . ' ' . $item['country']);
@endphp
<div class="b-places-card js-place-item{{ $extra ? ' js-places-extra' : '' }}"
     data-category="{{ $item['category'] }}"
     data-name="{{ \Illuminate\Support\Str::lower($item['name']) }}"
     data-city="{{ \Illuminate\Support\Str::lower($item['city']) }}"
     data-timestamp="{{ $item['last_visit_ts'] }}"
     @if ($extra) style="display: none;" @endif>
    <div class="b-places-card__photo b-contact-place-card__photo--{{ $item['photo'] }}">
        <span class="b-places-card__type-icon" style="color: {{ $item['category_color'] }};"><i class="{{ $item['icon'] }}"></i></span>
    </div>
    <div class="b-places-card__body">
        <div class="b-facts-row__value">{{ $item['name'] }}</div>
        <div class="text-muted small mb-1">{{ $item['type'] }}</div>
        <div class="text-muted small"><i class="fas fa-map-marker-alt mr-1"></i>{{ trim(($item['street'] ? $item['street'] . ', ' : '') . $item['city'] . ', ' . $item['country'], ', ') }}</div>
        <div class="text-muted small mb-2">Последнее посещение: {{ $item['last_visit'] }}</div>
        <a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener" class="b-places-card__link"><i class="fas fa-map-marker-alt mr-1"></i>Открыть в Google Maps</a>
    </div>
</div>
