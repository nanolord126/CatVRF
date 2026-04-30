<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model: UserQuestProgressModel
 *
 * Infrastructure model for tracking individual user progress on daily quests.
 * Maps to the user_quest_progress table.
 *
 * Table schema:
 * - id: UUID primary key
 * - user_id: Foreign key to users table
 * - tenant_id: Foreign key to tenants table (nullable)
 * - quest_id: Foreign key to daily_quests table
 * - status: Quest status enum (in_progress, completed, claimed, expired)
 * - progress_json: Progress data (JSON) - tracks completion of quest requirements
 * - claimed_at: Timestamp when rewards were claimed
 * - completed_at: Timestamp when quest was completed
 * - correlation_id: Correlation ID for distributed tracing
 * - metadata: Additional metadata (JSON)
 * - created_at, updated_at: Timestamps
 *
 * Relationships:
 * - user: BelongsTo User model
 * - quest: BelongsTo DailyQuestModel
 * - tenant: BelongsTo Tenant model (nullable)
 *
 * Progress tracking:
 * - Each quest has specific requirements (e.g., view 8 products, complete 2 AR try-ons)
 * - Progress is tracked in progress_json as key-value pairs
 * - Example: {"product_views": 5, "target": 8, "percentage": 62.5}
 * - Quest marked completed when all requirements met
 * - Quest marked claimed when user claims rewards
 * - Quest marked expired if not completed by expires_at
 *
 * @see Modules\Bonuses\Infrastructure\Models\DailyQuestModel
 */
class UserQuestProgressModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'user_quest_progress';

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
        'quest_id',
        'status',
        'progress_json',
        'claimed_at',
        'completed_at',
        'correlation_id',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'progress_json' => 'array',
        'claimed_at' => 'datetime',
        'completed_at' => 'datetime',
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
     * Relationship to quest.
     */
    public function quest(): BelongsTo
    {
        return $this->belongsTo(DailyQuestModel::class, 'quest_id');
    }

    /**
     * Relationship to tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.models.tenant'), 'tenant_id');
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
     * Scope for quest ID.
     */
    public function scopeForQuest($query, string $questId)
    {
        return $query->where('quest_id', $questId);
    }

    /**
     * Scope for status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for in-progress quests.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for completed quests.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for claimed quests.
     */
    public function scopeClaimed($query)
    {
        return $query->where('status', 'claimed');
    }

    /**
     * Scope for expired quests.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for quests that can be claimed (completed but not claimed).
     */
    public function scopeClaimable($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Updates progress for a specific requirement.
     */
    public function updateProgress(string $requirementKey, int $currentValue, int $targetValue): void
    {
        $progress = $this->progress_json ?? [];
        $percentage = $targetValue > 0 ? ($currentValue / $targetValue) * 100 : 0;

        $progress[$requirementKey] = [
            'current' => $currentValue,
            'target' => $targetValue,
            'percentage' => min(100, $percentage),
            'completed' => $currentValue >= $targetValue,
        ];

        $this->progress_json = $progress;
        $this->save();
    }

    /**
     * Checks if all quest requirements are completed.
     */
    public function isFullyCompleted(): bool
    {
        if (empty($this->progress_json)) {
            return false;
        }

        foreach ($this->progress_json as $requirement) {
            if (!($requirement['completed'] ?? false)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets overall progress percentage.
     */
    public function getOverallProgress(): float
    {
        if (empty($this->progress_json)) {
            return 0.0;
        }

        $totalPercentage = 0;
        $count = 0;

        foreach ($this->progress_json as $requirement) {
            $totalPercentage += $requirement['percentage'] ?? 0;
            $count++;
        }

        return $count > 0 ? $totalPercentage / $count : 0.0;
    }

    /**
     * Marks quest as completed.
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Marks quest as claimed.
     */
    public function markAsClaimed(): void
    {
        $this->status = 'claimed';
        $this->claimed_at = now();
        $this->save();
    }

    /**
     * Marks quest as expired.
     */
    public function markAsExpired(): void
    {
        $this->status = 'expired';
        $this->save();
    }
}
