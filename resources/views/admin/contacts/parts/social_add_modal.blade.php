{{--
    Поп-ап "Добавить социальную сеть" (боковая панель "Социальные сети", show.blade.php).

    Платформу можно выбрать из списка (иконка рядом с select обновляется на JS —
    переиспользует getSocialIcon(), см. admin.js) либо ввести свою вручную — кнопка
    "Добавить свою социальную сеть" прячет select и показывает текстовое поле.
    Ссылка и название аккаунта — оба реальных обязательных поля, сохраняются в
    contact_social_links (ContactController::storeSocial()).
--}}
<div class="modal fade" id="modal-add-social" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить социальную сеть</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group js-social-add-platform-group">
                    <label>Социальная сеть <span class="text-danger">*</span></label>
                    <div class="b-quote-source-select">
                        <span class="b-quote-source-select__icon js-social-add-platform-icon"><i class="fas fa-share-alt"></i></span>
                        <select class="form-control js-social-add-platform">
                            <option value="Instagram">Instagram</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Threads">Threads</option>
                            <option value="X (Twitter)">X (Twitter)</option>
                            <option value="Telegram">Telegram</option>
                            <option value="TikTok">TikTok</option>
                            <option value="YouTube">YouTube</option>
                            <option value="ВКонтакте">ВКонтакте</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="Pinterest">Pinterest</option>
                            <option value="Snapchat">Snapchat</option>
                        </select>
                    </div>

                    <div class="b-social-hint">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            Не нашли нужную социальную сеть? Добавьте свою.<br>
                            <a href="#" class="js-social-add-custom-toggle"><i class="fas fa-plus mr-1"></i>Добавить свою социальную сеть</a>
                        </div>
                    </div>
                </div>

                <div class="form-group js-social-add-custom-group" style="display: none;">
                    <label>Название социальной сети <span class="text-danger">*</span></label>
                    <input type="text" class="form-control js-social-add-custom-platform" placeholder="Например: Behance">
                    <a href="#" class="js-social-add-custom-back small d-inline-block mt-1"><i class="fas fa-arrow-left mr-1"></i>Выбрать из списка</a>
                </div>

                <div class="form-group">
                    <label>Ссылка на аккаунт</label>
                    <div class="b-social-input-icon">
                        <i class="fas fa-link"></i>
                        <input type="text" class="form-control js-social-add-url" placeholder="https://...">
                    </div>
                    <div class="text-muted small mt-1">Укажите полную ссылку на профиль, например: https://instagram.com/username</div>
                </div>

                <div class="form-group mb-0">
                    <label>Название аккаунта <span class="text-danger">*</span></label>
                    <div class="b-social-input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control js-social-add-handle" placeholder="Ник или название аккаунта">
                    </div>
                    <div class="text-muted small mt-1">Например: @albina или Альбина</div>
                </div>

                <div class="b-social-hint-box">
                    <div class="b-social-hint-box__title"><i class="fas fa-info-circle mr-1"></i>Где найти ссылку?</div>
                    <ul>
                        <li><strong>Instagram:</strong> откройте профиль и скопируйте ссылку из адресной строки.</li>
                        <li><strong>Telegram:</strong> нажмите «Поделиться» → «Копировать ссылку».</li>
                        <li><strong>YouTube:</strong> скопируйте ссылку на канал.</li>
                        <li><strong>TikTok:</strong> нажмите «Поделиться» → «Копировать ссылку».</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-info js-social-add-save">Добавить</button>
            </div>
        </div>
    </div>
</div>
