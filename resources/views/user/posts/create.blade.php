@extends('layouts.user_layout')

@section('title', 'Добавить пин')

{{-- Редизайн по макету Claude Design (артефакт 2fgoNE9x9nHsAR4vY3siPk): карточки выбора
     типа пина вместо dropdown-меню, шаги прогресса, живой предпросмотр и чек-лист
     готовности справа. Стили/скрипт страницы — отдельные файлы (по примеру "Мои запасы",
     см. /admin/my-stock.css и /admin/my-stock.js), а не общий admin-panel.css/admin.js,
     т.к. это разметка только этой страницы.

     Функциональность (загрузка изображений, парсинг по ссылке, хештеги, OCR, видео,
     отправка формы) не менялась — все id/классы,
     на которые завязан общий admin.js (#creationform, #title, #alias, category_id,
     .b-parsing-type-blocks > div c id="tab-*", #link-for-parsing, #get-parsed-post-info,
     film-*, .js-upload-image-section, #post-video-upload,
     #search-input-2/#add-tag/#b-selected-tags-2, numberOfPosts/group-posts,
     #submit-creation-form, #editor) сохранены как есть. Единственное, что реально
     поменялось в поведении — выбор типа пина теперь карточки (.js-pin-types), а не
     dropdown, поэтому старый обработчик в admin.js заменён на /admin/pin-create.js.

     В макете типы пина показывали единый набор вкладок способа добавления контента
     (файл/ссылка/текст) как взаимоисключающие — в этом приложении для поста можно
     одновременно добавить и картинку, и видео, и текст, поэтому здесь они показаны
     как отдельные секции в одной карточке "Добавьте контент", а не переключаемые вкладки. --}}

@push('head-styles')
    {{-- Manrope — шрифт макета Claude Design, тот же, что и на "База контактов"/"Все посты" --}}
    <link rel="stylesheet" href="/admin/fonts/manrope/manrope.css">
    <link rel="stylesheet" href="/admin/plugins/cropperjs/cropper.min.css">
    <link rel="stylesheet" href="/admin/pin-create.css?v={{ filemtime(public_path('admin/pin-create.css')) }}">
@endpush

@push('navbar-breadcrumb')
    <li class="nav-item d-flex align-items-center">
        <ol class="breadcrumb b-navbar-breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('cabinet.home') }}">Главная</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}">Все посты</a></li>
            <li class="breadcrumb-item active">Добавить пин</li>
        </ol>
    </li>
@endpush

@section('content')
<div class="b-pin-create">

    @if (session('success'))
        @push('footer-scripts')
            <script>
                toastr.success('{{ session('success') }}');
            </script>
        @endpush
    @endif

    <div class="content-header content-header--pin-create">
        <div class="container-fluid">
            <h1 class="m-0">Добавить пин</h1>
            <p class="b-pin-create__subtitle">Сохраните идею, изображение, заметку или полезную информацию в личную коллекцию.</p>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <div class="b-pin-create__steps js-pin-steps">
                <div class="b-pin-create__step is-done" data-step="1"><span class="b-pin-create__step-dot">1</span><span>Выберите тип пина</span></div>
                <span class="b-pin-create__step-sep">›</span>
                <div class="b-pin-create__step" data-step="2"><span class="b-pin-create__step-dot">2</span><span>Добавьте контент</span></div>
                <span class="b-pin-create__step-sep">›</span>
                <div class="b-pin-create__step" data-step="3"><span class="b-pin-create__step-dot">3</span><span>Проверьте данные</span></div>
                <span class="b-pin-create__step-sep">›</span>
                <div class="b-pin-create__step" data-step="4"><span class="b-pin-create__step-dot">4</span><span>Сохраните</span></div>
            </div>

            <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data" id="creationform" name="creationform" class="b-pin-create__layout">
                @csrf
                {{-- выбранный тип пина (pin-create.js) — по нему PostController сохраняет данные фильма только для "Фильм / Сериал" --}}
                <input type="hidden" name="pin_type" id="pin-type" value="text_picture">

                <div class="b-pin-create__main">

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Тип пина</h2>
                            <span class="b-pin-card__hint">Шаг 1 — определяет поля ниже</span>
                        </div>
                        <p class="b-pin-card__desc">Выберите, какой контент вы сохраняете.</p>
                        <div class="b-pin-types js-pin-types">
                            <button type="button" class="b-pin-type is-active" data-type="text_picture">
                                <span class="b-pin-type__icon">▤</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Текст + Изображение</span>
                                    <span class="b-pin-type__desc">Заметка с картинкой</span>
                                </span>
                            </button>
                            <button type="button" class="b-pin-type" data-type="picture">
                                <span class="b-pin-type__icon">◼</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Изображение</span>
                                    <span class="b-pin-type__desc">Фото, картинка, GIF</span>
                                </span>
                            </button>
                            <button type="button" class="b-pin-type" data-type="text">
                                <span class="b-pin-type__icon">¶</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Текст</span>
                                    <span class="b-pin-type__desc">Заметка, идея, текст</span>
                                </span>
                            </button>
                            <button type="button" class="b-pin-type" data-type="film">
                                <span class="b-pin-type__icon">▷</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Фильм / Сериал</span>
                                    <span class="b-pin-type__desc">Жанры, рейтинг, описание</span>
                                </span>
                            </button>
                            <button type="button" class="b-pin-type" data-type="recipe">
                                <span class="b-pin-type__icon">☰</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Рецепт</span>
                                    <span class="b-pin-type__desc">Пока как заметка</span>
                                </span>
                            </button>
                            {{-- пин "Одежда" потом можно добавить в "Мой гардероб" (кнопка на странице поста) --}}
                            <button type="button" class="b-pin-type" data-type="clothes">
                                <span class="b-pin-type__icon">👗</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Одежда</span>
                                    <span class="b-pin-type__desc">Одежда, обувь, аксессуары</span>
                                </span>
                            </button>
                            {{-- своих полей у новости нет — главное у неё дата (поле "Дата" ниже, оно есть у любого пина) --}}
                            <button type="button" class="b-pin-type" data-type="news">
                                <span class="b-pin-type__icon">📰</span>
                                <span class="b-pin-type__body">
                                    <span class="b-pin-type__label">Новость</span>
                                    <span class="b-pin-type__desc">Событие с датой</span>
                                </span>
                            </button>
                        </div>
                    </section>

                    <section class="b-pin-card b-pin-parse js-pin-parse">
                        <label class="b-pin-parse__toggle js-pin-parse-toggle">
                            <span class="b-pin-parse__check"></span>
                            <span class="b-pin-parse__text">
                                <span class="b-pin-parse__title">Спарсить информацию <span class="b-pin-card__badge">необязательно</span></span>
                                <span class="b-pin-card__desc">Автоматически заполнит доступные поля из источника — или заполните их вручную.</span>
                            </span>
                        </label>
                        <div class="b-pin-parse__body b-parse-info">
                            <div class="b-pin-parse__row">
                                <input id="link-for-parsing" type="text" class="b-pin-input" placeholder="Вставьте ссылку на страницу…">
                                <button type="button" class="b-pin-btn b-pin-btn--primary" id="get-parsed-post-info" data-action="{{ route('parse.get-parsed-post-info') }}">
                                    <span>✦</span> Спарсить информацию
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="b-pin-card">
                        <h2>Количество</h2>
                        <div class="b-pin-count">
                            <div class="b-pin-radio-group">
                                <label class="b-pin-radio">
                                    <input type="radio" id="one-post" name="numberOfPosts" value="one-post">
                                    <span class="b-pin-radio__dot"></span>
                                    <span>Один пост</span>
                                </label>
                                <label class="b-pin-radio">
                                    <input type="radio" id="two-posts" name="numberOfPosts" value="two-posts" checked>
                                    <span class="b-pin-radio__dot"></span>
                                    <span>Несколько постов</span>
                                </label>
                            </div>
                            <label class="b-pin-checkbox">
                                <input type="checkbox" id="group-posts" name="group-posts" value="group-posts">
                                <span class="b-pin-checkbox__box"></span>
                                <span class="b-pin-checkbox__text">
                                    <span>Сгруппировать посты</span>
                                    <span class="b-pin-card__desc">Объединить несколько пинов в одну группу. Текст станет общим текстом группы — его потом правят на странице группы. Фильмы, рецепты и одежда не группируются.</span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Добавьте контент</h2>
                        </div>

                        <div class="b-pin-content-label">Изображение или видео</div>
                        <div class="upload-image-section">
                            <section class="js-upload-image-section" data-action="{{ route('image.upload-to-temp-directory') }}" data-replace-action="{{ route('image.replace-temp') }}">
                                <div class="js-images images"></div>
                                <div class="title">
                                    <figure></figure>
                                    <p>Перетащите сюда фото или видео</p>
                                    <button type="button" class="b-pin-btn b-pin-btn--ghost js-pin-choose-file">Выбрать файл</button>
                                </div>
                                <input type="file" name="files[]" multiple="multiple" accept="image/x-png,image/jpeg,image/avif,video/*">
                            </section>
                            <div id="post-video-upload" data-action="{{ route('video.upload-to-temp-directory') }}" hidden></div>
                            <div class="progress">
                                <div class="progress-bar"></div>
                                <div class="progress-value">0 %</div>
                            </div>
                            <div class="js-error-block error-block alert alert-warning alert-dismissible">
                                <button type="button" class="js-close-error-block close">×</button>
                                <i class="icon fas fa-exclamation-triangle"></i><p></p>
                            </div>
                        </div>

                        <button type="button" class="js-get-text-from-image b-pin-btn b-pin-btn--ghost" data-action="{{ route('get-text-from-image') }}">
                            Считать текст с изображения
                        </button>
                        {{-- видна, когда на форме от двух картинок (pin-create.css), окно — #modal-image-collage --}}
                        <button type="button" class="js-collage-open b-pin-btn b-pin-btn--ghost">
                            <i class="fas fa-th-large"></i> Склеить картинки
                        </button>

                        <div class="b-pin-content-label b-pin-content-label--text">Текст</div>
                        <textarea id="editor" name="content" placeholder="Введите текст пина…"></textarea>
                    </section>

                    <section class="b-pin-card">
                        <div class="b-pin-card__head">
                            <h2>Информация о пине</h2>
                        </div>

                        <div class="b-pin-fields">
                            <div class="b-pin-field b-pin-field--wide">
                                <label for="title">Название пина</label>
                                <input type="text" class="b-pin-input" name="title" id="title" placeholder="Введите название пина…" required>
                            </div>
                            <div class="b-pin-field b-pin-field--wide">
                                <label for="alias">Alias пина</label>
                                <input type="text" class="b-pin-input" name="alias" id="alias" placeholder="введите-alias…" required>
                            </div>
                            <div class="b-pin-field b-pin-field--wide">
                                <label for="category_id">Категория</label>
                                <select class="b-pin-input b-pin-input--half" name="category_id" id="category_id">
                                    <option value="">-</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @include('user.posts.parts.event_date_field', ['value' => ''])
                        </div>

                        <div class="b-parsing-type-blocks">
                            <div id="tab-text_picture"></div>
                            <div id="tab-picture"></div>
                            <div id="tab-text"></div>

                            <div id="tab-film" style="display: none;">
                                {{-- поля сохраняются в post_films и показываются карточкой на странице поста
                                     (parts/film_card.blade.php), в текст поста их вставлять не нужно --}}
                                @include('user.posts.parts.film_fields')
                            </div>

                            <div id="tab-recipe"></div>

                            <div id="tab-clothes" style="display: none;">
                                {{-- поля сохраняются в post_clothes (только у типа "Одежда") и показываются карточкой
                                     на странице поста (parts/clothes_card.blade.php); "В гардероб" переносит их в вещь --}}
                                @include('user.posts.parts.clothes_fields')
                            </div>
                        </div>

                        <div class="b-pin-content-label b-pin-content-label--spaced">Хештеги</div>
                        <div class="b-add-tag b-pin-tags">
                            <div class="input-group input-group-sm add-tag b-pin-tags__row">
                                <input id="search-input-2" type="text" class="b-pin-input" placeholder="Новый тег…" data-action="{{ route('search.hashtag') }}">
                                <button type="button" class="b-pin-btn b-pin-btn--ghost" id="add-tag" data-action="{{ route('hashtags.store') }}">Добавить тег</button>
                            </div>
                            <ul id="b-search__results-2" class="b-search__results-2"></ul>
                            {{-- data-related-action — связанные хештеги выбранного тега добавляются сами (admin.js) --}}
                            <ul id="b-selected-tags-2" class="b-selected-tags-2 b-pin-tags__selected" data-related-action="{{ route('search.related-hashtags') }}"></ul>

                            {{-- подсказки из существующих тегов по загруженным картинкам (HashtagSuggester) — клик добавляет тег;
                                 data-auto — режим "Добавлять сразу" из Настроек ИИ: admin.js сам жмёт кнопку после загрузки картинки --}}
                            <button type="button" class="js-suggest-hashtags b-pin-btn b-pin-btn--ghost" data-action="{{ route('posts.suggest-hashtags') }}"
                                    data-auto="{{ \App\Models\UserAiSetting::forUser(auth()->id())->autoApplyTags() ? 1 : 0 }}">
                                Подобрать хештеги по картинке
                            </button>
                            <ul id="b-suggested-tags-2" class="b-selected-tags-2 b-pin-tags__selected b-suggested-tags" title="Нажмите на тег, чтобы добавить"></ul>
                        </div>
                    </section>

                    <div class="b-pin-create__footer">
                        <span class="b-pin-create__footer-hint">Заполните хотя бы название или контент</span>
                        <div class="b-pin-create__footer-actions">
                            <a href="{{ route('posts.index') }}" class="b-pin-btn b-pin-btn--ghost">Отмена</a>
                            <button id="submit-creation-form" type="submit" class="b-pin-btn b-pin-btn--primary"><span>✓</span> Сохранить пин</button>
                        </div>
                    </div>
                </div>

                <aside class="b-pin-create__aside">
                    <div class="b-pin-card b-pin-preview">
                        <div class="b-pin-card__head">
                            <h2>Предпросмотр</h2>
                            <span class="b-pin-preview__type js-pin-preview-type">Текст + Изображение</span>
                        </div>
                        <div class="b-pin-preview__card">
                            <div class="b-pin-preview__image js-pin-preview-image" style="display:none;"></div>
                            <div class="b-pin-preview__body">
                                <div class="b-pin-preview__title js-pin-preview-title" style="display:none;"></div>
                                <div class="b-pin-preview__text js-pin-preview-text" style="display:none;"></div>
                                <div class="b-pin-preview__tags js-pin-preview-tags"></div>
                                <div class="b-pin-preview__empty js-pin-preview-empty">Пин пока пустой.<br>Выберите тип и добавьте контент — предпросмотр обновится.</div>
                            </div>
                        </div>
                    </div>

                    <div class="b-pin-card b-pin-checklist">
                        <div class="b-pin-card__head"><h2>Готовность</h2></div>
                        <div class="b-pin-checklist__item js-check-type is-done"><span class="b-pin-checklist__dot">✓</span>Тип выбран</div>
                        <div class="b-pin-checklist__item js-check-content"><span class="b-pin-checklist__dot">·</span>Контент не добавлен</div>
                        <div class="b-pin-checklist__item js-check-title"><span class="b-pin-checklist__dot">·</span>Название пустое</div>
                    </div>
                </aside>
            </form>
        </div>
    </section>

    {{-- окно сравнения с уже существующим постом, в котором нашлась такая же картинка
         (заполняет askAboutDuplicate() в admin.js). Вне формы — иначе его радиокнопки
         попадут в serializeArray() формы создания --}}
    {{-- только что загруженная картинка похожа на ту, что уже стоит на этой форме (UploadImageController::
         uploadImagesToTempDirectory, form_duplicates): сравнить размеры и решить — это разные картинки (отдельный
         пост), заменить ту, что на форме, или не добавлять (admin.js, showNextFormDuplicate) --}}
    <div class="modal fade" id="modal-form-duplicate" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Похожая картинка уже есть на форме</h5>
                </div>
                <div class="modal-body">
                    <p>«<span class="js-form-duplicate-file"></span>» похожа на картинку, которую вы уже добавили. Если это разные
                        картинки (например, другой кадр той же серии), добавьте её — для неё будет создан отдельный пост.</p>
                    <div class="b-duplicate-choice__compare">
                        <div class="b-duplicate-choice__option">
                            <div class="b-duplicate-choice__label">Новая <span class="b-form-duplicate__better js-form-duplicate-better-new">лучше качество</span></div>
                            <img class="js-form-duplicate-new b-duplicate-choice__thumb" src="" alt="">
                            <div class="js-form-duplicate-new-size text-muted"></div>
                        </div>
                        <div class="b-duplicate-choice__option">
                            <div class="b-duplicate-choice__label">Уже на форме <span class="b-form-duplicate__better js-form-duplicate-better-old">лучше качество</span></div>
                            <img class="js-form-duplicate-old b-duplicate-choice__thumb" src="" alt="">
                            <div class="js-form-duplicate-old-size text-muted"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-decision="add">Это разные картинки — добавить отдельным постом</button>
                    <button type="button" class="btn btn-outline-primary js-form-duplicate-replace" data-decision="replace">Заменить ту, что на форме</button>
                    <button type="button" class="btn btn-secondary" data-decision="skip">Не добавлять</button>
                </div>
            </div>
        </div>
    </div>

    @include('user.posts.parts.image_editor_modal')

    {{-- Склейка нескольких картинок формы в одну (admin.js openCollageEditor) --}}
    <div class="modal fade b-collage" id="modal-image-collage" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Склеить картинки</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">×</button>
                </div>
                <div class="modal-body b-collage__body">
                    <div class="b-collage__preview">
                        <canvas class="js-collage-canvas"></canvas>
                        <canvas class="b-collage__lens js-collage-lens"></canvas>
                        <div class="b-collage__empty js-collage-empty">Отметьте хотя бы две картинки</div>
                        <div class="b-collage__lens-hint js-collage-lens-hint">
                            <i class="fas fa-search-plus"></i> Лупа: наведите на склейку, колесо мыши — сильнее / слабее · <b class="js-collage-lens-zoom">×3</b>
                        </div>
                    </div>
                    <div class="b-collage__settings">
                        <div class="b-collage__label">Картинки <span class="b-collage__hint">порядок — как на форме, его можно поменять перетаскиванием превью</span></div>
                        <div class="b-collage__images js-collage-images"></div>

                        <div class="b-collage__label">Раскладка</div>
                        <div class="b-collage__options">
                            <button type="button" class="b-pin-btn b-pin-btn--ghost is-active" data-collage-layout="row"><i class="fas fa-grip-lines-vertical"></i> В ряд</button>
                            <button type="button" class="b-pin-btn b-pin-btn--ghost" data-collage-layout="column"><i class="fas fa-grip-lines"></i> В столбик</button>
                            <button type="button" class="b-pin-btn b-pin-btn--ghost" data-collage-layout="grid"><i class="fas fa-th-large"></i> Сеткой</button>
                        </div>

                        <div class="b-collage__label">Отступ <span class="b-collage__hint js-collage-gap-value">2 %</span></div>
                        <input type="range" class="b-collage__range js-collage-gap" min="0" max="10" step="1" value="2">

                        <div class="b-collage__label">Цвет фона</div>
                        <input type="color" class="b-collage__color js-collage-bg" value="#ffffff">

                        <label class="b-pin-checkbox b-collage__remove">
                            <input type="checkbox" class="js-collage-remove-sources">
                            <span class="b-pin-checkbox__box"></span>
                            <span class="b-pin-checkbox__text"><span>Убрать исходные картинки с формы</span></span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary js-collage-save">Склеить и добавить</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade b-dup-merge" id="modal-duplicate-merge" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="b-dup-merge__head">
                    <div>
                        <h5 class="b-dup-merge__title js-dup-merge-title"></h5>
                        <a class="b-dup-merge__link js-dup-merge-link" href="#" target="_blank" rel="noopener"></a>
                    </div>
                    <span class="b-dup-merge__counter js-dup-merge-counter"></span>
                </div>
                <div class="b-dup-merge__body js-dup-merge-body"></div>
                <div class="b-dup-merge__footer">
                    <button type="button" class="b-pin-btn b-pin-btn--ghost js-dup-merge-cancel">Отмена</button>
                    <span class="b-dup-merge__footer-gap"></span>
                    <button type="button" class="b-pin-btn b-pin-btn--ghost js-dup-merge-separate"></button>
                    <button type="button" class="b-pin-btn b-pin-btn--primary js-dup-merge-apply"></button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('footer-scripts')
    <script src="/admin/plugins/cropperjs/cropper.min.js"></script>
    <script src="/admin/film-fields.js?v={{ filemtime(public_path('admin/film-fields.js')) }}"></script>
    <script src="/admin/event-date.js?v={{ filemtime(public_path('admin/event-date.js')) }}"></script>
    <script src="/admin/pin-create.js?v={{ filemtime(public_path('admin/pin-create.js')) }}"></script>
@endpush
