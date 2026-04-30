<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

/**
 * ManagerKPI — KPI менеджера в CRM
 * 
 * Отслеживает показатели эффективности менеджеров:
 * - Выполнение задач
 * - Своевременность
 * - Удовлетворенность клиентов
 * - Продажи (для B2B/B2C)
 */
final class ManagerKPI extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_manager_kpi';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'manager_id',
        'vertical_id',
        'period_type',
        'period_start',
        'period_end',
        'targets',
        'actuals',
        'score',
        'status',
        'business_type',
        'tasks_assigned',
        'tasks_completed',
        'tasks_on_time',
        'tasks_overdue',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'targets' => 'json',
        'actuals' => 'json',
        'score' => 'decimal:2',
        'metadata' => 'json',
    ];

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Business Group (филиал)
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    /**
     * Manager (user)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Vertical
     */
    public function vertical(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vertical::class);
    }

    /**
     * History records
     */
    public function history(): HasMany
    {
        return $this->hasMany(ManagerKPIHistory::class, 'kpi_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBusinessGroup($query, ?int $businessGroupId)
    {
        if ($businessGroupId === null) {
            return $query->whereNull('business_group_id');
        }

        return $query->where('business_group_id', $businessGroupId);
    }

    public function scopeByManager($query, int $managerId)
    {
        return $query->where('manager_id', $managerId);
    }

    public function scopeByVertical($query, ?int $verticalId)
    {
        if ($verticalId === null) {
            return $query->whereNull('vertical_id');
        }

        return $query->where('vertical_id', $verticalId);
    }

    public function scopeByPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
            ->where('period_end', '<=', $endDate);
    }

    public function scopeByPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    public function scopeByBusinessType($query, string $businessType)
    {
        return $query->where('business_type', $businessType);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Рассчитать KPI score на основе actuals и targets
     */
    public function calculateScore(): float
    {
        $targets = $this->targets ?? [];
        $actuals = $this->actuals ?? [];
        
        if (empty($targets)) {
            return 0.0;
        }

        $totalScore = 0;
        $weightSum = 0;

        foreach ($targets as $key => $target) {
            $weight = $target['weight'] ?? 1.0;
            $actual = $actuals[$key] ?? 0;
            $targetValue = $target['value'] ?? 0;

            if ($targetValue > 0) {
                $achievement = min(100, ($actual / $targetValue) * 100);
                $totalScore += $achievement * $weight;
                $weightSum += $weight;
            }
        }

        $this->score = $weightSum > 0 ? $totalScore / $weightSum : 0;
        $this->save();

        return (float) $this->score;
    }

    /**
     * Обновить actuals значение
     */
    public function updateActual(string $key, $value): void
    {
        $actuals = $this->actuals ?? [];
        $actuals[$key] = $value;
        $this->actuals = $actuals;
        $this->save();
    }

    /**
     * Завершить период KPI
     */
    public function complete(): bool
    {
        $this->status = 'completed';
        $this->calculateScore();
        
        // Создать запись в истории
        $this->history()->create([
            'score' => $this->score,
            'actuals' => $this->actuals,
            'notes' => 'Period completed',
        ]);

        return $this->save();
    }

    /**
     * Получить процент выполнения задач
     */
    public function getTaskCompletionRate(): float
    {
        if ($this->tasks_assigned === 0) {
            return 0.0;
        }

        return ($this->tasks_completed / $this->tasks_assigned) * 100;
    }

    /**
     * Получить процент своевременного выполнения
     */
    public function getOnTimeRate(): float
    {
        if ($this->tasks_completed === 0) {
            return 0.0;
        }

        return ($this->tasks_on_time / $this->tasks_completed) * 100;
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
