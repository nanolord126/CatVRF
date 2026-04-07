<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * CrmTask — задача CRM-оператора (звонок, письмо, встреча и т.д.).
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * @property int         $id
 * @property int|null    $tenant_id
 * @property int|null    $assignee_id
 * @property int|null    $related_lead_id
 * @property string      $title
 * @property string      $type          call|email|meeting|follow_up|demo
 * @property string      $priority      low|normal|high|urgent
 * @property string      $status        open|in_progress|done|cancelled
 * @property string|null $description
 * @property string|null $result
 * @property \Carbon\Carbon $due_at
 * @property string|null $correlation_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
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

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }

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

    public function scopeOpen($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotIn('status', ['done', 'cancelled']);
    }

    public function scopeOverdue($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->open()->where('due_at', '<', now());
    }

    public function scopeUrgent($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('priority', 'urgent')->open();
    }
}
