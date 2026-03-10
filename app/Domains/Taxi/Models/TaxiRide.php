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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $driver_id
 * @property int|null $vehicle_id
 * @property int|null $shift_id
 * @property array $pickup_coords
 * @property string $pickup_address
 * @property array $destination_coords
 * @property string $destination_address
 * @property float $distance_km
 * @property decimal $estimated_price
 * @property decimal $final_price
 * @property decimal $driver_earnings
 * @property decimal $platform_commission
 * @property float $surge_multiplier
 * @property string $status
 * @property string $vehicle_class
 * @property \DateTime|null $started_at
 * @property \DateTime|null $completed_at
 * @property int|null $duration_minutes
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class TaxiRide extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $guarded = [];

    protected $casts = [
        'pickup_coords' => 'array',
        'destination_coords' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'driver_earnings' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'surge_multiplier' => 'float',
        'distance_km' => 'float',
        'duration_minutes' => 'integer',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(TaxiShift::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(TaxiRideStatusLog::class)->orderBy('recorded_at', 'asc');
    }

    /**
     * Динамическая цена поездки с учётом surge, рейтинга, дистанции
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        try {
            // Коэффициент за surge (спрос)
            $surgeMultiplier = (float)($this->surge_multiplier ?? 1.0);

            // Коэффициент за качество водителя
            $driverRatingMultiplier = match (true) {
                $this->driver?->rating >= 4.8 => 1.08,
                $this->driver?->rating >= 4.5 => 1.04,
                $this->driver?->rating >= 4.0 => 1.00,
                $this->driver?->rating >= 3.5 => 0.96,
                default => 0.90
            };

            // Скидка по классу автомобиля (экономный дешевле)
            $classDiscount = match ($this->vehicle_class) {
                'economy' => 0.85,
                'comfort' => 1.00,
                'premium' => 1.30,
                'xl' => 1.50,
                default => 1.00
            };

            $adjustedPrice = $basePrice * $surgeMultiplier * $driverRatingMultiplier * $classDiscount;

            Log::channel('taxi')->info('Ride price adjusted', [
                'ride_id' => $this->id,
                'base_price' => $basePrice,
                'surge_multiplier' => $surgeMultiplier,
                'driver_rating_multiplier' => $driverRatingMultiplier,
                'class_discount' => $classDiscount,
                'final_price' => round($adjustedPrice, 2)
            ]);

            return round($adjustedPrice, 2);
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate ride adjusted price', [
                'ride_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return $basePrice;
        }
    }

    /**
     * Оценка доверия поездки (риск мошенничества)
     */
    public function getTrustScore(): int
    {
        try {
            // Базовый score 70
            $baseScore = 70;

            // Бонус за проверённого водителя
            $driverBonus = match (true) {
                $this->driver?->rating >= 4.5 && $this->driver?->total_rides >= 1000 => 20,
                $this->driver?->rating >= 4.0 => 15,
                $this->driver?->rating >= 3.5 => 10,
                default => 0
            };

            // Штраф за ночное время (выше риск)
            $hourOfDay = (int)$this->created_at->format('H');
            $nightPenalty = ($hourOfDay >= 22 || $hourOfDay <= 5) ? -15 : 0;

            // Штраф за длинную дистанцию без истории
            $distancePenalty = 0;
            if ($this->distance_km > 50 && !$this->driver?->shifts()->exists()) {
                $distancePenalty = -10;
            }

            // Бонус за краткую дистанцию
            $shortDistanceBonus = $this->distance_km <= 5 ? 5 : 0;

            $score = max(0, min(100, $baseScore + $driverBonus + $nightPenalty + $distancePenalty + $shortDistanceBonus));

            return (int)$score;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate ride trust score', [
                'ride_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 70;
        }
    }

    /**
     * AI чек-лист для контроля качества поездки
     */
    public function generateAiChecklist(): array
    {
        return [
            'Проверить статус платежа' => [
                'critical' => $this->final_price > 0 && empty($this->payment_confirmed_at),
                'amount' => $this->final_price,
                'status' => $this->status
            ],
            'Оценить водителя' => [
                'critical' => $this->status === 'completed' && !$this->customer_rated_at,
                'driver_id' => $this->driver_id,
                'current_rating' => $this->driver?->rating
            ],
            'Проверить целостность маршрута' => [
                'critical' => $this->status === 'completed' && !$this->distance_km,
                'pickup' => $this->pickup_address,
                'destination' => $this->destination_address
            ],
            'Отправить квитанцию' => [
                'critical' => $this->status === 'completed' && !$this->receipt_sent_at,
                'ride_id' => $this->id,
                'final_amount' => $this->final_price
            ],
            'Зафиксировать жалобу, если есть' => [
                'critical' => false,
                'enabled_if' => 'customer complaint filed',
                'ride_id' => $this->id
            ]
        ];
    }

    /**
     * Завершить поездку: расчёт финалов, комиссии
     */
    public function complete(float $actualDistance, int $durationMinutes): bool
    {
        return DB::transaction(function () use ($actualDistance, $durationMinutes) {
            try {
                // 1. Обновляем параметры поездки
                $this->update([
                    'distance_km' => $actualDistance,
                    'duration_minutes' => $durationMinutes,
                    'completed_at' => now(),
                    'status' => 'completed'
                ]);

                // 2. Финальная цена = смета + доп за длину + время
                $basePrice = (float)$this->estimated_price;
                $distanceAdditional = max(0, ($actualDistance - ($basePrice / 15)) * 3); // 3 за км сверх плана
                $timeAdditional = max(0, ($durationMinutes - 10) * 0.5); // 0.5 за минуту сверх 10

                $finalPrice = round($basePrice + $distanceAdditional + $timeAdditional, 2);

                // 3. Расчёт комиссии платформы (15%) и доходов водителя
                $platformCommission = round($finalPrice * 0.15, 2);
                $driverEarnings = $finalPrice - $platformCommission;

                $this->update([
                    'final_price' => $finalPrice,
                    'platform_commission' => $platformCommission,
                    'driver_earnings' => $driverEarnings
                ]);

                // 4. Обновляем статистику водителя
                $this->driver->increment('total_rides');
                $this->driver->update([
                    'total_earnings' => $this->driver->total_earnings + $driverEarnings
                ]);

                // 5. Обновляем смену, если привязана
                if ($this->shift) {
                    $this->shift->increment('rides_count');
                    $this->shift->increment('total_earnings', $finalPrice);
                    $this->shift->increment('driver_profit', $driverEarnings);
                }

                // 6. Логирование
                Log::channel('taxi')->info('Ride completed', [
                    'ride_id' => $this->id,
                    'driver_id' => $this->driver_id,
                    'distance_km' => $actualDistance,
                    'duration_minutes' => $durationMinutes,
                    'final_price' => $finalPrice,
                    'driver_earnings' => $driverEarnings
                ]);

                // 7. Audit Log
                AuditLog::create([
                    'user_id' => $this->driver?->user_id,
                    'tenant_id' => $this->tenant_id,
                    'action' => 'ride_completed',
                    'model' => self::class,
                    'model_id' => $this->id,
                    'changes' => [
                        'status' => ['started', 'completed'],
                        'final_price' => [$this->estimated_price, $finalPrice],
                        'driver_earnings' => [0, $driverEarnings]
                    ],
                    'correlation_id' => request()?->header('X-Correlation-ID'),
                    'ip_address' => request()?->ip()
                ]);

                // 8. Логируем статус
                TaxiRideStatusLog::recordStatus($this, 'completed', [
                    'distance' => $actualDistance,
                    'duration_minutes' => $durationMinutes,
                    'final_price' => $finalPrice
                ]);

                return true;
            } catch (\Exception $e) {
                Log::channel('taxi')->error('Failed to complete ride', [
                    'ride_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }

    /**
     * Отменить поездку (до начала)
     */
    public function cancel(string $reason = 'customer_request'): bool
    {
        try {
            if (in_array($this->status, ['completed', 'cancelled', 'no_show'])) {
                throw new \Exception("Cannot cancel ride with status: {$this->status}");
            }

            $this->update([
                'status' => 'cancelled',
                'completed_at' => now()
            ]);

            Log::channel('taxi')->warning('Ride cancelled', [
                'ride_id' => $this->id,
                'reason' => $reason,
                'driver_id' => $this->driver_id
            ]);

            TaxiRideStatusLog::recordStatus($this, 'cancelled', ['reason' => $reason]);

            return true;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to cancel ride', [
                'ride_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Активные поездки (поиск водителя или в пути)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['searching', 'accepted', 'arrived', 'started']);
    }

    /**
     * Завершённые поездки
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Поездки за период
     */
    public function scopeForPeriod($query, Carbon $from, Carbon $to)
    {
        return $query->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to);
    }

    /**
     * Поездки конкретного класса автомобиля
     */
    public function scopeByClass($query, string $class)
    {
        return $query->where('vehicle_class', $class);
    }

    /**
     * Поездки с оплатой выше суммы
     */
    public function scopeByMinPrice($query, float $minPrice)
    {
        return $query->where('final_price', '>=', $minPrice);
    }

    /**
     * Вычисляемый статус в читаемом виде
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match ($this->status) {
                    'searching' => 'Поиск водителя',
                    'accepted' => 'Водитель принял',
                    'arrived' => 'Водитель прибыл',
                    'started' => 'В пути',
                    'completed' => 'Завершена',
                    'cancelled' => 'Отменена',
                    'no_show' => 'Водитель не появился',
                    default => $this->status
                };
            }
        );
    }

    /**
     * Вычисляемая длительность поездки
     */
    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->duration_minutes) {
                    return 'Не завершена';
                }
                $hours = intdiv($this->duration_minutes, 60);
                $minutes = $this->duration_minutes % 60;
                return $hours > 0 
                    ? "{$hours}ч {$minutes}м"
                    : "{$minutes}м";
            }
        );
    }

    /**
     * Вычисляемая оценка стоимости км
     */
    protected function pricePerKm(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->distance_km <= 0) {
                    return 0;
                }
                return round((float)$this->final_price / $this->distance_km, 2);
            }
        );
    }
}
