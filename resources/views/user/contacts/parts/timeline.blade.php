{{-- $items: array{period, place, role, current?} --}}
@if (empty($items))
    <p class="text-muted mb-0">Пока ничего не добавлено.</p>
@else
    <ul class="b-contact-timeline">
        @foreach ($items as $item)
            <li>
                <div class="b-contact-timeline__dot"></div>
                <div class="b-contact-timeline__period">{{ $item['period'] }}</div>
                <div class="b-contact-timeline__place">
                    {{ $item['place'] }}
                    @if (!empty($item['current']))
                        <span class="b-contact-timeline__current">Сейчас</span>
                    @endif
                </div>
                <div class="text-muted small">{{ $item['role'] }}</div>
            </li>
        @endforeach
    </ul>
@endif
