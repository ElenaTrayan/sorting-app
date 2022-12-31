 console.log('======= TEST =======');

let tinymceTextarea = $('textarea#editor');
if (tinymceTextarea !== null && tinymceTextarea !== undefined) {
    tinymce.init({
        selector: 'textarea#editor',
        plugins: 'image codesample code',
        toolbar: 'undo redo | link image | code',
        codesample_languages: [
            {text: 'PHP', value: 'php'},
            {text: 'JavaScript', value: 'javascript'},
            {text: 'HTML/XML', value: 'markup'},
            {text: 'CSS', value: 'css'}
        ],
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

 $(document).ready(function() {

     let searchInput1 = '#search-input';
     let searchInput2 = '#search-input-2';

     let containerSelectedHashtagsTags1 = '#b-search__field__tags-container__tags';
     let containerSelectedHashtagsTags2 = '#b-selected-tags-2';

     //очищаем images в localStorage
     if (window.location.href !== '/admin_panel/posts/create') {
         if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
              localStorage.removeItem('images');
         }
         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             localStorage.removeItem('hashtags');
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
                 console.log(response);
                 debugger;
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

     function resetImagesFromForm() {
         if (localStorage.getItem('images') !== undefined && localStorage.getItem('images') !== null) {
             localStorage.removeItem('images');
         }

         $('.js-images').empty();
     }

     function resetHashtagsFromForm() {
         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             localStorage.removeItem('hashtags');
         }

         $(containerSelectedHashtagsTags2).empty();
     }

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

         let textareaContent = tinyMCE.activeEditor.getContent({format: 'raw'});

         //get the action-url of the form
         var actionurl = $('#creationform').attr('action');
         console.log('actionurl' + actionurl);

         let data = creationForm.serializeArray();
         data.push({name: 'images', value: images});
         data.push({name: 'hashtags', value: hashtags});
         data.push({name: 'text', value: textareaContent});

         console.log(data);
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

                     resetHashtagsFromForm();


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

     //ПОИСК - поиск тегов при вводе букв в input и вывод результатов в b-search__results
     $(document).on('input', searchInput1, function() {
         console.log('click');

         let searchUrl = $('#js-b-search__field')[0].getAttribute('data-action');
         console.log(searchUrl);

         let searchValue =  $(this).val();
         console.log(searchValue);

         let foundHashtagsContainer = $('#b-search__results');

         searchHashtag(searchValue, searchUrl, foundHashtagsContainer);

     });

     $(document).keypress(function (e) {
         if (e.which === 13) {
             //alert("Pressed");

             e.preventDefault();
             e.stopPropagation();
         }
     });

     //ПОИСК - поиск тегов при вводе букв в input и вывод результатов в b-search__results
     $(document).on('keyup', searchInput2, function(event) {
         let searchUrl = $(this)[0].getAttribute('data-action');

         let searchValue = $(this).val();
         console.log(searchValue);

         console.log('click2');

         if (event.keyCode === 13) {
             console.log('ENTER');
             document.getElementById("add-tag").click();

             return false;
         }

         let foundHashtagsContainer = $('#b-search__results-2');

         searchHashtag(searchValue, searchUrl, foundHashtagsContainer);

     });

     function searchHashtag(searchValue, searchUrl, foundHashtagsContainer) {
         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         let hashtagsValue = '';
         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             hashtagsValue = localStorage.getItem('hashtags');
         }

         $.ajax({
             type: 'post',
             url: searchUrl,
             data: {'search': searchValue, 'hashtags': hashtagsValue},
             success: function (response) {
                 console.log(response);

                 let hashtags = response.hashtags;

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

         let hashtagId = $(this)[0].getAttribute('data-id');
         let hashtagTitle = $(this)[0].getAttribute('data-name');

         console.log('hashtagId = ' + hashtagId);
         console.log('hashtagTitle = ' + hashtagTitle);

         let containerSelectedHashtags = $('#b-search__field__tags-container');

         let containerSelectedHashtagsTags = $(containerSelectedHashtagsTags1);
         console.log('containerSelectedHashtagsTags' + containerSelectedHashtagsTags);

         let foundHashtagsContainer = $('#b-search__results');

         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             let savedHashtags = localStorage.getItem('hashtags');
             console.log('savedHashtags' + savedHashtags);
             savedHashtags = JSON.parse(savedHashtags);
             console.log('savedHashtags' + savedHashtags);

             //проверить есть ли ключ в массиве
             //если нет - добавить значение в массив
             if(savedHashtags.includes(hashtagId) === false) {

                 addHashtagToSelectedHashtags(hashtagId, hashtagTitle, searchInput1, foundHashtagsContainer, null, '#b-search__input');

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

         } else {

             addHashtagToSelectedHashtags(hashtagId, hashtagTitle, searchInput1, foundHashtagsContainer, null, '#b-search__input');

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

     });

     //добавить хештег в список выбранных хештегов, после клика на хештег из результатов поиска
     $(document).on('click touchstart', '#b-search__results-2 li', function() {
         console.log('CLICK-CLICK 2');

         let hashtagId = $(this)[0].getAttribute('data-id');
         let hashtagTitle = $(this)[0].getAttribute('data-name');

         console.log('hashtagId = ' + hashtagId);
         console.log('hashtagTitle = ' + hashtagTitle);

         let foundHashtagsContainer = $('#b-search__results-2');
         let selectedHashtagsContainer = '#b-selected-tags-2';

         addHashtagToSelectedHashtags(hashtagId, hashtagTitle, searchInput2, foundHashtagsContainer, selectedHashtagsContainer);
     });

     //добавить хэштег к выбранным хэштегам
     function addHashtagToSelectedHashtags(hashtagId, hashtagTitle, searchInput, foundHashtagsContainer, selectedHashtagsContainer = null, bSearchInput = null) {

         let hashtagElement = '<span class="tag" data-id="' + hashtagId + '" data-name="' + hashtagTitle + '">#' + hashtagTitle + '<span class="icon font-icon fas close"></span></li>';

         if (selectedHashtagsContainer !== null) {
             $(selectedHashtagsContainer).append(hashtagElement);
         } else {
             $(hashtagElement).insertBefore(bSearchInput);
         }

         if (localStorage.getItem('hashtags') !== undefined && localStorage.getItem('hashtags') !== null) {
             let savedHashtags = localStorage.getItem('hashtags');
             console.log('savedHashtags' + savedHashtags);
             savedHashtags = JSON.parse(savedHashtags);
             console.log('savedHashtags' + savedHashtags);

             //проверить есть ли ключ в массиве. если нет - добавить значение в массив
             if(savedHashtags.includes(hashtagId) === false) {
                 addHashtagToStorage(hashtagId, savedHashtags, foundHashtagsContainer);
                 focusOnInput(searchInput);
             }
         } else {
             addHashtagToStorage(hashtagId, [], foundHashtagsContainer);
             focusOnInput(searchInput);
         }
     }

     //добавляем id хештега в массив hashtags в localStorage
     function addHashtagToStorage(hashtagId, hashtags, foundHashtagsContainer) {
         hashtags.push(hashtagId);
         console.log('hashtags' + hashtags);
         localStorage.setItem('hashtags', JSON.stringify(hashtags));

         //удалить все предыдущие результаты
         foundHashtagsContainer.empty();
     }

     function focusOnInput(searchInput) {
         $(searchInput).val('');
         $(searchInput).focus();
     }



     $(document).on('click touchstart', containerSelectedHashtagsTags1 + ' .tag .close', function() {
         console.log("**************");
         console.log($(this));

         removeHashtag($(this));
         focusOnInput(searchInput1);
     });

     $(document).on('click touchstart', containerSelectedHashtagsTags2 + ' .tag .close', function() {
         console.log("**************");
         console.log($(this));

         removeHashtag($(this));
         focusOnInput(searchInput2);
     });

     function removeHashtag(closeBtn) {
         let tag = closeBtn.closest('.tag');
         let tagId = tag.data('id');
         console.log('tagId = ' + tagId);

         let savedHashtags = localStorage.getItem('hashtags');
         console.log('savedHashtags' + savedHashtags);
         savedHashtags = JSON.parse(savedHashtags);
         console.log('savedHashtags' + savedHashtags);

         var position = savedHashtags.indexOf(tagId.toString());
         console.log('position' + position);

         savedHashtags.splice(position, 1);

         let hashtags = savedHashtags;
         console.log('hashtags' + hashtags);
         localStorage.setItem('hashtags', JSON.stringify(hashtags));

         tag.remove();
     }

     //добавить хештег, если его нет в найденных и выбранных - кнопка Добавить тег
     $(document).on('click touchstart', '#add-tag', function() {

         let savedHashtags = localStorage.getItem('hashtags');
         console.log('savedHashtags' + savedHashtags);
         //savedHashtags = JSON.parse(savedHashtags);
         //console.log('savedHashtags' + savedHashtags);

         let title = $(searchInput2)[0].value;

         let url = $('#add-tag')[0].getAttribute('data-action');
         console.log(url);
         console.log(title);

         if (title !== '') {
             $.ajax({
                 type: 'post',
                 url: url,
                 data: {'hashtags': savedHashtags, 'title': title},
                 success: function (response) {
                     console.log(response);

                     if (response.hasOwnProperty('info')) {
                         console.log(response.info);

                         let foundHashtagsContainer = $('#b-search__results-2');
                         let selectedHashtagsContainer = '#b-selected-tags-2';

                         addHashtagToSelectedHashtags(response.info.id, response.info.title, searchInput2, foundHashtagsContainer, selectedHashtagsContainer);
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
                 console.log(response);

                 // let hashtags = response.hashtags;
                 //
                 // let foundHashtagsContainer = $('#b-search__results');
                 // foundHashtagsContainer.empty(); //удалить все предыдущие результаты
                 //
                 // if (hashtags) {
                 //
                 //     for (let key in hashtags) {
                 //         let hashtagElement = '<li data-id="' + hashtags[key]['id'] + '" data-name="' + hashtags[key]['title'] + '">' + hashtags[key]['title'] + '</li>';
                 //
                 //         // Добавить в контейнер
                 //         foundHashtagsContainer.append(hashtagElement);
                 //     }
                 //
                 // }


             }
         });

     });



     $(document).on('click touchstart', '#posts-categories-index [data-action=delete-request]', function() {
         console.log('delete-request');
         console.log($(this));
         console.log($(this).data('actionUrl'));
         let actionUrl = $(this).data('actionUrl');
         let categoryId = $(this).data('elementId');

         console.log('categoryId ' + categoryId);
         console.log('actionUrl ' + actionUrl);

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         let categoryElement = $('tr[data-id='+categoryId+']');

         $('#exampleModal').modal('hide');

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
                     //showAlert(response.message, 'alert-info', 'fa-check');
                 } else {
                     if (response.message !== null && response.message !== undefined) {
                         toastr.error(response.message);
                     } else {
                         toastr.error('Some error has occurred');
                     }
                     //showAlert(response.message, 'alert-danger', 'fa-ban');
                 }
             }
         });

     });

     $(document).on('shown.bs.modal','#exampleModal', function (event) {
         console.log('TESTTT');
         let button = $(event.relatedTarget); // Button that triggered the modal
         console.log($(event.relatedTarget));
         console.log($(this));

         let path = window.location.pathname;
         let id = path.replace('/admin_panel/', '');
         console.log(id);

         let messages = getMessage(id);
         console.log(messages);
         let modal = $(this);

         if (messages.has('title')) {
             modal.find('.modal-title').text(messages.get('title'));
         }

         if (messages.has('sub-title')) {
             modal.find('.modal-body').text(messages.get('sub-title'));
         }

         let element = getElementInfo(id, button); //button.closest('tr');
         // let categoryId = element.data('id');
         // let actionUrl = element.data('url');

         console.log(element);

         if (element.has('element-id')) {
             console.log('---------- TEST ---------');
             $('[data-action=delete-request]').data('elementId', element.get('element-id'));
         }

         if (element.has('action-url')) {
             $('[data-action=delete-request]').data('actionUrl', element.get('action-url'));
         }

         //var recipient = button.data('whatever'); // Extract info from data-* attributes

         //modal.find('.modal-body input').val(recipient);
     });

     $(document).on('click touchstart', '#posts-categories-index [data-action=delete]', function() {
         console.log('delete');
         $('#exampleModal').modal('show', $(this));
     });

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
                 let element2 = button.closest('.b-card');
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
             case "Cherries":
                 console.log("Cherries are $3.00 a pound.");
                 break;
             case "Mangoes":
             case "Papayas":
                 console.log("Mangoes and papayas are $2.79 a pound.");
                 break;
             default:
                 console.log("Sorry, we are out of  ");
         }
     }

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
             case "Cherries":
                 console.log("Cherries are $3.00 a pound.");
                 break;
             case "Mangoes":
             case "Papayas":
                 console.log("Mangoes and papayas are $2.79 a pound.");
                 break;
             default:
                 console.log("Sorry, we are out of  ");
         }
     }




     //удаление постов
     $(document).on('click touchstart', '.b-list-of-posts [data-action=delete]', function() {
         console.log('delete');
         $('#exampleModal').modal('show', $(this));
     });

     $(document).on('click touchstart', '#posts-index [data-action=delete-request]', function() {
         console.log('delete-request');
         console.log($(this));
         console.log($(this).data('actionUrl'));
         let actionUrl = $(this).data('actionUrl');
         let postId = $(this).data('elementId');

         console.log('postId ' + postId);
         console.log('actionUrl ' + actionUrl);

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         let postElement = $('.b-card[data-id='+postId+']'); //$(this).closest('.b-card');

         $('#exampleModal').modal('hide');

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
                     //showAlert(response.message, 'alert-info', 'fa-check');
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

     //удаление хештегов на странице http://sorting.local/admin_panel/hashtags
     $(document).on('click touchstart', '#hashtags-index [data-action=delete]', function() {
         console.log('delete');
         $('#exampleModal').modal('show', $(this));
     });

     $(document).on('click touchstart', '#hashtags-index [data-action=delete-request]', function() {
         console.log('delete-request');
         console.log($(this));
         console.log($(this).data('actionUrl'));
         let actionUrl = $(this).data('actionUrl');
         let postId = $(this).data('elementId');

         console.log('postId ' + postId);
         console.log('actionUrl ' + actionUrl);

         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name = "csrf-token"]').attr('content')
             }
         });

         let postElement = $('#hashtags-index tr[data-id='+postId+']'); //$(this).closest('.b-card');

         $('#exampleModal').modal('hide');

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
                     //showAlert(response.message, 'alert-info', 'fa-check');
                 } else {
                     if (response.message !== null && response.message !== undefined) {
                         toastr.error(response.message);
                     } else {
                         toastr.error('Some error has occurred');
                     }
                     //showAlert(response.message, 'alert-danger', 'fa-ban');
                 }
             }
         });
     });

     $(document).on('keyup', '#posts-categories-edit #title', function(event) {
         let aliasInput = $('#posts-categories-edit #alias');
         //$(this).val()
         //let str = transliter($(this).val().slice(-1)); //transliter(event.target.value);
         $('#exampleModal').modal('show', $(this));
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

     //функцию isEmpty(obj), которая возвращает true, если у объекта нет свойств, иначе false
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
