@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'modal-edit-contact' : 'modal-create-contact';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $isEdit ? 'Редактировать контакт' : 'Создать контакт' }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" class="form-control js-contact-form__name" maxlength="255" value="{{ $isEdit ? $contact->name : '' }}">
                </div>

                <div class="form-group">
                    <label>Фото</label>
                    <div class="b-contact-form__photo-row js-contact-photo-dropzone">
                        @if ($isEdit && $contact->photo_url)
                            <div class="b-contact-form__photo-preview js-contact-form__photo-preview" style="background-image: url({{ $contact->photo_url }})"></div>
                        @else
                            <div class="b-contact-form__photo-preview js-contact-form__photo-preview"></div>
                        @endif
                        <div>
                            <label class="btn btn-outline-info mb-0">
                                Выбрать файл
                                <input type="file" class="js-contact-form__photo-input" accept="image/*" hidden>
                            </label>
                            <div class="text-muted small mt-1">или перетащите картинку сюда</div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6">
                        <label>Дата рождения</label>
                        <input type="date" class="form-control js-contact-form__birth-date" value="{{ $isEdit ? optional($contact->birth_date)->format('Y-m-d') : '' }}">
                    </div>
                    <div class="form-group col-6">
                        <label>Телефон</label>
                        <input type="text" class="form-control js-contact-form__phone" maxlength="50" value="{{ $isEdit ? $contact->phone : '' }}">
                    </div>
                </div>

                <div class="form-group">
                    <label>Место жительства</label>
                    <input type="text" class="form-control js-contact-form__residence" maxlength="255" value="{{ $isEdit ? $contact->residence : '' }}">
                </div>

                <div class="form-group">
                    <label>Хештеги</label>
                    <div class="b-contact-tag-picker js-contact-tag-picker">
                        <input type="text" class="form-control js-contact-tag-picker__input" placeholder="Начните вводить...">
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
            <div class="modal-footer">
                @if ($isEdit)
                    <button type="button" class="btn btn-outline-danger mr-auto js-contact-delete" data-id="{{ $contact->id }}">
                        <i class="fas fa-trash mr-1"></i> Удалить контакт
                    </button>
                    <button type="button" class="btn btn-primary js-contact-form__save" data-mode="edit" data-id="{{ $contact->id }}">Сохранить</button>
                @else
                    <button type="button" class="btn btn-primary js-contact-form__save" data-mode="create">Создать</button>
                @endif
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
