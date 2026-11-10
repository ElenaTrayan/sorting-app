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

    // часовой пояс, пока пользователь не выбрал свой ("Настройки аккаунта"). Europe/Kiev, а не Europe/Kyiv:
    // нового названия нет в базе поясов PHP 8.1
    public const DEFAULT_TIMEZONE = 'Europe/Kiev';

    // вверху списка поясов в "Настройках аккаунта": пояс => город
    public const POPULAR_TIMEZONES = [
        'Europe/Kiev' => 'Киев',
        'Europe/Warsaw' => 'Варшава',
        'Europe/Berlin' => 'Берлин',
        'Europe/Prague' => 'Прага',
        'Europe/Rome' => 'Рим',
        'Europe/Madrid' => 'Мадрид',
        'Europe/Paris' => 'Париж',
        'Europe/London' => 'Лондон',
        'Europe/Lisbon' => 'Лиссабон',
        'Europe/Bucharest' => 'Бухарест',
        'Europe/Chisinau' => 'Кишинёв',
        'Europe/Vilnius' => 'Вильнюс',
        'Europe/Riga' => 'Рига',
        'Europe/Tallinn' => 'Таллин',
        'Europe/Helsinki' => 'Хельсинки',
        'Europe/Istanbul' => 'Стамбул',
        'Asia/Tbilisi' => 'Тбилиси',
        'Asia/Dubai' => 'Дубай',
        'America/New_York' => 'Нью-Йорк',
        'America/Toronto' => 'Торонто',
        'America/Los_Angeles' => 'Лос-Анджелес',
    ];

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

    // свой часовой пояс или пояс по умолчанию (неизвестное название — тоже по умолчанию)
    public function timezoneName(): string
    {
        $timezone = (string)$this->timezone;

        return in_array($timezone, timezone_identifiers_list(\DateTimeZone::ALL_WITH_BC), true) ? $timezone : self::DEFAULT_TIMEZONE;
    }

    /**
     * "Сегодня" у пользователя — полночь его даты, но в поясе приложения (UTC), как даты из БД (касты 'date'):
     * так их можно сравнивать и считать дни без сдвига на разницу поясов
     *
     * @return \Illuminate\Support\Carbon
     */
    public function localToday(): \Illuminate\Support\Carbon
    {
        return \Illuminate\Support\Carbon::parse(now($this->timezoneName())->toDateString());
    }

    // "сегодня" вошедшего пользователя (localToday), а без него — сегодня по поясу по умолчанию
    public static function today(): \Illuminate\Support\Carbon
    {
        $user = auth()->user();

        return $user instanceof self
            ? $user->localToday()
            : \Illuminate\Support\Carbon::parse(now(self::DEFAULT_TIMEZONE)->toDateString());
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
