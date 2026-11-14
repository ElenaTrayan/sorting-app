{{-- Палитра обложки поста (posts.palette, App\Components\ImagePalette) на странице поста.
     Клик по цвету (в полоске или в списке) — "Все посты" с поиском по этому цвету (?color=..., admin.js);
     иконка рядом копирует HEX — скрипт в show.blade.php (.js-copy-color).
     Блок сворачивается (<details>): у постов, где он не главное, — свёрнут сразу ($collapsed, например у пина
     "Товар"), тогда в заголовке видна только тонкая полоска цветов.
     @param array $palette [['hex' => '#D8C3A5', 'share' => 0.34], ...]
     @param bool $collapsed — свернуть по умолчанию --}}
<details class="b-post-palette" @if (empty($collapsed)) open @endif>
    <summary class="b-post-show__section-head b-post-palette__summary">
        <i class="fas fa-palette"></i>
        <span class="b-post-show__section-label">Палитра</span>
        {{-- свёрнутый блок: полоска цветов прямо в заголовке (не ссылки — клик раскрывает блок) --}}
        <span class="b-post-palette__mini" aria-hidden="true">
            @foreach ($palette as $color)
                <span style="flex: {{ $color['share'] }}; background: {{ $color['hex'] }};"></span>
            @endforeach
        </span>
        <i class="fas fa-chevron-down b-post-palette__chevron" aria-hidden="true"></i>
        @isset($postId)
            {{-- правка — в блоке "Палитра" на странице редактирования (edit.blade.php, #palette) --}}
            <a href="{{ route('posts.edit', $postId) }}#palette" class="b-post-palette__edit" title="Изменить палитру">
                <i class="fas fa-pencil-alt"></i> Изменить
            </a>
        @endisset
    </summary>
    <div class="b-post-show__section b-post-palette__body">
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
</details>
