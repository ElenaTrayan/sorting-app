@extends('layouts.admin_layout')

@section('title', 'Мой список подарков')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Мой список подарков</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('homeAdmin') }}">Главная</a></li>
                        <li class="breadcrumb-item active">Мой список подарков</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="wishlists-index" id="wishlists-index" data-store-action="{{ route('wishlists.store') }}">

                @if (session('success'))
                    @push('footer-scripts')
                        <script>toastr.success('{{ session('success') }}');</script>
                    @endpush
                @elseif(session('errors'))
                    @push('footer-scripts')
                        <script>toastr.error('{{ session('errors') }}');</script>
                    @endpush
                @endif

                <div class="card-header" style="display: flex; justify-content: end;">
                    <button type="button" class="btn btn-block btn-outline-info js-wishlist-create-btn" style="width: 200px;">
                        <i class="fas fa-plus mr-1"></i> Создать вишлист
                    </button>
                </div>

                @if ($wishlists->isEmpty())
                    <div class="b-wishlists-empty">
                        <i class="fas fa-gift b-wishlists-empty__icon"></i>
                        <p>Пока нет ни одного вишлиста</p>
                        <button type="button" class="btn btn-info js-wishlist-create-btn">Создать первый вишлист</button>
                    </div>
                @else
                    <div class="b-wishlists-grid">
                        @foreach ($wishlists as $wishlist)
                            @php
                                $occasion = collect($occasions)->firstWhere('slug', $wishlist->occasion);
                            @endphp
                            <a href="{{ route('wishlists.show', $wishlist->id) }}" class="b-wishlist-card">
                                <div class="b-wishlist-card__icon"><i class="fas fa-gift"></i></div>
                                <div class="b-wishlist-card__title">{{ $wishlist->title }}</div>
                                @if ($occasion)
                                    <div class="b-wishlist-card__occasion">
                                        <i class="fas {{ $occasion['icon'] }} mr-1"></i>{{ $occasion['title'] }}
                                    </div>
                                @elseif ($wishlist->occasion)
                                    <div class="b-wishlist-card__occasion">{{ $wishlist->occasion }}</div>
                                @endif
                                <div class="b-wishlist-card__meta">{{ $wishlist->items_count }} {{ __('желаний') }}</div>
                            </a>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </section>

    <div class="modal fade" id="modal-create-wishlist" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Создать вишлист</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="wishlist-create-title">Название</label>
                        <input type="text" id="wishlist-create-title" class="form-control" placeholder="Мой вишлист">
                    </div>

                    <label>Повод (необязательно)</label>
                    <div class="b-occasion-picker js-occasion-picker">
                        @foreach ($occasions as $occasion)
                            <button type="button" class="b-occasion-picker__item js-occasion-picker__item" data-slug="{{ $occasion['slug'] }}">
                                <i class="fas {{ $occasion['icon'] }} mr-1"></i>{{ $occasion['title'] }}
                            </button>
                        @endforeach
                        <button type="button" class="b-occasion-picker__item js-occasion-picker__more">
                            <i class="fas fa-ellipsis-h mr-1"></i>Больше
                        </button>
                    </div>
                    <input type="text" id="wishlist-create-occasion-custom" class="form-control mt-2" placeholder="Свой повод" style="display: none;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary js-wishlist-create-confirm">Создать</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                </div>
            </div>
        </div>
    </div>
@endsection
