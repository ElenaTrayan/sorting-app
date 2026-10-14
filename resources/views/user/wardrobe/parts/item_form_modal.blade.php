{{--
    Форма добавления/редактирования вещи — одна на страницу: при открытии wardrobe.js подставляет
    в неё данные вещи (data-wardrobe-item на карточке), адрес и метод. Отправляет общий обработчик
    data-stock-ajax-form из my-stock.js (FormData — вместе с файлом).
    Картинка: свой файл (image) или картинка поста (post_id + post_image_index, окно parts/post_picker).
--}}
@php
    use App\Models\WardrobeItem;
    $canPickFromPosts = auth()->user()->hasService('posts');
@endphp
{{-- data-from-post — пришли кнопкой "В гардероб" с пина "Одежда": открыть форму новой вещи с картинкой поста;
     data-edit-item — ссылкой "В гардеробе": открыть форму уже добавленной вещи (wardrobe.js) --}}
<div class="modal fade" id="modal-wardrobe-item" tabindex="-1" role="dialog" aria-hidden="true" data-store-url="{{ route('wardrobe.items.store') }}"
     @if (!empty($fromPost)) data-from-post="{{ json_encode($fromPost) }}" @endif
     @if (!empty($editItemId)) data-edit-item="{{ $editItemId }}" @endif>
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content b-stock-form-modal">
            <div class="modal-header b-stock-form-modal__header">
                <div class="b-stock-form-modal__icon"><i class="fas fa-tshirt"></i></div>
                <div class="b-stock-form-modal__heading">
                    <h5 class="modal-title js-wardrobe-item-title">Добавить вещь</h5>
                    <div class="b-stock-form-modal__subtitle">Фото и всё, что стоит помнить о вещи</div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <form data-stock-ajax-form data-method="POST" action="{{ route('wardrobe.items.store') }}" class="js-wardrobe-item-form">
                {{-- галерея фото (wardrobe.js, initItemGallery): photos_order — итоговый порядок ("id:12", "new:0",
                     "post:5:1", см. ItemController::photosPlan), photos_new[] — новые файлы (их туда кладёт JS) --}}
                <input type="hidden" name="photos_order" class="js-wardrobe-photos-order" value="[]">
                {{-- повороты/отражения фото, по тем же позициям, что и photos_order: [{"r": 1, "f": 0}, ...] —
                     применяются при сохранении вещи (ItemController::applyPhotos, WardrobeImage::transform) --}}
                <input type="hidden" name="photos_transform" class="js-wardrobe-photos-transform" value="[]">
                <input type="file" name="photos_new[]" class="js-wardrobe-photos-new" multiple hidden>

                <div class="modal-body">
                    <div class="b-wardrobe-form">
                        <div class="b-wardrobe-form__photo">
                            <div class="b-wardrobe-gallery js-wardrobe-gallery">
                                <div class="b-wardrobe-gallery__empty js-wardrobe-gallery-empty">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <div class="b-wardrobe-gallery__list js-wardrobe-gallery-list"></div>
                            </div>
                            @if ($canPickFromPosts)
                                {{-- только у новой вещи (wardrobe.js, openItemForm): пин "Одежда" заполняет форму целиком —
                                     все картинки, название, бренд, цвет и остальные поля пина --}}
                                <button type="button" class="b-stock-btn b-stock-btn--primary b-stock-btn--sm b-wardrobe-form__photo-btn js-wardrobe-import-from-pin"><i class="fas fa-file-import"></i> Импорт из пина</button>
                            @endif
                            <label class="b-stock-btn b-stock-btn--outline b-stock-btn--sm b-wardrobe-form__photo-btn">
                                <i class="fas fa-upload"></i> Загрузить фото
                                <input type="file" class="js-wardrobe-photos-pick" accept="image/jpeg,image/png,image/gif,image/webp,image/avif" multiple hidden>
                            </label>
                            @if ($canPickFromPosts)
                                <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm b-wardrobe-form__photo-btn js-wardrobe-pick-from-posts"><i class="far fa-images"></i> Выбрать из постов</button>
                            @endif
                            <p class="b-wardrobe-gallery__hint js-wardrobe-gallery-hint" hidden>
                                Первое фото — обложка. Перетащите, чтобы поменять порядок.
                            </p>
                        </div>

                        <div class="b-wardrobe-form__fields">
                            <div class="b-stock-form-field">
                                <label>Название <span class="text-danger">*</span></label>
                                <div class="b-stock-form-input">
                                    <i class="fas fa-tag"></i>
                                    <input type="text" name="name" required maxlength="255" placeholder="Напр. Бежевый тренч">
                                </div>
                            </div>

                            <div class="b-stock-form-row">
                                <div class="b-stock-form-field">
                                    <label>Категория <span class="text-danger">*</span></label>
                                    <div class="b-stock-form-input">
                                        <i class="fas fa-th-large"></i>
                                        <select name="category" required>
                                            @foreach (\App\Models\WardrobeCategory::options() as $slug => $label)
                                                <option value="{{ $slug }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="b-stock-form-field">
                                    <label>Статус</label>
                                    <div class="b-wardrobe-choice">
                                        @foreach (WardrobeItem::STATUSES as $slug => $label)
                                            <label class="b-wardrobe-choice__item">
                                                <input type="radio" name="status" value="{{ $slug }}" @if ($slug === WardrobeItem::STATUS_OWNED) checked @endif>
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="b-stock-form-row">
                                <div class="b-stock-form-field">
                                    <label>Цвет</label>
                                    {{-- справочник цветов с образцами — такой же список, как у принтов и материалов --}}
                                    @include('user.wardrobe.parts.color_select', ['idName' => 'color_id', 'newName' => 'color_new', 'newColorName' => 'color_hex'])
                                </div>
                                <div class="b-stock-form-field">
                                    <label>Размер</label>
                                    {{-- размерные сетки и "Свой размер…"; значение ставит wardrobe.js --}}
                                    @include('user.wardrobe.parts.size_select', ['name' => 'size', 'newName' => 'size_new'])
                                </div>
                            </div>

                            <div class="b-stock-form-row">
                                <div class="b-stock-form-field">
                                    <label>Принт</label>
                                    {{-- справочник принтов с образцами; значение подставляет wardrobe.js (printSelect.set) --}}
                                    @include('user.wardrobe.parts.print_select', ['idName' => 'print_id', 'newName' => 'print_new'])
                                </div>
                                <div class="b-stock-form-field">
                                    <label>Материал</label>
                                    {{-- справочник материалов — такой же список, как у принтов --}}
                                    @include('user.wardrobe.parts.material_select', ['idName' => 'material_id', 'newName' => 'material_new'])
                                </div>
                            </div>

                            <div class="b-stock-form-row">
                                <div class="b-stock-form-field">
                                    <label>Состав</label>
                                    <div class="b-stock-form-input">
                                        <i class="fas fa-percent"></i>
                                        <input type="text" name="composition" maxlength="255" placeholder="Напр. хлопок 95%, эластан 5%" autocomplete="off">
                                    </div>
                                </div>
                                <div class="b-stock-form-field">
                                    <label>Длина</label>
                                    <div class="b-stock-form-input">
                                        <i class="fas fa-ruler-vertical"></i>
                                        {{-- подсказки WardrobeItem::LENGTHS, своё значение можно вписать --}}
                                        <input type="text" name="length" maxlength="50" list="wardrobe-length-suggestions" placeholder="Напр. миди" autocomplete="off">
                                        <datalist id="wardrobe-length-suggestions">
                                            @foreach (WardrobeItem::LENGTHS as $length)
                                                <option value="{{ $length }}"></option>
                                            @endforeach
                                        </datalist>
                                    </div>
                                </div>
                            </div>

                            <div class="b-stock-form-row">
                                <div class="b-stock-form-field">
                                    <label>Бренд</label>
                                    {{-- общие бренды и свои, с логотипами — такой же список, как у принтов и материалов --}}
                                    @include('user.wardrobe.parts.brand_select', ['idName' => 'brand_id', 'newName' => 'brand'])
                                </div>
                                <div class="b-stock-form-field">
                                    <label>Степень изношенности</label>
                                    <div class="b-stock-form-input">
                                        <i class="fas fa-heartbeat"></i>
                                        <select name="wear_level">
                                            <option value="">—</option>
                                            @foreach (WardrobeItem::WEAR_LEVELS as $slug => $label)
                                                <option value="{{ $slug }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Место хранения</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-box-open"></i>
                            {{-- свой список пользователя (вкладка "Места хранения"); "Новое место…" открывает поле для названия (wardrobe.js) --}}
                            <select name="storage_id" class="js-wardrobe-storage-select">
                                <option value="">Не указано</option>
                                @foreach ($storages as $storage)
                                    <option value="{{ $storage->id }}">{{ $storage->name }}</option>
                                @endforeach
                                <option value="new">+ Новое место…</option>
                            </select>
                        </div>
                        <div class="b-stock-form-input js-wardrobe-storage-new" style="display:none;margin-top:8px;">
                            <i class="fas fa-pen"></i>
                            <input type="text" name="storage_name" maxlength="255" placeholder="Напр. Органайзер ИКЕА BAXNA 204.743.66" autocomplete="off">
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Особенности ухода</label>
                        {{-- пункты для отметки (WardrobeItem::CARE_OPTIONS) по разделам; раздел раскрывается кликом,
                             счётчик отмеченного и раскрытие разделов с отметками — wardrobe.js (refreshCare) --}}
                        <div class="b-wardrobe-care js-wardrobe-care">
                            @foreach (WardrobeItem::CARE_OPTIONS as $groupLabel => $options)
                                <details class="b-wardrobe-care__group js-wardrobe-care-group">
                                    <summary class="b-wardrobe-care__title">
                                        <i class="fas fa-chevron-right b-wardrobe-care__chevron"></i>
                                        <span>{{ $groupLabel }}</span>
                                        <span class="b-wardrobe-care__count js-wardrobe-care-count" hidden></span>
                                    </summary>
                                    <div class="b-wardrobe-choice b-wardrobe-choice--wrap">
                                        @foreach ($options as $slug => $label)
                                            <label class="b-wardrobe-choice__item">
                                                <input type="checkbox" name="care_options[]" value="{{ $slug }}">
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                        <div class="b-stock-form-input" style="margin-top:8px;">
                            <i class="fas fa-pen"></i>
                            <input type="text" name="care" maxlength="2000" placeholder="Другое по уходу — если нужного пункта нет" autocomplete="off">
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <input type="hidden" name="needs_repair" value="0">
                        <label class="b-wardrobe-check">
                            <input type="checkbox" name="needs_repair" value="1" class="js-wardrobe-needs-repair">
                            <span>Нужен ремонт</span>
                        </label>
                        <div class="b-stock-form-input js-wardrobe-repair-note" style="display:none;margin-top:8px;">
                            <i class="fas fa-tools"></i>
                            <textarea name="repair_note" rows="2" maxlength="2000" placeholder="Какой именно: напр. заменить молнию, подшить низ"></textarea>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Стиль</label>
                        <div class="b-wardrobe-choice b-wardrobe-choice--wrap">
                            @foreach (WardrobeItem::STYLES as $slug => $label)
                                <label class="b-wardrobe-choice__item">
                                    <input type="checkbox" name="styles[]" value="{{ $slug }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Кому подходит</label>
                        <div class="b-wardrobe-choice b-wardrobe-choice--wrap">
                            @foreach (WardrobeItem::BODY_TYPES as $slug => $label)
                                <label class="b-wardrobe-choice__item">
                                    <input type="checkbox" name="body_types[]" value="{{ $slug }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- когда носить: сезон и температура — как у образов (outfits/form.blade.php), повод — WardrobeItem::OCCASIONS --}}
                    <div class="b-stock-form-field">
                        <label>Сезон</label>
                        <div class="b-wardrobe-choice b-wardrobe-choice--wrap">
                            @foreach (\App\Models\WardrobeOutfit::SEASONS as $slug => $label)
                                <label class="b-wardrobe-choice__item">
                                    <input type="checkbox" name="seasons[]" value="{{ $slug }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>При какой температуре комфортно носить, °C</label>
                        <div class="b-stock-form-row">
                            <div class="b-stock-form-input">
                                <i class="fas fa-temperature-low"></i>
                                <input type="number" name="temp_min" min="-60" max="60" placeholder="от">
                            </div>
                            <div class="b-stock-form-input">
                                <i class="fas fa-temperature-high"></i>
                                <input type="number" name="temp_max" min="-60" max="60" placeholder="до">
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Повод</label>
                        <div class="b-wardrobe-choice b-wardrobe-choice--wrap">
                            @foreach (WardrobeItem::OCCASIONS as $slug => $label)
                                <label class="b-wardrobe-choice__item">
                                    <input type="checkbox" name="occasions[]" value="{{ $slug }}">
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- с чем сочетается: вещи из своего гардероба, по разделам "обувь / аксессуары / остальное" (раздел —
                         по категории вещи, WardrobeItem::compatGroup). На сервер уходит один список id — compatible_ids
                         (JSON); связь двусторонняя (WardrobeItem::syncCompatible). Выбор и плитки — wardrobe.js, initItemCompat --}}
                    @php
                        $compatOptions = WardrobeItem::where('user_id', auth()->id())->orderByDesc('id')->get(['id', 'name', 'category', 'image'])
                            ->map(function (WardrobeItem $option) {
                                return [
                                    'id' => $option->id,
                                    'name' => $option->name,
                                    'group' => WardrobeItem::compatGroup($option->category),
                                    'image_url' => $option->image_url,
                                ];
                            })->values();
                    @endphp
                    <input type="hidden" name="compatible_ids" class="js-wardrobe-compat-input" value="[]">
                    <div class="js-wardrobe-compat" data-items="{{ $compatOptions->toJson() }}">
                        @foreach (WardrobeItem::COMPAT_GROUPS as $group => $label)
                            <div class="b-stock-form-field js-wardrobe-compat-group" data-group="{{ $group }}">
                                <label>{{ $label }}</label>
                                <div class="b-wardrobe-selected b-wardrobe-compat__selected js-wardrobe-compat-list"></div>
                                <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm js-wardrobe-compat-add">
                                    <i class="fas fa-plus"></i> Выбрать из гардероба
                                </button>
                                <div class="b-wardrobe-compat__picker js-wardrobe-compat-picker" hidden>
                                    <div class="b-stock-form-input">
                                        <i class="fas fa-search"></i>
                                        <input type="text" class="js-wardrobe-compat-search" placeholder="Поиск по названию" autocomplete="off">
                                    </div>
                                    <div class="b-wardrobe-compat__options js-wardrobe-compat-options"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="b-stock-form-row">
                        <div class="b-stock-form-field">
                            <label>Цена</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-hryvnia-sign"></i>
                                <input type="number" name="price" step="0.01" min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="b-stock-form-field">
                            <label>Ссылка на магазин</label>
                            <div class="b-stock-form-input">
                                <i class="fas fa-link"></i>
                                <input type="text" name="shop_url" maxlength="1000" placeholder="https://">
                            </div>
                        </div>
                    </div>

                    <div class="b-stock-form-field">
                        <label>Заметка</label>
                        <div class="b-stock-form-input">
                            <i class="fas fa-sticky-note"></i>
                            <textarea name="note" rows="2" maxlength="2000" placeholder="Необязательно"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer b-stock-form-modal__footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary js-wardrobe-item-submit">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    {{-- внутри .modal — иначе Bootstrap отбирает фокус у поля поиска (enforceFocus) --}}
    @if ($canPickFromPosts)
        @include('user.wardrobe.parts.post_picker')
    @endif
</div>
