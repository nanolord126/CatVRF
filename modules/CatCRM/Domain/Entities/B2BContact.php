<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;

/**
 * B2BContact — Контактное лицо в B2B CRM
 * 
 * Представляет контактное лицо в корпоративном клиенте.
 */
final class B2BContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'lead_id',
        'deal_id',
        'name',
        'position',
        'department',
        'email',
        'phone',
        'mobile',
        'type',
        'is_primary',
        'is_decision_maker',
        'linkedin',
        'telegram',
        'whatsapp',
        'notes',
        'communication_preferences',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_decision_maker' => 'boolean',
        'communication_preferences' => 'json',
        'metadata' => 'json',
        'type' => \Modules\CatCRM\Domain\Enums\ContactType::class,
    ];

    protected $table = 'crm_b2b_contacts';

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

    public function lead(): BelongsTo
    {
        return $this->belongsTo(B2BLead::class, 'lead_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(B2BDeal::class, 'deal_id');
    }

    public function interactions(): BelongsToMany
    {
        return $this->belongsToMany(Interaction::class, 'crm_contact_interactions');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByLead($query, int $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeDecisionMakers($query)
    {
        return $query->where('is_decision_maker', true);
    }

    public function scopeByType($query, \Modules\CatCRM\Domain\Enums\ContactType $type)
    {
        return $query->where('type', $type);
    }

    // ========================
    // METHODS
    // ========================

    public function markAsPrimary(): bool
    {
        // Сначала убираем primary у других контактов того же лида/сделки
        if ($this->lead_id) {
            self::where('lead_id', $this->lead_id)
                ->where('id', '!=', $this->id)
                ->update(['is_primary' => false]);
        } elseif ($this->deal_id) {
            self::where('deal_id', $this->deal_id)
                ->where('id', '!=', $this->id)
                ->update(['is_primary' => false]);
        }

        return $this->update(['is_primary' => true]);
    }

    public function markAsDecisionMaker(): bool
    {
        return $this->update(['is_decision_maker' => true]);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            $model->correlation_id ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
