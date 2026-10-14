@extends('layouts.user_layout')

@section('title', 'Редактировать пост')

{{-- Тот же язык дизайна, что и у "Добавить пин" (create.blade.php, /admin/pin-create.css):
     Manrope, карточки на светло-сером фоне, липкий футер с кнопками. Стили, которые есть
     только у этой страницы (галерея изображений, видео, палитра, сайдбар
     со сведениями о посте) — в /admin/post-edit.css.

     Функциональность не менялась — все id/классы, на которые завязаны admin.js и
     palette-editor.js (#editform, #submit-edit-form, #title, #alias, #category_id, #editor,
     #search-input-2/#add-tag/#b-search__results-2/#b-selected-tags-2, .js-suggest-hashtags,
     #post-images и .js-post-images__*, #post-video и .js-post-video__*, .js-palette-*,
     #modal-duplicate-image-choice и .js-duplicate-choice__*), сохранены как есть. --}}

@push('head-styles')
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
    {{-- общие стили формы пина (.b-pin-card, .b-pin-input, .b-pin-btn, поля фильма и т.п.) --}}
    <link rel="stylesheet" href="/admin/pin-create.css?v={{ filemtime(public_path('admin/pin-create.css')) }}">
    <link rel="stylesheet" href="/admin/post-edit.css?v={{ filemtime(public_path('admin/post-edit.css')) }}">
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Все посты</a></li>
            <li class="breadcrumb-item active">Редактировать пост</li>
        </ol>
    </li>
@endpush

@section('content')
@php $paletteCover = $post->coverImagePath(); @endphp
<div class="b-pin-create b-post-edit">

    <div class="content-header content-header--pin-create">
        <div class="container-fluid">
            <h1 class="m-0">Редактировать пост</h1>
            <p class="b-pin-create__subtitle">
                @if ($post->title)
                    «{{ $post->title }}»
                @else
                    Пост #{{ $post->id }}
                @endif
            </p>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            {{-- data-id — id поста для старых обработчиков admin.js, ищущих его через closest('.card') --}}
            <div class="card b-post-edit__card" data-id="{{ $post->id }}">
            <form action="{{ route('posts.update', [$post->id]) }}" method="POST" enctype="multipart/form-data" id="editform" name="editform" class="b-pin-create__layout">
                @csrf
                @method('PUT')

                <div class="b-pin-create__main">

                    {{-- тип поста: "Текст / изображение" определяется по содержимому сам (Post::booted) —
                         добавили текст к картинке, пост стал "Текст + Изображение"; фильм и рецепт выбирают
                         вручную. Переключает видимость полей фильма (post-edit.js) --}}
                    @php
                        $postType = in_array($post->type, \App\Models\Post::MANUAL_TYPES, true) ? $post->type : 'auto';
                        $contentTypeLabel = \App\Models\Post::TYPE_LABELS[$post->detectContentType()];
                    @endphp
                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Тип поста</h2>
                        </div>
                        <input type="hidden" name="post_type" id="post-type" value="{{ $postType }}" data-initial="{{ $postType }}">
                        <div class="b-pin-types b-post-edit__types js-post-types">
                            <button type="button" class="b-pin-type {{ $postType === 'auto' ? 'is-active' : '' }}" data-type="auto">
                                <span class="b-pin-type__icon">▤</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Текст / изображение</span>
                                    <span class="b-pin-type__desc">По содержимому — сейчас «{{ $contentTypeLabel }}»</span>
                                </span>
                            </button>
                            <button type="button" class="b-pin-type {{ $postType === 'film' ? 'is-active' : '' }}" data-type="film">
                                <span class="b-pin-type__icon">▷</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Фильм / Сериал</span>
                                    <span class="b-pin-type__desc">Жанры, рейтинг, описание</span>
                                </span>
                            </button>
                            <button type="button" class="b-pin-type {{ $postType === 'recipe' ? 'is-active' : '' }}" data-type="recipe">
                                <span class="b-pin-type__icon">☰</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Рецепт</span>
                                    <span class="b-pin-type__desc">Пока как заметка</span>
                                </span>
                            </button>
                            <button type="button" class="b-pin-type {{ $postType === 'clothes' ? 'is-active' : '' }}" data-type="clothes">
                                <span class="b-pin-type__icon">👗</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Одежда</span>
                                    <span class="b-pin-type__desc">Можно добавить в гардероб</span>
                                </span>
                            </button>
                        </div>
                    </section>

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Основное</h2>
                        </div>
                        <div class="b-pin-fields">
                            <div class="b-pin-field b-pin-field--wide">
                                <label for="title">Название поста</label>
                                <input type="text" class="b-pin-input" name="title" id="title" value="{{ $post->title }}" placeholder="Введите название поста…">
                            </div>
                            <div class="b-pin-field">
                                <label for="alias">Alias поста</label>
                                <input type="text" class="b-pin-input" name="alias" id="alias" value="{{ $post->alias }}" placeholder="введите-alias…" required>
                            </div>
                            <div class="b-pin-field">
                                <label for="category_id">Категория</label>
                                <select class="b-pin-input" name="category_id" id="category_id">
                                    <option value="" {{ empty($post->category_id) ? 'selected' : '' }}>-</option>
                                    @foreach($categories as $category)
                                        <option style="font-weight: 600;" value="{{ $category->id }}" {{ $post->category_id === $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                        @if (!empty($category->children))
                                            @foreach($category->children as $childrenCategory)
                                                <option value="{{ $childrenCategory->id }}" {{ $post->category_id === $childrenCategory->id ? 'selected' : '' }}> - {{ $childrenCategory->title }}</option>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Текст</h2>
                        </div>
                        <textarea id="editor" name="content" placeholder="Введите текст поста…">{{ $post->content }}</textarea>
                    </section>

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Изображения</h2>
                            <span class="b-pin-card__hint">Изменения сохраняются сразу</span>
                        </div>

                        <div class="b-post-images" id="post-images"
                             data-post-id="{{ $post->id }}"
                             data-add-action="{{ route('posts.images.add', $post->id) }}"
                             data-rename-action="{{ route('posts.images.rename', $post->id) }}"
                             data-transform-action="{{ route('posts.images.transform', $post->id) }}"
                             data-delete-action="{{ route('posts.images.delete') }}"
                             data-confirm-replace-action="{{ route('posts.images.confirm-replace-duplicate', $post->id) }}"
                             data-confirm-add-action="{{ route('posts.images.confirm-add-duplicate', $post->id) }}">

                            <div class="b-post-edit__gallery">
                                <div class="b-post-images__list js-post-images__list">
                                    @foreach ($postImages as $name => $image)
                                        <div class="b-post-images__item" data-name="{{ $name }}" data-extension="{{ $image['extension'] }}" data-path="{{ $image['path'] }}">
                                            <a class="b-post-images__thumb" data-fancybox="post-images" data-src="/storage{{ $image['path'] }}" style="background-image: url(/storage{{ $image['path'] }})"></a>
                                            {{-- повернуть/отразить — сразу сохраняется (admin.js, PostController::transformImage);
                                                 ту же разметку для добавленных картинок строит buildItem() в admin.js --}}
                                            <div class="b-post-images__transform">
                                                <button type="button" class="js-post-images__transform" data-action="rotate_left" title="Повернуть влево"><i class="fas fa-undo"></i></button>
                                                <button type="button" class="js-post-images__transform" data-action="rotate_right" title="Повернуть вправо"><i class="fas fa-redo"></i></button>
                                                <button type="button" class="js-post-images__transform" data-action="flip_h" title="Отразить по горизонтали"><i class="fas fa-arrows-alt-h"></i></button>
                                                <button type="button" class="js-post-images__transform" data-action="flip_v" title="Отразить по вертикали"><i class="fas fa-arrows-alt-v"></i></button>
                                            </div>
                                            <div class="b-post-images__controls">
                                                <div class="b-post-images__rename">
                                                    <input type="text" class="form-control form-control-sm js-post-images__name-input" value="{{ $name }}">
                                                    <button type="button" class="btn btn-sm btn-outline-info js-post-images__rename" title="Переименовать">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </div>
                                                <div class="b-post-images__buttons">
                                                    <button type="button" class="btn btn-sm btn-outline-danger js-post-images__delete" title="Удалить">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <label class="b-post-edit__add-tile js-post-images__add-label">
                                    <span class="b-post-edit__add-icon"><i class="fas fa-plus"></i></span>
                                    <span>Добавить изображение</span>
                                    <input type="file" class="js-post-images__add-input" accept="image/*" hidden>
                                </label>
                            </div>

                            <div class="js-post-images__error b-post-edit__error" style="display: none;"></div>
                        </div>
                    </section>

                    {{-- палитра обложки: поправить вручную — убрать цвет, пометить фон, сделать цвет главным,
                         добавить пипеткой с картинки. Всё сохраняется сразу (palette-editor.js,
                         PostController::updatePalette/resetPalette); пока обложка та же, сама не пересчитывается --}}
                    <section class="b-pin-card">
                        @include('user.posts.parts.palette_editor', ['post' => $post])
                    </section>

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Видео</h2>
                            <span class="b-pin-card__hint">Изменения сохраняются сразу</span>
                        </div>

                        <div class="b-post-images__video b-post-edit__video" id="post-video"
                             data-add-action="{{ route('posts.video.add', $post->id) }}"
                             data-delete-action="{{ route('posts.video.delete', $post->id) }}">

                            <div class="js-post-video__player b-post-edit__video-player">
                                @if ($post->video_url)
                                    <video src="{{ $post->video_url }}" controls preload="metadata"></video>
                                @endif
                            </div>

                            <div class="b-post-edit__video-actions">
                                <label class="b-pin-btn js-post-video__add-label">
                                    <i class="fas fa-video"></i>
                                    <span class="js-post-video__add-label-text">{{ $post->video_url ? 'Заменить видео' : 'Добавить видео' }}</span>
                                    <input type="file" class="js-post-video__add-input" accept="video/*" hidden>
                                </label>
                                <button type="button" class="b-pin-btn b-post-edit__danger-btn js-post-video__delete" style="{{ $post->video_url ? '' : 'display: none;' }}">
                                    <i class="fas fa-trash"></i> Удалить видео
                                </button>
                            </div>

                            <div class="js-post-video__error b-post-edit__error" style="display: none;"></div>
                        </div>
                    </section>

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Хештеги</h2>
                        </div>
                        <div class="b-add-tag b-pin-tags">
                            <div class="input-group input-group-sm add-tag b-pin-tags__row">
                                <input id="search-input-2" type="text" class="b-pin-input" placeholder="Найти или создать тег…" data-action="{{ route('search.hashtag') }}">
                                <button type="button" class="b-pin-btn b-pin-btn--ghost" id="add-tag" data-action="{{ route('hashtags.store') }}">Добавить тег</button>
                            </div>
                            <ul id="b-search__results-2" class="b-search__results-2"></ul>
                            {{-- data-related-action — связанные хештеги выбранного тега добавляются сами (admin.js) --}}
                            <ul id="b-selected-tags-2" class="b-selected-tags-2 b-pin-tags__selected" data-related-action="{{ route('search.related-hashtags') }}">
                                @if (!empty($post->hashtags))
                                    @foreach($post->hashtags as $hashtag)
                                        <span class="tag" data-id="{{ $hashtag->id }}" data-name="{{ $hashtag->title }}">#{{ $hashtag->title }}<span class="icon font-icon fas close"></span></span>
                                    @endforeach
                                @endif
                            </ul>

                            {{-- подсказки из существующих тегов по картинкам поста (HashtagSuggester) — клик добавляет тег --}}
                            <button type="button" class="js-suggest-hashtags b-pin-btn b-pin-btn--ghost" data-action="{{ route('posts.suggest-hashtags-for-post', $post->id) }}" data-post-images="1">
                                <span>✦</span> Подобрать хештеги по картинке
                            </button>
                            <ul id="b-suggested-tags-2" class="b-selected-tags-2 b-pin-tags__selected b-suggested-tags" title="Нажмите на тег, чтобы добавить"></ul>
                        </div>
                    </section>

                    {{-- данные фильма/сериала (post_films) — видны только у типа "Фильм / Сериал"
                         (переключатель типа выше); у других типов сервер данные фильма не сохраняет --}}
                    <section class="b-pin-card js-post-film-section" @if ($postType !== 'film') hidden @endif>
                        <div class="b-pin-card__head">
                            <h2>Фильм / Сериал</h2>
                        </div>
                        @include('user.posts.parts.film_fields', ['film' => $post->film])
                    </section>

                    {{-- данные вещи (post_clothes) — видны только у типа "Одежда"; у других типов сервер их не сохраняет --}}
                    <section class="b-pin-card js-post-clothes-section" @if ($postType !== 'clothes') hidden @endif>
                        <div class="b-pin-card__head">
                            <h2>Одежда</h2>
                            <span class="b-pin-card__hint">«В гардероб» перенесёт эти поля в вещь</span>
                        </div>
                        @include('user.posts.parts.clothes_fields', ['clothes' => $post->clothes])
                    </section>

                    <div class="b-pin-create__footer">
                        <span class="b-pin-create__footer-hint">Изображения, видео и палитра уже сохранены — кнопка сохраняет остальные поля</span>
                        <div class="b-pin-create__footer-actions">
                            <a href="{{ route('posts.index') }}" class="b-pin-btn">Отменить</a>
                            <button id="submit-edit-form" type="submit" class="b-pin-btn b-pin-btn--primary"><span>✓</span> Сохранить изменения</button>
                        </div>
                    </div>
                </div>

                <aside class="b-pin-create__aside">
                    <div class="b-pin-card b-post-edit__summary">
                        <div class="b-post-edit__cover" @if ($paletteCover) style="background-image: url('/storage{{ $paletteCover }}')" @endif>
                            @unless ($paletteCover)
                                <span>Нет обложки</span>
                            @endunless
                        </div>
                        <dl class="b-post-edit__facts">
                            <dt>ID</dt>
                            <dd>#{{ $post->id }}</dd>
                            <dt>Тип</dt>
                            <dd class="js-post-type-label">{{ $post->typeLabel() }}</dd>
                            @if ($post->created_at)
                                <dt>Создан</dt>
                                <dd>{{ $post->created_at->format('d.m.Y H:i') }}</dd>
                            @endif
                            @if ($post->updated_at)
                                <dt>Изменён</dt>
                                <dd>{{ $post->updated_at->format('d.m.Y H:i') }}</dd>
                            @endif
                            <dt>Изображений</dt>
                            <dd>{{ count($postImages) }}</dd>
                        </dl>
                        <a href="{{ route('posts.show', $post->id) }}" class="b-pin-btn b-post-edit__open" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt"></i> Открыть пост
                        </a>
                    </div>
                </aside>
            </form>
            </div>
        </div>
    </section>

    <div class="modal fade" id="modal-duplicate-image-choice" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Похожее изображение уже есть в другом посте</h5>
                </div>
                <div class="modal-body">
                    <p class="js-duplicate-choice__message"></p>
                    <div class="b-duplicate-choice__compare">
                        <div class="b-duplicate-choice__option">
                            <div class="b-duplicate-choice__label">Новое</div>
                            <img class="js-duplicate-choice__new-thumb b-duplicate-choice__thumb" src="" alt="">
                            <div class="js-duplicate-choice__new-size text-muted"></div>
                        </div>
                        <div class="b-duplicate-choice__option">
                            <div class="b-duplicate-choice__label">Уже есть в посте #<span class="js-duplicate-choice__post-id"></span></div>
                            <img class="js-duplicate-choice__old-thumb b-duplicate-choice__thumb" src="" alt="">
                            <div class="js-duplicate-choice__old-size text-muted"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="b-pin-btn b-pin-btn--primary js-duplicate-choice__confirm-replace">Заменить: перенести сюда, удалить из поста #<span class="js-duplicate-choice__post-id-2"></span></button>
                    <button type="button" class="b-pin-btn js-duplicate-choice__confirm-add">Это разные картинки — всё равно добавить</button>
                    <button type="button" class="b-pin-btn" data-dismiss="modal">Отмена, не добавлять</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('footer-scripts')
    <script src="/admin/film-fields.js?v={{ filemtime(public_path('admin/film-fields.js')) }}"></script>
    <script src="/admin/palette-editor.js?v={{ filemtime(public_path('admin/palette-editor.js')) }}"></script>
    <script src="/admin/post-edit.js?v={{ filemtime(public_path('admin/post-edit.js')) }}"></script>
    <script>
        // просмотр изображений поста в полном размере (в том числе добавленных динамически) —
        // Fancybox подключается в самом низу layout'а, поэтому инициализация идёт через
        // стек footer-scripts, а не инлайн-скриптом прямо в теле страницы
        Fancybox.bind('[data-fancybox="post-images"]', {
            infinite: false
        });
    </script>
@endpush
