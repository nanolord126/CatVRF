<?php

namespace App\Domains\Taxi\Models;

use App\Models\AuditLog;
use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Зоны повышенного спроса (Surge Pricing Zones)
 * Автоматически активируются при высоком спросе и истекают по времени
 * 
 * @property int $id
 * @property float $lat
 * @property float $lng
 * @property float $radius
 * @property float $multiplier
 * @property bool $is_active
 * @property \DateTime|null $expires_at
 * @property string|null $trigger_reason
 * @property array $meta
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class TaxiSurgeZone extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'multiplier' => 'float',
        'radius' => 'float',
        'lat' => 'float',
        'lng' => 'float',
        'expires_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * Динамическая цена surge множителя в зависимости от спроса
     * (базовая стоимость * коэффициент)
     */
    public function getAiAdjustedPrice(float $basePrice, array $context = []): float
    {
        try {
            if (!$this->is_active) {
                return $basePrice;
            }

            // Применяем multiplier из зоны
            $surgedPrice = $basePrice * $this->multiplier;

            // Допольнительный коэффициент за время пиковой нагрузки
            $hourOfDay = now()->hour;
            $timePeakMultiplier = match (true) {
                $hourOfDay >= 7 && $hourOfDay <= 9 => 1.15,   // Утренний пик
                $hourOfDay >= 17 && $hourOfDay <= 19 => 1.15,  // Вечерний пик
                $hourOfDay >= 22 || $hourOfDay <= 2 => 1.25,   // Поздно ночью
                default => 1.0
            };

            $finalPrice = $surgedPrice * $timePeakMultiplier;

            Log::channel('taxi')->info('Surge zone price adjusted', [
                'zone_id' => $this->id,
                'base_price' => $basePrice,
                'surge_multiplier' => $this->multiplier,
                'time_peak_multiplier' => $timePeakMultiplier,
                'final_price' => round($finalPrice, 2),
                'reason' => $this->trigger_reason
            ]);

            return round($finalPrice, 2);
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate surge zone price', [
                'zone_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return $basePrice;
        }
    }

    /**
     * Оценка доверия зоны (валидность данных о спросе)
     */
    public function getTrustScore(): int
    {
        try {
            // Базовый score 70
            $baseScore = 70;

            // Бонус за наличие причины активации
            $reasonBonus = !empty($this->trigger_reason) ? 15 : 0;

            // Штраф за долгую активность (может быть устаревшей)
            $activeDaysAgo = now()->diffInDays($this->created_at);
            $stalePenalty = match (true) {
                $activeDaysAgo >= 7 => -30,
                $activeDaysAgo >= 3 => -15,
                $activeDaysAgo >= 1 => -5,
                default => 0
            };

            // Бонус за близость к истечению (значит данные свежие)
            $hoursUntilExpire = $this->expires_at ? now()->diffInHours($this->expires_at) : 0;
            $freshnessBonus = match (true) {
                $hoursUntilExpire <= 1 => 20,
                $hoursUntilExpire <= 3 => 10,
                $hoursUntilExpire <= 6 => 5,
                default => 0
            };

            $score = max(0, min(100, $baseScore + $reasonBonus + $stalePenalty + $freshnessBonus));

            return (int)$score;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to calculate surge zone trust score', [
                'zone_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return 70;
        }
    }

    /**
     * AI чек-лист для управления surge зонами
     */
    public function generateAiChecklist(): array
    {
        $hoursUntilExpire = $this->expires_at ? now()->diffInHours($this->expires_at) : 0;

        return [
            'Проверить актуальность коэффициента спроса' => [
                'critical' => $hoursUntilExpire > 6,
                'current_multiplier' => $this->multiplier,
                'age_hours' => now()->diffInHours($this->created_at)
            ],
            'Расширить зону при необходимости' => [
                'critical' => false,
                'current_radius_km' => $this->radius,
                'active_rides_nearby' => 'TBD by service layer'
            ],
            'Запланировать деактивацию' => [
                'critical' => $hoursUntilExpire <= 1,
                'expires_at' => $this->expires_at ? $this->expires_at->format('Y-m-d H:i:s') : null,
                'hours_remaining' => $hoursUntilExpire
            ],
            'Уведомить водителей о завершении surge' => [
                'critical' => $hoursUntilExpire <= 0 && $this->is_active,
                'notify_drivers_count' => 'TBD by service layer',
                'zone_id' => $this->id
            ]
        ];
    }

    /**
     * Проверить, находится ли точка внутри зоны
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $distance = $this->calculateDistance($this->lat, $this->lng, $lat, $lng);
        return $distance <= $this->radius;
    }

    /**
     * Расчёт расстояния между двумя координатами (формула Хаверсина)
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // км

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Автоматически деактивировать истёкшие зоны
     */
    public function deactivateIfExpired(): bool
    {
        try {
            if (!$this->is_active || !($this->expires_at && $this->expires_at <= now())) {
                return false;
            }

            $this->update(['is_active' => false]);

            Log::channel('taxi')->info('Surge zone expired and deactivated', [
                'zone_id' => $this->id,
                'expired_at' => $this->expires_at,
                'multiplier_was' => $this->multiplier
            ]);

            AuditLog::create([
                'tenant_id' => $this->tenant_id,
                'action' => 'surge_zone_expired',
                'model' => self::class,
                'model_id' => $this->id,
                'changes' => [
                    'is_active' => [true, false]
                ],
                'correlation_id' => request()?->header('X-Correlation-ID'),
                'ip_address' => request()?->ip()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to deactivate surge zone', [
                'zone_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Активные и не истёкшие зоны
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            });
    }

    /**
     * Зоны в определённом радиусе от координаты
     */
    public function scopeNearPoint($query, float $lat, float $lng, float $radiusKm = 5.0)
    {
        // Используем примерный бокс для оптимизации (будет уточнено в PHP)
        $latOffset = $radiusKm / 111.0; // ~1 градус = 111 км
        $lngOffset = $radiusKm / (111.0 * cos(deg2rad($lat)));

        return $query->where('is_active', true)
            ->whereBetween('lat', [$lat - $latOffset, $lat + $latOffset])
            ->whereBetween('lng', [$lng - $lngOffset, $lng + $lngOffset]);
    }

    /**
     * Зоны по коэффициенту спроса
     */
    public function scopeByMultiplier($query, float $minMultiplier = 1.5)
    {
        return $query->where('multiplier', '>=', $minMultiplier)
            ->orderByDesc('multiplier');
    }

    /**
     * Вычисляемый статус зоны
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_active) {
                    return 'Неактивна';
                }
                if ($this->expires_at && $this->expires_at <= now()) {
                    return 'Истекла';
                }
                return 'Активна';
            }
        );
    }

    /**
     * Вычисляемый уровень спроса
     */
    protected function demandLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match (true) {
                    $this->multiplier >= 2.0 => 'Критический',
                    $this->multiplier >= 1.5 => 'Высокий',
                    $this->multiplier >= 1.2 => 'Средний',
                    $this->multiplier >= 1.0 => 'Низкий',
                    default => 'Нормальный'
                };
            }
        );
    }

    /**
     * Вычисляемое время до истечения
     */
    protected function timeRemaining(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->expires_at) {
                    return 'Неопределённо';
                }
                if ($this->expires_at <= now()) {
                    return 'Истекло';
                }
                $hours = now()->diffInHours($this->expires_at);
                $minutes = now()->diffInMinutes($this->expires_at) % 60;
                return $hours > 0 ? "{$hours}ч {$minutes}м" : "{$minutes}м";
            }
        );
    }
}
