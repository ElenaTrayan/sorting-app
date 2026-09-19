{{-- Карточка товара в "Мои запасы". $item — StockItem. --}}
@php
    $statusClass = in_array($item->expiry_status, ['expired', 'soon']) ? ' b-stock-item-card--' . $item->expiry_status : '';
@endphp
<div class="b-stock-item-card{{ $statusClass }}">
    <div class="b-stock-item-card__top">
        <div class="b-stock-item-card__photo" @if ($item->image) style="background-image:url({{ $item->image }})" @endif>
            @if (!$item->image)<i class="fas fa-box"></i>@endif
        </div>
        <div style="flex:1;min-width:0;">
            <div class="b-stock-item-card__name">{{ $item->name }}</div>
            @if ($item->brand)<div class="b-stock-item-card__brand">{{ $item->brand }}</div>@endif
        </div>
        <div class="b-stock-more">
            <button type="button" class="b-stock-more__btn"><i class="fas fa-ellipsis-v"></i></button>
            <div class="b-stock-more__menu">
                <button type="button" data-toggle="modal" data-target="#modal-edit-stock-item-{{ $item->id }}"><i class="fas fa-pen"></i> Редактировать</button>
                <button type="button" data-stock-post-url="{{ route('stock.inventory.save-as-regular', $item) }}" data-success-message="Сохранено как постоянная покупка"><i class="fas fa-repeat"></i> Сохранить как постоянную покупку</button>
                <button type="button" data-stock-post-url="{{ route('stock.inventory.add-to-shopping-list', $item) }}" data-success-message="Добавлено в список покупок" data-no-reload><i class="fas fa-shopping-basket"></i> В список покупок</button>
                <button type="button" class="b-stock-more__danger" data-stock-delete-url="{{ route('stock.inventory.destroy', $item) }}" data-stock-delete-title="Удалить «{{ $item->name }}»?"><i class="fas fa-trash"></i> Удалить</button>
            </div>
        </div>
    </div>

    <div class="b-stock-item-card__meta">
        <span><i class="fas fa-cubes"></i> {{ $item->quantity == floor($item->quantity) ? (int) $item->quantity : $item->quantity }} {{ $item->unit }}</span>
        @if ($item->location)<span><i class="fas fa-map-marker-alt"></i> {{ $item->location->name }}</span>@endif
        @if ($item->price)<span><i class="fas fa-hryvnia-sign"></i> {{ number_format($item->price, 0, ',', ' ') }} грн</span>@endif
    </div>

    <div class="b-stock-item-card__footer">
        @include('admin.stock.parts.expiry_badge', ['status' => $item->expiry_status, 'daysLeft' => $item->days_left])
        @if ($item->expires_at)<span style="font-size:12px;color:#9099b8;">до {{ $item->expires_at->format('d.m.Y') }}</span>@endif
    </div>
</div>

@include('admin.stock.parts.item_form_modal', ['mode' => 'edit', 'item' => $item, 'locations' => $locations, 'categories' => $categories])
