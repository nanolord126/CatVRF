<?php

namespace App\Domains\Taxi\Models;

use App\Models\AuditLog;
use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $current_vehicle_id
 * @property float $rating
 * @property int $total_rides
 * @property float $total_earnings
 * @property array $last_location
 * @property \DateTime|null $last_online_at
 * @property bool $is_active
 * @property string|null $license_number
 * @property \DateTime|null $license_expires_at
 * @property string|null $bank_account
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class TaxiDriver extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $guarded = [];

    protected $casts = [
        'last_location' => 'array',
        'rating' => 'float',
        'total_rides' => 'integer',
        'total_earnings' => 'decimal:2',
        'last_online_at' => 'datetime',
        'is_active' => 'boolean',
        'license_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class, 'current_vehicle_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(TaxiRide::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(TaxiShift::class, 'driver_id');
    }

    /**
     * Динамическая цена за услугу (базовая тариф × рейтинг)
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        try {
            // Коэффициент за рейтинг: 4-5 звёзд = +10%, 3-4 = 0%, <3 = -15%
            $ratingMultiplier = match (true) {
                $this->rating >= 4.5 => 1.10,
                $this->rating >= 4.0 => 1.05,
                $this->rating >= 3.5 => 1.00,
                $this->rating >= 3.0 => 0.95,
                default => 0.85
            };

            // Бонус за количество поездок (доверие)
            $experienceBonus = match (true) {
                $this->total_rides >= 10000 => 1.15,
                $this->total_rides >= 5000 => 1.10,
                $this->total_rides >= 1000 => 1.05,
                default => 1.00
            };

            $adjustedPrice = $basePrice * $ratingMultiplier * $experienceBonus;

            Log::channel('taxi')->info('Driver price adjusted', [
                'driver_id' => $this->id,
                'base_price' => $basePrice,
                'rating_multiplier' => $ratingMultiplier,
                'experience_bonus' => $experienceBonus,
                'final_price' => $adjustedPrice,
                'correlation_id' => request()?->header('X-Correlation-ID')
            ]);

            return round($adjustedPrice, 2);
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate driver adjusted price', [
                'driver_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return $basePrice;
        }
    }

    /**
     * Оценка доверия водителя (0-100)
     */
    public function getTrustScore(): int
    {
        try {
            // Базовый рейтинг: 50 + (rating * 10)
            $baseScore = 50 + (int)($this->rating * 10);

            // Бонус за активность
            $activityBonus = $this->is_active ? 20 : -30;

            // Бонус за опыт (поездки)
            $experienceBonus = match (true) {
                $this->total_rides >= 10000 => 20,
                $this->total_rides >= 5000 => 15,
                $this->total_rides >= 1000 => 10,
                default => 0
            };

            // Штраф за давность последнего входа
            $lastSeenDaysAgo = $this->last_online_at ? now()->diffInDays($this->last_online_at) : 999;
            $recencyPenalty = match (true) {
                $lastSeenDaysAgo > 90 => -20,
                $lastSeenDaysAgo > 30 => -10,
                $lastSeenDaysAgo > 7 => -5,
                default => 0
            };

            $score = max(0, min(100, $baseScore + $activityBonus + $experienceBonus + $recencyPenalty));

            return (int)$score;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate driver trust score', [
                'driver_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 50;
        }
    }

    /**
     * Генерирует AI чек-лист проверок для водителя
     */
    public function generateAiChecklist(): array
    {
        $checklist = [
            'Проверить лицензию водителя' => [
                'critical' => $this->license_expires_at ? $this->license_expires_at <= now() : true,
                'days_until_expiry' => $this->license_expires_at ? now()->diffInDays($this->license_expires_at) : 0
            ],
            'Обновить банковские реквизиты' => [
                'critical' => empty($this->bank_account),
                'status' => empty($this->bank_account) ? 'required' : 'completed'
            ],
            'Проверить технический статус ТС' => [
                'critical' => !$this->vehicle?->is_active,
                'vehicle_id' => $this->current_vehicle_id
            ],
            'Обновить геолокацию' => [
                'critical' => $this->last_online_at ? now()->diffInMinutes($this->last_online_at) > 120 : true,
                'last_seen' => $this->last_online_at ? $this->last_online_at->format('Y-m-d H:i:s') : null
            ],
            'Провести аттестацию при рейтинге < 3.5' => [
                'critical' => $this->rating < 3.5,
                'current_rating' => $this->rating
            ]
        ];

        // Добавляем задачи на основе истории
        if ($this->total_rides >= 5000) {
            $checklist['Пройти продвинутое обучение'] = [
                'critical' => false,
                'recommended' => true,
                'rides_count' => $this->total_rides
            ];
        }

        return $checklist;
    }

    /**
     * Активные водители
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('license_expires_at', '>', now());
    }

    /**
     * Водители, онлайн в последние N минут
     */
    public function scopeOnline($query, int $minutes = 15)
    {
        return $query->where('last_online_at', '>', now()->subMinutes($minutes));
    }

    /**
     * Водители по рейтингу (от выше к ниже)
     */
    public function scopeByRating($query, float $minRating = 3.5)
    {
        return $query->where('rating', '>=', $minRating)->orderByDesc('rating');
    }

    /**
     * Вычисляемый статус водителя
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_active) {
                    return 'Неактивен';
                }
                if ($this->last_online_at && now()->diffInMinutes($this->last_online_at) <= 5) {
                    return 'Онлайн';
                }
                if ($this->last_online_at && now()->diffInMinutes($this->last_online_at) <= 30) {
                    return 'Недавно онлайн';
                }
                return 'Не в сети';
            }
        );
    }

    /**
     * Вычисляемый уровень опыта
     */
    protected function experienceLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match (true) {
                    $this->total_rides >= 10000 => 'Профессионал',
                    $this->total_rides >= 5000 => 'Опытный',
                    $this->total_rides >= 1000 => 'Средний',
                    $this->total_rides >= 100 => 'Новичок',
                    default => 'Стажер'
                };
            }
        );
    }

    /**
     * Вычисляемая оценка рейтинга в текстовом формате
     */
    protected function ratingLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match (true) {
                    $this->rating >= 4.8 => 'Отличный',
                    $this->rating >= 4.5 => 'Очень хороший',
                    $this->rating >= 4.0 => 'Хороший',
                    $this->rating >= 3.5 => 'Удовлетворительный',
                    $this->rating >= 3.0 => 'Требует улучшения',
                    default => 'Неудовлетворительный'
                };
            }
        );
    }
}
