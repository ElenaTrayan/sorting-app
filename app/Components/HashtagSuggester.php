<?php

namespace App\Components;

use Anthropic\Client;
use App\Models\Hashtag;
use App\Models\UserAiSetting;
use App\Services\Ai\OpenAiClient;
use App\Services\Ai\OpenAiException;
use App\Services\Integrations\IntegrationManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Подсказки хештегов для нового поста по его картинкам. Новые теги не придумываются —
 * выбираются только из уже существующих хештегов пользователя, двумя способами:
 *
 * - по смыслу картинки: картинки и список тегов уходят в ИИ, в ответ — id подходящих тегов
 *   (JSON по схеме, см. responseSchema()). Если пользователь подключил свой ключ OpenAI
 *   ("Настройки ИИ", UserAiSetting) — запрос идёт в OpenAI за его счёт (askOpenAi()). Иначе —
 *   в Claude (Anthropic API) с ключом сайта: из админки (раздел "Внешние API", интеграция
 *   'anthropic'), иначе из .env: ANTHROPIC_API_KEY, ANTHROPIC_TAGS_MODEL (см. config/services.php);
 * - по цвету: основные цвета картинки (ImagePalette) получают названия (ColorMatch::NAMES),
 *   и если у пользователя есть тег с таким названием ("красный", "#Бежевые") — он в подсказках.
 *   Работает локально и бесплатно, даже когда ИИ отключён или без ключа.
 */
class HashtagSuggester
{
    // сколько тегов максимум просить у ИИ
    private const MAX_AI_TAGS = 10;

    // цвет попадает в подсказки, если занимает хотя бы такую долю картинки (фон не считается)
    private const MIN_COLOR_SHARE = 0.15;

    // Claude всё равно уменьшает картинки больше ~1568px по длинной стороне — шлём сразу такие,
    // чтобы не гонять лишние мегабайты
    private const MAX_IMAGE_SIDE = 1568;

    // модели, для которых включаем серверный fallback на случай отказа модели (refusal)
    private const FALLBACK_MODELS = ['claude-opus-5', 'claude-fable-5-1'];

    /** @var string|null почему не сработал ИИ (для сообщения пользователю), null — всё ок */
    private $aiError = null;

    public function aiError(): ?string
    {
        return $this->aiError;
    }

    /**
     * @param string[] $absolutePaths картинки поста на диске
     * @param int $userId
     * @param int[] $excludeIds уже выбранные теги — их не предлагаем
     * @return array [['id' => 5, 'title' => 'кухня', 'source' => 'ai'|'color'], ...] — сначала теги от ИИ
     */
    public function suggest(array $absolutePaths, int $userId, array $excludeIds = []): array
    {
        $hashtags = Hashtag::where('user_id', $userId)->get(['id', 'title', 'parent_id'])->keyBy('id');
        if ($hashtags->isEmpty() || empty($absolutePaths)) {
            return [];
        }

        $result = [];
        foreach ($this->aiTagIds($absolutePaths, $hashtags, $userId) as $id) {
            $result[$id] = 'ai';
        }
        foreach ($this->colorTagIds($absolutePaths, $hashtags) as $id) {
            $result[$id] = $result[$id] ?? 'color';
        }

        $suggestions = [];
        foreach ($result as $id => $source) {
            if (in_array($id, $excludeIds, true)) {
                continue;
            }
            $suggestions[] = ['id' => $id, 'title' => $hashtags[$id]->title, 'source' => $source];
        }

        return $suggestions;
    }

    /**
     * Теги по названиям основных цветов картинок
     *
     * @param string[] $absolutePaths
     * @param Collection $hashtags id => Hashtag
     * @return int[]
     */
    private function colorTagIds(array $absolutePaths, Collection $hashtags): array
    {
        $colorNames = [];
        foreach ($absolutePaths as $path) {
            foreach (ImagePalette::fromFile($path) as $color) {
                if (empty($color['background']) && $color['share'] >= self::MIN_COLOR_SHARE) {
                    $colorNames[self::normalize(ColorMatch::nameFor($color['hex']))] = true;
                }
            }
        }

        $ids = [];
        foreach ($hashtags as $hashtag) {
            if (isset($colorNames[self::normalize($hashtag->title)])) {
                $ids[] = $hashtag->id;
            }
        }

        return $ids;
    }

    /**
     * Название тега/цвета для сравнения: без "#", регистра, "ё" и окончания прилагательного —
     * чтобы "#Красные", "красная" и "красный" считались одним цветом
     */
    private static function normalize(string $title): string
    {
        $title = mb_strtolower(trim(ltrim(trim($title), '#')));
        $title = str_replace('ё', 'е', $title);

        return preg_replace('/(ый|ий|ой|ая|яя|ое|ее|ые|ие|ую|юю)$/u', '', $title);
    }

    /**
     * @param string[] $absolutePaths
     * @param Collection $hashtags id => Hashtag
     * @return int[] id тегов, которые выбрал ИИ (только существующие у пользователя)
     */
    private function aiTagIds(array $absolutePaths, Collection $hashtags, int $userId): array
    {
        $integrations = app(IntegrationManager::class);
        $setting = UserAiSetting::where('user_id', $userId)->first();

        try {
            if ($setting && $setting->hasKey()) {
                // свой ключ OpenAI пользователя — платит он сам
                if (!$integrations->isEnabled('openai')) {
                    $this->aiError = 'Подбор тегов через OpenAI отключён администратором — показаны только теги по цвету.';
                    return [];
                }
                $ids = $this->askOpenAi($absolutePaths, $hashtags, $setting->api_key, $setting->openAiModel());
            } else {
                // ключ сайта (Claude)
                if (!$integrations->isEnabled('anthropic')) {
                    $this->aiError = 'Подбор тегов по картинке отключён — показаны только теги по цвету. '
                        . 'Можно подключить свой ключ OpenAI в «Настройках ИИ».';
                    return [];
                }

                $apiKey = $integrations->credential('anthropic', 'key');
                if (empty($apiKey)) {
                    $this->aiError = 'Подбор тегов по картинке не настроен — показаны только теги по цвету. '
                        . 'Подключите свой ключ OpenAI в «Настройках ИИ».';
                    return [];
                }

                $model = $integrations->credential('anthropic', 'model') ?: 'claude-opus-5';
                $ids = $this->askClaude($absolutePaths, $hashtags, $apiKey, $model);
            }
        } catch (OpenAiException $e) {
            Log::warning('HashtagSuggester: OpenAI request failed (user ' . $userId . '): ' . $e->getMessage());
            $this->aiError = ($e->userMessage() ?: 'Не удалось подобрать теги через OpenAI.') . ' Показаны только теги по цвету.';
            return [];
        } catch (\Throwable $e) {
            Log::error('HashtagSuggester: AI request failed: ' . $e->getMessage());
            $this->aiError = 'Не удалось подобрать теги по картинке — показаны только теги по цвету.';
            return [];
        }

        // модель выбирает из списка, но на всякий случай отбрасываем всё, чего у пользователя нет
        return array_values(array_unique(array_filter($ids, function ($id) use ($hashtags) {
            return $hashtags->has($id);
        })));
    }

    /**
     * Запрос в OpenAI (Responses API) с ключом пользователя. Инструкция и список тегов идут
     * первыми и не меняются между запросами — OpenAI сам кэширует такой префикс, это дешевле.
     *
     * @return int[]
     * @throws OpenAiException
     */
    private function askOpenAi(array $absolutePaths, Collection $hashtags, string $apiKey, string $model): array
    {
        $content = [
            ['type' => 'input_text', 'text' => 'Подбери хештеги из списка для поста с этими картинками.'],
        ];
        foreach ($absolutePaths as $path) {
            $content[] = [
                'type' => 'input_image',
                'image_url' => 'data:image/jpeg;base64,' . base64_encode($this->prepareImage($path)),
                'detail' => 'high',
            ];
        }

        $text = (new OpenAiClient($apiKey))->responseText([
            'model' => $model,
            'instructions' => self::instructions() . "\n\n" . $this->tagList($hashtags),
            'input' => [['role' => 'user', 'content' => $content]],
            // задача простая — низкого effort хватает, так дешевле и быстрее
            'reasoning' => ['effort' => 'low'],
            'max_output_tokens' => 2000,
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'hashtags',
                    'strict' => true,
                    'schema' => self::responseSchema(),
                ],
            ],
            // картинки пользователя у OpenAI не храним
            'store' => false,
        ]);

        return self::parseIds($text);
    }

    /**
     * @return int[]
     */
    private function askClaude(array $absolutePaths, Collection $hashtags, string $apiKey, string $model): array
    {
        $client = new Client(apiKey: $apiKey, requestOptions: ['timeout' => 60, 'maxRetries' => 2]);

        $content = [];
        foreach ($absolutePaths as $path) {
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'mediaType' => 'image/jpeg',
                    'data' => base64_encode($this->prepareImage($path)),
                ],
            ];
        }
        $content[] = [
            'type' => 'text',
            'text' => 'Подбери хештеги из списка для поста с этими картинками.',
        ];

        $params = [
            'model' => $model,
            'maxTokens' => 2000,
            'system' => [
                ['type' => 'text', 'text' => self::instructions()],
                // список тегов пользователя меняется редко — кэшируем, когда добавляют несколько постов подряд
                ['type' => 'text', 'text' => $this->tagList($hashtags), 'cacheControl' => ['type' => 'ephemeral']],
            ],
            'messages' => [['role' => 'user', 'content' => $content]],
            // задача простая — низкого effort хватает, так дешевле и быстрее
            'outputConfig' => [
                'effort' => 'low',
                'format' => [
                    'type' => 'json_schema',
                    'schema' => self::responseSchema(),
                ],
            ],
        ];

        if (in_array($model, self::FALLBACK_MODELS, true)) {
            // если модель откажется отвечать (refusal), API само повторит запрос на запасной модели
            $params['betas'] = ['server-side-fallback-2026-07-01'];
            $params['fallbacks'] = 'default';
            $message = $client->beta->messages->create(...$params);
        } else {
            $message = $client->messages->create(...$params);
        }

        if ($message->stopReason === 'refusal') {
            throw new \RuntimeException('Claude refused: ' . ($message->stopDetails->explanation ?? ''));
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return self::parseIds($block->text);
            }
        }

        return [];
    }

    /** JSON-схема ответа — одна для Claude и OpenAI */
    private static function responseSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'hashtag_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
            'required' => ['hashtag_ids'],
            'additionalProperties' => false,
        ];
    }

    /** @return int[] */
    private static function parseIds(string $json): array
    {
        $data = json_decode($json, true);
        $ids = array_map('intval', $data['hashtag_ids'] ?? []);

        return array_slice($ids, 0, self::MAX_AI_TAGS);
    }

    private static function instructions(): string
    {
        return 'Ты помогаешь раскладывать пины (сохранённые картинки и заметки) по хештегам личной коллекции. '
            . 'Посмотри на картинки и выбери из списка хештегов пользователя те, что точно подходят: '
            . 'что изображено, тема, назначение, стиль. Выбирай только из списка, новые теги не придумывай. '
            . 'Хештеги устроены деревом: "Родитель / Ребёнок" — если подходит вложенный тег, родителя отдельно не добавляй. '
            . 'Лучше меньше, но точных; не больше ' . self::MAX_AI_TAGS . '. Если ничего не подходит — верни пустой список. '
            . 'Цвета не оценивай, их подбирают отдельно. Ответ — id выбранных хештегов.';
    }

    /**
     * Список тегов для ИИ: "id: Родитель / Ребёнок", по одному в строке, отсортирован по id,
     * чтобы при тех же тегах текст не менялся (иначе не сработает кэш)
     */
    private function tagList(Collection $hashtags): string
    {
        $lines = [];
        foreach ($hashtags->sortKeys() as $id => $hashtag) {
            $path = [$hashtag->title];
            $parent = $hashtags->get($hashtag->parent_id);
            // глубина дерева ограничена (Hashtag::MAX_LEVEL), счётчик — защита от зацикленных parent_id
            for ($i = 0; $parent && $i < Hashtag::MAX_LEVEL; $i++) {
                array_unshift($path, $parent->title);
                $parent = $hashtags->get($parent->parent_id);
            }
            $lines[] = $id . ': ' . implode(' / ', $path);
        }

        return "Хештеги пользователя:\n" . implode("\n", $lines);
    }

    /** Картинка для отправки: JPEG не больше MAX_IMAGE_SIDE по длинной стороне */
    private function prepareImage(string $absolutePath): string
    {
        return (string) \Intervention\Image\Facades\Image::make($absolutePath)
            ->resize(self::MAX_IMAGE_SIDE, self::MAX_IMAGE_SIDE, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('jpg', 85);
    }
}
