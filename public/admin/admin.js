 console.log('======= TEST =======');

let tinymceTextarea = $('textarea#editor');
if (tinymceTextarea !== null && tinymceTextarea !== undefined) {
    tinymce.init({
        selector: 'textarea#editor',
        plugins: 'advlist autolink image codesample code charmap lists link preview wordcount',
        toolbar: 'undo redo | link image | code | bullist numlist outdent indent',
        codesample_languages: [
            {text: 'PHP', value: 'php'},
            {text: 'JavaScript', value: 'javascript'},
            {text: 'HTML/XML', value: 'markup'},
            {text: 'CSS', value: 'css'}
        ],
        extended_valid_elements: "svg[*],defs[*],pattern[*],desc[*],metadata[*],g[*],mask[*],path[*],line[*],marker[*],rect[*],circle[*],ellipse[*],polygon[*],polyline[*],linearGradient[*],radialGradient[*],stop[*],image[*],view[*],text[*],textPath[*],title[*],tspan[*],glyph[*],symbol[*],switch[*],use[*]",
        /* enable title field in the Image dialog*/
        image_title: true,
        /* enable automatic uploads of images represented by blob or data URIs*/
        automatic_uploads: true,
        /*
          URL of our upload handler (for more details check: https://www.tiny.cloud/docs/configure/file-image-upload/#images_upload_url)
          images_upload_url: 'postAcceptor.php',
          here we add custom filepicker only to Image dialog
        */
        file_picker_types: 'image',
        /* and here's our custom image picker*/
        file_picker_callback: function (cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');

            /*
              Note: In modern browsers input[type="file"] is functional without
              even adding it to the DOM, but that might not be the case in some older
              or quirky browsers like IE, so you might want to add it to the DOM
              just in case, and visually hide it. And do not forget do remove it
              once you do not need it anymore.
            */

            input.onchange = function () {
                var file = this.files[0];

                var reader = new FileReader();
                reader.onload = function () {
                    /*
                      Note: Now we need to register the blob in TinyMCEs image blob
                      registry. In the next release this part hopefully won't be
                      necessary, as we are looking to handle it internally.
                    */
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);

                    /* call the callback and populate the Title field with the file name */
                    cb(blobInfo.blobUri(), { title: file.name });
                };
                reader.readAsDataURL(file);
            };

            input.click();
        },
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
    });
}


 // Set the options that I want
 toastr.options = {
     "closeButton": true,
     "newestOnTop": false,
     "progressBar": true,
     "positionClass": "toast-top-right",
     "preventDuplicates": false,
     "onclick": null,
     "showDuration": "300",
     "hideDuration": "1000",
     "timeOut": "5000",
     "extendedTimeOut": "1000",
     "showEasing": "swing",
     "hideEasing": "linear",
     "showMethod": "fadeIn",
     "hideMethod": "fadeOut"
 };

 // function sendCreatePostForm() {
 //     //e.preventDefault();
 //
 //     console.log('TEST sendCreatePostForm');
 //
 //     // document.getElementsByClassName('text-block')[0].style.display = 'block';
 //     // document.getElementById('payRetailersPIX_form').style.display = 'none';
 //     //document.creationform.submit();
 // }

 var containerWidth = 1200;

 var masonryOptions = {
     itemSelector: '.grid-item',
     //columnWidth: '.grid-item',
     gutter: 20,
     //horizontalOrder: true,
     percentPosition: true,
     // по умолчанию Masonry анимирует перемещение карточек на новое место за 0.4s —
     // именно из-за этого раскладка выглядит так, будто карточки "заезжают" одна за другой,
     // даже если сама раскладка строится один раз. Отключаем анимацию — карточки сразу
     // становятся на свои места.
     transitionDuration: 0,
 };

 $(document).ready(function() {

     // initialize Masonry
     // Masonry + ImagesLoaded
     var $grid = $('.grid');

     // грид скрыт классом are-images-unloaded (см. CSS), пока не готовы ОБА условия,
     // влияющих на высоту карточек: картинки догрузились И веб-шрифт подставился
     // (до этого раскладка пересчитывалась по каждому из двух условий отдельно —
     // если шрифт догружался позже картинок, грид сначала показывался,
     // а потом ещё раз видимо перестраивался). Раскладываем и показываем один раз,
     // когда готово всё сразу
     var imagesReady = new Promise(function (resolve) {
         // .always() срабатывает и для сломанных картинок тоже — иначе из-за одной
         // неподгрузившейся картинки грид завис бы скрытым навсегда
         $grid.imagesLoaded().always(resolve);
     });
     var fontsReady = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();

     Promise.all([imagesReady, fontsReady]).then(function () {
         $grid.masonry(masonryOptions);
         $grid.removeClass('are-images-unloaded');

         // прелоадер AdminLTE (лого на всю страницу) по умолчанию скрывается сам через
         // фиксированные 200мс после window.load — вообще не проверяя, догрузилась ли
         // сетка постов. Мы отключили этот таймер (data-preload-duration на <body>)
         // и скрываем прелоадер сами, только когда сетка реально готова
         $('.preloader').fadeOut(200);
     });

     // var $grid = $('.grid').imagesLoaded( function() {
     //     $grid.masonry({
     //         itemSelector: '.grid-item'
     //     });
     // });

         // Infinite Scroll
     // $grid.infinitescroll({
     //
     //         // selector for the paged navigation (it will be hidden)
     //         navSelector  : ".pagination",
     //         // selector for the NEXT link (to page 2)
     //         nextSelector : ".page-item .page-link",
     //         // selector for all items you'll retrieve
     //         itemSelector : ".grid-item",
     //
     //         // finished message
     //         loading: {
     //             finishedMsg: 'No more pages to load.'
     //         }
     //     },
     //
     //     // Trigger Masonry as a callback
     //     function( newElements ) {
     //         // hide new items while they are loading
     //         var $newElems = $( newElements ).css({ opacity: 0 });
     //         // ensure that images load before adding to masonry layout
     //         $newElems.imagesLoaded(function(){
     //             // show elems now they're ready
     //             $newElems.animate({ opacity: 1 });
     //             $grid.masonry( 'appended', $newElems, true );
     //         });
     //
     // });

     // Resume Infinite Scroll
     // $('#load-more').click(function(){
     //     $grid.infinitescroll('retrieve');
     //     return false;
     // });

     var page = 1;
     var isLoadingMore = false; // защита от параллельных запросов, пока предыдущий ещё не завершился
     var noMoreRecords = false; // достигнут конец списка — больше не пытаемся подгружать
     $(window).scroll(function() {
         if (window.location.pathname === '/admin_panel/posts') {
             if (isLoadingMore || noMoreRecords) {
                 return;
             }
             console.log(window.location.pathname);
             console.log('---scroll---');
             console.log($(window).scrollTop());
             console.log($(window).height());
             console.log($(document).height());
             if($(window).scrollTop() + $(window).height() + 10 >= $(document).height()) {
                 console.log('---scroll2---');
                 page++;
                 loadMoreData(page);
             }
         }
     });


     function loadMoreData(page){
         console.log('loadMoreData');

         isLoadingMore = true;

         let requestUrl;

         let savedHashtags = localStorage.getItem('hashtags');
         console.log('loadMoreData savedHashtags' + savedHashtags);

         if (savedHashtags !== undefined && savedHashtags !== null && savedHashtags !== '{}') {
             requestUrl =  $('#js-b-search')[0].getAttribute('data-action') + '?page=' + page;
             console.log(requestUrl);
         } else {
             // сохраняем текущие query-параметры страницы (например, without_hashtags=1),
             // подменяя в них только page, чтобы фильтр не терялся при подгрузке следующих страниц
             let params = new URLSearchParams(window.location.search);
             params.set('page', page);
             requestUrl = window.location.pathname + '?' + params.toString();
         }

         // disable scrolling on page load
         document.body.style.overflow = 'hidden';

         //TODO

         $.ajax(
             {
                 url: requestUrl,
                 type: "get",
                 beforeSend: function()
                 {
                     $('.ajax-load').show();
                 }
             })
             .done(function(html)
             {
                 console.log('ddd');
                 console.log(html);

                 //$grid.masonry('destroy'); // destroy masonry

                 if(html === ""){
                     $('.ajax-load').html('<span class="ajax-load__text">Больше постов нет</span>');
                     // больше подгружать нечего — вернуть возможность скроллить страницу
                     // и не пытаться больше грузить следующие страницы
                     document.body.style.overflow = '';
                     isLoadingMore = false;
                     noMoreRecords = true;
                     return;
                 }

                 // $grid.append(html).masonry('appended', html);
                 // $grid.masonry();

                 var $content = $( html );
                 // прячем новые карточки, пока картинки не загрузились и Masonry не расставила
                 // их по местам — иначе видно, как они наезжают друг на друга в процессе
                 $content.css('opacity', 0);
                 $grid.append( $content );
                 $grid.masonry( 'appended', $content) ;

                 // wait for images and Masonry layout to load
                 var $gridd = $('.grid').imagesLoaded( function() {
                     // init Masonry after all images have loaded
                     $gridd.masonry('reloadItems');
                     $gridd.masonry(masonryOptions);

                     // только теперь показываем прелоадер как завершённый и плавно
                     // проявляем новые карточки — раскладка уже корректная
                     $('.ajax-load').hide();
                     $content.animate({opacity: 1}, 200);

                     // enable scrolling when images and Masonry layout have loaded
                     document.body.style.overflow = '';
                     isLoadingMore = false;
                 });

                 // $grid.imagesLoaded().progress( function() {
                 //     // init Masonry after all images have loaded
                 //     $grid.masonry('reloadItems');
                 //     $grid.masonry(masonryOptions);
                 //
                 //     // enable scrolling when images and Masonry layout have loaded
                 //     document.body.style.overflow = '';
                 // });

                 // $grid.masonry(masonryOptions)

                 // var types = ['w1', 'w2', 'w3', 'w4'];
                 // var elems = $(); // empty jquery object
                 // for (var i = 0; i < 3; i++) {
                 //     var elem = $("<div></div>").addClass('item ' + types[Math.floor(Math.random() * types.length)]);
                 //     elems = elems.add( elem );
                 // }
                 //
                 // $grid.append( elems );
                 // $grid.masonry('appended',elems);

                 //$grid.append(data);
                 // initialize Masonry
                 //$grid.masonry( masonryOptions );
             })
             .fail(function(jqXHR, ajaxOptions, thrownError)
             {
                  console.log('fff');
                 // запрос не удался — тоже вернуть возможность скроллить страницу
                 $('.ajax-load').hide();
                 document.body.style.overflow = '';
                 isLoadingMore = false;
                 alert('server not responding...');
             })
             .always(function(html)
             {
                 console.log('aaa');
             });
     }

     let searchInput1 = '#search-input'; //поиск в шапке - по хештегам
     let searchInput1_2 = '#search-input-1-2'; //поиск в шапке - по хештегам (not in)

     let searchInput2 = '#search-input-2'; //поиск при добавлении хештега при создании поста

     let containerSelectedHashtagsTags1 = '#b-search__field__tags-container__tags';
     let containerSelectedHashtagsTags1_2 = '#b-search__field__tags-container__tags-1-2';
     let containerSelectedHashtagsTags2 = '#b-selected-tags-2';

     //очищаем images в localStorage
     if (window.location.href !== '/admin_panel/posts/create') {
         if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
              localStorage.removeItem('images');
         }
         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             localStorage.removeItem('hashtags');
         }
         if (localStorage.getItem('hashtags2') !== undefined && localStorage.getItem('hashtags2') !== null) {
             localStorage.removeItem('hashtags2');
         }
     }

     $.ajaxSetup({ headers: { 'csrftoken' : '{{ csrf_token() }}' } });

     let uploadImageSection = $('.js-upload-image-section');

     // При перетаскивании файлов в форму, подсветить
     uploadImageSection.on('dragover', function (e) {
         $(this).addClass('dd');
         e.preventDefault();
         e.stopPropagation();
     });

     // Предотвратить действие по умолчанию для события dragenter
     uploadImageSection.on('dragenter', function (e) {
         e.preventDefault();
         e.stopPropagation();
     });

     uploadImageSection.on('dragleave', function (e) {
         $(this).removeClass('dd');
     });

     uploadImageSection.on('drop', function (e) {
         $(this).addClass('active');

         console.log($(this));
         console.log($(e.target));

         var url = $(e.target).closest('.js-upload-image-section').attr('data-action');

         if (e.originalEvent.dataTransfer) {
             if (e.originalEvent.dataTransfer.files.length) {
                 e.preventDefault();
                 e.stopPropagation();

                 console.log(e.originalEvent.dataTransfer);

                 // Вызвать функцию загрузки. Перетаскиваемые файлы содержатся
                 // в свойстве e.originalEvent.dataTransfer.files
                 upload(e.originalEvent.dataTransfer.files, url);

                 //$('#add-project-form').trigger('click');
             }
         }
     });

     //Загрузка файлов классическим образом - через модальное окно
     // $(':file').on('change', function () {
     //     //$('#add-project-form').trigger('click');
     //     //upload($(this).prop('files'));
     // });

     // Функция загрузки файлов
     function upload(files, url = '') {
         console.log("UPLOAD");
         console.log(files);

         let formData = new FormData();

         if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
             let savedImages = localStorage.getItem('images');
             console.log('savedImages' + savedImages);
             let images = JSON.parse(savedImages);
             console.log('images' + images);

             for( let i = 0; i < files.length; ++i ) {
                 let imageName = files[i]['name'].split('.')[0];
                 console.log('imageName' + imageName);
                 if (imageName in images) {
                     console.log('Уже есть!');
                     formData.append('files[]', files[i]);
                 } else {
                     formData.append('files[]', files[i]);
                 }
             }

         } else {
             for( let i = 0; i < files.length; ++i ) {
                 console.log('***');
                 console.log(files);
                 formData.append('files[]', files[i]);
             }
         }

         formData.append('username', 'Chris');

         for (let value of formData.values()) {
             console.log('=========' + value);
         }

         let tokenValue = '';
         let inputToken = $("[name='_token']");
         for (let token of inputToken) {
             tokenValue = token.value;
         }

         $.ajax({
             url: url,
             headers: {
                 'X-CSRF-TOKEN': tokenValue,
             },
             method: 'POST',
             data: formData,
             contentType: false,
             cache: false,
             processData: false,
             beforeSend: function () {

             },
             success:function(response)
             {
                 console.log(response);

                 if (response.status === false) {
                     $('.js-error-block').addClass('active');
                     $('.js-error-block p').text(response.error).show();
                     $('.progress').hide();
                 } else {
                     $('.progress-bar').css('width', '100%');
                     $('.progress-value').text('100 %');

                     let uploadedImages = response.images;

                     console.log(uploadedImages);

                     if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
                         let savedImages = localStorage.getItem('images');
                         console.log('savedImages' + savedImages);
                         let images = JSON.parse(savedImages);
                         console.log('images' + images);

                         images = Object.assign({}, images, uploadedImages);
                         console.log('images' + images);

                         localStorage.setItem('images', JSON.stringify(images));
                     } else {
                         localStorage.setItem('images', JSON.stringify(response.images));
                     }

                     console.log(localStorage.getItem('images'));

                     // Отобразить загруженные картинки
                     if (uploadedImages) {

                         // Обертка для картинки со ссылкой
                         // let img = '<div class="image" data-name="1">' +
                         //     '<span style="background-image: url(0)"></span>' +
                         //     '<i class="js-delete-image fas fa-times-circle"></i>' +
                         //     '</div>';

                         for (let key in uploadedImages) {
                             if (uploadedImages.hasOwnProperty(key)) {
                                 console.log(uploadedImages[key]);
                                 console.log(uploadedImages[key]['image_name']);

                                 let img = '<div class="image" data-name="' + uploadedImages[key]['image_name'] + '" data-extension="' + uploadedImages[key]['image_extension'] + '">';

                                 if (uploadedImages[key]['small'] !== undefined) {
                                     img += '<span style="background-image: url(/storage/' + uploadedImages[key]['s_image_path'] + ')"></span>' +
                                         '<i class="js-delete-image temp fas fa-times-circle" data-action="/admin_panel/delete-download-file"></i>' +
                                         '</div>';
                                 } else {
                                     img += '<span style="background-image: url(/storage/' + uploadedImages[key]['image_path'] + ')"></span>' +
                                         '<i class="js-delete-image temp fas fa-times-circle" data-action="/admin_panel/delete-download-file"></i>' +
                                         '</div>';
                                 }

                                 // Добавить в контейнер
                                 $('.js-upload-image-section .images').append(img);
                             }
                         }

                     }
                 }
             },
             error: function(response) {
                 console.log(response);
                 debugger;
             },
         });
     }

     $('.js-close-error-block').on('click', function(e) {
         $(this).closest('.js-error-block').removeClass('active');
     });

     //удаление изображений из поста на странице edit post
     $(document).on('click touchstart', '.js-delete-image.saved', function() {
         console.log('test delete image saved');
         let postImages = localStorage.getItem('postImages');
         console.log('postImages' + postImages);
         let images = JSON.parse(postImages);
         console.log('images' + images);

         let image = $(this)[0].closest('.image');
         console.log('image' + image);
         let name = image.dataset.name;
         console.log('name' + name);
         let extension = image.dataset.extension;
         console.log('extension' + extension);
         let path = image.dataset.path;
         console.log('path' + path);

         let postId = $(this)[0].closest('.card').dataset.id;

         if (delete images[name] === true) {

             console.log($(this).data('action'));

             const myArray = {
                 'postId': postId,
                 'name': name,
                 'extension': extension,
                 'path': path,
             }

             let tokenValue = '';
             let inputToken = $("[name='_token']");
             for (let token of inputToken) {
                 tokenValue = token.value;
             }

             //TODO
             $.ajax({
                 url: $(this).data('action'),
                 type: 'post',
                 headers: {
                     'X-CSRF-TOKEN': tokenValue,
                 },
                 dataType: 'application/json',
                 data: myArray, //image
                 complete: function(response) {
                     console.log("ответ");
                     console.log(response.responseText);
                     let result = JSON.parse(response.responseText);
                     console.log('response' + result);

                     if (result.status === true) {
                         console.log('=====images=====' + JSON.stringify(images));
                         localStorage.setItem('images', JSON.stringify(images));
                         image.remove();
                         toastr.success(result.message);
                     } else {
                         toastr.error(result.message);
                     }
                 },
             });

         }
     });

     //удаление изображений из временной папки
     $(document).on('click touchstart', '.js-delete-image.temp', function() {
         console.log('delete');

         let savedImages = localStorage.getItem('images');
         console.log('savedImages' + savedImages);
         let images = JSON.parse(savedImages);
         console.log('images' + images);

         console.log($(this));
         let image = $(this)[0].closest('.image');
         console.log('image' + image);
         let name = image.dataset.name;
         console.log('name' + name);
         let extension = image.dataset.extension;
         console.log('extension' + extension);

         if (delete images[name] === true) {

             console.log($(this).data('action'));

             const myArray = {
                 'name': name,
                 'extension': extension,
             }

             let tokenValue = '';
             let inputToken = $("[name='_token']");
             for (let token of inputToken) {
                 tokenValue = token.value;
             }

             //TODO
             $.ajax({
                 url: $(this).data('action'),
                 type: 'post',
                 headers: {
                     'X-CSRF-TOKEN': tokenValue,
                 },
                 dataType: 'application/json',
                 data: JSON.stringify(myArray), //image
                 complete: function(response) {
                     console.log("ответ");
                     console.log(response.responseText);
                     let result = JSON.parse(response.responseText);
                     console.log('response' + result);

                     if (result.status === true) {
                         console.log('=====images=====' + JSON.stringify(images));
                         localStorage.setItem('images', JSON.stringify(images));
                         image.remove();
                         toastr.success(result.msg);
                     } else {
                         toastr.error(result.msg);
                     }
                 },
             });

         }

     });

     function resetImagesFromForm() {
         if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
             localStorage.removeItem('images');
         }

         $('.js-images').empty();
     }

     function resetVideoFromForm() {
         localStorage.removeItem('postVideo');
         let preview = document.querySelector('#post-video-upload .js-post-video__preview');
         if (preview) {
             preview.style.display = 'none';
             preview.innerHTML = '';
         }
     }

     // ===== Видео поста на странице создания (страница /admin_panel/posts/create) =====
     // отдельный, простой аплоадер (без drag&drop/ресайза, как у картинок) — файл сразу
     // грузится во временную папку, а его данные сохраняются в localStorage, чтобы уйти
     // в submitCreationForm() вместе с остальной формой
     (function () {
         const videoUploadBlock = document.getElementById('post-video-upload');
         if (!videoUploadBlock) {
             return;
         }

         const uploadUrl = videoUploadBlock.getAttribute('data-action');
         const videoInput = videoUploadBlock.querySelector('.js-post-video__add-input');
         const preview = videoUploadBlock.querySelector('.js-post-video__preview');

         function renderPreview(fileName) {
             preview.style.display = 'flex';
             preview.innerHTML = '<span class="js-post-video__preview-name">' + fileName + '</span>' +
                 '<button type="button" class="btn btn-sm btn-outline-danger js-post-video__remove" title="Убрать"><i class="fas fa-times"></i></button>';
         }

         videoInput.addEventListener('change', function () {
             if (!videoInput.files.length) {
                 return;
             }

             let file = videoInput.files[0];
             let formData = new FormData();
             formData.append('video', file);

             $.ajax({
                 url: uploadUrl,
                 type: 'post',
                 headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function (response) {
                     if (response.status === true) {
                         localStorage.setItem('postVideo', JSON.stringify(response.video));
                         renderPreview(file.name);
                         toastr.success('Видео добавлено к посту');
                     } else {
                         toastr.error(response.message || 'Не удалось загрузить видео');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось загрузить видео');
                 },
                 complete: function () {
                     videoInput.value = '';
                 }
             });
         });

         videoUploadBlock.addEventListener('click', function (event) {
             if (event.target.closest('.js-post-video__remove')) {
                 resetVideoFromForm();
             }
         });
     })();

     function resetHashtagsFromForm(storageKey) {
         if (localStorage.getItem(storageKey) !== undefined && localStorage.getItem(storageKey) !== null) {
             localStorage.removeItem(storageKey);
         }

         $(containerSelectedHashtagsTags2).empty();
     }

     //отправка формы создания поста (на странице /admin_panel/posts/create)
     $('#submit-creation-form').on('click', function(e) {
         e.preventDefault();

         console.log('CLICK');

         submitCreationForm([]);
     });

     // вынесено в отдельную функцию, чтобы можно было переотправить форму с тем же
     // содержимым, но со списком картинок, для которых пользователь подтвердил
     // "это не дубликат, добавить всё равно" (см. showDuplicateConfirmAndRetry ниже)
     function submitCreationForm(forceDuplicateKeys) {
         let creationForm = $("#creationform");

         let images = localStorage.getItem('images');
         console.log('PARSE' + JSON.parse(images));

         let storageKey = 'hashtags2';
         let hashtags = localStorage.getItem(storageKey);

         console.log('hashtags' + hashtags);

         let textareaContent = tinyMCE.activeEditor.getContent({format: 'raw'});

         var numberOfPosts = $('input[type="radio"]:checked').val();
         var groupPosts = document.getElementById("group-posts").checked;

         //get the action-url of the form
         var actionurl = $('#creationform').attr('action');
         console.log('actionurl' + actionurl);

         let data = creationForm.serializeArray();
         data.push({name: 'images', value: images});
         data.push({name: 'hashtags', value: hashtags});
         data.push({name: 'text', value: textareaContent});
         data.push({name: 'numberOfPosts', value: numberOfPosts});
         data.push({name: 'groupPosts', value: groupPosts});
         data.push({name: 'force_duplicate_keys', value: JSON.stringify(forceDuplicateKeys || [])});
         data.push({name: 'video', value: localStorage.getItem('postVideo') || ''});

         //do your own request an handle the results
         $.ajax({
             url: actionurl,
             type: 'post',
             dataType: 'application/json',
             data: data, //images
             complete: function(response) {
                 console.log("ответ");
                 console.log(response.responseText);
                 let result = JSON.parse(response.responseText);
                 console.log('response' + result);
                 console.log('message' + result.message);

                 //обнуляем форму
                 //выводим исчезающее сообщение об успехе

                 if (result.status === true) {
                     creationForm.trigger('reset');
                     $('#editor').trigger('reset');

                     resetImagesFromForm();
                     resetVideoFromForm();

                     resetHashtagsFromForm(storageKey);


                     toastr.success(result.message);
                     //showAlert(result.message, 'alert-info', 'fa-check');
                 } else if (result.duplicate_infos && result.duplicate_infos.length) {
                     confirmDuplicatesAndRetry(result.duplicate_infos, forceDuplicateKeys);
                 } else {
                     // сервер при ошибке отдаёт либо message, либо errors
                     // (плоский массив строк или массив массивов — например, по одной ошибке на картинку)
                     let errorText = result.message;
                     if (!errorText && result.errors) {
                         errorText = [].concat(result.errors).flat(Infinity).join(' ');
                     }
                     toastr.error(errorText || 'Не удалось добавить пост');
                     //showAlert(result.message, 'alert-danger', 'fa-ban');
                 }
             },
         });
     }

     // для каждой найденной "похожей" картинки спрашиваем пользователя (браузерный confirm —
     // тут не заводим отдельную модалку, как на странице редактирования, картинка уже видна
     // на форме создания, а сравнить с существующей можно по ссылке на пост). Если пользователь
     // подтвердил все — переотправляем форму с этими ключами в force_duplicate_keys, если
     // отменил хотя бы одну — просто останавливаемся, ничего не отправляем.
     function confirmDuplicatesAndRetry(duplicateInfos, alreadyForcedKeys) {
         let newForceKeys = (alreadyForcedKeys || []).slice();

         for (let i = 0; i < duplicateInfos.length; i++) {
             let info = duplicateInfos[i];
             let duplicate = info.duplicate;
             let message = duplicate
                 ? 'Похоже, такая картинка уже есть в посте #' + duplicate.post_id + ' (' + duplicate.width + '×' + duplicate.height + ').\n\n'
                     + 'Посмотреть: ' + window.location.origin + '/admin_panel/posts/' + duplicate.post_id + '/edit\n\n'
                     + 'Это точно другая картинка — добавить всё равно?'
                 : 'Похоже, такая картинка уже есть на сайте. Это точно другая картинка — добавить всё равно?';

             if (window.confirm(message)) {
                 if (info.image_key) {
                     newForceKeys.push(info.image_key);
                 }
             } else {
                 toastr.error('Пост не добавлен — есть неподтверждённое совпадение с уже существующей картинкой');
                 return;
             }
         }

         submitCreationForm(newForceKeys);
     }

     let editForm = $("#editform");
     if (editForm !== undefined && editForm !== null) {
         //записать в localStorage теги поста при загрузке страницы
         let savedHashtags = {};
         $(containerSelectedHashtagsTags2 + ' span.tag').each(function(i,elem) {
             savedHashtags[elem.getAttribute('data-id')] = elem.getAttribute('data-name');
             //hashtags.push(elem.getAttribute('data-id'));
         });

         localStorage.setItem('hashtags2', JSON.stringify(savedHashtags));

         //записать в localStorage изображения поста при загрузке страницы
         //{"f-93293":{
         // "image_name":"f-93293","image_extension":"jpg","image_path":"temp_directory/f-93293.jpg",
         // "s_image_name":"f-93293_567_350.jpg","s_image_path":"temp_directory/f-93293_567_350.jpg",
         // "m_image_name":"f-93293_1296_800.jpg","m_image_path":"temp_directory/f-93293_1296_800.jpg"
         // }}
         let postImages = {};
         $('.js-images .image').each(function(i,elem) {
             let imageName = elem.getAttribute('data-name');
             if (!postImages[imageName]) {
                 postImages[imageName] = [];
             }
             let imageExtension = elem.getAttribute('data-extension');
             let imagePath = elem.getAttribute('data-path');
             // let sImageName = elem.getAttribute('data-');
             // let sImagePath = elem.getAttribute('data-');
             // let mImageName = elem.getAttribute('data-');
             // let mImagePath = elem.getAttribute('data-');
             postImages[imageName] = {
                 image_name: imageName,
                 image_extension: imageExtension,
                 image_path: imagePath
             };
         });
         console.log(postImages);
         localStorage.setItem('postImages', JSON.stringify(postImages));
     }

     // Старый обработчик загрузки картинки на странице редактирования поста (через
     // #exampleInputFile/.post-update-image) убран вместе с этими элементами —
     // теперь этим занимается панель "Изображения поста" (см. #post-images ниже),
     // с добавлением/заменой/переименованием/удалением сразу через AJAX.

     $('#submit-edit-form').on('click', function (e) {
         e.preventDefault();

         let storageKey = 'hashtags2';
         let hashtags = localStorage.getItem(storageKey);

         let images = localStorage.getItem('images');
         console.log('PARSE' + JSON.parse(images));

         let data = editForm.serializeArray();
         data.push({name: 'hashtags', value: hashtags});
         data.push({name: 'images', value: images});
         console.log(data);

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         let actionurl = editForm.attr('action');

         $.ajax({
             url: actionurl,
             type: 'PUT',
             data: data,
             dataType: 'application/json',
             complete: function (response) {
                 let result = JSON.parse(response.responseText);

                 //выводим исчезающее сообщение об успехе
                 if (result.status === true) {
                     toastr.success(result.message);
                 } else {
                     toastr.error(result.message);
                 }
             },
         });
     });

     //страница создания поста - добавить хештег
     // $('#add-tag').on('click', function(e) {
     //     console.log('CLICK');
     //
     //     let value = $("#search-hashtag").val();
     //     console.log(value);
     //
     //     localStorage.setItem('hashtags', JSON.stringify());
     // });



     // let tagsContainer = $('#tags');
     //
     // $(document).on('click touchstart', '#found-hashtags li', function() {
     //     console.log('CLICK-CLICK ННННННННННННННННННННННННННННННН');
     //
     //     let hashtagId = $(this)[0].getAttribute('data-id');
     //     let hashtagTitle = $(this)[0].getAttribute('data-name');
     //
     //     if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
     //         let savedHashtags = localStorage.getItem('hashtags');
     //         console.log('savedHashtags' + savedHashtags);
     //         savedHashtags = JSON.parse(savedHashtags);
     //         console.log('savedHashtags' + savedHashtags);
     //
     //         //проверить есть ли ключ в массиве
     //         //если нет - добавить значение в массив
     //         if(savedHashtags.includes(hashtagId) === false) {
     //             let hashtags = savedHashtags;
     //             hashtags.push(hashtagId);
     //             console.log('hashtags' + hashtags);
     //
     //             localStorage.setItem('hashtags', JSON.stringify(hashtags));
     //
     //             let hashtagElement = '<li class="hashtag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">' + hashtagTitle + '<i class="js-delete-image fas fa-times-circle"></i></li>';
     //             tagsContainer.append(hashtagElement);
     //         }
     //
     //     } else {
     //         let hashtags = [];
     //         hashtags.push(hashtagId);
     //
     //         localStorage.setItem('hashtags', JSON.stringify(hashtags));
     //
     //         let hashtagElement = '<li class="hashtag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">' + hashtagTitle + '<i class="js-delete-image fas fa-times-circle"></i></li>';
     //         tagsContainer.append(hashtagElement);
     //     }
     //
     // });

     $('#submit-creation-form2').on('click', function(e) {
         e.preventDefault();

         console.log('CLICK2');

         let creationForm = $("#creationform2");

         creationForm.submit();

         // //get the action-url of the form
         // let actionurl = creationForm.attr('action');
         // console.log('actionurl' + actionurl);
         //
         // let data = creationForm.serializeArray();
         //
         // $.ajaxSetup({
         //     headers: {
         //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         //     }
         // });
         //
         // $.ajax({
         //     url: actionurl,
         //     type: 'post',
         //     dataType: 'application/json',
         //     data: data,
         //     complete: function(response) {
         //         console.log("ответ");
         //         console.log(response.responseText);
         //         let result = JSON.parse(response.responseText);
         //         console.log('response' + result);
         //         console.log('message' + result.message);
         //
         //         //обнуляем форму
         //         //выводим исчезающее сообщение об успехе
         //
         //         if (result.status === true) {
         //             creationForm.trigger('reset');
         //             $('#editor').trigger('reset');
         //
         //             resetImagesFromForm();
         //
         //             resetHashtagsFromForm();
         //
         //
         //             toastr.success(result.message);
         //             //showAlert(result.message, 'alert-info', 'fa-check');
         //         } else {
         //             toastr.error(result.message);
         //             //showAlert(result.message, 'alert-danger', 'fa-ban');
         //         }
         //
         //
         //
         //
         //         //window.location.href = result.redirect;
         //
         //         // if (result.indexOf(errors) === -1) {
         //         //     if (result.indexOf(redirect) !== -1) {
         //         //         window.location.href = result[redirect];
         //         //     } else {
         //         //         window.location.href = "http://profhelp.com.ua";
         //         //     }
         //         //
         //         // }
         //     },
         // });


     });


     // ===== Связанные хештеги при СОЗДАНИИ хештега (страница /admin_panel/hashtags/create) =====
     // Хештег ещё не создан — привязать связь физически не к чему, поэтому здесь просто
     // копим выбранные хештеги локально (чипы), а отправляются они одним запросом
     // вместе с самим хештегом при нажатии "Добавить" (см. related_hashtag_ids ниже).
     let createPageRelatedHashtags = {}; // { id: title }

     (function () {
         const picker = document.querySelector('.js-hashtag-related-picker');
         if (!picker) {
             return;
         }

         const input = document.getElementById('related-hashtags-input');
         const container = document.getElementById('related-hashtags-container');
         const dropdown = document.getElementById('related-hashtags-dropdown');
         const searchUrl = picker.getAttribute('data-search-action');

         function appendChip(id, title) {
             let chip = document.createElement('div');
             chip.classList.add('b-tags__item');
             chip.setAttribute('data-id', id);
             chip.setAttribute('data-name', title);
             chip.textContent = title;

             let removeBtn = document.createElement('span');
             removeBtn.classList.add('close');
             removeBtn.innerHTML = '&times;';
             removeBtn.addEventListener('click', function () {
                 delete createPageRelatedHashtags[id];
                 chip.remove();
             });

             chip.appendChild(removeBtn);
             container.appendChild(chip);
         }

         let searchTimer = null;

         input.addEventListener('input', function () {
             const query = input.value.trim();

             clearTimeout(searchTimer);

             if (!query.length) {
                 dropdown.innerHTML = '';
                 dropdown.style.display = 'none';
                 return;
             }

             searchTimer = setTimeout(function () {
                 $.ajax({
                     type: 'post',
                     url: searchUrl,
                     headers: {
                         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                     },
                     data: {
                         search: query,
                         hashtags: createPageRelatedHashtags,
                     },
                     success: function (response) {
                         dropdown.innerHTML = '';

                         let items = (response && response.hashtags) ? response.hashtags : [];

                         if (!items.length) {
                             dropdown.style.display = 'none';
                             return;
                         }

                         dropdown.style.display = 'block';

                         items.forEach(function (item) {
                             let li = document.createElement('li');
                             li.classList.add('b-tags__dropdown-item');
                             li.textContent = item.title;

                             li.addEventListener('click', function () {
                                 createPageRelatedHashtags[item.id] = item.title;
                                 appendChip(item.id, item.title);

                                 input.value = '';
                                 dropdown.innerHTML = '';
                                 dropdown.style.display = 'none';
                                 input.focus();
                             });

                             dropdown.appendChild(li);
                         });
                     },
                     error: function () {
                         dropdown.innerHTML = '';
                         dropdown.style.display = 'none';
                     }
                 });
             }, 250);
         });

         document.addEventListener('click', function (event) {
             if (!dropdown.contains(event.target) && event.target !== input) {
                 dropdown.style.display = 'none';
             }
         });
     })();

     $('#hashtags-create #btn-submit-form').on('click', function(e) {
         e.preventDefault();

         console.log('CLICK 3');

         let form = $(this).closest('form');

         let fieldTitle = $('#title');
         let fieldTitleValue = fieldTitle.val();
         let errorMessageElement = fieldTitle.next('.error');
         console.log(fieldTitleValue.length);

         let errors = 0;
         if (fieldTitleValue.length === 0) {
             errors = 1;
             fieldTitle.addClass('is-invalid');

             console.log(errorMessageElement);
             errorMessageElement.addClass('active');
             errorMessageElement.text('Пожалуйста введите название хештега');

             // let span = '<span id="exampleInputEmail1-error" class="error invalid-feedback">' + 'Пожалуйста введите название хештега' + '</span>';
             // fieldTitle.after(span);
         } else {
             fieldTitle.removeClass('is-invalid');
             errorMessageElement.removeClass('active');
         }

         console.log('fieldTitleValue' + fieldTitleValue);
         let url = fieldTitle.data('action');
         console.log('url' + url);

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         $.ajax({
             type: 'post',
             url: url,
             data: {'title': fieldTitleValue},
             success: function (response) {
                 //console.log(response);

                 if (response.hasOwnProperty('hashtags') && response.hashtags.length !== 0) {
                     console.log('THIS - ' + fieldTitle);
                     fieldTitle.addClass('is-invalid');
                     errorMessageElement.addClass('active');
                     errorMessageElement.text('Такой хештег уже сущетвует!');
                     console.log(response.hashtags.length);
                 } else {
                     console.log('YESSSSSS');
                     if (errors === 0) {
                         console.log('YES2');

                         // отправляем создание хештега тоже через AJAX (а не обычный submit),
                         // иначе браузер переходит прямо на "сырой" JSON-ответ контроллера.
                         // Вместе с формой отправляем и связанные хештеги, выбранные в пикере выше —
                         // всё создаётся и привязывается одним запросом
                         let submitData = form.serializeArray();
                         Object.keys(createPageRelatedHashtags).forEach(function (relatedId) {
                             submitData.push({name: 'related_hashtag_ids[]', value: relatedId});
                         });

                         $.ajax({
                             type: 'post',
                             url: form.attr('action'),
                             data: submitData,
                             success: function (createResponse) {
                                 if (createResponse.status === true) {
                                     toastr.success(createResponse.message || 'Хештег был успешно добавлен');

                                     // добавляем новый хештег в список "Хештег-родитель" без похода на сервер —
                                     // чтобы его можно было сразу выбрать родителем для следующего
                                     $('#parent_id').append(
                                         $('<option>', {
                                             value: createResponse.info.id,
                                             text: createResponse.info.title,
                                         })
                                     );

                                     // очищаем форму (и пикер связанных хештегов) для ввода следующего хештега —
                                     // без перезагрузки страницы
                                     form[0].reset();
                                     fieldTitle.removeClass('is-invalid');
                                     errorMessageElement.removeClass('active').text('');
                                     fieldTitle.focus();

                                     createPageRelatedHashtags = {};
                                     let relatedContainer = document.getElementById('related-hashtags-container');
                                     if (relatedContainer) {
                                         relatedContainer.innerHTML = '';
                                     }
                                 } else {
                                     toastr.error(createResponse.message || 'Хештег не был добавлен');
                                 }
                             },
                             error: function () {
                                 toastr.error('Не удалось добавить хештег');
                             }
                         });
                     }
                 }
             }
         });

         console.log('errors' + errors);
     });

     $(document).on('input', '#hashtags-create #title', function() {
         console.log('TITLE');
         $(this).removeClass('is-invalid');
         $(this).next('.error').removeClass('active');
     });

     // searchInput1
     // ПОИСК - поиск тегов при вводе букв в input и вывод результатов в b-search__results
     $(document).on('input', searchInput1, function() {
         console.log('click searchInput1');

         let searchUrl = $('#js-b-search__field')[0].getAttribute('data-action');
         console.log(searchUrl);

         let searchValue = $(this).val();
         console.log(searchValue);

         let storageKey = 'hashtags'
         let foundHashtagsContainer = $('#b-search__results');

         searchHashtag(searchValue, searchUrl, storageKey, foundHashtagsContainer);

     });

     // searchInput1
     // ПОИСК - поиск тегов при вводе букв в input и вывод результатов в b-search__results
     $(document).on('input', searchInput1_2, function() {
         console.log('click searchInput1_2');

         let searchUrl = $('#js-b-search__field')[0].getAttribute('data-action');
         console.log(searchUrl);

         let searchValue = $(this).val();
         console.log(searchValue);

         let storageKey = 'hashtags'
         let foundHashtagsContainer = $('#b-search__results-1-2');

         searchHashtag(searchValue, searchUrl, storageKey, foundHashtagsContainer);
     });

     // $('#b-search__results').mousedown(function(event){
     //     event.preventDefault();
     //     if(event.button === 0) {
     //         alert('Вы кликнули левой клавишей');
     //     } else if(event.button === 1) {
     //         alert('Вы кликнули левой колесиком');
     //     } else if(event.button === 2) {
     //         //alert('Вы кликнули правой клавишей');
     //         let myDiv = document.createElement('div');
     //         myDiv.id = 'my';
     //         myDiv.className = 'some';
     //         console.log('target', event.target);
     //         console.log('target', event.target);
     //         event.target.append($('<div id="menu_id"/>'));
     //     }
     // });

     $(document).keypress(function (e) {
         if (e.which === 13) {
             //alert("Pressed");

             e.preventDefault();
             e.stopPropagation();
         }
     });

     // searchInput2
     // ПОИСК - поиск тегов при вводе букв в input и вывод результатов в b-search__results
     $(document).on('keyup', searchInput2, function(event) {
         let searchUrl = $(this)[0].getAttribute('data-action');

         let searchValue = $(this).val();
         console.log(searchValue);

         console.log('click2');

         if (event.keyCode === 13) {
             console.log('ENTER');
             document.getElementById("add-tag").click();

             //TODO

             return false;
         }

         let storageKey = 'hashtags2'
         let foundHashtagsContainer = $('#b-search__results-2');

         searchHashtag(searchValue, searchUrl, storageKey, foundHashtagsContainer);

     });

     // Поиск хештегов
     function searchHashtag(searchValue, searchUrl, storageKey, foundHashtagsContainer) {

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         let hashtagsValue = [];
         let storageHashtags = localStorage.getItem(storageKey);
         if (storageHashtags !== undefined && storageHashtags !== null && storageHashtags !== {}) {
             hashtagsValue = JSON.parse(storageHashtags);
         }

         console.log('hashtagsValue = ' + hashtagsValue);

         $.ajax({
             type: 'post',
             url: searchUrl,
             data: {'search': searchValue, 'hashtags': hashtagsValue},
             success: function (response) {
                 console.log('response - ' + response);

                 let hashtags = response.hashtags;
                 console.log('hashtags - ' + hashtags);

                 foundHashtagsContainer.empty(); //удалить все предыдущие результаты

                 if (hashtags) {
                     for (let key in hashtags) {
                         let hashtagElement = '<li data-id="' + hashtags[key]['id'] + '" data-name="' + hashtags[key]['title'] + '">' + hashtags[key]['title'] + '</li>';
                         // Добавить в контейнер
                         foundHashtagsContainer.append(hashtagElement);
                     }
                 }

             }
         });
     }

     //добавить хештег в список выбранных хештегов, после клика на хештег из результатов поиска
     $(document).on('click touchstart', '#b-search__results li', function() {
         console.log('CLICK-CLICK');
         addHashtagToListForSearch(
             $(this)[0],
             '#b-search__field__tags-container',
             '#b-search__results',
             containerSelectedHashtagsTags1,
             '#b-search__input'
         );
     });

     //добавить хештег в список выбранных хештегов, после клика на хештег из результатов поиска
     $(document).on('click touchstart', '#b-search__results-1-2 li', function() {
         console.log('CLICK-CLICK b-search__results-1-2');
         addHashtagToListForSearch(
             $(this)[0],
             '#b-search__field__tags-container-1-2',
             '#b-search__results-1-2',
             containerSelectedHashtagsTags1_2,
             '#b-search__input-1-2'
         );
     });

     //addTagToSelectedList
     function addHashtagToListForSearch(element, tagsContainer, searchResults, containerSelectedHashtagsTagsElem, searchInput)
     {
         let hashtagId = element.getAttribute('data-id');
         let hashtagTitle = element.getAttribute('data-name');
         console.log('hashtagId = ' + hashtagId);
         console.log('hashtagTitle = ' + hashtagTitle);

         let containerSelectedHashtags = $(tagsContainer);
         let containerSelectedHashtagsTags = $(containerSelectedHashtagsTagsElem);
         console.log('containerSelectedHashtagsTags' + containerSelectedHashtagsTags);

         let foundHashtagsContainer = $(searchResults);

         let storageKey = 'hashtags';

         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null && localStorage.getItem('hashtags') !== '{}') {
             let savedHashtags = localStorage.getItem('hashtags');
             console.log('savedHashtags' + savedHashtags);

             //проверить есть ли ключ в массиве
             //если нет - добавить значение в массив
             if (savedHashtags !== undefined && savedHashtags !== null && savedHashtags !== '{}') {
                 savedHashtags = JSON.parse(savedHashtags);
                 console.log('savedHashtags' + savedHashtags);

                 //проверить есть ли ключ в массиве. если нет - добавить значение в массив
                 if(savedHashtags.hasOwnProperty(hashtagId) === false) {
                     // addHashtagToStorage(hashtagId, hashtagTitle, savedHashtags, storageKey, foundHashtagsContainer);
                     // focusOnInput(searchInput);

                     addHashtagToSelectedHashtags(hashtagId, hashtagTitle, storageKey, searchInput1, foundHashtagsContainer, null, searchInput);

                     let fieldTagsContainerWidth = containerSelectedHashtagsTags.width();
                     let fieldTagsContainerHeightDefault = 42; //containerSelectedHashtags.height();

                     let tagsWidth = 0;
                     let allTagsWidth = 150;
                     let inputWidth = 0;
                     $('#b-search__field__tags-container__tags .tag').each(function(i,elem) {
                         tagsWidth += $(elem).outerWidth() + 8;
                         allTagsWidth += $(elem).outerWidth() + 8;

                         console.log($(elem).data('name'));

                         if (tagsWidth > fieldTagsContainerWidth) {
                             tagsWidth = $(elem).outerWidth() + 8;
                         }

                         let count = Math.floor(allTagsWidth / fieldTagsContainerWidth);

                         let quotient = allTagsWidth / fieldTagsContainerWidth;
                         console.log('quotient = ' + quotient);
                         let numberAfterPoint = quotient.toFixed(2);
                         console.log('numberAfterPoint = ' + numberAfterPoint);

                         inputWidth = fieldTagsContainerWidth - tagsWidth - 24;
                         if (inputWidth < 100) {
                             inputWidth = 100;
                             let quotient = (allTagsWidth + inputWidth) / fieldTagsContainerWidth;
                             console.log('quotient = ' + quotient);
                             let numberAfterPoint = quotient.toFixed(2);

                             //count = Math.round((allTagsWidth + inputWidth) / fieldTagsContainerWidth);
                             // console.log('allTagsWidth + inputWidth = ' + allTagsWidth + inputWidth);
                             // console.log('(allTagsWidth + inputWidth) / fieldTagsContainerWidth = ' + ((allTagsWidth + inputWidth) / fieldTagsContainerWidth));
                         }

                         if ((numberAfterPoint+'').split(".")[1].substr(0,2) > 80 ) {
                             count = count + 1;
                         }

                         console.log('count = ' + count);
                         console.log('inputWidth = ' + inputWidth);
                         console.log('fieldTagsContainerWidth = ' + fieldTagsContainerWidth);
                         console.log('allTagsWidth = ' + allTagsWidth);
                         console.log('count = ' + count);
                         console.log('fieldTagsContainerHeightDefault = ' + fieldTagsContainerHeightDefault);

                         $('#b-search__input').width(inputWidth);

                         fieldTagsContainerHeight = fieldTagsContainerHeightDefault + (36 * count);

                         containerSelectedHashtagsTags.css("height", fieldTagsContainerHeight);
                         containerSelectedHashtags.css("height", fieldTagsContainerHeight);

                         console.log('fieldTagsContainerHeight = ' + fieldTagsContainerHeight);
                         console.log('fieldTagsContainerWidth = ' + fieldTagsContainerWidth);
                         console.log('tagsWidth = ' + tagsWidth);

                     });
                 }
             }

         } else {

             addHashtagToSelectedHashtags(hashtagId, hashtagTitle, storageKey, searchInput1, foundHashtagsContainer, null, '#b-search__input');

             let fieldTagsContainerWidth = containerSelectedHashtagsTags.width();
             let fieldTagsContainerHeightDefault = 42; //containerSelectedHashtags.height();

             let tagsWidth = 0;
             let allTagsWidth = 150;
             let inputWidth = 0;
             $('#b-search__field__tags-container__tags .tag').each(function(i,elem) {
                 tagsWidth += $(elem).outerWidth() + 8;
                 allTagsWidth += $(elem).outerWidth() + 8;

                 console.log($(elem).data('name'));

                 if (tagsWidth > fieldTagsContainerWidth) {
                     tagsWidth = $(elem).outerWidth() + 8;
                 }

                 let count = Math.floor(allTagsWidth / fieldTagsContainerWidth);

                 let quotient = allTagsWidth / fieldTagsContainerWidth;
                 console.log('quotient = ' + quotient);
                 let numberAfterPoint = quotient.toFixed(2);
                 console.log('numberAfterPoint = ' + numberAfterPoint);

                 inputWidth = fieldTagsContainerWidth - tagsWidth - 24;
                 if (inputWidth < 100) {
                     inputWidth = 100;
                     let quotient = (allTagsWidth + inputWidth) / fieldTagsContainerWidth;
                     console.log('quotient = ' + quotient);
                     let numberAfterPoint = quotient.toFixed(2);

                     //count = Math.round((allTagsWidth + inputWidth) / fieldTagsContainerWidth);
                     // console.log('allTagsWidth + inputWidth = ' + allTagsWidth + inputWidth);
                     // console.log('(allTagsWidth + inputWidth) / fieldTagsContainerWidth = ' + ((allTagsWidth + inputWidth) / fieldTagsContainerWidth));
                 }

                 if ((numberAfterPoint+'').split(".")[1].substr(0,2) > 80 ) {
                     count = count + 1;
                 }

                 console.log('count = ' + count);
                 console.log('inputWidth = ' + inputWidth);
                 console.log('fieldTagsContainerWidth = ' + fieldTagsContainerWidth);
                 console.log('allTagsWidth = ' + allTagsWidth);
                 console.log('count = ' + count);
                 console.log('fieldTagsContainerHeightDefault = ' + fieldTagsContainerHeightDefault);

                 $('#b-search__input').width(inputWidth);

                 fieldTagsContainerHeight = fieldTagsContainerHeightDefault + (36 * count);

                 containerSelectedHashtagsTags.css("height", fieldTagsContainerHeight);
                 containerSelectedHashtags.css("height", fieldTagsContainerHeight);

                 console.log('fieldTagsContainerHeight = ' + fieldTagsContainerHeight);
                 console.log('fieldTagsContainerWidth = ' + fieldTagsContainerWidth);
                 console.log('tagsWidth = ' + tagsWidth);
             });
         }
     }

     //добавить хештег в список выбранных хештегов, после клика на хештег из результатов поиска
     $(document).on('click touchstart', '#b-search__results-2 li', function() {
         console.log('CLICK-CLICK 2');

         let hashtagId = $(this)[0].getAttribute('data-id');
         let hashtagTitle = $(this)[0].getAttribute('data-name');

         console.log('hashtagId = ' + hashtagId);
         console.log('hashtagTitle = ' + hashtagTitle);

         let foundHashtagsContainer = $('#b-search__results-2');
         let selectedHashtagsContainer = '#b-selected-tags-2';
         let storageKey = 'hashtags2';

         addHashtagToSelectedHashtags(hashtagId, hashtagTitle, storageKey, searchInput2, foundHashtagsContainer, selectedHashtagsContainer);
     });

     //добавить хэштег к выбранным хэштегам
     function addHashtagToSelectedHashtags(hashtagId, hashtagTitle, storageKey, searchInput, foundHashtagsContainer, selectedHashtagsContainer = null, bSearchInput = null) {

         let hashtagElement = '<span class="tag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">#' + hashtagTitle + '<span class="icon font-icon fas close"></span></span>';

         if (selectedHashtagsContainer !== null) {
             $(selectedHashtagsContainer).append(hashtagElement);
         } else {
             $(hashtagElement).insertBefore(bSearchInput);
         }

         let savedHashtags = localStorage.getItem(storageKey);
         console.log('savedHashtags' + savedHashtags);
         console.log(storageKey);

         if (savedHashtags !== undefined && savedHashtags !== null && savedHashtags !== '{}') {
             savedHashtags = JSON.parse(savedHashtags);
             console.log('savedHashtags' + savedHashtags);

             //проверить есть ли ключ в массиве. если нет - добавить значение в массив
             if(savedHashtags.hasOwnProperty(hashtagId) === false) {
                 addHashtagToStorage(hashtagId, hashtagTitle, savedHashtags, storageKey, foundHashtagsContainer);
                 focusOnInput(searchInput);
             }
         } else {
             addHashtagToStorage(hashtagId, hashtagTitle, {}, storageKey, foundHashtagsContainer);
             focusOnInput(searchInput);
         }
     }

     //добавляем id и title хештега в массив hashtags в localStorage
     function addHashtagToStorage(hashtagId, hashtagTitle, savedHashtags, storageKey, foundHashtagsContainer) {
         // hashtags.push(hashtagId);
         // console.log('hashtags' + hashtags);
         // localStorage.setItem('hashtags', JSON.stringify(hashtags));

         // const arr = {
         //     key1: 'value1',
         // };

         // arr в JSON
         // let str = JSON.stringify(arr);
         // console.log('str = ' + str);
         // str в объект (ассоциативный массив)

         console.log('hashtagId = ' + hashtagId);
         console.log('hashtagTitle = ' + hashtagTitle);
         console.log('savedHashtags = ' + savedHashtags);

         savedHashtags[hashtagId] = hashtagTitle;
         //hashtags[2] = 'bla';
         console.log('savedHashtags = ' + JSON.stringify(savedHashtags));

         localStorage.setItem(storageKey, JSON.stringify(savedHashtags));

         //удалить все предыдущие результаты
         foundHashtagsContainer.empty();
     }

     //сделать активным и пустым input
     function focusOnInput(searchInput) {
         $(searchInput).val('');
         $(searchInput).focus();
     }

     $(document).on('click touchstart', containerSelectedHashtagsTags1 + ' .tag .close', function() {
         console.log("**************");
         console.log($(this));

         let storageKey = 'hashtags';
         removeHashtag($(this), storageKey);
         focusOnInput(searchInput1);
     });

     //удалить хештег из выбранных -> вызов метода removeHashtag
     $(document).on('click touchstart', containerSelectedHashtagsTags2 + ' .tag .close', function() {
         console.log("**************");
         console.log($(this));

         let storageKey = 'hashtags2';

         removeHashtag($(this), storageKey);
         focusOnInput(searchInput2);
     });

     //удалить хештег из выбранных
     function removeHashtag(closeBtn, storageKey) {
         let tag = closeBtn.closest('.tag');
         let tagId = tag.data('id');
         console.log('tagId = ' + tagId);

         let savedHashtags = localStorage.getItem(storageKey);
         console.log('savedHashtags' + savedHashtags);

         if (savedHashtags !== undefined && savedHashtags !== null && savedHashtags !== '{}') {
             savedHashtags = JSON.parse(savedHashtags);
             console.log('savedHashtags' + savedHashtags);

             // var position = savedHashtags.indexOf(tagId.toString());
             // console.log('position' + position);
             // savedHashtags.splice(position, 1);

             delete savedHashtags[tagId];

             let hashtags = savedHashtags;
             console.log('hashtags' + hashtags);
             localStorage.setItem(storageKey, JSON.stringify(hashtags));

             tag.remove();
         } else {
             //TODO
         }

     }

     //добавить хештег, если его нет в найденных и выбранных - кнопка Добавить тег
     $(document).on('click touchstart', '#add-tag', function() {

         let storageKey = 'hashtags2';

         let savedHashtags = localStorage.getItem(storageKey);
         if (savedHashtags === undefined || savedHashtags === null) {
             savedHashtags = {};
         } else {
             savedHashtags = JSON.parse(savedHashtags);
         }

         console.log('savedHashtags' + JSON.stringify(savedHashtags));

         let title = $(searchInput2)[0].value;
         let url = $('#add-tag')[0].getAttribute('data-action');
         console.log('url = ' + url);
         console.log('title = ' + title);

         // let foundHashtagsContainer = $('#b-search__results-2');
         // let selectedHashtagsContainer = '#b-selected-tags-2';
         // addHashtagToSelectedHashtags('88', title, searchInput2, foundHashtagsContainer, selectedHashtagsContainer);

         if (title !== '') {
             $.ajax({
                 type: 'post',
                 url: url,
                 data: {'hashtags': savedHashtags, 'title': title},
                 success: function (response) {
                     console.log(response);

                     if (response.hasOwnProperty('info')) {
                         console.log(response.info);

                         if (response.message !== null && response.message !== undefined) {
                             toastr.success(response.message);
                         } else {
                             toastr.success('Хештег был добавлен');
                         }

                         let foundHashtagsContainer = $('#b-search__results-2');
                         let selectedHashtagsContainer = '#b-selected-tags-2';

                         addHashtagToSelectedHashtags(response.info.id, response.info.title, storageKey, searchInput2, foundHashtagsContainer, selectedHashtagsContainer);
                     } else {
                         if (response.message !== null && response.message !== undefined) {
                             toastr.error(response.message);
                         } else {
                             toastr.error('Хештег не был добавлен');
                         }
                     }

                 }
             });
         }

     });

     //поиск постов по хештегам по нажатию на кнопку "Поиск"
     $(document).on('click touchstart', '#btn-search', function() {
         console.log("*****ПОИСК*****");

         let savedHashtags = localStorage.getItem('hashtags');
         console.log('savedHashtags' + savedHashtags);
         savedHashtags = JSON.parse(savedHashtags);
         console.log('savedHashtags' + savedHashtags);

         let searchUrl = $('#js-b-search')[0].getAttribute('data-action');
         console.log(searchUrl);

         $.ajax({
             type: 'post',
             url: searchUrl,
             data: {'hashtags': savedHashtags},
             success: function (response) {
                 //console.log(response);
                 // let gridItems = $('#posts-index .b-cards .grid .grid-item');
                 // console.log(gridItems);
                 //$grid.masonry('remove', gridItems);
                 $grid.masonry('destroy'); // destroy

                 let postsBlock = $('#posts-index .b-cards .grid');
                 postsBlock.html("");
                 postsBlock.append(response);

                 let postsPagination = $('#posts-index .posts-pagination');
                 postsPagination.html("");

                 $grid.masonry( masonryOptions );

                 // let gridItemsNew = $('#posts-index .b-cards .grid .grid-item');
                 // console.log(gridItemsNew);
             },
             error: function () {
                 console.log('eee');
             }
         });

     });


     // document.getElementById('toggle-exclude').addEventListener('click', function () {
     //     const excludeBlock = document.getElementById('exclude-block');
     //     excludeBlock.style.display =
     //         excludeBlock.style.display === 'none' ? 'block' : 'none';
     // });


     $(document).ready(function() {
         // Переключение вкладок
         const tabButtons = document.querySelectorAll('.js-b-tabs__button');
         const tabPanels = document.querySelectorAll('.js-b-tabs__panel');


         tabButtons.forEach(button => {
             button.addEventListener('click', () => {
                 const tabName = button.dataset.tab;

                 // Активируем кнопку и панель
                 tabButtons.forEach(btn => btn.classList.remove('b-tabs__button--active'));
                 tabPanels.forEach(panel => (panel.style.display = 'none'));

                 button.classList.add('b-tabs__button--active');
                 document.querySelector(`.js-b-tabs__panel[data-tab="${tabName}"]`).style.display = 'block';
             });
         });

         // Кнопка "Поиск" в меню слева: на "Все посты" виджет и так виден в шапке —
         // прокручиваем/фокусируем его; на остальных страницах открывает поп-ап
         // modal-search-global (см. layouts/admin_layout.blade.php)
         const sidebarSearchBtn = document.querySelector('.js-sidebar-search-btn');
         if (sidebarSearchBtn) {
             sidebarSearchBtn.addEventListener('click', function () {
                 let globalModal = document.getElementById('modal-search-global');
                 if (globalModal) {
                     $(globalModal).modal('show');
                     return;
                 }

                 let inlineInput = document.getElementById('include-tags-input');
                 if (inlineInput) {
                     inlineInput.scrollIntoView({behavior: 'smooth', block: 'center'});
                     inlineInput.focus();
                 }
             });
         }

         // ===== Поиск по хэштегам (вкладка "Поиск по хэштегам") =====

         // Выбранные хэштеги отдельно для поля "включить" и поля "исключить": { id: title, ... }
         let hashtagSearchState = {
             include: {},
             exclude: {},
         };

         // Объединяет выбранные хэштеги из обоих полей в один объект { id: title, ... },
         // именно в таком виде их ждёт бэкенд (searchHashtag использует array_keys())
         function getAllSelectedHashtags() {
             return Object.assign({}, hashtagSearchState.include, hashtagSearchState.exclude);
         }

         // Блок "Связанные:" — показывает связанные хэштеги (associated_hashtags) для тех,
         // что сейчас выбраны в поле "включить" (js-b-tags__input). Обновляется при каждом
         // добавлении/удалении хэштега из этого поля.
         const relatedTagsContainer = document.querySelector('.js-b-search-by-hashtags__popular-tags');
         const relatedTagsUrl = relatedTagsContainer ? relatedTagsContainer.getAttribute('data-action') : null;

         function refreshRelatedHashtags() {
             if (!relatedTagsContainer || !relatedTagsUrl) {
                 return;
             }

             // убираем ранее отрисованные подсказки, заголовок "Связанные:" не трогаем
             relatedTagsContainer.querySelectorAll('.js-b-search-by-hashtags__tag').forEach(function (el) {
                 el.remove();
             });

             const includeIds = Object.keys(hashtagSearchState.include);
             if (!includeIds.length) {
                 relatedTagsContainer.style.display = 'none';
                 return;
             }

             $.ajax({
                 type: 'post',
                 url: relatedTagsUrl,
                 headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                 },
                 data: {
                     hashtags: hashtagSearchState.include,
                 },
                 success: function (response) {
                     let items = (response && response.hashtags) ? response.hashtags : [];

                     if (!items.length) {
                         relatedTagsContainer.style.display = 'none';
                         return;
                     }

                     relatedTagsContainer.style.display = 'flex';

                     items.forEach(function (item) {
                         let li = document.createElement('li');
                         li.classList.add('b-search-by-hashtags__tag', 'js-b-search-by-hashtags__tag');
                         li.setAttribute('data-id', item.id);
                         li.setAttribute('data-name', item.title);
                         li.textContent = '#' + item.title;

                         li.addEventListener('click', function () {
                             hashtagSearchState.include[item.id] = item.title;
                             appendHashtagChip(document.getElementById('include-tags-container'), 'include', item.id, item.title);
                             refreshRelatedHashtags();
                         });

                         relatedTagsContainer.appendChild(li);
                     });
                 },
                 error: function () {
                     relatedTagsContainer.style.display = 'none';
                 }
             });
         }

         // Добавляет выбранный хэштег в контейнер в виде "чипы" с крестиком удаления
         function appendHashtagChip(container, stateKey, hashtagId, hashtagTitle) {
             let chip = document.createElement('div');
             chip.classList.add('b-tags__item');
             chip.setAttribute('data-id', hashtagId);
             chip.setAttribute('data-name', hashtagTitle);
             chip.textContent = hashtagTitle;

             let removeBtn = document.createElement('span');
             removeBtn.classList.add('close');
             removeBtn.innerHTML = '&times;';
             removeBtn.addEventListener('click', function () {
                 delete hashtagSearchState[stateKey][hashtagId];
                 chip.remove();
                 if (stateKey === 'include') {
                     refreshRelatedHashtags();
                 }
             });

             chip.appendChild(removeBtn);
             container.appendChild(chip);
         }

         // Универсальная функция для работы с полем ввода хэштегов
         function setupTagField(inputId, containerId, dropdownId, stateKey) {
             const input = document.getElementById(inputId);
             if (!input) {
                 // поле хэштегов не отрисовано — на этой странице вкладка "по хэштегам"
                 // скрыта (см. search_widget.blade.php, $showHashtagsTab)
                 return;
             }

             const container = document.getElementById(containerId);
             const dropdown = document.getElementById(dropdownId);
             const field = input.closest('.js-b-search-by-hashtags__field, .js-b-search-by-hashtags__exclude');
             const searchUrl = field ? field.getAttribute('data-action') : null;

             let searchTimer = null;

             input.addEventListener('input', function () {
                 const query = input.value.trim();

                 clearTimeout(searchTimer);

                 if (query.length === 0 || !searchUrl) {
                     dropdown.innerHTML = '';
                     dropdown.style.display = 'none';
                     return;
                 }

                 searchTimer = setTimeout(function () {
                 $.ajax({
                     type: 'post',
                     url: searchUrl,
                     headers: {
                         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                     },
                     data: {
                         search: query,
                         hashtags: getAllSelectedHashtags(),
                     },
                     success: function (response) {
                         dropdown.innerHTML = '';

                         const filteredTags = (response && response.hashtags) ? response.hashtags : [];

                     if (filteredTags.length > 0) {
                         dropdown.style.display = 'block'; // Показываем выпадающий список

                         filteredTags.forEach(function (hashtag) {
                             const item = document.createElement('li');
                             item.textContent = hashtag.title;
                             item.setAttribute('data-id', hashtag.id);
                             item.setAttribute('data-name', hashtag.title);
                             item.classList.add('b-tags__dropdown-item');
                             dropdown.appendChild(item);

                             // Добавление хэштега в контейнер при клике
                             item.addEventListener('click', function () {
                                 hashtagSearchState[stateKey][hashtag.id] = hashtag.title;
                                 appendHashtagChip(container, stateKey, hashtag.id, hashtag.title);
                                 if (stateKey === 'include') {
                                     refreshRelatedHashtags();
                                 }
                                 input.value = '';
                                 dropdown.innerHTML = '';
                                 dropdown.style.display = 'none';
                                 input.focus();
                             });
                         });
                     } else {
                         dropdown.style.display = 'none';
                     }
                     },
                     error: function () {
                         dropdown.innerHTML = '';
                         dropdown.style.display = 'none';
                     }
                 });
                 }, 250);
             });

             // Скрытие выпадающего списка при клике вне его
             document.addEventListener('click', function (event) {
                 if (!dropdown.contains(event.target) && event.target !== input) {
                     dropdown.style.display = 'none';
                 }
             });
         }

         // Настройка полей для включения и исключения хэштегов
         setupTagField('include-tags-input', 'include-tags-container', 'include-tags-dropdown', 'include');
         setupTagField('exclude-tags-input', 'exclude-tags-container', 'exclude-tags-dropdown', 'exclude');

         // Крестик рядом с полем ввода — очищает все выбранные хэштеги из b-tags (поле "включить")
         const clearIncludeTagsBtn = document.querySelector('.js-b-tags__clear');
         if (clearIncludeTagsBtn) {
             clearIncludeTagsBtn.addEventListener('click', function () {
                 hashtagSearchState.include = {};
                 document.getElementById('include-tags-container').innerHTML = '';
                 refreshRelatedHashtags();
             });
         }

         // Переключение видимости блока исключений
         const toggleExclude = document.querySelector('.js-b-search-by-hashtags__toggle');
         const excludeContainer = document.querySelector('.js-b-search-by-hashtags__exclude-container');
         const hideExcludeBtn = document.querySelector('.js-b-search-by-hashtags__exclude-hide');

         if (toggleExclude && excludeContainer) {
             toggleExclude.addEventListener('click', function () {
                 let isHidden = excludeContainer.style.display === 'none' || excludeContainer.style.display === '';
                 excludeContainer.style.display = isHidden ? 'flex' : 'none';
                 // визуально — "чекбокс" в поп-апе (#modal-search-global), см. admin-panel.css
                 toggleExclude.classList.toggle('is-checked', isHidden);
             });
         }

         if (hideExcludeBtn) {
             hideExcludeBtn.addEventListener('click', function () {
                 // очищаем все выбранные хэштеги в блоке исключений
                 hashtagSearchState.exclude = {};
                 document.getElementById('exclude-tags-container').innerHTML = '';
             });
         }

         // "Очистить все" (шапка секции "Хештеги" в поп-апе) и "Сбросить" (футер) — оба
         // чистят и включённые, и исключённые хэштеги; "Сбросить" дополнительно прячет
         // блок исключений и возвращает активной вкладку "по хэштегам"
         function clearAllHashtags() {
             hashtagSearchState.include = {};
             hashtagSearchState.exclude = {};
             document.getElementById('include-tags-container').innerHTML = '';
             document.getElementById('exclude-tags-container').innerHTML = '';
             refreshRelatedHashtags();
         }

         const clearAllBtn = document.querySelector('.js-b-tags__clear-all');
         if (clearAllBtn) {
             clearAllBtn.addEventListener('click', clearAllHashtags);
         }

         const searchResetBtn = document.querySelector('.js-b-search-reset');
         if (searchResetBtn) {
             searchResetBtn.addEventListener('click', function () {
                 clearAllHashtags();

                 if (excludeContainer) {
                     excludeContainer.style.display = 'none';
                 }
                 if (toggleExclude) {
                     toggleExclude.classList.remove('is-checked');
                 }

                 let hashtagsTabBtn = document.querySelector('.js-b-tabs__button[data-tab="hashtags"]');
                 if (hashtagsTabBtn) {
                     hashtagsTabBtn.click();
                 }
             });
         }

         // Фильтрует карточки контактов на месте (#contacts-index) — все контакты и так
         // уже в DOM (contacts.index не пагинирует список), поэтому без AJAX/бэкенда:
         // сверяем data-hashtag-ids каждой карточки с выбранными хэштегами
         function filterContactsByHashtags(contactsIndex) {
             let includeIds = Object.keys(hashtagSearchState.include);
             let excludeIds = Object.keys(hashtagSearchState.exclude);
             let cards = contactsIndex.querySelectorAll('.b-contact-card');
             let visibleCount = 0;

             cards.forEach(function (card) {
                 let cardIds = (card.getAttribute('data-hashtag-ids') || '').split(',').filter(Boolean);
                 let hasAllIncluded = includeIds.every(function (id) { return cardIds.indexOf(id) !== -1; });
                 let hasExcluded = excludeIds.some(function (id) { return cardIds.indexOf(id) !== -1; });
                 let visible = hasAllIncluded && !hasExcluded;
                 card.style.display = visible ? '' : 'none';
                 if (visible) {
                     visibleCount++;
                 }
             });

             // держим счётчик "N из M контактов" и состояние "не найдено" в том же виде,
             // что и после поиска по тексту (см. applySearch() ниже, в блоке "База
             // контактов: список контактов"). Сам счётчик лежит в шапке (content-header),
             // вне #contacts-index, поэтому ищем его глобально, а не через contactsIndex
             let countLabel = document.querySelector('.js-contacts-count');
             if (countLabel) {
                 countLabel.textContent = visibleCount + ' из ' + cards.length + ' ' + (countLabel.getAttribute('data-word') || '');
             }
             let emptySearchState = contactsIndex.querySelector('.js-contacts-empty-search');
             if (emptySearchState) {
                 emptySearchState.style.display = visibleCount === 0 ? '' : 'none';
             }
             let grid = contactsIndex.querySelector('.js-contacts-grid');
             if (grid) {
                 grid.style.display = visibleCount === 0 ? 'none' : '';
             }

             toastr.info('Найдено контактов: ' + visibleCount);
         }

         // Клик по кнопке "Найти" — ищет по выбранным хэштегам (с учётом исключённых) там,
         // где для этого открыт виджет: посты фильтруются на "Все посты" (#posts-index,
         // AJAX), контакты — на "База контактов" (#contacts-index, на месте, JS). Если
         // виджет открыт поп-апом откуда-то ещё — фильтровать нечего (вкладка "по хэштегам"
         // в таком случае вообще не показывается, см. search_widget.blade.php), но на всякий
         // случай просто переходим на "Все посты"
         const searchByHashtagsBtn = document.querySelector('.js-b-search-by-hashtags__button');
         if (searchByHashtagsBtn) {
             searchByHashtagsBtn.addEventListener('click', function () {
                 if (!Object.keys(hashtagSearchState.include).length) {
                     toastr.warning('Выберите хотя бы один хэштег для поиска');
                     return;
                 }

                 let contactsIndex = document.getElementById('contacts-index');
                 if (contactsIndex) {
                     filterContactsByHashtags(contactsIndex);
                     $('#modal-search-global').modal('hide');
                     return;
                 }

                 // сохраняем в localStorage для совместимости с подгрузкой следующих страниц (infinite scroll)
                 localStorage.setItem('hashtags', JSON.stringify(hashtagSearchState.include));

                 if (!document.getElementById('posts-index')) {
                     window.location.href = searchByHashtagsBtn.getAttribute('data-posts-url');
                     return;
                 }

                 let searchUrl = searchByHashtagsBtn.getAttribute('data-action');

                 $.ajax({
                     type: 'post',
                     url: searchUrl,
                     headers: {
                         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                     },
                     data: {
                         hashtags: hashtagSearchState.include,
                         excludeHashtags: hashtagSearchState.exclude,
                     },
                     success: function (response) {
                         let $searchGrid = $('.grid');
                         $searchGrid.masonry('destroy');

                         let postsBlock = $('#posts-index .b-cards .grid');
                         postsBlock.html('');
                         postsBlock.append(response);

                         let postsPagination = $('#posts-index .posts-pagination');
                         postsPagination.html('');

                         $searchGrid.masonry(masonryOptions);
                     },
                     error: function () {
                         toastr.error('Не удалось выполнить поиск');
                     }
                 });
             });
         }

         // Кнопка "Экспорт изображений" (страница /admin_panel/posts) — открывает окно
         // выбора имени архива, сам архив собирается из результатов текущего поиска
         // по хештегам (тот же hashtagSearchState.include, что и кнопка "Поиск" выше)
         const exportImagesBtn = document.getElementById('js-export-images-btn');
         if (exportImagesBtn) {
             const exportModal = $('#modal-export-images');
             const exportFilenameInput = document.getElementById('export-images-filename');
             const exportSuggestions = document.querySelector('.js-export-images__suggestions');

             function slugifyForFilename(text) {
                 return text
                     .toString()
                     .trim()
                     .toLowerCase()
                     .replace(/\s+/g, '-')
                     .replace(/[^\p{L}\p{N}\-]+/gu, '');
             }

             function buildExportFilenameSuggestions() {
                 let today = new Date().toISOString().slice(0, 10);
                 let hashtagTitles = Object.values(hashtagSearchState.include).map(slugifyForFilename).filter(Boolean);
                 let suggestions = [];

                 if (hashtagTitles.length) {
                     suggestions.push(hashtagTitles.join('-'));
                     suggestions.push(hashtagTitles.join('-') + '-' + today);
                 }

                 suggestions.push('экспорт-изображений-' + today);

                 return suggestions.filter(function (value, index, self) {
                     return self.indexOf(value) === index;
                 });
             }

             exportImagesBtn.addEventListener('click', function () {
                 if (!Object.keys(hashtagSearchState.include).length) {
                     toastr.warning('Сначала выполните поиск по хештегам — экспортируются картинки из результатов поиска');
                     return;
                 }

                 let suggestions = buildExportFilenameSuggestions();
                 exportFilenameInput.value = suggestions[0] || '';

                 exportSuggestions.innerHTML = '';
                 suggestions.forEach(function (suggestion) {
                     let chip = document.createElement('span');
                     chip.className = 'b-export-images__suggestion';
                     chip.textContent = suggestion;
                     chip.addEventListener('click', function () {
                         exportFilenameInput.value = suggestion;
                     });
                     exportSuggestions.appendChild(chip);
                 });

                 exportModal.modal('show');
             });

             exportModal.on('click', '.js-export-images__confirm', function () {
                 let filename = exportFilenameInput.value.trim() || 'изображения';
                 let exportUrl = exportImagesBtn.getAttribute('data-action');

                 exportModal.modal('hide');

                 window.location.href = exportUrl + '?filename=' + encodeURIComponent(filename);
             });
         }

     });






     //удаление категорий постов на странице /admin_panel/posts-categories -> вызов попап окна #modal-delete-item
     $(document).on('click touchstart', '#posts-categories-index [data-action=delete]', function() {
         $('#modal-delete-item').modal('show', $(this));
     });

     //удаление категорий постов на странице /admin_panel/posts-categories -
     // при нажатии на кнопку Удалить в попап окне #modal-delete-item происходит ajax-запрос
     $(document).on('click touchstart', '#posts-categories-index [data-action=delete-request]', function() {
         console.log('delete-request');

         let actionUrl = $(this).data('actionUrl');
         let categoryId = $(this).data('elementId');

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         let categoryElement = $('tr[data-id='+categoryId+']');

         $('#modal-delete-item').modal('hide');

         $.ajax({
             type: 'delete',
             url: actionUrl,
             data: {'id': categoryId},
             success: function (response) {
                 console.log(response);

                 if (response.status === true) {
                     categoryElement.remove();
                     if (response.message !== null && response.message !== undefined) {
                         toastr.success(response.message);
                     } else {
                         toastr.success('Item has been removed');
                     }

                 } else {
                     if (response.message !== null && response.message !== undefined) {
                         toastr.error(response.message);
                     } else {
                         toastr.error('Some error has occurred');
                     }
                 }

             }
         });

     });

     //событие при открытии попап окна #modal-delete-item
     $(document).on('shown.bs.modal','#modal-delete-item', function (event) {
         let button = $(event.relatedTarget); // Button that triggered the modal

         // let path = window.location.pathname;
         // let id = path.replace('/admin_panel/', '');
         let pageId = button.data('page-id')
         console.log(pageId);
         let modal = $(this);

         let messages = getMessage(pageId);

         if (messages.has('title')) {
             modal.find('.modal-title').text(messages.get('title'));
         }

         if (messages.has('sub-title')) {
             modal.find('.modal-body').text(messages.get('sub-title'));
         }

         let element = getElementInfo(pageId, button);
         console.log(element);

         if (element.has('element-id')) {
             $('[data-action=delete-request]').data('elementId', element.get('element-id'));
         }

         if (element.has('action-url')) {
             $('[data-action=delete-request]').data('actionUrl', element.get('action-url'));
         }
     });

     //получаем данные для попап окна #modal-delete-item
     function getElementInfo(id, button) {
         switch (id) {
             case "posts-categories":
                 let element = button.closest('tr');
                 let elementId = element.data('id');
                 let actionUrl = button.data('url');

                 return new Map([
                     ['element-id', elementId],
                     ['action-url', actionUrl],
                 ]);
             case "posts":
                 let element2 = button.closest('.grid-item');
                 let elementId2 = element2.data('id');
                 let actionUrl2 = button.data('url');

                 return new Map([
                     ['element-id', elementId2],
                     ['action-url', actionUrl2],
                 ]);
             case "hashtags":
                 let element3 = button.closest('[data-id]');
                 let elementId3 = element3.data('id');
                 let actionUrl3 = button.data('url');

                 return new Map([
                     ['element-id', elementId3],
                     ['action-url', actionUrl3],
                 ]);
             default:
                 console.log("Sorry, we are out of  ");
         }
     }

     //получаем данные для попап окна #modal-delete-item
     function getMessage(id) {
         switch (id) {
             case "posts-categories":
                 return new Map([
                     ['title', 'Вы уверены, что хотите удалить категорию?'],
                     ['sub-title', 'Вы не сможете восстановить категорию после удаления!'],
                 ]);
             case "posts":
                 return new Map([
                     ['title', 'Вы уверены, что хотите удалить пост?'],
                     ['sub-title', 'Вы не сможете восстановить пост после удаления!'],
                 ]);
             case "hashtags":
                 return new Map([
                     ['title', 'Вы уверены, что хотите удалить хештег?'],
                     ['sub-title', 'Вы не сможете восстановить хештег после удаления!'],
                 ]);
             default:
                 return new Map([
                     ['title', 'Вы уверены, что хотите это удалить?'],
                     ['sub-title', 'Вы не сможете восстановить это после удаления!'],
                 ]);
         }
     }

     //пока не используется (возможно потом удалю)
     function getModalButtons(id) {
         let modalButtons = '<button type="button" class="btn btn-primary" data-action="delete-request">Удалить</button>\n';
         switch (id) {
             case "delete":
                 modalButtons += '<button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>';
                 break;
             default:
                 console.log("Sorry, we are out of  ");
         }

         return modalButtons;
     }

     //удаление постов на странице /admin_panel/posts -> вызов попап окна #modal-delete-item
     $(document).on('click touchstart', '.b-list-of-posts [data-action=delete]', function() {
         $('#modal-delete-item').modal('show', $(this));
     });

     //удаление постов на странице /admin_panel/posts -
     // при нажатии на кнопку Удалить в попап окне #modal-delete-item происходит ajax-запрос
     $(document).on('click touchstart', '#posts-index [data-action=delete-request]', function() {
         console.log('delete-request POST');

         let actionUrl = $(this).data('actionUrl');
         let postId = $(this).data('elementId');

         let postElement = $('.grid-item[data-id='+postId+']');

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         $.ajax({
             type: 'delete',
             url: actionUrl,
             data: {'id': postId},
             success: function (response) {
                 $('#modal-delete-item').modal('hide');

                 console.log('test 77777777');
                 console.log(response);

                 if (response.status === true) {
                     $grid.masonry('destroy'); // destroy masonry
                     postElement.remove(); // убираем удаленный пост со страницы

                     if (response.message !== null && response.message !== undefined) {
                         toastr.success(response.message);
                     } else {
                         toastr.success('Item has been removed');
                     }

                     // initialize Masonry
                     $grid.masonry( masonryOptions );

                 } else {
                     if (response.message !== null && response.message !== undefined) {
                         toastr.error(response.message);
                     } else {
                         toastr.error('Some error has occurred');
                     }
                 }
             }
         });
     });

     //удаление хештегов на странице /admin_panel/hashtags -> вызов попап окна #modal-delete-item
     $(document).on('click touchstart', '#hashtags-index [data-action=delete]', function() {
         $('#modal-delete-item').modal('show', $(this));
     });

     //удаление хештегов на странице /admin_panel/hashtags -
     // при нажатии на кнопку Удалить в попап окне #modal-delete-item происходит ajax-запрос
     $(document).on('click touchstart', '#hashtags-index [data-action=delete-request]', function() {
         console.log('delete-request');

         let actionUrl = $(this).data('actionUrl');
         let postId = $(this).data('elementId');

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         let postElement = $('#hashtags-index [data-id='+postId+']');

         $('#modal-delete-item').modal('hide');

         $.ajax({
             type: 'delete',
             url: actionUrl,
             data: {'id': postId},
             success: function (response) {
                 console.log(response);

                 if (response.status === true) {
                     // перезапрашиваем блок целиком — так вместе с хештегом корректно
                     // пропадёт и опустевшая буква (и из навигации, и из списка),
                     // обновится счётчик "Всего хештегов"
                     $.get(window.location.pathname)
                         .done(function (html) {
                             $('#hashtags-index-body').html(html);
                         })
                         .fail(function () {
                             // если перезапрос не удался — хотя бы уберём удалённый элемент вручную
                             postElement.remove();
                         });

                     if (response.message !== null && response.message !== undefined) {
                         toastr.success(response.message);
                     } else {
                         toastr.success('Item has been removed');
                     }

                 } else {
                     if (response.message !== null && response.message !== undefined) {
                         toastr.error(response.message);
                     } else {
                         toastr.error('Some error has occurred');
                     }
                 }

             }
         });
     });

     $(document).on('keyup', '#posts-categories-edit #title', function(event) {
         let aliasInput = $('#posts-categories-edit #alias');
         //$(this).val()
         //let str = transliter($(this).val().slice(-1)); //transliter(event.target.value);
         $('#modal-delete-item').modal('show', $(this));
         let inputValue = event.target.value; //$(this).val();
         console.log(inputValue);

         let aliasValue = '';
         for ( let i = 0; i < inputValue.length; ++i) {
             aliasValue += transliter(inputValue[i]);
         }
         aliasInput.val(aliasValue);
     });

     $(document).on('keyup', '#posts-categories-create #title', function(event) {
         let aliasInput = $('#posts-categories-create #alias');

         let inputValue = event.target.value; //$(this).val();
         console.log(inputValue);

         let aliasValue = '';
         for ( let i = 0; i < inputValue.length; ++i) {
             aliasValue += transliter(inputValue[i]);
         }
         aliasInput.val(aliasValue);
     });

     function showAlert(message, alertStyle, alertIconStyle) {
         $(".alert").addClass(alertStyle);
         $(".alert i").addClass(alertIconStyle);
         $(".alert").find('.message').text(message);
         // $(".alert").fadeIn("slow", function() {
         //     setTimeout(function() {
         //         $(".alert").fadeOut("slow");
         //     }, 4000);
         // });
     }

     //страница - список постов: кнопка Добавить тег -> открывается поп-ап окно
     $(document).on('click', '#posts-index [data-action=add-hashtag]', function(event) {
         $('#modal-add-hashtag').modal('show', $(this));

         let searchInput = $('#modal-add-hashtag #search-input-2')[0];
         console.log(searchInput);
         setTimeout(function() { searchInput.focus() }, 500);

         let tagsBlockElement = $(this).closest('.grid-item').find('.tags li');
         console.log(tagsBlockElement);

         let hashtags = {};
         tagsBlockElement.each(function(i,elem) {
             //TODO добавить к .tags li  data-id
             console.log('data-id = ' + elem.getAttribute('data-id'));
             let hashtagId = elem.getAttribute('data-id');
             let hashtagTitle = elem.getAttribute('data-title');
             hashtags[hashtagId] = hashtagTitle;

             let postHashtag = '<span class="tag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">#' + hashtagTitle + '<span class="icon font-icon fas close"></span></span>';
             $(containerSelectedHashtagsTags2).append(postHashtag);
         });

         localStorage.setItem('hashtags2', JSON.stringify(hashtags));
     });

     // закрыть popup окно при клике вне его области
     $(document).mouseup(function(e) {
         var container = $('#modal-add-hashtag');
         if (!container.is(e.target) && container.has(e.target).length === 0) {
             container.hide();
         }
     });

     //страница - список постов: кнопка Отмена -> закрывается поп-ап окно
     $(document).on('click', '#posts-index [data-action=close-modal-add-hashtag]', function(event) {
         $('#modal-add-hashtag').modal('hide');
         //очистить блок свыбранными хештегами в поп-ап окне
         $(containerSelectedHashtagsTags2).html('');
     });

     $(document).on('shown.bs.modal','#modal-add-hashtag', function (event) {
         console.log('modal-add-hashtag');
         let button = $(event.relatedTarget); // Button that triggered the modal
         console.log(button);
         console.log($(this));

         let actionUrl = button.data('url');
         console.log(actionUrl);
         let postId = button.data('post-id');
         console.log(postId);

         $('[data-action=save-request]').data('url', actionUrl);
         $('[data-action=save-request]').data('post-id', postId);
         console.log($('[data-action=save-request]').data('url'));
     });

     //страница - список постов: добавить хештеги к посту из localStorage после нажатия на кнопку Сохранить в поп-ап окне
     $(document).on('click', '#modal-add-hashtag [data-action=save-request]', function(event) {
         //TODO
         let hashtags = localStorage.getItem('hashtags2');
         console.log('hashtags' + hashtags);

         let actionurl = $(this).data('url');
         let postId = $(this).data('post-id');

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         $.ajax({
             url: actionurl,
             type: 'PUT',
             dataType: 'application/json',
             data: {'hashtags': hashtags},
             complete: function(response) {
                 console.log("ответ");
                 console.log(response.responseText);
                 let result = JSON.parse(response.responseText);
                 console.log('response' + result);
                 console.log('message' + result.message);

                 if (result.status === true) {
                     toastr.success(result.message);
                     //showAlert(result.message, 'alert-info', 'fa-check');
                     let postElement = $('.grid-item[data-id='+postId+']');
                     let tagsBlock = postElement.find('.tags');
                     let tagsBlockElements = tagsBlock.find('li');
                     console.log(postElement);
                     console.log(tagsBlock);
                     console.log(tagsBlockElements);

                     // let selectedHashtagsElements = $('#modal-add-hashtag #b-selected-tags-2 .tag');
                     // let selectedHashtags = [];
                     // tagsBlockElements.each(function(i,elem) {
                     //     console.log('data-id = ' + elem.getAttribute('data-id'));
                     //     selectedHashtags.push(elem.getAttribute('data-id'));
                     // });

                     let postHashtagsElement = postElement.find('.tags');
                     postHashtagsElement.empty();

                     const savedHashtags = JSON.parse(hashtags);
                     console.log('savedHashtags = ' + savedHashtags);

                     $grid.masonry('destroy'); // destroy

                     for (key in savedHashtags) {
                         console.log(key);
                         let postHashtag = '<li data-id="' + key + '" data-title="' + savedHashtags[key] + '"><a rel="tag" href="#">#' + savedHashtags[key] + '</a></li>';
                         postHashtagsElement.append(postHashtag);
                     }

                     $grid.masonry( masonryOptions );

                 } else {
                     toastr.error(result.message);
                     //showAlert(result.message, 'alert-danger', 'fa-ban');
                 }

                 $('#modal-add-hashtag').modal('hide');
                 if (localStorage.getItem('hashtags2') !== undefined && localStorage.getItem('hashtags2') !== null) {
                     localStorage.removeItem('hashtags2');
                 }
                 $('#modal-add-hashtag #b-selected-tags-2').empty();

             },
         });
     });

     // Выбрать тип данных, которые будем парсить (dropdown-menu)
     let bParseInfoDropdownMenu = $(".card-create .b-parse-info .dropdown-menu");
     if (bParseInfoDropdownMenu !== undefined && bParseInfoDropdownMenu !== null) {

         var liItems = bParseInfoDropdownMenu.find('li[data-type]');
         console.log(liItems);

         var changeTypeButton = $('#change-type-for-parsing');
         //var liItems = $('.card-create .b-parse-info .dropdown-item[data-type]');

         liItems.on('click', function(event) {
             event.preventDefault();
             var liText = $(this).text().trim();
             var liDataType = $(this).data('type');
             changeTypeButton.text(liText);
             changeTypeButton.data('type', liDataType);
             console.log(changeTypeButton.data('type'));

             var parsingTypeBlocks = $('.b-parsing-type-blocks > div');
             console.log('parsingTypeBlocks = ' + parsingTypeBlocks);

             parsingTypeBlocks.hide();
             let tabType = liDataType.substring(liDataType.indexOf("-") + 1).replace("-", "")
             console.log(tabType);
             $('#tab-' + tabType).show();
         });
     }


     $(document).on('click', '#get-parsed-post-info', function(event) {
         console.log('=========' + 'TTT' + '==========');

         //значение input-а
         var linkForParsing = $('#link-for-parsing').val();
         console.log('=========' + linkForParsing + '==========');

         //url для запроса
         let actionurl = $('#get-parsed-post-info').data('action')

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         $.ajax({
             url: actionurl,
             type: 'POST',
             dataType: 'application/json',
             data: {'link': linkForParsing},
             complete: function(response) {
                 console.log("ответ");
                 console.log(response.responseText);
                 let result = JSON.parse(response.responseText);
                 console.log('response' + result);

                 // let uploadedImages = result['images'];
                 // console.log('uploadedImages' + uploadedImages);

                 //TODO
                 if (result.hasOwnProperty('images')) {
                     let uploadedImages = result['images'];

                     if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
                         let savedImages = localStorage.getItem('images');
                         console.log('savedImages' + savedImages);
                         //localStorage images
                         let lsImages = JSON.parse(savedImages);
                         console.log('lsImages' + lsImages);

                         for (let key in uploadedImages) {
                             if (lsImages.hasOwnProperty(key) === false) {
                                 let image = uploadedImages[key];
                                 console.log('image' + image);

                                 //data-extension="' + image.image_extension + '"
                                 let img = '<div class="image" data-name="' + image.image_name + '"  data-extension="' + image.image_extension + '">';

                                 if (image.s_image_name !== undefined) {
                                     img += '<span style="background-image: url(/storage/' + image.s_image_path + ')"></span>' +
                                         '<i class="js-delete-image temp fas fa-times-circle" data-action="/admin_panel/delete-download-file"></i>' +
                                         '</div>';
                                 } else {
                                     img += '<span style="background-image: url(/storage/temp_directory/' + image.image_name + '.' + image.image_extension + ')"></span>' +
                                         '<i class="js-delete-image temp fas fa-times-circle" data-action="/admin_panel/delete-download-file"></i>' +
                                         '</div>';
                                 }

                                 // Добавить в контейнер
                                 $('.js-upload-image-section .images').append(img);

                                 $('.upload-image-section .title').hide();
                             }
                         }

                         let images = Object.assign({}, lsImages, imageObj);
                         console.log('images' + images);

                         localStorage.setItem('images', JSON.stringify(images));
                     } else {

                         for (let key in uploadedImages) {
                             if (uploadedImages.hasOwnProperty(key)) {
                                 let image2 = uploadedImages[key];
                                 console.log('image2' + image2);

                                 //data-extension="' + image.image_extension + '"
                                 let img = '<div class="image" data-name="' + image2.image_name + '"  data-extension="' + image2.image_extension + '">';

                                 if (image2.s_image_path !== undefined) {
                                     img += '<span style="background-image: url(/storage/' + image2.s_image_path + ')"></span>' +
                                         '<i class="js-delete-image temp fas fa-times-circle" data-action="/admin_panel/delete-download-file"></i>' +
                                         '</div>';
                                 } else {
                                     img += '<span style="background-image: url(/storage/temp_directory/' + image2.image_name + '.' + image2.image_extension + ')"></span>' +
                                         '<i class="js-delete-image temp fas fa-times-circle" data-action="/admin_panel/delete-download-file"></i>' +
                                         '</div>';
                                 }

                                 // Добавить в контейнер
                                 $('.js-upload-image-section .images').append(img);

                                 $('.upload-image-section .title').hide();
                             }
                         }

                         localStorage.setItem('images', JSON.stringify(uploadedImages));
                     }

                     console.log('TEST-------' + localStorage.getItem('images'));
                 }

                 if (result.hasOwnProperty('title')) {
                     console.log('title' + result.title);
                     $('#title').val(result.title);
                 }

                 if (result.hasOwnProperty('alias')) {
                     console.log('alias' + result.alias);
                     $('#alias').val(result.alias);
                 }

                 if (result.hasOwnProperty('film_genres')) {
                     let filmGenres = '';
                     if (Array.isArray(result.film_genres)) {
                         for (var i = 0; i < result.film_genres.length; i++) {
                             filmGenres += result.film_genres[i];
                             if (i < result.film_genres.length - 1) {
                                 filmGenres += ', ';
                             }
                         }
                     } else {
                         filmGenres = result.film_genres;
                     }

                     $('#film-genres').val(filmGenres);
                 }

                 if (result.hasOwnProperty('imdb_rating')) {
                     console.log('imdb_rating' + result.imdb_rating);
                     $('#imdb-rating').val(result.imdb_rating);
                 }

                 if (result.hasOwnProperty('film_year')) {
                     console.log('film_year' + result.film_year);
                     $('#film-year').val(result.film_year);
                 }

                 if (result.hasOwnProperty('film_countries')) {
                     let filmCountries = '';
                     if (Array.isArray(result.film_countries)) {
                         for (var i = 0; i < result.film_countries.length; i++) {
                             filmCountries += result.film_countries[i];
                             if (i < result.film_countries.length - 1) {
                                 filmCountries += ', ';
                             }
                         }
                     } else {
                         filmCountries = result.film_countries;
                     }

                     console.log('film-country' + filmCountries);
                     $('#film-country').val(filmCountries);
                 }

                 if (result.hasOwnProperty('film_directors')) {
                     let filmDirectors = '';
                     if (Array.isArray(result.film_directors)) {
                         for (var i = 0; i < result.film_directors.length; i++) {
                             filmDirectors += result.film_directors[i];
                             if (i < result.film_directors.length - 1) {
                                 filmDirectors += ', ';
                             }
                         }
                     } else {
                         filmDirectors = result.film_directors;
                     }

                     console.log('film-director' + filmDirectors);
                     $('#film-director').val(filmDirectors);
                 }

                 if (result.hasOwnProperty('film_actors')) {
                     let filmActors = '';
                     if (Array.isArray(result.film_actors)) {
                         for (var i = 0; i < result.film_actors.length; i++) {
                             filmActors += result.film_actors[i];
                             if (i < result.film_actors.length - 1) {
                                 filmActors += ', ';
                             }
                         }
                     } else {
                         filmActors = result.film_actors;
                     }

                     console.log('film_actors' + filmActors);
                     $('#film-actors').val(filmActors);
                 }

                 if (result.hasOwnProperty('film_duration')) {
                     console.log('film_duration' + result.film_duration);
                     $('#film-duration').val(result.film_duration);
                 }

                 if (result.hasOwnProperty('mpaa_rating')) {
                     console.log('mpaa_rating' + result.mpaa_rating);
                     $('#film-rating-mpaa').val(result.mpaa_rating);
                 }

                 if (result.hasOwnProperty('film_description')) {
                     console.log('film_description' + result.film_description);
                     $('#film-description').val(result.film_description);
                 }

             },
         });
     });

     //Generate post TODO
     $(document).on('click', '#generate-post', function(event) {
         console.log('=========' + 'generate-post' + '==========');
         console.log($('#film-rating-mpaa').val());

         //TODO - в зависимости от типа (film, text, recept)
         let generateText = '<div style="width:100%; display:block; font-family: Roboto,sans-serif">';
         generateText += '<h2 style="font-size: 16px;font-style: italic;color: #757575; line-height: 24px;">';
         generateText += 'The Lord of the Rings: The Fellowship of the Ring';
         generateText += '</h2>';


         if ($('#film-genres').val() !== null && $('#film-genres').val() !== undefined) {
             generateText += '<p style="display: block; clear:both; line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;">Жанр: </span>';
             generateText += '<span>';
             generateText += $('#film-genres').val();
             generateText += '</span>';
             generateText += '</p>';
         }

         if ($('#imdb-rating').val() !== null && $('#imdb-rating').val() !== undefined) {
             generateText += '<p style="display: block; clear:both; display:flex;align-items: center; line-height: 28px; margin:0;">';
             generateText += '<span style="font-weight: bold; padding: 0 5px 0 0;">Рейтинг IMDb:</span>\n' +
                 '        <svg xmlns="http://www.w3.org/2000/svg" width="37" height="16" viewBox="0 0 37 16" fill="none">\n' +
                 '        <path d="M4.02172 0H32.9783C35.1994 0 37 1.79085 37 3.99998V12C37 14.2091 35.1994 16 32.9783 16H4.02172C1.80059 16 0 14.2091 0 12V3.99998C0 1.79085 1.80059 0 4.02172 0Z" fill="#FFC107"></path>\n' +
                 '        <path fill-rule="evenodd" clip-rule="evenodd" d="M7.23943 12.8002C6.79521 12.8002 6.43506 12.442 6.43506 12.0001V4.00022C6.43506 3.55839 6.79521 3.2002 7.23943 3.2002C7.68366 3.2002 8.04381 3.55839 8.04381 4.00022V12.0002C8.04381 12.442 7.68366 12.8002 7.23943 12.8002ZM16.891 12.8002C16.4468 12.8002 16.0866 12.442 16.0866 12.0002V4.80017H15.9418L14.4619 12.1602C14.3735 12.5932 13.9489 12.8729 13.5136 12.785C13.197 12.7211 12.9497 12.4751 12.8853 12.1602L11.4053 4.80025H11.2605V12.0002C11.2605 12.4421 10.9004 12.8003 10.4562 12.8003C10.0119 12.8003 9.65186 12.4421 9.65186 12.0002V4.00022C9.65186 3.5584 10.012 3.2002 10.4562 3.2002H12.0649C12.4472 3.20012 12.7767 3.46765 12.8531 3.84017L13.6736 7.92017L14.494 3.84017C14.5705 3.46765 14.9 3.20012 15.2822 3.2002H16.891C17.3352 3.2002 17.6954 3.5584 17.6954 4.00022V12.0002C17.6954 12.4421 17.3352 12.8002 16.891 12.8002ZM20.1095 12.8002H21.7182C23.0509 12.8002 24.1312 11.7256 24.1312 10.4002V5.60019C24.1312 4.27472 23.0509 3.2002 21.7182 3.2002H20.1095C19.6653 3.2002 19.3052 3.55839 19.3052 4.00022V12.0001C19.3052 12.442 19.6653 12.8002 20.1095 12.8002ZM21.7182 11.2002H20.9138V4.80016H21.7182C22.1624 4.80016 22.5226 5.15836 22.5226 5.60019V10.4002C22.5226 10.842 22.1624 11.2002 21.7182 11.2002ZM26.5436 12.8002C26.0994 12.8002 25.7393 12.442 25.7393 12.0001V10.3998V7.19979V4.00022C25.7393 3.55839 26.0994 3.2002 26.5436 3.2002C26.9879 3.2002 27.348 3.55839 27.348 4.00022V4.93635C27.5995 4.84792 27.8702 4.7998 28.1523 4.7998C29.485 4.7998 30.5654 5.87432 30.5654 7.19979V10.3998C30.5654 11.7253 29.485 12.7998 28.1523 12.7998C27.7798 12.7998 27.4271 12.7159 27.1122 12.566C26.9666 12.7107 26.7656 12.8002 26.5436 12.8002ZM27.348 10.4126C27.3548 10.8485 27.7123 11.1999 28.1523 11.1999C28.5965 11.1999 28.9567 10.8416 28.9567 10.3998V7.19979C28.9567 6.75804 28.5965 6.39985 28.1523 6.39985C27.7123 6.39985 27.3548 6.75122 27.348 7.18718V10.4126Z" fill="black"></path>\n' +
                 '        &nbsp;</svg>';
             generateText += '<span style="padding: 0 0 0 4px;">';
             generateText += $('#imdb-rating').val();
             generateText += '</span>';
             generateText += '</p>';
         }

         if ($('#my-assessment').val() !== null && $('#my-assessment').val() !== undefined) {
             let myAssessment = $('#my-assessment').val();
             myAssessment = myAssessment.replace(/value/, "");
             if (myAssessment === "0") {
                 myAssessment = "-";
             }

             generateText += '<div class="my-assessment" style="display:flex;align-items:center;">';
             generateText += '<p style="margin: 0 10px 0 0;">\n' +
                 '            <span class="title" style="font-weight: bold;padding: 0 4px 0 0;">Моя оценка: </span>';
             generateText += '<span style="height:20px;width:25px;border-right:1px solid rgba(120, 119, 119, .5);padding: 0 15px 0 0;">';
             generateText += myAssessment;
             generateText += '</span>';
             generateText += '</p>';
             generateText += '<div style="display:flex;flex-flow: column wrap;align-items:center;">\n' +
                 '            <p class="wrapper_block_rating" style="display:inline-block;float:left;width:auto;margin: 0 0 5px;color: #b8b8b8;font: 500 13px roboto, sans-serif;">Оценить фильм \n' +
                 '            </p>\n' +
                 '            <p class="rate-movie" style="display: flex;font: 700 18px/30px roboto, sans-serif;margin:0;">\n' +
                 '                <span data-value="1" style="color:#ff3300;cursor:pointer;display:block;text-align:center;width:30px;">1</span>\n' +
                 '                <span data-value="2" style="color:#ff3300;cursor:pointer;display:block;text-align:center;width:30px;">2</span>\n' +
                 '                <span data-value="3" style="color:#ff3300;cursor:pointer;display:block;text-align:center;width:30px;">3</span>';
             generateText += '<span data-value="4" style="color:#ff3300;cursor:pointer;display:block;text-align:center;width:30px;">4</span>\n' +
                 '                <span data-value="5" style="color:#ff9900;cursor:pointer;display:block;text-align:center;width:30px;">5</span>\n' +
                 '                <span data-value="6" style="color:#ff9900;cursor:pointer;display:block;text-align:center;width:30px;">6</span>\n' +
                 '                <span data-value="7" style="color:#ff9900;cursor:pointer;display:block;text-align:center;width:30px;">7</span>\n' +
                 '                <span data-value="8" style="color:#00b400;cursor:pointer;display:block;text-align:center;width:30px;">8</span>\n' +
                 '                <span data-value="9" style="color:#00b400;cursor:pointer;display:block;text-align:center;width:30px;">9</span>\n' +
                 '                <span data-value="10" style="color:#00b400;cursor:pointer;display:block;text-align:center;width:30px;">10</span>\n' +
                 '            </p>\n' +
                 '        </div>';
             generateText += '</div>';
         }

         if ($('#film-year').val() !== null && $('#film-year').val() !== undefined) {
             generateText += '<p style="display: block; clear:both;line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;">Год: </span>';
             generateText += $('#film-year').val();
             generateText += '</p>';
         }

         if ($('#film-country').val() !== null && $('#film-country').val() !== undefined) {
             generateText += '<p style="display: block; clear:both; line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;">Страна: </span>';
             generateText += $('#film-country').val();
             generateText += '</p>';
         }

         if ($('#film-director').val() !== null && $('#film-director').val() !== undefined) {
             generateText += '<p style="display: block; clear:both;line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;">Режиссер: </span>';
             generateText += $('#film-director').val();
             generateText += '</p>';
         }

         if ($('#film-actors').val() !== null && $('#film-actors').val() !== undefined) {
             generateText += '<p style="display: block; clear:both; line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;">Актёры: </span>';
             generateText += $('#film-actors').val();
             generateText += '</p>';
         }

         if ($('#film-duration').val() !== null && $('#film-duration').val() !== undefined) {
             generateText += '<p style="clear:both; display:flex; align-items: center;line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;padding: 0 5px 0 0;">Длительность: </span>\n' +
                 '    <svg class="film__duration--clock" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">\n' +
                 '        <path fill-rule="evenodd" clip-rule="evenodd" d="M0.666668 9.19735C0.666668 4.60235 4.405 0.864014 9 0.864014C13.595 0.864014 17.3333 4.60235 17.3333 9.19735C17.3333 13.7923 13.595 17.5307 9 17.5307C4.405 17.5307 0.666668 13.7923 0.666668 9.19735ZM9 15.864C5.32417 15.864 2.33333 12.8732 2.33333 9.19735C2.33333 5.52151 5.32417 2.53068 9 2.53068C12.6758 2.53068 15.6667 5.52151 15.6667 9.19735C15.6667 12.8732 12.6758 15.864 9 15.864ZM12.3333 8.36401H9.83333V5.86401C9.83333 5.40318 9.46 5.03068 9 5.03068C8.54 5.03068 8.16667 5.40318 8.16667 5.86401V9.19735C8.16667 9.65818 8.54 10.0307 9 10.0307H12.3333C12.7942 10.0307 13.1667 9.65818 13.1667 9.19735C13.1667 8.73651 12.7942 8.36401 12.3333 8.36401Z" fill="#20BEC6"></path>\n' +
                 '    &nbsp;</svg>';
             generateText += '<span style="padding: 0 0 0 3px;">';
             generateText += $('#film-duration').val();
             generateText += '</span>';
             generateText += '</p>';
         }

         if ($('#film-rating-mpaa').val() !== null && $('#film-rating-mpaa').val() !== undefined) {
             generateText += '<p style="display:flex; align-items: center;clear:both;line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;padding: 0 5px 0 0;">Pейтинг MPAA: </span>\n' +
                 '    <svg width="15" height="15" viewBox="0 0 20 18" fill="grey" xmlns="http://www.w3.org/2000/svg">';
             generateText += '<path fill-rule="evenodd" clip-rule="evenodd" d="M1.29275 0.292599C1.68375 -0.0974008 2.31675 -0.0974008 2.70675 0.292599L18.7067 16.2926C19.0977 16.6836 19.0977 17.3166 18.7067 17.7066C18.5117 17.9026 18.2558 17.9996 17.9998 17.9996C17.7437 17.9996 17.4888 17.9026 17.2927 17.7066L11.6628 12.0766C11.1548 12.3526 10.5898 12.4996 9.99975 12.4996C8.07075 12.4996 6.49975 10.9296 6.49975 8.9996C6.49975 8.4106 6.64675 7.8456 6.92375 7.3376L1.29275 1.7066C0.90275 1.3166 0.90275 0.683599 1.29275 0.292599ZM16.9548 13.1266C18.4767 11.7386 19.4527 10.2206 19.8678 9.4976C20.0438 9.1896 20.0438 8.8106 19.8678 8.5026C19.2297 7.3906 15.7048 1.8166 9.72975 2.0036C8.54475 2.0336 7.47375 2.2896 6.50175 2.6736L8.08175 4.2536C8.61775 4.1116 9.18075 4.0176 9.78075 4.0026C14.0717 3.8936 16.8948 7.5856 17.8267 9.0046C17.3708 9.7186 16.6038 10.7646 15.5437 11.7156L16.9548 13.1266ZM13.4978 15.3266L11.9178 13.7466C11.3828 13.8886 10.8198 13.9826 10.2198 13.9976C5.91475 14.0976 3.10475 10.4146 2.17275 8.9956C2.62975 8.2816 3.39575 7.2356 4.45575 6.2846L3.04475 4.8726C1.52275 6.2616 0.54675 7.7796 0.13275 8.5026C-0.04425 8.8106 -0.04425 9.1896 0.13275 9.4976C0.76175 10.5946 4.16175 15.9996 10.0247 15.9996C10.1067 15.9996 10.1888 15.9986 10.2708 15.9966C11.4548 15.9666 12.5268 15.7106 13.4978 15.3266ZM9.99975 10.4996C9.17275 10.4996 8.49975 9.8276 8.49975 8.9996C8.49975 8.98667 8.50269 8.97427 8.50567 8.96172C8.50845 8.95001 8.51127 8.93815 8.51175 8.9256L10.0747 10.4886C10.0624 10.4891 10.0507 10.4916 10.0391 10.4941L10.0391 10.4941L10.039 10.4941C10.0262 10.4968 10.0134 10.4996 9.99975 10.4996Z"></path>\n' +
                 '        &nbsp;</svg>';
             generateText += '<span style="padding: 0 0 0 3px;">';
             generateText += $('#film-rating-mpaa').val();
             generateText += '</span>';
             generateText += '</p>';
         }

         if ($('#film-description').val() !== null && $('#film-description').val() !== undefined) {
             generateText += '<p style="display: block; clear:both; line-height: 28px; margin: 0 0 10px;">';
             generateText += '<span style="font-weight: bold;">Описание: </span>';
             generateText += $('#film-description').val();
             generateText += '</p>';
         }

         generateText += '</div>';

         console.log(generateText);

         var editor = tinymce.get('editor');
        // Проверяем, что редактор найден
         if (editor) {
             // Устанавливаем новый текст в редактор
             var newText = 'Новый текст';
             editor.setContent(generateText);
         }
     });

     //считать текст с изображения
     $(document).on('click', '.js-get-text-from-image', function(event) {
         //url для запроса
         let actionurl = $('.js-get-text-from-image').data('action');
         let imagePath = '';

         if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
             let savedImages = localStorage.getItem('images');
             console.log('savedImages' + savedImages);
             //localStorage images
             let lsImages = JSON.parse(savedImages);
             console.log('lsImages' + lsImages);

             for (let key in lsImages) {
                 console.log(lsImages[key]);
                 let image = lsImages[key];

                 imagePath = '/storage/temp_directory/' + image.image_name + '.' + image.image_extension;
             }
         }

         console.log('imagePath = ' + imagePath);

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         $.ajax({
             url: actionurl,
             type: 'POST',
             dataType: 'application/json',
             data: {'imagePath': imagePath},
             complete: function(response) {
                 console.log("ответ");
                 console.log(response.responseText);

                 let result = JSON.parse(response.responseText);
                 console.log('response' + result);

                 if (result.hasOwnProperty('title')) {
                     console.log('title' + result.title);
                     $('#title').val(result.title);
                 }

             },
         });
     });

     //скопировать текст поста (страница - список постов)
     $(document).on('click', '#posts-index [data-action=copy-text]', function(event) {
         //let gridItem = $( event.target ).closest('.grid-item'); //
         let gridItem = this.closest('.grid-item'); //
         console.log(gridItem);
         let textElement = gridItem.querySelector('.b-card__content .b-card__content__text');
         //let textElement = gridItem.find('.b-card__content .b-card__content__text');
         console.log(textElement);
         let text = textElement.innerText;
         console.log(text);

         if (window.isSecureContext && navigator.clipboard) {
             //This feature is available only in secure contexts (HTTPS), in some or all supporting browsers.
             navigator.clipboard.writeText(text);
         } else {
             unsecuredCopyToClipboard(textElement, event);
         }

         // let gridItem = this.closest('.grid-item'); //
         // console.log(gridItem);
         // let textElement = gridItem.querySelector('.b-card__content .b-card__content__text');
         // console.log(textElement);
         // // let text = textElement.textContent;
         // // console.log(text);
         //
         // selectText(textElement);
         // document.execCommand("copy");
         // document.body.removeChild(copyTextarea);

     });

     function unsecuredCopyToClipboard(textElement, event) {

         console.log('unsecuredCopyToClipboard');

         let cursorPosition = getPosition(event);
         console.log(cursorPosition);

         selectText(textElement);
         document.execCommand("copy");

         unselectText();
     }

     //выделить текст элемента
     function selectText(textElement) {
         var doc = document;
         var element = textElement;
         console.log(this, element);
         if (doc.body.createTextRange) {
             var range = document.body.createTextRange();
             range.moveToElementText(element);
             range.select();
         } else if (window.getSelection) {
             var selection = window.getSelection();
             var range = document.createRange();
             range.selectNodeContents(element);
             selection.removeAllRanges();
             selection.addRange(range);
         }

         // if (txt = window.getSelection) { // Не IE, используем метод getSelection
         //     txt = window.getSelection().toString();
         // } else { // IE, используем объект selection
         //     txt = document.selection.createRange().text;
         // }
     }

     //снять выделение с текста
     function unselectText() {
         let selection = window.getSelection();
         selection.removeAllRanges();
     }

     //получить координаты курсора мыши
     function getPosition(e){
         var x = y = 0;

         if (!e) {
             var e = window.event;
         }

         if (e.pageX || e.pageY){
             x = e.pageX;
             y = e.pageY;
         } else if (e.clientX || e.clientY){
             x = e.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
             y = e.clientY + document.body.scrollTop + document.documentElement.scrollTop;
         }

         return {x: x, y: y}
     }

     // метод для транслитерации символов
     function transliter(str) {

         console.log('str ' + str);

         str = str.toLowerCase(); // все в нижний регистр
         var cyr2latChars = new Array(
             ['а', 'a'], ['б', 'b'], ['в', 'v'], ['г', 'g'],
             ['д', 'd'],  ['е', 'e'], ['ё', 'yo'], ['ж', 'zh'], ['з', 'z'],
             ['и', 'i'], ['й', 'y'], ['к', 'k'], ['л', 'l'],
             ['м', 'm'],  ['н', 'n'], ['о', 'o'], ['п', 'p'],  ['р', 'r'],
             ['с', 's'], ['т', 't'], ['у', 'u'], ['ф', 'f'],
             ['х', 'h'],  ['ц', 'c'], ['ч', 'ch'],['ш', 'sh'], ['щ', 'shch'],
             ['ъ', ''],  ['ы', 'y'], ['ь', ''],  ['э', 'e'], ['ю', 'yu'], ['я', 'ya'],

             ['А', 'A'], ['Б', 'B'],  ['В', 'V'], ['Г', 'G'],
             ['Д', 'D'], ['Е', 'E'], ['Ё', 'YO'],  ['Ж', 'ZH'], ['З', 'Z'],
             ['И', 'I'], ['Й', 'Y'],  ['К', 'K'], ['Л', 'L'],
             ['М', 'M'], ['Н', 'N'], ['О', 'O'],  ['П', 'P'],  ['Р', 'R'],
             ['С', 'S'], ['Т', 'T'],  ['У', 'U'], ['Ф', 'F'],
             ['Х', 'H'], ['Ц', 'C'], ['Ч', 'CH'], ['Ш', 'SH'], ['Щ', 'SHCH'],
             ['Ъ', ''],  ['Ы', 'Y'],
             ['Ь', ''],
             ['Э', 'E'],
             ['Ю', 'YU'],
             ['Я', 'YA'],

             ['a', 'a'], ['b', 'b'], ['c', 'c'], ['d', 'd'], ['e', 'e'],
             ['f', 'f'], ['g', 'g'], ['h', 'h'], ['i', 'i'], ['j', 'j'],
             ['k', 'k'], ['l', 'l'], ['m', 'm'], ['n', 'n'], ['o', 'o'],
             ['p', 'p'], ['q', 'q'], ['r', 'r'], ['s', 's'], ['t', 't'],
             ['u', 'u'], ['v', 'v'], ['w', 'w'], ['x', 'x'], ['y', 'y'],
             ['z', 'z'],

             ['A', 'A'], ['B', 'B'], ['C', 'C'], ['D', 'D'],['E', 'E'],
             ['F', 'F'],['G', 'G'],['H', 'H'],['I', 'I'],['J', 'J'],['K', 'K'],
             ['L', 'L'], ['M', 'M'], ['N', 'N'], ['O', 'O'],['P', 'P'],
             ['Q', 'Q'],['R', 'R'],['S', 'S'],['T', 'T'],['U', 'U'],['V', 'V'],
             ['W', 'W'], ['X', 'X'], ['Y', 'Y'], ['Z', 'Z'],

             [' ', '_'],['0', '0'],['1', '1'],['2', '2'],['3', '3'],
             ['4', '4'],['5', '5'],['6', '6'],['7', '7'],['8', '8'],['9', '9'],
             ['-', '-']

         );

         var newStr = new String();

         for (var i = 0; i < str.length; i++) {

             ch = str.charAt(i);
             var newCh = '';

             for (var j = 0; j < cyr2latChars.length; j++) {
                 if (ch == cyr2latChars[j][0]) {
                     newCh = cyr2latChars[j][1];

                 }
             }
             // Если найдено совпадение, то добавляется соответствие, если нет - пустая строка
             newStr += newCh;

         }
         // Удаляем повторяющие знаки - Именно на них заменяются пробелы.
         // Так же удаляем символы перевода строки, но это наверное уже лишнее
         return newStr.replace(/[_]{2,}/gim, '_').replace(/\n/gim, '');

         // const ru = {
         //         'а': 'a', 'б': 'b', 'в': 'v', 'г': 'g', 'д': 'd',
         //         'е': 'e', 'ё': 'e', 'ж': 'j', 'з': 'z', 'и': 'i',
         //         'к': 'k', 'л': 'l', 'м': 'm', 'н': 'n', 'о': 'o',
         //         'п': 'p', 'р': 'r', 'с': 's', 'т': 't', 'у': 'u',
         //         'ф': 'f', 'х': 'h', 'ц': 'c', 'ч': 'ch', 'ш': 'sh',
         //         'щ': 'shch', 'ы': 'y', 'э': 'e', 'ю': 'u', 'я': 'ya', ' ': '-'
         //     },
         //     n_str = [],
         //     len = str.length;
         //
         //
         // str = str.replace(/[ъь]+/g, '').replace(/й/g, 'i');
         //
         // for (let i = 0; i < length; ++i) {
         //     n_str.push(
         //         ru[str[i]]
         //         || ru[str[i].toLowerCase()] === undefined && str[i]
         //         || ru[str[i].toLowerCase()].replace(/^(.)/, function (match) {
         //             return match.toUpperCase()
         //         })
         //     );
         // }
         //
         // return n_str.join('');
     }

     //функция isEmpty(obj), которая возвращает true, если у объекта нет свойств, иначе false
     function isEmptyObject(obj) {
         for (let key in obj) {
             // если тело цикла начнет выполняться - значит в объекте есть свойства
             return false;
         }
         return true;
     }

     // при наведении
     // $(document).on({
     //     mouseenter: function () {
     //         console.log('fff');
     //         var imgurl = $(this).data("image");
     //         $(this).css("background-image", "url(" + imgurl + ")")
     //     },
     //     mouseleave: function () {
     //         $(this).css("background-image", "");
     //     }
     // }, ".b-card__content__image");

     // ===== Связанные хештеги (страницы /admin_panel/hashtags/{id}/edit и .../create) =====
     // добавление/удаление применяется сразу через AJAX, без отдельной кнопки "Сохранить".
     // На странице создания хештега в момент загрузки виджет ещё не привязан ни к какому
     // хештегу (у него нет id, пока хештег не создан) — поэтому все атрибуты (data-hashtag-id,
     // data-search-action, data-attach-action) читаются заново при каждом действии, а не
     // один раз при инициализации: как только hashtags-create заполнит их после создания
     // хештега, виджет заработает без какой-либо повторной инициализации.
     (function () {
         const field = document.querySelector('.js-hashtag-related-field');
         if (!field) {
             return;
         }

         const input = document.getElementById('related-hashtags-input');
         const container = document.getElementById('related-hashtags-container');
         const dropdown = document.getElementById('related-hashtags-dropdown');

         function hashtagId() {
             return field.getAttribute('data-hashtag-id');
         }

         function searchUrl() {
             return field.getAttribute('data-search-action');
         }

         function attachUrl() {
             return field.getAttribute('data-attach-action');
         }

         function csrfToken() {
             return $('meta[name="csrf-token"]').attr('content');
         }

         function getSelectedHashtags() {
             let selected = {};
             container.querySelectorAll('.b-tags__item').forEach(function (chip) {
                 selected[chip.getAttribute('data-id')] = chip.getAttribute('data-name');
             });
             // не предлагать в поиске сам этот хештег
             selected[hashtagId()] = '';
             return selected;
         }

         function bindRemove(chip, relatedHashtagId) {
             let removeBtn = chip.querySelector('.close');
             removeBtn.addEventListener('click', function () {
                 $.ajax({
                     type: 'delete',
                     url: attachUrl() + '/' + relatedHashtagId,
                     headers: {
                         'X-CSRF-TOKEN': csrfToken()
                     },
                     success: function () {
                         chip.remove();
                         toastr.success('Связь удалена');
                     },
                     error: function () {
                         toastr.error('Не удалось удалить связь');
                     }
                 });
             });
         }

         function appendChip(relatedHashtagId, relatedHashtagTitle) {
             let chip = document.createElement('div');
             chip.classList.add('b-tags__item');
             chip.setAttribute('data-id', relatedHashtagId);
             chip.setAttribute('data-name', relatedHashtagTitle);
             chip.textContent = relatedHashtagTitle;

             let removeBtn = document.createElement('span');
             removeBtn.classList.add('close');
             removeBtn.innerHTML = '&times;';
             chip.appendChild(removeBtn);

             container.appendChild(chip);
             bindRemove(chip, relatedHashtagId);
         }

         // навешиваем удаление на уже отрендеренные сервером чипы
         container.querySelectorAll('.b-tags__item').forEach(function (chip) {
             bindRemove(chip, chip.getAttribute('data-id'));
         });

         let searchTimer = null;

         input.addEventListener('input', function () {
             const query = input.value.trim();

             clearTimeout(searchTimer);

             if (!query.length) {
                 dropdown.innerHTML = '';
                 dropdown.style.display = 'none';
                 return;
             }

             searchTimer = setTimeout(function () {
                 $.ajax({
                     type: 'post',
                     url: searchUrl(),
                     headers: {
                         'X-CSRF-TOKEN': csrfToken()
                     },
                     data: {
                         search: query,
                         hashtags: getSelectedHashtags(),
                     },
                     success: function (response) {
                         dropdown.innerHTML = '';

                         let items = (response && response.hashtags) ? response.hashtags : [];

                         if (!items.length) {
                             dropdown.style.display = 'none';
                             return;
                         }

                         dropdown.style.display = 'block';

                         items.forEach(function (item) {
                             let li = document.createElement('li');
                             li.classList.add('b-tags__dropdown-item');
                             li.textContent = item.title;

                             li.addEventListener('click', function () {
                                 $.ajax({
                                     type: 'post',
                                     url: attachUrl(),
                                     headers: {
                                         'X-CSRF-TOKEN': csrfToken()
                                     },
                                     data: {
                                         related_hashtag_id: item.id,
                                     },
                                     success: function (attachResponse) {
                                         if (attachResponse.status === true) {
                                             appendChip(attachResponse.hashtag.id, attachResponse.hashtag.title);
                                             toastr.success('Хештег добавлен в связанные');
                                         } else {
                                             toastr.error(attachResponse.message || 'Не удалось добавить связь');
                                         }
                                     },
                                     error: function () {
                                         toastr.error('Не удалось добавить связь');
                                     }
                                 });

                                 input.value = '';
                                 dropdown.innerHTML = '';
                                 dropdown.style.display = 'none';
                                 input.focus();
                             });

                             dropdown.appendChild(li);
                         });
                     },
                     error: function () {
                         dropdown.innerHTML = '';
                         dropdown.style.display = 'none';
                     }
                 });
             }, 250);
         });

         document.addEventListener('click', function (event) {
             if (!dropdown.contains(event.target) && event.target !== input) {
                 dropdown.style.display = 'none';
             }
         });
     })();

     // ===== Изображения поста (страница /admin_panel/posts/{id}/edit) =====
     // добавление/замена/переименование/удаление — сразу через AJAX, без общей кнопки "Сохранить"
     (function () {
         const panel = document.getElementById('post-images');
         if (!panel) {
             return;
         }

         const list = panel.querySelector('.js-post-images__list');
         const errorBox = panel.querySelector('.js-post-images__error');
         const postId = panel.getAttribute('data-post-id');
         const addUrl = panel.getAttribute('data-add-action');
         const renameUrl = panel.getAttribute('data-rename-action');
         const deleteUrl = panel.getAttribute('data-delete-action');
         const confirmReplaceUrl = panel.getAttribute('data-confirm-replace-action');
         const confirmAddUrl = panel.getAttribute('data-confirm-add-action');
         const duplicateModal = $('#modal-duplicate-image-choice');
         const confirmReplaceBtn = duplicateModal.find('.js-duplicate-choice__confirm-replace');
         const confirmAddBtn = duplicateModal.find('.js-duplicate-choice__confirm-add');
         // сюда кладём staged_image/дубликат, полученные вместе с needs_choice,
         // чтобы отправить их обратно при подтверждении
         let pendingDuplicateChoice = null;

         function csrfToken() {
             return $('meta[name="csrf-token"]').attr('content');
         }

         function showError(message) {
             errorBox.textContent = message;
             errorBox.style.display = 'block';
         }

         function clearError() {
             errorBox.style.display = 'none';
             errorBox.textContent = '';
         }

         function uploadImage(file, onSuccess) {
             let formData = new FormData();
             formData.append('image', file);

             $.ajax({
                 url: addUrl,
                 type: 'post',
                 headers: {
                     'X-CSRF-TOKEN': csrfToken()
                 },
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function (response) {
                     if (response.status === true) {
                         onSuccess(response.image);
                     } else if (response.needs_choice) {
                         showDuplicateChoice(response, onSuccess);
                     } else {
                         showError(response.message || 'Не удалось загрузить изображение');
                     }
                 },
                 error: function () {
                     showError('Не удалось загрузить изображение');
                 }
             });
         }

         // найден похожий дубликат — показываем сравнение и даём пользователю выбрать,
         // что делать (набор кнопок зависит от response.mode, см. PostController::addImage())
         function showDuplicateChoice(response, onSuccess) {
             pendingDuplicateChoice = {
                 staged_image: response.staged_image,
                 duplicate_post_id: response.duplicate.post_id,
                 duplicate_image_name: response.duplicate.image_name,
                 onSuccess: onSuccess
             };

             let isReplaceMode = response.mode === 'replace_or_cancel';
             confirmReplaceBtn.toggle(isReplaceMode);
             confirmAddBtn.toggle(!isReplaceMode);

             duplicateModal.find('.js-duplicate-choice__message').text(response.message || '');
             duplicateModal.find('.js-duplicate-choice__post-id, .js-duplicate-choice__post-id-2').text(response.duplicate.post_id);

             duplicateModal.find('.js-duplicate-choice__new-thumb').attr('src', response.new_image.thumbnail_url);
             duplicateModal.find('.js-duplicate-choice__new-size').text(response.new_image.width + '×' + response.new_image.height);

             duplicateModal.find('.js-duplicate-choice__old-thumb').attr('src', response.duplicate.thumbnail_url);
             duplicateModal.find('.js-duplicate-choice__old-size').text(response.duplicate.width + '×' + response.duplicate.height);

             duplicateModal.modal('show');
         }

         function submitDuplicateChoice(url, extraData, successMessage, failMessage) {
             if (!pendingDuplicateChoice) {
                 return;
             }

             let choice = pendingDuplicateChoice;
             pendingDuplicateChoice = null;
             duplicateModal.modal('hide');
             clearError();

             $.ajax({
                 url: url,
                 type: 'post',
                 headers: {
                     'X-CSRF-TOKEN': csrfToken()
                 },
                 data: Object.assign({staged_image: choice.staged_image}, extraData),
                 success: function (response) {
                     if (response.status === true) {
                         choice.onSuccess(response.image);
                         toastr.success(response.message || successMessage);
                     } else {
                         showError(response.message || failMessage);
                     }
                 },
                 error: function () {
                     showError(failMessage);
                 }
             });
         }

         // новое изображение лучше — переносим его сюда, старое (в другом посте) удаляем
         confirmReplaceBtn.on('click', function () {
             if (!pendingDuplicateChoice) {
                 return;
             }

             submitDuplicateChoice(confirmReplaceUrl, {
                 duplicate_post_id: pendingDuplicateChoice.duplicate_post_id,
                 duplicate_image_name: pendingDuplicateChoice.duplicate_image_name
             }, 'Изображение перенесено', 'Не удалось перенести изображение');
         });

         // это ложное совпадение (или не страшно) — добавляем новое как есть, старое не трогаем
         confirmAddBtn.on('click', function () {
             submitDuplicateChoice(confirmAddUrl, {}, 'Изображение добавлено', 'Не удалось добавить изображение');
         });

         duplicateModal.on('hidden.bs.modal', function () {
             pendingDuplicateChoice = null;
         });

         function bindItem(item) {
             const renameBtn = item.querySelector('.js-post-images__rename');
             const nameInput = item.querySelector('.js-post-images__name-input');
             const deleteBtn = item.querySelector('.js-post-images__delete');

             renameBtn.addEventListener('click', function () {
                 let oldName = item.getAttribute('data-name');
                 let newName = nameInput.value.trim();

                 if (!newName || newName === oldName) {
                     return;
                 }

                 clearError();

                 $.ajax({
                     type: 'post',
                     url: renameUrl,
                     headers: {
                         'X-CSRF-TOKEN': csrfToken()
                     },
                     data: {
                         old_name: oldName,
                         new_name: newName,
                     },
                     success: function (response) {
                         if (response.status === true) {
                             item.setAttribute('data-name', response.image.name);
                             item.setAttribute('data-path', response.image.path);
                             nameInput.value = response.image.name;
                             let thumb = item.querySelector('.b-post-images__thumb');
                             thumb.style.backgroundImage = 'url(/storage' + response.image.path + ')';
                             thumb.setAttribute('data-src', '/storage' + response.image.path);
                             toastr.success(response.message || 'Файл переименован');
                         } else {
                             showError(response.message || 'Не удалось переименовать файл');
                         }
                     },
                     error: function () {
                         showError('Не удалось переименовать файл');
                     }
                 });
             });

             deleteBtn.addEventListener('click', function () {
                 if (!confirm('Удалить это изображение?')) {
                     return;
                 }

                 clearError();

                 $.ajax({
                     type: 'post',
                     url: deleteUrl,
                     headers: {
                         'X-CSRF-TOKEN': csrfToken()
                     },
                     data: {
                         postId: postId,
                         name: item.getAttribute('data-name'),
                         path: item.getAttribute('data-path'),
                     },
                     success: function (response) {
                         if (response.status === true) {
                             item.remove();
                             toastr.success(response.message || 'Изображение удалено');
                         } else {
                             showError(response.message || response.msg || 'Не удалось удалить изображение');
                         }
                     },
                     error: function () {
                         showError('Не удалось удалить изображение');
                     }
                 });
             });
         }

         function buildItem(name, extension, path) {
             let item = document.createElement('div');
             item.className = 'b-post-images__item';
             item.setAttribute('data-name', name);
             item.setAttribute('data-extension', extension);
             item.setAttribute('data-path', path);

             item.innerHTML =
                 '<a class="b-post-images__thumb" data-fancybox="post-images" data-src="/storage' + path + '" style="background-image: url(/storage' + path + ')"></a>' +
                 '<div class="b-post-images__controls">' +
                     '<div class="b-post-images__rename">' +
                         '<input type="text" class="form-control form-control-sm js-post-images__name-input" value="' + name + '">' +
                         '<button type="button" class="btn btn-sm btn-outline-info js-post-images__rename" title="Переименовать"><i class="fas fa-check"></i></button>' +
                     '</div>' +
                     '<div class="b-post-images__buttons">' +
                         '<button type="button" class="btn btn-sm btn-outline-danger js-post-images__delete" title="Удалить"><i class="fas fa-trash"></i></button>' +
                     '</div>' +
                 '</div>';

             bindItem(item);
             return item;
         }

         // навешиваем обработчики на уже отрендеренные сервером карточки
         list.querySelectorAll('.b-post-images__item').forEach(bindItem);

         const addInput = panel.querySelector('.js-post-images__add-input');
         addInput.addEventListener('change', function () {
             if (!addInput.files.length) {
                 return;
             }

             let file = addInput.files[0];
             addInput.value = '';

             clearError();

             uploadImage(file, function (newImage) {
                 let newItem = buildItem(newImage.name, newImage.extension, newImage.path);
                 list.appendChild(newItem);
                 toastr.success('Изображение добавлено');
             });
         });
     })();

     // ===== Видео поста (страница /admin_panel/posts/{id}/edit) =====
     (function () {
         const panel = document.getElementById('post-video');
         if (!panel) {
             return;
         }

         const addUrl = panel.getAttribute('data-add-action');
         const deleteUrl = panel.getAttribute('data-delete-action');
         const player = panel.querySelector('.js-post-video__player');
         const addLabelText = panel.querySelector('.js-post-video__add-label-text');
         const addInput = panel.querySelector('.js-post-video__add-input');
         const deleteBtn = panel.querySelector('.js-post-video__delete');
         const errorBox = panel.querySelector('.js-post-video__error');

         function csrfToken() {
             return $('meta[name="csrf-token"]').attr('content');
         }

         function showError(message) {
             errorBox.textContent = message;
             errorBox.style.display = 'block';
         }

         function clearError() {
             errorBox.style.display = 'none';
             errorBox.textContent = '';
         }

         addInput.addEventListener('change', function () {
             if (!addInput.files.length) {
                 return;
             }

             let file = addInput.files[0];
             addInput.value = '';
             clearError();

             let formData = new FormData();
             formData.append('video', file);

             $.ajax({
                 url: addUrl,
                 type: 'post',
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function (response) {
                     if (response.status === true) {
                         player.innerHTML = '<video src="/storage' + response.video.path + '" controls preload="metadata"></video>';
                         addLabelText.textContent = 'Заменить видео';
                         deleteBtn.style.display = '';
                         toastr.success(response.message || 'Видео добавлено');
                     } else {
                         showError(response.message || 'Не удалось загрузить видео');
                     }
                 },
                 error: function () {
                     showError('Не удалось загрузить видео');
                 }
             });
         });

         deleteBtn.addEventListener('click', function () {
             if (!confirm('Удалить видео поста?')) {
                 return;
             }

             clearError();

             $.ajax({
                 url: deleteUrl,
                 type: 'delete',
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 success: function (response) {
                     if (response.status === true) {
                         player.innerHTML = '';
                         addLabelText.textContent = 'Добавить видео';
                         deleteBtn.style.display = 'none';
                         toastr.success(response.message || 'Видео удалено');
                     } else {
                         showError(response.message || 'Не удалось удалить видео');
                     }
                 },
                 error: function () {
                     showError('Не удалось удалить видео');
                 }
             });
         });
     })();

     // ===== Мой список подарков: общие хелперы =====
     function csrfToken() {
         return $('meta[name="csrf-token"]').attr('content');
     }

     // Пилюли выбора повода (используются и в попапе создания вишлиста, и в его настройках).
     // Возвращает {getValue} — активный slug пилюли либо текст из поля "свой повод".
     function initOccasionPicker(pickerEl, customInputEl) {
         const items = pickerEl.querySelectorAll('.js-occasion-picker__item');
         const moreBtn = pickerEl.querySelector('.js-occasion-picker__more');

         function selectItem(selected) {
             items.forEach(function (item) {
                 item.classList.toggle('b-occasion-picker__item--active', item === selected);
             });
             moreBtn.classList.remove('b-occasion-picker__item--active');
             customInputEl.style.display = 'none';
         }

         items.forEach(function (item) {
             item.addEventListener('click', function () {
                 selectItem(item);
             });
         });

         moreBtn.addEventListener('click', function () {
             items.forEach(function (item) {
                 item.classList.remove('b-occasion-picker__item--active');
             });
             moreBtn.classList.add('b-occasion-picker__item--active');
             customInputEl.style.display = 'block';
             customInputEl.focus();
         });

         return {
             getValue: function () {
                 let active = pickerEl.querySelector('.js-occasion-picker__item.b-occasion-picker__item--active');
                 if (active) {
                     return active.getAttribute('data-slug');
                 }
                 if (customInputEl.style.display !== 'none' && customInputEl.value.trim()) {
                     return customInputEl.value.trim();
                 }
                 return '';
             }
         };
     }

     // ===== Мой список подарков: список вишлистов (/admin_panel/wishlists) =====
     (function () {
         const indexPage = document.getElementById('wishlists-index');
         if (!indexPage) {
             return;
         }

         const storeUrl = indexPage.getAttribute('data-store-action');
         const createModal = $('#modal-create-wishlist');

         const occasionPicker = initOccasionPicker(
             document.querySelector('#modal-create-wishlist .js-occasion-picker'),
             document.getElementById('wishlist-create-occasion-custom')
         );

         document.querySelectorAll('.js-wishlist-create-btn').forEach(function (btn) {
             btn.addEventListener('click', function () {
                 createModal.modal('show');
             });
         });

         document.querySelector('.js-wishlist-create-confirm').addEventListener('click', function () {
             let title = document.getElementById('wishlist-create-title').value.trim();
             let occasion = occasionPicker.getValue();

             $.ajax({
                 type: 'post',
                 url: storeUrl,
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 data: {title: title, occasion: occasion},
                 success: function (response) {
                     if (response.status === true) {
                         window.location.href = response.redirect;
                     } else {
                         toastr.error(response.message || 'Не удалось создать вишлист');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось создать вишлист');
                 }
             });
         });

         // --- поиск по названию ---
         const grid = document.querySelector('.js-wishlists-grid');
         const searchInput = document.querySelector('.js-wishlists-search-input');
         const emptySearchState = document.querySelector('.js-wishlists-empty-search');
         // копия списка карточек, а не live-NodeList — при удалении вишлиста (см. ниже)
         // просто вырезаем элемент отсюда же, без повторного querySelectorAll
         const cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.js-wishlist-card')) : [];

         function applySearch() {
             if (!grid) {
                 return;
             }
             let query = (searchInput ? searchInput.value : '').trim().toLowerCase();
             let visibleCount = 0;
             cards.forEach(function (card) {
                 let match = !query || card.getAttribute('data-title').toLowerCase().indexOf(query) !== -1;
                 card.style.display = match ? '' : 'none';
                 if (match) {
                     visibleCount++;
                 }
             });
             if (emptySearchState) {
                 emptySearchState.style.display = visibleCount === 0 ? '' : 'none';
             }
             grid.style.display = visibleCount === 0 ? 'none' : '';
         }

         if (searchInput) {
             searchInput.addEventListener('input', applySearch);
         }

         // --- сортировка (просто переставляет карточки в DOM — grid не Masonry,
         // как на странице одного вишлиста, поэтому relayout не нужен) ---
         const sortWrap = document.querySelector('.js-wishlists-sort');
         if (sortWrap && grid) {
             const sortTrigger = sortWrap.querySelector('.js-wishlists-sort-trigger');
             const sortLabel = sortWrap.querySelector('.js-wishlists-sort-label');

             sortTrigger.addEventListener('click', function (event) {
                 event.stopPropagation();
                 sortWrap.classList.toggle('is-open');
             });

             sortWrap.querySelectorAll('.js-wishlists-sort-option').forEach(function (option) {
                 option.addEventListener('click', function () {
                     sortWrap.querySelectorAll('.js-wishlists-sort-option').forEach(function (o) {
                         o.classList.remove('is-active');
                     });
                     option.classList.add('is-active');
                     sortLabel.textContent = option.textContent.trim();
                     sortWrap.classList.remove('is-open');

                     let order = option.getAttribute('data-order');
                     let sorted = cards.slice().sort(function (a, b) {
                         if (order === 'new') {
                             return b.getAttribute('data-created') - a.getAttribute('data-created');
                         }
                         if (order === 'old') {
                             return a.getAttribute('data-created') - b.getAttribute('data-created');
                         }
                         let titleA = a.getAttribute('data-title').toLowerCase();
                         let titleB = b.getAttribute('data-title').toLowerCase();
                         return order === 'az' ? titleA.localeCompare(titleB, 'ru') : titleB.localeCompare(titleA, 'ru');
                     });
                     sorted.forEach(function (card) {
                         grid.appendChild(card);
                     });
                 });
             });
         }

         // --- "..." меню карточки (пока только удаление) ---
         document.querySelectorAll('.js-wishlist-card__menu').forEach(function (menu) {
             const toggle = menu.querySelector('.js-wishlist-card__menu-toggle');
             const deleteBtn = menu.querySelector('.js-wishlist-card__delete');
             const card = menu.closest('.js-wishlist-card');

             // вся карточка — это <a>, поэтому клики по кнопке/пунктам меню не должны
             // ни открывать вишлист, ни "протекать" на document (закрывающий меню)
             toggle.addEventListener('click', function (event) {
                 event.preventDefault();
                 event.stopPropagation();
                 let wasOpen = menu.classList.contains('is-open');
                 document.querySelectorAll('.js-wishlist-card__menu.is-open').forEach(function (m) {
                     m.classList.remove('is-open');
                 });
                 if (!wasOpen) {
                     menu.classList.add('is-open');
                 }
             });

             deleteBtn.addEventListener('click', function (event) {
                 event.preventDefault();
                 event.stopPropagation();
                 menu.classList.remove('is-open');

                 if (!confirm('Удалить вишлист «' + card.getAttribute('data-title') + '» вместе со всеми желаниями? Это действие нельзя отменить.')) {
                     return;
                 }

                 $.ajax({
                     type: 'delete',
                     url: card.getAttribute('data-destroy-action'),
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     success: function (response) {
                         if (response.status === true) {
                             let idx = cards.indexOf(card);
                             if (idx !== -1) {
                                 cards.splice(idx, 1);
                             }
                             card.remove();
                             toastr.success(response.message || 'Вишлист удалён');
                             applySearch();
                         } else {
                             toastr.error(response.message || 'Не удалось удалить вишлист');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось удалить вишлист');
                     }
                 });
             });
         });

         document.addEventListener('click', function (event) {
             if (!event.target.closest('.js-wishlist-card__menu')) {
                 document.querySelectorAll('.js-wishlist-card__menu.is-open').forEach(function (m) {
                     m.classList.remove('is-open');
                 });
             }
             if (sortWrap && !sortWrap.contains(event.target)) {
                 sortWrap.classList.remove('is-open');
             }
         });
         document.addEventListener('keydown', function (event) {
             if (event.key !== 'Escape') {
                 return;
             }
             document.querySelectorAll('.js-wishlist-card__menu.is-open').forEach(function (m) {
                 m.classList.remove('is-open');
             });
             if (sortWrap) {
                 sortWrap.classList.remove('is-open');
             }
         });
     })();

     // ===== Мой список подарков: страница одного вишлиста =====
     (function () {
         const page = document.getElementById('wishlist-page');
         if (!page) {
             return;
         }

         const publicUrl = page.getAttribute('data-public-url');
         const updateUrl = page.getAttribute('data-update-action');
         const destroyUrl = page.getAttribute('data-destroy-action');
         const linkPreviewUrl = page.getAttribute('data-link-preview-action');
         const itemsStoreUrl = page.getAttribute('data-items-store-action');
         const itemBaseUrl = page.getAttribute('data-item-base-url');
         const wishlistsIndexUrl = page.getAttribute('data-index-url');

         const itemsContainer = document.querySelector('.js-wishlist-items');
         const emptyHint = document.querySelector('.js-wishlist-empty-hint');
         const countLabel = document.querySelector('.js-wishlist-count');

         // --- раскладка плиток колонками разной высоты (как в примере) через Masonry ---
         // библиотека уже подключена глобально в admin_layout.blade.php (используется и для
         // сетки постов) — здесь свой контейнер (.js-wishlist-items), поэтому свой инстанс
         const $wishlistGrid = $(itemsContainer);
         const wishlistMasonryOptions = {
             itemSelector: '.b-wishlist-item',
             percentPosition: true, // ширина колонки задана в % (см. CSS), а не пикселями
             gutter: 16, // тот же отступ, что и в calc() у .b-wishlist-item в CSS
             transitionDuration: 0,
         };

         // после любого изменения набора/порядка/видимости карточек (фильтр, сортировка,
         // добавление, удаление) пересчитываем раскладку заново — так же, как это уже
         // делается для сетки постов (см. document.ready выше): дешевле и надёжнее, чем
         // вручную отслеживать, какая именно карточка появилась/исчезла/переместилась
         function relayoutWishlistGrid() {
             $wishlistGrid.masonry('destroy');
             $wishlistGrid.masonry(wishlistMasonryOptions);
         }

         // первая раскладка — только после того, как все картинки (у них разная высота)
         // реально догрузятся, иначе Masonry посчитает высоту карточек по ещё не
         // загруженным (нулевым) изображениям
         $wishlistGrid.imagesLoaded().always(function () {
             $wishlistGrid.masonry(wishlistMasonryOptions);
         });

         // страница уже рендерится сервером в порядке "новые сначала" (sortByDesc('created_at')) —
         // запоминаем этот исходный порядок на самих карточках, чтобы можно было вернуться к нему
         // после сортировки по цене; новым карточкам (появляются через appendItemCard) будет
         // назначаться order меньше любого из существующих (см. nextOrderValue ниже)
         itemsContainer.querySelectorAll('.b-wishlist-item').forEach(function (card, index) {
             card.setAttribute('data-order', String(index));
         });

         // --- поделиться: попап со ссылкой + соцсети ---
         const shareModal = $('#modal-share-wishlist');
         const shareLinkInput = document.querySelector('.js-share-link-input');
         const shareNativeBtn = document.querySelector('.js-share-link-native');

         function shareText() {
             let titleEl = document.querySelector('.js-wishlist-title');
             return 'Мой вишлист' + (titleEl && titleEl.textContent.trim() ? ': ' + titleEl.textContent.trim() : '');
         }

         function buildSocialShareUrl(network, url, text) {
             let encodedUrl = encodeURIComponent(url);
             let encodedText = encodeURIComponent(text);

             switch (network) {
                 case 'telegram':
                     return 'https://t.me/share/url?url=' + encodedUrl + '&text=' + encodedText;
                 case 'viber':
                     return 'viber://forward?text=' + encodeURIComponent(text + ' ' + url);
                 case 'whatsapp':
                     return 'https://wa.me/?text=' + encodeURIComponent(text + ' ' + url);
                 case 'pinterest':
                     return 'https://pinterest.com/pin/create/button/?url=' + encodedUrl + '&description=' + encodedText;
                 case 'facebook':
                     return 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl;
                 case 'twitter':
                     return 'https://twitter.com/intent/tweet?url=' + encodedUrl + '&text=' + encodedText;
                 default:
                     return url;
             }
         }

         function openShareModal() {
             shareLinkInput.value = publicUrl;

             document.querySelectorAll('.js-share-social').forEach(function (link) {
                 link.setAttribute('href', buildSocialShareUrl(link.getAttribute('data-network'), publicUrl, shareText()));
             });

             // нативный "поделиться" доступен не везде (обычно только на мобильных/HTTPS) —
             // если браузер его не поддерживает, просто скрываем кнопку вместо того, чтобы
             // показывать нерабочую
             shareNativeBtn.style.display = navigator.share ? '' : 'none';

             shareModal.modal('show');
         }

         document.querySelector('.js-wishlist-share').addEventListener('click', openShareModal);

         document.querySelector('.js-share-link-copy').addEventListener('click', function () {
             if (navigator.clipboard) {
                 navigator.clipboard.writeText(publicUrl).then(function () {
                     toastr.success('Ссылка скопирована');
                 }, function () {
                     shareLinkInput.select();
                 });
             } else {
                 shareLinkInput.select();
             }
         });

         shareNativeBtn.addEventListener('click', function () {
             if (navigator.share) {
                 navigator.share({title: shareText(), url: publicUrl}).catch(function () {});
             }
         });

         // --- вкладки Все/Свободные/Забронированные + панель фильтров + сортировка ---
         let activeTab = 'all'; // 'all' | 'available' | 'reserved'
         let activeSort = 'date-desc';
         let nextOrderValue = -1; // у новых карточек (появляются через JS) порядок всегда меньше 0 —
                                   // то есть "новее" любой отрендеренной сервером (см. data-order в item_cards.blade.php)

         function updateTabCounts() {
             let cards = Array.from(itemsContainer.querySelectorAll('.b-wishlist-item'));
             let total = cards.length;
             let available = cards.filter(function (c) {
                 return c.getAttribute('data-reserved') !== '1' && c.getAttribute('data-fulfilled') !== '1';
             }).length;
             let reserved = total - available;

             let allTab = document.querySelector('.js-wishlist-tab[data-tab="all"]');
             let availableTab = document.querySelector('.js-wishlist-tab[data-tab="available"]');
             let reservedTab = document.querySelector('.js-wishlist-tab[data-tab="reserved"]');
             if (allTab) {
                 allTab.textContent = 'Все (' + total + ')';
             }
             if (availableTab) {
                 availableTab.textContent = 'Свободные (' + available + ')';
             }
             if (reservedTab) {
                 reservedTab.textContent = 'Забронированные (' + reserved + ')';
             }
         }

         // значения отмеченных чекбоксов одной группы панели фильтров (пустой массив = группа
         // не ограничивает — показываем карточки с любым значением этого поля)
         function getCheckedFilterValues(selector) {
             return Array.from(document.querySelectorAll(selector + ':checked')).map(function (el) {
                 return el.value;
             });
         }

         function applyFilters() {
             let categories = getCheckedFilterValues('.js-filter-category');
             let bookingStatuses = getCheckedFilterValues('.js-filter-booking');
             let fulfillStatuses = getCheckedFilterValues('.js-filter-fulfilled');
             let priorities = getCheckedFilterValues('.js-filter-priority');
             let stores = getCheckedFilterValues('.js-filter-store');

             let cards = itemsContainer.querySelectorAll('.b-wishlist-item');
             let visibleCount = 0;

             cards.forEach(function (card) {
                 let isReservedOrFulfilled = card.getAttribute('data-reserved') === '1' || card.getAttribute('data-fulfilled') === '1';

                 let matchesTab = true;
                 if (activeTab === 'available') {
                     matchesTab = !isReservedOrFulfilled;
                 } else if (activeTab === 'reserved') {
                     matchesTab = isReservedOrFulfilled;
                 }

                 let matchesCategory = categories.length === 0 || categories.indexOf(card.getAttribute('data-category')) !== -1;
                 let matchesBooking = bookingStatuses.length === 0 || bookingStatuses.indexOf(card.getAttribute('data-reserved')) !== -1;
                 let matchesFulfilled = fulfillStatuses.length === 0 || fulfillStatuses.indexOf(card.getAttribute('data-fulfilled')) !== -1;
                 let matchesPriority = priorities.length === 0 || priorities.indexOf(card.getAttribute('data-priority') || '') !== -1;
                 let matchesStore = stores.length === 0 || stores.indexOf(card.getAttribute('data-store') || '') !== -1;

                 let visible = matchesTab && matchesCategory && matchesBooking && matchesFulfilled && matchesPriority && matchesStore;
                 card.style.display = visible ? '' : 'none';
                 if (visible) {
                     visibleCount++;
                 }
             });

             countLabel.textContent = visibleCount + ' из ' + cards.length;
             emptyHint.style.display = cards.length === 0 ? '' : 'none';
             updateTabCounts();

             // часть карточек могла скрыться/показаться — колонки нужно сложить заново
             relayoutWishlistGrid();
         }

         function applySort() {
             let cards = Array.from(itemsContainer.querySelectorAll('.b-wishlist-item'));

             cards.sort(function (a, b) {
                 if (activeSort === 'price-asc' || activeSort === 'price-desc') {
                     let priceA = parseFloat(a.getAttribute('data-price')) || 0;
                     let priceB = parseFloat(b.getAttribute('data-price')) || 0;
                     return activeSort === 'price-asc' ? priceA - priceB : priceB - priceA;
                 }
                 // 'date-desc' — восстанавливаем порядок по data-order (новые сначала)
                 return parseFloat(a.getAttribute('data-order')) - parseFloat(b.getAttribute('data-order'));
             });

             cards.forEach(function (card) {
                 itemsContainer.appendChild(card);
             });

             // порядок карточек в DOM поменялся — Masonry должна разложить их заново
             // в новом порядке (иначе колонки останутся в старом виде до следующего фильтра)
             relayoutWishlistGrid();
         }

         document.querySelectorAll('.js-wishlist-tab').forEach(function (tab) {
             tab.addEventListener('click', function () {
                 document.querySelectorAll('.js-wishlist-tab').forEach(function (t) {
                     t.classList.remove('b-wishlist-toolbar__tab--active');
                 });
                 tab.classList.add('b-wishlist-toolbar__tab--active');
                 activeTab = tab.getAttribute('data-tab');
                 applyFilters();
             });
         });

         // --- выпадающее меню "Сортировка" ---
         function closeAllToolbarMenus() {
             document.querySelectorAll('.js-wishlist-sort-menu').forEach(function (menu) {
                 menu.classList.remove('b-wishlist-toolbar__menu--open');
             });
         }

         function bindToolbarDropdown(toggleSelector, menuSelector) {
             let toggleBtn = document.querySelector(toggleSelector);
             let menu = document.querySelector(menuSelector);
             if (!toggleBtn || !menu) {
                 return;
             }

             toggleBtn.addEventListener('click', function (event) {
                 event.stopPropagation();
                 let wasOpen = menu.classList.contains('b-wishlist-toolbar__menu--open');
                 closeAllToolbarMenus();
                 if (!wasOpen) {
                     menu.classList.add('b-wishlist-toolbar__menu--open');
                 }
             });
         }

         bindToolbarDropdown('.js-wishlist-sort-toggle', '.js-wishlist-sort-menu');

         document.addEventListener('click', function (event) {
             if (!event.target.closest('.js-wishlist-sort-dropdown')) {
                 closeAllToolbarMenus();
             }
         });

         // --- панель фильтров (выезжает справа, см. filter_panel.blade.php) ---
         const filterPanel = document.querySelector('.js-wishlist-filter-panel');
         const filterToggleBtn = document.querySelector('.js-wishlist-filter-toggle');

         if (filterPanel && filterToggleBtn) {
             filterToggleBtn.addEventListener('click', function () {
                 filterPanel.classList.add('b-filter-panel--open');
             });

             document.querySelectorAll('.js-wishlist-filter-panel-close').forEach(function (btn) {
                 btn.addEventListener('click', function () {
                     filterPanel.classList.remove('b-filter-panel--open');
                 });
             });

             document.querySelectorAll('.js-filter-group-toggle').forEach(function (head) {
                 head.addEventListener('click', function () {
                     head.closest('.js-filter-group').classList.toggle('b-filter-panel__group--collapsed');
                 });
             });

             filterPanel.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                 checkbox.addEventListener('change', applyFilters);
             });

             let filterResetBtn = document.querySelector('.js-wishlist-filter-reset');
             if (filterResetBtn) {
                 filterResetBtn.addEventListener('click', function () {
                     filterPanel.querySelectorAll('input[type="checkbox"]:checked').forEach(function (checkbox) {
                         checkbox.checked = false;
                     });
                     applyFilters();
                 });
             }
         }

         document.querySelectorAll('.js-wishlist-sort-option').forEach(function (btn) {
             btn.addEventListener('click', function () {
                 document.querySelectorAll('.js-wishlist-sort-option').forEach(function (b) {
                     b.classList.remove('b-wishlist-toolbar__menu-item--active');
                 });
                 btn.classList.add('b-wishlist-toolbar__menu-item--active');
                 activeSort = btn.getAttribute('data-sort');
                 closeAllToolbarMenus();
                 applySort();
             });
         });

         // --- удаление желания ---
         const deleteModal = $('#modal-delete-wishlist-item');
         let pendingDeleteId = null;

         itemsContainer.addEventListener('click', function (event) {
             let btn = event.target.closest('.js-wishlist-item-delete');
             if (!btn) {
                 return;
             }
             pendingDeleteId = btn.closest('.b-wishlist-item').getAttribute('data-id');
             deleteModal.modal('show');
         });

         document.querySelector('.js-wishlist-item-delete-confirm').addEventListener('click', function () {
             if (!pendingDeleteId) {
                 return;
             }

             $.ajax({
                 type: 'delete',
                 url: itemBaseUrl + '/' + pendingDeleteId,
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 success: function (response) {
                     deleteModal.modal('hide');
                     if (response.status === true) {
                         let card = itemsContainer.querySelector('.b-wishlist-item[data-id="' + pendingDeleteId + '"]');
                         if (card) {
                             card.remove();
                         }
                         applyFilters();
                         toastr.success(response.message || 'Желание удалено');
                     } else {
                         toastr.error(response.message || 'Не удалось удалить желание');
                     }
                     pendingDeleteId = null;
                 },
                 error: function () {
                     deleteModal.modal('hide');
                     toastr.error('Не удалось удалить желание');
                 }
             });
         });

         // --- меню "..." на карточке (Редактировать / Создать копию / Поделиться / Удалить) ---
         function closeAllMoreMenus() {
             document.querySelectorAll('.js-wishlist-item-more-menu').forEach(function (menu) {
                 menu.classList.remove('b-wishlist-item__more-menu--open');
             });
         }

         itemsContainer.addEventListener('click', function (event) {
             let btn = event.target.closest('.js-wishlist-item-more');
             if (!btn) {
                 return;
             }

             event.stopPropagation();
             let menu = btn.closest('.b-wishlist-item').querySelector('.js-wishlist-item-more-menu');
             let alreadyOpen = menu.classList.contains('b-wishlist-item__more-menu--open');
             closeAllMoreMenus();
             if (!alreadyOpen) {
                 menu.classList.add('b-wishlist-item__more-menu--open');
             }
         });

         // клик вне меню — закрываем все открытые
         document.addEventListener('click', function (event) {
             if (!event.target.closest('.js-wishlist-item-more-menu') && !event.target.closest('.js-wishlist-item-more')) {
                 closeAllMoreMenus();
             }
         });

         itemsContainer.addEventListener('click', function (event) {
             let btn = event.target.closest('.js-wishlist-item-edit-trigger');
             if (!btn) {
                 return;
             }

             closeAllMoreMenus();
             currentDetailCard = btn.closest('.b-wishlist-item');
             itemDetailModal.modal('show');
             showItemDetailEdit();
         });

         itemsContainer.addEventListener('click', function (event) {
             let btn = event.target.closest('.js-wishlist-item-share-menu');
             if (!btn) {
                 return;
             }

             closeAllMoreMenus();
             openShareModal();
         });

         itemsContainer.addEventListener('click', function (event) {
             let btn = event.target.closest('.js-wishlist-item-duplicate');
             if (!btn) {
                 return;
             }

             closeAllMoreMenus();
             let itemId = btn.closest('.b-wishlist-item').getAttribute('data-id');

             $.ajax({
                 type: 'post',
                 url: itemBaseUrl + '/' + itemId + '/duplicate',
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 success: function (response) {
                     if (response.status === true) {
                         appendItemCard(response.item);
                         toastr.success(response.message || 'Копия создана');
                     } else {
                         toastr.error(response.message || 'Не удалось создать копию');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось создать копию');
                 }
             });
         });

         // --- добавление желания ---
         const addItemModal = $('#modal-add-wishlist-item');
         const itemInput = document.getElementById('wishlist-item-input');
         const itemTitleInput = document.getElementById('wishlist-item-title');
         const itemPriceInput = document.getElementById('wishlist-item-price');
         const itemCategorySelect = document.getElementById('wishlist-item-category');
         const itemPrioritySelect = document.getElementById('wishlist-item-priority');
         const itemPreviewBox = document.querySelector('.js-wishlist-item-preview');
         const itemImageFileInput = document.getElementById('wishlist-item-image-input');
         let previewImageUrl = null;
         let previewDescription = null;

         function resetAddItemForm() {
             itemInput.value = '';
             itemTitleInput.value = '';
             itemPriceInput.value = '';
             itemCategorySelect.value = '';
             itemPrioritySelect.value = '';
             itemPreviewBox.style.display = 'none';
             itemPreviewBox.innerHTML = '';
             itemImageFileInput.value = '';
             previewImageUrl = null;
             previewDescription = null;
             document.querySelectorAll('.js-wishlist-item-category-pick').forEach(function (b) {
                 b.classList.remove('b-wishlist-filters__category--active');
             });
         }

         document.querySelector('.js-wishlist-add-item').addEventListener('click', function () {
             resetAddItemForm();
             addItemModal.modal('show');
         });

         document.querySelectorAll('.js-wishlist-idea-chip').forEach(function (chip) {
             chip.addEventListener('click', function () {
                 resetAddItemForm();
                 itemInput.value = chip.textContent.trim();
                 itemTitleInput.value = chip.textContent.trim();
                 addItemModal.modal('show');
             });
         });

         document.querySelector('.js-wishlist-item-image-pick').addEventListener('click', function () {
             itemImageFileInput.click();
         });

         itemImageFileInput.addEventListener('change', function () {
             if (!itemImageFileInput.files.length) {
                 return;
             }
             let file = itemImageFileInput.files[0];
             previewImageUrl = null; // ручной файл важнее найденной по ссылке картинки

             let reader = new FileReader();
             reader.onload = function () {
                 itemPreviewBox.style.display = 'flex';
                 itemPreviewBox.className = 'b-wishlist-item-preview js-wishlist-item-preview';
                 itemPreviewBox.innerHTML = '<div class="b-wishlist-item-preview__thumb" style="background-image: url(' + reader.result + ')"></div>' +
                     '<div><div class="b-wishlist-item-preview__title">Своя картинка</div></div>';
             };
             reader.readAsDataURL(file);
         });

         document.querySelectorAll('.js-wishlist-item-category-pick').forEach(function (btn) {
             btn.addEventListener('click', function () {
                 document.querySelectorAll('.js-wishlist-item-category-pick').forEach(function (b) {
                     b.classList.remove('b-wishlist-filters__category--active');
                 });
                 btn.classList.add('b-wishlist-filters__category--active');
                 itemCategorySelect.value = btn.getAttribute('data-category');
             });
         });

         document.querySelector('.js-wishlist-item-find').addEventListener('click', function () {
             let value = itemInput.value.trim();
             if (!value) {
                 toastr.warning('Вставьте ссылку или введите название');
                 return;
             }

             if (!/^https?:\/\//i.test(value)) {
                 // не похоже на ссылку — просто используем как название
                 itemTitleInput.value = value;
                 return;
             }

             let findBtn = this;
             findBtn.disabled = true;
             findBtn.textContent = 'Ищем...';

             $.ajax({
                 type: 'post',
                 url: linkPreviewUrl,
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 data: {url: value},
                 success: function (response) {
                     if (response.status === true) {
                         itemTitleInput.value = response.title || '';
                         if (response.price) {
                             itemPriceInput.value = response.price;
                         }
                         previewDescription = response.description || null;

                         if (response.image_url) {
                             previewImageUrl = response.image_url;
                             itemPreviewBox.style.display = 'flex';
                             itemPreviewBox.className = 'b-wishlist-item-preview js-wishlist-item-preview';
                             itemPreviewBox.innerHTML = '<div class="b-wishlist-item-preview__thumb" style="background-image: url(' + response.image_url + ')"></div>' +
                                 '<div><div class="b-wishlist-item-preview__title">' + (response.title || '') + '</div>' +
                                 (response.price ? '<div class="b-wishlist-item-preview__price">' + response.price + ' грн</div>' : '') + '</div>';
                         }

                         if (!response.title && !response.image_url) {
                             toastr.warning('Не удалось найти данные о товаре — заполните вручную');
                         }
                     } else {
                         toastr.warning(response.message || 'Не удалось найти данные о товаре — заполните вручную');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось найти данные о товаре — заполните вручную');
                 },
                 complete: function () {
                     findBtn.disabled = false;
                     findBtn.innerHTML = 'Найти в магазинах <i class="fas fa-arrow-right ml-1"></i>';
                 }
             });
         });

         document.querySelector('.js-wishlist-item-save').addEventListener('click', function () {
             let title = itemTitleInput.value.trim() || itemInput.value.trim();
             if (!title) {
                 toastr.warning('Введите название желания');
                 return;
             }

             let formData = new FormData();
             formData.append('title', title);
             if (previewDescription) {
                 formData.append('description', previewDescription);
             }
             if (/^https?:\/\//i.test(itemInput.value.trim())) {
                 formData.append('url', itemInput.value.trim());
             }
             if (itemPriceInput.value) {
                 formData.append('price', itemPriceInput.value);
             }
             if (itemCategorySelect.value) {
                 formData.append('category', itemCategorySelect.value);
             }
             if (itemPrioritySelect.value) {
                 formData.append('priority', itemPrioritySelect.value);
             }
             if (itemImageFileInput.files.length) {
                 formData.append('image', itemImageFileInput.files[0]);
             } else if (previewImageUrl) {
                 formData.append('image_url', previewImageUrl);
             }

             $.ajax({
                 type: 'post',
                 url: itemsStoreUrl,
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function (response) {
                     if (response.status === true) {
                         addItemModal.modal('hide');
                         appendItemCard(response.item);
                         toastr.success(response.message || 'Желание добавлено');
                     } else {
                         toastr.error(response.message || 'Не удалось добавить желание');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось добавить желание');
                 }
             });
         });

         function appendItemCard(item) {
             emptyHint.style.display = 'none';

             let card = document.createElement('div');
             card.className = 'b-wishlist-item';
             card.setAttribute('data-id', item.id);
             card.setAttribute('data-category', item.category || '');
             card.setAttribute('data-priority', item.priority || '');
             card.setAttribute('data-store', item.url_domain || '');
             card.setAttribute('data-reserved', item.is_reserved ? '1' : '0');
             card.setAttribute('data-fulfilled', item.is_fulfilled ? '1' : '0');
             card.setAttribute('data-title', item.title);
             card.setAttribute('data-description', item.description || '');
             card.setAttribute('data-price', item.price || '');
             card.setAttribute('data-url', item.url || '');
             card.setAttribute('data-image-url', item.image_url || '');
             card.setAttribute('data-image-large-url', item.image_large_url || item.image_url || '');
             card.setAttribute('data-created-at', item.created_at || '');
             // новое желание — всегда "новее" любого уже отрендеренного (см. data-order выше)
             card.setAttribute('data-order', String(nextOrderValue--));

             let titleHtml = item.url
                 ? '<a href="' + item.url + '" target="_blank" rel="noopener">' + item.title + '</a>'
                 : item.title;

             card.innerHTML =
                 '<div class="b-wishlist-item__thumb">' +
                     (item.image_url
                         ? '<img src="' + item.image_url + '" alt="' + item.title + '" loading="lazy">'
                         : '<div class="b-wishlist-item__thumb-placeholder"><i class="fas fa-gift"></i></div>') +
                     '<div class="b-wishlist-item__hover-top">' +
                         '<button type="button" class="b-wishlist-item__hover-btn js-wishlist-item-more" title="Ещё"><i class="fas fa-ellipsis-h"></i></button>' +
                         '<button type="button" class="b-wishlist-item__hover-btn js-wishlist-item-share" title="Поделиться"><i class="fas fa-share-alt"></i></button>' +
                         '<button type="button" class="b-wishlist-item__hover-btn b-wishlist-item__hover-btn--accent js-wishlist-item-detail" title="Подробнее"><i class="fas fa-shopping-bag"></i></button>' +
                     '</div>' +
                     '<div class="b-wishlist-item__hover-bottom">' +
                         '<button type="button" class="b-wishlist-item__fulfill-pill js-wishlist-item-fulfill">Выполнить</button>' +
                     '</div>' +
                 '</div>' +
                 '<div class="b-wishlist-item__more-menu js-wishlist-item-more-menu">' +
                     '<button type="button" class="b-wishlist-item__more-menu-item js-wishlist-item-edit-trigger"><i class="fas fa-pencil-alt"></i> Редактировать</button>' +
                     '<button type="button" class="b-wishlist-item__more-menu-item js-wishlist-item-duplicate"><i class="fas fa-copy"></i> Создать копию</button>' +
                     '<button type="button" class="b-wishlist-item__more-menu-item js-wishlist-item-share-menu"><i class="fas fa-share-alt"></i> Поделиться</button>' +
                     '<div class="b-wishlist-item__more-menu-divider"></div>' +
                     '<button type="button" class="b-wishlist-item__more-menu-item b-wishlist-item__more-menu-item--danger js-wishlist-item-delete"><i class="fas fa-trash"></i> Удалить</button>' +
                 '</div>' +
                 '<div class="b-wishlist-item__body">' +
                     '<div class="b-wishlist-item__title">' + titleHtml + '</div>' +
                     '<div class="b-wishlist-item__meta">' +
                         (item.url_domain ? '<span class="b-wishlist-item__domain">' + item.url_domain + '</span>' : '') +
                         (item.price !== null ? '<span class="b-wishlist-item__price">' + Math.round(item.price) + ' грн</span>' : '') +
                     '</div>' +
                 '</div>';

             itemsContainer.appendChild(card);
             applySort();
             applyFilters(); // уже пересчитывает раскладку (relayoutWishlistGrid), но пока без учёта
                              // размера новой картинки — она в этот момент ещё не догрузилась

             // как только картинка новой карточки реально загрузится (известна её высота) —
             // раскладка пересчитывается ещё раз, иначе колонка посчитает такую карточку нулевой
             // высоты и плитки съедут
             if (item.image_url) {
                 $(card).imagesLoaded().always(function () {
                     relayoutWishlistGrid();
                 });
             }
         }

         // --- настройки вишлиста (название/описание/повод/удаление) ---
         const editModal = $('#modal-edit-wishlist');

         document.querySelectorAll('.js-wishlist-edit').forEach(function (btn) {
             btn.addEventListener('click', function () {
                 editModal.modal('show');
             });
         });

         const editOccasionPicker = initOccasionPicker(
             document.querySelector('#modal-edit-wishlist .js-occasion-picker'),
             document.getElementById('wishlist-edit-occasion-custom')
         );

         document.querySelector('.js-wishlist-edit-save').addEventListener('click', function () {
             let title = document.getElementById('wishlist-edit-title').value.trim();
             let description = document.getElementById('wishlist-edit-description').value.trim();
             let occasion = editOccasionPicker.getValue();

             $.ajax({
                 type: 'put',
                 url: updateUrl,
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 data: {title: title, description: description, occasion: occasion},
                 success: function (response) {
                     if (response.status === true) {
                         editModal.modal('hide');
                         if (title) {
                             document.querySelector('.js-wishlist-title').textContent = title;
                         }
                         document.querySelector('.js-wishlist-description').textContent = description || 'Дополнительная информация о вишлисте (необязательно)';
                         toastr.success(response.message || 'Вишлист обновлён');
                     } else {
                         toastr.error(response.message || 'Не удалось сохранить изменения');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось сохранить изменения');
                 }
             });
         });

         document.querySelector('.js-wishlist-delete').addEventListener('click', function () {
             if (!confirm('Удалить вишлист вместе со всеми желаниями? Это действие нельзя отменить.')) {
                 return;
             }

             $.ajax({
                 type: 'delete',
                 url: destroyUrl,
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 success: function (response) {
                     if (response.status === true) {
                         window.location.href = wishlistsIndexUrl;
                     } else {
                         toastr.error(response.message || 'Не удалось удалить вишлист');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось удалить вишлист');
                 }
             });
         });

         // --- "поделиться" конкретным желанием — открывает тот же попап "Поделиться
         // вишлистом", что и кнопка в шапке (отдельных страниц у желаний нет) ---
         itemsContainer.addEventListener('click', function (event) {
             if (!event.target.closest('.js-wishlist-item-share')) {
                 return;
             }

             openShareModal();
         });

         // --- попап "Подробнее о желании" (открывается по клику на иконку сумки) ---
         const itemDetailModal = $('#modal-wishlist-item-detail');
         const detailView = document.querySelector('.js-item-detail-view');
         const detailEditFields = document.querySelector('.js-item-detail-edit-fields');
         const detailEditToggleBtn = document.querySelector('.js-item-detail-edit-toggle');
         const detailSaveBtn = document.querySelector('.js-item-detail-save');
         const detailStoreLink = document.querySelector('.js-item-detail-open-store');
         const detailStoreLabel = document.querySelector('.js-item-detail-store-label');
         const detailSubtitle = document.querySelector('.js-item-detail-subtitle');
         const detailDescriptionEl = document.querySelector('.js-item-detail-description');
         const detailDescriptionToggle = document.querySelector('.js-item-detail-description-toggle');
         const detailDetailsBlock = document.querySelector('.js-item-detail-details');
         const detailMoreMenu = document.querySelector('.js-item-detail-more-menu');
         const detailFulfillLink = document.querySelector('.js-item-detail-fulfill-toggle');
         let currentDetailCard = null;

         // клик по картинке в попапе "Подробнее" открывает её увеличенную версию (fancybox
         // уже подключён глобально в admin_layout.blade.php, как и на страницах постов/контактов)
         if (typeof Fancybox !== 'undefined') {
             Fancybox.bind('[data-fancybox="wishlist-item-image"]', {infinite: false});
         }

         // "3 минуты назад" / "2 часа назад" / "5 дней назад" и т.д.
         function pluralizeRu(n, one, few, many) {
             let mod100 = Math.abs(n) % 100;
             let mod10 = mod100 % 10;
             if (mod100 >= 11 && mod100 <= 19) {
                 return many;
             }
             if (mod10 === 1) {
                 return one;
             }
             if (mod10 >= 2 && mod10 <= 4) {
                 return few;
             }
             return many;
         }

         function formatRelativeTime(isoString) {
             if (!isoString) {
                 return '';
             }
             let then = new Date(isoString).getTime();
             if (isNaN(then)) {
                 return '';
             }
             let diffSeconds = Math.max(0, Math.floor((Date.now() - then) / 1000));

             if (diffSeconds < 60) {
                 return 'только что';
             }
             if (diffSeconds < 3600) {
                 let n = Math.floor(diffSeconds / 60);
                 return n + ' ' + pluralizeRu(n, 'минуту', 'минуты', 'минут') + ' назад';
             }
             if (diffSeconds < 86400) {
                 let n = Math.floor(diffSeconds / 3600);
                 return n + ' ' + pluralizeRu(n, 'час', 'часа', 'часов') + ' назад';
             }
             let n = Math.floor(diffSeconds / 86400);
             if (n < 30) {
                 return n + ' ' + pluralizeRu(n, 'день', 'дня', 'дней') + ' назад';
             }
             let date = new Date(then);
             return date.toLocaleDateString('ru-RU');
         }

         function getUrlDomain(url) {
             try {
                 return new URL(url).hostname.replace(/^www\./, '');
             } catch (e) {
                 return '';
             }
         }

         function closeDetailMoreMenu() {
             if (detailMoreMenu) {
                 detailMoreMenu.classList.remove('b-item-detail__more-menu--open');
             }
         }

         function setDetailFulfillLinkState(isFulfilled) {
             if (detailFulfillLink) {
                 detailFulfillLink.textContent = isFulfilled ? 'Отменить' : 'Отметить как выполненное';
             }
         }

         function showItemDetailView(card) {
             let thumb = document.querySelector('.js-item-detail-thumb');
             let imageUrl = card.getAttribute('data-image-url');
             thumb.style.backgroundImage = imageUrl ? 'url(' + imageUrl + ')' : '';
             // ссылку на увеличенную версию ставим только когда есть что показывать —
             // <a> без href не кликабельна и fancybox её не откроет
             let largeImageUrl = card.getAttribute('data-image-large-url') || imageUrl;
             if (largeImageUrl) {
                 thumb.setAttribute('href', largeImageUrl);
             } else {
                 thumb.removeAttribute('href');
             }
             document.querySelector('.js-item-detail-title').textContent = card.getAttribute('data-title');
             let price = card.getAttribute('data-price');
             document.querySelector('.js-item-detail-price').textContent = price ? Math.round(price) + ' грн' : '';

             if (detailSubtitle) {
                 let relative = formatRelativeTime(card.getAttribute('data-created-at'));
                 detailSubtitle.textContent = relative ? 'Добавлено тобой ' + relative : '';
             }

             let description = card.getAttribute('data-description') || '';
             detailDescriptionEl.textContent = description;
             detailDetailsBlock.style.display = description ? '' : 'none';
             if (detailDescriptionToggle) {
                 detailDescriptionEl.classList.remove('b-item-detail__description--expanded');
                 detailDescriptionToggle.textContent = 'Показать больше';
                 detailDescriptionToggle.style.display = 'none';
                 if (description) {
                     // измеряем после рендера — показываем "Показать больше" только если текст
                     // реально обрезается по высоте (3 строки)
                     setTimeout(function () {
                         if (detailDescriptionEl.scrollHeight > detailDescriptionEl.clientHeight + 1) {
                             detailDescriptionToggle.style.display = '';
                         }
                     }, 0);
                 }
             }

             setDetailFulfillLinkState(card.getAttribute('data-fulfilled') === '1');

             let url = card.getAttribute('data-url');
             if (url) {
                 detailStoreLink.style.display = '';
                 detailStoreLink.href = url;
                 if (detailStoreLabel) {
                     let domain = getUrlDomain(url);
                     detailStoreLabel.textContent = domain ? 'Купить на ' + domain : 'Открыть в магазине';
                 }
             } else {
                 detailStoreLink.style.display = 'none';
             }

             closeDetailMoreMenu();
             detailView.style.display = '';
             if (detailEditFields) {
                 detailEditFields.style.display = 'none';
             }
             if (detailSaveBtn) {
                 detailSaveBtn.style.display = 'none';
             }
         }

         function showItemDetailEdit() {
             if (!currentDetailCard) {
                 return;
             }

             closeDetailMoreMenu();

             document.getElementById('item-detail-edit-title').value = currentDetailCard.getAttribute('data-title') || '';
             document.getElementById('item-detail-edit-description').value = currentDetailCard.getAttribute('data-description') || '';
             document.getElementById('item-detail-edit-price').value = currentDetailCard.getAttribute('data-price') || '';
             document.getElementById('item-detail-edit-url').value = currentDetailCard.getAttribute('data-url') || '';
             let priorityField = document.getElementById('item-detail-edit-priority');
             if (priorityField) {
                 priorityField.value = currentDetailCard.getAttribute('data-priority') || '';
             }

             detailView.style.display = 'none';
             detailEditFields.style.display = '';
             detailSaveBtn.style.display = '';
         }

         itemsContainer.addEventListener('click', function (event) {
             if (!event.target.closest('.js-wishlist-item-detail')) {
                 return;
             }

             currentDetailCard = event.target.closest('.b-wishlist-item');
             showItemDetailView(currentDetailCard);
             itemDetailModal.modal('show');
         });

         if (detailEditToggleBtn) {
             detailEditToggleBtn.addEventListener('click', showItemDetailEdit);
         }

         // --- меню "..." рядом с заголовком в попапе подробностей ---
         const detailMoreToggleBtn = document.querySelector('.js-item-detail-more-toggle');
         if (detailMoreToggleBtn && detailMoreMenu) {
             detailMoreToggleBtn.addEventListener('click', function (event) {
                 event.stopPropagation();
                 detailMoreMenu.classList.toggle('b-item-detail__more-menu--open');
             });
             document.addEventListener('click', function (event) {
                 if (!event.target.closest('.js-item-detail-more')) {
                     closeDetailMoreMenu();
                 }
             });
         }

         // --- "Показать больше" / "Свернуть" у описания ---
         if (detailDescriptionToggle) {
             detailDescriptionToggle.addEventListener('click', function () {
                 let expanded = detailDescriptionEl.classList.toggle('b-item-detail__description--expanded');
                 detailDescriptionToggle.textContent = expanded ? 'Свернуть' : 'Показать больше';
             });
         }

         // --- "Отметить как выполненное" / "Отменить" прямо из попапа подробностей ---
         if (detailFulfillLink) {
             detailFulfillLink.addEventListener('click', function () {
                 if (!currentDetailCard) {
                     return;
                 }

                 let itemId = currentDetailCard.getAttribute('data-id');
                 if (currentDetailCard.getAttribute('data-fulfilled') === '1') {
                     toggleFulfillRequest(itemId);
                 } else {
                     pendingFulfillItemId = itemId;
                     itemDetailModal.modal('hide');
                     fulfillConfirmModal.modal('show');
                 }
             });
         }

         if (detailSaveBtn) {
             detailSaveBtn.addEventListener('click', function () {
                 if (!currentDetailCard) {
                     return;
                 }

                 let itemId = currentDetailCard.getAttribute('data-id');
                 let title = document.getElementById('item-detail-edit-title').value.trim();
                 let description = document.getElementById('item-detail-edit-description').value.trim();
                 let price = document.getElementById('item-detail-edit-price').value;
                 let url = document.getElementById('item-detail-edit-url').value.trim();
                 let priorityField = document.getElementById('item-detail-edit-priority');
                 let priority = priorityField ? priorityField.value : '';

                 if (!title) {
                     toastr.warning('Введите название');
                     return;
                 }

                 $.ajax({
                     type: 'put',
                     url: itemBaseUrl + '/' + itemId,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: {title: title, description: description, price: price, url: url, priority: priority},
                     success: function (response) {
                         if (response.status !== true) {
                             toastr.error(response.message || 'Не удалось сохранить изменения');
                             return;
                         }

                         let item = response.item;
                         currentDetailCard.setAttribute('data-title', item.title);
                         currentDetailCard.setAttribute('data-description', item.description || '');
                         currentDetailCard.setAttribute('data-price', item.price || '');
                         currentDetailCard.setAttribute('data-url', item.url || '');
                         currentDetailCard.setAttribute('data-priority', item.priority || '');
                         currentDetailCard.setAttribute('data-store', item.url_domain || '');

                         let titleEl = currentDetailCard.querySelector('.b-wishlist-item__title');
                         titleEl.textContent = item.title;

                         let priceEl = currentDetailCard.querySelector('.b-wishlist-item__price');
                         if (priceEl) {
                             priceEl.textContent = item.price !== null ? Math.round(item.price) + ' грн' : '';
                         }

                         showItemDetailView(currentDetailCard);
                         toastr.success(response.message || 'Изменения сохранены');
                     },
                     error: function () {
                         toastr.error('Не удалось сохранить изменения');
                     }
                 });
             });
         }

         // --- "Выполнить" — попап подтверждения, затем отмечаем желание как исполненное ---
         const fulfillConfirmModal = $('#modal-wishlist-fulfill-confirm');
         let pendingFulfillItemId = null;

         function toggleFulfillRequest(itemId) {
             $.ajax({
                 type: 'post',
                 url: itemBaseUrl + '/' + itemId + '/toggle-fulfilled',
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 success: function (response) {
                     if (response.status !== true) {
                         toastr.error('Не удалось изменить статус');
                         return;
                     }
                     updateFulfillCardState(itemId, response.item.is_fulfilled);
                 },
                 error: function () {
                     toastr.error('Не удалось изменить статус');
                 }
             });
         }

         // "исполнено" (это поле) и "забронировано" (data-reserved, ставит гость) — разные,
         // независимые статусы. Бейдж на карточке показывает только один — "исполнено" в
         // приоритете, как более сильный статус (см. item_cards.blade.php)
         function updateFulfillCardState(itemId, isFulfilled) {
             let card = itemsContainer.querySelector('.b-wishlist-item[data-id="' + itemId + '"]');
             if (!card) {
                 return;
             }

             card.classList.toggle('b-wishlist-item--fulfilled', isFulfilled);
             card.setAttribute('data-fulfilled', isFulfilled ? '1' : '0');

             let thumb = card.querySelector('.b-wishlist-item__thumb');
             let existingBadge = thumb.querySelector('.b-wishlist-item__reserved-badge');
             if (existingBadge) {
                 existingBadge.remove();
             }
             if (isFulfilled) {
                 let badge = document.createElement('div');
                 badge.className = 'b-wishlist-item__reserved-badge b-wishlist-item__reserved-badge--fulfilled';
                 badge.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Выполнено';
                 thumb.appendChild(badge);
             } else if (card.getAttribute('data-reserved') === '1') {
                 // сняли отметку "исполнено" — если гость его бронировал, бейдж брони
                 // должен вернуться
                 let badge = document.createElement('div');
                 badge.className = 'b-wishlist-item__reserved-badge';
                 badge.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Забронировано';
                 thumb.appendChild(badge);
             }

             let pill = card.querySelector('.js-wishlist-item-fulfill');
             if (pill) {
                 pill.textContent = isFulfilled ? 'Отменить' : 'Выполнить';
             }

             if (currentDetailCard === card) {
                 setDetailFulfillLinkState(isFulfilled);
             }

             applyFilters();
         }

         itemsContainer.addEventListener('click', function (event) {
             let btn = event.target.closest('.js-wishlist-item-fulfill');
             if (!btn) {
                 return;
             }

             let card = btn.closest('.b-wishlist-item');
             let itemId = card.getAttribute('data-id');

             if (card.getAttribute('data-fulfilled') === '1') {
                 // снимаем отметку сразу, без подтверждения
                 toggleFulfillRequest(itemId);
             } else {
                 pendingFulfillItemId = itemId;
                 fulfillConfirmModal.modal('show');
             }
         });

         document.querySelector('.js-wishlist-fulfill-confirm').addEventListener('click', function () {
             if (!pendingFulfillItemId) {
                 return;
             }
             fulfillConfirmModal.modal('hide');
             toggleFulfillRequest(pendingFulfillItemId);
             pendingFulfillItemId = null;
         });
     })();

     // ===== База контактов: общий виджет подбора хештегов =====
     // (используется и в попапе создания контакта, и в попапе редактирования)
     // раньше было объявлено только внутри блока страницы контакта — вынесено сюда,
     // т.к. теперь нужна и здесь, в initHashtagPicker() (переиспользуется на нескольких
     // страницах/попапах, см. ниже)
     function escapeHtml(text) {
         let div = document.createElement('div');
         div.textContent = text;
         return div.innerHTML;
     }

     function initHashtagPicker(pickerEl, searchUrl, createUrl) {
         const input = pickerEl.querySelector('.js-contact-tag-picker__input');
         const results = pickerEl.querySelector('.js-contact-tag-picker__results');
         const selected = pickerEl.querySelector('.js-contact-tag-picker__selected');
         let searchTimeout = null;

         // Список по умолчанию открывается вниз; разворачиваем вверх, только если снизу
         // не остаётся места даже на пару пунктов — сам список и так скроллится внутри
         // себя (overflow-y:auto, max-height 200px в CSS), поэтому не требуем поместить
         // весь найденный список целиком: если сравнивать со scrollHeight, поиск обычно
         // находит достаточно хештегов, чтобы scrollHeight почти всегда упирался в те
         // же 200px — а в форме контакта поле "Хештеги" как раз оказывается на высоте,
         // где свободного места чуть меньше 200px, из-за чего список разворачивался
         // вверх практически всегда, даже когда снизу места объективно достаточно.
         let MIN_RESULTS_SPACE = 90; // примерно 2-3 пункта списка
         function positionResults() {
             let spaceBelow = window.innerHeight - pickerEl.getBoundingClientRect().bottom;
             results.classList.toggle('b-contact-tag-picker__results--above', spaceBelow < MIN_RESULTS_SPACE);
         }

         function getSelectedMap() {
             let map = {};
             selected.querySelectorAll('.b-contact-tag-picker__tag').forEach(function (tag) {
                 map[tag.getAttribute('data-id')] = true;
             });
             return map;
         }

         function addTag(id, title) {
             if (selected.querySelector('.b-contact-tag-picker__tag[data-id="' + id + '"]')) {
                 return;
             }
             let tag = document.createElement('span');
             tag.className = 'b-contact-tag-picker__tag';
             tag.setAttribute('data-id', id);
             tag.innerHTML = '#' + title + ' <i class="fas fa-times js-contact-tag-picker__remove"></i>';
             selected.appendChild(tag);
         }

         selected.addEventListener('click', function (event) {
             if (event.target.closest('.js-contact-tag-picker__remove')) {
                 event.target.closest('.b-contact-tag-picker__tag').remove();
             }
         });

         input.addEventListener('input', function () {
             clearTimeout(searchTimeout);
             let value = input.value.trim();
             if (!value) {
                 results.classList.remove('b-contact-tag-picker__results--open');
                 results.innerHTML = '';
                 return;
             }

             searchTimeout = setTimeout(function () {
                 $.ajax({
                     type: 'post',
                     url: searchUrl,
                     headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                     data: {search: value, hashtags: getSelectedMap()},
                     success: function (response) {
                         results.innerHTML = '';
                         let hashtags = (response.status === true && response.hashtags) ? response.hashtags : [];

                         hashtags.forEach(function (hashtag) {
                             let li = document.createElement('li');
                             li.textContent = '#' + hashtag.title;
                             li.addEventListener('click', function () {
                                 addTag(hashtag.id, hashtag.title);
                                 input.value = '';
                                 results.classList.remove('b-contact-tag-picker__results--open');
                                 results.innerHTML = '';
                             });
                             results.appendChild(li);
                         });

                         // если среди найденного нет хештега с точно таким названием — даём
                         // создать новый прямо отсюда, не уходя в раздел "Хештеги"
                         let exactMatch = hashtags.some(function (hashtag) {
                             return hashtag.title.toLowerCase() === value.toLowerCase();
                         });
                         if (createUrl && !exactMatch) {
                             let createLi = document.createElement('li');
                             createLi.className = 'js-contact-tag-picker__create';
                             createLi.innerHTML = '<i class="fas fa-plus mr-1"></i>Создать «' + escapeHtml(value) + '»';
                             createLi.addEventListener('click', function () {
                                 if (createLi.classList.contains('is-loading')) {
                                     return;
                                 }
                                 createLi.classList.add('is-loading');
                                 $.ajax({
                                     type: 'post',
                                     url: createUrl,
                                     headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                                     data: {title: value},
                                     success: function (createResponse) {
                                         if (createResponse && createResponse.status === true) {
                                             addTag(createResponse.info.id, createResponse.info.title);
                                             input.value = '';
                                             results.classList.remove('b-contact-tag-picker__results--open');
                                             results.innerHTML = '';
                                         } else {
                                             toastr.error((createResponse && createResponse.message) || 'Не удалось создать хештег');
                                             createLi.classList.remove('is-loading');
                                         }
                                     },
                                     error: function () {
                                         toastr.error('Не удалось создать хештег');
                                         createLi.classList.remove('is-loading');
                                     }
                                 });
                             });
                             results.appendChild(createLi);
                         }

                         let hasResults = results.children.length > 0;
                         results.classList.toggle('b-contact-tag-picker__results--open', hasResults);
                         if (hasResults) {
                             positionResults();
                         }
                     }
                 });
             }, 300);
         });

         document.addEventListener('click', function (event) {
             if (!pickerEl.contains(event.target)) {
                 results.classList.remove('b-contact-tag-picker__results--open');
             }
         });

         return {
             getSelectedIds: function () {
                 return Array.from(selected.querySelectorAll('.b-contact-tag-picker__tag')).map(function (tag) {
                     return parseInt(tag.getAttribute('data-id'), 10);
                 });
             }
         };
     }

     // Даёт перетащить файл картинки на блок с превью фото (форма создания/редактирования
     // контакта, .js-contact-photo-dropzone в contact_form_modal.blade.php) в дополнение
     // к обычному клику на "Выбрать файл". onFile(file) вызывается и по клику, и по drop —
     // именно там читается файл и обновляется превью.
     function bindPhotoDropzone(rowEl, inputEl, onFile) {
         inputEl.addEventListener('change', function () {
             if (inputEl.files.length) {
                 onFile(inputEl.files[0]);
             }
         });

         ['dragover', 'dragenter'].forEach(function (evt) {
             rowEl.addEventListener(evt, function (event) {
                 event.preventDefault();
                 rowEl.classList.add('is-dragover');
             });
         });
         ['dragleave', 'drop'].forEach(function (evt) {
             rowEl.addEventListener(evt, function (event) {
                 event.preventDefault();
                 rowEl.classList.remove('is-dragover');
             });
         });
         rowEl.addEventListener('drop', function (event) {
             let files = event.dataTransfer && event.dataTransfer.files;
             if (!files || !files.length) {
                 return;
             }
             let file = files[0];
             if (file.type && file.type.indexOf('image/') !== 0) {
                 toastr.warning('Можно перетащить только изображение');
                 return;
             }
             onFile(file);
         });
     }

     // Кнопка "×" внутри поля "Место жительства" (contact_form_modal.blade.php, оба
     // режима — есть в обоих попапах, поэтому вешаем на все найденные сразу): видна,
     // только пока в поле что-то введено, по клику чистит поле и прячется обратно.
     document.querySelectorAll('.js-contact-form__residence-clear').forEach(function (btn) {
         let input = btn.closest('.b-contact-form-input').querySelector('.js-contact-form__residence');
         if (!input) {
             return;
         }
         function syncClearVisibility() {
             btn.classList.toggle('is-visible', input.value.trim().length > 0);
         }
         syncClearVisibility();
         input.addEventListener('input', syncClearVisibility);
         btn.addEventListener('click', function () {
             input.value = '';
             syncClearVisibility();
             // 'input' событие — чтобы initResidenceAutocomplete() ниже тоже узнал об
             // очистке и закрыл список подсказок (он сам на неё не подписан)
             input.dispatchEvent(new Event('input'));
             input.focus();
         });
     });

     // Календарь "Дата рождения" в форме контакта (tempusdominus-bootstrap-4, уже
     // подключён в admin_layout.blade.php, но раньше нигде не использовался) — вместо
     // нативного <input type="date">, чей календарь нельзя стилизовать. Есть в обоих
     // попапах (создание/редактирование), поэтому просто инициализируем все найденные.
     document.querySelectorAll('.js-contact-form__birth-date-wrap').forEach(function (wrap) {
         $(wrap).datetimepicker({
             format: 'DD.MM.YYYY',
             locale: 'ru',
             useCurrent: false,
             maxDate: moment(),
             icons: {previous: 'fas fa-chevron-left', next: 'fas fa-chevron-right'}
         });
     });

     // Маска поля "Телефон" в форме контакта (jquery.inputmask, подключён в
     // admin_layout.blade.php). Контакты международные (см. demo-данные — Стокгольм,
     // Швеция и т.п.), поэтому НЕ жёсткий шаблон под один код страны/длину
     // (+7 (999) 999-99-99 отсёк бы любой не-российский номер) — просто не даём
     // вводить ничего, кроме цифр, "+", пробелов, скобок и дефисов.
     document.querySelectorAll('.js-contact-form__phone').forEach(function (phoneInput) {
         $(phoneInput).inputmask({
             regex: '^[+]?[0-9 ()\\-]*$',
             greedy: false,
             showMaskOnFocus: false,
             showMaskOnHover: false
         });
     });

     // Координаты выбранного города (lat/lng) — просто складываем в скрытые
     // .js-contact-form__residence-lat/-lng вместе с текстом поля, про запас на будущее
     // (сортировка/фильтр по расстоянию и т.п.); в самой форме никак не показываются.
     // При ручном наборе без выбора из списка координаты не проставляются/сбрасываются.
     function residenceCoordsControls(modalEl) {
         const latInput = modalEl.querySelector('.js-contact-form__residence-lat');
         const lngInput = modalEl.querySelector('.js-contact-form__residence-lng');
         if (!latInput || !lngInput) {
             return {setCoords: function () {}, clearCoords: function () {}};
         }

         return {
             setCoords: function (lat, lng) {
                 latInput.value = lat;
                 lngInput.value = lng;
             },
             clearCoords: function () {
                 latInput.value = '';
                 lngInput.value = '';
             }
         };
     }

     // Подсказки город/страна для "Места жительства" — сначала своя локальная база,
     // при необходимости — Photon/Nominatim (ContactController::searchResidence, URL в
     // data-residence-search-action на корневом .modal, см. contact_form_modal.blade.php).
     // Выбор из списка просто заменяет значение поля целиком — не список тегов, поэтому
     // не переиспользует initHashtagPicker(), хоть и повторяет его паттерн (debounce,
     // разворот вверх/вниз по месту).
     function initResidenceAutocomplete(modalEl) {
         const wrap = modalEl.querySelector('.js-contact-residence');
         const input = modalEl.querySelector('.js-contact-form__residence');
         const results = modalEl.querySelector('.js-contact-residence-results');
         const searchUrl = modalEl.getAttribute('data-residence-search-action');
         if (!wrap || !input || !results || !searchUrl) {
             return;
         }

         const coords = residenceCoordsControls(modalEl);

         let searchTimeout = null;
         let requestSeq = 0; // чтобы устаревший ответ (пользователь уже печатает дальше) не перезаписал список

         function closeResults() {
             results.classList.remove('b-contact-residence__results--open');
         }

         function positionResults() {
             let spaceBelow = window.innerHeight - wrap.getBoundingClientRect().bottom;
             results.classList.toggle('b-contact-residence__results--above', spaceBelow < 90);
         }

         function openResults() {
             results.classList.add('b-contact-residence__results--open');
             positionResults();
         }

         function renderEmpty(text) {
             results.innerHTML = '';
             let li = document.createElement('li');
             li.className = 'b-contact-residence__empty-item';
             li.textContent = text;
             results.appendChild(li);
             openResults();
         }

         function selectPlace(item) {
             input.value = item.label;
             closeResults();
             if (typeof item.lat === 'number' && typeof item.lng === 'number') {
                 coords.setCoords(item.lat, item.lng);
             } else {
                 coords.clearCoords();
             }
         }

         input.addEventListener('input', function () {
             // текст больше не соответствует ранее выбранной подсказке (если она вообще
             // была) — координаты устарели
             coords.clearCoords();

             clearTimeout(searchTimeout);
             let value = input.value.trim();
             if (value.length < 2) {
                 closeResults();
                 return;
             }

             let seq = ++requestSeq;
             // Nominatim просит не спамить запросами — 400мс достаточно, чтобы не слать
             // запрос на каждую нажатую клавишу, но не ощущалось "тормозным" при вводе
             searchTimeout = setTimeout(function () {
                 $.ajax({
                     type: 'post',
                     url: searchUrl,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: {search: value},
                     success: function (response) {
                         if (seq !== requestSeq) {
                             return;
                         }
                         let items = (response && response.status === true && response.results) ? response.results : [];
                         if (!items.length) {
                             renderEmpty('Ничего не найдено');
                             return;
                         }
                         results.innerHTML = '';
                         items.forEach(function (item) {
                             let li = document.createElement('li');
                             let icon = document.createElement('i');
                             icon.className = 'fas fa-map-marker-alt';
                             li.appendChild(icon);
                             li.appendChild(document.createTextNode(' ' + item.label));
                             li.addEventListener('click', function () {
                                 selectPlace(item);
                             });
                             results.appendChild(li);
                         });
                         openResults();
                     },
                     error: function () {
                         if (seq === requestSeq) {
                             closeResults();
                         }
                     }
                 });
             }, 400);
         });

         document.addEventListener('click', function (event) {
             if (!wrap.contains(event.target)) {
                 closeResults();
             }
         });
         document.addEventListener('keydown', function (event) {
             if (event.key === 'Escape') {
                 closeResults();
             }
         });
     }
     document.querySelectorAll('#modal-create-contact, #modal-edit-contact').forEach(initResidenceAutocomplete);

     // Поле пишет дату как ДД.ММ.ГГГГ (см. datetimepicker выше) — на бэкенд шлём
     // однозначный ISO-формат, а не полагаемся на то, как PHP/Carbon разберёт строку
     // с точками. Пустая/нераспознанная строка → пустая строка (birth_date у контакта
     // nullable, backend это допускает).
     function parseBirthDateInput(inputEl) {
         let raw = (inputEl.value || '').trim();
         if (!raw) {
             return '';
         }
         let parsed = moment(raw, 'DD.MM.YYYY', true);
         return parsed.isValid() ? parsed.format('YYYY-MM-DD') : '';
     }

     // ===== База контактов: список контактов (/admin_panel/contacts) =====
     (function () {
         const indexPage = document.getElementById('contacts-index');
         if (!indexPage) {
             return;
         }

         const storeUrl = indexPage.getAttribute('data-store-action');
         const searchHashtagUrl = indexPage.getAttribute('data-search-hashtag-action');
         const createHashtagUrl = indexPage.getAttribute('data-create-hashtag-action');
         const createModal = $('#modal-create-contact');

         const tagPicker = initHashtagPicker(document.querySelector('#modal-create-contact .js-contact-tag-picker'), searchHashtagUrl, createHashtagUrl);
         const photoInput = document.querySelector('#modal-create-contact .js-contact-form__photo-input');
         const photoPreview = document.querySelector('#modal-create-contact .js-contact-form__photo-preview');
         const photoDropzone = document.querySelector('#modal-create-contact .js-contact-photo-dropzone');
         let selectedPhotoFile = null;

         document.querySelectorAll('.js-contact-create-btn').forEach(function (btn) {
             btn.addEventListener('click', function () {
                 createModal.modal('show');
             });
         });

         bindPhotoDropzone(photoDropzone, photoInput, function (file) {
             selectedPhotoFile = file;
             let reader = new FileReader();
             reader.onload = function () {
                 photoPreview.style.backgroundImage = 'url(' + reader.result + ')';
                 // прячет плейсхолдер (иконка + "фото") — см. .has-photo в admin-panel.css
                 photoPreview.classList.add('has-photo');
             };
             reader.readAsDataURL(selectedPhotoFile);
         });

         document.querySelector('#modal-create-contact .js-contact-form__save').addEventListener('click', function () {
             let name = document.querySelector('#modal-create-contact .js-contact-form__name').value.trim();
             if (!name) {
                 toastr.warning('Введите имя');
                 return;
             }

             let formData = new FormData();
             formData.append('name', name);
             formData.append('birth_date', parseBirthDateInput(document.querySelector('#modal-create-contact .js-contact-form__birth-date')));
             formData.append('phone', document.querySelector('#modal-create-contact .js-contact-form__phone').value);
             formData.append('residence', document.querySelector('#modal-create-contact .js-contact-form__residence').value);
             formData.append('residence_lat', document.querySelector('#modal-create-contact .js-contact-form__residence-lat').value);
             formData.append('residence_lng', document.querySelector('#modal-create-contact .js-contact-form__residence-lng').value);
             formData.append('hashtag_ids', JSON.stringify(tagPicker.getSelectedIds()));
             if (selectedPhotoFile) {
                 formData.append('photo', selectedPhotoFile);
             }

             $.ajax({
                 type: 'post',
                 url: storeUrl,
                 headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function (response) {
                     if (response.status === true) {
                         window.location.href = response.redirect;
                     } else {
                         toastr.error(response.message || 'Не удалось создать контакт');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось создать контакт');
                 }
             });
         });

         // --- панель поиска/фильтров/сортировки над сеткой карточек ---
         const grid = document.querySelector('.js-contacts-grid');
         if (grid) {
             const cards = Array.prototype.slice.call(grid.querySelectorAll('.b-contact-card'));
             const emptySearchState = document.querySelector('.js-contacts-empty-search');
             const searchInput = document.querySelector('.js-contacts-search-input');
             const sortBtn = document.querySelector('.js-contacts-sort-btn');
             const sortLabel = document.querySelector('.js-contacts-sort-label');
             const countLabel = document.querySelector('.js-contacts-count');
             const countWord = countLabel ? countLabel.getAttribute('data-word') : '';
             const totalCount = cards.length;

             // "N из M контактов" — как в макете (Claude Design), счётчик всегда в этом
             // формате, а не только пока что-то отфильтровано
             function updateCount(visibleCount) {
                 if (countLabel) {
                     countLabel.textContent = visibleCount + ' из ' + totalCount + ' ' + countWord;
                 }
             }

             // Текстовый поиск (тулбар) и фильтр по хэштегам (панель "Фильтры" справа) —
             // два независимых условия, combined через И в refreshVisibility(); хэштеги
             // сюда попадают только по кнопке "Применить" в панели (см. ниже)
             let hashtagFilterIds = [];
             let hashtagFilterMode = 'all';
             let hashtagExcludeIds = [];

             function cardMatchesHashtags(card) {
                 let cardIds = (card.getAttribute('data-hashtag-ids') || '').split(',').filter(Boolean);

                 if (hashtagExcludeIds.length && hashtagExcludeIds.some(function (id) { return cardIds.indexOf(id) !== -1; })) {
                     return false;
                 }
                 if (!hashtagFilterIds.length) {
                     return true;
                 }
                 return hashtagFilterMode === 'any'
                     ? hashtagFilterIds.some(function (id) { return cardIds.indexOf(id) !== -1; })
                     : hashtagFilterIds.every(function (id) { return cardIds.indexOf(id) !== -1; });
             }

             function refreshVisibility() {
                 let q = (searchInput && searchInput.value || '').trim().toLowerCase();
                 let visibleCount = 0;

                 cards.forEach(function (card) {
                     let name = (card.getAttribute('data-name') || '').toLowerCase();
                     let tags = Array.prototype.map.call(card.querySelectorAll('.b-contact-card__tag'), function (el) {
                         return el.textContent.toLowerCase();
                     }).join(' ');
                     let matchesText = !q || name.indexOf(q) !== -1 || tags.indexOf(q) !== -1;
                     let visible = matchesText && cardMatchesHashtags(card);
                     card.style.display = visible ? '' : 'none';
                     if (visible) {
                         visibleCount++;
                     }
                 });

                 if (emptySearchState) {
                     emptySearchState.style.display = visibleCount === 0 ? '' : 'none';
                 }
                 grid.style.display = visibleCount === 0 ? 'none' : '';
                 updateCount(visibleCount);
             }

             if (searchInput) {
                 searchInput.addEventListener('input', refreshVisibility);
             }

             // по умолчанию карточки уже отсортированы А-Я (как и стоит на кнопке при
             // загрузке страницы) — сервер отдаёт их по дате добавления (latest()), сортируем
             // на месте, чтобы начальный вид совпадал с макетом
             cards.sort(function (a, b) {
                 return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '', 'ru');
             });
             cards.forEach(function (card) {
                 grid.appendChild(card);
             });

             if (sortBtn) {
                 sortBtn.addEventListener('click', function () {
                     let asc = sortBtn.getAttribute('data-order') !== 'asc';
                     sortBtn.setAttribute('data-order', asc ? 'asc' : 'desc');
                     if (sortLabel) {
                         sortLabel.textContent = asc ? 'А–Я' : 'Я–А';
                     }

                     cards.sort(function (a, b) {
                         let an = a.getAttribute('data-name') || '';
                         let bn = b.getAttribute('data-name') || '';
                         return asc ? an.localeCompare(bn, 'ru') : bn.localeCompare(an, 'ru');
                     });
                     cards.forEach(function (card) {
                         grid.appendChild(card);
                     });
                 });
             }

             // --- панель "Фильтры" (b-contacts-filters, filters_panel.blade.php) ---
             // По ТЗ по-настоящему работает только раздел "Хештеги" (переиспользует
             // initHashtagPicker() — тот же виджет, что и в форме контакта). Статус, Тип
             // связи, Социальные сети, Личные данные, География, "Есть/нет данных" и
             // "Дополнительные фильтры" — только разметка под макет, ничего не фильтруют.
             const filterBtn = document.querySelector('.js-contacts-filter-btn');
             const filtersPanel = document.querySelector('.js-contacts-filters');
             const filtersOverlay = document.querySelector('.js-contacts-filters-overlay');

             if (filterBtn && filtersPanel && filtersOverlay) {
                 const tagPickerEl = filtersPanel.querySelector('.js-contacts-filters-tag-picker');
                 const tagPicker = initHashtagPicker(tagPickerEl, searchHashtagUrl, null);
                 const countBadge = filtersPanel.querySelector('.js-contacts-filters-count');

                 // "исключить хэштег из поиска" — как в общем поиске по хэштегам
                 // (search_widget.blade.php): чекбокс показывает/прячет отдельный пикер
                 // для исключаемых хэштегов; это тот же initHashtagPicker(), просто вторым
                 // экземпляром на своём контейнере (id тут не участвуют — конфликтов нет)
                 const excludeToggle = filtersPanel.querySelector('.js-contacts-filters-exclude-toggle');
                 const excludeContainer = filtersPanel.querySelector('.js-contacts-filters-exclude');
                 const excludeTagPickerEl = filtersPanel.querySelector('.js-contacts-filters-exclude-tag-picker');
                 const excludeTagPicker = excludeTagPickerEl ? initHashtagPicker(excludeTagPickerEl, searchHashtagUrl, null) : null;

                 if (excludeToggle && excludeContainer) {
                     excludeToggle.addEventListener('change', function () {
                         excludeContainer.style.display = excludeToggle.checked ? '' : 'none';
                     });
                 }

                 function updateFiltersCountBadge() {
                     let n = tagPicker.getSelectedIds().length + (excludeTagPicker ? excludeTagPicker.getSelectedIds().length : 0);
                     countBadge.textContent = n ? '(' + n + ')' : '';
                 }
                 // и добавление хэштега (клик по варианту в выпадающем списке), и его
                 // удаление (клик по ×) — оба клики внутри пикера, достаточно одного
                 // делегированного слушателя на каждый из двух пикеров
                 tagPickerEl.addEventListener('click', updateFiltersCountBadge);
                 if (excludeTagPickerEl) {
                     excludeTagPickerEl.addEventListener('click', updateFiltersCountBadge);
                 }

                 function openFilters() {
                     filtersPanel.classList.add('is-open');
                     filtersOverlay.classList.add('is-open');
                     filtersPanel.setAttribute('aria-hidden', 'false');
                 }

                 function closeFilters() {
                     filtersPanel.classList.remove('is-open');
                     filtersOverlay.classList.remove('is-open');
                     filtersPanel.setAttribute('aria-hidden', 'true');
                 }

                 filterBtn.addEventListener('click', openFilters);
                 filtersOverlay.addEventListener('click', closeFilters);
                 let closeBtn = filtersPanel.querySelector('.js-contacts-filters-close');
                 let cancelBtn = filtersPanel.querySelector('.js-contacts-filters-cancel');
                 if (closeBtn) {
                     closeBtn.addEventListener('click', closeFilters);
                 }
                 if (cancelBtn) {
                     cancelBtn.addEventListener('click', closeFilters);
                 }

                 let applyBtn = filtersPanel.querySelector('.js-contacts-filters-apply');
                 if (applyBtn) {
                     applyBtn.addEventListener('click', function () {
                         hashtagFilterIds = tagPicker.getSelectedIds().map(String);
                         hashtagExcludeIds = excludeTagPicker ? excludeTagPicker.getSelectedIds().map(String) : [];
                         let modeInput = filtersPanel.querySelector('input[name="contacts-filters-hashtags-mode"]:checked');
                         hashtagFilterMode = modeInput ? modeInput.value : 'all';
                         refreshVisibility();
                         closeFilters();
                     });
                 }

                 let resetAllBtn = filtersPanel.querySelector('.js-contacts-filters-reset-all');
                 if (resetAllBtn) {
                     resetAllBtn.addEventListener('click', function () {
                         // хэштеги (включая исключённые) — по-настоящему очищаем, это
                         // единственный рабочий фильтр
                         tagPickerEl.querySelectorAll('.b-contact-tag-picker__tag').forEach(function (tag) {
                             tag.remove();
                         });
                         if (excludeTagPickerEl) {
                             excludeTagPickerEl.querySelectorAll('.b-contact-tag-picker__tag').forEach(function (tag) {
                                 tag.remove();
                             });
                         }
                         if (excludeToggle) {
                             excludeToggle.checked = false;
                         }
                         if (excludeContainer) {
                             excludeContainer.style.display = 'none';
                         }
                         updateFiltersCountBadge();
                         // остальные поля — просто визуально возвращаем к значениям по
                         // умолчанию (defaultChecked/defaultValue — то, что было в разметке)
                         filtersPanel.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach(function (input) {
                             input.checked = input.defaultChecked;
                         });
                         filtersPanel.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (input) {
                             input.value = '';
                         });
                     });
                 }

                 // сворачивающиеся секции ("Личные данные", "География" и т.д.)
                 filtersPanel.querySelectorAll('.js-contacts-filters-collapse-toggle').forEach(function (toggle) {
                     toggle.addEventListener('click', function () {
                         let section = toggle.closest('.js-contacts-filters-collapse');
                         let body = section.querySelector('.b-contacts-filters__collapse-body');
                         let isOpen = section.classList.toggle('is-open');
                         body.style.display = isOpen ? '' : 'none';
                     });
                 });
             }
         }
     })();

     // ===== База контактов: страница одного контакта =====
     (function () {
         const page = document.getElementById('contact-page');
         if (!page) {
             return;
         }

         function csrfToken() {
             return $('meta[name="csrf-token"]').attr('content');
         }

         // --- редактирование контакта (попап переиспользует ту же разметку/тот же
         // виджет хештегов, что и создание, но с data-mode="edit") ---
         const editModal = $('#modal-edit-contact');
         const editModalEl = document.getElementById('modal-edit-contact');
         const searchHashtagUrl = page.getAttribute('data-search-hashtag-action');
         const createHashtagUrl = page.getAttribute('data-create-hashtag-action');

         document.querySelector('.js-contact-edit-btn').addEventListener('click', function () {
             editModal.modal('show');
         });

         const editTagPicker = initHashtagPicker(editModalEl.querySelector('.js-contact-tag-picker'), searchHashtagUrl, createHashtagUrl);

         const editPhotoInput = editModalEl.querySelector('.js-contact-form__photo-input');
         const editPhotoPreview = editModalEl.querySelector('.js-contact-form__photo-preview');
         const editPhotoDropzone = editModalEl.querySelector('.js-contact-photo-dropzone');
         let editSelectedPhotoFile = null;

         bindPhotoDropzone(editPhotoDropzone, editPhotoInput, function (file) {
             editSelectedPhotoFile = file;
             let reader = new FileReader();
             reader.onload = function () {
                 editPhotoPreview.style.backgroundImage = 'url(' + reader.result + ')';
                 editPhotoPreview.classList.add('has-photo');
             };
             reader.readAsDataURL(editSelectedPhotoFile);
         });

         document.querySelector('#modal-edit-contact .js-contact-form__save').addEventListener('click', function () {
             let name = editModalEl.querySelector('.js-contact-form__name').value.trim();
             if (!name) {
                 toastr.warning('Введите имя');
                 return;
             }

             let formData = new FormData();
             formData.append('_method', 'PUT');
             formData.append('name', name);
             formData.append('birth_date', parseBirthDateInput(editModalEl.querySelector('.js-contact-form__birth-date')));
             formData.append('phone', editModalEl.querySelector('.js-contact-form__phone').value);
             formData.append('residence', editModalEl.querySelector('.js-contact-form__residence').value);
             formData.append('residence_lat', editModalEl.querySelector('.js-contact-form__residence-lat').value);
             formData.append('residence_lng', editModalEl.querySelector('.js-contact-form__residence-lng').value);
             formData.append('hashtag_ids', JSON.stringify(editTagPicker.getSelectedIds()));
             if (editSelectedPhotoFile) {
                 formData.append('photo', editSelectedPhotoFile);
             }

             $.ajax({
                 type: 'post',
                 url: page.getAttribute('data-update-action'),
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 data: formData,
                 contentType: false,
                 processData: false,
                 success: function (response) {
                     if (response.status === true) {
                         window.location.reload();
                     } else {
                         toastr.error(response.message || 'Не удалось сохранить изменения');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось сохранить изменения');
                 }
             });
         });

         // два триггера ведут на одно и то же действие: кнопка в поп-апе "Редактировать"
         // и пункт "Удалить контакт" в меню "..." шапки контакта (b-contact-head__menu)
         function deleteContact() {
             if (!confirm('Удалить контакт вместе со всеми фото, соцсетями, высказываниями и фактами? Это действие нельзя отменить.')) {
                 return;
             }

             $.ajax({
                 type: 'delete',
                 url: page.getAttribute('data-destroy-action'),
                 headers: {'X-CSRF-TOKEN': csrfToken()},
                 success: function (response) {
                     if (response.status === true) {
                         window.location.href = page.getAttribute('data-index-action');
                     } else {
                         toastr.error(response.message || 'Не удалось удалить контакт');
                     }
                 },
                 error: function () {
                     toastr.error('Не удалось удалить контакт');
                 }
             });
         }

         document.querySelectorAll('.js-contact-delete').forEach(function (btn) {
             btn.addEventListener('click', deleteContact);
         });

         // --- шапка контакта: меню "..." рядом с "Редактировать" (открыть/закрыть) ---
         const contactHeadMenu = document.querySelector('.js-contact-head-menu');
         if (contactHeadMenu) {
             let menuToggle = contactHeadMenu.querySelector('.js-contact-head-menu-toggle');
             let menuList = contactHeadMenu.querySelector('.js-contact-head-menu-list');
             menuToggle.addEventListener('click', function (event) {
                 event.stopPropagation();
                 menuList.classList.toggle('is-open');
             });
             menuList.addEventListener('click', function (event) {
                 if (event.target.closest('.b-contact-head__menu-item')) {
                     menuList.classList.remove('is-open');
                 }
             });
             document.addEventListener('click', function (event) {
                 if (!event.target.closest('.js-contact-head-menu')) {
                     menuList.classList.remove('is-open');
                 }
             });
         }

         // --- карточка "О себе" на вкладке "Обзор": сворачивает длинный текст (факт +
         // все цитаты) под градиентом с кнопкой "Показать полностью" ---
         document.querySelectorAll('.js-contact-about-toggle').forEach(function (btn) {
             let section = btn.closest('.b-contact-section');
             let about = section ? section.querySelector('.js-contact-about') : null;
             let label = btn.querySelector('.js-contact-about-toggle-label');
             if (!about) {
                 return;
             }
             // Кнопку показываем только если текст реально не помещается в свёрнутую
             // высоту — иначе она осталась бы висеть без дела при коротком "О себе"
             if (about.scrollHeight <= about.clientHeight + 4) {
                 btn.style.display = 'none';
                 return;
             }
             btn.addEventListener('click', function () {
                 let expanded = about.classList.toggle('is-expanded');
                 btn.classList.toggle('is-expanded', expanded);
                 if (label) {
                     label.textContent = expanded ? 'Свернуть' : 'Показать полностью';
                 }
             });
         });

         // --- вкладки верхнего уровня (Обзор/Высказывания/.../Посты) — просто группируют
         // уже существующие секции, никакого AJAX: все панели уже отрендерены, переключаем видимость ---
         function activateContactTab(tab) {
             document.querySelectorAll('.js-contact-tab-btn').forEach(function (b) {
                 b.classList.toggle('is-active', b.getAttribute('data-tab') === tab);
             });
             document.querySelectorAll('.js-contact-tab-panel').forEach(function (p) {
                 p.classList.toggle('is-active', p.getAttribute('data-tab-panel') === tab);
             });
         }

         document.querySelectorAll('.js-contact-tab-btn').forEach(function (btn) {
             btn.addEventListener('click', function () {
                 activateContactTab(btn.getAttribute('data-tab'));
             });
         });

         // --- ссылки-переходы вида "Все связи ->" на вкладке "Обзор": просто переключают
         // на нужную вкладку верхнего уровня ---
         document.querySelectorAll('.js-contact-tab-link').forEach(function (link) {
             link.addEventListener('click', function (event) {
                 event.preventDefault();
                 activateContactTab(link.getAttribute('data-tab'));
             });
         });

         // --- подвкладки внутри "Связи" (Родственники/Друзья/Враги) — та же механика ---
         document.querySelectorAll('.js-contact-subtab-btn').forEach(function (btn) {
             btn.addEventListener('click', function () {
                 let subtab = btn.getAttribute('data-subtab');
                 document.querySelectorAll('.js-contact-subtab-btn').forEach(function (b) {
                     b.classList.toggle('is-active', b === btn);
                 });
                 document.querySelectorAll('.js-contact-subtab-panel').forEach(function (p) {
                     p.classList.toggle('is-active', p.getAttribute('data-subtab-panel') === subtab);
                 });
             });
         });

         // ===== Вкладка "Места": категории слева, сортировка, "Показать ещё" =====
         (function () {
             const placesGrid = document.querySelector('.js-places-grid');
             if (!placesGrid) {
                 return;
             }

             // --- "Показать ещё места" — просто открываем скрытые карточки, без пагинации.
             // Пагинация (лимит из показать ещё) имеет смысл только на "Все места" — при выборе
             // конкретной категории показываем вообще все её карточки, независимо от лимита ---
             let showMoreClicked = false;
             let showMoreBtn = document.querySelector('.js-places-show-more');
             if (showMoreBtn) {
                 showMoreBtn.addEventListener('click', function () {
                     showMoreClicked = true;
                     showMoreBtn.style.display = 'none';
                     applyPlacesCategory();
                 });
             }

             // --- категории слева — фильтр по data-category ---
             let activeCategory = '';

             function applyPlacesCategory() {
                 let visibleCount = 0;
                 let extraStillHidden = false;

                 placesGrid.querySelectorAll('.js-place-item').forEach(function (card) {
                     let matchesCategory = !activeCategory || card.getAttribute('data-category') === activeCategory;
                     card.classList.toggle('is-filtered-out', !matchesCategory);
                     if (!matchesCategory) {
                         return;
                     }

                     visibleCount++;
                     if (card.classList.contains('js-places-extra')) {
                         // за пределами лимита показываем только когда выбрана категория
                         // (тогда пагинация не действует) или когда уже нажали "Показать ещё"
                         let show = !!activeCategory || showMoreClicked;
                         card.style.display = show ? '' : 'none';
                         if (!show) {
                             extraStillHidden = true;
                         }
                     }
                 });

                 let countEl = document.querySelector('.js-places-count');
                 if (countEl) {
                     countEl.textContent = '(' + visibleCount + ')';
                 }
                 let noMatchEl = document.querySelector('.js-places-no-match');
                 if (noMatchEl) {
                     noMatchEl.style.display = visibleCount === 0 ? '' : 'none';
                 }
                 if (showMoreBtn) {
                     showMoreBtn.style.display = extraStillHidden ? '' : 'none';
                 }

                 // --- карта (b-places-map): точки этой же категории, всплывающая карточка —
                 // самая свежая видимая точка (точки на странице уже отсортированы по дате) ---
                 let firstMatchingPin = null;
                 document.querySelectorAll('.js-places-map-pin').forEach(function (pin) {
                     let matches = !activeCategory || pin.getAttribute('data-category') === activeCategory;
                     pin.style.display = matches ? '' : 'none';
                     if (matches && !firstMatchingPin) {
                         firstMatchingPin = pin;
                     }
                 });

                 let popup = document.querySelector('.js-places-map-popup');
                 if (popup) {
                     if (firstMatchingPin) {
                         popup.style.display = '';
                         popup.querySelector('.js-places-map-popup-name').textContent = firstMatchingPin.getAttribute('data-name');
                         popup.querySelector('.js-places-map-popup-type').textContent = firstMatchingPin.getAttribute('data-type');
                         popup.querySelector('.js-places-map-popup-visit').textContent = 'Последнее посещение: ' + firstMatchingPin.getAttribute('data-last-visit');
                         let photoEl = popup.querySelector('.js-places-map-popup-photo');
                         photoEl.className = photoEl.className.replace(/b-contact-place-card__photo--\d/, 'b-contact-place-card__photo--' + firstMatchingPin.getAttribute('data-photo'));
                     } else {
                         popup.style.display = 'none';
                     }
                 }
             }

             document.querySelectorAll('.js-places-category-btn').forEach(function (btn) {
                 btn.addEventListener('click', function () {
                     document.querySelectorAll('.js-places-category-btn').forEach(function (b) {
                         b.classList.remove('is-active');
                     });
                     btn.classList.add('is-active');
                     document.querySelector('.js-places-category-label').textContent = btn.querySelector('span').textContent.trim();

                     activeCategory = btn.getAttribute('data-category') || '';
                     applyPlacesCategory();
                 });
             });

             // --- сортировка (по дате/по названию/по городу) ---
             let sortToggleBtn = document.querySelector('.js-places-sort-toggle');
             let sortMenu = document.querySelector('.js-places-sort-menu');
             if (sortToggleBtn && sortMenu) {
                 sortToggleBtn.addEventListener('click', function (event) {
                     event.stopPropagation();
                     sortMenu.classList.toggle('b-wishlist-toolbar__menu--open');
                 });
                 document.addEventListener('click', function (event) {
                     if (!event.target.closest('.js-places-sort-dropdown')) {
                         sortMenu.classList.remove('b-wishlist-toolbar__menu--open');
                     }
                 });
                 document.querySelectorAll('.js-places-sort-option').forEach(function (btn) {
                     btn.addEventListener('click', function () {
                         document.querySelectorAll('.js-places-sort-option').forEach(function (b) {
                             b.classList.remove('b-wishlist-toolbar__menu-item--active');
                         });
                         btn.classList.add('b-wishlist-toolbar__menu-item--active');
                         document.querySelector('.js-places-sort-label').textContent = btn.textContent.trim();
                         sortMenu.classList.remove('b-wishlist-toolbar__menu--open');

                         let sort = btn.getAttribute('data-sort');
                         let cards = Array.prototype.slice.call(placesGrid.querySelectorAll('.js-place-item'));
                         cards.sort(function (a, b2) {
                             if (sort === 'name') {
                                 return (a.getAttribute('data-name') || '').localeCompare(b2.getAttribute('data-name') || '');
                             }
                             if (sort === 'city') {
                                 return (a.getAttribute('data-city') || '').localeCompare(b2.getAttribute('data-city') || '');
                             }
                             return parseFloat(b2.getAttribute('data-timestamp')) - parseFloat(a.getAttribute('data-timestamp'));
                         });
                         cards.forEach(function (card) {
                             placesGrid.appendChild(card);
                         });
                     });
                 });
             }
         })();

         // ===== Вкладка "Связи": поиск и фильтр по категории =====
         // Данных совсем немного (карточки фиксированы, см. Contact::getRelationsAttribute()),
         // отдельной выезжающей панели фильтров тут не нужно — категория выбирается прямо
         // в тулбаре тем же дропдауном, что и сортировка на вкладке "Высказывания".
         (function () {
             const relationsSearchInput = document.querySelector('.js-relations-search');
             const relationsGrid = document.querySelector('.b-relations-grid');
             if (!relationsGrid) {
                 return;
             }

             let activeCategory = '';

             function applyRelationsFilters() {
                 let search = relationsSearchInput ? (relationsSearchInput.value || '').trim().toLowerCase() : '';
                 let anyVisible = false;

                 document.querySelectorAll('.js-relations-card').forEach(function (card) {
                     let cardCategory = card.getAttribute('data-category');
                     if (activeCategory && cardCategory !== activeCategory) {
                         card.classList.add('is-hidden-by-filter');
                         return;
                     }

                     let cardHasVisible = false;
                     card.querySelectorAll('.js-relation-item').forEach(function (item) {
                         let matches = !search || (item.getAttribute('data-search') || '').indexOf(search) !== -1;
                         item.classList.toggle('is-filtered-out', !matches);
                         if (matches) {
                             cardHasVisible = true;
                             anyVisible = true;
                         }
                     });
                     card.classList.toggle('is-hidden-by-filter', !cardHasVisible);
                 });

                 let noMatchEl = document.querySelector('.js-relations-no-match');
                 if (noMatchEl) {
                     noMatchEl.style.display = anyVisible ? 'none' : '';
                 }
             }

             if (relationsSearchInput) {
                 relationsSearchInput.addEventListener('input', applyRelationsFilters);
             }

             document.querySelectorAll('.js-relations-category-option').forEach(function (btn) {
                 btn.addEventListener('click', function () {
                     document.querySelectorAll('.js-relations-category-option').forEach(function (b) {
                         b.classList.remove('b-wishlist-toolbar__menu-item--active');
                     });
                     btn.classList.add('b-wishlist-toolbar__menu-item--active');
                     document.querySelector('.js-relations-category-label').textContent = btn.textContent.trim();
                     document.querySelector('.js-relations-category-menu').classList.remove('b-wishlist-toolbar__menu--open');

                     activeCategory = btn.getAttribute('data-category') || '';
                     applyRelationsFilters();
                 });
             });

             let categoryToggleBtn = document.querySelector('.js-relations-category-toggle');
             let categoryMenu = document.querySelector('.js-relations-category-menu');
             if (categoryToggleBtn && categoryMenu) {
                 categoryToggleBtn.addEventListener('click', function (event) {
                     event.stopPropagation();
                     categoryMenu.classList.toggle('b-wishlist-toolbar__menu--open');
                 });
                 document.addEventListener('click', function (event) {
                     if (!event.target.closest('.js-relations-category-dropdown')) {
                         categoryMenu.classList.remove('b-wishlist-toolbar__menu--open');
                     }
                 });
             }
         })();

         // ===== Вкладка "Факты": панель фильтров =====
         // Та же механика, что и у фильтров на вкладке "Высказывания"/на странице Wishlist,
         // только "видимая единица" — это не карточка, а строка внутри карточки (.js-fact-item):
         // если после фильтрации в карточке не осталось ни одной видимой строки, прячем и саму
         // карточку целиком (иначе будет пустая карточка с одним заголовком).
         (function () {
             const factsFilterPanel = document.querySelector('.js-facts-filter-panel');
             const factsFilterToggleBtn = document.querySelector('.js-facts-filter-toggle');
             if (!factsFilterPanel) {
                 return;
             }

             factsFilterToggleBtn.addEventListener('click', function () {
                 factsFilterPanel.classList.add('b-filter-panel--open');
             });
             factsFilterPanel.querySelectorAll('.js-facts-filter-panel-close').forEach(function (btn) {
                 btn.addEventListener('click', function () {
                     factsFilterPanel.classList.remove('b-filter-panel--open');
                 });
             });
             factsFilterPanel.querySelectorAll('.js-facts-filter-group-toggle').forEach(function (head) {
                 head.addEventListener('click', function () {
                     head.closest('.js-facts-filter-group').classList.toggle('b-filter-panel__group--collapsed');
                 });
             });

             function checkedFactValues(selector) {
                 return Array.prototype.map.call(
                     factsFilterPanel.querySelectorAll(selector + ':checked'),
                     function (el) { return el.value; }
                 );
             }

             function applyFactFilters() {
                 let categories = checkedFactValues('.js-facts-filter-category');
                 let sources = checkedFactValues('.js-facts-filter-source');
                 let search = (factsFilterPanel.querySelector('.js-facts-search') || document.querySelector('.js-facts-search'));
                 search = search ? (search.value || '').trim().toLowerCase() : '';

                 let anyVisible = false;
                 document.querySelectorAll('.js-facts-card').forEach(function (card) {
                     let cardHasVisible = false;
                     card.querySelectorAll('.js-fact-item').forEach(function (item) {
                         let matches = true;
                         if (categories.length && categories.indexOf(item.getAttribute('data-category')) === -1) matches = false;
                         if (sources.length && sources.indexOf(item.getAttribute('data-source')) === -1) matches = false;
                         if (search && (item.getAttribute('data-search') || '').indexOf(search) === -1) matches = false;

                         item.classList.toggle('is-filtered-out', !matches);
                         if (matches) {
                             cardHasVisible = true;
                             anyVisible = true;
                         }
                     });
                     card.classList.toggle('is-hidden-by-filter', !cardHasVisible);
                 });

                 let noMatchEl = document.querySelector('.js-facts-no-match');
                 if (noMatchEl) {
                     noMatchEl.style.display = anyVisible ? 'none' : '';
                 }
             }

             factsFilterPanel.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
                 input.addEventListener('change', applyFactFilters);
             });
             let factsSearchInput = document.querySelector('.js-facts-search');
             if (factsSearchInput) {
                 factsSearchInput.addEventListener('input', applyFactFilters);
             }

             let factsFilterResetBtn = factsFilterPanel.querySelector('.js-facts-filter-reset');
             if (factsFilterResetBtn) {
                 factsFilterResetBtn.addEventListener('click', function () {
                     factsFilterPanel.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
                         cb.checked = false;
                     });
                     if (factsSearchInput) {
                         factsSearchInput.value = '';
                     }
                     applyFactFilters();
                 });
             }

             // --- тулбарная кнопка "Добавить факт" — прокручивает к настоящей форме добавления
             // внутри карточки "Мои заметки" и открывает её (единственная реальная карточка) ---
             let factsAddJumpBtn = document.querySelector('.js-facts-add-jump');
             let realFactToggleBtn = document.querySelector('.js-contact-fact-add-toggle');
             if (factsAddJumpBtn && realFactToggleBtn) {
                 factsAddJumpBtn.addEventListener('click', function () {
                     let form = document.querySelector('.js-contact-fact-form');
                     if (form && form.style.display === 'none') {
                         realFactToggleBtn.click();
                     }
                     realFactToggleBtn.scrollIntoView({behavior: 'smooth', block: 'center'});
                     let textEl = document.querySelector('.js-contact-fact-text');
                     if (textEl) {
                         textEl.focus();
                     }
                 });
             }
         })();

         // ===== Вкладка "Высказывания": панель фильтров =====
         // Открывается/закрывается так же, как на странице Wishlist (тот же компонент
         // b-filter-panel, см. filter_panel.blade.php там), просто со своими JS-хуками
         // (js-quotes-*), чтобы не пересекаться с фильтрами Wishlist. В отличие от Wishlist,
         // где фильтруются данные, отданные сервером, здесь фильтры реальные и работают по
         // data-атрибутам карточек (quotes_tab.blade.php), которые уже все на странице.
         (function () {
             const quotesFilterPanel = document.querySelector('.js-quotes-filter-panel');
             const quotesFilterToggleBtn = document.querySelector('.js-quotes-filter-toggle');
             const quotesFeedEl = document.querySelector('.js-quotes-feed');
             if (!quotesFilterPanel || !quotesFeedEl) {
                 return;
             }

             quotesFilterToggleBtn.addEventListener('click', function () {
                 quotesFilterPanel.classList.add('b-filter-panel--open');
             });
             quotesFilterPanel.querySelectorAll('.js-quotes-filter-panel-close').forEach(function (btn) {
                 btn.addEventListener('click', function () {
                     quotesFilterPanel.classList.remove('b-filter-panel--open');
                 });
             });
             quotesFilterPanel.querySelectorAll('.js-quotes-filter-group-toggle').forEach(function (head) {
                 head.addEventListener('click', function () {
                     head.closest('.js-quotes-filter-group').classList.toggle('b-filter-panel__group--collapsed');
                 });
             });

             function checkedValues(selector) {
                 return Array.prototype.map.call(
                     quotesFilterPanel.querySelectorAll(selector + ':checked'),
                     function (el) { return el.value; }
                 );
             }

             function applyQuoteFilters() {
                 let platforms = checkedValues('.js-quotes-filter-platform');
                 let contentTypes = checkedValues('.js-quotes-filter-content-type');
                 let sentiments = checkedValues('.js-quotes-filter-sentiment');
                 let topics = checkedValues('.js-quotes-filter-topic');
                 let search = (quotesFilterPanel.querySelector('.js-quotes-search').value || '').trim().toLowerCase();
                 let dateFrom = quotesFilterPanel.querySelector('.js-quotes-filter-date-from').value;
                 let dateTo = quotesFilterPanel.querySelector('.js-quotes-filter-date-to').value;
                 let fromTs = dateFrom ? new Date(dateFrom + 'T00:00:00').getTime() / 1000 : null;
                 let toTs = dateTo ? new Date(dateTo + 'T23:59:59').getTime() / 1000 : null;

                 let visibleCount = 0;
                 let cards = quotesFeedEl.querySelectorAll('.js-quote-card');
                 cards.forEach(function (card) {
                     let ts = parseFloat(card.getAttribute('data-timestamp'));
                     let matches = true;
                     if (platforms.length && platforms.indexOf(card.getAttribute('data-platform')) === -1) matches = false;
                     if (contentTypes.length && contentTypes.indexOf(card.getAttribute('data-content-type')) === -1) matches = false;
                     if (sentiments.length && sentiments.indexOf(card.getAttribute('data-sentiment')) === -1) matches = false;
                     if (topics.length && topics.indexOf(card.getAttribute('data-topic')) === -1) matches = false;
                     if (search && card.getAttribute('data-search').indexOf(search) === -1) matches = false;
                     if (fromTs !== null && ts < fromTs) matches = false;
                     if (toTs !== null && ts > toTs) matches = false;

                     card.classList.toggle('is-filtered-out', !matches);
                     if (matches) {
                         visibleCount++;
                     }
                 });

                 let countEl = document.querySelector('.js-quotes-count');
                 if (countEl) {
                     countEl.textContent = 'Найдено ' + visibleCount;
                 }
                 let noMatchEl = document.querySelector('.js-quotes-no-match');
                 if (noMatchEl) {
                     noMatchEl.style.display = (visibleCount === 0 && cards.length > 0) ? '' : 'none';
                 }
             }

             quotesFilterPanel.querySelectorAll('input[type="checkbox"], input[type="date"]').forEach(function (input) {
                 input.addEventListener('change', applyQuoteFilters);
             });
             let quotesSearchInput = quotesFilterPanel.querySelector('.js-quotes-search');
             if (quotesSearchInput) {
                 quotesSearchInput.addEventListener('input', applyQuoteFilters);
             }

             let quotesFilterResetBtn = quotesFilterPanel.querySelector('.js-quotes-filter-reset');
             if (quotesFilterResetBtn) {
                 quotesFilterResetBtn.addEventListener('click', function () {
                     quotesFilterPanel.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
                         cb.checked = false;
                     });
                     quotesFilterPanel.querySelectorAll('input[type="date"]').forEach(function (d) {
                         d.value = '';
                     });
                     if (quotesSearchInput) {
                         quotesSearchInput.value = '';
                     }
                     applyQuoteFilters();
                 });
             }

             // --- сортировка (реальная — по data-timestamp, дата у высказываний настоящая) ---
             const sortToggleBtn = document.querySelector('.js-quotes-sort-toggle');
             const sortMenu = document.querySelector('.js-quotes-sort-menu');
             if (sortToggleBtn && sortMenu) {
                 sortToggleBtn.addEventListener('click', function (event) {
                     event.stopPropagation();
                     sortMenu.classList.toggle('b-wishlist-toolbar__menu--open');
                 });
                 document.addEventListener('click', function (event) {
                     if (!event.target.closest('.js-quotes-sort-dropdown')) {
                         sortMenu.classList.remove('b-wishlist-toolbar__menu--open');
                     }
                 });
                 document.querySelectorAll('.js-quotes-sort-option').forEach(function (btn) {
                     btn.addEventListener('click', function () {
                         document.querySelectorAll('.js-quotes-sort-option').forEach(function (b) {
                             b.classList.remove('b-wishlist-toolbar__menu-item--active');
                         });
                         btn.classList.add('b-wishlist-toolbar__menu-item--active');
                         document.querySelector('.js-quotes-sort-label').textContent = btn.textContent.trim();
                         sortMenu.classList.remove('b-wishlist-toolbar__menu--open');

                         let sort = btn.getAttribute('data-sort');
                         let cards = Array.prototype.slice.call(quotesFeedEl.querySelectorAll('.js-quote-card'));
                         cards.sort(function (a, b2) {
                             let tsA = parseFloat(a.getAttribute('data-timestamp'));
                             let tsB = parseFloat(b2.getAttribute('data-timestamp'));
                             return sort === 'date-asc' ? tsA - tsB : tsB - tsA;
                         });
                         cards.forEach(function (card) {
                             quotesFeedEl.appendChild(card);
                         });
                     });
                 });
             }
         })();

         // --- вспомогательное: показать/скрыть маленькую форму добавления (соцсети/факты/цитаты) ---
         function bindAddToggle(toggleSelector, formSelector) {
             let toggleBtn = document.querySelector(toggleSelector);
             let form = document.querySelector(formSelector);
             if (!toggleBtn || !form) {
                 return;
             }
             toggleBtn.addEventListener('click', function () {
                 form.style.display = form.style.display === 'none' ? 'flex' : 'none';
             });
         }

         bindAddToggle('.js-contact-fact-add-toggle', '.js-contact-fact-form');
         bindAddToggle('.js-contact-quote-add-toggle', '.js-contact-quote-form');

         // --- удаление элемента из простого списка (соцсети) — общий обработчик.
         // У высказываний и фактов своя разметка (карточки, не <li>) и свои обработчики — см. ниже ---
         document.querySelectorAll('.js-contact-socials-list').forEach(function (list) {
             list.addEventListener('click', function (event) {
                 let removeIcon = event.target.closest('.js-contact-list-remove');
                 if (!removeIcon) {
                     return;
                 }

                 let li = removeIcon.closest('li');
                 let id = li.getAttribute('data-id');
                 let baseUrl = removeIcon.getAttribute('data-base-url');

                 $.ajax({
                     type: 'delete',
                     url: baseUrl + '/' + id,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     success: function (response) {
                         if (response.status === true) {
                             li.remove();
                         } else {
                             toastr.error(response.message || 'Не удалось удалить');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось удалить');
                     }
                 });
             });
         });

         // --- соцсети: иконка/цвет по названию платформы (дублирует ContactSocialLink::iconFor() на бэкенде,
         // т.к. новая строка добавляется на лету без перезагрузки страницы) ---
         function getSocialIcon(platform) {
             let key = (platform || '').trim().toLowerCase();
             let map = [
                 ['instagram', ['fab fa-instagram', 'linear-gradient(135deg, #f9d13e 0%, #f2603f 40%, #d92e7f 70%, #8a3ab9 100%)']],
                 ['telegram', ['fab fa-telegram-plane', '#229ED9']],
                 ['tiktok', ['fab fa-tiktok', '#010101']],
                 ['youtube', ['fab fa-youtube', '#FF0000']],
                 ['facebook', ['fab fa-facebook-f', '#1877F2']],
                 ['вконтакте', ['fab fa-vk', '#4A76A8']],
                 ['vkontakte', ['fab fa-vk', '#4A76A8']],
                 ['vk', ['fab fa-vk', '#4A76A8']],
                 ['whatsapp', ['fab fa-whatsapp', '#25D366']],
                 ['snapchat', ['fab fa-snapchat-ghost', '#FFFC00']],
                 ['linkedin', ['fab fa-linkedin-in', '#0A66C2']],
                 ['pinterest', ['fab fa-pinterest-p', '#E60023']],
                 ['threads', ['fab fa-at', '#000000']],
                 ['twitter', ['fab fa-twitter', '#1DA1F2']],
                 ['x', ['fab fa-twitter', '#1DA1F2']]
             ];
             for (let i = 0; i < map.length; i++) {
                 if (key && key.indexOf(map[i][0]) !== -1) {
                     return map[i][1];
                 }
             }
             return ['fas fa-share-alt', '#6c757d'];
         }

         // --- соцсети: добавление (поп-ап "Добавить социальную сеть", social_add_modal.blade.php) ---
         const socialsStoreUrl = page.getAttribute('data-socials-store-action');
         const socialsList = document.querySelector('.js-contact-socials-list');
         const socialsEmpty = document.querySelector('.js-contact-social-empty');
         (function () {
             const socialAddModalEl = document.getElementById('modal-add-social');
             const socialOpenBtn = document.querySelector('.js-social-add-open');
             if (!socialAddModalEl || !socialOpenBtn) {
                 return;
             }
             const socialAddModal = $(socialAddModalEl);

             socialOpenBtn.addEventListener('click', function () {
                 socialAddModal.modal('show');
             });

             // --- иконка рядом с select (переиспользуем getSocialIcon(), см. выше) ---
             const platformSelect = socialAddModalEl.querySelector('.js-social-add-platform');
             const platformIconEl = socialAddModalEl.querySelector('.js-social-add-platform-icon i');
             const selectValueEl = socialAddModalEl.querySelector('.js-social-select-value');
             function updateSocialPlatformIcon() {
                 let icon = getSocialIcon(platformSelect.value);
                 platformIconEl.className = icon[0];
                 platformIconEl.parentElement.style.background = icon[1];
                 // подпись кастомного дропдауна (см. ниже) держим в синхроне с select —
                 // и по change-событию, и при программном сбросе (resetSocialAddForm)
                 if (selectValueEl && platformSelect.selectedIndex >= 0) {
                     selectValueEl.textContent = platformSelect.options[platformSelect.selectedIndex].text;
                 }
             }
             if (platformSelect) {
                 platformSelect.addEventListener('change', updateSocialPlatformIcon);
                 updateSocialPlatformIcon();
             }

             // --- кастомный дропдаун вместо нативного select ---
             // раскрытый список нативного <select> рисует ОС/браузер, CSS его не стилизует
             // (нет доступа к иконкам по пунктам, hover-подсветке, скруглениям), поэтому
             // список рисуем сами; сам <select> остаётся источником значения (скрыт, но
             // на него завязаны updateSocialPlatformIcon/сохранение/сброс формы выше и ниже) —
             // клик по кастомному пункту просто проставляет ему value и шлёт 'change'
             (function () {
                 const selectWrap = socialAddModalEl.querySelector('.js-social-select');
                 const selectTrigger = socialAddModalEl.querySelector('.js-social-select-trigger');
                 const selectList = socialAddModalEl.querySelector('.js-social-select-list');
                 if (!selectWrap || !selectTrigger || !selectList) {
                     return;
                 }
                 function closeSelectList() {
                     selectList.hidden = true;
                     selectTrigger.setAttribute('aria-expanded', 'false');
                 }
                 function openSelectList() {
                     selectList.hidden = false;
                     selectTrigger.setAttribute('aria-expanded', 'true');
                 }
                 selectTrigger.addEventListener('click', function (event) {
                     event.preventDefault();
                     if (selectList.hidden) {
                         openSelectList();
                     } else {
                         closeSelectList();
                     }
                 });
                 selectList.querySelectorAll('.js-social-select-option').forEach(function (option) {
                     option.addEventListener('click', function () {
                         platformSelect.value = option.getAttribute('data-value');
                         platformSelect.dispatchEvent(new Event('change', {bubbles: true}));
                         selectList.querySelectorAll('.js-social-select-option').forEach(function (o) {
                             o.classList.toggle('is-selected', o === option);
                         });
                         closeSelectList();
                     });
                 });
                 document.addEventListener('click', function (event) {
                     if (!selectWrap.contains(event.target)) {
                         closeSelectList();
                     }
                 });
                 document.addEventListener('keydown', function (event) {
                     if (event.key === 'Escape') {
                         closeSelectList();
                     }
                 });
             })();

             // --- "Добавить свою социальную сеть" — прячем select, показываем текстовое поле ---
             const platformGroup = socialAddModalEl.querySelector('.js-social-add-platform-group');
             const customGroup = socialAddModalEl.querySelector('.js-social-add-custom-group');
             const customToggle = socialAddModalEl.querySelector('.js-social-add-custom-toggle');
             const customBack = socialAddModalEl.querySelector('.js-social-add-custom-back');
             const customInput = socialAddModalEl.querySelector('.js-social-add-custom-platform');

             function showCustomPlatform() {
                 platformGroup.style.display = 'none';
                 customGroup.style.display = '';
                 customInput.focus();
             }
             function showPlatformSelect() {
                 customGroup.style.display = 'none';
                 platformGroup.style.display = '';
                 customInput.value = '';
             }
             if (customToggle) {
                 customToggle.addEventListener('click', function (event) {
                     event.preventDefault();
                     showCustomPlatform();
                 });
             }
             if (customBack) {
                 customBack.addEventListener('click', function (event) {
                     event.preventDefault();
                     showPlatformSelect();
                 });
             }

             // --- сохранение: блокируем кнопку на время запроса + прелоадер, как у высказываний ---
             const socialSaveBtn = socialAddModalEl.querySelector('.js-social-add-save');
             const socialSaveBtnDefaultHtml = socialSaveBtn.innerHTML;
             function setSocialSaveLoading(isLoading) {
                 socialSaveBtn.disabled = isLoading;
                 socialSaveBtn.innerHTML = isLoading
                     ? '<i class="fas fa-spinner fa-spin mr-1"></i>Добавляем...'
                     : socialSaveBtnDefaultHtml;
             }

             function resetSocialAddForm() {
                 showPlatformSelect();
                 if (platformSelect.options.length) {
                     platformSelect.selectedIndex = 0;
                     updateSocialPlatformIcon();
                 }
                 // закрываем кастомный дропдаун и возвращаем выделение на первый пункт —
                 // иначе при повторном открытии попапа список мог остаться раскрытым
                 let selectList = socialAddModalEl.querySelector('.js-social-select-list');
                 if (selectList) {
                     selectList.hidden = true;
                     selectList.querySelectorAll('.js-social-select-option').forEach(function (o, i) {
                         o.classList.toggle('is-selected', i === 0);
                     });
                 }
                 socialAddModalEl.querySelector('.js-social-add-url').value = '';
                 socialAddModalEl.querySelector('.js-social-add-handle').value = '';
                 setSocialSaveLoading(false);
             }

             socialAddModal.on('hidden.bs.modal', resetSocialAddForm);

             socialSaveBtn.addEventListener('click', function () {
                 if (socialSaveBtn.disabled) {
                     // запрос уже отправлен — игнорируем повторный клик
                     return;
                 }

                 let isCustom = customGroup.style.display !== 'none';
                 let platform = (isCustom ? customInput.value : platformSelect.value).trim();
                 let url = socialAddModalEl.querySelector('.js-social-add-url').value.trim();
                 let handle = socialAddModalEl.querySelector('.js-social-add-handle').value.trim();

                 if (!platform) {
                     toastr.warning('Укажите название социальной сети');
                     return;
                 }
                 if (!handle) {
                     toastr.warning('Укажите название аккаунта');
                     return;
                 }

                 setSocialSaveLoading(true);

                 $.ajax({
                     type: 'post',
                     url: socialsStoreUrl,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: {platform: platform, handle: handle, value: url},
                     success: function (response) {
                         setSocialSaveLoading(false);
                         if (response.status === true) {
                             let icon = getSocialIcon(platform);
                             let linkHtml = url.toLowerCase().indexOf('http') === 0
                                 ? ' <a href="' + url + '" target="_blank" rel="noopener" class="b-contact-social-row__link"><i class="fas fa-external-link-alt"></i></a>'
                                 : '';

                             let li = document.createElement('li');
                             li.className = 'b-contact-social-row';
                             li.setAttribute('data-id', response.social.id);
                             li.innerHTML = '<span class="b-contact-social-row__icon" style="background-color: ' + icon[1] + '"><i class="' + icon[0] + '"></i></span>' +
                                 '<span class="b-contact-social-row__value">' + escapeHtml(handle) + '</span>' +
                                 linkHtml +
                                 ' <i class="fas fa-times js-contact-list-remove" data-base-url="' + page.getAttribute('data-social-base-url') + '"></i>';
                             socialsList.appendChild(li);
                             if (socialsEmpty) {
                                 socialsEmpty.style.display = 'none';
                             }
                             toastr.success('Соцсеть добавлена');
                             socialAddModal.modal('hide');
                         } else {
                             toastr.error(response.message || 'Не удалось добавить');
                         }
                     },
                     error: function () {
                         setSocialSaveLoading(false);
                         toastr.error('Не удалось добавить');
                     }
                 });
             });
         })();

         // --- факты: добавление. Карточка "Мои заметки" на вкладке "Факты" (facts_tab.blade.php)
         // соседствует с подставными карточками, у которых свои счётчики в шапке и в панели
         // фильтров — как и с высказываниями ниже, проще перезагрузить страницу после успешного
         // добавления, чем пересчитывать все эти счётчики на JS ---
         const factsStoreUrl = page.getAttribute('data-facts-store-action');
         const factSaveBtn = document.querySelector('.js-contact-fact-save');
         if (factSaveBtn) {
             factSaveBtn.addEventListener('click', function () {
                 let textEl = document.querySelector('.js-contact-fact-text');
                 let text = textEl.value.trim();
                 if (!text) {
                     return;
                 }

                 $.ajax({
                     type: 'post',
                     url: factsStoreUrl,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: {fact: text},
                     success: function (response) {
                         if (response.status === true) {
                             window.location.reload();
                         } else {
                             toastr.error(response.message || 'Не удалось добавить');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось добавить');
                     }
                 });
             });
         }

         // --- факты: удаление (строки в карточке "Мои заметки", см. facts_tab.blade.php) ---
         const factsListEl = document.querySelector('.js-contact-facts-list');
         if (factsListEl) {
             factsListEl.addEventListener('click', function (event) {
                 let removeIcon = event.target.closest('.js-contact-list-remove');
                 if (!removeIcon) {
                     return;
                 }

                 let row = removeIcon.closest('[data-id]');
                 let id = row.getAttribute('data-id');
                 let baseUrl = removeIcon.getAttribute('data-base-url');

                 $.ajax({
                     type: 'delete',
                     url: baseUrl + '/' + id,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     success: function (response) {
                         if (response.status === true) {
                             window.location.reload();
                         } else {
                             toastr.error(response.message || 'Не удалось удалить');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось удалить');
                     }
                 });
             });
         }

         // --- высказывания: добавление через поп-ап "Добавить высказывание"
         // (quote_add_modal.blade.php). Карточка на вкладке "Высказывания" (b-quotes-card)
         // считает много подставных полей на сервере (тема/тональность/счётчики фильтров,
         // см. ContactQuote::feedMeta()) — вместо дублирования этой логики на JS просто
         // перезагружаем страницу после успешного добавления (кроме случая "Добавить ещё
         // одно высказывание" — там просто очищаем форму и оставляем поп-ап открытым) ---
         const quotesStoreUrl = page.getAttribute('data-quotes-store-action');
         (function () {
             const addModalEl = document.getElementById('modal-add-quote');
             const openBtn = document.querySelector('.js-quote-add-open');
             if (!addModalEl || !openBtn) {
                 return;
             }
             const addModal = $(addModalEl);

             openBtn.addEventListener('click', function () {
                 addModal.modal('show');
             });

             // --- счётчики символов ---
             function bindCounter(textSelector, countSelector) {
                 let el = addModalEl.querySelector(textSelector);
                 let countEl = addModalEl.querySelector(countSelector);
                 if (el && countEl) {
                     el.addEventListener('input', function () {
                         countEl.textContent = el.value.length;
                     });
                 }
             }
             bindCounter('.js-quote-add-text', '.js-quote-add-text-count');
             bindCounter('.js-quote-add-comment', '.js-quote-add-comment-count');

             // --- иконка источника рядом с select (переиспользуем getSocialIcon(), см. выше) ---
             const platformSelect = addModalEl.querySelector('.js-quote-add-platform');
             const sourceIconEl = addModalEl.querySelector('.js-quote-source-icon i');
             function updateSourceIcon() {
                 let icon = getSocialIcon(platformSelect.value);
                 sourceIconEl.className = icon[0];
                 sourceIconEl.parentElement.style.background = icon[1];
             }
             if (platformSelect) {
                 platformSelect.addEventListener('change', updateSourceIcon);
                 updateSourceIcon();
             }

             // --- ссылка на источник: кнопка "открыть" ---
             let urlOpenBtn = addModalEl.querySelector('.js-quote-add-url-open');
             if (urlOpenBtn) {
                 urlOpenBtn.addEventListener('click', function () {
                     let url = addModalEl.querySelector('.js-quote-add-url').value.trim();
                     if (url) {
                         window.open(url, '_blank', 'noopener');
                     }
                 });
             }

             // --- теги: тот же виджет, что и у хештегов контакта ---
             const quoteTagPicker = initHashtagPicker(addModalEl.querySelector('.js-quote-add-tag-picker'), searchHashtagUrl, createHashtagUrl);

             // --- изображения: дропзона + превью, копится в JS-массиве (не в <input>,
             // т.к. из input нельзя убрать один файл — приходится пересобирать список) ---
             let selectedImages = [];
             const dropzone = addModalEl.querySelector('.js-quote-dropzone');
             const imagesInput = addModalEl.querySelector('.js-quote-images-input');
             const imagesPreview = addModalEl.querySelector('.js-quote-images-preview');
             const imagesCountEl = addModalEl.querySelector('.js-quote-images-count');

             function renderImagePreviews() {
                 imagesPreview.innerHTML = '';
                 selectedImages.forEach(function (file, index) {
                     let reader = new FileReader();
                     let thumb = document.createElement('div');
                     thumb.className = 'b-quote-image-thumb';
                     let removeBtn = document.createElement('button');
                     removeBtn.type = 'button';
                     removeBtn.className = 'b-quote-image-thumb__remove';
                     removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                     removeBtn.addEventListener('click', function () {
                         selectedImages.splice(index, 1);
                         renderImagePreviews();
                         if (!selectedImages.length) {
                             recognizeBanner.style.display = 'none';
                         }
                     });
                     thumb.appendChild(removeBtn);
                     imagesPreview.appendChild(thumb);
                     reader.onload = function () {
                         thumb.style.backgroundImage = 'url(' + reader.result + ')';
                     };
                     reader.readAsDataURL(file);
                 });
                 if (imagesCountEl) {
                     imagesCountEl.style.display = selectedImages.length ? '' : 'none';
                     imagesCountEl.textContent = selectedImages.length + ' ' + (selectedImages.length === 1 ? 'изображение' : 'изображения');
                 }
             }

             // --- дата: если пользователь ещё не трогал поле руками, пытаемся подставить её
             // из имени файла скриншота — там обычно есть дата съёмки (Screenshot_20251127-143205.png,
             // "Screenshot 2025-11-27 at 14.32.05.png", photo_2025-11-27_14-32-05.jpg и т.п.) ---
             const dateInput = addModalEl.querySelector('.js-quote-add-date');
             let quoteDateTouched = false;
             if (dateInput) {
                 dateInput.addEventListener('input', function () {
                     quoteDateTouched = true;
                 });
             }

             // год/месяц/день -> "YYYY-MM-DD" для <input type="date">, либо null, если дата
             // невалидна (например, 30 февраля — совпадение цифр) или из будущего (тоже
             // явно не дата на скриншоте/в имени файла)
             function buildDateString(year, month, day) {
                 if (month < 1 || month > 12 || day < 1 || day > 31) {
                     return null;
                 }

                 let date = new Date(year, month - 1, day);
                 if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
                     return null;
                 }
                 if (date.getTime() > Date.now() + 24 * 60 * 60 * 1000) {
                     return null;
                 }

                 let pad = function (n) { return n < 10 ? '0' + n : '' + n; };
                 return year + '-' + pad(month) + '-' + pad(day);
             }

             function extractDateFromFilename(name) {
                 let m = (name || '').match(/(20\d{2})[-_.]?(\d{2})[-_.]?(\d{2})/);
                 if (!m) {
                     return null;
                 }

                 return buildDateString(parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10));
             }

             function applyDateFromFilenames(fileList) {
                 if (!dateInput || quoteDateTouched) {
                     return;
                 }

                 for (let i = 0; i < fileList.length; i++) {
                     let found = extractDateFromFilename(fileList[i].name);
                     if (found) {
                         dateInput.value = found;
                         break;
                     }
                 }
             }

             // дата на самом скрине надёжнее, чем дата в имени файла — распознаём её из
             // текста, который вернул OCR (см. recognizeText() ниже), и переопределяем ей
             // то, что уже могли подставить из имени файла
             const RU_MONTHS_GENITIVE = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
                 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

             function extractDateFromText(text) {
                 text = (text || '').toLowerCase();

                 // ISO/со слэшами: 2025-11-27, 2025.11.27, 2025/11/27
                 let m = text.match(/\b(20\d{2})[.\-\/](\d{1,2})[.\-\/](\d{1,2})\b/);
                 if (m) {
                     let found = buildDateString(parseInt(m[1], 10), parseInt(m[2], 10), parseInt(m[3], 10));
                     if (found) {
                         return found;
                     }
                 }

                 // день.месяц.год — привычный формат в переписках/на скриншотах (27.11.2025)
                 m = text.match(/\b(\d{1,2})[.\-\/](\d{1,2})[.\-\/](20\d{2})\b/);
                 if (m) {
                     let found = buildDateString(parseInt(m[3], 10), parseInt(m[2], 10), parseInt(m[1], 10));
                     if (found) {
                         return found;
                     }
                 }

                 // тот же день.месяц.год, но с двузначным годом (07/07/26) — тоже частый
                 // формат в шапках скриншотов/переписок; год трактуем как 20XX
                 m = text.match(/\b(\d{1,2})[.\-\/](\d{1,2})[.\-\/](\d{2})\b/);
                 if (m) {
                     let found = buildDateString(2000 + parseInt(m[3], 10), parseInt(m[2], 10), parseInt(m[1], 10));
                     if (found) {
                         return found;
                     }
                 }

                 // "27 ноября 2025" / "27 ноября" — без года берём текущий, а если вышло
                 // в будущем (месяц ещё не наступил в этом году), значит это прошлый год
                 // trailing \b намеренно не ставим — после кириллицы (не входит в \w) он
                 // не срабатывает как граница и ломает совпадение без года на конце строки
                 m = text.match(new RegExp('\\b(\\d{1,2})\\s+(' + RU_MONTHS_GENITIVE.join('|') + ')(?:\\s+(20\\d{2})\\s*(?:г\\.?|года)?)?'));
                 if (m) {
                     let day = parseInt(m[1], 10);
                     let month = RU_MONTHS_GENITIVE.indexOf(m[2]) + 1;
                     let year = m[3] ? parseInt(m[3], 10) : new Date().getFullYear();
                     let found = buildDateString(year, month, day);
                     if (!found && !m[3]) {
                         found = buildDateString(year - 1, month, day);
                     }
                     if (found) {
                         return found;
                     }
                 }

                 return null;
             }

             function applyDateFromRecognizedText(text) {
                 if (!dateInput || quoteDateTouched) {
                     return;
                 }

                 let found = extractDateFromText(text);
                 if (found) {
                     dateInput.value = found;
                 }
             }

             function addFiles(fileList) {
                 Array.prototype.forEach.call(fileList, function (file) {
                     if (file.type && file.type.indexOf('image/') === 0) {
                         selectedImages.push(file);
                     }
                 });
                 applyDateFromFilenames(fileList);
                 renderImagePreviews();
                 recognizeText();
             }

             // --- распознавание текста со скриншотов (OCR.space, см.
             // ContactController::recognizeQuoteText()) — запускается сразу при добавлении
             // изображений, "Распознать заново" повторяет тот же запрос вручную ---
             const recognizeUrl = page.getAttribute('data-quote-recognize-action');
             const recognizeBanner = addModalEl.querySelector('.js-quote-recognize');
             const recognizeStatusText = addModalEl.querySelector('.js-quote-recognize-text');
             const recognizeStatusIcon = addModalEl.querySelector('.js-quote-recognize-status i');
             const recognizeRetryBtn = addModalEl.querySelector('.js-quote-recognize-retry');

             function setRecognizeState(state, text) {
                 recognizeBanner.classList.remove('is-success', 'is-error');
                 if (state === 'success') {
                     recognizeBanner.classList.add('is-success');
                     recognizeStatusIcon.className = 'fas fa-check-circle';
                 } else if (state === 'error') {
                     recognizeBanner.classList.add('is-error');
                     recognizeStatusIcon.className = 'fas fa-exclamation-circle';
                 } else {
                     recognizeStatusIcon.className = 'fas fa-spinner fa-spin';
                 }
                 recognizeStatusText.textContent = text;
             }

             function recognizeText() {
                 if (!recognizeUrl || !selectedImages.length) {
                     recognizeBanner.style.display = 'none';
                     return;
                 }

                 recognizeBanner.style.display = '';
                 recognizeRetryBtn.disabled = true;
                 setRecognizeState('loading', 'Распознаём текст...');

                 let formData = new FormData();
                 selectedImages.forEach(function (file) {
                     formData.append('images[]', file);
                 });

                 $.ajax({
                     type: 'post',
                     url: recognizeUrl,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: formData,
                     contentType: false,
                     processData: false,
                     success: function (response) {
                         recognizeRetryBtn.disabled = false;
                         if (response.status === true) {
                             if (response.text) {
                                 let textEl = addModalEl.querySelector('.js-quote-add-text');
                                 textEl.value = response.text;
                                 addModalEl.querySelector('.js-quote-add-text-count').textContent = response.text.length;
                                 // дата на самом скрине надёжнее, чем в имени файла — если нашлась,
                                 // подставляем её поверх (applyDateFromFilenames уже могла отработать)
                                 applyDateFromRecognizedText(response.text);
                             }
                             setRecognizeState('success', 'Текст успешно распознан из ' + response.count + ' ' + (response.count === 1 ? 'изображения' : 'изображений'));
                         } else {
                             setRecognizeState('error', response.message || 'Не удалось распознать текст');
                         }
                     },
                     error: function () {
                         recognizeRetryBtn.disabled = false;
                         setRecognizeState('error', 'Не удалось распознать текст');
                     }
                 });
             }

             if (recognizeRetryBtn) {
                 recognizeRetryBtn.addEventListener('click', recognizeText);
             }

             if (dropzone && imagesInput) {
                 dropzone.addEventListener('click', function () {
                     imagesInput.click();
                 });
                 imagesInput.addEventListener('change', function () {
                     addFiles(imagesInput.files);
                     imagesInput.value = '';
                 });
                 ['dragover', 'dragenter'].forEach(function (evt) {
                     dropzone.addEventListener(evt, function (event) {
                         event.preventDefault();
                         dropzone.classList.add('is-dragover');
                     });
                 });
                 ['dragleave', 'drop'].forEach(function (evt) {
                     dropzone.addEventListener(evt, function (event) {
                         event.preventDefault();
                         dropzone.classList.remove('is-dragover');
                     });
                 });
                 dropzone.addEventListener('drop', function (event) {
                     if (event.dataTransfer && event.dataTransfer.files) {
                         addFiles(event.dataTransfer.files);
                     }
                 });
             }

             // --- сохранение: блокируем кнопку на время запроса, чтобы не наплодить
             // дублей высказывания повторными кликами, и показываем прелоадер ---
             const saveBtn = addModalEl.querySelector('.js-quote-add-save');
             const saveBtnDefaultHtml = saveBtn.innerHTML;
             function setSaveLoading(isLoading) {
                 saveBtn.disabled = isLoading;
                 saveBtn.innerHTML = isLoading
                     ? '<i class="fas fa-spinner fa-spin mr-1"></i>Сохраняем...'
                     : saveBtnDefaultHtml;
             }

             function resetAddQuoteForm() {
                 addModalEl.querySelector('.js-quote-add-text').value = '';
                 addModalEl.querySelector('.js-quote-add-text-count').textContent = '0';
                 addModalEl.querySelector('.js-quote-add-comment').value = '';
                 addModalEl.querySelector('.js-quote-add-comment-count').textContent = '0';
                 addModalEl.querySelector('.js-quote-add-url').value = '';
                 selectedImages = [];
                 renderImagePreviews();
                 recognizeBanner.style.display = 'none';
                 quoteDateTouched = false;
                 setSaveLoading(false);
                 addModalEl.querySelectorAll('.js-contact-tag-picker__selected .b-contact-tag-picker__tag').forEach(function (tag) {
                     tag.remove();
                 });
             }

             addModal.on('hidden.bs.modal', resetAddQuoteForm);

             saveBtn.addEventListener('click', function () {
                 if (saveBtn.disabled) {
                     // запрос уже отправлен — игнорируем повторный клик/двойной клик
                     return;
                 }

                 let text = addModalEl.querySelector('.js-quote-add-text').value.trim();
                 if (!text) {
                     toastr.warning('Введите текст высказывания');
                     return;
                 }

                 let addAnother = addModalEl.querySelector('.js-quote-add-another').checked;
                 let formData = new FormData();
                 formData.append('quote', text);
                 formData.append('platform', platformSelect ? platformSelect.value : '');
                 formData.append('source_url', addModalEl.querySelector('.js-quote-add-url').value.trim());
                 formData.append('occurred_at', addModalEl.querySelector('.js-quote-add-date').value);
                 formData.append('is_exact_date', addModalEl.querySelector('.js-quote-add-exact-date').checked ? '1' : '0');
                 formData.append('comment', addModalEl.querySelector('.js-quote-add-comment').value.trim());
                 formData.append('hashtag_ids', JSON.stringify(quoteTagPicker.getSelectedIds()));
                 selectedImages.forEach(function (file) {
                     formData.append('images[]', file);
                 });

                 setSaveLoading(true);

                 $.ajax({
                     type: 'post',
                     url: quotesStoreUrl,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: formData,
                     contentType: false,
                     processData: false,
                     success: function (response) {
                         if (response.status === true) {
                             if (addAnother) {
                                 toastr.success('Высказывание добавлено');
                                 resetAddQuoteForm();
                             } else {
                                 // страница перезагрузится — кнопку не разблокируем, чтобы
                                 // нельзя было кликнуть ещё раз, пока идёт reload
                                 window.location.reload();
                             }
                         } else {
                             setSaveLoading(false);
                             toastr.error(response.message || 'Не удалось добавить');
                         }
                     },
                     error: function () {
                         setSaveLoading(false);
                         toastr.error('Не удалось добавить');
                     }
                 });
             });
         })();

         // --- высказывания: меню "..." у карточки (открыть/закрыть, см. quotes_tab.blade.php) ---
         const quotesFeed = document.querySelector('.js-quotes-feed');
         if (quotesFeed) {
             function closeQuoteCardMenus(exceptList) {
                 quotesFeed.querySelectorAll('.js-quote-card-menu-list.is-open').forEach(function (list) {
                     if (list !== exceptList) {
                         list.classList.remove('is-open');
                     }
                 });
             }

             quotesFeed.addEventListener('click', function (event) {
                 let toggle = event.target.closest('.js-quote-card-menu-toggle');
                 if (toggle) {
                     let list = toggle.parentElement.querySelector('.js-quote-card-menu-list');
                     let wasOpen = list.classList.contains('is-open');
                     closeQuoteCardMenus(null);
                     list.classList.toggle('is-open', !wasOpen);
                     return;
                 }

                 // клик по пункту меню (ссылка "Открыть оригинал" или кнопка "Удалить") — закрываем
                 if (event.target.closest('.js-quote-card-menu-list')) {
                     closeQuoteCardMenus(null);
                 }
             });
             document.addEventListener('click', function (event) {
                 if (!event.target.closest('.js-quote-card-menu')) {
                     closeQuoteCardMenus(null);
                 }
             });
         }

         // --- высказывания: декоративная кнопка "закладка" (как лайки/комменты — просто
         // переключает иконку, никуда не сохраняется, см. quotes_tab.blade.php) ---
         if (quotesFeed) {
             quotesFeed.addEventListener('click', function (event) {
                 let bookmarkBtn = event.target.closest('.js-quote-card-bookmark');
                 if (!bookmarkBtn) {
                     return;
                 }

                 bookmarkBtn.classList.toggle('is-active');
                 let icon = bookmarkBtn.querySelector('i');
                 icon.classList.toggle('far');
                 icon.classList.toggle('fas');
             });
         }

         // --- высказывания: удаление (карточки в ленте, см. quotes_tab.blade.php) ---
         if (quotesFeed) {
             quotesFeed.addEventListener('click', function (event) {
                 let removeIcon = event.target.closest('.js-contact-list-remove');
                 if (!removeIcon) {
                     return;
                 }

                 let card = removeIcon.closest('.js-quote-card');
                 let id = card.getAttribute('data-id');
                 let baseUrl = removeIcon.getAttribute('data-base-url');

                 $.ajax({
                     type: 'delete',
                     url: baseUrl + '/' + id,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     success: function (response) {
                         if (response.status === true) {
                             window.location.reload();
                         } else {
                             toastr.error(response.message || 'Не удалось удалить');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось удалить');
                     }
                 });
             });
         }


         // --- фотогалерея: добавление/удаление ---
         const photosStoreUrl = page.getAttribute('data-photos-store-action');
         const photosGrid = document.querySelector('.js-contact-photos-grid');
         const contactPhotoInput = document.querySelector('.js-contact-photo-input');

         if (contactPhotoInput) {
             contactPhotoInput.addEventListener('change', function () {
                 if (!contactPhotoInput.files.length) {
                     return;
                 }

                 let file = contactPhotoInput.files[0];
                 let caption = prompt('Подпись к фото (необязательно, например "скрин из инстаграм")') || '';
                 contactPhotoInput.value = '';

                 let formData = new FormData();
                 formData.append('image', file);
                 formData.append('caption', caption);

                 $.ajax({
                     url: photosStoreUrl,
                     type: 'post',
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: formData,
                     contentType: false,
                     processData: false,
                     success: function (response) {
                         if (response.status === true) {
                             let div = document.createElement('div');
                             div.className = 'b-contact-photo';
                             div.setAttribute('data-id', response.photo.id);
                             div.innerHTML =
                                 '<a data-fancybox="contact-photos" href="' + response.photo.image_url + '" class="b-contact-photo__thumb" style="background-image: url(' + response.photo.image_url + ')"></a>' +
                                 '<button type="button" class="b-contact-photo__remove js-contact-photo-remove" title="Удалить"><i class="fas fa-times"></i></button>' +
                                 (response.photo.caption ? '<div class="b-contact-photo__caption">' + escapeHtml(response.photo.caption) + '</div>' : '');
                             photosGrid.appendChild(div);
                             toastr.success('Фото добавлено');
                         } else {
                             toastr.error(response.message || 'Не удалось добавить фото');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось добавить фото');
                     }
                 });
             });
         }

         if (photosGrid) {
             photosGrid.addEventListener('click', function (event) {
                 let btn = event.target.closest('.js-contact-photo-remove');
                 if (!btn) {
                     return;
                 }

                 let photoEl = btn.closest('.b-contact-photo');
                 let photoId = photoEl.getAttribute('data-id');

                 $.ajax({
                     type: 'delete',
                     url: page.getAttribute('data-photo-base-url') + '/' + photoId,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     success: function (response) {
                         if (response.status === true) {
                             photoEl.remove();
                         } else {
                             toastr.error(response.message || 'Не удалось удалить фото');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось удалить фото');
                     }
                 });
             });
         }

         // --- переписки: добавление/удаление (та же механика, что и у фотогалереи выше) ---
         const conversationsStoreUrl = page.getAttribute('data-conversations-store-action');
         const conversationsGrid = document.querySelector('.js-contact-conversations-grid');
         const contactConversationInput = document.querySelector('.js-contact-conversation-input');

         if (contactConversationInput) {
             contactConversationInput.addEventListener('change', function () {
                 if (!contactConversationInput.files.length) {
                     return;
                 }

                 let file = contactConversationInput.files[0];
                 let caption = prompt('Подпись (необязательно, например "Telegram, март 2026")') || '';
                 contactConversationInput.value = '';

                 let formData = new FormData();
                 formData.append('image', file);
                 formData.append('caption', caption);

                 $.ajax({
                     url: conversationsStoreUrl,
                     type: 'post',
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     data: formData,
                     contentType: false,
                     processData: false,
                     success: function (response) {
                         if (response.status === true) {
                             let div = document.createElement('div');
                             div.className = 'b-contact-photo';
                             div.setAttribute('data-id', response.conversation.id);
                             div.innerHTML =
                                 '<a data-fancybox="contact-conversations" href="' + response.conversation.image_url + '" class="b-contact-photo__thumb" style="background-image: url(' + response.conversation.image_url + ')"></a>' +
                                 '<button type="button" class="b-contact-photo__remove js-contact-conversation-remove" title="Удалить"><i class="fas fa-times"></i></button>' +
                                 (response.conversation.caption ? '<div class="b-contact-photo__caption">' + escapeHtml(response.conversation.caption) + '</div>' : '');
                             conversationsGrid.appendChild(div);
                             toastr.success('Скрин добавлен');
                         } else {
                             toastr.error(response.message || 'Не удалось добавить скрин');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось добавить скрин');
                     }
                 });
             });
         }

         if (conversationsGrid) {
             conversationsGrid.addEventListener('click', function (event) {
                 let btn = event.target.closest('.js-contact-conversation-remove');
                 if (!btn) {
                     return;
                 }

                 let el = btn.closest('.b-contact-photo');
                 let id = el.getAttribute('data-id');

                 $.ajax({
                     type: 'delete',
                     url: page.getAttribute('data-conversation-base-url') + '/' + id,
                     headers: {'X-CSRF-TOKEN': csrfToken()},
                     success: function (response) {
                         if (response.status === true) {
                             el.remove();
                         } else {
                             toastr.error(response.message || 'Не удалось удалить скрин');
                         }
                     },
                     error: function () {
                         toastr.error('Не удалось удалить скрин');
                     }
                 });
             });
         }
     })();

 });
