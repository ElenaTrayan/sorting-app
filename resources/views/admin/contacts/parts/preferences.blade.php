{{-- $demo: массив из Contact::getDemoAttribute() --}}
<div class="b-contact-pref-columns">
    <div>
        <div class="b-contact-pref-columns__title b-contact-pref-columns__title--likes"><i class="fas fa-heart mr-1"></i>Нравится</div>
        <div class="b-contact-pref-columns__tags">
            @foreach ($demo['likes'] as $like)
                <span class="b-contact-pref-tag b-contact-pref-tag--like">{{ $like }}</span>
            @endforeach
        </div>
    </div>
    <div>
        <div class="b-contact-pref-columns__title b-contact-pref-columns__title--dislikes"><i class="fas fa-times-circle mr-1"></i>Раздражает</div>
        <div class="b-contact-pref-columns__tags">
            @foreach ($demo['dislikes'] as $dislike)
                <span class="b-contact-pref-tag b-contact-pref-tag--dislike">{{ $dislike }}</span>
            @endforeach
        </div>
    </div>
</div>
