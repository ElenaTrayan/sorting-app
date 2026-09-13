<?php

namespace App\Http\Controllers\Admin;

use App\Components\ImageReader;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactConversation;
use App\Models\ContactFact;
use App\Models\ContactPhoto;
use App\Models\ContactQuote;
use App\Models\ContactQuoteImage;
use App\Models\ContactSocialLink;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use function PHPUnit\Framework\isJson;

/**
 * Раздел "База контактов" — карточки людей с хештегами, фото, соцсетями,
 * высказываниями и фактами. На странице контакта также показываются посты
 * (фото и видео), привязанные теми же хештегами, что и у контакта.
 */
class ContactController extends Controller
{
    public function index()
    {
        $contacts = Contact::where('user_id', Auth::id())
            ->with('hashtags')
            ->latest()
            ->get();

        return view('admin.contacts.index', [
            'contacts' => $contacts,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'residence' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp',
            'hashtag_ids' => 'nullable|string',
        ]);

        $contact = Contact::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'birth_date' => $request->birth_date ?: null,
            'residence' => $request->residence,
            'phone' => $request->phone,
        ]);

        if ($request->hasFile('photo')) {
            $contact->photo = json_encode($this->storeContactImage($request->file('photo')));
        }

        $this->syncHashtags($contact, $request->hashtag_ids);
        $contact->save();

        return response()->json([
            'status' => true,
            'message' => 'Контакт добавлен',
            'redirect' => route('contacts.show', $contact->id),
        ]);
    }

    public function show(Contact $contact)
    {
        $this->authorizeOwner($contact);

        $contact->load(['hashtags', 'photos', 'conversations', 'socialLinks', 'quotes.hashtags', 'quotes.images', 'facts']);

        $hashtagIds = $contact->hashtags->pluck('id');
        $relatedPosts = collect();

        if ($hashtagIds->isNotEmpty()) {
            // одного общего хештега мало, чтобы считать пост "про этого человека" — у контакта
            // могут быть и широкие категориальные теги (например, "мужчины"), которые есть
            // и на совершенно посторонних постах. Требуем совпадения минимум по двум хештегам
            // (либо по одному, если у контакта их и всего один — иначе такие контакты вообще
            // никогда не находили бы посты)
            $minMatches = min(2, $hashtagIds->count());

            $candidatePosts = Post::whereHas('hashtags', function ($q) use ($hashtagIds) {
                $q->whereIn('hashtags.id', $hashtagIds);
            })
                ->where('user_id', Auth::id())
                ->with('hashtags')
                ->latest()
                ->get();

            $relatedPosts = $candidatePosts->filter(function ($post) use ($hashtagIds, $minMatches) {
                return $post->hashtags->pluck('id')->intersect($hashtagIds)->count() >= $minMatches;
            })->values();

            foreach ($relatedPosts as $post) {
                if (!empty($post->small_image) && isJson($post->small_image)) {
                    $smallImage = json_decode($post->small_image, true);
                    $post['cover_image'] = $smallImage['path'] ?? (array_values($smallImage)[0]['path'] ?? '');
                } elseif (!empty($post->medium_image) && isJson($post->medium_image)) {
                    $mediumImage = json_decode($post->medium_image, true);
                    $post['cover_image'] = $mediumImage['path'] ?? (array_values($mediumImage)[0]['path'] ?? '');
                } elseif (!empty($post->original_image) && isJson($post->original_image)) {
                    $originalImage = json_decode($post->original_image, true);
                    $post['cover_image'] = $originalImage['path'] ?? (array_values($originalImage)[0]['path'] ?? '');
                }
            }
        }

        return view('admin.contacts.show', [
            'contact' => $contact,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'name' => 'required|string|max:255',
            'birth_date' => 'nullable|date',
            'residence' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp',
            'hashtag_ids' => 'nullable|string',
        ]);

        $contact->name = $request->name;
        $contact->birth_date = $request->birth_date ?: null;
        $contact->residence = $request->residence;
        $contact->phone = $request->phone;

        if ($request->hasFile('photo')) {
            $this->deleteContactPhotoFile($contact->photo_data);
            $contact->photo = json_encode($this->storeContactImage($request->file('photo')));
        }

        $this->syncHashtags($contact, $request->hashtag_ids);
        $contact->save();

        return response()->json(['status' => true, 'message' => 'Контакт обновлён']);
    }

    public function destroy(Contact $contact)
    {
        $this->authorizeOwner($contact);

        $this->deleteContactPhotoFile($contact->photo_data);
        foreach ($contact->photos as $photo) {
            $this->deleteContactPhotoFile($photo->image_data);
        }
        foreach ($contact->conversations as $conversation) {
            $this->deleteContactPhotoFile($conversation->image_data);
        }

        // без внешних ключей в БД (как и везде в проекте) — чистим связанные записи вручную
        $contact->photos()->delete();
        $contact->conversations()->delete();
        $contact->socialLinks()->delete();
        foreach ($contact->quotes as $quote) {
            foreach ($quote->images as $image) {
                $this->deleteContactPhotoFile($image->image_data);
            }
            $quote->images()->delete();
            $quote->hashtags()->detach();
        }
        $contact->quotes()->delete();
        $contact->facts()->delete();
        $contact->hashtags()->detach();

        $contact->delete();

        return response()->json(['status' => true, 'message' => 'Контакт удалён']);
    }

    public function storePhoto(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp',
            'caption' => 'nullable|string|max:255',
        ]);

        $photo = ContactPhoto::create([
            'contact_id' => $contact->id,
            'image' => json_encode($this->storeContactImage($request->file('image'))),
            'caption' => $request->caption,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Фото добавлено',
            'photo' => [
                'id' => $photo->id,
                'image_url' => $photo->image_url,
                'caption' => $photo->caption,
            ],
        ]);
    }

    public function destroyPhoto(ContactPhoto $photo)
    {
        $this->authorizeOwner($photo->contact);

        $this->deleteContactPhotoFile($photo->image_data);
        $photo->delete();

        return response()->json(['status' => true, 'message' => 'Фото удалено']);
    }

    /**
     * Скрин переписки ("Переписки" на странице контакта) — та же механика, что и
     * у обычных фото (contact_photos), просто отдельная сущность/раздел на странице.
     */
    public function storeConversation(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp',
            'caption' => 'nullable|string|max:255',
        ]);

        $conversation = ContactConversation::create([
            'contact_id' => $contact->id,
            'image' => json_encode($this->storeContactImage($request->file('image'))),
            'caption' => $request->caption,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Скрин добавлен',
            'conversation' => [
                'id' => $conversation->id,
                'image_url' => $conversation->image_url,
                'caption' => $conversation->caption,
            ],
        ]);
    }

    public function destroyConversation(ContactConversation $conversation)
    {
        $this->authorizeOwner($conversation->contact);

        $this->deleteContactPhotoFile($conversation->image_data);
        $conversation->delete();

        return response()->json(['status' => true, 'message' => 'Скрин удалён']);
    }

    public function storeSocial(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'platform' => 'required|string|max:100',
            'handle' => 'required|string|max:255',
            'value' => 'nullable|string|max:500',
        ]);

        $social = ContactSocialLink::create([
            'contact_id' => $contact->id,
            'platform' => $request->platform,
            'handle' => $request->handle,
            'value' => $request->value ?: null,
        ]);

        return response()->json(['status' => true, 'social' => $social]);
    }

    public function destroySocial(ContactSocialLink $social)
    {
        $this->authorizeOwner($social->contact);

        $social->delete();

        return response()->json(['status' => true, 'message' => 'Удалено']);
    }

    /**
     * Распознавание текста со скриншотов ("Текст успешно распознан..." в поп-апе "Добавить
     * высказывание") — до сохранения самого высказывания, поэтому не привязано к контакту.
     * Изображения не сохраняются на диск — распознаём прямо из содержимого загруженного файла
     * и забываем. Использует OCR.space (см. App\Components\ImageReader) — при
     * нескольких изображениях распознанный текст каждого склеивается через пустую строку.
     */
    public function recognizeQuoteText(Request $request)
    {
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $reader = new ImageReader();
        $texts = [];

        foreach ($request->file('images') as $image) {
            try {
                $texts[] = $reader->recognizeText(file_get_contents($image->getRealPath()));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('ImageReader::recognizeText failed: ' . $e->getMessage());

                $message = Str::contains($e->getMessage(), 'OCR_SPACE_API_KEY')
                    ? 'Распознавание текста не настроено: нет ключа OCR.space. Введите текст вручную.'
                    : 'Не удалось распознать текст. Введите его вручную.';

                return response()->json(['status' => false, 'message' => $message]);
            }
        }

        return response()->json([
            'status' => true,
            'text' => implode("\n\n", array_filter($texts, function ($text) { return $text !== ''; })),
            'count' => count($texts),
        ]);
    }

    /**
     * Поп-ап "Добавить высказывание" (quote_add_modal.blade.php). Источник/ссылка/дата/теги/
     * комментарий/сами изображения сохраняются по-настоящему; текст можно распознать со
     * скриншотов автоматически (см. recognizeQuoteText() выше), но это отдельный запрос —
     * сюда просто приходит уже готовый (при необходимости отредактированный) текст.
     */
    public function storeQuote(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'quote' => 'required|string|max:5000',
            'platform' => 'nullable|string|max:100',
            'source_url' => 'nullable|string|max:500',
            'occurred_at' => 'nullable|date',
            'is_exact_date' => 'nullable|boolean',
            'comment' => 'nullable|string|max:1000',
            'hashtag_ids' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'file|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        $quote = ContactQuote::create([
            'contact_id' => $contact->id,
            'quote' => $request->quote,
            'platform' => $request->platform ?: null,
            'source_url' => $request->source_url ?: null,
            'occurred_at' => $request->occurred_at ?: null,
            'is_exact_date' => $request->boolean('is_exact_date', true),
            'comment' => $request->comment ?: null,
        ]);

        $this->syncHashtags($quote, $request->hashtag_ids);

        foreach ($request->file('images', []) as $image) {
            ContactQuoteImage::create([
                'contact_quote_id' => $quote->id,
                'image' => json_encode($this->storeContactImage($image)),
            ]);
        }

        $quote->load(['hashtags', 'images']);

        return response()->json(['status' => true, 'quote' => $quote]);
    }

    public function destroyQuote(ContactQuote $quote)
    {
        $this->authorizeOwner($quote->contact);

        foreach ($quote->images as $image) {
            $this->deleteContactPhotoFile($image->image_data);
        }
        $quote->images()->delete();
        $quote->hashtags()->detach();
        $quote->delete();

        return response()->json(['status' => true, 'message' => 'Удалено']);
    }

    public function storeFact(Request $request, Contact $contact)
    {
        $this->authorizeOwner($contact);

        $request->validate([
            'fact' => 'required|string|max:2000',
        ]);

        $fact = ContactFact::create([
            'contact_id' => $contact->id,
            'fact' => $request->fact,
        ]);

        return response()->json(['status' => true, 'fact' => $fact]);
    }

    public function destroyFact(ContactFact $fact)
    {
        $this->authorizeOwner($fact->contact);

        $fact->delete();

        return response()->json(['status' => true, 'message' => 'Удалено']);
    }

    private function authorizeOwner(Contact $contact): void
    {
        abort_unless($contact->user_id == Auth::id(), 403);
    }

    /**
     * @param \App\Models\Contact|\App\Models\ContactQuote $model Любая модель со связью hashtags()
     * @param string|null $hashtagIdsJson JSON-массив id хештегов
     */
    private function syncHashtags($model, ?string $hashtagIdsJson): void
    {
        $ids = json_decode($hashtagIdsJson ?? '[]', true);
        $model->hashtags()->sync(is_array($ids) ? $ids : []);
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @return array{name: string, extension: string, path: string}
     */
    private function storeContactImage($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $name = Str::random(20);
        $relativeDir = 'contacts/' . Auth::id();

        Storage::disk('local')->makeDirectory($relativeDir);
        $file->storeAs($relativeDir, $name . '.' . $extension, 'local');
        $relativePath = '/' . $relativeDir . '/' . $name . '.' . $extension;

        // приводим к разумному размеру, как и у остальных загрузок в проекте
        try {
            $absolutePath = Storage::disk('local')->path($relativePath);
            $image = Image::make($absolutePath);
            if ($image->width() > 1200) {
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $image->save($absolutePath);
            }
        } catch (\Throwable $e) {
            // не смогли обработать — оставляем как есть
        }

        return [
            'name' => $name,
            'extension' => $extension,
            'path' => $relativePath,
        ];
    }

    private function deleteContactPhotoFile(?array $imageData): void
    {
        if (empty($imageData['path'])) {
            return;
        }

        Storage::disk('local')->delete($imageData['path']);
    }
}
