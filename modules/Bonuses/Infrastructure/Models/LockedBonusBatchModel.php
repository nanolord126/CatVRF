<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Bonuses\Domain\Entities\LockedBonusBatch as DomainEntity;
use Modules\Bonuses\Domain\ValueObjects\HoldPeriod;
use Modules\Bonuses\Domain\ValueObjects\VestingCurve;

/**
 * Eloquent Model: LockedBonusBatchModel
 *
 * Infrastructure model for persisting LockedBonusBatch domain entities to the database.
 * Maps to the locked_bonus_batches table.
 *
 * Table schema:
 * - id: UUID primary key
 * - user_id: Foreign key to users table
 * - tenant_id: Foreign key to tenants table (nullable)
 * - total_amount: Total bonus amount in kopecks
 * - unlocked_amount: Amount already vested/unlocked in kopecks
 * - locked_amount: Current locked amount in kopecks
 * - vesting_curve_type: Enum (LINEAR, ACCELERATED, CUSTOM)
 * - vesting_curve_json: JSON for custom schedules
 * - total_days: Total vesting period in days
 * - base_hold_days: Base hold period in days
 * - activity_acceleration_days: Days reduced from activity
 * - streak_acceleration_days: Days reduced from streak
 * - tier_acceleration_days: Days reduced from tier
 * - actual_hold_days: Calculated hold period after accelerators
 * - start_date: Timestamp when hold period started
 * - end_date: Timestamp when batch will be fully available
 * - status: Enum (LOCKED, VESTING, UNLOCKED, SOLD)
 * - source_type: Source that triggered bonus award
 * - source_id: ID of source entity
 * - vertical: Vertical where bonus was earned
 * - user_tier: User tier for hold calculation
 * - correlation_id: Correlation ID for distributed tracing
 * - metadata: Additional metadata (JSON)
 * - created_at, updated_at: Timestamps
 *
 * Relationships:
 * - user: BelongsTo User model
 * - tenant: BelongsTo Tenant model (nullable)
 *
 * Domain mapping:
 * - toDomainEntity(): Converts model to domain entity
 * - fromDomainEntity(): Creates model from domain entity
 *
 * @see Modules\Bonuses\Domain\Entities\LockedBonusBatch
 */
class LockedBonusBatchModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'locked_bonus_batches';

    /**
     * The primary key type.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'user_id',
        'tenant_id',
        'total_amount',
        'unlocked_amount',
        'locked_amount',
        'vesting_curve_type',
        'vesting_curve_json',
        'total_days',
        'base_hold_days',
        'activity_acceleration_days',
        'streak_acceleration_days',
        'tier_acceleration_days',
        'actual_hold_days',
        'start_date',
        'end_date',
        'status',
        'source_type',
        'source_id',
        'vertical',
        'user_tier',
        'correlation_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_amount' => 'integer',
        'unlocked_amount' => 'integer',
        'locked_amount' => 'integer',
        'total_days' => 'integer',
        'base_hold_days' => 'integer',
        'activity_acceleration_days' => 'integer',
        'streak_acceleration_days' => 'integer',
        'tier_acceleration_days' => 'integer',
        'actual_hold_days' => 'integer',
        'vesting_curve_json' => 'array',
        'metadata' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Relationship to tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.models.tenant'), 'tenant_id');
    }

    /**
     * Converts model to domain entity.
     */
    public function toDomainEntity(): DomainEntity
    {
        $vestingCurve = VestingCurve::fromArray([
            'type' => $this->vesting_curve_type,
            'total_days' => $this->total_days,
            'custom_schedule' => $this->vesting_curve_json,
        ]);

        $holdPeriod = HoldPeriod::fromArray([
            'base_days' => $this->base_hold_days,
            'activity_acceleration_days' => $this->activity_acceleration_days,
            'streak_acceleration_days' => $this->streak_acceleration_days,
            'tier_acceleration_days' => $this->tier_acceleration_days,
            'start_date' => $this->start_date->format('Y-m-d H:i:s'),
        ]);

        return DomainEntity::fromArray([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'total_amount' => $this->total_amount,
            'unlocked_amount' => $this->unlocked_amount,
            'locked_amount' => $this->locked_amount,
            'vesting_curve' => $vestingCurve->toArray(),
            'hold_period' => $holdPeriod->toArray(),
            'status' => $this->status,
            'locked_at' => $this->created_at->format('Y-m-d H:i:s'),
            'fully_available_at' => $this->end_date->format('Y-m-d H:i:s'),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'vertical' => $this->vertical,
            'user_tier' => $this->user_tier,
            'correlation_id' => $this->correlation_id,
            'metadata' => $this->metadata,
        ]);
    }

    /**
     * Creates model from domain entity.
     */
    public static function fromDomainEntity(DomainEntity $entity): self
    {
        $data = $entity->toArray();

        return self::updateOrCreate(
            ['id' => $entity->id],
            [
                'user_id' => $entity->userId,
                'tenant_id' => $entity->tenantId,
                'total_amount' => $entity->totalAmount,
                'unlocked_amount' => $entity->unlockedAmount,
                'locked_amount' => $entity->lockedAmount,
                'vesting_curve_type' => $entity->vestingCurve->getType()->value,
                'vesting_curve_json' => $entity->vestingCurve->toArray()['custom_schedule'],
                'total_days' => $entity->vestingCurve->getTotalDays(),
                'base_hold_days' => $entity->holdPeriod->getBaseDays(),
                'activity_acceleration_days' => $entity->holdPeriod->getActivityAccelerationDays(),
                'streak_acceleration_days' => $entity->holdPeriod->getStreakAccelerationDays(),
                'tier_acceleration_days' => $entity->holdPeriod->getTierAccelerationDays(),
                'actual_hold_days' => $entity->holdPeriod->getActualHoldDays(),
                'start_date' => $entity->holdPeriod->getStartDate()->format('Y-m-d H:i:s'),
                'end_date' => $entity->holdPeriod->getEndDate()->format('Y-m-d H:i:s'),
                'status' => $entity->status->value,
                'source_type' => $entity->sourceType,
                'source_id' => $entity->sourceId,
                'vertical' => $entity->vertical,
                'user_tier' => $entity->userTier,
                'correlation_id' => $entity->correlationId,
                'metadata' => $entity->metadata,
            ]
        );
    }

    /**
     * Scope for user ID.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for tenant ID.
     */
    public function scopeForTenant($query, ?string $tenantId)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }
        return $query;
    }

    /**
     * Scope for status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for active batches (locked or vesting).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['locked', 'vesting']);
    }

    /**
     * Scope for batches that have ended hold period.
     */
    public function scopeHoldEnded($query)
    {
        return $query->where('end_date', '<=', now());
    }
}
