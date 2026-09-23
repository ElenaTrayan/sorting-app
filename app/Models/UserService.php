<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Сервис (раздел кабинета), включённый пользователю в подписке.
 * Список возможных сервисов — config/subscription.php.
 */
class UserService extends Model
{
    protected $table = 'user_services';

    protected $fillable = [
        'user_id',
        'service',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Действует ли сервис сейчас (без срока — всегда). */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
