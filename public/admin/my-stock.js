/*
 * "Мои запасы" — JS только для страниц раздела (подключается через
 * @push('footer-scripts') на admin/stock/*). Держим отдельно от общего admin.js,
 * т.к. раздел большой; переиспользует те же паттерны (jQuery не обязателен —
 * страницы простые, обычный vanilla JS + fetch), toastr и .modal из Bootstrap,
 * которые уже подключены в layouts/admin_layout.blade.php.
 */
(function () {
    'use strict';

    if (!document.querySelector('.b-stock')) {
        return; // не на странице раздела "Мои запасы" — ничего не делаем
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ---------- общий AJAX-хелпер ----------
    function stockAjax(url, method, data) {
        const options = {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        };
        if (data !== undefined) {
            if (data instanceof FormData) {
                options.body = data;
            } else {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }
        }
        return fetch(url, options).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (json) {
                if (!response.ok) {
                    let message = (json && json.message) || 'Не удалось выполнить действие';
                    if (json && json.errors) {
                        const first = Object.values(json.errors)[0];
                        if (first && first[0]) {
                            message = first[0];
                        }
                    }
                    throw new Error(message);
                }
                return json;
            });
        });
    }
    window.stockAjax = stockAjax;

    function stockError(err) {
        console.error(err);
        if (window.toastr) {
            toastr.error(err.message || 'Не удалось выполнить действие');
        }
    }
    window.stockError = stockError;

    // ---------- drawer фильтров (переиспользует .b-filter-panel из contacts) ----------
    // Открывающая кнопка: data-filter-panel-open="<id>", сама панель: data-filter-panel="<id>"
    document.querySelectorAll('[data-filter-panel-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = btn.getAttribute('data-filter-panel-open');
            const panel = document.querySelector('[data-filter-panel="' + id + '"]');
            if (panel) {
                panel.classList.add('b-filter-panel--open');
            }
        });
    });
    document.querySelectorAll('.b-filter-panel').forEach(function (panel) {
        panel.querySelectorAll('.js-filter-panel-close, .b-filter-panel__backdrop').forEach(function (el) {
            el.addEventListener('click', function () {
                panel.classList.remove('b-filter-panel--open');
            });
        });
        // сворачиваемые группы фильтров
        panel.querySelectorAll('.js-filter-group-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                toggle.closest('.b-filter-panel__group').classList.toggle('b-filter-panel__group--collapsed');
            });
        });
    });

    // ---------- dropdown "..." на карточках ----------
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.b-stock-more__btn');
        document.querySelectorAll('.b-stock-more.is-open').forEach(function (openMenu) {
            if (!btn || openMenu !== btn.closest('.b-stock-more')) {
                openMenu.classList.remove('is-open');
            }
        });
        if (btn) {
            btn.closest('.b-stock-more').classList.toggle('is-open');
        }
    });

    // ---------- удаление с подтверждением (общая модалка #modal-delete-item) ----------
    // Кнопка: data-stock-delete-url + data-stock-delete-title (+ опционально data-stock-delete-text)
    const deleteModalEl = document.getElementById('modal-delete-item');
    let pendingDeleteUrl = null;
    if (deleteModalEl) {
        deleteModalEl.querySelector('[data-action="delete-request"]').addEventListener('click', function () {
            if (!pendingDeleteUrl) {
                return;
            }
            stockAjax(pendingDeleteUrl, 'DELETE')
                .then(function () {
                    $(deleteModalEl).modal('hide');
                    if (pendingDeleteRedirect) {
                        window.location.href = pendingDeleteRedirect;
                    } else {
                        window.location.reload();
                    }
                })
                .catch(stockError);
        });
    }
    let pendingDeleteRedirect = null;
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('[data-stock-delete-url]');
        if (!trigger || !deleteModalEl) {
            return;
        }
        pendingDeleteUrl = trigger.getAttribute('data-stock-delete-url');
        pendingDeleteRedirect = trigger.getAttribute('data-stock-delete-redirect');
        deleteModalEl.querySelector('.modal-title').textContent = trigger.getAttribute('data-stock-delete-title') || 'Удалить?';
        deleteModalEl.querySelector('.modal-body').textContent = trigger.getAttribute('data-stock-delete-text') || 'Это действие нельзя отменить.';
        $(deleteModalEl).modal('show');
    });

    // ---------- переключатели (checkbox списка покупок, switch мониторинга магазина) ----------
    document.addEventListener('click', function (e) {
        const check = e.target.closest('[data-stock-toggle-url]');
        if (!check || check.tagName === 'INPUT') {
            return;
        }
        e.preventDefault();
        const url = check.getAttribute('data-stock-toggle-url');
        check.classList.add('is-loading');
        stockAjax(url, 'POST')
            .then(function () {
                window.location.reload();
            })
            .catch(function (err) {
                check.classList.remove('is-loading');
                stockError(err);
            });
    });
    document.addEventListener('change', function (e) {
        const input = e.target.closest('input[data-stock-switch-url]');
        if (!input) {
            return;
        }
        stockAjax(input.getAttribute('data-stock-switch-url'), 'POST')
            .catch(function (err) {
                input.checked = !input.checked;
                stockError(err);
            });
    });

    // ---------- generic "fire and reload" POST action (data-stock-post-url) ----------
    // Для кнопок вроде "Сохранить как постоянную покупку" / "Добавить в список покупок" /
    // "Добавить в запасы" — без формы, без подтверждения (в отличие от delete).
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-stock-post-url]');
        if (!btn) {
            return;
        }
        e.preventDefault();
        if (btn.hasAttribute('data-confirm') && !window.confirm(btn.getAttribute('data-confirm'))) {
            return;
        }
        btn.setAttribute('disabled', 'disabled');
        stockAjax(btn.getAttribute('data-stock-post-url'), 'POST')
            .then(function (json) {
                if (window.toastr && btn.getAttribute('data-success-message')) {
                    toastr.success(btn.getAttribute('data-success-message'));
                }
                if (btn.hasAttribute('data-no-reload')) {
                    btn.removeAttribute('disabled');
                } else {
                    window.location.reload();
                }
            })
            .catch(function (err) {
                btn.removeAttribute('disabled');
                stockError(err);
            });
    });

    // ---------- "Искать лучшее предложение" (Постоянные покупки) ----------
    const bestOfferModalEl = document.getElementById('modal-best-offer');
    document.querySelectorAll('.js-search-offer-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!bestOfferModalEl) {
                return;
            }
            const url = btn.getAttribute('data-search-offers-url');
            const productName = btn.getAttribute('data-product-name') || '';
            const body = bestOfferModalEl.querySelector('.js-best-offer-body');
            const subtitle = bestOfferModalEl.querySelector('.js-best-offer-subtitle');

            subtitle.textContent = productName;
            body.innerHTML = '<div class="b-stock-offers-loading"><i class="fas fa-spinner fa-spin"></i>Ищем лучшие предложения…</div>';
            $(bestOfferModalEl).modal('show');

            stockAjax(url, 'POST')
                .then(function (json) {
                    renderOffers(body, json.offers || []);
                })
                .catch(function (err) {
                    body.innerHTML = '<div class="b-stock-offers-loading"><i class="fas fa-exclamation-triangle"></i>' + (err.message || 'Не удалось получить предложения') + '</div>';
                });
        });
    });

    function renderOffers(container, offers) {
        if (!offers.length) {
            container.innerHTML = '<div class="b-stock-offers-loading"><i class="fas fa-frown"></i>Предложений не найдено — добавьте магазины в разделе "Магазины"</div>';
            return;
        }
        const html = offers.map(function (offer) {
            const bestTag = offer.is_best ? '<div class="b-stock-offer__best-tag"><i class="fas fa-crown"></i> Лучшее предложение</div>' : '';
            const oldPrice = offer.old_price ? '<div class="b-stock-offer__old-price">' + Math.round(offer.old_price) + ' грн</div>' : '';
            const discount = offer.discount_percent ? '<span class="b-stock-status b-stock-status--soon">-' + offer.discount_percent + '%</span>' : '';
            const availabilityLabels = {in_stock: 'В наличии', limited: 'Ограничено', out_of_stock: 'Нет в наличии'};
            const logo = offer.store_logo
                ? '<div class="b-stock-offer__logo" style="background-image:url(' + offer.store_logo + ')"></div>'
                : '<div class="b-stock-offer__logo">' + (offer.store_name || '?').charAt(0) + '</div>';
            const link = offer.url ? '<a href="' + offer.url + '" target="_blank" rel="noopener" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm">Перейти в магазин</a>' : '';

            return '<div class="b-stock-offer' + (offer.is_best ? ' b-stock-offer--best' : '') + '">' +
                logo +
                '<div class="b-stock-offer__body">' +
                    bestTag +
                    '<div class="b-stock-offer__store">' + offer.store_name + '</div>' +
                    '<div class="b-stock-offer__title">' + offer.title + ' · ' + (availabilityLabels[offer.availability] || offer.availability) + '</div>' +
                '</div>' +
                '<div class="b-stock-offer__aside">' +
                    '<div class="b-stock-offer__price">' + Math.round(offer.price) + ' грн ' + discount + '</div>' +
                    oldPrice +
                    link +
                '</div>' +
            '</div>';
        }).join('');
        container.innerHTML = html;
    }

    // ---------- динамические строки товаров в форме заказа ----------
    document.querySelectorAll('.js-order-items').forEach(function (container) {
        const template = container.parentElement.querySelector('.js-order-item-template');
        const addBtn = container.parentElement.querySelector('.js-order-add-item');
        let index = container.querySelectorAll('.js-order-item-row').length;

        function addRow() {
            const html = template.innerHTML.replace(/__INDEX__/g, index++);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            container.appendChild(wrapper.firstElementChild);
        }

        if (addBtn) {
            addBtn.addEventListener('click', addRow);
        }
        container.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.js-order-remove-item');
            if (removeBtn) {
                removeBtn.closest('.js-order-item-row').remove();
            }
        });
        if (index === 0 && addBtn) {
            addRow();
        }
    });

    // ---------- графики "История покупок" (Chart.js уже подключён в layout) ----------
    const historyDataEl = document.getElementById('stock-history-data');
    if (historyDataEl && window.Chart) {
        const chartData = JSON.parse(historyDataEl.textContent);

        const monthCanvas = document.getElementById('stock-chart-by-month');
        if (monthCanvas) {
            new Chart(monthCanvas, {
                type: 'bar',
                data: {
                    labels: chartData.byMonth.labels,
                    datasets: [{
                        label: 'Расходы, грн',
                        data: chartData.byMonth.values,
                        backgroundColor: '#4b3bf5',
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {legend: {display: false}},
                    scales: {y: {beginAtZero: true}},
                },
            });
        }

        const categoryCanvas = document.getElementById('stock-chart-by-category');
        if (categoryCanvas) {
            new Chart(categoryCanvas, {
                type: 'doughnut',
                data: {
                    labels: chartData.byCategory.labels,
                    datasets: [{
                        data: chartData.byCategory.values,
                        backgroundColor: ['#4b3bf5', '#1fa855', '#e08a00', '#dc3545', '#06aec4', '#9099b8'],
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                },
            });
        }
    }

    // ---------- generic modal form submit (data-stock-form-url + method) ----------
    document.querySelectorAll('form[data-stock-ajax-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const url = form.getAttribute('action');
            const method = (form.getAttribute('data-method') || 'POST').toUpperCase();
            const formData = new FormData(form);
            const submitBtn = form.querySelector('[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; }

            let requestUrl = url;
            if (method !== 'POST') {
                formData.append('_method', method);
            }

            stockAjax(requestUrl, 'POST', formData)
                .then(function () {
                    window.location.reload();
                })
                .catch(function (err) {
                    if (submitBtn) { submitBtn.disabled = false; }
                    stockError(err);
                });
        });
    });

})();
