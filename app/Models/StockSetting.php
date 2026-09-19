<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Настройки раздела "Мои запасы" — одна строка на пользователя.
 */
class StockSetting extends Model
{
    protected $table = 'stock_settings';

    public const NOTIFICATION_KEYS = [
        'low_stock' => 'Заканчиваются запасы',
        'expiry' => 'Срок годности',
        'price_drop' => 'Снижение цены',
        'order_status' => 'Статус заказа',
        'order_ready' => 'Заказ можно забрать',
        'return_deadline' => 'Срок возврата',
        'weekly_summary' => 'Еженедельная сводка',
    ];

    protected $fillable = [
        'user_id',
        'notifications',
        'currency',
        'region',
        'preferred_store_ids',
        'email_integration_enabled',
    ];

    protected $casts = [
        'email_integration_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNotificationsListAttribute(): array
    {
        $saved = $this->notifications ? json_decode($this->notifications, true) : [];
        $defaults = array_fill_keys(array_keys(self::NOTIFICATION_KEYS), true);
        return array_merge($defaults, is_array($saved) ? $saved : []);
    }

    public function getPreferredStoreIdsListAttribute(): array
    {
        return $this->preferred_store_ids ? json_decode($this->preferred_store_ids, true) : [];
    }

    public function notificationEnabled(string $key): bool
    {
        return (bool) ($this->notifications_list[$key] ?? true);
    }

    public static function forUser(int $userId): self
    {
        return static::firstOrCreate(['user_id' => $userId], [
            'currency' => 'UAH',
        ]);
    }
}
