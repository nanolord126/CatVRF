<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

/**
 * Task — Задача в CRM
 * 
 * Представляет задачу, связанную с клиентом, сделкой или независимую.
 * Используется для планирования действий менеджеров.
 */
final class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'vertical_id',
        'deal_id',
        'customer_id',
        'supplier_id',
        'tender_id',
        'b2b_order_id',
        'assigned_to_id',
        'created_by_id',
        'title',
        'description',
        'category',
        'type',
        'priority',
        'status',
        'progress',
        'due_date',
        'completed_at',
        'reminder_date',
        'reminder_sent_at',
        'reminder_sent',
        'location',
        'business_type',
        'kpi_weight',
        'kpi_tracked',
        'kpi_period_start',
        'kpi_period_end',
        'parent_task_id',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'reminder_date' => 'datetime',
        'kpi_period_start' => 'datetime',
        'kpi_period_end' => 'datetime',
        'priority' => \Modules\CatCRM\Domain\Enums\TaskPriority::class,
        'status' => \Modules\CatCRM\Domain\Enums\TaskStatus::class,
        'type' => \Modules\CatCRM\Domain\Enums\TaskType::class,
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
     * Vertical
     */
    public function vertical(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vertical::class);
    }

    /**
     * Связанный поставщик (для B2B)
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    /**
     * Связанный тендер
     */
    public function tender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tender::class);
    }

    /**
     * Связанный B2B заказ
     */
    public function b2bOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\B2BOrder::class);
    }

    /**
     * Business Group (филиал)
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    /**
     * Связанная сделка
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * Связанный клиент
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Ответственный исполнитель
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    /**
     * Родительская задача (для подзадач)
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Подзадачи
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Создатель задачи
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Комментарии к задаче
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
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

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to_id', $userId);
    }

    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by_id', $userId);
    }

    public function scopeByStatus($query, \Modules\CatCRM\Domain\Enums\TaskStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, \Modules\CatCRM\Domain\Enums\TaskPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, \Modules\CatCRM\Domain\Enums\TaskType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByVertical($query, int $verticalId)
    {
        return $query->where('vertical_id', $verticalId);
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByBusinessType($query, string $businessType)
    {
        return $query->where('business_type', $businessType);
    }

    public function scopeKPITracked($query)
    {
        return $query->where('kpi_tracked', true);
    }

    public function scopeByKPIPeriod($query, $startDate, $endDate)
    {
        return $query->where('kpi_period_start', '>=', $startDate)
            ->where('kpi_period_end', '<=', $endDate);
    }

    public function scopePending($query)
    {
        return $query->where('status', \Modules\CatCRM\Domain\Enums\TaskStatus::Pending);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', \Modules\CatCRM\Domain\Enums\TaskStatus::InProgress);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', \Modules\CatCRM\Domain\Enums\TaskStatus::Completed);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', [
                \Modules\CatCRM\Domain\Enums\TaskStatus::Pending,
                \Modules\CatCRM\Domain\Enums\TaskStatus::InProgress,
            ]);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
            ->whereIn('status', [
                \Modules\CatCRM\Domain\Enums\TaskStatus::Pending,
                \Modules\CatCRM\Domain\Enums\TaskStatus::InProgress,
            ]);
    }

    public function scopeDueSoon($query, int $hours = 24)
    {
        return $query->whereBetween('due_date', [now(), now()->addHours($hours)])
            ->whereIn('status', [
                \Modules\CatCRM\Domain\Enums\TaskStatus::Pending,
                \Modules\CatCRM\Domain\Enums\TaskStatus::InProgress,
            ]);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [
            \Modules\CatCRM\Domain\Enums\TaskPriority::High,
            \Modules\CatCRM\Domain\Enums\TaskPriority::Urgent,
        ]);
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Завершить задачу
     */
    public function complete(): bool
    {
        $this->status = \Modules\CatCRM\Domain\Enums\TaskStatus::Completed;
        $this->completed_at = CarbonImmutable::now();
        $this->progress = 100;

        return $this->save();
    }

    /**
     * Отменить задачу
     */
    public function cancel(): bool
    {
        $this->status = \Modules\CatCRM\Domain\Enums\TaskStatus::Cancelled;

        return $this->save();
    }

    /**
     * Начать выполнение задачи
     */
    public function start(): bool
    {
        $this->status = \Modules\CatCRM\Domain\Enums\TaskStatus::InProgress;

        return $this->save();
    }

    /**
     * Обновить прогресс задачи
     */
    public function updateProgress(int $progress): bool
    {
        $this->progress = max(0, min(100, $progress));
        
        if ($this->progress >= 100 && $this->status !== \Modules\CatCRM\Domain\Enums\TaskStatus::Completed) {
            return $this->complete();
        }

        return $this->save();
    }

    /**
     * Проверить, просрочена ли задача
     */
    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && !$this->status->isCompleted();
    }

    /**
     * Получить время до дедлайна
     */
    public function getTimeUntilDue(): ?int
    {
        if ($this->due_date === null) {
            return null;
        }

        return now()->diffInHours($this->due_date, false);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
