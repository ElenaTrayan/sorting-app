{{-- Карточка поста на странице группы (posts/group.blade.php); её же отдаёт сервер для постов, добавленных
     без перезагрузки страницы (PostController::renderGroupItem()). У группы изображений — плитка с картинкой
     (клик — просмотр в полном размере), у группы текстов — редактор текста поста.

     @param \App\Models\Post $post      с cover_image / full_image (PostController::fillGroupImages())
     @param int              $groupId
     @param string           $kind      Post::TYPE_PICTURE или Post::TYPE_TEXT
     @param int              $position  номер по порядку, с 1 (показываем только у текстов)
     @param bool             $editing   группа текстов: true — редактор текста и кнопки "убрать/удалить",
                                        false — просмотр: текст только читают, карточку можно перетащить --}}
@php $isText = $kind === \App\Models\Post::TYPE_TEXT; @endphp
<li class="b-post-group__item {{ $isText ? 'b-post-group__item--text' : 'b-post-group__item--picture' }} {{ $isText && !$editing ? 'b-post-group__item--view' : '' }} js-post-group-item" id="post-{{ $post->id }}" data-id="{{ $post->id }}">
    @if ($isText)
        <div class="b-post-group__item-head">
            <span class="b-post-group__handle js-post-group-handle" title="Перетащить"><i class="fas fa-grip-vertical"></i></span>
            <span class="b-post-group__position js-post-group-position">{{ $position }}</span>
            @if ($post->created_at)
                <span class="b-post-group__date"><i class="far fa-calendar-alt"></i> {{ $post->created_at->format('d.m.Y, H:i') }}</span>
            @endif
            {{-- число знаков в тексте поста — считает post-group.js, при редактировании на лету --}}
            <span class="b-post-group__chars js-post-group-chars" title="Знаков в тексте, с пробелами"></span>
            @if ($editing)
                <span class="b-post-group__item-actions">
                    <button type="button" class="b-post-group__action js-post-group-detach" data-url="{{ route('posts.groups.detach', [$groupId, $post->id]) }}" title="Убрать из группы (сам пост останется)"><i class="fas fa-unlink"></i></button>
                    <button type="button" class="b-post-group__action b-post-group__action--danger js-post-group-delete" data-url="{{ route('posts.groups.detach', [$groupId, $post->id]) }}" title="Удалить пост"><i class="fas fa-trash"></i></button>
                </span>
            @endif
        </div>
        @if ($editing)
            <textarea class="js-post-group-text" id="post-group-text-{{ $post->id }}" name="texts[{{ $post->id }}]" placeholder="Текст поста…">{{ $post->content }}</textarea>
        @elseif (trim(strip_tags((string) $post->content)) !== '')
            <div class="b-post-show__prose">{!! $post->content !!}</div>
        @else
            <div class="b-post-group__empty-text">Текста пока нет</div>
        @endif
    @else
        @if ($post->video_url)
            <a href="{{ $post->video_url }}" data-fancybox="post-group" class="b-post-group__media">
                <video src="{{ $post->video_url }}" preload="metadata" muted></video>
                <span class="b-film-materials__play"><i class="fas fa-play"></i></span>
            </a>
        @elseif ($post->cover_image)
            <a href="/storage{{ $post->full_image }}" data-fancybox="post-group" class="b-post-group__media">
                <img src="/storage{{ $post->cover_image }}" alt="" loading="lazy" draggable="false">
            </a>
        @else
            <span class="b-post-group__media"><i class="fas fa-image"></i></span>
        @endif
        {{-- пост не из одних картинок (попал в группу раньше) — у него остаётся своя страница --}}
        @if ($post->groupKind() === null)
            <a href="{{ route('posts.show', $post->id) }}" class="b-post-group__note" title="У этого поста есть свой текст или данные — в группе изображений они не видны. Открыть пост">{{ $post->typeLabel() }}</a>
        @endif
        <span class="b-post-group__item-actions">
            <button type="button" class="b-post-group__action js-post-group-detach" data-url="{{ route('posts.groups.detach', [$groupId, $post->id]) }}" title="Убрать из группы (сам пост останется)"><i class="fas fa-unlink"></i></button>
            <button type="button" class="b-post-group__action b-post-group__action--danger js-post-group-delete" data-url="{{ route('posts.groups.detach', [$groupId, $post->id]) }}" title="Удалить пост"><i class="fas fa-trash"></i></button>
        </span>
        {{-- поворот (сохраняется сразу, PostController::transformImage) и палитра обложки (окно с тем же
             редактором, что на странице поста) — у поста с картинкой; у видео их нет --}}
        @if ($post->cover_image && !$post->video_url)
            <span class="b-post-group__tools">
                @if ($post->image_name)
                    <button type="button" class="b-post-group__action js-post-group-rotate" data-action="rotate_left" data-url="{{ route('posts.images.transform', $post->id) }}" data-name="{{ $post->image_name }}" title="Повернуть влево"><i class="fas fa-undo"></i></button>
                    <button type="button" class="b-post-group__action js-post-group-rotate" data-action="rotate_right" data-url="{{ route('posts.images.transform', $post->id) }}" data-name="{{ $post->image_name }}" title="Повернуть вправо"><i class="fas fa-redo"></i></button>
                @endif
                <button type="button" class="b-post-group__action js-post-group-palette" title="Палитра"
                        data-show-action="{{ route('posts.palette.show', $post->id) }}"
                        data-update-action="{{ route('posts.palette.update', $post->id) }}"
                        data-reset-action="{{ route('posts.palette.reset', $post->id) }}"><i class="fas fa-palette"></i></button>
            </span>
            {{-- полоска палитры, как на карточке в "Все посты": ширина цвета — его доля на картинке --}}
            <span class="b-post-group__palette js-post-group-palette-strip">
                @foreach ($post->palette ?? [] as $color)
                    <span style="flex: {{ $color['share'] }}; background: {{ $color['hex'] }};"></span>
                @endforeach
            </span>
        @endif
    @endif
</li>
