<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Bonuses\Domain\Entities\DailyQuest as DomainEntity;

/**
 * Eloquent Model: DailyQuestModel
 *
 * Infrastructure model for persisting DailyQuest domain entities to the database.
 * Maps to the daily_quests table.
 *
 * Table schema:
 * - id: UUID primary key
 * - type: Quest type enum (LOGIN, PRODUCT_VIEW, AR_TRY_ON, etc.)
 * - title: Quest title (displayed to user)
 * - description: Quest description
 * - requirements_json: Quest requirements (JSON)
 * - bonus_reward: Instant bonus reward (kopecks)
 * - hold_reduction_reward: Hold days reduction reward
 * - multiplier_reward: Bonus multiplier reward (percentage)
 * - loyalty_points_reward: Loyalty points reward
 * - quest_date: Quest date (YYYY-MM-DD)
 * - vertical: Vertical where quest applies (nullable)
 * - difficulty: Difficulty level (easy, medium, hard)
 * - is_sponsored: Whether this is a sponsored quest
 * - sponsor_name: Sponsor brand name (if sponsored)
 * - sponsor_reward: Sponsor reward override (kopecks)
 * - priority: Quest priority (higher = more likely to be shown)
 * - expires_at: Timestamp when quest expires
 * - correlation_id: Correlation ID for distributed tracing
 * - metadata: Additional metadata (JSON)
 * - created_at, updated_at: Timestamps
 *
 * Domain mapping:
 * - toDomainEntity(): Converts model to domain entity
 * - fromDomainEntity(): Creates model from domain entity
 *
 * @see Modules\Bonuses\Domain\Entities\DailyQuest
 */
class DailyQuestModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'daily_quests';

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
        'type',
        'title',
        'description',
        'requirements_json',
        'bonus_reward',
        'hold_reduction_reward',
        'multiplier_reward',
        'loyalty_points_reward',
        'quest_date',
        'vertical',
        'difficulty',
        'is_sponsored',
        'sponsor_name',
        'sponsor_reward',
        'priority',
        'expires_at',
        'correlation_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'requirements_json' => 'array',
        'bonus_reward' => 'integer',
        'hold_reduction_reward' => 'integer',
        'multiplier_reward' => 'float',
        'loyalty_points_reward' => 'integer',
        'is_sponsored' => 'boolean',
        'sponsor_reward' => 'integer',
        'priority' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Converts model to domain entity.
     */
    public function toDomainEntity(): DomainEntity
    {
        return DomainEntity::fromArray([
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'requirements' => $this->requirements_json,
            'bonus_reward' => $this->bonus_reward,
            'hold_reduction_reward' => $this->hold_reduction_reward,
            'multiplier_reward' => $this->multiplier_reward,
            'loyalty_points_reward' => $this->loyalty_points_reward,
            'quest_date' => $this->quest_date,
            'vertical' => $this->vertical,
            'difficulty' => $this->difficulty,
            'is_sponsored' => $this->is_sponsored,
            'sponsor_name' => $this->sponsor_name,
            'sponsor_reward' => $this->sponsor_reward,
            'priority' => $this->priority,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at->format('Y-m-d H:i:s'),
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
                'type' => $entity->type->value,
                'title' => $entity->title,
                'description' => $entity->description,
                'requirements_json' => $entity->requirements,
                'bonus_reward' => $entity->bonusReward,
                'hold_reduction_reward' => $entity->holdReductionReward,
                'multiplier_reward' => $entity->multiplierReward,
                'loyalty_points_reward' => $entity->loyaltyPointsReward,
                'quest_date' => $entity->questDate,
                'vertical' => $entity->vertical,
                'difficulty' => $entity->difficulty,
                'is_sponsored' => $entity->isSponsored,
                'sponsor_name' => $entity->sponsorName,
                'sponsor_reward' => $entity->sponsorReward,
                'priority' => $entity->priority,
                'expires_at' => $entity->expiresAt->format('Y-m-d H:i:s'),
                'correlation_id' => $entity->correlationId,
                'metadata' => $entity->metadata,
            ]
        );
    }

    /**
     * Scope for quest type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for quest date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('quest_date', $date);
    }

    /**
     * Scope for vertical.
     */
    public function scopeForVertical($query, ?string $vertical)
    {
        if ($vertical) {
            return $query->where('vertical', $vertical);
        }
        return $query->whereNull('vertical');
    }

    /**
     * Scope for difficulty.
     */
    public function scopeForDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope for sponsored quests.
     */
    public function scopeSponsored($query)
    {
        return $query->where('is_sponsored', true);
    }

    /**
     * Scope for non-sponsored quests.
     */
    public function scopeNotSponsored($query)
    {
        return $query->where('is_sponsored', false);
    }

    /**
     * Scope for active quests (not expired).
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope for expired quests.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope ordered by priority.
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
