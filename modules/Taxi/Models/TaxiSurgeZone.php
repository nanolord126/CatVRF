<?php

declare(strict_types=1);

namespace Modules\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Модель зоны повышенного спроса (Surge pricing) в такси.
 * Согласно КАНОН 2026: динамическое ценообразование на основе спроса и предложения.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $uuid
 * @property string $name Название зоны
 * @property string|null $city Город
 * @property array $polygon_coords Координаты полигона зоны (GeoJSON или массив)
 * @property float $multiplier Коэффициент наценки (1.0 = без наценки, 2.0 = двойная цена)
 * @property float|null $base_fare_multiplier Коэффициент для базовой ставки
 * @property float|null $distance_multiplier Коэффициент для расстояния
 * @property int $min_multiplier Минимальный коэффициент (в процентах)
 * @property int $max_multiplier Максимальный коэффициент (в процентах)
 * @property bool $is_active Активна ли зона
 * @property \Carbon\Carbon|null $starts_at Время начала действия
 * @property \Carbon\Carbon|null $expires_at Время окончания действия
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class TaxiSurgeZone extends Model
{
    use SoftDeletes;

    protected $table = 'taxi_surge_zones';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'city',
        'polygon_coords',
        'multiplier',
        'base_fare_multiplier',
        'distance_multiplier',
        'min_multiplier',
        'max_multiplier',
        'is_active',
        'starts_at',
        'expires_at',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'polygon_coords' => 'json',
        'multiplier' => 'float',
        'base_fare_multiplier' => 'float',
        'distance_multiplier' => 'float',
        'min_multiplier' => 'integer',
        'max_multiplier' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Скоуп для получения активных зон наценки.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->where(function ($q) {
                    $q->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                });
    }

    /**
     * Скоуп для зон, действительных в определённое время.
     */
    public function scopeActiveAt(Builder $query, \Carbon\Carbon $time): Builder
    {
        return $query->where('is_active', true)
                ->where(function ($q) use ($time) {
                    $q->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', $time);
                })
                ->where(function ($q) use ($time) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', $time);
                });
    }

    /**
     * Проверить, активна ли зона в текущий момент.
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && (!$this->starts_at || $this->starts_at <= now())
            && (!$this->expires_at || $this->expires_at > now());
    }

    /**
     * Получить эффективный коэффициент для расчета цены.
     */
    public function getEffectiveMultiplier(): float
    {
        $multiplier = $this->multiplier;

        // Ограничить минимальным коэффициентом
        if ($this->min_multiplier && $multiplier < ($this->min_multiplier / 100)) {
            $multiplier = $this->min_multiplier / 100;
        }

        // Ограничить максимальным коэффициентом
        if ($this->max_multiplier && $multiplier > ($this->max_multiplier / 100)) {
            $multiplier = $this->max_multiplier / 100;
        }

        return $multiplier;
    }

    /**
     * Вычислить цену с учётом коэффициента surge.
     */
    public function calculateSurgePrice(int $basePrice): int
    {
        return (int) ($basePrice * $this->getEffectiveMultiplier());
    }
}

    /**
     * Поиск зон с повышенным спросом для конкретных координат.
     */
    public static function getMultiplierAt(float $lat, float $lon): float
    {
        // В реальном приложении здесь используется ST_Contains в PostGIS или MySQL.
        // Для MVP проверяем наличие пересечения через JSON (или просто берем максимальный по городу).
        $zone = self::active()
            ->orderByDesc('multiplier')
            ->first();

        return (float) ($zone?->multiplier ?? 1.0);
    }
}
