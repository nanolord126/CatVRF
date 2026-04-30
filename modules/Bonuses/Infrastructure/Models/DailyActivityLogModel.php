<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Bonuses\Domain\Entities\DailyActivityLog as DomainEntity;
use Modules\Bonuses\Domain\ValueObjects\ActivityScore;
use Modules\Bonuses\Domain\ValueObjects\StreakCount;

/**
 * Eloquent Model: DailyActivityLogModel
 *
 * Infrastructure model for persisting DailyActivityLog domain entities to the database.
 * Maps to the daily_activity_logs table.
 *
 * Table schema:
 * - id: UUID primary key
 * - user_id: Foreign key to users table
 * - tenant_id: Foreign key to tenants table (nullable)
 * - activity_date: Date of activity (YYYY-MM-DD)
 * - activity_score_json: JSON containing activity scores
 * - streak_count_json: JSON containing streak data
 * - bonus_multiplier: Current bonus multiplier
 * - activity_hold_reduction: Hold days reduction from activity
 * - streak_hold_reduction: Hold days reduction from streak
 * - total_hold_reduction: Total hold days reduction
 * - loyalty_points_awarded: Loyalty points awarded
 * - created_at, updated_at: Timestamps
 * - correlation_id: Correlation ID for distributed tracing
 * - metadata: Additional metadata (JSON)
 *
 * Relationships:
 * - user: BelongsTo User model
 * - tenant: BelongsTo Tenant model (nullable)
 *
 * Domain mapping:
 * - toDomainEntity(): Converts model to domain entity
 * - fromDomainEntity(): Creates model from domain entity
 *
 * @see Modules\Bonuses\Domain\Entities\DailyActivityLog
 */
class DailyActivityLogModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'daily_activity_logs';

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
        'activity_date',
        'activity_score_json',
        'streak_count_json',
        'bonus_multiplier',
        'activity_hold_reduction',
        'streak_hold_reduction',
        'total_hold_reduction',
        'loyalty_points_awarded',
        'correlation_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'activity_score_json' => 'array',
        'streak_count_json' => 'array',
        'bonus_multiplier' => 'float',
        'activity_hold_reduction' => 'integer',
        'streak_hold_reduction' => 'integer',
        'total_hold_reduction' => 'integer',
        'loyalty_points_awarded' => 'integer',
        'metadata' => 'array',
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
        return DomainEntity::fromArray([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tenant_id' => $this->tenant_id,
            'activity_date' => $this->activity_date,
            'activity_score' => $this->activity_score_json,
            'streak_count' => $this->streak_count_json,
            'bonus_multiplier' => $this->bonus_multiplier,
            'activity_hold_reduction' => $this->activity_hold_reduction,
            'streak_hold_reduction' => $this->streak_hold_reduction,
            'total_hold_reduction' => $this->total_hold_reduction,
            'loyalty_points_awarded' => $this->loyalty_points_awarded,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlation_id,
            'metadata' => $this->metadata,
        ]);
    }

    /**
     * Creates model from domain entity.
     */
    public static function fromDomainEntity(DomainEntity $entity): self
    {
        return self::updateOrCreate(
            ['id' => $entity->id],
            [
                'user_id' => $entity->userId,
                'tenant_id' => $entity->tenantId,
                'activity_date' => $entity->activityDate,
                'activity_score_json' => $entity->activityScore->toArray(),
                'streak_count_json' => $entity->streakCount->toArray(),
                'bonus_multiplier' => $entity->bonusMultiplier,
                'activity_hold_reduction' => $entity->activityHoldReduction,
                'streak_hold_reduction' => $entity->streakHoldReduction,
                'total_hold_reduction' => $entity->totalHoldReduction,
                'loyalty_points_awarded' => $entity->loyaltyPointsAwarded,
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
     * Scope for activity date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('activity_date', $date);
    }

    /**
     * Scope for date range.
     */
    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    /**
     * Scope for finalized logs.
     */
    public function scopeFinalized($query)
    {
        return $query->where('loyalty_points_awarded', '>', 0);
    }

    /**
     * Scope for logs with streak maintained.
     */
    public function scopeWithStreak($query)
    {
        return $query->whereJsonLength('streak_count_json->current_days', '>', 0);
    }

    /**
     * Scope for logs meeting threshold.
     */
    public function scopeMeetingThreshold($query)
    {
        return $query->whereJsonLength('activity_score_json->total_score', '>=', 30);
    }
}
