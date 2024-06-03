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
 };

 $(document).ready(function() {

     // initialize Masonry
     // Masonry + ImagesLoaded
     var $grid = $('.grid');
     $grid.imagesLoaded().progress( function() {
         // init Masonry after all images have loaded
         $grid.masonry(masonryOptions);
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
     $(window).scroll(function() {
         if (window.location.pathname === '/admin_panel/posts') {
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

         let requestUrl = '?page=' + page;

         let savedHashtags = localStorage.getItem('hashtags');
         console.log('loadMoreData savedHashtags' + savedHashtags);

         if (savedHashtags !== undefined && savedHashtags !== null && savedHashtags !== '{}') {
             requestUrl =  $('#js-b-search')[0].getAttribute('data-action') + requestUrl;
             console.log(requestUrl);
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
                     $('.ajax-load').html("No more records found");
                     return;
                 }

                 // $grid.append(html).masonry('appended', html);
                 // $grid.masonry();

                 $('.ajax-load').hide();

                 var $content = $( html );
                 $grid.append( $content );
                 $grid.masonry( 'appended', $content) ;

                 // wait for images and Masonry layout to load
                 var $gridd = $('.grid').imagesLoaded( function() {
                     // init Masonry after all images have loaded
                     $gridd.masonry('reloadItems');
                     $gridd.masonry(masonryOptions);

                     // enable scrolling when images and Masonry layout have loaded
                     document.body.style.overflow = '';
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

         let creationForm = $("#creationform");

         //let creationFormData = new FormData(creationForm[0]);
         //console.log(creationFormData);

         //creationFormData.append('test', 'test');

         let images = localStorage.getItem('images');
         console.log('PARSE' + JSON.parse(images));
         //creationForm.append('<input type="hidden" name="images" value="' + images + '" />');
         //console.log('creationForm' + creationForm);

         let storageKey = 'hashtags2';
         let hashtags = localStorage.getItem(storageKey);

         // for ( let i = 0; i < hashtagsElements.length; ++i) {
         //     console.log(hashtagsElements[i].getAttribute('data-id'));
         //     console.log(hashtagsElements[i].getAttribute('data-name'));
         //     let hashtag = {
         //         id: hashtagsElements[i].getAttribute('data-id'),
         //         title: hashtagsElements[i].getAttribute('data-name'),
         //     };
         //     console.log(JSON.stringify(hashtag));
         //     //console.log('===' + hashtagsElements[i].getAttribute('data-id'));
         //     hashtags.sex = 'Male';
         //     //parseInt(hashtagsElements[i].getAttribute('data-id'))
         // }

         console.log('hashtags' + hashtags);

         let textareaContent = tinyMCE.activeEditor.getContent({format: 'raw'});

         var numberOfPosts = $('input[type="radio"]:checked').val();
         //console.log('Выбрана кнопка с значением: ' + numberOfPosts);
         var groupPosts = document.getElementById("group-posts").checked;
         //console.log('checked: ' + groupPosts);

         //get the action-url of the form
         var actionurl = $('#creationform').attr('action');
         console.log('actionurl' + actionurl);

         let data = creationForm.serializeArray();
         data.push({name: 'images', value: images});
         data.push({name: 'hashtags', value: hashtags});
         data.push({name: 'text', value: textareaContent});
         data.push({name: 'numberOfPosts', value: numberOfPosts});
         data.push({name: 'groupPosts', value: groupPosts});

         //console.log(data);
         //return true;

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

                     resetHashtagsFromForm(storageKey);


                     toastr.success(result.message);
                     //showAlert(result.message, 'alert-info', 'fa-check');
                 } else {
                     toastr.error(result.message);
                     //showAlert(result.message, 'alert-danger', 'fa-ban');
                 }




                 //window.location.href = result.redirect;

                 // if (result.indexOf(errors) === -1) {
                 //     if (result.indexOf(redirect) !== -1) {
                 //         window.location.href = result[redirect];
                 //     } else {
                 //         window.location.href = "http://profhelp.com.ua";
                 //     }
                 //
                 // }
             },
         });

         //creationForm.submit();

         //return true;
     });

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

     $('#editform input[type="file"]').on('change', function (event) {
         console.log(this.files[0]);

         let formData = new FormData();
         formData.append('files[]', this.files[0]);
         formData.append('username', 'Chris');
         console.log(formData);

         for (let value of formData.values()) {
             console.log('=========' + value);
         }

         let tokenValue = '';
         let inputToken = $("[name='_token']");
         for (let token of inputToken) {
             tokenValue = token.value;
         }

         let url = this.getAttribute('data-action');

         $.ajax({
             url: url,
             headers: {
                 'X-CSRF-TOKEN': tokenValue,
             },
             type: 'POST',
             data: formData,
             cache       : false,
             dataType    : 'json',
             // отключаем обработку передаваемых данных, пусть передаются как есть
             processData : false,
             // отключаем установку заголовка типа запроса. Так jQuery скажет серверу что это строковой запрос
             contentType : false,
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

                     let uploadedImage = response.images;
                     console.log(uploadedImage);

                     localStorage.setItem('images', JSON.stringify(uploadedImage));
                     console.log(localStorage.getItem('images'));

                     // Отобразить загруженные картинки
                     if (uploadedImage) {

                         let imageSrc = '';
                         let imageOriginalSrc = '';

                         for (let key in uploadedImage) {
                             if (uploadedImage.hasOwnProperty(key)) {
                                 console.log(uploadedImage[key]);
                                 console.log(uploadedImage[key]['name']);

                                 if (uploadedImage[key]['original'] !== undefined) {
                                     imageOriginalSrc = uploadedImage[key]['original'];
                                 }
                                 if (uploadedImage[key]['medium'] !== undefined) {
                                     imageSrc = uploadedImage[key]['medium'];
                                 } else if (uploadedImage[key]['original'] !== undefined) {
                                     imageSrc = uploadedImage[key]['original'];
                                 }
                             }
                         }

                         console.log(imageSrc);

                         $('#js-post-image img').attr('src', '/storage/' + imageSrc);
                         $('#js-post-image').attr('data-src', '/storage/' + imageOriginalSrc);

                     }
                 }
             },
             error: function(response) {
                 console.log(response);
                 debugger;
             },
         });

     });

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
                         form.submit();
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
                 let element3 = button.closest('tr');
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

         let postElement = $('#hashtags-index tr[data-id='+postId+']');

         $('#modal-delete-item').modal('hide');

         $.ajax({
             type: 'delete',
             url: actionUrl,
             data: {'id': postId},
             success: function (response) {
                 console.log(response);

                 if (response.status === true) {
                     postElement.remove();
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

 });
