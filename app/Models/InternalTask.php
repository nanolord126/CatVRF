<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin IdeHelperInternalTask
 */
final class InternalTask extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'creator_id',
        'assignee_id',
        'controller_id',
        'title',
        'description',
        'priority',
        'status',
        'type',
        'due_date',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_REVIEW = 'review';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_OVERDUE = 'overdue';

    public const TYPE_NOTIFICATION = 'notification';
    public const TYPE_CAMPAIGN = 'campaign';
    public const TYPE_REPORT = 'report';
    public const TYPE_REVIEW = 'review';
    public const TYPE_OTHER = 'other';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function controller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'controller_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'task_id');
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(TaskCheckpoint::class, 'task_id')->orderBy('sort_order');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAssignee($query, int $userId)
    {
        return $query->where('assignee_id', $userId);
    }

    public function scopeByController($query, int $userId)
    {
        return $query->where('controller_id', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function scopeDueSoon($query, int $days = 3)
    {
        return $query->whereBetween('due_date', [now(), now()->addDays($days)])
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function isDueSoon(int $days = 3): bool
    {
        return $this->due_date && $this->due_date->between(now(), now()->addDays($days)) && !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function markAsReview(): void
    {
        $this->update(['status' => self::STATUS_REVIEW]);
    }

    public function getProgressAttribute(): float
    {
        if ($this->checkpoints->isEmpty()) {
            return $this->status === self::STATUS_COMPLETED ? 100 : 0;
        }

        $completed = $this->checkpoints->where('is_completed', true)->count();
        return ($completed / $this->checkpoints->count()) * 100;
    }
}
