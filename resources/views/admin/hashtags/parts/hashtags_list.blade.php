<div class="b-hashtags-index__header">
    <div class="b-hashtags-index__count">Всего хештегов: {{ $hashtags->count() }}</div>
    <a href="{{ route('hashtags.create') }}" class="btn btn-outline-info">Добавить хештег</a>
</div>

@if ($groupedHashtags->isEmpty())
    <div class="b-hashtags-index__empty">Хештегов пока нет.</div>
@else
    <nav class="b-hashtags-index__alphabet">
        @foreach ($groupedHashtags as $letter => $letterHashtags)
            <a href="#letter-{{ $loop->index }}" class="b-hashtags-index__alphabet-link">{{ $letter }}</a>
        @endforeach
    </nav>

    <div class="b-hashtags-index__groups">
        @foreach ($groupedHashtags as $letter => $letterHashtags)
            <section class="b-hashtags-index__group" id="letter-{{ $loop->index }}">
                <div class="b-hashtags-index__letter">{{ $letter }}</div>
                <ul class="b-hashtags-index__list">
                    @foreach ($letterHashtags as $hashtag)
                        <li class="b-hashtags-index__item" data-id="{{ $hashtag->id }}">
                            <span class="b-hashtags-index__title">#{{ $hashtag->title }}</span>
                            <span class="b-hashtags-index__actions">
                                <a href="{{ route('hashtags.edit', $hashtag->id) }}"
                                   class="b-hashtags-index__action"
                                   title="Редактировать">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                                <button type="button"
                                        class="b-hashtags-index__action b-hashtags-index__action--delete"
                                        data-action="delete"
                                        data-url="{{ route('hashtags.destroy', $hashtag->id) }}"
                                        data-page-id="hashtags"
                                        title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
@endif
