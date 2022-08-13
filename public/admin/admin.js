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

 $(document).ready(function($) {
     $('.js-b-popup-1-open').click(function() {
         $('.js-b-popup-1').addClass('active');
         return false;
     });

     $('.js-b-popup-1-close').click(function() {
         $(this).parents('.js-b-popup-1').removeClass('active');
         $(this).closest('.b-popup-1').removeClass('maximize-popup');
         return false;
     });

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

     $('.js-b-popup-1-maximize').click(function(e) {
         $(e.target).closest('.b-popup-1').toggleClass('maximize-popup');
     });


     // $('figure').on('click', function () {
     //     $('#add-project-form').trigger('click');
     // });

     // При перетаскивании файлов в форму, подсветить
     $('section').on('dragover', function (e) {
         $(this).addClass('dd');
         e.preventDefault();
         e.stopPropagation();
     });

     // Предотвратить действие по умолчанию для события dragenter
     $('section').on('dragenter', function (e) {
         e.preventDefault();
         e.stopPropagation();
     });

     $('section').on('dragleave', function (e) {
         $(this).removeClass('dd');
     });

     $('section').on('drop', function (e) {
         $(this).addClass('active');

         var url = $(e.target).closest("#add-project-form").attr('data-action');
         console.log(url);

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

     $('#add-project-form').on('submit', function(event){
         event.preventDefault();

         var url = $(this).attr('data-action');

         console.log(url);
         console.log(this);

         var formData = new FormData(this);

         upload(formData, url);
     });

     // Функция загрузки файлов
     function upload(files, url = '') {
         console.log("UPLOAD");
         //console.log(formData);
         //console.log(url);

         var formData = new FormData($("#add-project-form")[0]);

         console.log(files);
         console.log(formData);
         console.log($("#add-project-form")[0]);

         for( var i = 0; i < files.length; ++i ) {
             formData.append('files[]', files[i]);
         }

         $.ajax({
             url: url,
             method: 'POST',
             data: formData,
             dataType: 'JSON',
             contentType: false,
             cache: false,
             processData: false,
             beforeSend: function () {
                 $('section').removeClass('dd');

                 // Перед загрузкой файла удалить старые ошибки и показать индикатор
                 $('.error').text('').hide();
                 $('.progress').show();

                 // Установить прогресс-бар на 0
                 $('.progress-bar').css('width', '0');
                 $('.progress-value').text('0 %');
             },
             success:function(response)
             {
                 console.log(response);

                 if (response.Error) {
                     $('.error').text(response.Error).show();
                     $('.progress').hide();
                 }
                 else {
                     $('.progress-bar').css('width', '100%');
                     $('.progress-value').text('100 %');

                     // Отобразить загруженные картинки
                     if (response.Files) {
                         // Обертка для картинки со ссылкой
                         var img = '<div class="image">' +
                             '<span style="background-image: url(0)"></span>' +
                             '<i class="js-delete-image fas fa-times-circle"></i>' +
                             '</div>';

                         var imageBlock = $('.js-images-block');
                         var creationFormData = new FormData($("#creationform")[0]);
                         console.log("=== creationFormData ===");
                         console.log(creationFormData);

                         for (var i = 0; i < response.Files.length; i++) {
                             // Сгенерировать вставляемый элемент с картинкой
                             // (символ 0 заменяем ссылкой с помощью регулярного выражения)
                             var element = $(img.replace(/0/g, response.Files[i]['small']));
                             // Добавить в контейнер
                             $('.js-upload-image-section .images').append(element);

                             imageBlock.append('<div class="image">\n' +
                                 '        <img src="' + response.Files[i]['small'] + '" data-original="' + response.Files[i]['original'] + '" data-name="' + response.Files[i]['name'] + '" data-extension="' + response.Files[i]['extension'] + '">\n' +
                                 '      </div>');

                             creationFormData.append('images[]', response.Files[i]['original']);
                         }

                         console.log(creationFormData);
                     }
                 }
             },
             error: function(response) {
             },
             xhrFields: { // Отслеживаем процесс загрузки файлов
                 onprogress: function (e) {
                     if (e.lengthComputable) {
                         // Отображение процентов и длины прогресс бара
                         var perc = e.loaded / 100 * e.total;
                         $('.progress-bar').css('width', perc + '%');
                         $('.progress-value').text(perc + ' %');
                     }
                 }
             },
         });
     }

     $('.js-images').on('click', '.js-delete-image', function () {
         console.log('TEST=======');
         // $(this).css('background', rndColor);

         $(this).closest('.image').remove();
         // $('.js-b-popup-1').addClass('active');
     });

     $('.js-btn-upload-image').click(function() {
         let images = document.querySelectorAll('.js-images > .image');
         console.log(images);
         localStorage.setItem('upload_images', 'test');
     });

     // $('.js-delete-image').click(function(e) {
     //     console.log(e);
     //     $(e.target).closest('.image').fadeOut();
     //     return false;
     // });

     // $('.images').change(function() {
     //     console.log('CHANGE');
     // });

     $("body").on('DOMSubtreeModified', ".js-images", function() {
         console.log('CHANGE');
         // $('.js-b-popup-1').addClass('active');
         //$('.js-b-popup-1').css('display', 'block');
         //console.log($('.js-b-popup-1'));
     });

     // '.js-delete-image',

 });
