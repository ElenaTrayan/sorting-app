{{-- $demo: массив из Contact::getDemoAttribute() — краткий превью для вкладки "Обзор" --}}
<div class="b-contact-place-cards">
    @foreach (array_slice($demo['places_loves'], 0, 3) as $i => $place)
        <div class="b-contact-place-card">
            <div class="b-contact-place-card__photo b-contact-place-card__photo--{{ $i % 4 }}"></div>
            <div class="b-contact-place-card__body">
                <div class="b-contact-place-card__name">{{ $place['name'] }}</div>
                <div class="b-contact-place-card__address">{{ $place['address'] }}</div>
            </div>
        </div>
    @endforeach
</div>
