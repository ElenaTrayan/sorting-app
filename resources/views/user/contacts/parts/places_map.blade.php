{{-- $items: все места, отсортированные по last_visit_ts desc (Collection).
     Декоративная карта — под места нет координат (только город/страна текстом),
     поэтому это не настоящая интерактивная карта, а стилизованная иллюстрация.
     Точки — для всех мест (не только видимых в сетке ниже), чтобы при выборе категории
     в b-places-categories на карте всегда было что показать; сама сетка при этом всё ещё
     подчиняется лимиту "Показать ещё" независимо от карты. --}}
@php
    $topItem = $items->first();
@endphp
<div class="b-places-map js-places-map">
    <div class="b-places-map__label"><i class="fas fa-map-marker-alt mr-1"></i>Места, которые посещает контакт</div>

    @foreach ($items as $item)
        @php
            $seed = crc32($item['name']);
            $left = 10 + ($seed % 80);
            $top = 15 + (($seed >> 4) % 65);
        @endphp
        <span class="b-places-map__pin js-places-map-pin"
              data-category="{{ $item['category'] }}"
              data-name="{{ $item['name'] }}"
              data-type="{{ $item['type'] }}"
              data-last-visit="{{ $item['last_visit'] }}"
              data-photo="{{ $item['photo'] }}"
              style="left: {{ $left }}%; top: {{ $top }}%; background: {{ $item['category_color'] }};"
              title="{{ $item['name'] }}">
            <i class="{{ $item['icon'] }}"></i>
        </span>
    @endforeach

    @if ($topItem)
        <div class="b-places-map__popup js-places-map-popup">
            <div class="b-places-map__popup-photo b-contact-place-card__photo--{{ $topItem['photo'] }} js-places-map-popup-photo"></div>
            <div class="min-width-0">
                <div class="b-facts-row__value js-places-map-popup-name">{{ $topItem['name'] }}</div>
                <div class="text-muted small js-places-map-popup-type">{{ $topItem['type'] }}</div>
                <div class="text-muted small"><span class="js-places-map-popup-visit">Последнее посещение: {{ $topItem['last_visit'] }}</span></div>
            </div>
        </div>
    @endif
</div>
