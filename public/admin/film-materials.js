// Блок "Материалы" на странице пина "Фильм / Сериал" (resources/views/user/posts/parts/film_materials.blade.php):
// вкладки разделов, просмотр материалов в Fancybox, открепление и окно "Добавить материалы" — выбор своих
// постов и прикрепление их к фильму (PostController::filmMaterialCandidates/attachFilmMaterials/detachFilmMaterial).
(function () {
    var block = document.querySelector('.js-film-materials');
    if (!block) {
        return;
    }

    var $block = $(block);
    var csrfHeaders = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};

    // превью материала — тот же поп-ап, что и у карточек на "Все посты" (см. posts/index.blade.php)
    Fancybox.bind('[data-fancybox^="film-materials-"]', {
        type: 'ajax',
        dragToClose: false,
        mainClass: 'fancybox-posts-gallery',
        Thumbs: false,
    });
    // клик по картинке внутри превью — она же в полный размер
    Fancybox.bind('[data-fancybox^="post-preview-image-"]', {
        dragToClose: false,
        mainClass: 'fancybox-post-zoom',
    });

    // ===== вкладки разделов =====

    $block.on('click', '.js-film-materials-tab', function () {
        var section = $(this).data('section');
        $block.find('.js-film-materials-tab').removeClass('b-posts-toolbar__tab--active');
        $(this).addClass('b-posts-toolbar__tab--active');
        $block.find('.js-film-materials-panel').each(function () {
            $(this).toggle($(this).data('section') === section);
        });
    });

    // ===== открепление =====

    $block.on('click', '.js-film-materials-detach', function () {
        var $button = $(this).prop('disabled', true);
        $.ajax({url: $button.data('url'), type: 'DELETE', headers: csrfHeaders})
            .done(function () {
                // счётчики вкладок и разделы считает сервер — проще перерисовать страницу
                window.location.reload();
            })
            .fail(function () {
                $button.prop('disabled', false);
                toastr.error('Не удалось открепить пост');
            });
    });

    // ===== окно "Добавить материалы" =====

    var $modal = $('#modal-film-materials');
    var $search = $modal.find('.js-film-materials-search');
    var $candidates = $modal.find('.js-film-materials-candidates');
    var $none = $modal.find('.js-film-materials-none');
    var $more = $modal.find('.js-film-materials-more');
    var $submit = $modal.find('.js-film-materials-submit');

    // выбранные посты { id: true } — живут, пока открыто окно, и переживают новый поиск
    var selected = {};
    var page = 1;
    var request = null;
    var searchTimer = null;

    function updateSubmit() {
        var count = Object.keys(selected).length;
        $submit.prop('disabled', count === 0).text(count ? 'Прикрепить (' + count + ')' : 'Прикрепить');
    }

    function candidateTile(item) {
        var $tile = $('<button type="button" class="b-film-materials__candidate js-film-materials-candidate"></button>')
            .attr('data-id', item.id)
            .attr('title', item.title || '')
            .toggleClass('is-selected', !!selected[item.id]);

        if (item.video) {
            $tile.append($('<video preload="metadata" muted></video>').attr('src', item.video));
            $tile.append('<span class="b-film-materials__play"><i class="fas fa-play"></i></span>');
        } else if (item.cover) {
            $tile.append($('<img alt="" loading="lazy">').attr('src', item.cover));
        } else {
            $tile.append($('<span class="b-film-materials__thumb-text"></span>').text(item.title || 'Без названия'));
        }
        if (item.film) {
            // уже материал другого фильма — прикрепление перенесёт его сюда
            $tile.append($('<span class="b-film-materials__candidate-film"></span>').text(item.film));
        }
        $tile.append('<span class="b-film-materials__candidate-check"><i class="fas fa-check"></i></span>');

        return $tile;
    }

    function loadCandidates(reset) {
        if (reset) {
            page = 1;
        }
        if (request) {
            request.abort();
        }
        request = $.get(block.dataset.candidatesUrl, {q: $search.val(), page: page})
            .done(function (data) {
                if (reset) {
                    $candidates.empty();
                }
                data.items.forEach(function (item) {
                    $candidates.append(candidateTile(item));
                });
                $more.toggle(!!data.has_more);
                $none.toggle($candidates.children().length === 0);
            })
            .fail(function (xhr, status) {
                if (status !== 'abort') {
                    toastr.error('Не удалось загрузить посты');
                }
            });
    }

    $block.on('click', '.js-film-materials-add', function () {
        selected = {};
        updateSubmit();
        $search.val('');
        $candidates.empty();
        loadCandidates(true);
        $modal.modal('show');
    });

    $search.on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            loadCandidates(true);
        }, 300);
    });

    $more.on('click', function () {
        page++;
        loadCandidates(false);
    });

    $candidates.on('click', '.js-film-materials-candidate', function () {
        var id = $(this).data('id');
        if (selected[id]) {
            delete selected[id];
        } else {
            selected[id] = true;
        }
        $(this).toggleClass('is-selected', !!selected[id]);
        updateSubmit();
    });

    $submit.on('click', function () {
        $submit.prop('disabled', true);
        $.ajax({
            url: block.dataset.attachUrl,
            type: 'POST',
            headers: csrfHeaders,
            data: {
                ids: Object.keys(selected),
                kind: $modal.find('input[name="film-material-kind"]:checked').val(),
            },
        })
            .done(function () {
                window.location.reload();
            })
            .fail(function () {
                updateSubmit();
                toastr.error('Не удалось прикрепить посты');
            });
    });
})();
