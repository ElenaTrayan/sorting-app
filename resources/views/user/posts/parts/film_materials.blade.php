{{-- Блок "Материалы" на странице пина "Фильм / Сериал" (posts/show.blade.php): посты, прикреплённые к фильму
     (posts.film_post_id), по разделам "Кадры / Промо / Видео", и окно "Добавить материалы" — выбор своих постов.
     Клик по материалу — то же превью поста, что и на "Все посты" (parts/post_preview.blade.php), со стрелками
     внутри раздела. Поведение — /admin/film-materials.js, стили — .b-film-materials* в admin-panel.css.

     @param \App\Models\Post $film      пин "Фильм / Сериал"
     @param array            $sections  [раздел => коллекция постов], PostController::filmMaterialsBySection() --}}
@php
    $materialsTotal = array_sum(array_map('count', $sections));
    // открыт первый непустой раздел; пока ничего не прикреплено — первый ("Кадры")
    $activeSection = array_key_first(array_filter($sections, fn ($materials) => $materials->isNotEmpty())) ?? array_key_first($sections);
@endphp
<div class="b-film-materials js-film-materials"
     data-candidates-url="{{ route('posts.film-materials.candidates', $film->id) }}"
     data-attach-url="{{ route('posts.film-materials.attach', $film->id) }}">
    <div class="b-film-materials__head">
        <div class="b-post-show__section-head">
            <i class="fas fa-photo-video"></i>
            <span class="b-post-show__section-label">Материалы</span>
            @if ($materialsTotal > 0)
                <span class="b-post-show__section-count">{{ $materialsTotal }}</span>
            @endif
        </div>
        <button type="button" class="b-post-show__btn b-post-show__btn--accent js-film-materials-add">
            <i class="fas fa-plus"></i> Добавить материалы
        </button>
    </div>

    {{-- вкладки разделов видны всегда, в том числе пока к фильму ничего не прикреплено --}}
        <div class="b-posts-toolbar__tabs">
            @foreach ($sections as $section => $materials)
                <button type="button" class="b-posts-toolbar__tab js-film-materials-tab @if ($section === $activeSection) b-posts-toolbar__tab--active @endif" data-section="{{ $section }}">
                    {{ \App\Models\Post::MATERIAL_SECTION_LABELS[$section] }} ({{ $materials->count() }})
                </button>
            @endforeach
        </div>

        @foreach ($sections as $section => $materials)
            <div class="b-film-materials__grid js-film-materials-panel" data-section="{{ $section }}" @if ($section !== $activeSection) style="display: none" @endif>
                @forelse ($materials as $material)
                    <div class="b-film-materials__item">
                        {{-- своя Fancybox-группа на раздел — стрелки листают только открытую вкладку --}}
                        <a href="{{ route('posts.show', $material->id) }}?fragment=1" data-fancybox="film-materials-{{ $section }}" data-type="ajax" class="b-film-materials__thumb">
                            @if ($material->video_url)
                                <video src="{{ $material->video_url }}" preload="metadata" muted></video>
                                <span class="b-film-materials__play"><i class="fas fa-play"></i></span>
                            @elseif (!empty($material->cover_image))
                                <img src="/storage{{ $material->cover_image }}" alt="" loading="lazy">
                            @else
                                <span class="b-film-materials__thumb-text">{{ $material->title ?: 'Без названия' }}</span>
                            @endif
                        </a>
                        <button type="button" class="b-film-materials__detach js-film-materials-detach"
                                data-url="{{ route('posts.film-materials.detach', [$film->id, $material->id]) }}"
                                title="Открепить от фильма (сам пост останется)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @empty
                    <div class="b-film-materials__empty">В этом разделе пока пусто — нажмите «Добавить материалы» и выберите свои посты.</div>
                @endforelse
            </div>
        @endforeach
</div>

<div class="modal fade" id="modal-film-materials" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить материалы к фильму</h5>
            </div>
            <div class="modal-body">
                <div class="b-film-materials__picker-bar">
                    <input type="text" class="form-control js-film-materials-search" placeholder="Поиск по названию или хештегу">
                    {{-- раздел для выбранных постов; посты с видео всегда попадают в "Видео" --}}
                    <div class="b-film-materials__kinds">
                        @foreach (\App\Models\Post::MATERIAL_KIND_LABELS as $kind => $kindLabel)
                            <label class="b-film-materials__kind">
                                <input type="radio" name="film-material-kind" value="{{ $kind }}" @if ($loop->first) checked @endif>
                                <span>{{ $kindLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="b-film-materials__candidates js-film-materials-candidates"></div>
                <div class="b-film-materials__empty js-film-materials-none" style="display: none">Подходящих постов не найдено.</div>
                <button type="button" class="b-post-show__btn b-film-materials__more js-film-materials-more" style="display: none">Показать ещё</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary js-film-materials-submit" disabled>Прикрепить</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

@push('footer-scripts')
    <script src="/admin/film-materials.js?v={{ filemtime(public_path('admin/film-materials.js')) }}"></script>
@endpush
