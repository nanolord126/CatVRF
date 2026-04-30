<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTaskAssignment
 */
final class TaskAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'role',
        'assigned_at',
        'acknowledged_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public const ROLE_ASSIGNEE = 'assignee';
    public const ROLE_CONTROLLER = 'controller';
    public const ROLE_OBSERVER = 'observer';

    public function task(): BelongsTo
    {
        return $this->belongsTo(InternalTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function acknowledge(): void
    {
        $this->update(['acknowledged_at' => now()]);
    }
}
