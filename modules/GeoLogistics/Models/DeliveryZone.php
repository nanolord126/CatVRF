<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель зоны доставки.
 * Согласно КАНОН 2026: управление зонами доставки, расчёт стоимости, динамическое ценообразование.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string|null $uuid
 * @property string $name Название зоны
 * @property array|null $polygon_coordinates Координаты полигона (JSON)
 * @property int $base_price_kopeki Базовая цена доставки в копейках
 * @property int $delivery_time_minutes Время доставки в минутах
 * @property float|null $surge_multiplier Множитель за повышенный спрос
 * @property bool $is_active Активна ли зона
 * @property int|null $max_orders_per_hour Максимум заказов в час
 * @property float|null $latitude Центр зоны - широта
 * @property float|null $longitude Центр зоны - долгота
 * @property int|null $radius_km Радиус зоны в км
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class DeliveryZone extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_zones';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'name',
        'polygon_coordinates',
        'base_price_kopeki',
        'delivery_time_minutes',
        'surge_multiplier',
        'is_active',
        'max_orders_per_hour',
        'latitude',
        'longitude',
        'radius_km',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'polygon_coordinates' => 'json',
        'base_price_kopeki' => 'integer',
        'delivery_time_minutes' => 'integer',
        'surge_multiplier' => 'float',
        'is_active' => 'boolean',
        'max_orders_per_hour' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_km' => 'float',
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
     * Получить маршруты в этой зоне.
     */
    public function routes(): HasMany
    {
        return $this->hasMany(DeliveryRoute::class, 'zone_id');
    }

    /**
     * Получить цену доставки в рублях.
     */
    public function getBasePriceInRubles(): float
    {
        return (float) ($this->base_price_kopeki / 100);
    }

    /**
     * Установить цену в рублях.
     */
    public function setBasePriceInRubles(float $rubles): void
    {
        $this->base_price_kopeki = (int) ($rubles * 100);
    }

    /**
     * Получить эффективную цену с учётом surge multiplier.
     */
    public function getEffectivePrice(): int
    {
        $basePrice = $this->base_price_kopeki;
        if ($this->surge_multiplier) {
            return (int) ($basePrice * $this->surge_multiplier);
        }

        return $basePrice;
    }

    /**
     * Проверить, активна ли зона.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Проверить, не перегружена ли зона.
     */
    public function isOverloaded(): bool
    {
        if (!$this->max_orders_per_hour) {
            return false;
        }

        return $this->routes()
            ->where('status', 'in_progress')
            ->whereDate('created_at', today())
            ->whereTime('created_at', '>=', now()->subHour())
            ->count() >= $this->max_orders_per_hour;
    }
}
