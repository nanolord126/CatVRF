<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IncidentResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'response_id',
        'security_event_id',
        'user_id',
        'tenant_id',
        'trigger_type',
        'trigger_condition',
        'trigger_data',
        'action_taken',
        'action_data',
        'status',
        'error_message',
        'executed_at',
        'rolled_back_at',
        'auto_rollback',
        'rollback_after_minutes',
        'correlation_id',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'action_data' => 'array',
        'executed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'auto_rollback' => 'boolean',
    ];

    public function securityEvent(): BelongsTo
    {
        return $this->belongsTo(SecurityEvent::class, 'security_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeExecuted($query)
    {
        return $query->where('status', 'executed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRolledBack($query)
    {
        return $query->whereNotNull('rolled_back_at');
    }

    public function scopeByTriggerType($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('executed_at', '>=', CarbonImmutable::now()->subHours($hours));
    }

    public function isRollable(): bool
    {
        return $this->status === 'executed' && $this->rolled_back_at === null;
    }

    public function isAutoRollback(): bool
    {
        return $this->auto_rollback && $this->rollback_after_minutes !== null;
    }

    public function shouldRollback(): bool
    {
        if (! $this->isAutoRollback()) {
            return false;
        }

        $rollbackAt = $this->executed_at->addMinutes($this->rollback_after_minutes);

        return CarbonImmutable::now()->gte($rollbackAt);
    }
}
