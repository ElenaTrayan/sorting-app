{{--
    Поп-ап "Добавить высказывание" (вкладка "Высказывания", quotes_tab.blade.php).

    Реальное: текст, источник, ссылка на источник, дата, теги, комментарий, сами
    прикреплённые изображения — всё это сохраняется в contact_quotes/contact_quote_hashtag/
    contact_quote_images (см. ContactController::storeQuote()).

    Распознавание текста со скриншота ("Текст успешно распознан..." в макете) — настоящее,
    через OCR.space (см. ContactController::recognizeQuoteText(),
    App\Components\ImageReader). Запускается автоматически при добавлении изображений
    (js-quote-dropzone ниже), кнопка "Распознать заново" — повторный запуск вручную.
--}}
<div class="modal fade" id="modal-add-quote" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавить высказывание</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Изображения (скриншоты)</label>
                    <div class="b-quote-dropzone js-quote-dropzone">
                        <i class="fas fa-image b-quote-dropzone__icon"></i>
                        <div>Перетащите файлы сюда или <span class="b-quote-dropzone__browse">выберите на устройстве</span></div>
                        <div class="text-muted small">PNG, JPG, WEBP, до 10 МБ (можно несколько)</div>
                        <input type="file" class="js-quote-images-input" accept="image/png,image/jpeg,image/webp" multiple hidden>
                    </div>
                    <div class="b-quote-images-preview js-quote-images-preview"></div>
                    <div class="text-muted small mt-1 js-quote-images-count" style="display: none;"></div>

                    <div class="b-quote-recognize js-quote-recognize" style="display: none;">
                        <div class="b-quote-recognize__status js-quote-recognize-status">
                            <i class="fas fa-spinner fa-spin"></i> <span class="js-quote-recognize-text"></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary js-quote-recognize-retry">
                            <i class="fas fa-sync-alt mr-1"></i>Распознать заново
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Текст высказывания <span class="text-danger">*</span></label>
                    <textarea class="form-control js-quote-add-text" rows="4" maxlength="5000" placeholder="Введите текст высказывания..."></textarea>
                    <div class="text-right text-muted small"><span class="js-quote-add-text-count">0</span> / 5000</div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Источник <span class="text-danger">*</span></label>
                        <div class="b-quote-source-select">
                            <span class="b-quote-source-select__icon js-quote-source-icon"><i class="fas fa-share-alt"></i></span>
                            <select class="form-control js-quote-add-platform">
                                <option value="Instagram">Instagram</option>
                                <option value="Facebook">Facebook</option>
                                <option value="Threads">Threads</option>
                                <option value="X (Twitter)">X (Twitter)</option>
                                <option value="Telegram">Telegram</option>
                                <option value="TikTok">TikTok</option>
                                <option value="YouTube">YouTube</option>
                                <option value="Интервью">Интервью</option>
                                <option value="Личное общение">Личное общение</option>
                                <option value="Другое">Другое</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Дата <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center" style="gap: 10px;">
                            <input type="date" class="form-control js-quote-add-date" value="{{ now()->format('Y-m-d') }}">
                            <label class="mb-0 text-nowrap" style="font-weight: 400;">
                                <input type="checkbox" class="js-quote-add-exact-date" checked> Точная дата
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Ссылка на источник</label>
                    <div class="input-group">
                        <input type="text" class="form-control js-quote-add-url" placeholder="https://...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary js-quote-add-url-open" title="Открыть ссылку"><i class="fas fa-external-link-alt"></i></button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Теги</label>
                    <div class="b-contact-tag-picker js-contact-tag-picker js-quote-add-tag-picker">
                        <input type="text" class="form-control js-contact-tag-picker__input" placeholder="Добавить тег...">
                        <ul class="b-contact-tag-picker__results js-contact-tag-picker__results"></ul>
                        <div class="b-contact-tag-picker__selected js-contact-tag-picker__selected"></div>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label>Комментарий</label>
                    <textarea class="form-control js-quote-add-comment" rows="2" maxlength="1000" placeholder="Добавьте комментарий (необязательно)..."></textarea>
                    <div class="text-right text-muted small"><span class="js-quote-add-comment-count">0</span> / 1000</div>
                </div>
            </div>
            <div class="modal-footer">
                <label class="mr-auto mb-0" style="font-weight: 400;">
                    <input type="checkbox" class="js-quote-add-another"> Добавить ещё одно высказывание
                </label>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary js-quote-add-save">Сохранить</button>
            </div>
        </div>
    </div>
</div>
