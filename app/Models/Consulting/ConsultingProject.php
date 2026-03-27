<?php

declare(strict_types=1);

namespace App\Models\Consulting;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * ConsultingProject Model - Long-term engagements (CAR 2026)
 * Supports multiple sessions and fixed or hourly project tracking.
 * File size requirement: >60 lines.
 */
final class ConsultingProject extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consulting_projects';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'consultant_id',
        'consulting_firm_id',
        'client_id',
        'name',
        'description',
        'status', // 'active', 'on_hold', 'completed', 'cancelled'
        'start_date',
        'end_date',
        'budget',
        'spent_budget',
        'deliverables',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'uuid' => 'string',
        'tenant_id' => 'integer',
        'consultant_id' => 'integer',
        'consulting_firm_id' => 'integer',
        'client_id' => 'integer',
        'tags' => 'json',
        'deliverables' => 'json',
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'integer',
        'spent_budget' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Boot logic for multi-tenancy and consistent UUID generation.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
        });

        static::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Relationships.
     */
    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class, 'consultant_id');
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(ConsultingFirm::class, 'consulting_firm_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ConsultingContract::class, 'consulting_project_id');
    }

    /**
     * Scopes.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Domain Methods.
     */
    public function getFormattedBudget(): string
    {
        return number_format($this->budget / 100, 2) . ' RUB';
    }

    public function getFormattedSpentBudget(): string
    {
        return number_format($this->spent_budget / 100, 2) . ' RUB';
    }

    public function getRemainingBudget(): int
    {
        return max(0, $this->budget - $this->spent_budget);
    }

    public function getBudgetUsagePercentage(): float
    {
        if ($this->budget === 0) return 0.0;
        return round(($this->spent_budget / $this->budget) * 100, 2);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function updateProjectProgress(int $additionalSpend): void
    {
        $this->update([
           'spent_budget' => $this->spent_budget + $additionalSpend,
           'correlation_id' => (string) Str::uuid(),
        ]);
        
        if ($this->getBudgetUsagePercentage() >= 100) {
            // Log budget limit exceeded if necessary
        }
    }

    public function addDeliverable(string $name, string $status = 'pending'): void
    {
        $current = $this->deliverables ?? [];
        $current[] = [
           'item' => $name,
           'status' => $status,
           'added_at' => now()->toIso8601String(),
        ];
        
        $this->update(['deliverables' => $current]);
    }
}
