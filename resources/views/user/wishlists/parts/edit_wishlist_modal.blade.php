<div class="modal fade" id="modal-edit-wishlist" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Настройки вишлиста</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" id="wishlist-edit-title" class="form-control" value="{{ $wishlist->title }}" maxlength="255">
                </div>
                <div class="form-group">
                    <label>Описание (необязательно)</label>
                    <textarea id="wishlist-edit-description" class="form-control" rows="2" maxlength="2000">{{ $wishlist->description }}</textarea>
                </div>

                <label>Повод</label>
                <div class="b-occasion-picker js-occasion-picker">
                    @foreach ($occasions as $occasion)
                        <button type="button" class="b-occasion-picker__item js-occasion-picker__item{{ $wishlist->occasion === $occasion['slug'] ? ' b-occasion-picker__item--active' : '' }}" data-slug="{{ $occasion['slug'] }}">
                            <i class="fas {{ $occasion['icon'] }} mr-1"></i>{{ $occasion['title'] }}
                        </button>
                    @endforeach
                    <button type="button" class="b-occasion-picker__item js-occasion-picker__more">
                        <i class="fas fa-ellipsis-h mr-1"></i>Больше
                    </button>
                </div>
                <input type="text" id="wishlist-edit-occasion-custom" class="form-control mt-2"
                       placeholder="Свой повод"
                       value="{{ $wishlist->occasion && !collect($occasions)->firstWhere('slug', $wishlist->occasion) ? $wishlist->occasion : '' }}"
                       style="{{ $wishlist->occasion && !collect($occasions)->firstWhere('slug', $wishlist->occasion) ? '' : 'display: none;' }}">

                <hr>
                <button type="button" class="btn btn-outline-danger btn-block js-wishlist-delete">
                    <i class="fas fa-trash mr-1"></i> Удалить вишлист
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary js-wishlist-edit-save">Сохранить</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>
