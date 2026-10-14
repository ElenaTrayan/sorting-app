<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserAiSetting;
use App\Services\Ai\OpenAiClient;
use App\Services\Ai\OpenAiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * "Настройки ИИ" в кабинете: свой ключ OpenAI для подбора хештегов по картинке
 * (см. HashtagSuggester), модель и режим "предлагать / добавлять сразу".
 * Ключ в форму обратно не выводится — только маска; пустое поле = оставить старый ключ.
 */
class AiSettingsController extends Controller
{
    public function index()
    {
        return view('user.ai-settings.index', [
            'settings' => UserAiSetting::forUser(Auth::id()),
            'models' => UserAiSetting::OPENAI_MODELS,
            'modes' => UserAiSetting::TAGS_MODES,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'api_key' => ['nullable', 'string', 'max:300', 'regex:/^sk-[A-Za-z0-9_\-]+$/'],
            'model' => 'required|in:' . implode(',', array_keys(UserAiSetting::OPENAI_MODELS)),
            'tags_mode' => 'required|in:' . implode(',', array_keys(UserAiSetting::TAGS_MODES)),
        ], [
            'api_key.regex' => 'Ключ OpenAI начинается с «sk-» и состоит из латинских букв, цифр, «-» и «_». Скопируйте его целиком.',
        ]);

        $settings = UserAiSetting::forUser(Auth::id());
        $newKey = trim($data['api_key'] ?? '');

        if ($newKey !== '') {
            try {
                (new OpenAiClient($newKey))->checkKey();
            } catch (OpenAiException $e) {
                Log::info('AiSettings: OpenAI key check failed (user ' . Auth::id() . '): ' . $e->getMessage());

                return back()
                    ->withInput($request->except('api_key'))
                    ->withErrors(['api_key' => $e->userMessage() ?: 'OpenAI не принял ключ. Проверьте, что скопировали его целиком.']);
            }
            $settings->api_key = $newKey;
        }

        $settings->provider = 'openai';
        $settings->model = $data['model'];
        $settings->tags_mode = $data['tags_mode'];
        $settings->save();

        return redirect()->route('ai-settings.index')
            ->with('success', $newKey !== '' ? 'Ключ OpenAI проверен и сохранён.' : 'Настройки сохранены.');
    }

    public function destroyKey()
    {
        $settings = UserAiSetting::forUser(Auth::id());
        if ($settings->exists) {
            $settings->api_key = null;
            $settings->save();
        }

        return redirect()->route('ai-settings.index')->with('success', 'Ключ OpenAI удалён.');
    }
}
