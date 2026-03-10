<?php

namespace App\Domains\Taxi\Models;

use App\Models\AuditLog;
use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $license_plate
 * @property string $make
 * @property string $model
 * @property string $year
 * @property string $vin
 * @property string $vehicle_class
 * @property array $features
 * @property float $mileage_km
 * @property int $capacity
 * @property string $color
 * @property bool $is_active
 * @property \DateTime|null $last_inspection_at
 * @property \DateTime|null $next_inspection_due
 * @property decimal $condition_rating
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class TaxiVehicle extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'last_inspection_at' => 'datetime',
        'next_inspection_due' => 'datetime',
        'mileage_km' => 'float',
        'condition_rating' => 'decimal:1',
        'capacity' => 'integer',
    ];

    public function drivers(): HasMany
    {
        return $this->hasMany(TaxiDriver::class, 'current_vehicle_id');
    }

    public function rides(): HasMany
    {
        return $this->hasMany(TaxiRide::class, 'vehicle_id');
    }

    /**
     * Динамическая цена за использование ТС
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        try {
            // Коэффициент за класс автомобиля
            $classMultiplier = match ($this->vehicle_class) {
                'economy' => 0.85,
                'comfort' => 1.00,
                'premium' => 1.30,
                'xl' => 1.50,
                default => 1.00
            };

            // Штраф за состояние ТС
            $conditionMultiplier = match (true) {
                $this->condition_rating >= 4.5 => 1.10,
                $this->condition_rating >= 4.0 => 1.05,
                $this->condition_rating >= 3.5 => 1.00,
                $this->condition_rating >= 3.0 => 0.95,
                default => 0.85
            };

            // Бонус за дополнительные опции
            $featureBonus = 1.0;
            if (!empty($this->features)) {
                $premiumFeatures = array_intersect($this->features, ['wifi', 'charger', 'water', 'wifi']);
                $featureBonus = 1.0 + (count($premiumFeatures) * 0.05);
            }

            $adjustedPrice = $basePrice * $classMultiplier * $conditionMultiplier * $featureBonus;

            Log::channel('taxi')->info('Vehicle price adjusted', [
                'vehicle_id' => $this->id,
                'base_price' => $basePrice,
                'class_multiplier' => $classMultiplier,
                'condition_multiplier' => $conditionMultiplier,
                'feature_bonus' => $featureBonus,
                'final_price' => round($adjustedPrice, 2)
            ]);

            return round($adjustedPrice, 2);
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate vehicle adjusted price', [
                'vehicle_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return $basePrice;
        }
    }

    /**
     * Оценка доверия ТС (техническое состояние)
     */
    public function getTrustScore(): int
    {
        try {
            // Базовый score из рейтинга состояния
            $baseScore = (int)((float)$this->condition_rating * 20);

            // Бонус за свежесть осмотра
            $inspectionBonus = 0;
            if ($this->last_inspection_at) {
                $daysSinceInspection = now()->diffInDays($this->last_inspection_at);
                $inspectionBonus = match (true) {
                    $daysSinceInspection <= 30 => 20,
                    $daysSinceInspection <= 90 => 10,
                    $daysSinceInspection <= 180 => 0,
                    default => -20
                };
            }

            // Штраф за высокий пробег
            $mileagePenalty = 0;
            if ($this->mileage_km > 500000) {
                $mileagePenalty = -30;
            } elseif ($this->mileage_km > 300000) {
                $mileagePenalty = -15;
            }

            // Бонус за активность (много недавних поездок)
            $recentRides = $this->rides()->where('completed_at', '>', now()->subDays(7))->count();
            $activityBonus = min(10, $recentRides);

            $score = max(0, min(100, $baseScore + $inspectionBonus + $mileagePenalty + $activityBonus));

            return (int)$score;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate vehicle trust score', [
                'vehicle_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 50;
        }
    }

    /**
     * AI чек-лист проверок для ТС
     */
    public function generateAiChecklist(): array
    {
        $inspectionDaysAgo = $this->last_inspection_at ? now()->diffInDays($this->last_inspection_at) : 999;

        return [
            'Пройти техническое обслуживание' => [
                'critical' => $inspectionDaysAgo > 180,
                'days_since_last' => $inspectionDaysAgo,
                'due_date' => $this->next_inspection_due ? $this->next_inspection_due->format('Y-m-d') : null
            ],
            'Проверить уровень масла и жидкостей' => [
                'critical' => $inspectionDaysAgo > 90,
                'status' => $this->condition_rating < 3.5 ? 'urgent' : 'routine'
            ],
            'Проверить шины (давление и износ)' => [
                'critical' => false,
                'recommended_interval' => '30 дней'
            ],
            'Помыть внутреннюю отделку' => [
                'critical' => false,
                'frequency' => 'ежедневно'
            ],
            'Обновить страховку (при необходимости)' => [
                'critical' => $this->mileage_km > 400000,
                'current_mileage' => $this->mileage_km
            ],
            'Проверить документы и лицензии' => [
                'critical' => $inspectionDaysAgo > 365,
                'documents' => ['техпаспорт', 'страховка', 'талон ТО']
            ]
        ];
    }

    /**
     * Провести техническое обслуживание
     */
    public function inspectAndMaintain(int $conductedBy, string $notes = ''): bool
    {
        return DB::transaction(function () use ($conductedBy, $notes) {
            try {
                $this->update([
                    'last_inspection_at' => now(),
                    'next_inspection_due' => now()->addDays(180),
                    'condition_rating' => 5.0 // Сброс на максимум после ТО
                ]);

                Log::channel('taxi')->info('Vehicle inspected and maintained', [
                    'vehicle_id' => $this->id,
                    'conducted_by' => $conductedBy,
                    'notes' => $notes,
                    'mileage_km' => $this->mileage_km
                ]);

                AuditLog::create([
                    'user_id' => $conductedBy,
                    'tenant_id' => $this->tenant_id,
                    'action' => 'vehicle_inspected',
                    'model' => self::class,
                    'model_id' => $this->id,
                    'changes' => [
                        'last_inspection_at' => [null, now()->toIso8601String()],
                        'condition_rating' => [null, 5.0]
                    ],
                    'correlation_id' => request()?->header('X-Correlation-ID'),
                    'ip_address' => request()?->ip()
                ]);

                return true;
            } catch (\Exception $e) {
                Log::channel('taxi')->error('Failed to inspect vehicle', [
                    'vehicle_id' => $this->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }

    /**
     * Обновить пробег ТС
     */
    public function updateMileage(float $newMileage, string $reason = 'ride_completed'): bool
    {
        try {
            if ($newMileage < $this->mileage_km) {
                throw new \Exception("Mileage cannot decrease");
            }

            $kmAdded = $newMileage - $this->mileage_km;
            $this->update(['mileage_km' => $newMileage]);

            Log::channel('taxi')->info('Vehicle mileage updated', [
                'vehicle_id' => $this->id,
                'km_added' => $kmAdded,
                'total_mileage' => $newMileage,
                'reason' => $reason
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to update vehicle mileage', [
                'vehicle_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Активные ТС
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('next_inspection_due', '>', now());
    }

    /**
     * ТС по классу
     */
    public function scopeByClass($query, string $class)
    {
        return $query->where('vehicle_class', $class);
    }

    /**
     * ТС требующие обслуживания
     */
    public function scopeNeedsMaintenance($query)
    {
        return $query->where('next_inspection_due', '<=', now())
            ->orWhere('condition_rating', '<', 3.0);
    }

    /**
     * Вычисляемая информация о техническом состоянии
     */
    protected function condition(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match (true) {
                    $this->condition_rating >= 4.5 => 'Отличное',
                    $this->condition_rating >= 4.0 => 'Хорошее',
                    $this->condition_rating >= 3.5 => 'Удовлетворительное',
                    $this->condition_rating >= 3.0 => 'Требует ремонта',
                    default => 'Критическое'
                };
            }
        );
    }

    /**
     * Вычисляемый статус осмотра
     */
    protected function inspectionStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->last_inspection_at) {
                    return 'Никогда не проверялось';
                }

                $daysSince = now()->diffInDays($this->last_inspection_at);

                return match (true) {
                    $daysSince <= 30 => 'Свежий',
                    $daysSince <= 90 => 'Действительный',
                    $daysSince <= 180 => 'Требует обновления',
                    default => 'Просрочено'
                };
            }
        );
    }

    /**
     * Вычисляемый возраст ТС в годах
     */
    protected function ageYears(): Attribute
    {
        return Attribute::make(
            get: function () {
                return now()->year - $this->year;
            }
        );
    }

    /**
     * Вычисляемая полная информация об автомобиле
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                return "{$this->year} {$this->make} {$this->model} ({$this->license_plate})";
            }
        );
    }
}
