<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactSocialLink extends Model
{
    protected $table = 'contact_social_links';

    protected $fillable = [
        'contact_id',
        'platform',
        'handle',
        'value',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /**
     * Иконка (Font Awesome) и брендовый цвет для платформы по её названию —
     * платформа хранится свободным текстом, поэтому сопоставляем по подстроке.
     * Используется на странице контакта; JS-версия (для добавления без
     * перезагрузки страницы) продублирована в admin.js — getSocialIcon().
     *
     * @return array{0: string, 1: string} [iconClass, color]
     */
    public static function iconFor(?string $platform): array
    {
        $key = Str::lower(trim((string) $platform));

        $map = [
            'instagram' => ['fab fa-instagram', '#E1306C'],
            'telegram' => ['fab fa-telegram-plane', '#229ED9'],
            'tiktok' => ['fab fa-tiktok', '#010101'],
            'youtube' => ['fab fa-youtube', '#FF0000'],
            'facebook' => ['fab fa-facebook-f', '#1877F2'],
            'вконтакте' => ['fab fa-vk', '#4A76A8'],
            'vkontakte' => ['fab fa-vk', '#4A76A8'],
            'vk' => ['fab fa-vk', '#4A76A8'],
            'whatsapp' => ['fab fa-whatsapp', '#25D366'],
            'snapchat' => ['fab fa-snapchat-ghost', '#FFFC00'],
            'linkedin' => ['fab fa-linkedin-in', '#0A66C2'],
            'pinterest' => ['fab fa-pinterest-p', '#E60023'],
            'threads' => ['fab fa-at', '#000000'],
            'twitter' => ['fab fa-twitter', '#1DA1F2'],
            'x' => ['fab fa-twitter', '#1DA1F2'],
        ];

        foreach ($map as $needle => $icon) {
            if ($key !== '' && Str::contains($key, $needle)) {
                return $icon;
            }
        }

        return ['fas fa-share-alt', '#6c757d'];
    }
}
