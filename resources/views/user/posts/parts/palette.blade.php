{{-- Палитра обложки поста (posts.palette, App\Components\ImagePalette) на странице поста.
     Клик по цвету (в полоске или в списке) — "Все посты" с поиском по этому цвету (?color=..., admin.js);
     иконка рядом копирует HEX — скрипт в show.blade.php (.js-copy-color).
     @param array $palette [['hex' => '#D8C3A5', 'share' => 0.34], ...] --}}
<div class="b-post-show__section">
    <div class="b-post-show__section-head">
        <i class="fas fa-palette"></i>
        <span class="b-post-show__section-label">Палитра</span>
        @isset($postId)
            {{-- правка — в блоке "Палитра" на странице редактирования (edit.blade.php, #palette) --}}
            <a href="{{ route('posts.edit', $postId) }}#palette" class="b-post-palette__edit" title="Изменить палитру">
                <i class="fas fa-pencil-alt"></i> Изменить
            </a>
        @endisset
    </div>
    {{-- полоска: ширина цвета = его доля на картинке --}}
    <div class="b-post-palette__bar">
        @foreach ($palette as $color)
            <a href="{{ route('posts.index', ['color' => ltrim($color['hex'], '#')]) }}"
               style="flex: {{ $color['share'] }}; background: {{ $color['hex'] }};"
               title="Найти посты в цвете {{ $color['hex'] }}" aria-label="Найти посты в цвете {{ $color['hex'] }}"></a>
        @endforeach
    </div>
    <div class="b-post-palette__colors">
        @foreach ($palette as $color)
            <span class="b-post-palette__color">
                <a href="{{ route('posts.index', ['color' => ltrim($color['hex'], '#')]) }}" class="b-post-palette__search"
                   title="{{ round($color['share'] * 100) }}% картинки{{ !empty($color['background']) ? ', фон' : '' }} — найти посты в этом цвете">
                    <span class="b-post-palette__swatch" style="background: {{ $color['hex'] }};"></span>
                    {{ $color['hex'] }}
                    {{-- фон (ImagePalette) — последним, главные цвета — у того, что на картинке --}}
                    @if (!empty($color['background']))
                        <span class="b-post-palette__bg-label">фон</span>
                    @endif
                </a>
                <button type="button" class="b-post-palette__copy js-copy-color" data-hex="{{ $color['hex'] }}"
                        title="Скопировать {{ $color['hex'] }}" aria-label="Скопировать {{ $color['hex'] }}">
                    <i class="far fa-copy"></i>
                </button>
            </span>
        @endforeach
    </div>
</div>
