@extends('layouts.user_layout')

@section('title', 'Стирка')

@push('head-styles')
    @include('user.wardrobe.parts.head_assets')
@endpush

@push('navbar-breadcrumb')
    @include('user.wardrobe.parts.breadcrumb', ['page' => 'Стирка'])
@endpush

@php use App\Models\WardrobeItem; @endphp

@section('content')
<section class="content b-stock b-wardrobe">
    <div class="container-fluid">

        <div class="b-stock-page-header">
            <div>
                <h1 class="b-stock-page-header__title">Стирка</h1>
                <p class="b-stock-page-header__subtitle">
                    Что стирать вместе. Давно не стиранные — первыми (больше {{ WardrobeItem::WASH_OVERDUE_DAYS }} дней или ни разу)
                </p>
            </div>
        </div>

        @include('user.wardrobe.parts.section_tabs', ['active' => 'laundry'])

        @if ($specialWash->isNotEmpty())
            <div class="b-laundry-group b-laundry-group--special">
                <div class="b-laundry-group__head">
                    <div class="b-laundry-group__title"><i class="fas fa-tint"></i> Особая стирка <span class="b-wardrobe-count">{{ $specialWash->count() }}</span></div>
                    <div class="b-laundry-group__sub">Пятна, которые нужно вывести отдельно. Помогло — нажмите «Исправлено»</div>
                </div>
                <div class="b-laundry-special">
                    @foreach ($specialWash as $item)
                        <div class="b-laundry-special__item">
                            <a href="{{ route('wardrobe.items.index', ['edit_item' => $item->id]) }}" class="b-laundry-special__photo"
                               @if ($item->image_url) style="background-image:url('{{ $item->image_url }}')" @endif>
                                @if (!$item->image_url)<i class="fas fa-tshirt"></i>@endif
                            </a>
                            <div class="b-laundry-special__body">
                                <a href="{{ route('wardrobe.items.index', ['edit_item' => $item->id]) }}" class="b-laundry-special__name">{{ $item->name }}</a>
                                <div class="b-wardrobe-card__chips">
                                    @foreach (WardrobeItem::labelsFor($item->special_wash_types, WardrobeItem::SPECIAL_WASH_TYPES) as $label)
                                        <span class="b-stock-chip">{{ $label }}</span>
                                    @endforeach
                                </div>
                                @if ($item->special_wash_note)
                                    <div class="b-laundry-special__note">{{ $item->special_wash_note }}</div>
                                @endif
                                <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm"
                                        data-stock-post-url="{{ route('wardrobe.items.special-wash-fixed', $item) }}"
                                        data-success-message="Отмечено: пятно исправлено"><i class="fas fa-check"></i> Исправлено</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!$groups && $noGroup->isEmpty())
            <div class="b-stock-empty">
                <i class="fas fa-soap b-stock-empty__icon"></i>
                <p>В гардеробе пока нет вещей</p>
            </div>
        @endif

        {{-- группы стирки: выбрать вещи и отметить "Постирала" — дата стирки станет сегодняшней --}}
        @foreach ($groups as $key => $group)
            @php $overdue = $group['items']->filter->isWashOverdue()->count(); @endphp
            <div class="b-laundry-group js-laundry-group">
                <div class="b-laundry-group__head">
                    <div>
                        <div class="b-laundry-group__title">{{ $group['label'] }} <span class="b-wardrobe-count">{{ $group['items']->count() }}</span></div>
                        <div class="b-laundry-group__sub">
                            @if ($overdue)<span class="b-laundry-overdue">давно не стирались: {{ $overdue }}</span>@else всё постирано недавно @endif
                        </div>
                    </div>
                    <div class="b-laundry-group__actions">
                        <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm js-laundry-select-all">Выбрать все</button>
                        <button type="button" class="b-stock-btn b-stock-btn--primary b-stock-btn--sm js-laundry-washed" data-url="{{ route('wardrobe.laundry.washed') }}" disabled>
                            <i class="fas fa-check"></i> Постирала <span class="js-laundry-selected-count"></span>
                        </button>
                    </div>
                </div>
                <div class="b-wardrobe-tiles">
                    @foreach ($group['items'] as $item)
                        @include('user.wardrobe.parts.laundry_tile', ['item' => $item])
                    @endforeach
                </div>
            </div>
        @endforeach

        @if ($noGroup->isNotEmpty())
            <div class="b-laundry-group js-laundry-group">
                <div class="b-laundry-group__head">
                    <div>
                        <div class="b-laundry-group__title">Группа не определена <span class="b-wardrobe-count">{{ $noGroup->count() }}</span></div>
                        <div class="b-laundry-group__sub">Укажите у вещи цвет или группу стирки — и она попадёт в свою группу</div>
                    </div>
                    <div class="b-laundry-group__actions">
                        <button type="button" class="b-stock-btn b-stock-btn--outline b-stock-btn--sm js-laundry-select-all">Выбрать все</button>
                        <button type="button" class="b-stock-btn b-stock-btn--primary b-stock-btn--sm js-laundry-washed" data-url="{{ route('wardrobe.laundry.washed') }}" disabled>
                            <i class="fas fa-check"></i> Постирала <span class="js-laundry-selected-count"></span>
                        </button>
                    </div>
                </div>
                <div class="b-wardrobe-tiles">
                    @foreach ($noGroup as $item)
                        @include('user.wardrobe.parts.laundry_tile', ['item' => $item])
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</section>
@endsection

@push('footer-scripts')
    @include('user.wardrobe.parts.footer_scripts')
@endpush
