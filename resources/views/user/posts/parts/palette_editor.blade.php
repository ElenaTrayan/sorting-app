{{-- Редактор палитры обложки: убрать цвет, пометить фон, сделать цвет главным, добавить пипеткой с картинки.
     Всё сохраняется сразу (palette-editor.js, PostController::updatePalette/resetPalette); пока обложка та же,
     палитра сама не пересчитывается. Блок "Палитра" на странице редактирования поста и окно палитры на странице
     группы (posts/group.blade.php) — там это заготовка без данных, их подставляет post-group.js.

     @param \App\Models\Post|null $post  null — заготовка без данных поста --}}
<div class="b-palette-editor js-palette-editor"
     @if ($post)
         id="palette"
         data-show-action="{{ route('posts.palette.show', $post->id) }}"
         data-update-action="{{ route('posts.palette.update', $post->id) }}"
         data-reset-action="{{ route('posts.palette.reset', $post->id) }}"
         data-cover-url="{{ $post->coverImagePath() ? '/storage' . $post->coverImagePath() : '' }}"
         data-edited="{{ $post->palette_edited ? '1' : '0' }}"
         data-palette="{{ json_encode($post->palette ?? []) }}"
     @endif>
    <div class="b-palette-editor__head">
        <h2>Палитра</h2>
        <span class="b-palette-editor__badge js-palette-edited" hidden>изменена вручную</span>
    </div>
    <p class="b-palette-editor__hint">
        Первый цвет — главный. Фон — в конце: в поиске по цвету он весит меньше.
        Пока обложка та же, палитра остаётся такой, как вы её поправили.
    </p>
    <p class="b-palette-editor__empty js-palette-empty" hidden>У поста нет картинки — палитры нет.</p>
    <ul class="b-palette-editor__list js-palette-list"></ul>

    <div class="b-palette-editor__actions js-palette-actions">
        <button type="button" class="b-pin-btn js-palette-pick-toggle">
            <i class="fas fa-eye-dropper"></i>Добавить цвет пипеткой
        </button>
        <button type="button" class="b-pin-btn b-post-edit__link-btn js-palette-reset" hidden>
            <i class="fas fa-sync-alt"></i>Пересчитать автоматически
        </button>
    </div>

    <div class="b-palette-editor__picker js-palette-picker" hidden>
        <p class="b-palette-editor__hint mb-2">Нажмите на картинку там, где нужный цвет.</p>
        <div class="b-palette-editor__canvas-wrap">
            <canvas class="js-palette-canvas"></canvas>
            <span class="b-palette-editor__loupe js-palette-loupe" hidden></span>
        </div>
    </div>
</div>
