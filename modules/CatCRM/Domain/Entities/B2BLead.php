<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

/**
 * B2BLead — B2B лид в CRM
 * 
 * Представляет B2B лид (потенциального корпоративного клиента) в CRM системе.
 * Включает привязки к персоналу, складам и инвентаризации.
 */
final class B2BLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'vertical_id',
        'company_name',
        'company_inn',
        'company_kpp',
        'company_legal_address',
        'company_actual_address',
        'contact_person',
        'contact_position',
        'contact_email',
        'contact_phone',
        'website',
        'industry',
        'company_size',
        'annual_revenue',
        'requirement',
        'budget_range',
        'category',
        'status',
        'source',
        'assigned_to_id',
        'assigned_team_id',
        'priority',
        'expected_close_date',
        'probability',
        'notes',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'expected_close_date' => 'datetime',
        'probability' => 'integer',
        'priority' => 'integer',
        'annual_revenue' => 'integer',
        'metadata' => 'json',
        'status' => \Modules\CatCRM\Domain\Enums\LeadStatus::class,
    ];

    protected $table = 'crm_b2b_leads';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function vertical(): BelongsTo
    {
        return $this->belongsTo(\Modules\CatCRM\Domain\Entities\Vertical::class, 'vertical_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'assigned_team_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(B2BContact::class, 'lead_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class, 'entity_id')
            ->where('entity_type', 'b2b_lead');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'entity_id')
            ->where('entity_type', 'b2b_lead');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'crm_b2b_lead_tags');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\Modules\Supermarket\Domain\Entities\Warehouse::class, 'default_warehouse_id');
    }

    public function inventoryRequests(): HasMany
    {
        return $this->hasMany(\Modules\Inventory\Domain\Entities\InventoryRequest::class, 'b2b_lead_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByVertical($query, string $vertical)
    {
        return $query->where('vertical_id', $vertical);
    }

    public function scopeByStatus($query, \Modules\CatCRM\Domain\Enums\LeadStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
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
                \Modules\CatCRM\Domain\Enums\LeadStatus::New,
                \Modules\CatCRM\Domain\Enums\LeadStatus::Contacted,
                \Modules\CatCRM\Domain\Enums\LeadStatus::Qualified,
            ]);
    }

    // ========================
    // METHODS
    // ========================

    public function convertToDeal(): B2BDeal
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $deal = B2BDeal::create([
                'tenant_id' => $this->tenant_id,
                'business_group_id' => $this->business_group_id,
                'vertical_id' => $this->vertical_id,
                'lead_id' => $this->id,
                'company_name' => $this->company_name,
                'contact_person' => $this->contact_person,
                'contact_email' => $this->contact_email,
                'contact_phone' => $this->contact_phone,
                'title' => "Сделка с {$this->company_name}",
                'value' => $this->estimateValue(),
                'status' => \Modules\CatCRM\Domain\Enums\DealStatus::New,
                'priority' => $this->priority,
                'assigned_to_id' => $this->assigned_to_id,
                'expected_close_date' => $this->expected_close_date,
                'correlation_id' => $this->correlation_id,
            ]);

            $this->update([
                'status' => \Modules\CatCRM\Domain\Enums\LeadStatus::Converted,
            ]);

            return $deal;
        });
    }

    public function estimateValue(): int
    {
        $budgetRanges = [
            'до 100 000 ₽' => 50000,
            '100 000 - 500 000 ₽' => 300000,
            '500 000 - 1 000 000 ₽' => 750000,
            '1 000 000 - 5 000 000 ₽' => 2500000,
            'более 5 000 000 ₽' => 10000000,
        ];

        return $budgetRanges[$this->budget_range] ?? 0;
    }

    public function assignToUser(User $user): bool
    {
        return $this->update(['assigned_to_id' => $user->id]);
    }

    public function assignToTeam(\App\Models\Team $team): bool
    {
        return $this->update(['assigned_team_id' => $team->id]);
    }

    public function linkWarehouse(int $warehouseId): bool
    {
        return $this->update(['default_warehouse_id' => $warehouseId]);
    }

    public function createInventoryRequest(array $items): \Modules\Inventory\Domain\Entities\InventoryRequest
    {
        return \Modules\Inventory\Domain\Entities\InventoryRequest::create([
            'tenant_id' => $this->tenant_id,
            'b2b_lead_id' => $this->id,
            'request_type' => 'quote',
            'status' => 'pending',
            'items' => $items,
            'correlation_id' => $this->correlationId(),
        ]);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            $model->correlation_id ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }

    public function correlationId(): string
    {
        return $this->correlation_id ?? \Illuminate\Support\Str::uuid()->toString();
    }
}
