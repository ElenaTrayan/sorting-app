<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'blocked_at' => 'datetime',
    ];

    /** @var string[]|null ключи действующих сервисов подписки, кэш на время запроса */
    private $activeServiceKeys;

    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function services()
    {
        return $this->hasMany(UserService::class, 'user_id');
    }

    /**
     * Ключи сервисов, доступных пользователю прямо сейчас (включены и не истёк срок).
     * Для админа — все сервисы из config/subscription.php.
     *
     * @return string[]
     */
    public function activeServiceKeys(): array
    {
        if ($this->activeServiceKeys === null) {
            if ($this->isAdmin()) {
                $this->activeServiceKeys = array_keys(config('subscription.services', []));
            } else {
                $this->activeServiceKeys = $this->services()
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->pluck('service')
                    ->intersect(array_keys(config('subscription.services', [])))
                    ->values()
                    ->all();
            }
        }

        return $this->activeServiceKeys;
    }

    public function hasService(string $service): bool
    {
        return in_array($service, $this->activeServiceKeys(), true);
    }

    /**
     * Заменить набор сервисов подписки (сервис вне $services удаляется).
     *
     * @param array<string, \DateTimeInterface|string|null> $services ключ сервиса => срок действия (null — без срока)
     */
    public function syncServices(array $services): void
    {
        $known = array_keys(config('subscription.services', []));

        $this->services()->whereNotIn('service', array_keys($services))->delete();

        foreach ($services as $service => $expiresAt) {
            if (!in_array($service, $known, true)) {
                continue;
            }
            $this->services()->updateOrCreate(['service' => $service], ['expires_at' => $expiresAt]);
        }

        $this->activeServiceKeys = null;
    }

    /** Включить пользователю сервисы, которые выдаются при регистрации (config/subscription.php). */
    public function grantDefaultServices(): void
    {
        $this->syncServices(array_fill_keys(config('subscription.default_services', []), null));
    }
}
