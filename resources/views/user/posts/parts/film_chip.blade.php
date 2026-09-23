{{-- Один чип вайба/достоинства/недостатка (parts/film_fields.blade.php).
     Значение — data-value: эмодзи только подпись и в сохранённые данные не попадает (film-fields.js).
     @param array $option ['text' => ..., 'emoji' => ..., 'active' => bool] — из PostFilm::chipOptions() --}}
<button type="button" class="b-pin-chip {{ $option['active'] ? 'is-active' : '' }}" data-value="{{ $option['text'] }}" aria-pressed="{{ $option['active'] ? 'true' : 'false' }}">@if ($option['emoji'] !== '')<span class="b-pin-chip__emoji" aria-hidden="true">{{ $option['emoji'] }}</span>@endif{{ $option['text'] }}</button>
