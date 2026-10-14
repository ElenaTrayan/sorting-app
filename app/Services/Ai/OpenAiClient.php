<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Запросы к OpenAI API с ключом пользователя (свой ключ из "Настроек ИИ").
 * Ключ уходит только с сервера, в браузер не попадает. Ошибки — OpenAiException
 * с понятным пользователю текстом (неверный ключ, нет денег на балансе и т.п.).
 */
class OpenAiClient
{
    private const BASE_URL = 'https://api.openai.com/v1';

    /** @var string */
    private $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Проверка ключа при сохранении в настройках: список моделей отдаётся только с рабочим ключом.
     * Баланс так не проверить — "нет денег" OpenAI сообщает только на настоящем запросе.
     *
     * @throws OpenAiException
     */
    public function checkKey(): void
    {
        $this->send('get', '/models', [], 15);
    }

    /**
     * POST /responses (Responses API). Возвращает текст ответа модели.
     *
     * @throws OpenAiException
     */
    public function responseText(array $payload): string
    {
        $data = $this->send('post', '/responses', $payload, 60);

        if (($data['status'] ?? null) === 'incomplete') {
            throw new OpenAiException('OpenAI не закончил ответ: ' . ($data['incomplete_details']['reason'] ?? 'unknown'));
        }

        foreach ($data['output'] ?? [] as $item) {
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }
            foreach ($item['content'] ?? [] as $part) {
                if (($part['type'] ?? null) === 'refusal') {
                    throw new OpenAiException('OpenAI отказался отвечать: ' . ($part['refusal'] ?? ''));
                }
                if (($part['type'] ?? null) === 'output_text') {
                    return $part['text'];
                }
            }
        }

        throw new OpenAiException('В ответе OpenAI нет текста');
    }

    /**
     * @throws OpenAiException
     */
    private function send(string $method, string $path, array $payload, int $timeout): array
    {
        try {
            /** @var Response $response */
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout($timeout)
                ->$method(self::BASE_URL . $path, $payload);
        } catch (ConnectionException $e) {
            throw new OpenAiException('Нет связи с OpenAI: ' . $e->getMessage(), 'Не удалось связаться с OpenAI. Попробуйте ещё раз.');
        }

        if ($response->successful()) {
            return $response->json() ?: [];
        }

        $error = $response->json('error') ?: [];
        $code = $error['code'] ?? null;
        $details = 'OpenAI ' . $response->status() . ': ' . ($error['message'] ?? $response->body());

        if ($response->status() === 401) {
            throw new OpenAiException($details, 'Ключ OpenAI не подходит — проверьте его в «Настройках ИИ».');
        }
        if ($code === 'insufficient_quota') {
            throw new OpenAiException($details, 'На балансе OpenAI API закончились деньги — пополните его на platform.openai.com (Billing).');
        }
        if ($response->status() === 429) {
            throw new OpenAiException($details, 'OpenAI просит подождать (слишком много запросов) — попробуйте через минуту.');
        }
        if ($response->status() === 403 || $code === 'model_not_found') {
            throw new OpenAiException($details, 'У вашего ключа OpenAI нет доступа к выбранной модели — выберите другую в «Настройках ИИ».');
        }

        throw new OpenAiException($details);
    }
}
