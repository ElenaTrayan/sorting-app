{{--
    Форма создания/редактирования контакта (modal-create-contact / modal-edit-contact) —
    один и тот же partial на два режима ($mode). Вёрстка — по макету Claude Design
    (артефакт eb49d882): компактная карточка, поля с иконкой в скруглённой рамке,
    круглое превью фото с бейджем-камерой, зона перетаскивания файла.
--}}
@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-contact' : 'modal-create-contact';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true" data-residence-search-action="{{ route('contacts.residence.search') }}">
    <div class="modal-dialog b-contact-form-modal-dialog" role="document">
        <div class="modal-content b-contact-form-modal">
            <div class="modal-header b-contact-form-modal__header">
                <div class="b-contact-form-modal__icon"><i class="fas fa-user"></i></div>
                <div class="b-contact-form-modal__heading">
                    <h5 class="modal-title">{{ $isEdit ? 'Редактировать контакт' : 'Создать контакт' }}</h5>
                    <div class="b-contact-form-modal__subtitle">{{ $isEdit ? 'Обновите информацию о контакте' : 'Заполните информацию о новом контакте' }}</div>
                </div>
                <button type="button" class="close b-contact-form-modal__close" data-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="b-contact-form-field">
                    <label>Имя <span class="text-danger">*</span></label>
                    <div class="b-contact-form-input">
                        <i class="fas fa-user"></i>
                        <input type="text" class="js-contact-form__name" maxlength="255" placeholder="Введите имя" value="{{ $isEdit ? $contact->name : '' }}">
                    </div>
                </div>

                <div class="b-contact-form-field">
                    <label>Фото</label>
                    <div class="b-contact-form__photo-row js-contact-photo-dropzone">
                        <div class="b-contact-form__photo-wrap">
                            <div class="b-contact-form__photo-preview js-contact-form__photo-preview @if ($isEdit && $contact->photo_url) has-photo @endif" @if ($isEdit && $contact->photo_url) style="background-image: url({{ $contact->photo_url }})" @endif>
                                <i class="fas fa-image"></i>
                                <span>фото</span>
                            </div>
                            <span class="b-contact-form__photo-badge"><i class="fas fa-camera"></i></span>
                        </div>
                        <div class="b-contact-form__photo-drop">
                            <label class="b-contact-form__photo-drop-inner">
                                <span class="b-contact-form__photo-drop-icon"><i class="fas fa-file-image"></i></span>
                                <span class="b-contact-form__photo-drop-text">
                                    <span class="b-contact-form__photo-drop-title">Выбрать файл</span>
                                    <span class="b-contact-form__photo-drop-hint">JPG, PNG или WebP, не более 5 МБ</span>
                                    <span class="b-contact-form__photo-drop-sub">или перетащите картинку сюда</span>
                                </span>
                                <input type="file" class="js-contact-form__photo-input" accept="image/*" hidden>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="b-contact-form-row">
                    <div class="b-contact-form-field">
                        <label>Дата рождения</label>
                        {{-- tempusdominus-bootstrap-4 (уже подключён в admin_layout.blade.php,
                             просто нигде не использовался) — свой календарь вместо нативного
                             у браузера, который никак не стилизовать. Инициализация в admin.js,
                             см. .js-contact-form__birth-date-wrap --}}
                        <div class="b-contact-form-input b-contact-form-date js-contact-form__birth-date-wrap" id="{{ $modalId }}-birth-date" data-target-input="nearest">
                            <i class="far fa-calendar-alt" data-toggle="datetimepicker" data-target="#{{ $modalId }}-birth-date"></i>
                            <input type="text" class="js-contact-form__birth-date datetimepicker-input" data-toggle="datetimepicker" data-target="#{{ $modalId }}-birth-date" placeholder="ДД.ММ.ГГГГ" autocomplete="off" value="{{ $isEdit ? optional($contact->birth_date)->format('d.m.Y') : '' }}">
                        </div>
                    </div>
                    <div class="b-contact-form-field">
                        <label>Телефон</label>
                        <div class="b-contact-form-input">
                            <i class="fas fa-phone-alt"></i>
                            <input type="text" class="js-contact-form__phone" maxlength="50" placeholder="Введите номер телефона" value="{{ $isEdit ? $contact->phone : '' }}">
                        </div>
                    </div>
                </div>

                <div class="b-contact-form-field">
                    <label>Место жительства</label>
                    {{-- Подсказки город/страна — сначала своя локальная база (residence_cities/
                         residence_countries, импорт из GeoNames), а если там пусто — подстраховка
                         внешними геосервисами (Photon/Nominatim), через свой прокси-роут
                         contacts.residence.search (см. ContactController, URL лежит в
                         data-residence-search-action на корневом .modal выше). Координаты
                         (lat/lng, центр города) приходят вместе с подсказкой и сохраняются вместе
                         с контактом (residence_lat/residence_lng) — про запас на будущее
                         (сортировка/фильтр по расстоянию, карта и т.п.), в самой форме сейчас
                         никак не показываются. При ручном наборе без выбора из списка координаты
                         не проставляются. --}}
                    <div class="b-contact-residence js-contact-residence">
                        <div class="b-contact-form-input">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" class="js-contact-form__residence" maxlength="255" placeholder="Город, страна" autocomplete="off" value="{{ $isEdit ? $contact->residence : '' }}">
                            <button type="button" class="b-contact-form-input__clear js-contact-form__residence-clear" aria-label="Очистить"><i class="fas fa-times"></i></button>
                        </div>
                        <ul class="b-contact-residence__results js-contact-residence-results" role="listbox"></ul>
                        <input type="hidden" class="js-contact-form__residence-lat" value="{{ $isEdit ? $contact->residence_lat : '' }}">
                        <input type="hidden" class="js-contact-form__residence-lng" value="{{ $isEdit ? $contact->residence_lng : '' }}">
                    </div>
                </div>

                <div class="b-contact-form-field">
                    <label>Хештеги</label>
                    <div class="b-contact-tag-picker js-contact-tag-picker">
                        <div class="b-contact-form-input">
                            <i class="fas fa-tag"></i>
                            <input type="text" class="js-contact-tag-picker__input" placeholder="Начните вводить хештеги...">
                        </div>
                        <ul class="b-contact-tag-picker__results js-contact-tag-picker__results"></ul>
                        <div class="b-contact-tag-picker__selected js-contact-tag-picker__selected">
                            @if ($isEdit)
                                @foreach ($contact->hashtags as $hashtag)
                                    <span class="b-contact-tag-picker__tag" data-id="{{ $hashtag->id }}">#{{ $hashtag->title }}<i class="fas fa-times js-contact-tag-picker__remove"></i></span>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer b-contact-form-modal__footer">
                @if ($isEdit)
                    <button type="button" class="b-contact-form-modal__btn b-contact-form-modal__btn--danger js-contact-delete" data-id="{{ $contact->id }}">
                        <i class="fas fa-trash-alt"></i> Удалить контакт
                    </button>
                @endif
                <div class="b-contact-form-modal__spacer"></div>
                <button type="button" class="b-contact-form-modal__btn b-contact-form-modal__btn--cancel" data-dismiss="modal">Отмена</button>
                <button type="button" class="b-contact-form-modal__btn b-contact-form-modal__btn--save js-contact-form__save" data-mode="{{ $isEdit ? 'edit' : 'create' }}" @if ($isEdit) data-id="{{ $contact->id }}" @endif>
                    <i class="fas fa-save"></i> {{ $isEdit ? 'Сохранить' : 'Создать' }}
                </button>
            </div>
        </div>
    </div>
</div>
