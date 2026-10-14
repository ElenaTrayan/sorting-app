{{--
    Окно "Выбрать из постов": картинки постов пользователя (wardrobe.post-images), поиск по названию
    поста или хештегу, подгрузка "Показать ещё". Что делать с выбранной картинкой, решает тот, кто
    открыл окно (wardrobe.js, openPostPicker): форма вещи подставляет её себе, конструктор образа
    создаёт вещь "нужно купить".
    Режим пинов ("Импорт из пина" в форме новой вещи): только пины типа "Одежда", плитка — пин целиком;
    выбранный пин заполняет форму картинками и полями (data-import-url, wardrobe.js).
--}}
<div class="b-wardrobe-picker js-wardrobe-post-picker" data-url="{{ route('wardrobe.post-images') }}" data-import-url="{{ route('wardrobe.post-import') }}" style="display:none;">
    <div class="b-wardrobe-picker__backdrop js-wardrobe-post-picker-close"></div>
    <div class="b-wardrobe-picker__window">
        <div class="b-wardrobe-picker__header">
            <div class="b-wardrobe-picker__title js-wardrobe-post-picker-title">Картинки из постов</div>
            <button type="button" class="b-wardrobe-picker__close js-wardrobe-post-picker-close" aria-label="Закрыть"><i class="fas fa-times"></i></button>
        </div>
        <div class="b-stock-toolbar__search b-wardrobe-picker__search">
            <i class="fas fa-search"></i>
            <input type="text" class="js-wardrobe-post-picker-search" placeholder="Название поста или хештег, напр. «одежда»">
        </div>
        <div class="b-wardrobe-picker__body">
            <div class="b-wardrobe-picker__grid js-wardrobe-post-picker-grid"></div>
            <div class="b-wardrobe-picker__status js-wardrobe-post-picker-status"></div>
            <button type="button" class="b-stock-btn b-stock-btn--outline js-wardrobe-post-picker-more" style="display:none;">Показать ещё</button>
        </div>
    </div>
</div>
