@foreach($posts as $post)
    <?php //dd($post); ?>
    @php
        // карточка-стопка: в ленте "Все посты" и в поиске по хештегам группа — одна карточка, её первый пост
        // (Post::scopeCollapsedGroups, счётчик — Post::fillGroupCounts). Клик открывает страницу группы,
        // а не превью поста; удалить пост группы можно только на её странице
        $isStack = !empty($post->group_id) && isset($post->group_count);
        $groupUrl = !empty($post->group_id) ? route('posts.groups.show', $post->group_id) : null;
    @endphp
    {{-- data-group-id — у любого поста группы, и у стопки, и у отдельной карточки (снята галочка "Свернуть группы"):
         по нему режим "Выбрать" снимает отметки с постов групп при переключении галочки (posts-bulk.js) --}}
    <div class="grid-item @if ($isStack) grid-item--stack @endif" data-id="{{ $post->id }}" @if (!empty($post->group_id)) data-group-id="{{ $post->group_id }}" @endif>

        <div class="b-card__user-options">
            <a href="#" id="triggerId" class="button" data-toggle="dropdown" aria-haspopup="true"
               aria-expanded="false">
                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="triggerId">
                @if ($isStack)
                    <a class="dropdown-item text-dark" href="{{ $groupUrl }}"><i
                            class="fas fa-layer-group mr-1"></i>Открыть группу</a>
                    <a class="dropdown-item text-dark" href="{{ route('posts.groups.show', [$post->group_id, 'edit' => 1]) }}"><i
                            class="fas fa-edit mr-1"></i>Редактировать группу</a>
                @else
                <a class="dropdown-item text-dark" href="{{ route('posts.show', [$post->id]) }}"><i
                        class="fas fa-eye mr-1"></i>Перейти к публикации</a>
                <a class="dropdown-item text-dark" href="{{ route('posts.edit', [$post->id]) }}"><i
                        class="fas fa-edit mr-1"></i>Редактировать</a>
                @endif
                {{-- пин "Одежда" — в "Мой гардероб" (форма новой вещи с картинкой поста, Wardrobe\ItemController::index) --}}
                @if ($post->type === \App\Models\Post::TYPE_CLOTHES && auth()->user()->hasService('wardrobe'))
                    @if ($post->wardrobeItems->isNotEmpty())
                        <a class="dropdown-item text-dark" href="{{ route('wardrobe.items.index', ['edit_item' => $post->wardrobeItems->first()->id]) }}"><i
                                class="fas fa-tshirt mr-1"></i>В гардеробе</a>
                    @else
                        <a class="dropdown-item text-dark" href="{{ route('wardrobe.items.index', ['from_post' => $post->id]) }}"><i
                                class="fas fa-tshirt mr-1"></i>Добавить в гардероб</a>
                    @endif
                @endif
                {{-- группа: у её постов своих страниц нет, всё на странице группы (PostController::showGroup);
                     создать группу можно из поста с одними картинками или с одним текстом (Post::groupKind) --}}
                @if ($isStack)
                    {{-- пункты группы — в начале меню --}}
                @elseif (!empty($post->group_id))
                    <a class="dropdown-item text-dark" href="{{ $groupUrl }}#post-{{ $post->id }}"><i
                            class="fas fa-layer-group mr-1"></i>Открыть группу</a>
                @elseif ($post->groupKind() !== null)
                    <form action="{{ route('posts.groups.start', $post->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item text-dark"><i class="fas fa-layer-group mr-1"></i>Создать группу</button>
                    </form>
                @endif
                {{-- пункты ниже работают через скрипты ленты "Все посты" (admin.js, posts-bulk.js); там, где карточки
                     показаны без них (страница контакта, $linksOnly), остаются только пункты-ссылки --}}
                @if (empty($linksOnly))
                {{-- отметка "Использовано" (posts-bulk.js, PostController::bulkSetUsed); у стопки — на всю группу --}}
                <span class="dropdown-item text-dark" data-action="toggle-used"><i
                        class="fas fa-check-double mr-1"></i><span class="js-post-used-label">{{ !empty($post->is_used) ? 'Снять отметку «Использовано»' : 'Отметить использованным' }}</span></span>
                <span class="dropdown-item text-dark" data-action="add-hashtag"
                      data-url="{{ route('posts.update', [$post->id]) }}" data-post-id="{{ $post->id }}"><i
                        class="fas fa-tags mr-1"></i>Добавить тег</span>
                {{--                    <span class="dropdown-item text-dark"><i class="fas fa-link mr-1"></i>Копировать ссылку</span>--}}
                <span class="dropdown-item text-dark" data-action="copy-text"><i
                        class="fas fa-copy mr-1"></i>Скопировать текст</span>
                @endif
                {{-- скачать оригиналы: стопка — вся группа архивом, пост с несколькими файлами — архивом, один файл —
                     как есть (PostController::download / downloadGroup); у поста без картинок и видео пункта нет --}}
                @php
                    $downloadCount = count(\App\Http\Controllers\User\Packages\UploadImageController::normalizeImages($post->original_image)) + ($post->video_url ? 1 : 0);
                @endphp
                @if ($isStack)
                    <a class="dropdown-item text-dark" href="{{ route('posts.groups.download', $post->group_id) }}"><i
                            class="fas fa-cloud-download-alt mr-1"></i>Скачать группу архивом</a>
                @elseif ($downloadCount > 0)
                    <a class="dropdown-item text-dark" href="{{ route('posts.download', $post->id) }}"><i
                            class="fas fa-cloud-download-alt mr-1"></i>{{ $downloadCount > 1 ? 'Скачать файлы архивом' : ($post->video_url ? 'Скачать видео' : 'Скачать изображение') }}</a>
                @endif
                {{-- у стопки "Удалить" нет: удалился бы только первый пост группы, а не то, что видно на карточке --}}
                @if (!$isStack && empty($linksOnly))
                <span class="dropdown-item text-danger" data-action="delete"
                      data-url="{{ route('posts.destroy', [$post->id]) }}" data-page-id="posts"><i
                        class="fa fa-trash mr-1"></i>Удалить</span>
                @endif
            </div>
        </div>

        @if ($isStack)
            <a href="{{ $groupUrl }}" class="b-card__stack-badge" title="Группа: {{ $post->group_count }} — открыть">
                <i class="fas fa-layer-group"></i> {{ $post->group_count }}
            </a>
        @endif

        @php
            // общий триггер Fancybox-галереи "Все посты" — клик открывает компактное
            // превью поста (post_preview.blade.php) с навигацией стрелками между постами
            // в том же порядке, что и в сетке (см. @push('footer-scripts') в index.blade.php)
            $galleryUrl = route('posts.show', $post->id) . '?fragment=1';
            // стопка ведёт на страницу группы обычной ссылкой — в галерею превью она не входит
            $cardLink = $isStack
                ? 'href="' . e($groupUrl) . '"'
                : 'href="' . e($galleryUrl) . '" data-fancybox="posts-gallery" data-type="ajax"';
        @endphp
        <div class="b-card__content">
            @if ($post->video_url)
                <a {!! $cardLink !!} class="b-card__content__image b-card__content__video">
                    <video src="{{ $post->video_url }}" controls preload="metadata"></video>
                </a>
            @elseif (!empty($post->cover_image))
                <a {!! $cardLink !!} class="b-card__content__image scale">
                    <img src="/storage{{ $post->cover_image }}" alt="Content img">
                </a>
                {{-- палитра обложки: тонкая полоска, ширина цвета = его доля на картинке --}}
                @if (!empty($post->palette))
                    <div class="b-card__palette" title="{{ implode(' · ', array_column($post->palette, 'hex')) }}">
                        @foreach ($post->palette as $color)
                            <span style="flex: {{ $color['share'] }}; background: {{ $color['hex'] }};"></span>
                        @endforeach
                    </div>
                @endif
            @endif

            @if (!empty($post->title) || !empty($post->content))
                <div class="b-card__content__text">
                    @if (!empty($post->title))
                        <a {!! $cardLink !!} class="title">{{ $post->title }}</a>
                    @endif
                    @if (!empty($post->content))
                        {!! $post->content !!}
                    @endif
                </div>
            @endif

            @if ($post->film)
                @include('user.posts.parts.film_summary', ['film' => $post->film])
            @endif

            {{-- пост прикреплён к фильму (кадр/промо/видео) — чип ведёт на пин фильма. relationLoaded:
                 карточки рисуют и там, где связь не подгружена (контакты) — не делаем запрос на каждую --}}
            @if ($post->relationLoaded('filmPost') && $post->filmPost)
                <a href="{{ route('posts.show', $post->filmPost->id) }}" class="b-card__film-chip" title="Материал фильма">
                    <i class="fas fa-film"></i> {{ $post->filmPost->title ?: 'Фильм' }}
                </a>
            @endif

            {{-- пост группы там, где группы не сворачиваются в стопку (поиск по цвету, страница контакта) --}}
            @if (!empty($post->group_id) && !$isStack)
                <a href="{{ $groupUrl }}#post-{{ $post->id }}" class="b-card__film-chip b-card__group-chip" title="Открыть группу">
                    <i class="fas fa-layer-group"></i> Группа
                </a>
            @endif
        </div><!-- /.b-card__content -->

        @if (!empty($post->hashtags) && $post->hashtags->isNotEmpty())
            <div class="b-card__footer">
                <ul class="tags">
                    @foreach($post->hashtags as $hashtag)
                        <li data-id="{{ $hashtag->id }}" data-title="{{ $hashtag->title }}">
                            <a rel="tag" href="#">#{{ $hashtag->title }}</a>
                        </li>
                    @endforeach
                </ul>
            </div><!-- /.card-footer -->
        @endif

        @if (!empty($post->is_used))
            <div class="ribbon ribbon-success float-end"><i class="fas fa-solid fa-check mr-1"></i>Использовано
            </div>
        @endif
    </div>
@endforeach


