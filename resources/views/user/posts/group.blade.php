@extends('layouts.user_layout')

@section('title', 'Группа постов')

{{-- Страница группы постов: на ней группу и смотрят, и редактируют — своих страниц у постов группы нет
     (PostController::show()/edit() ведут сюда). Группа бывает из изображений или из текстов:
     название и категория у неё общие, хештеги — общие (блок "Общее") и у каждого поста ещё свои (под его
     карточкой, parts/group_item_own_tags.blade.php), у текстов — ещё и свой текст у каждого поста.
     Поведение — /admin/post-group.js; блок хештегов — тот же, что на форме поста (admin.js,
     #search-input-2/#add-tag/#b-search__results-2/#b-selected-tags-2). --}}

@php
    $isText = $kind === \App\Models\Post::TYPE_TEXT;
    // у группы текстов два режима — просмотр и редактирование (?edit=1), см. PostController::showGroup()
    $pluralizePosts = function ($n) {
        $mod100 = abs($n) % 100;
        $mod10 = $mod100 % 10;
        if ($mod100 >= 11 && $mod100 <= 19) {
            return 'постов';
        }
        if ($mod10 === 1) {
            return 'пост';
        }
        return $mod10 >= 2 && $mod10 <= 4 ? 'поста' : 'постов';
    };
@endphp

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Все посты</a></li>
            <li class="breadcrumb-item active">Группа постов</li>
        </ol>
    </li>
@endpush

@push('head-styles')
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
    {{-- общие стили формы пина (.b-pin-card, .b-pin-input, .b-pin-btn, хештеги) --}}
    <link rel="stylesheet" href="/admin/pin-create.css?v={{ filemtime(public_path('admin/pin-create.css')) }}">
@endpush

@section('content')
<div class="content-header content-header--post-show">
    <div class="container-fluid">
        <div class="b-post-show-header">
            <a href="{{ route('posts.index') }}" class="b-post-show__back">
                <i class="fas fa-arrow-left"></i> Назад к постам
            </a>

            {{-- отметка "Использовано" — одна на всю группу (PostController::bulkSetUsed, post-group.js) --}}
            @php
                // весь текст группы в буфер обмена, посты через пустую строку (post-group.js) — только у группы текстов
                $copyButton = $isText
                    ? '<button type="button" class="b-post-show__btn js-post-group-copy" title="Скопировать тексты всех постов группы по порядку">'
                        . '<i class="far fa-copy"></i> Скопировать текст</button>'
                    : '';
                $usedButton ='<button type="button" class="b-post-show__btn ' . ($isUsed ? 'b-post-show__btn--done' : '') . ' js-post-group-used"'
                    . ' data-url="' . e(route('posts.bulk.used')) . '" data-id="' . e($headId) . '" data-used="' . ($isUsed ? '1' : '0') . '"'
                    . ' title="Отметка одна на всю группу — нажмите, чтобы поставить или снять">'
                    . '<i class="fas fa-check-double"></i> <span>' . ($isUsed ? 'Использовано' : 'Отметить использованной') . '</span></button>';
            @endphp
            {{-- группа текстов в режиме просмотра — только кнопка перехода к редактированию --}}
            @if (!$editing)
            <div class="b-post-show-header__actions">
                {!! $usedButton !!}
                {!! $copyButton !!}
                {{-- экспорт в файл: каждый пост — абзац (PostController::exportGroup) --}}
                <a href="{{ route('posts.groups.export', [$groupId, 'docx']) }}" class="b-post-show__btn" title="Скачать тексты группы файлом Word">
                    <i class="fas fa-file-word"></i> DOCX
                </a>
                <a href="{{ route('posts.groups.export', [$groupId, 'pdf']) }}" class="b-post-show__btn" title="Скачать тексты группы файлом PDF">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('posts.groups.show', [$groupId, 'edit' => 1]) }}" class="b-post-show__btn b-post-show__btn--accent">
                    <i class="fas fa-pencil-alt"></i> Редактировать
                </a>
            </div>
            @else
            <div class="b-post-show-header__actions">
                {!! $usedButton !!}
                {!! $copyButton !!}
                @if ($isText)
                    <a href="{{ route('posts.groups.show', $groupId) }}" class="b-post-show__btn">
                        <i class="fas fa-eye"></i> Просмотр
                    </a>
                    <button type="button" class="b-post-show__btn js-post-group-add-text">
                        <i class="fas fa-plus"></i> Новый текст
                    </button>
                @else
                    {{-- картинки и видео группы архивом, в её порядке (PostController::downloadGroup) --}}
                    <a href="{{ route('posts.groups.download', $groupId) }}" class="b-post-show__btn" title="Скачать картинки группы архивом — в том же порядке">
                        <i class="fas fa-cloud-download-alt"></i> Скачать архивом
                    </a>
                    <label class="b-post-show__btn mb-0 js-post-group-upload-label">
                        <i class="fas fa-upload"></i> <span>Загрузить изображения</span>
                        <input type="file" class="js-post-group-upload" accept="image/*" multiple hidden>
                    </label>
                @endif
                <button type="button" class="b-post-show__btn b-post-show__btn--accent js-post-group-add">
                    <i class="fas fa-layer-group"></i> Добавить посты
                </button>
                <button type="button" class="b-post-show__btn b-post-show__btn--danger js-post-group-dissolve">
                    <i class="fas fa-object-ungroup"></i> Разгруппировать
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="b-post-group b-pin-create" id="post-group"
             data-kind="{{ $kind }}"
             data-editing="{{ $editing ? '1' : '0' }}"
             data-update-action="{{ route('posts.groups.update', $groupId) }}"
             data-reorder-action="{{ route('posts.groups.reorder', $groupId) }}"
             data-dissolve-action="{{ route('posts.groups.dissolve', $groupId) }}"
             data-candidates-url="{{ route('posts.groups.candidates', $groupId) }}"
             data-attach-url="{{ route('posts.groups.attach', $groupId) }}"
             data-upload-action="{{ route('posts.groups.upload', $groupId) }}"
             data-add-text-action="{{ route('posts.groups.add-text', $groupId) }}"
             data-hashtag-search-action="{{ route('search.hashtag') }}"
             data-hashtag-store-action="{{ route('hashtags.store') }}">

            <div class="b-post-group__intro">
                <h1 class="b-post-group__title">{{ $isText ? 'Группа текстов' : 'Группа изображений' }}</h1>
                <p class="b-post-group__hint">
                    <span class="js-post-group-count" data-forms="пост,поста,постов">{{ $posts->count() }} {{ $pluralizePosts($posts->count()) }}</span>
                    · {{ $isText ? 'название и категория' : 'название, категория и текст' }} — общие для всех постов группы, хештеги — общие и у каждого поста свои{{ $isText ? ', текст у каждого свой' : '' }}
                    {{-- знаков во всех текстах группы — считает post-group.js --}}
                    @if ($isText)
                        · <span class="b-post-group__chars-total js-post-group-chars-total" title="Знаков во всех текстах группы, с пробелами"></span>
                    @endif
                </p>
            </div>

            <form id="post-group-form" class="b-post-group__form" autocomplete="off">
                @if (!$editing)
                {{-- просмотр группы текстов: общие поля только показываем --}}
                @php $groupCategory = $categories->firstWhere('id', $categoryId); @endphp
                <section class="b-pin-card">
                    <h2 class="b-post-show__title">{{ $title ?: 'Без названия' }}</h2>
                    @if ($eventDateLabel)
                        <div class="b-event-date-chip" title="Дата группы"><i class="far fa-calendar-alt"></i> {{ $eventDateLabel }}</div>
                    @endif
                    @if ($hashtags->isNotEmpty() || $groupCategory)
                        <div class="b-post-show__tags">
                            @foreach ($hashtags as $hashtag)
                                <span class="b-post-show__tag">#{{ $hashtag->title }}</span>
                            @endforeach
                            @if ($groupCategory)
                                <span class="b-post-show__category-chip">{{ $groupCategory->title ?: $groupCategory->alias }}</span>
                            @endif
                        </div>
                    @endif
                </section>
                @else
                <section class="b-pin-card">
                    <div class="b-pin-card__head">
                        <h2>Общее</h2>
                    </div>
                    <div class="b-pin-fields">
                        <div class="b-pin-field b-pin-field--wide">
                            <label for="group-title">Название</label>
                            <input type="text" class="b-pin-input" name="title" id="group-title" value="{{ $title }}" maxlength="255" placeholder="Название группы…">
                        </div>
                        <div class="b-pin-field">
                            <label for="group-category">Категория</label>
                            <select class="b-pin-input" name="category_id" id="group-category">
                                <option value="" {{ empty($categoryId) ? 'selected' : '' }}>-</option>
                                @foreach($categories as $category)
                                    <option style="font-weight: 600;" value="{{ $category->id }}" {{ $categoryId === $category->id ? 'selected' : '' }}>{{ $category->title }}</option>
                                    @if (!empty($category->children))
                                        @foreach($category->children as $childrenCategory)
                                            <option value="{{ $childrenCategory->id }}" {{ $categoryId === $childrenCategory->id ? 'selected' : '' }}> - {{ $childrenCategory->title }}</option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        {{-- дата у группы общая, как название (PostController::updateGroup) --}}
                        @include('user.posts.parts.event_date_field', ['value' => $eventDateInput])
                        {{-- у группы изображений текст общий — сохраняется у всех её постов (у группы текстов свой у каждого) --}}
                        @unless ($isText)
                            <div class="b-pin-field b-pin-field--wide b-post-group__text">
                                <label for="group-text">Текст группы <span class="b-pin-card__badge">общий для всех постов</span></label>
                                <textarea name="group_text" id="group-text" placeholder="Общий текст для всех постов группы…">{{ $groupText }}</textarea>
                            </div>
                        @endunless
                    </div>

                    <div class="b-post-group__tags">
                        <label for="search-input-2">Общие хештеги <span class="b-post-group__tags-hint">— у всех постов группы; свои хештеги поста — под его карточкой</span></label>
                        <div class="b-add-tag b-pin-tags">
                            <div class="input-group input-group-sm add-tag b-pin-tags__row">
                                <input id="search-input-2" type="text" class="b-pin-input" placeholder="Найти или создать тег…" data-action="{{ route('search.hashtag') }}">
                                <button type="button" class="b-pin-btn b-pin-btn--ghost" id="add-tag" data-action="{{ route('hashtags.store') }}">Добавить тег</button>
                            </div>
                            <ul id="b-search__results-2" class="b-search__results-2"></ul>
                            <ul id="b-selected-tags-2" class="b-selected-tags-2 b-pin-tags__selected" data-related-action="{{ route('search.related-hashtags') }}">
                                @foreach($hashtags as $hashtag)
                                    <span class="tag" data-id="{{ $hashtag->id }}" data-name="{{ $hashtag->title }}">#{{ $hashtag->title }}<span class="icon font-icon fas close"></span></span>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </section>
                @endif

                <section class="b-pin-card">
                    <div class="b-pin-card__head">
                        <h2>Посты</h2>
                        <span class="b-pin-card__hint">
                            @if (!$editing)
                                Перетаскивайте карточки, чтобы изменить порядок
                            @else
                                {{ $isText ? 'Перетаскивайте за ручку, чтобы изменить порядок' : 'Перетаскивайте, чтобы изменить порядок · клик — просмотр' }}
                            @endif
                        </span>
                    </div>

                    <ul class="b-post-group__list {{ $isText ? 'b-post-group__list--text' : 'b-post-group__list--picture' }} js-post-group-list">
                        @foreach ($posts as $post)
                            @include('user.posts.parts.group_item', ['post' => $post, 'position' => $loop->iteration])
                        @endforeach
                    </ul>

                    <div class="b-post-group__errors js-post-group-errors" style="display: none;"></div>
                </section>

                @if ($editing)
                <div class="b-pin-create__footer">
                    <span class="b-pin-create__footer-hint">Порядок и состав группы сохраняются сразу — кнопка сохраняет {{ $isText ? 'название, категорию, хештеги и тексты' : 'название, категорию, текст, дату и хештеги' }}</span>
                    <div class="b-pin-create__footer-actions">
                        <button type="button" class="b-pin-btn b-pin-btn--primary js-post-group-save"><span>✓</span> Сохранить</button>
                    </div>
                </div>
                @endif
            </form>
        </div>
    </div>
</section>

<div class="modal fade" id="modal-post-group-add" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить посты в группу</h5>
            </div>
            <div class="modal-body">
                <div class="b-film-materials__picker-bar">
                    <input type="text" class="form-control js-post-group-search" placeholder="Поиск по названию, тексту или хештегу">
                </div>
                <p class="b-post-group__hint mb-3">
                    Здесь только {{ $isText ? 'посты с одним текстом' : 'посты с картинками — без текста или с тем же текстом, что у группы' }}, которые ещё не в группе.
                    Название и категория у добавленных станут общими с группой, общие хештеги группы добавятся к ним,
                    а их прежние хештеги останутся у них как собственные.
                </p>
                <div class="b-film-materials__candidates js-post-group-candidates"></div>
                <div class="b-film-materials__empty js-post-group-none" style="display: none">Подходящих постов не найдено.</div>
                <button type="button" class="b-post-show__btn b-film-materials__more js-post-group-more" style="display: none">Показать ещё</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary js-post-group-submit" disabled>Добавить</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

{{-- подтверждение удаления поста — то же окно и те же слова, что у "Удалить" на "Все посты" и на странице
     поста (includes/modal_delete_item.blade.php); своё, потому что то обслуживает admin.js по id страницы --}}
<div class="modal fade" id="modal-post-group-delete" tabindex="-1" role="dialog" aria-labelledby="modalPostGroupDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPostGroupDeleteLabel">Вы уверены, что хотите удалить пост?</h5>
            </div>
            <div class="modal-body">Пост попадёт в корзину — оттуда его можно вернуть в течение {{ \App\Components\PostTrash::KEEP_DAYS }} дней, и он вернётся в группу.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary js-post-group-delete-confirm">Удалить</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

{{-- палитра обложки поста группы (кнопка на плитке): редактор — тот же, что на странице поста; каждый раз
     берётся свежая заготовка из <template>, данные поста подставляет post-group.js --}}
{{-- загружаемая в группу картинка похожа на уже существующую — сравнить и решить (PostController::uploadToGroup,
     post-group.js): заменить старую новой, добавить как другую или пропустить --}}
@unless ($isText)
    <div class="modal fade" id="modal-post-group-duplicate" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Похожая картинка уже есть</h5>
                </div>
                <div class="modal-body">
                    <p class="js-post-group-duplicate-message"></p>
                    <div class="b-duplicate-choice__compare">
                        <div class="b-duplicate-choice__option">
                            <div class="b-duplicate-choice__label">Новая</div>
                            <img class="js-post-group-duplicate-new b-duplicate-choice__thumb" src="" alt="">
                            <div class="js-post-group-duplicate-new-size text-muted"></div>
                        </div>
                        <div class="b-duplicate-choice__option">
                            <div class="b-duplicate-choice__label">Уже есть в посте #<span class="js-post-group-duplicate-post"></span></div>
                            <img class="js-post-group-duplicate-old b-duplicate-choice__thumb" src="" alt="">
                            <div class="js-post-group-duplicate-old-size text-muted"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary js-post-group-duplicate-replace">Заменить старую новой</button>
                    <button type="button" class="btn btn-primary js-post-group-duplicate-add">Это разные картинки — добавить</button>
                    <button type="button" class="btn btn-secondary js-post-group-duplicate-skip">Не добавлять</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-post-group-palette" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body b-pin-create js-post-group-palette-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>
    <template id="post-group-palette-template">
        @include('user.posts.parts.palette_editor', ['post' => null])
    </template>
@endunless

@push('footer-scripts')
    <script src="/admin/palette-editor.js?v={{ filemtime(public_path('admin/palette-editor.js')) }}"></script>
    <script src="/admin/event-date.js?v={{ filemtime(public_path('admin/event-date.js')) }}"></script>
    <script src="/admin/post-group.js?v={{ filemtime(public_path('admin/post-group.js')) }}"></script>
@endpush

@endsection
