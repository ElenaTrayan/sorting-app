{{-- Плитка вещи на странице "Стирка" (laundry.blade.php): клик по фото — выбрать для "Постирала" (wardrobe.js,
     initLaundry); название — ссылка на форму вещи. Под названием — когда стиралась, давно не стиранные выделены.
     $item — WardrobeItem --}}
@php $ago = $item->washedAgo(); @endphp
<div class="b-wardrobe-tile b-laundry-tile @if ($item->isWashOverdue()) is-overdue @endif">
    <button type="button" class="b-wardrobe-tile__photo b-laundry-tile__photo js-laundry-tile" data-id="{{ $item->id }}"
            @if ($item->image_url) style="background-image:url('{{ $item->image_url }}')" @endif title="Выбрать">
        @if (!$item->image_url)<i class="fas fa-tshirt"></i>@endif
        @if ($item->needsSpecialWash())<span class="b-wardrobe-badge b-wardrobe-badge--stain" title="Нужна особая стирка"><i class="fas fa-tint"></i></span>@endif
        <span class="b-wardrobe-tile__check"><i class="fas fa-check"></i></span>
    </button>
    <a href="{{ route('wardrobe.items.index', ['edit_item' => $item->id]) }}" class="b-wardrobe-tile__name" title="Открыть вещь">{{ $item->name }}</a>
    <span class="b-laundry-tile__washed">{{ $ago ? 'стирка: ' . $ago : 'не стиралась' }}</span>
</div>
