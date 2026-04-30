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
 * CrmTask — задача в CRM.
 * Может быть привязана к сделке, клиенту или быть самостоятельной.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmTask extends Model
{
    use TenantScoped, SoftDeletes;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'assigned_to_id',
        'created_by_id',
        'uuid',
        'title',
        'description',
        'type',
        'status',
        'priority',
        'due_date',
        'completed_at',
        'reminder_sent_at',
        'location',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', CarbonImmutable::now())
            ->where('status', '!=', 'completed');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CrmCustomer::class, 'customer_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function complete(): void
    {
        $this->status = 'completed';
        $this->completed_at = CarbonImmutable::now();
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
