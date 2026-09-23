{{-- $demo: массив из Contact::getDemoAttribute() --}}
<ul class="b-contact-info-list">
    @if ($demo['zodiac'])
        <li><span class="b-contact-info-list__label"><i class="fas fa-star mr-2"></i>Знак зодиака</span><span>{{ $demo['zodiac'] }}</span></li>
    @endif
    <li><span class="b-contact-info-list__label"><i class="fas fa-{{ $demo['regime'] === 'Сова' ? 'moon' : 'sun' }} mr-2"></i>Режим</span><span>{{ $demo['regime'] }}</span></li>
    <li><span class="b-contact-info-list__label"><i class="far fa-clock mr-2"></i>Распорядок дня</span><span>{{ $demo['daily_routine'] }}</span></li>
    <li><span class="b-contact-info-list__label"><i class="fas fa-umbrella-beach mr-2"></i>Свободное время</span><span>{{ $demo['free_time'] }}</span></li>
    <li><span class="b-contact-info-list__label"><i class="fas fa-exclamation-circle mr-2"></i>Самый большой страх</span><span>{{ $demo['fear'] }}</span></li>
</ul>
