<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Личные настройки ИИ пользователя — одна строка на пользователя ("Настройки ИИ" в кабинете).
 * Ключ OpenAI хранится зашифрованным и наружу (в браузер) не отдаётся — только последние символы.
 */
class UserAiSetting extends Model
{
    protected $table = 'user_ai_settings';

    // модели OpenAI на выбор: все понимают картинки и reasoning.effort
    public const OPENAI_MODELS = [
        'gpt-6-luna' => 'GPT-6 Luna — быстрая и самая дешёвая (рекомендуется)',
        'gpt-6.1-sol' => 'GPT-6.1 Sol — точнее, примерно в 20 раз дороже',
    ];

    public const DEFAULT_OPENAI_MODEL = 'gpt-6-luna';

    public const TAGS_MODES = [
        'suggest' => 'Предлагать — теги появляются подсказками, добавляете их сами',
        'auto' => 'Добавлять сразу — после загрузки картинки теги ставятся автоматически',
    ];

    protected $fillable = [
        'user_id',
        'provider',
        'api_key',
        'model',
        'tags_mode',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hasKey(): bool
    {
        return !empty($this->api_key);
    }

    /** "sk-…a1B2" — чтобы пользователь узнал свой ключ, не видя его целиком */
    public function maskedKey(): ?string
    {
        return $this->hasKey() ? 'sk-…' . substr($this->api_key, -4) : null;
    }

    public function openAiModel(): string
    {
        return isset(self::OPENAI_MODELS[$this->model]) ? $this->model : self::DEFAULT_OPENAI_MODEL;
    }

    public function autoApplyTags(): bool
    {
        return $this->hasKey() && $this->tags_mode === 'auto';
    }

    public static function forUser(int $userId): self
    {
        return static::firstOrNew(['user_id' => $userId], [
            'provider' => 'openai',
            'tags_mode' => 'suggest',
        ]);
    }
}
