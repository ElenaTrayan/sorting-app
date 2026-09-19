{{--
    Бейдж статуса срока годности — переиспользуется на Overview, "Мои запасы" и т.д.
    $status: expired/soon/month/ok/none (см. StockItem::getExpiryStatusAttribute())
    $daysLeft: int|null
--}}
@php
    $labels = [
        'expired' => 'Просрочено',
        'soon' => 'Истекает скоро',
        'month' => 'Истекает в этом месяце',
        'ok' => 'Срок в порядке',
        'none' => 'Без срока',
    ];
    $icons = [
        'expired' => 'fa-exclamation-triangle',
        'soon' => 'fa-clock',
        'month' => 'fa-calendar-alt',
        'ok' => 'fa-check-circle',
        'none' => 'fa-minus-circle',
    ];
    if (($daysLeft ?? null) !== null && in_array($status, ['soon', 'month'])) {
        $labels['soon'] = $daysLeft <= 0 ? 'Истекает сегодня' : 'Осталось ' . $daysLeft . ' дн.';
        $labels['month'] = 'Осталось ' . $daysLeft . ' дн.';
    }
@endphp
<span class="b-stock-status b-stock-status--{{ $status }}"><i class="fas {{ $icons[$status] ?? 'fa-circle' }}"></i> {{ $labels[$status] ?? $status }}</span>
