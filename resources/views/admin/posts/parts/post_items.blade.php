<div class="modal fade" id="modal-add-hashtag" tabindex="-1" role="dialog" aria-labelledby="modalAddHashtagLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddHashtagLabel"></h5>
            </div>
            <div class="modal-body">
                <div class="b-add-tag">
                    <div class="input-group input-group-sm add-tag">
                        <input id="search-input-2" type="text" class="form-control" data-action="{{ route('search.hashtag') }}">
                        <span class="input-group-append">
                            <button type="button" class="btn btn-info btn-flat" id="add-tag" data-action="{{ route('hashtags.store') }}">Добавить тег</button>
                        </span>
                    </div>
                    <ul id="b-search__results-2" class="b-search__results-2"></ul>
                    <ul id="b-selected-tags-2" class="b-selected-tags-2"></ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-action="save-request">Сохранить</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

@foreach($posts as $post)
    <div class="grid-item" data-id="{{ $post->id }}">
        <div class="b-card__user-options">
            <a href="#" id="triggerId" class="button" data-toggle="dropdown" aria-haspopup="true"
               aria-expanded="false">
                <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="triggerId">
                <a class="dropdown-item text-dark" href="{{ route('posts.show', [$post->id]) }}"><i class="fas fa-eye mr-1"></i>Перейти к публикации</a>
                <a class="dropdown-item text-dark" href="{{ route('posts.edit', [$post->id]) }}"><i class="fas fa-edit mr-1"></i>Редактировать</a>
                <span class="dropdown-item text-dark" data-action="add-hashtag" data-url="{{ route('posts.update', [$post->id]) }}" data-post-id="{{ $post->id }}"><i class="fas fa-tags mr-1"></i>Добавить тег</span>
                {{--                    <span class="dropdown-item text-dark"><i class="fas fa-link mr-1"></i>Копировать ссылку</span>--}}
                <span class="dropdown-item text-dark" data-action="copy-text"><i class="fas fa-copy mr-1"></i>Скопировать текст</span>
                <span class="dropdown-item text-dark"><i class="fas fa-cloud-download-alt mr-1"></i>Скачать изображение</span>
                <span class="dropdown-item text-dark"><i class="fas fa-share mr-1"></i>Поделиться</span>
                <span class="dropdown-item text-danger" data-action="delete" data-url="{{ route('posts.destroy', [$post->id]) }}" data-page-id="posts"><i class="fa fa-trash mr-1"></i>Удалить</span>
            </div>
        </div>

        <div class="b-card__content">
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
                    <li data-id="{{ $hashtag->id }}" data-title="{{ $hashtag->title }}"><a rel="tag" href="#">#{{ $hashtag->title }}</a></li>
                @endforeach
            </ul>
        </div><!-- /.card-footer -->

        @if (!empty($post->is_used))
            <div class="ribbon ribbon-success float-end"><i class="fas fa-solid fa-check mr-1"></i>Использовано</div>
        @endif
    </div>
@endforeach
