<?php

namespace App\Domains\Sports\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Traits\Common\HasEcosystemMedia;
use App\Contracts\Common\AIEnableEcosystemEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Модель спортивного зала/фитнеса.
 * 
 * @property int $id
 * @property string $name Название зала
 * @property string $address Адрес зала
 * @property float $latitude Координата широты
 * @property float $longitude Координата долготы
 * @property array $amenities Удобства (спорт зал, бассейн, сауна и т.д.)
 * @property array $operating_hours Часы работы
 * @property int $total_members Количество членов
 * @property float $rating Средний рейтинг
 * @property bool $is_active Активен ли зал
 * @property array|null $metadata Метаданные
 */
class Gym extends Model implements AIEnableEcosystemEntity, HasMedia
{
    use HasEcosystemFeatures, HasEcosystemAuth, HasEcosystemMedia, InteractsWithMedia;

    protected $table = 'gyms';

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'amenities',
        'operating_hours',
        'total_members',
        'rating',
        'is_active',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
        'operating_hours' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'rating' => 'float',
        'total_members' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Получить динамическую цену на основе спроса членов.
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        try {
            $base = $basePrice > 0 ? $basePrice : 50;
            
            // Получить текущее количество активных членов
            $activeMembers = $this->memberships()
                ->whereHas('holders', function (Builder $q) {
                    $q->where('is_active', true)
                      ->where('expires_at', '>', now());
                })
                ->count();
            
            // Максимальная вместимость (50 членов по умолчанию)
            $maxCapacity = $context['max_capacity'] ?? 50;
            $occupancyPercent = min(100, ($activeMembers / $maxCapacity) * 100);
            
            // Динамическая корректировка на основе спроса
            $demandMultiplier = match (true) {
                $occupancyPercent >= 90 => 1.25,
                $occupancyPercent >= 75 => 1.15,
                $occupancyPercent >= 50 => 1.05,
                $occupancyPercent >= 25 => 0.95,
                default => 0.80,
            };
            
            $adjusted = $base * $demandMultiplier;
            
            Log::channel('sports')->debug('Gym price adjusted', [
                'gym_id' => $this->id,
                'active_members' => $activeMembers,
                'occupancy' => $occupancyPercent,
                'demand_mul' => $demandMultiplier,
                'final_price' => $adjusted,
            ]);
            
            return round($adjusted, 2);
        } catch (\Exception $e) {
            Log::error('Error calculating gym price', ['error' => $e->getMessage()]);
            return $basePrice > 0 ? $basePrice : 50;
        }
    }

    /**
     * Получить trust score зала (0-100).
     * На основе рейтинга, количества членов и условия оборудования.
     */
    public function getTrustScore(): int
    {
        try {
            $score = 80;
            
            // Плюс баллы за рейтинг
            $score += (int) ($this->rating * 4); // Максимум +20 баллов
            
            // Плюс баллы за количество членов
            if ($this->total_members > 100) {
                $score += 10;
            } elseif ($this->total_members > 50) {
                $score += 5;
            }
            
            // Минус баллы если неактивен
            if (!$this->is_active) {
                $score -= 30;
            }
            
            return min(100, max(0, $score));
        } catch (\Exception $e) {
            Log::error('Error calculating trust score', ['error' => $e->getMessage()]);
            return 80;
        }
    }

    /**
     * Генерировать AI-checklist для обслуживания зала.
     */
    public function generateAiChecklist(): array
    {
        $checklist = [
            'Дезинфицировать оборудование',
            'Проверить водоснабжение',
            'Проверить вентиляцию',
            'Проверить кондиционирование',
            'Убрать зал',
            'Проверить безопасность',
        ];
        
        // Добавить специфичные задачи на основе удобств
        if (in_array('pool', $this->amenities ?? [])) {
            $checklist[] = 'Проверить уровень воды в бассейне';
            $checklist[] = 'Дезинфицировать бассейн';
        }
        
        if (in_array('sauna', $this->amenities ?? [])) {
            $checklist[] = 'Проверить температуру сауны';
        }
        
        return $checklist;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(GymMembership::class, 'gym_id');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(GymAttendanceLog::class, 'gym_id');
    }

    /**
     * Получить активные членства.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Получить залы в радиусе (в км).
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $radiusKm): Builder
    {
        $radiusDegrees = $radiusKm / 111; // Примерное преобразование км в градусы
        
        return $query->whereBetween('latitude', [$lat - $radiusDegrees, $lat + $radiusDegrees])
            ->whereBetween('longitude', [$lng - $radiusDegrees, $lng + $radiusDegrees]);
    }

    /**
     * Получить рейтинг с локализацией.
     */
    protected function ratingLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match (true) {
                $this->rating >= 4.5 => 'Отличный',
                $this->rating >= 4.0 => 'Очень хороший',
                $this->rating >= 3.5 => 'Хороший',
                $this->rating >= 3.0 => 'Удовлетворительный',
                default => 'Требует улучшения',
            },
        );
    }

    /**
     * Получить статус загруженности зала.
     */
    protected function busyStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => match (true) {
                $this->total_members >= 90 => 'crowded',
                $this->total_members >= 70 => 'busy',
                $this->total_members >= 40 => 'moderate',
                default => 'free',
            },
        );
    }
}

/**
 * Модель членства в спортивном зале.
 * 
 * @property int $id
 * @property int $gym_id ID зала
 * @property string $name Название тарифа (Basic, Premium, VIP)
 * @property float $price Цена за период
 * @property int $duration_months Длительность в месяцах
 * @property bool $is_active Активно ли членство
 * @property array $features Особенности (sauna, pool, trainer и т.д.)
 */
class GymMembership extends Model
{
    use HasEcosystemFeatures;

    protected $table = 'gym_memberships';

    protected $fillable = [
        'gym_id',
        'name',
        'price',
        'duration_months',
        'is_active',
        'features',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'price' => 'float',
        'duration_months' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
        'metadata' => 'array',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class, 'gym_id');
    }

    public function holders(): HasMany
    {
        return $this->hasMany(GymMembershipHolder::class, 'membership_id');
    }

    /**
     * Получить количество активных держателей.
     */
    public function activeHoldersCount(): int
    {
        return $this->holders()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->count();
    }

    /**
     * Получить активные членства.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Получить прибыль за период.
     */
    public function revenueForPeriod(\DateTime $from, \DateTime $to): float
    {
        return $this->holders()
            ->whereBetween('starts_at', [$from, $to])
            ->where('is_active', true)
            ->sum('paid_amount') ?? 0;
    }
}

/**
 * Модель держателя членства.
 * 
 * @property int $id
 * @property int $user_id ID пользователя
 * @property int $membership_id ID членства
 * @property int $gym_id ID зала
 * @property \Carbon\Carbon $starts_at Дата начала
 * @property \Carbon\Carbon $expires_at Дата окончания
 * @property bool $is_active Активно ли
 * @property float $paid_amount Оплаченная сумма
 */
class GymMembershipHolder extends Model
{
    use HasEcosystemFeatures;

    protected $table = 'gym_membership_holders';

    protected $fillable = [
        'user_id',
        'membership_id',
        'gym_id',
        'starts_at',
        'expires_at',
        'is_active',
        'paid_amount',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'paid_amount' => 'float',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(GymMembership::class, 'membership_id');
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class, 'gym_id');
    }

    /**
     * Проверить, истёк ли член.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Получить количество дней до истечения.
     */
    public function daysUntilExpiration(): int
    {
        return now()->diffInDays($this->expires_at, false);
    }

    /**
     * Пролонгировать членство.
     */
    public function extend(\DateTime $newExpireDate): bool
    {
        try {
            $this->update(['expires_at' => $newExpireDate]);
            Log::channel('sports')->info('Membership extended', [
                'holder_id' => $this->id,
                'new_expire_date' => $newExpireDate,
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to extend membership', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Активные членства.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('expires_at', '>', now());
    }

    /**
     * Членства близко к истечению (менее 7 дней).
     */
    public function scopeExpiringSoon(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(7));
    }
}

/**
 * Модель логов посещаемости.
 * 
 * @property int $id
 * @property int $gym_id ID зала
 * @property int $user_id ID пользователя
 * @property \Carbon\Carbon $checked_at Время входа/выхода
 * @property bool $is_checkout Это ли выход (true) или вход (false)
 */
class GymAttendanceLog extends Model
{
    use HasEcosystemFeatures;

    protected $table = 'gym_attendance_logs';

    protected $fillable = [
        'gym_id',
        'user_id',
        'checked_at',
        'is_checkout',
        'metadata',
        'tenant_id',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'is_checkout' => 'boolean',
        'metadata' => 'array',
    ];

    const CHECK_IN = false;
    const CHECK_OUT = true;

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class, 'gym_id');
    }

    /**
     * Зарегистрировать вход пользователя.
     */
    public static function recordCheckIn(int $userId, int $gymId): ?self
    {
        try {
            // Проверить, есть ли активный вход
            $existingCheckIn = self::where('user_id', $userId)
                ->where('gym_id', $gymId)
                ->where('is_checkout', false)
                ->where('checked_at', '>=', now()->subHours(12))
                ->first();

            if ($existingCheckIn) {
                Log::warning('User already checked in', ['user_id' => $userId]);
                return $existingCheckIn;
            }

            $log = self::create([
                'user_id' => $userId,
                'gym_id' => $gymId,
                'checked_at' => now(),
                'is_checkout' => self::CHECK_IN,
            ]);

            Log::channel('sports')->info('User checked in', [
                'user_id' => $userId,
                'gym_id' => $gymId,
            ]);

            return $log;
        } catch (\Exception $e) {
            Log::error('Failed to record check-in', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Зарегистрировать выход пользователя.
     */
    public static function recordCheckOut(int $userId, int $gymId): ?self
    {
        try {
            // Найти последний активный вход
            $lastCheckIn = self::where('user_id', $userId)
                ->where('gym_id', $gymId)
                ->where('is_checkout', false)
                ->where('checked_at', '>=', now()->subHours(12))
                ->latest('checked_at')
                ->first();

            if (!$lastCheckIn) {
                Log::warning('No active check-in found', ['user_id' => $userId]);
                return null;
            }

            $log = self::create([
                'user_id' => $userId,
                'gym_id' => $gymId,
                'checked_at' => now(),
                'is_checkout' => self::CHECK_OUT,
            ]);

            Log::channel('sports')->info('User checked out', [
                'user_id' => $userId,
                'gym_id' => $gymId,
                'session_duration' => $lastCheckIn->checked_at->diffInMinutes(now()),
            ]);

            return $log;
        } catch (\Exception $e) {
            Log::error('Failed to record check-out', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Получить входы.
     */
    public function scopeCheckIns(Builder $query): Builder
    {
        return $query->where('is_checkout', self::CHECK_IN);
    }

    /**
     * Получить выходы.
     */
    public function scopeCheckOuts(Builder $query): Builder
    {
        return $query->where('is_checkout', self::CHECK_OUT);
    }

    /**
     * Получить посещаемость за период.
     */
    public function scopeForPeriod(Builder $query, \DateTime $from, \DateTime $to): Builder
    {
        return $query->whereBetween('checked_at', [$from, $to]);
    }
}

