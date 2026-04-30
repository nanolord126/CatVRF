<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;

/**
 * Deal — Сделка/лид в CRM
 * 
 * Представляет сделку, лид или заказ в CRM системе.
 * Проходит через этапы воронки (Pipeline → Stage).
 */
final class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'pipeline_id',
        'stage_id',
        'customer_id',
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
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'value' => 'integer',
        'expected_close_date' => 'datetime',
        'actual_close_date' => 'datetime',
        'priority' => 'integer', // 1-5
        'metadata' => 'json',
        'status' => \Modules\CatCRM\Domain\Enums\DealStatus::class,
    ];

    protected $table = 'crm_deals';

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
     * Воронка
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * Текущий этап
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    /**
     * Клиент
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Ответственный менеджер
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to_id');
    }

    /**
     * Задачи по сделке
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Взаимодействия по сделке
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    /**
     * Теги
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\CatCRM\Domain\Entities\Tag::class, 'crm_deal_tags');
    }

    /**
     * Связь с заказом маркетплейса (если применимо)
     */
    public function marketplaceOrder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class, 'marketplace_order_id');
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

    public function scopeByPipeline($query, int $pipelineId)
    {
        return $query->where('pipeline_id', $pipelineId);
    }

    public function scopeByStage($query, int $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStatus($query, \Modules\CatCRM\Domain\Enums\DealStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            \Modules\CatCRM\Domain\Enums\DealStatus::New,
            \Modules\CatCRM\Domain\Enums\DealStatus::InProgress,
            \Modules\CatCRM\Domain\Enums\DealStatus::Negotiation,
        ]);
    }

    public function scopeWon($query)
    {
        return $query->where('status', \Modules\CatCRM\Domain\Enums\DealStatus::Won);
    }

    public function scopeLost($query)
    {
        return $query->where('status', \Modules\CatCRM\Domain\Enums\DealStatus::Lost);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to_id', $userId);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', '>=', 4);
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_close_date', '<', now())
            ->whereIn('status', [
                \Modules\CatCRM\Domain\Enums\DealStatus::New,
                \Modules\CatCRM\Domain\Enums\DealStatus::InProgress,
                \Modules\CatCRM\Domain\Enums\DealStatus::Negotiation,
            ]);
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Переместить сделку на другой этап
     */
    public function moveToStage(Stage $stage, ?string $reason = null): bool
    {
        $oldStage = $this->stage;
        
        $this->stage_id = $stage->id;
        
        // Автоматический статус на основе этапа
        if ($stage->is_won_stage) {
            $this->status = \Modules\CatCRM\Domain\Enums\DealStatus::Won;
            $this->actual_close_date = CarbonImmutable::now();
            $this->won_reason = $reason;
        } elseif ($stage->is_lost_stage) {
            $this->status = \Modules\CatCRM\Domain\Enums\DealStatus::Lost;
            $this->actual_close_date = CarbonImmutable::now();
            $this->lost_reason = $reason;
        } elseif ($this->status === \Modules\CatCRM\Domain\Enums\DealStatus::New) {
            $this->status = \Modules\CatCRM\Domain\Enums\DealStatus::InProgress;
        }

        return $this->save();
    }

    /**
     * Выиграть сделку
     */
    public function win(?string $reason = null): bool
    {
        $wonStage = $this->pipeline->stages()->where('is_won_stage', true)->first();
        
        if ($wonStage) {
            return $this->moveToStage($wonStage, $reason);
        }

        $this->status = \Modules\CatCRM\Domain\Enums\DealStatus::Won;
        $this->actual_close_date = CarbonImmutable::now();
        $this->won_reason = $reason;

        return $this->save();
    }

    /**
     * Проиграть сделку
     */
    public function lose(string $reason): bool
    {
        $lostStage = $this->pipeline->stages()->where('is_lost_stage', true)->first();
        
        if ($lostStage) {
            return $this->moveToStage($lostStage, $reason);
        }

        $this->status = \Modules\CatCRM\Domain\Enums\DealStatus::Lost;
        $this->actual_close_date = CarbonImmutable::now();
        $this->lost_reason = $reason;

        return $this->save();
    }

    /**
     * Получить время нахождения на текущем этапе
     */
    public function getTimeInStage(): int
    {
        return $this->updated_at->diffInDays(now());
    }

    /**
     * Проверить, просрочена ли сделка
     */
    public function isOverdue(): bool
    {
        return $this->expected_close_date !== null
            && $this->expected_close_date->isPast()
            && !$this->status->isFinal();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматически назначаем первый этап воронки
            if ($model->stage_id === null && $model->pipeline_id !== null) {
                $pipeline = Pipeline::find($model->pipeline_id);
                if ($pipeline) {
                    $firstStage = $pipeline->getFirstStage();
                    if ($firstStage) {
                        $model->stage_id = $firstStage->id;
                    }
                }
            }
        });
    }
}
