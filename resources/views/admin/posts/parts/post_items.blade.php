@foreach($posts as $post)
    <div class="grid-item">
        <div class="b-card__content">
            <div class="b-card__user-options">
                <a href="#" id="triggerId" class="button" data-toggle="dropdown" aria-haspopup="true"
                   aria-expanded="false">
                    <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="triggerId">
                    <a class="dropdown-item text-dark" href="{{ route('posts.show', [$post->id]) }}"><i class="fas fa-eye mr-1"></i>Перейти к публикации</a>
                    <a class="dropdown-item text-dark" href="{{ route('posts.edit', [$post->id]) }}"><i class="fas fa-edit mr-1"></i>Редактировать</a>
                    <a class="dropdown-item text-dark" href="#"><i class="fas fa-tags mr-1"></i>Добавить тег</a>
                    <a class="dropdown-item text-dark" href="#"><i class="fas fa-link mr-1"></i>Копировать ссылку</a>
                    <a class="dropdown-item text-dark" href="#" data-action="copy-text"><i class="fas fa-copy mr-1"></i>Скопировать текст</a>
                    <a class="dropdown-item text-dark" href="#"><i class="fas fa-cloud-download-alt mr-1"></i>Скачать изображение</a>
                    <a class="dropdown-item text-dark" href="#"><i class="fas fa-share mr-1"></i>Поделиться</a>
                    <span class="dropdown-item text-danger" data-action="delete" data-url="{{ route('posts.destroy', [$post->id]) }}"><i class="fa fa-trash mr-1"></i>Удалить</span>
                </div>
            </div>

            @if (!empty($post->cover_image))
                <div class="b-card__content__image scale">
                    <img src="/storage{{ $post->cover_image }}" alt="Content img">
                </div>
            @endif

            <div class="b-card__content__text">
                @if (!empty($post->title))
                    <a href="#" class="title">{{ $post->title }}</a>
                @endif
                @if (!empty($post->content))
                    {!! substr($post->content, 0, 2000) !!}
                @endif
            </div>
        </div><!-- /.b-card__content -->

        <div class="b-card__footer">
            <ul class="tags">
                @foreach($post->hashtags as $hashtag)
                    <li><a rel="tag" href="#">#{{ $hashtag->title }}</a></li>
                @endforeach
            </ul>
        </div><!-- /.card-footer -->

        @if (!empty($post->is_used))
            <div class="ribbon ribbon-success float-end"><i class="fas fa-solid fa-check mr-1"></i>Использовано</div>
        @endif
    </div>
@endforeach
