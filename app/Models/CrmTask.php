<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * CrmTask — задача CRM-оператора (звонок, письмо, встреча и т.д.).
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $assignee_id
 * @property int|null $related_lead_id
 * @property string $title
 * @property string $type call|email|meeting|follow_up|demo
 * @property string $priority low|normal|high|urgent
 * @property string $status open|in_progress|done|cancelled
 * @property string|null $description
 * @property string|null $result
 * @property Carbon $due_at
 * @property string|null $correlation_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class CrmTask extends Model
{
    protected $table = 'crm_tasks';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'assignee_id',
        'related_lead_id',
        'title',
        'type',
        'priority',
        'status',
        'description',
        'result',
        'due_at',
        'correlation_id',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'related_lead_id');
    }

    // ── Scopes ───────────────────────────────────────────────

    public function scopeOpen($query): Builder
    {
        return $query->whereNotIn('status', ['done', 'cancelled']);
    }

    public function scopeOverdue($query): Builder
    {
        return $query->open()->where('due_at', '<', CarbonImmutable::now());
    }

    public function scopeUrgent($query): Builder
    {
        return $query->where('priority', 'urgent')->open();
    }

    protected static function booted(): void
    {
        self::creating(function (self $model): void {
            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }
}
