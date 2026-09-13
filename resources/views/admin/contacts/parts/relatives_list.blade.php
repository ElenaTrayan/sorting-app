{{-- $relatives: array{name, relation} --}}
@if (empty($relatives))
    <p class="text-muted mb-0">Пока не добавлено ни одного родственника.</p>
@else
    <ul class="b-contact-relation-list">
        @foreach ($relatives as $relative)
            <li>
                <span class="b-contact-relation-list__avatar">{{ mb_substr($relative['name'], 0, 1) }}</span>
                <div>
                    <div class="b-contact-relation-list__name">{{ $relative['name'] }}</div>
                    <div class="text-muted small">{{ $relative['relation'] }}</div>
                </div>
            </li>
        @endforeach
    </ul>
@endif
