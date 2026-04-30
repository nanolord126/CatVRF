<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Pickup Point (ПВЗ) Model
 *
 * Represents a pickup point for order collection.
 * Follows CatVRF production standards with tenant scoping.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $uuid
 * @property string $name
 * @property string $address
 * @property float $lat
 * @property float $lng
 * @property int $capacity_slots
 * @property int $current_load
 * @property string $working_hours
 * @property bool $is_24h
 * @property string $status
 * @property string|null $phone
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class PickupPoint extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TenantScoped;

    /**
     * Pickup point statuses
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_MAINTENANCE = 'maintenance';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'pickup_points';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'address',
        'lat',
        'lng',
        'capacity_slots',
        'current_load',
        'working_hours',
        'is_24h',
        'status',
        'phone',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'capacity_slots' => 'integer',
        'current_load' => 'integer',
        'is_24h' => 'boolean',
        'metadata' => 'json',
    ];

    /**
     * Check if pickup point is open now
     */
    public function isOpen(): bool
    {
        if ($this->is_24h) {
            return true;
        }

        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        // Parse working hours format: "09:00-21:00"
        $hours = explode('-', $this->working_hours);
        if (count($hours) !== 2) {
            return false;
        }

        $now = CarbonImmutable::now()->format('H:i');

        return $now >= $hours[0] && $now <= $hours[1];
    }

    /**
     * Check if pickup point has available slots
     */
    public function hasAvailableSlots(): bool
    {
        return $this->current_load < $this->capacity_slots;
    }

    /**
     * Get load percentage
     */
    public function getLoadPercentage(): float
    {
        if ($this->capacity_slots === 0) {
            return 0;
        }

        return ($this->current_load / $this->capacity_slots) * 100;
    }

    /**
     * Check if pickup point is near overloaded (>85%)
     */
    public function isNearOverload(): bool
    {
        return $this->getLoadPercentage() > 85;
    }

    /**
     * Increment current load
     */
    public function incrementLoad(int $amount = 1): void
    {
        $this->increment('current_load', $amount);
    }

    /**
     * Decrement current load
     */
    public function decrementLoad(int $amount = 1): void
    {
        $this->decrement('current_load', $amount);
    }

    // --- RELATIONS ---

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class, 'pickup_point_id');
    }

    /**
     * Boot method for UUID generation
     */
    protected static function booted(): void
    {
        self::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });
    }
}
