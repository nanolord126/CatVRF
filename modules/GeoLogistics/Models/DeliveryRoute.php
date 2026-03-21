<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель маршрута доставки.
 * Согласно КАНОН 2026: отслеживание маршрутов, расчёт расстояния, оптимизация.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $zone_id
 * @property string|null $uuid
 * @property float $start_latitude Широта начала маршрута
 * @property float $start_longitude Долгота начала маршрута
 * @property float $end_latitude Широта конца маршрута
 * @property float $end_longitude Долгота конца маршрута
 * @property int $distance_meters Расстояние в метрах
 * @property int $estimated_minutes Ориентировочное время доставки
 * @property string $status (pending, assigned, in_progress, completed, failed, cancelled)
 * @property int|null $actual_minutes Фактическое время
 * @property array|null $waypoints_json Промежуточные точки (JSON)
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class DeliveryRoute extends Model
{
    use SoftDeletes;

    protected $table = 'delivery_routes';

    protected $fillable = [
        'tenant_id',
        'zone_id',
        'uuid',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'distance_meters',
        'estimated_minutes',
        'status',
        'actual_minutes',
        'waypoints_json',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'start_latitude' => 'float',
        'start_longitude' => 'float',
        'end_latitude' => 'float',
        'end_longitude' => 'float',
        'distance_meters' => 'integer',
        'estimated_minutes' => 'integer',
        'actual_minutes' => 'integer',
        'waypoints_json' => 'json',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Статусы маршрута.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

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
     * Получить зону доставки.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    /**
     * Получить расстояние в км.
     */
    public function getDistanceInKm(): float
    {
        return (float) ($this->distance_meters / 1000);
    }

    /**
     * Получить средюю скорость (км/ч).
     */
    public function getAverageSpeed(): float
    {
        if (!$this->actual_minutes || $this->actual_minutes === 0) {
            return 0;
        }

        $hours = $this->actual_minutes / 60;
        return (float) ($this->getDistanceInKm() / $hours);
    }

    /**
     * Отметить как начата.
     */
    public function markAsInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    /**
     * Отметить как завершена.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'actual_minutes' => (int) $this->created_at->diffInMinutes(now()),
        ]);
    }

    /**
     * Отметить как не доставлена.
     */
    public function fail(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Отменить маршрут.
     */
    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Проверить, завершён ли маршрут.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Проверить, в процессе ли.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
