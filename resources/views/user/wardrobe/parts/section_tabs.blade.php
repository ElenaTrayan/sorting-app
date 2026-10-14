{{-- Переключатель подстраниц раздела "Мой гардероб". $active: items|outfits|storages --}}
<div class="b-wardrobe-sections">
    <a href="{{ route('wardrobe.items.index') }}" class="b-wardrobe-sections__item @if ($active === 'items') is-active @endif"><i class="fas fa-tshirt"></i> Одежда</a>
    <a href="{{ route('wardrobe.outfits.index') }}" class="b-wardrobe-sections__item @if ($active === 'outfits') is-active @endif"><i class="fas fa-layer-group"></i> Образы</a>
    <a href="{{ route('wardrobe.storages.index') }}" class="b-wardrobe-sections__item @if ($active === 'storages') is-active @endif"><i class="fas fa-box-open"></i> Места хранения</a>
</div>
