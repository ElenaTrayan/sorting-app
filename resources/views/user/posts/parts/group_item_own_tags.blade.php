{{-- Собственные хештеги поста группы — в дополнение к общим хештегам группы (parts/group_item.blade.php).
     При редактировании — с полем поиска/создания тега (post-group.js); в режиме просмотра — только сами теги.
     Список тегов и поле — отдельными строками: у плиток картинок список в одну строку с прокруткой, чтобы
     все плитки были одной высоты (они стоят на float), а подсказки поля не обрезались этой прокруткой

     @param \App\Models\Post $post     с own_hashtags (PostController::showGroup()); у нового поста их нет
     @param bool             $editing --}}
@php $ownHashtags = $post->own_hashtags ?? collect(); @endphp
@if ($editing || $ownHashtags->isNotEmpty())
    <div class="b-post-group__own-tags">
        <div class="b-post-group__own-tag-list js-post-group-own-tags">
            @foreach ($ownHashtags as $hashtag)
                <span class="b-post-group__own-tag js-post-group-own-tag" data-id="{{ $hashtag->id }}" data-name="{{ $hashtag->title }}" title="Хештег только этого поста">#{{ $hashtag->title }}@if ($editing)<button type="button" class="b-post-group__own-tag-remove js-post-group-own-tag-remove" title="Убрать хештег у этого поста">&times;</button>@endif</span>
            @endforeach
        </div>
        @if ($editing)
            <div class="b-post-group__own-tag-field">
                <input type="text" class="b-post-group__own-tag-input js-post-group-own-tag-input" placeholder="+ свой тег" autocomplete="off"
                       title="Хештег только для этого поста — в дополнение к общим хештегам группы">
                <ul class="b-post-group__own-tag-results js-post-group-own-tag-results" hidden></ul>
            </div>
        @endif
    </div>
@endif
