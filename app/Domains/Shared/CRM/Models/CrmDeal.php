<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Traits\TenantScoped;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * CrmDeal — сделка/лид в воронке.
 * Может быть связана с заказом, бронированием, записью и т.д.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmDeal extends Model
{
    use TenantScoped, SoftDeletes;

    protected $table = 'crm_deals';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'pipeline_id',
        'stage_id',
        'customer_id',
        'uuid',
        'title',
        'description',
        'value',
        'currency',
        'status',
        'source',
        'priority',
        'expected_close_date',
        'actual_close_date',
        'won_reason',
        'lost_reason',
        'assigned_to_id',
        'contact_person',
        'contact_phone',
        'contact_email',
        'marketplace_order_id',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'value' => 'integer',
        'priority' => 'integer',
        'expected_close_date' => 'datetime',
        'actual_close_date' => 'datetime',
        'metadata' => 'json',
    ];

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->where('status', 'won');
    }

    public function scopeLost(Builder $query): Builder
    {
        return $query->where('status', 'lost');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('expected_close_date', '<', CarbonImmutable::now())
            ->whereIn('status', ['new', 'in_progress', 'negotiation']);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'stage_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CrmCustomer::class, 'customer_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'deal_id');
    }

    public function moveToStage(CrmStage $stage, ?string $reason = null): void
    {
        $this->stage_id = $stage->id;

        if ($stage->is_won) {
            $this->status = 'won';
            $this->won_reason = $reason;
            $this->actual_close_date = CarbonImmutable::now();
        } elseif ($stage->is_lost) {
            $this->status = 'lost';
            $this->lost_reason = $reason;
            $this->actual_close_date = CarbonImmutable::now();
        } else {
            $this->status = 'in_progress';
        }

        $this->save();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
