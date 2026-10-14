{{-- Компактный превью поста для Fancybox-галереи на "Все посты" (клик по карточке).
     Не дублирует полный .b-post-show__card с каруселью — тут только "витрина":
     обложка/видео, текст, хештеги, категория и ссылка "Открыть полностью" на
     настоящую страницу поста (там уже вся карусель, группа, редактирование).

     Если у пина нет текста — показываем только картинку/видео, блок .b-post-show__body
     не выводим вовсе. Если нет ни картинки, ни видео — только текст (на всю ширину). --}}
@php
    $hasMedia = (bool) ($post->video_url || count($galleryImages) > 0);
    // карточка фильма тоже "текст" — без неё пин с одной картинкой и данными фильма показал бы только картинку
    $hasText = (bool) ($post->title || $post->content || $post->film);
@endphp
<div class="b-post-preview @if ($hasMedia && !$hasText) b-post-preview--media-only @endif @if (!$hasMedia) b-post-preview--text-only @endif">
    @if ($hasMedia)
        <div class="b-post-preview__media">
            @if ($post->video_url)
                <video src="{{ $post->video_url }}" controls preload="metadata"></video>
            @else
                {{-- отдельный Fancybox-триггер поверх уже открытого поп-апа — клик по
                     картинке открывает её отдельно в полном размере с зумом (см.
                     data-fancybox="post-preview-image-*" в posts/index.blade.php).
                     Группа с id поста, а не общая на все посты — Fancybox предзагружает
                     соседние слайды галереи (пред./тек./след., то есть сразу 3 фрагмента
                     в DOM одновременно), и с одним общим именем группы эти 3 картинки
                     из РАЗНЫХ постов схлопывались в одну зацикленную мини-галерею --}}
                <a href="/storage{{ $galleryImages[0]['full'] }}" data-fancybox="post-preview-image-{{ $post->id }}">
                    <img src="/storage{{ $galleryImages[0]['full'] }}" alt="">
                </a>
                @if (count($galleryImages) > 1)
                    <span class="b-post-preview__media-badge"><i class="far fa-images"></i> {{ count($galleryImages) }}</span>
                @endif
            @endif
        </div>
    @endif

    @if ($hasText || !$hasMedia)
    <div class="b-post-preview__body">
        <div class="b-post-show__meta">
            @if ($post->created_at)
                <span class="b-post-show__meta-item"><i class="far fa-calendar-alt"></i> {{ $post->created_at->format('d.m.Y, H:i') }}</span>
            @endif
            @if ($groupCount > 1)
                <span class="b-post-show__meta-sep"></span>
                <span class="b-post-show__meta-item"><i class="fas fa-layer-group"></i> Группа из {{ $groupCount }}</span>
            @endif
        </div>

        @if ($post->title || $post->content)
            <div class="b-post-show__text">
                @if ($post->title)
                    <h2 class="b-post-show__title">{{ $post->title }}</h2>
                @endif
                @if ($post->content)
                    <div class="b-post-show__prose">{!! $post->content !!}</div>
                @endif
            </div>
        @elseif (!$post->film)
            <div class="b-post-show__empty-text">
                <div class="b-post-show__empty-text-icon"><i class="fas fa-align-left"></i></div>
                <div class="b-post-show__empty-text-title">Пост без описания</div>
            </div>
        @endif

        @if ($post->film)
            @include('user.posts.parts.film_card', ['film' => $post->film, 'compact' => true])
        @endif

        @if ($post->hashtags->isNotEmpty() || $post->category || $post->filmPost)
            <div class="b-post-show__tags">
                {{-- пост прикреплён к фильму (кадр/промо/видео) --}}
                @if ($post->filmPost)
                    <a href="{{ route('posts.show', $post->filmPost->id) }}" class="b-post-show__film-chip">
                        <i class="fas fa-film"></i> {{ $post->filmPost->title ?: 'Без названия' }}
                    </a>
                @endif
                @foreach ($post->hashtags as $hashtag)
                    <span class="b-post-show__tag">#{{ $hashtag->title }}</span>
                @endforeach
                @if ($post->category)
                    <span class="b-post-show__category-chip">{{ $post->category->title ?: $post->category->alias }}</span>
                @endif
            </div>
        @endif

        <div class="b-post-preview__actions">
            <a href="{{ route('posts.edit', $post->id) }}" class="b-post-show__btn">
                <i class="fas fa-pencil-alt"></i> Редактировать
            </a>
            <a href="{{ route('posts.show', $post->id) }}" class="b-posts-toolbar__create">
                Открыть полностью <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    @endif
</div>
