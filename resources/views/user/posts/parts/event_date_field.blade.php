{{-- Поле "Дата" пина (posts.event_date) — на создании, редактировании и странице группы. Дату вводят как удобно:
     "12.03.2024", "03.2024", "март 2024", "2024" — точность (день / месяц / год) сервер определяет сам
     (Post::parseEventDate()), рядом показывается, как он её понял (admin.js, event-date). При создании дата
     подставляется сама — из фото (EXIF или дата файла) или из ссылки; откуда — пишет .js-event-date-source

     @param string $value  дата для поля (Post::eventDateInput()), '' — пусто --}}
<div class="b-pin-field b-pin-field--wide b-event-date js-event-date-field">
    <label for="event_date">Дата <span class="b-pin-card__badge">необязательно</span></label>
    <div class="b-event-date__row">
        <input type="text" class="b-pin-input b-event-date__input js-event-date" name="event_date" id="event_date" value="{{ $value }}"
               placeholder="12.03.2024, 03.2024 или 2024" autocomplete="off" maxlength="40">
        <span class="b-event-date__label js-event-date-label"></span>
    </div>
    <span class="b-event-date__source js-event-date-source" hidden></span>
    <span class="b-pin-card__desc">Когда это было: день новости, день съёмки фото. Если известен только месяц или год — введите их.</span>
</div>
