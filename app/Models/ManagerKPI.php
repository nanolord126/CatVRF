<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ManagerKPI extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'manager_id',
        'tenant_id',
        'vertical_id',
        'kpi_type',
        'kpi_name',
        'description',
        'target_value',
        'target_unit',
        'minimum_value',
        'period_type',
        'period_start',
        'period_end',
        'current_value',
        'progress_percentage',
        'status',
        'completed_at',
        'failed_at',
        'bonus_eligible',
        'bonus_multiplier',
        'bonus_amount',
        'targets',
        'metrics',
        'metadata',
    ];

    protected $casts = [
        'target_value' => 'decimal:2',
        'minimum_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'bonus_multiplier' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'targets' => 'array',
        'metrics' => 'array',
        'metadata' => 'array',
    ];

    // KPI Types
    public const TYPE_SALES_REVENUE = 'sales_revenue';
    public const TYPE_SALES_COUNT = 'sales_count';
    public const TYPE_CONVERSION_RATE = 'conversion_rate';
    public const TYPE_TASK_COMPLETION = 'task_completion';
    public const TYPE_CLIENT_RETENTION = 'client_retention';
    public const TYPE_NEW_CLIENTS = 'new_clients';
    public const TYPE_BONUS_EARNED = 'bonus_earned';

    // Period Types
    public const PERIOD_DAILY = 'daily';
    public const PERIOD_WEEKLY = 'weekly';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_YEARLY = 'yearly';

    // Status
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Relations
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function vertical(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vertical::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ManagerKPILog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeForPeriod($query, string $startDate, string $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('kpi_type', $type);
    }

    public function scopeBonusEligible($query)
    {
        return $query->where('bonus_eligible', true);
    }

    // Methods
    public function updateValue(float $newValue, string $eventType, ?string $referenceType = null, ?int $referenceId = null): void
    {
        $previousValue = $this->current_value;
        $this->current_value = $newValue;
        
        // Calculate progress
        if ($this->target_value > 0) {
            $this->progress_percentage = ($newValue / $this->target_value) * 100;
        }
        
        $this->save();

        // Log the change
        ManagerKPILog::create([
            'manager_kpi_id' => $this->id,
            'manager_id' => $this->manager_id,
            'previous_value' => $previousValue,
            'new_value' => $newValue,
            'delta' => $newValue - $previousValue,
            'event_type' => $eventType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        // Check if completed
        if ($this->current_value >= $this->target_value && $this->status === self::STATUS_ACTIVE) {
            $this->complete();
        }
    }

    public function complete(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->progress_percentage = 100;
        $this->save();
    }

    public function fail(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->failed_at = now();
        $this->save();
    }

    public function isAchieved(): bool
    {
        return $this->current_value >= $this->target_value;
    }

    public function isFailed(): bool
    {
        if ($this->status === self::STATUS_FAILED) {
            return true;
        }

        if ($this->minimum_value !== null && $this->current_value < $this->minimum_value) {
            return $this->period_end->isPast();
        }

        return false;
    }

    public function getBonusAmount(): float
    {
        if (!$this->bonus_eligible || !$this->isAchieved()) {
            return 0;
        }

        return ($this->bonus_amount ?? 0) * $this->bonus_multiplier;
    }
}
