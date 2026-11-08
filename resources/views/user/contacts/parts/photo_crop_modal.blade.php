{{--
    Поп-ап "Фото контакта": выбор части фото, которая попадёт в кружок (форма создания/редактирования
    контакта, contact_form_modal.blade.php). Открывается после выбора или перетаскивания файла
    (cropContactPhoto() в admin.js); на сервер уходит уже обрезанный квадрат — ContactController
    принимает его как обычное фото. Cropper.js — тот же, что в редакторе картинок поста.
--}}
@once
    @push('head-styles')
        <link rel="stylesheet" href="/admin/plugins/cropperjs/cropper.min.css">
    @endpush
    @push('footer-scripts')
        <script src="/admin/plugins/cropperjs/cropper.min.js"></script>
    @endpush

    <div class="modal fade" id="modal-contact-photo-crop" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Фото контакта</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="text-muted small mb-2">Передвиньте и измените размер круга — эта часть фото будет в кружке. Колёсико мыши — масштаб.</div>
                    <div class="b-contact-photo-crop">
                        <img class="js-contact-photo-crop-image" alt="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary js-contact-photo-crop-apply">Применить</button>
                </div>
            </div>
        </div>
    </div>
@endonce
