{{-- Поля вещи для пина "Одежда" — общие для создания (блок #tab-clothes) и редактирования поста.
     Сохраняются в post_clothes (App\Models\PostClothes::attributesFromRequest — имена инпутов должны совпадать).
     Бренд, стили и "кому подходит" — справочники "Моего гардероба": кнопка "В гардероб" переносит их в форму вещи.
     На странице поста эти данные показывает parts/clothes_card.blade.php. Стили — public/admin/pin-create.css.

     @param \App\Models\PostClothes|null $clothes — сохранённые данные (на странице редактирования) --}}
@php
    use App\Models\PostClothes;
    use App\Models\WardrobeItem;

    /** @var PostClothes|null $clothes */
    $clothes = $clothes ?? null;
    $clothesStyles = $clothes->styles ?? [];
    $clothesBodyTypes = $clothes->body_types ?? [];
@endphp
{{-- hidden: поля вещи были на форме — PostController::update по нему понимает, что их надо сохранить
     (а все пустые — значит, данные вещи удалить) --}}
<input type="hidden" name="clothes-fields" value="1">
<div class="b-pin-fields js-clothes-fields">
    <div class="b-pin-field">
        <label for="clothes-kind">Тип вещи</label>
        <input type="text" class="b-pin-input" name="clothes-kind" id="clothes-kind" maxlength="50" list="clothes-kind-suggestions"
               placeholder="Свитшот, плащ, кеды…" value="{{ $clothes->kind ?? '' }}" autocomplete="off">
        <datalist id="clothes-kind-suggestions">
            {{-- типы вещей по категориям гардероба — ведёт админ (админка → Категории одежды) --}}
            @foreach (\App\Models\WardrobeCategory::kindsBySlug() as $category => $kinds)
                @foreach ($kinds as $kind)
                    <option value="{{ $kind }}">{{ \App\Models\WardrobeCategory::label($category) }}</option>
                @endforeach
            @endforeach
        </datalist>
    </div>
    <div class="b-pin-field">
        <label>Бренд</label>
        {{-- справочник брендов с логотипами — тот же список, что в форме вещи "Моего гардероба" --}}
        @include('user.wardrobe.parts.brand_select', [
            'idName' => 'clothes-brand-id',
            'newName' => 'clothes-brand-new',
            'selectedId' => $clothes->brand_id ?? null,
        ])
    </div>
    <div class="b-pin-field">
        <label for="clothes-year">Год коллекции</label>
        <input type="text" class="b-pin-input" name="clothes-year" id="clothes-year" inputmode="numeric" maxlength="4"
               placeholder="{{ date('Y') }}" value="{{ $clothes->collection_year ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label>Цвет</label>
        {{-- справочник цветов — тот же список, что в форме вещи "Моего гардероба" --}}
        @include('user.wardrobe.parts.color_select', [
            'idName' => 'clothes-color-id',
            'newName' => 'clothes-color-new',
            'newColorName' => 'clothes-color-hex',
            'selectedId' => $clothes->color_id ?? null,
        ])
    </div>
    <div class="b-pin-field">
        <label>Принт</label>
        {{-- справочник принтов — тот же список, что в форме вещи "Моего гардероба" --}}
        @include('user.wardrobe.parts.print_select', [
            'idName' => 'clothes-print-id',
            'newName' => 'clothes-print-new',
            'selectedId' => $clothes->print_id ?? null,
        ])
    </div>
    <div class="b-pin-field">
        <label>Материал</label>
        {{-- справочник материалов — тот же список, что в форме вещи "Моего гардероба" --}}
        @include('user.wardrobe.parts.material_select', [
            'idName' => 'clothes-material-id',
            'newName' => 'clothes-material-new',
            'selectedId' => $clothes->material_id ?? null,
        ])
    </div>
    <div class="b-pin-field">
        <label for="clothes-composition">Состав</label>
        <input type="text" class="b-pin-input" name="clothes-composition" id="clothes-composition" maxlength="255"
               placeholder="Например, хлопок 95%, эластан 5%" value="{{ $clothes->composition ?? '' }}">
    </div>
    <div class="b-pin-field">
        <label for="clothes-length">Длина</label>
        <input type="text" class="b-pin-input" name="clothes-length" id="clothes-length" maxlength="50" list="clothes-length-suggestions"
               placeholder="Мини, миди, макси…" value="{{ $clothes->length ?? '' }}" autocomplete="off">
        <datalist id="clothes-length-suggestions">
            @foreach (WardrobeItem::LENGTHS as $length)
                <option value="{{ $length }}"></option>
            @endforeach
        </datalist>
    </div>
    <div class="b-pin-field">
        <label for="clothes-temp-min">Комфортная температура, °C</label>
        <div class="b-pin-clothes-temp">
            <input type="number" class="b-pin-input" name="clothes-temp-min" id="clothes-temp-min" min="-60" max="60" placeholder="от" value="{{ $clothes->temp_min ?? '' }}">
            <input type="number" class="b-pin-input" name="clothes-temp-max" min="-60" max="60" placeholder="до" value="{{ $clothes->temp_max ?? '' }}" aria-label="Комфортная температура до">
        </div>
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label>Сезон</label>
        <div class="b-pin-chips">
            @foreach (\App\Models\WardrobeOutfit::SEASONS as $key => $label)
                <label class="b-pin-chip b-pin-chip--check">
                    <input type="checkbox" name="clothes-seasons[]" value="{{ $key }}" {{ in_array($key, $clothes->seasons ?? [], true) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label>Повод</label>
        <div class="b-pin-chips">
            @foreach (WardrobeItem::OCCASIONS as $key => $label)
                <label class="b-pin-chip b-pin-chip--check">
                    <input type="checkbox" name="clothes-occasions[]" value="{{ $key }}" {{ in_array($key, $clothes->occasions ?? [], true) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label>Стиль</label>
        <div class="b-pin-chips">
            @foreach (WardrobeItem::STYLES as $key => $label)
                <label class="b-pin-chip b-pin-chip--check">
                    <input type="checkbox" name="clothes-styles[]" value="{{ $key }}" {{ in_array($key, $clothesStyles, true) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label>Кому подходит</label>
        <div class="b-pin-chips">
            @foreach (WardrobeItem::BODY_TYPES as $key => $label)
                <label class="b-pin-chip b-pin-chip--check">
                    <input type="checkbox" name="clothes-body-types[]" value="{{ $key }}" {{ in_array($key, $clothesBodyTypes, true) ? 'checked' : '' }}>
                    {{ $label }}
                </label>
            @endforeach
        </div>
    </div>
    <div class="b-pin-field b-pin-field--wide">
        <label for="clothes-shop-url">Ссылка на магазин</label>
        <input type="text" class="b-pin-input" name="clothes-shop-url" id="clothes-shop-url" maxlength="1000"
               placeholder="https://…" value="{{ $clothes->shop_url ?? '' }}">
    </div>
</div>
