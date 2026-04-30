<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Carbon\CarbonImmutable;

use App\Domains\Logistics\Enums\CourierType;
use App\Models\User;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Courier extends Model
{
    use TenantScoped;

    /**
     * Courier statuses
     */
    public const STATUS_ONLINE = 'online';

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_BUSY = 'busy';

    public const STATUS_IDLE = 'idle';

    public const STATUS_ON_DELIVERY = 'on_delivery';

    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Vehicle types (legacy constants, use CourierType enum instead)
     */
    public const VEHICLE_PEDESTRIAN = 'pedestrian';

    public const VEHICLE_BIKE = 'bike';

    public const VEHICLE_CAR = 'car';

    public const VEHICLE_SCOOTER = 'scooter';

    public const VEHICLE_TAXI = 'taxi';

    protected $table = 'couriers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'vehicle_id',
        'status',
        'current_location',
        'rating',
        'commission_percent',
        'tags',
        'correlation_id',
        'vehicle_type',
        'current_lat',
        'current_lng',
        'capacity_kg',
        'max_radius_km',
        'preferred_zones',
        'is_taxi_driver',
        'is_verified',
        'is_active',
        'delivery_count',
        'earnings_kopeki',
        'battery_level',
        'last_location_update',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'token',
        'secret',
    ];

    protected $casts = [
        'uuid' => 'string',
        'status' => 'string',
        'rating' => 'float',
        'commission_percent' => 'integer',
        'tags' => 'json',
        'current_location' => 'object',
        'current_lat' => 'float',
        'current_lng' => 'float',
        'capacity_kg' => 'float',
        'max_radius_km' => 'float',
        'preferred_zones' => 'json',
        'is_taxi_driver' => 'boolean',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'delivery_count' => 'integer',
        'earnings_kopeki' => 'integer',
        'battery_level' => 'integer',
        'last_location_update' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Is courier online and ready for new tasks.
     */
    public function isAvailable(): bool
    {
        return in_array($this->status, [self::STATUS_ONLINE, self::STATUS_IDLE], true) && ! $this->deleted_at;
    }

    /**
     * Check if courier can handle the weight
     */
    public function canHandleWeight(float $weightKg): bool
    {
        return $this->capacity_kg >= $weightKg;
    }

    /**
     * Check if courier is a taxi driver (hybrid mode)
     */
    public function isTaxiDriver(): bool
    {
        return $this->is_taxi_driver === true;
    }

    /**
     * Get courier type enum instance.
     */
    public function getType(): ?CourierType
    {
        if ($this->vehicle_type === null) {
            return null;
        }

        return CourierType::tryFrom($this->vehicle_type);
    }

    /**
     * Get max radius in km (courier-specific override or type default).
     */
    public function getMaxRadiusKm(): float
    {
        if ($this->max_radius_km !== null) {
            return $this->max_radius_km;
        }

        return $this->getType()?->getMaxRadiusKm() ?? 10.0;
    }

    /**
     * Check if courier can handle distance.
     */
    public function canHandleDistance(float $distanceKm): bool
    {
        return $this->getMaxRadiusKm() >= $distanceKm;
    }

    /**
     * Check if courier has sufficient battery (for electric vehicles).
     */
    public function hasSufficientBattery(?int $threshold = null): bool
    {
        if (! $this->getType()?->requiresBattery()) {
            return true;
        }

        $minBattery = $threshold ?? $this->getType()?->getBatteryThreshold() ?? 30;

        return $this->battery_level >= $minBattery;
    }

    /**
     * Check if location is in preferred zones.
     */
    public function isInPreferredZone(float $lat, float $lng): bool
    {
        if ($this->preferred_zones === null || empty($this->preferred_zones)) {
            return true; // No zones defined = all zones allowed
        }

        // Simple bounding box check (production should use proper point-in-polygon)
        foreach ($this->preferred_zones as $zone) {
            if (isset($zone['bounds'])) {
                $bounds = $zone['bounds'];
                if ($lat >= $bounds['min_lat'] && $lat <= $bounds['max_lat']
                    && $lng >= $bounds['min_lng'] && $lng <= $bounds['max_lng']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get routing mode for this courier type.
     */
    public function getRoutingMode(): string
    {
        return $this->getType()?->getRoutingMode() ?? 'driving';
    }

    /**
     * Get parking time in minutes for this courier type.
     */
    public function getParkingTimeMin(): int
    {
        return $this->getType()?->getParkingTimeMin() ?? 0;
    }

    /**
     * Get cost multiplier for this courier type.
     */
    public function getCostMultiplier(): float
    {
        return $this->getType()?->getCostMultiplier() ?? 1.0;
    }

    /**
     * Update courier location
     */
    public function updateLocation(float $lat, float $lng): void
    {
        $this->update([
            'current_lat' => $lat,
            'current_lng' => $lng,
            'current_location' => ['lat' => $lat, 'lng' => $lng],
            'last_location_update' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Mark as on delivery
     */
    public function markOnDelivery(): void
    {
        $this->update(['status' => self::STATUS_ON_DELIVERY]);
    }

    /**
     * Mark as idle
     */
    public function markIdle(): void
    {
        $this->update(['status' => self::STATUS_IDLE]);
    }

    /**
     * Get current status color for UI.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'busy' => 'warning',
            default => 'gray',
        };
    }

    // --- RELATIONS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class, 'courier_id');
    }

    public function orderShipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class, 'courier_id');
    }

    /**
     * Related routes for the courier via orders.
     */
    public function routes(): HasMany
    {
        return $this->hasMany(Route::class, 'courier_id');
    }

    /**
     * Глобальная изоляция по tenant_id
     */
    protected static function booted_disabled(): void
    {
        self::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        self::addGlobalScope('tenant_id', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
