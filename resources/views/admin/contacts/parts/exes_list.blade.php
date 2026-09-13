{{-- $exes: array{name, years} --}}
@if (empty($exes))
    <p class="text-muted mb-0">Пока не добавлено.</p>
@else
    <ul class="b-contact-relation-list">
        @foreach ($exes as $ex)
            <li>
                <span class="b-contact-relation-list__avatar">{{ mb_substr($ex['name'], 0, 1) }}</span>
                <div>
                    <div class="b-contact-relation-list__name">{{ $ex['name'] }}</div>
                    <div class="text-muted small">{{ $ex['years'] }}</div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
