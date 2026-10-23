{{-- Обрезка и поворот картинки (admin.js openImageEditor): на форме создания — карандаш на превью загруженной
     картинки, на странице редактирования — кнопка "Обрезать" на карточке картинки поста.
     Нужны /admin/plugins/cropperjs/cropper.min.{css,js} и стили .b-image-editor из pin-create.css --}}
<div class="modal fade b-image-editor" id="modal-image-editor" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Обрезать и повернуть</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">×</button>
            </div>
            <div class="modal-body">
                <div class="b-image-editor__canvas">
                    <img class="js-image-editor-img" src="" alt="">
                </div>
                <div class="b-image-editor__tools">
                    <div class="b-image-editor__group">
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="rotate" data-value="-90" title="Повернуть влево"><i class="fas fa-undo"></i></button>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="rotate" data-value="90" title="Повернуть вправо"><i class="fas fa-redo"></i></button>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="flipX" title="Отразить по горизонтали"><i class="fas fa-arrows-alt-h"></i></button>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="flipY" title="Отразить по вертикали"><i class="fas fa-arrows-alt-v"></i></button>
                    </div>
                    <div class="b-image-editor__group">
                        <span class="b-image-editor__label">Пропорции:</span>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost is-active" data-editor="ratio" data-value="NaN">Свободно</button>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="ratio" data-value="1">1:1</button>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="ratio" data-value="0.75">3:4</button>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="ratio" data-value="1.3333">4:3</button>
                        <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="ratio" data-value="1.7778">16:9</button>
                    </div>
                    <button type="button" class="b-pin-btn b-pin-btn--ghost" data-editor="reset" title="Отменить все изменения">Сбросить</button>
                </div>
                <p class="b-image-editor__note js-image-editor-note" hidden></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary js-image-editor-save">Сохранить</button>
            </div>
        </div>
    </div>
</div>
