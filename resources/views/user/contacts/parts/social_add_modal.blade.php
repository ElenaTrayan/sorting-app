{{--
    Поп-ап "Добавить социальную сеть" (боковая панель "Социальные сети", show.blade.php).
    Вёрстка/пропорции/цвета взяты из макета Claude Design (артефакт 8b355a1b) —
    крупный шрифт, скруглённые поля, градиентная иконка Instagram и т.д.

    Платформу можно выбрать из списка (иконка рядом с select обновляется на JS —
    переиспользует getSocialIcon(), см. admin.js) либо ввести свою вручную — кнопка
    "Добавить свою" прячет select и показывает текстовое поле.
    Ссылка и название аккаунта — оба реальных обязательных поля, сохраняются в
    contact_social_links (ContactController::storeSocial()).
--}}
@php
    $socialPlatforms = ['Instagram', 'Facebook', 'Threads', 'X (Twitter)', 'Telegram', 'TikTok', 'YouTube', 'ВКонтакте', 'WhatsApp', 'LinkedIn', 'Pinterest', 'Snapchat'];
@endphp
<div class="modal fade" id="modal-add-social" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered b-social-modal-dialog" role="document">
        <div class="modal-content b-social-modal">
            <div class="modal-header b-social-modal__header">
                <div class="b-social-modal__icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6d3ff3" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="2.6"></circle>
                        <circle cx="6" cy="12" r="2.6"></circle>
                        <circle cx="18" cy="19" r="2.6"></circle>
                        <line x1="8.4" y1="10.7" x2="15.6" y2="6.3"></line>
                        <line x1="8.4" y1="13.3" x2="15.6" y2="17.7"></line>
                    </svg>
                </div>
                <div class="b-social-modal__heading">
                    <h5 class="modal-title">Добавить социальную сеть</h5>
                    <div class="b-social-modal__subtitle">Укажите ссылку на профиль, чтобы добавить социальную сеть</div>
                </div>
                <button type="button" class="close b-social-modal__close" data-dismiss="modal" aria-label="Close">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group js-social-add-platform-group">
                    <label>Социальная сеть <span class="text-danger">*</span></label>

                    {{-- Нативный <select> у раскрытого списка стилизуется браузером/ОС, CSS туда
                         почти не достаёт — поэтому список рисуем сами (b-social-select__list),
                         а <select> прячем и используем только как хранилище значения: вся
                         существующая JS-логика (иконка, сохранение, сброс формы) как читала/писала
                         .js-social-add-platform, так и продолжает — просто платформу в него теперь
                         пишет клик по кастомному пункту, а не браузер. --}}
                    <div class="b-social-select js-social-select">
                        <select class="js-social-add-platform" tabindex="-1" aria-hidden="true">
                            @foreach ($socialPlatforms as $platform)
                                <option value="{{ $platform }}">{{ $platform }}</option>
                            @endforeach
                        </select>

                        <button type="button" class="b-social-select__trigger js-social-select-trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="b-quote-source-select__icon js-social-add-platform-icon"><i class="fas fa-share-alt"></i></span>
                            <span class="b-social-select__value js-social-select-value">{{ $socialPlatforms[0] }}</span>
                            <svg class="b-social-modal__select-chevron" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8b93a7" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>

                        <ul class="b-social-select__list js-social-select-list" role="listbox" hidden>
                            @foreach ($socialPlatforms as $platform)
                                @if (strtolower($platform) === 'instagram')
                                    @php $optionBg = 'linear-gradient(135deg, #f9d13e 0%, #f2603f 40%, #d92e7f 70%, #8a3ab9 100%)'; $optionIcon = 'fab fa-instagram'; @endphp
                                @else
                                    @php [$optionIcon, $optionBg] = \App\Models\ContactSocialLink::iconFor($platform); @endphp
                                @endif
                                <li class="b-social-select__option js-social-select-option @if ($loop->first) is-selected @endif" data-value="{{ $platform }}" role="option">
                                    <span class="b-social-select__option-icon" style="background: {{ $optionBg }}"><i class="{{ $optionIcon }}"></i></span>
                                    {{ $platform }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="b-social-hint">
                        <span class="b-social-hint__icon">i</span>
                        <div class="b-social-hint__text">
                            <div class="b-social-hint__text-line1">Не нашли нужную социальную сеть?</div>
                            <div class="b-social-hint__text-line2">Вы можете добавить свою социальную сеть.</div>
                        </div>
                        <a href="#" class="b-social-hint__btn js-social-add-custom-toggle">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Добавить свою
                        </a>
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
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9aa2b1" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 13a5 5 0 0 0 7.07 0l2.12-2.12a5 5 0 0 0-7.07-7.07l-1.06 1.06"></path>
                            <path d="M14 11a5 5 0 0 0-7.07 0L4.81 13.12a5 5 0 0 0 7.07 7.07l1.06-1.06"></path>
                        </svg>
                        <input type="text" class="form-control js-social-add-url" placeholder="https://...">
                    </div>
                    <div class="text-muted small mt-1">Укажите полную ссылку на профиль, например: https://instagram.com/username</div>
                </div>

                <div class="form-group mb-0">
                    <label>Название аккаунта <span class="text-danger">*</span></label>
                    <div class="b-social-input-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#9aa2b1">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c0-3.6 3.6-6 8-6s8 2.4 8 6z"></path>
                        </svg>
                        <input type="text" class="form-control js-social-add-handle" placeholder="Ник или название аккаунта">
                    </div>
                    <div class="text-muted small mt-1">Например: @albina или Альбина</div>
                </div>

                <div class="b-social-hint-box">
                    <div class="b-social-hint-box__title"><span class="b-social-hint-box__title-icon">i</span>Где найти ссылку?</div>
                    <ul>
                        <li>
                            <span class="b-social-hint-box__row-icon" style="background: linear-gradient(135deg, #f9d13e 0%, #f2603f 40%, #d92e7f 70%, #8a3ab9 100%); border-radius: 10px;"><i class="fab fa-instagram"></i></span>
                            <span><strong>Instagram:</strong> откройте профиль и скопируйте ссылку из адресной строки.</span>
                        </li>
                        <li>
                            <span class="b-social-hint-box__row-icon" style="background: #2ca5e0; border-radius: 50%;"><i class="fab fa-telegram-plane"></i></span>
                            <span><strong>Telegram:</strong> нажмите «Поделиться» → «Копировать ссылку».</span>
                        </li>
                        <li>
                            <span class="b-social-hint-box__row-icon" style="background: #ed1d24; border-radius: 10px;"><i class="fab fa-youtube"></i></span>
                            <span><strong>YouTube:</strong> скопируйте ссылку на канал.</span>
                        </li>
                        <li>
                            <span class="b-social-hint-box__row-icon" style="background: #111318; border-radius: 10px;"><i class="fab fa-tiktok"></i></span>
                            <span><strong>TikTok:</strong> нажмите «Поделиться» → «Копировать ссылку».</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer b-social-modal__footer">
                <button type="button" class="b-social-modal__btn b-social-modal__btn--cancel" data-dismiss="modal">Отмена</button>
                <button type="button" class="b-social-modal__btn b-social-modal__btn--save js-social-add-save">Добавить</button>
            </div>
        </div>
    </div>
</div>
