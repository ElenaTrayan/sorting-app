<?php

namespace App\Services\Ai;

/**
 * Ошибка запроса к OpenAI. getMessage() — подробности для лога,
 * userMessage() — текст, который можно показать пользователю.
 */
class OpenAiException extends \RuntimeException
{
    /** @var string|null */
    private $userMessage;

    public function __construct(string $message, ?string $userMessage = null)
    {
        parent::__construct($message);
        $this->userMessage = $userMessage;
    }

    public function userMessage(): ?string
    {
        return $this->userMessage;
    }
}
