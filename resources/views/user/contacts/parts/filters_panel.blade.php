{{--
    Панель "Фильтры" (b-contacts-filters) — выезжает справа по кнопке "Фильтры" в тулбаре
    "Базы контактов" (см. index.blade.php). Разметка повторяет присланный макет почти
    целиком, но по-настоящему фильтрует список только раздел "Хештеги" (переиспользует
    initHashtagPicker() из admin.js — тот же виджет, что и в форме создания/редактирования
    контакта). Остальные поля (Статус, Тип связи, Социальные сети, Личные данные, География,
    "Есть/нет данных", "Дополнительные фильтры") — визуальные заглушки под дизайн: под ними
    нет соответствующих полей в contacts/contact_social_links, поэтому они ничего не
    отфильтровывают. Кнопка "Применить" применяет только хэштеги; "Сбросить все" по-честному
    очищает хэштеги и визуально возвращает остальные поля к значениям по умолчанию.
--}}
<div class="b-contacts-filters-overlay js-contacts-filters-overlay"></div>
<aside class="b-contacts-filters js-contacts-filters" aria-hidden="true">
    <div class="b-contacts-filters__header">
        <div class="b-contacts-filters__header-top">
            <h5 class="b-contacts-filters__title">Фильтры</h5>
            <button type="button" class="b-contacts-filters__close js-contacts-filters-close" aria-label="Закрыть"><i class="fas fa-times"></i></button>
        </div>
        <button type="button" class="b-contacts-filters__reset-all js-contacts-filters-reset-all">Сбросить все</button>
    </div>

    <div class="b-contacts-filters__body">

        <div class="b-contacts-filters__section">
            <div class="b-contacts-filters__label">Поиск</div>
            <label class="b-contacts-filters__search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Имя, никнейм...">
            </label>
        </div>

        <div class="b-contacts-filters__section">
            <div class="b-contacts-filters__label">Хештеги</div>
            <div class="b-contact-tag-picker js-contacts-filters-tag-picker">
                <input type="text" class="form-control js-contact-tag-picker__input" placeholder="Выберите хештеги...">
                <ul class="b-contact-tag-picker__results js-contact-tag-picker__results"></ul>
                <div class="b-contact-tag-picker__selected js-contact-tag-picker__selected"></div>
            </div>
            <div class="b-contacts-filters__radio-group">
                <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-hashtags-mode" value="all" checked> Все выбранные</label>
                <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-hashtags-mode" value="any"> Любой из выбранных</label>
            </div>

            {{-- "исключить хэштег из поиска" — как в общем поиске по хэштегам
                 (layouts/parts/search_widget.blade.php), только своя, независимая пара
                 include/exclude-пикеров (initHashtagPicker переиспользуется дважды) --}}
            <label class="b-contacts-filters__exclude-toggle">
                <input type="checkbox" class="js-contacts-filters-exclude-toggle"> исключить хэштег из поиска
            </label>
            <div class="b-contacts-filters__exclude js-contacts-filters-exclude" style="display: none;">
                <div class="b-contact-tag-picker js-contacts-filters-exclude-tag-picker">
                    <input type="text" class="form-control js-contact-tag-picker__input" placeholder="Исключить хэштег...">
                    <ul class="b-contact-tag-picker__results js-contact-tag-picker__results"></ul>
                    <div class="b-contact-tag-picker__selected js-contact-tag-picker__selected"></div>
                </div>
            </div>
        </div>

        <div class="b-contacts-filters__section">
            <div class="b-contacts-filters__label">Статус</div>
            <div class="b-contacts-filters__radio-group">
                <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-status" checked> Все</label>
                <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-status"> Активные</label>
                <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-status"> Неактивные</label>
            </div>
        </div>

        <div class="b-contacts-filters__section">
            <div class="b-contacts-filters__label">Тип связи</div>
            <div class="b-contacts-filters__checkbox-grid">
                <label class="b-contacts-filters__checkbox"><input type="checkbox"> Публичная персона</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"> Родственник</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"> Знакомый</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"> Коллега</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"> Друг</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"> Другое</label>
            </div>
        </div>

        <div class="b-contacts-filters__section">
            <div class="b-contacts-filters__label">Социальные сети</div>
            <div class="b-contacts-filters__checkbox-grid b-contacts-filters__checkbox-grid--social">
                <label class="b-contacts-filters__checkbox"><input type="checkbox"><i class="fab fa-instagram" style="color:#E1306C"></i> Instagram</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"><i class="fab fa-tiktok"></i> TikTok</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"><i class="fab fa-telegram-plane" style="color:#229ED9"></i> Telegram</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"><i class="fab fa-youtube" style="color:#FF0000"></i> YouTube</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"><i class="fab fa-facebook-f" style="color:#1877F2"></i> Facebook</label>
                <label class="b-contacts-filters__checkbox"><input type="checkbox"><i class="fab fa-twitter" style="color:#1DA1F2"></i> X (Twitter)</label>
            </div>
        </div>

        <div class="b-contacts-filters__collapse js-contacts-filters-collapse">
            <button type="button" class="b-contacts-filters__collapse-toggle js-contacts-filters-collapse-toggle">Личные данные <i class="fas fa-chevron-down"></i></button>
            <div class="b-contacts-filters__collapse-body" style="display: none;">
                <div class="b-contacts-filters__label">Возраст</div>
                <div class="b-contacts-filters__range">
                    <input type="number" min="0" placeholder="От">
                    <span>—</span>
                    <input type="number" min="0" placeholder="До">
                </div>
                <div class="b-contacts-filters__label" style="margin-top: 14px;">Пол</div>
                <div class="b-contacts-filters__checkbox-grid">
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Мужской</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Женский</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Не указан</label>
                </div>
            </div>
        </div>

        <div class="b-contacts-filters__collapse js-contacts-filters-collapse">
            <button type="button" class="b-contacts-filters__collapse-toggle js-contacts-filters-collapse-toggle">География <i class="fas fa-chevron-down"></i></button>
            <div class="b-contacts-filters__collapse-body" style="display: none;">
                <div class="b-contacts-filters__label">Место жительства</div>
                <input type="text" class="b-contacts-filters__text-input" placeholder="Город, страна...">
            </div>
        </div>

        <div class="b-contacts-filters__collapse js-contacts-filters-collapse">
            <button type="button" class="b-contacts-filters__collapse-toggle js-contacts-filters-collapse-toggle">Есть данные <i class="fas fa-chevron-down"></i></button>
            <div class="b-contacts-filters__collapse-body" style="display: none;">
                <div class="b-contacts-filters__checkbox-grid">
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Телефон</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Дата рождения</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Фото</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Соцсети</label>
                </div>
            </div>
        </div>

        <div class="b-contacts-filters__collapse js-contacts-filters-collapse">
            <button type="button" class="b-contacts-filters__collapse-toggle js-contacts-filters-collapse-toggle">Нет данных <i class="fas fa-chevron-down"></i></button>
            <div class="b-contacts-filters__collapse-body" style="display: none;">
                <div class="b-contacts-filters__checkbox-grid">
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Телефон</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Дата рождения</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Фото</label>
                    <label class="b-contacts-filters__checkbox"><input type="checkbox"> Соцсети</label>
                </div>
            </div>
        </div>

        <div class="b-contacts-filters__collapse js-contacts-filters-collapse">
            <button type="button" class="b-contacts-filters__collapse-toggle js-contacts-filters-collapse-toggle">Дополнительные фильтры <i class="fas fa-chevron-down"></i></button>
            <div class="b-contacts-filters__collapse-body" style="display: none;">
                <div class="b-contacts-filters__label">Добавлен</div>
                <div class="b-contacts-filters__radio-group">
                    <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-added" checked> За всё время</label>
                    <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-added"> За 30 дней</label>
                    <label class="b-contacts-filters__radio"><input type="radio" name="contacts-filters-added"> За 7 дней</label>
                </div>
            </div>
        </div>

    </div>

    <div class="b-contacts-filters__footer">
        <button type="button" class="b-contacts-filters__cancel js-contacts-filters-cancel">Отменить</button>
        <button type="button" class="b-contacts-filters__apply js-contacts-filters-apply">Применить <span class="js-contacts-filters-count"></span></button>
    </div>
</aside>
