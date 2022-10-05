 console.log('======= TEST =======');

 tinymce.init({
     selector: 'textarea#editor',
     plugins: 'image code',
     toolbar: 'undo redo | link image | code',
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



 // function sendCreatePostForm() {
 //     //e.preventDefault();
 //
 //     console.log('TEST sendCreatePostForm');
 //
 //     // document.getElementsByClassName('text-block')[0].style.display = 'block';
 //     // document.getElementById('payRetailersPIX_form').style.display = 'none';
 //     //document.creationform.submit();
 // }

 $(document).ready(function() {

     //очищаем images в localStorage
     if (window.location.href !== '/admin_panel/posts/create') {
         if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
              localStorage.removeItem('images');
         }
         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             localStorage.removeItem('hashtags');
         }
     }

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
     $(':file').on('change', function () {
         //$('#add-project-form').trigger('click');
         //upload($(this).prop('files'));
     });

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
                 } else {
                     formData.append('files[]', files[i]);
                 }
             }

         } else {
             for( let i = 0; i < files.length; ++i ) {
                 console.log('***' + JSON.stringify(files));
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

         console.log('========= YES ---------');

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
                                 console.log(uploadedImages[key]['name']);

                                 let img = '<div class="image" data-name="' + uploadedImages[key]['name'] + '">' +
                                     '<span style="background-image: url(/storage/' + uploadedImages[key]['small'] + ')"></span>' +
                                     '<i class="js-delete-image fas fa-times-circle"></i>' +
                                     '</div>';

                                 // Добавить в контейнер
                                 $('.js-upload-image-section .images').append(img);
                             }
                         }

                     }
                 }
             },
             error: function(response) {
             },
         });
     }

     $('.js-close-error-block').on('click', function(e) {
         $(this).closest('.js-error-block').removeClass('active');
     });

     $(document).on('click touchstart', '.js-delete-image', function(){
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

         if (delete images[name] === true) {
             console.log('=====images=====' + JSON.stringify(images));
             localStorage.setItem('images', JSON.stringify(images));

             image.remove();
         }

     });

     $('#submit-creation-form').on('click', function(e) {
         e.preventDefault();

         console.log('CLICK');
         // let form = $(this);
         // console.log('this' + form);
         let creationForm = $("#creationform");

         //let creationFormData = new FormData(creationForm[0]);
         //console.log(creationFormData);

         //creationFormData.append('test', 'test');

         let images = localStorage.getItem('images');
         console.log('PARSE' + JSON.parse(images));
         //creationForm.append('<input type="hidden" name="images" value="' + images + '" />');
         //console.log('creationForm' + creationForm);

         let hashtags = localStorage.getItem('hashtags');

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

         //get the action-url of the form
         var actionurl = $('#creationform').attr('action');
         console.log('actionurl' + actionurl);

         let data = creationForm.serializeArray();
         data.push({name: 'images', value: images});
         data.push({name: 'hashtags', value: hashtags});

         console.log(data);
         //do your own request an handle the results
         $.ajax({
             url: actionurl,
             type: 'post',
             dataType: 'application/json',
             data: data, //images
             success: function(response) {
                 console.log(response);
             }
         });

         //creationForm.submit();

         //return true;
     });

     //страница создания поста - добавить хештег
     $('#add-tag').on('click', function(e) {
         console.log('CLICK');

         let value = $("#search-hashtag").val();
         console.log(value);

         localStorage.setItem('hashtags', JSON.stringify());
     });

     $('#search-hashtag').on('keyup', function () {
         let searchUrl = $(this)[0].getAttribute('data-action');

         let searchValue = $(this).val();
         console.log(searchValue);

         $.ajax({
             type: 'get',
             url: searchUrl,
             data: {'search': searchValue},
             success: function (response) {
                 console.log(response);

                 let hashtags = response.hashtags;

                 let foundHashtagsContainer = $('#found-hashtags');

                 var myNode = document.getElementById("found-hashtags");
                 while (myNode.firstChild) {
                     myNode.removeChild(myNode.firstChild);
                 }


                 // let foundHashtagsElements = foundHashtagsContainer.find('li');
                 //
                 // for ( let i = 0; i < foundHashtagsElements.length; ++i) {
                 //     console.log('===' + foundHashtagsElements[i].getAttribute('data-id'));
                 //     alreadyFoundHashtags.push(parseInt(foundHashtagsElements[i].getAttribute('data-id')));
                 // }

                 if (hashtags) {

                     for (let key in hashtags) {
                         let hashtagElement = '<li class="hashtag" data-id="' + hashtags[key]['id'] + '" data-name="' + hashtags[key]['title'] + '">' + hashtags[key]['title'] + '</li>';

                         // Добавить в контейнер
                         foundHashtagsContainer.append(hashtagElement);
                     }

                 }


             }
         });

     });

     $.ajaxSetup({ headers: { 'csrftoken' : '{{ csrf_token() }}' } });

     let tagsContainer = $('#tags');

     $(document).on('click touchstart', '#found-hashtags li', function() {
         console.log('CLICK-CLICK');

         let hashtagId = $(this)[0].getAttribute('data-id');
         let hashtagTitle = $(this)[0].getAttribute('data-name');

         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             let savedHashtags = localStorage.getItem('hashtags');
             console.log('savedHashtags' + savedHashtags);
             savedHashtags = JSON.parse(savedHashtags);
             console.log('savedHashtags' + savedHashtags);

             //проверить есть ли ключ в массиве
             //если нет - добавить значение в массив
             if(savedHashtags.includes(hashtagId) === false) {
                 let hashtags = savedHashtags;
                 hashtags.push(hashtagId);
                 console.log('hashtags' + hashtags);

                 localStorage.setItem('hashtags', JSON.stringify(hashtags));

                 let hashtagElement = '<li class="hashtag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">' + hashtagTitle + '<i class="js-delete-image fas fa-times-circle"></i></li>';
                 tagsContainer.append(hashtagElement);
             }

         } else {
             let hashtags = [];
             hashtags.push(hashtagId);

             localStorage.setItem('hashtags', JSON.stringify(hashtags));

             let hashtagElement = '<li class="hashtag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">' + hashtagTitle + '<i class="js-delete-image fas fa-times-circle"></i></li>';
             tagsContainer.append(hashtagElement);
         }

     });

     //ПОИСК - поиск тегов при вводе букв в input и вывод результатов в b-search__results
     $(document).on('input', '#search-input', function() {
         console.log('click');

         let searchUrl = $('#js-b-search')[0].getAttribute('data-action');
         console.log(searchUrl);

         let searchValue =  $(this).val();
         console.log(searchValue);

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         $.ajax({
             type: 'post',
             url: searchUrl,
             data: {'search': searchValue},
             success: function (response) {
                 console.log(response);

                 let hashtags = response.hashtags;

                 let foundHashtagsContainer = $('#b-search__results');
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

     });

     //добавить хештег в список выбранных хештегов, после клика на хештег из результатов поиска
     $(document).on('click touchstart', '#b-search__results li', function() {
         console.log('CLICK-CLICK');

         let hashtagId = $(this)[0].getAttribute('data-id');
         let hashtagTitle = $(this)[0].getAttribute('data-name');

         console.log('hashtagId = ' + hashtagId);
         console.log('hashtagTitle = ' + hashtagTitle);

         let containerSelectedHashtags = $('#b-search__field__tags-container__tags');
         console.log('containerSelectedHashtags' + containerSelectedHashtags);

         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             let savedHashtags = localStorage.getItem('hashtags');
             console.log('savedHashtags' + savedHashtags);
             savedHashtags = JSON.parse(savedHashtags);
             console.log('savedHashtags' + savedHashtags);

             //проверить есть ли ключ в массиве
             //если нет - добавить значение в массив
             if(savedHashtags.includes(hashtagId) === false) {

                 let hashtagElement = '<span class="tag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">#' + hashtagTitle + '<span class="icon font-icon fas close"></span></li>';
                 containerSelectedHashtags.append(hashtagElement);

                 let containerSelectedHashtagsWidth = containerSelectedHashtags.width();
                 console.log('b-search__field__tags-container__tags = ' + containerSelectedHashtagsWidth);

                 let fieldTagsContainerWidth = $('#b-search__field__tags-container').width();
                 console.log('#b-search__field__tags-container = ' + fieldTagsContainerWidth);
                 let fieldTagsContainerHeight = $('#b-search__field__tags-container').height();

                 let elementSearchInput = $('#b-search__input');

                 let left = containerSelectedHashtagsWidth + 10;
                 console.log('left = ' + left);
                 let width = fieldTagsContainerWidth - containerSelectedHashtagsWidth + 10;
                 console.log('width = ' + width);

                 if (width === 10) {
                     $('#b-search__field__tags-container').css("height", fieldTagsContainerHeight + 44);
                 } else {
                     elementSearchInput.css("left", left);
                     elementSearchInput.css("width", width);
                 }

                 //добавляем id хештега в массив hashtags в localStorage
                 let hashtags = savedHashtags;
                 hashtags.push(hashtagId);
                 console.log('hashtags' + hashtags);
                 localStorage.setItem('hashtags', JSON.stringify(hashtags));

                 let foundHashtagsContainer = $('#b-search__results');
                 foundHashtagsContainer.empty(); //удалить все предыдущие результаты

                 $('#search-input').val('');
                 $('#search-input').focus();
             }

         } else {
             let hashtagElement = '<span class="tag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">#' + hashtagTitle + '<span class="icon font-icon fas close"></span></li>';
             containerSelectedHashtags.append(hashtagElement);

             let containerSelectedHashtagsWidth = containerSelectedHashtags.innerWidth();
             console.log('b-search__field__tags-container__tags = ' + containerSelectedHashtagsWidth);

             let fieldTagsContainerWidth = $('#b-search__field__tags-container').innerWidth();
             console.log('#b-search__field__tags-container = ' + fieldTagsContainerWidth);

             let elementSearchInput = $('#b-search__input');

             let left = containerSelectedHashtagsWidth + 10;
             console.log('left = ' + left);
             let width = fieldTagsContainerWidth - containerSelectedHashtagsWidth - 10;
             console.log('width = ' + width);

             elementSearchInput.css("left", left);
             elementSearchInput.css("width", width);

             //добавляем id хештега в массив hashtags в localStorage
             let hashtags = [];
             hashtags.push(hashtagId);
             console.log('hashtags' + hashtags);
             localStorage.setItem('hashtags', JSON.stringify(hashtags));

             let foundHashtagsContainer = $('#b-search__results');
             foundHashtagsContainer.empty(); //удалить все предыдущие результаты

             $('#search-input').val('');
             $('#search-input').focus();
         }

     });


     //ПОИСК - вывод связанных хештегов

     // $('.js-b-popup-1-open').click(function() {
     //     $('.js-b-popup-1').addClass('active');
     //     return false;
     // });
     //
     // $('.js-b-popup-1-close').click(function() {
     //     $(this).parents('.js-b-popup-1').removeClass('active');
     //     $(this).closest('.b-popup-1').removeClass('maximize-popup');
     //     return false;
     // });

     // $(document).keydown(function(e) {
     //     if (e.keyCode === 27) {
     //         e.stopPropagation();
     //         $('.js-b-popup-1').removeClass('active');
     //     }
     // });

     // $('.js-b-popup-1').click(function(e) {
     //     if ($(e.target).closest('.b-popup-1').length == 0) {
     //         $(this).removeClass('active');
     //     }
     // });

     // $('.js-b-popup-1-maximize').click(function(e) {
     //     $(e.target).closest('.b-popup-1').toggleClass('maximize-popup');
     // });


     // $('figure').on('click', function () {
     //     $('#add-project-form').trigger('click');
     // });

     // При перетаскивании файлов в форму, подсветить
     // $('section').on('dragover', function (e) {
     //     $(this).addClass('dd');
     //     e.preventDefault();
     //     e.stopPropagation();
     // });
     //
     // // Предотвратить действие по умолчанию для события dragenter
     // $('section').on('dragenter', function (e) {
     //     e.preventDefault();
     //     e.stopPropagation();
     // });
     //
     // $('section').on('dragleave', function (e) {
     //     $(this).removeClass('dd');
     // });

     // $('section').on('drop', function (e) {
     //     $(this).addClass('active');
     //
     //     var url = $(e.target).closest("#add-project-form").attr('data-action');
     //     console.log(url);
     //
     //     if (e.originalEvent.dataTransfer) {
     //         if (e.originalEvent.dataTransfer.files.length) {
     //             e.preventDefault();
     //             e.stopPropagation();
     //
     //             console.log(e.originalEvent.dataTransfer);
     //
     //             // Вызвать функцию загрузки. Перетаскиваемые файлы содержатся
     //             // в свойстве e.originalEvent.dataTransfer.files
     //             upload(e.originalEvent.dataTransfer.files, url);
     //
     //             //$('#add-project-form').trigger('click');
     //         }
     //     }
     // });

     //Загрузка файлов классическим образом - через модальное окно
     // $(':file').on('change', function () {
     //     //$('#add-project-form').trigger('click');
     //     //upload($(this).prop('files'));
     // });

     // $('#add-project-form').on('submit', function(event){
     //     event.preventDefault();
     //
     //     var url = $(this).attr('data-action');
     //
     //     console.log(url);
     //     console.log(this);
     //
     //     var formData = new FormData(this);
     //
     //     upload(formData, url);
     // });

     // // Функция загрузки файлов
     // function upload(files, url = '') {
     //     console.log("UPLOAD");
     //     //console.log(formData);
     //     //console.log(url);
     //
     //     var formData = new FormData($("#add-project-form")[0]);
     //
     //     console.log(files);
     //     console.log(formData);
     //     console.log($("#add-project-form")[0]);
     //
     //     for( var i = 0; i < files.length; ++i ) {
     //         formData.append('files[]', files[i]);
     //     }
     //
     //     $.ajax({
     //         url: url,
     //         method: 'POST',
     //         data: formData,
     //         dataType: 'JSON',
     //         contentType: false,
     //         cache: false,
     //         processData: false,
     //         beforeSend: function () {
     //             $('section').removeClass('dd');
     //
     //             // Перед загрузкой файла удалить старые ошибки и показать индикатор
     //             $('.error').text('').hide();
     //             $('.progress').show();
     //
     //             // Установить прогресс-бар на 0
     //             $('.progress-bar').css('width', '0');
     //             $('.progress-value').text('0 %');
     //         },
     //         success:function(response)
     //         {
     //             console.log(response);
     //
     //             if (response.Error) {
     //                 $('.error').text(response.Error).show();
     //                 $('.progress').hide();
     //             }
     //             else {
     //                 $('.progress-bar').css('width', '100%');
     //                 $('.progress-value').text('100 %');
     //
     //                 // Отобразить загруженные картинки
     //                 if (response.Files) {
     //                     // Обертка для картинки со ссылкой
     //                     var img = '<div class="image">' +
     //                         '<span style="background-image: url(0)"></span>' +
     //                         '<i class="js-delete-image fas fa-times-circle"></i>' +
     //                         '</div>';
     //
     //                     var imageBlock = $('.js-images-block');
     //                     var creationFormData = new FormData($("#creationform")[0]);
     //                     console.log("=== creationFormData ===");
     //                     console.log(creationFormData);
     //
     //                     for (var i = 0; i < response.Files.length; i++) {
     //                         // Сгенерировать вставляемый элемент с картинкой
     //                         // (символ 0 заменяем ссылкой с помощью регулярного выражения)
     //                         var element = $(img.replace(/0/g, response.Files[i]['small']));
     //                         // Добавить в контейнер
     //                         $('.js-upload-image-section .images').append(element);
     //
     //                         imageBlock.append('<div class="image">\n' +
     //                             '        <img src="' + response.Files[i]['small'] + '" data-original="' + response.Files[i]['original'] + '" data-name="' + response.Files[i]['name'] + '" data-extension="' + response.Files[i]['extension'] + '">\n' +
     //                             '      </div>');
     //
     //                         creationFormData.append('images[]', response.Files[i]['original']);
     //                     }
     //
     //                     console.log(creationFormData);
     //                 }
     //             }
     //         },
     //         error: function(response) {
     //         },
     //         xhrFields: { // Отслеживаем процесс загрузки файлов
     //             onprogress: function (e) {
     //                 if (e.lengthComputable) {
     //                     // Отображение процентов и длины прогресс бара
     //                     var perc = e.loaded / 100 * e.total;
     //                     $('.progress-bar').css('width', perc + '%');
     //                     $('.progress-value').text(perc + ' %');
     //                 }
     //             }
     //         },
     //     });
     // }

     // $('.js-images').on('click', '.js-delete-image', function () {
     //     console.log('TEST=======');
     //     // $(this).css('background', rndColor);
     //
     //     $(this).closest('.image').remove();
     //     // $('.js-b-popup-1').addClass('active');
     // });
     //
     // $('.js-btn-upload-image').click(function() {
     //     let images = document.querySelectorAll('.js-images > .image');
     //     console.log(images);
     //     localStorage.setItem('upload_images', 'test');
     // });
     //
     // $("body").on('DOMSubtreeModified', ".js-images", function() {
     //     console.log('CHANGE');
     // });

     // '.js-delete-image',

 });
