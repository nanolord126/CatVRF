<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;

use App\Models\BusinessGroup;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * CRM Client — универсальная карточка клиента.
 * Привязана к tenant, содержит vertical_data для вертикаль-специфичных полей.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmClient extends Model
{

    protected static function newFactory(): \Database\Factories\CRM\CrmClientFactory
    {
        return \Database\Factories\CRM\CrmClientFactory::new();
    }

    protected $table = 'crm_clients';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id',
        'uuid',
        'correlation_id',
        'tags',
        'first_name',
        'last_name',
        'company_name',
        'email',
        'phone',
        'phone_secondary',
        'client_type',
        'status',
        'source',
        'vertical',
        'addresses',
        'total_spent',
        'total_orders',
        'average_order_value',
        'bonus_points',
        'loyalty_tier',
        'segment',
        'last_interaction_at',
        'last_order_at',
        'preferences',
        'special_notes',
        'internal_notes',
        'vertical_data',
        'avatar_url',
        'preferred_language',
    ];

    protected $casts = [
        'tags' => 'json',
        'addresses' => 'json',
        'preferences' => 'json',
        'special_notes' => 'json',
        'vertical_data' => 'json',
        'total_spent' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'total_orders' => 'integer',
        'bonus_points' => 'integer',
        'last_interaction_at' => 'datetime',
        'last_order_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * Полное имя клиента (ФИО или название компании).
     */
    public function getFullNameAttribute(): string
    {
        if ($this->company_name) {
            return $this->company_name;
        }

        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByVertical(Builder $query, string $vertical): Builder
    {
        return $query->where('vertical', $vertical);
    }

    public function scopeBySegment(Builder $query, string $segment): Builder
    {
        return $query->where('segment', $segment);
    }

    public function scopeSleeping(Builder $query, int $daysInactive = 60): Builder
    {
        return $query->where('last_order_at', '<', now()->subDays($daysInactive));
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CrmInteraction::class, 'crm_client_id');
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(CrmSegment::class, 'crm_client_segment', 'crm_client_id', 'crm_segment_id')
            ->withTimestamps();
    }

    public function automationLogs(): HasMany
    {
        return $this->hasMany(CrmAutomationLog::class, 'crm_client_id');
    }

    public function beautyProfile(): HasOne
    {
        return $this->hasOne(CrmBeautyProfile::class, 'crm_client_id');
    }

    public function hotelGuestProfile(): HasOne
    {
        return $this->hasOne(CrmHotelGuestProfile::class, 'crm_client_id');
    }

    public function flowerClientProfile(): HasOne
    {
        return $this->hasOne(CrmFlowerClientProfile::class, 'crm_client_id');
    }

    public function autoProfile(): HasOne
    {
        return $this->hasOne(CrmAutoProfile::class, 'crm_client_id');
    }

    public function foodProfile(): HasOne
    {
        return $this->hasOne(CrmFoodProfile::class, 'crm_client_id');
    }

    public function furnitureProfile(): HasOne
    {
        return $this->hasOne(CrmFurnitureProfile::class, 'crm_client_id');
    }

    public function fashionProfile(): HasOne
    {
        return $this->hasOne(CrmFashionProfile::class, 'crm_client_id');
    }

    public function fitnessProfile(): HasOne
    {
        return $this->hasOne(CrmFitnessProfile::class, 'crm_client_id');
    }

    public function realEstateProfile(): HasOne
    {
        return $this->hasOne(CrmRealEstateProfile::class, 'crm_client_id');
    }

    public function medicalProfile(): HasOne
    {
        return $this->hasOne(CrmMedicalProfile::class, 'crm_client_id');
    }

    public function educationProfile(): HasOne
    {
        return $this->hasOne(CrmEducationProfile::class, 'crm_client_id');
    }

    public function travelProfile(): HasOne
    {
        return $this->hasOne(CrmTravelProfile::class, 'crm_client_id');
    }

    public function petProfile(): HasOne
    {
        return $this->hasOne(CrmPetProfile::class, 'crm_client_id');
    }

    public function taxiProfile(): HasOne
    {
        return $this->hasOne(CrmTaxiProfile::class, 'crm_client_id');
    }

    public function electronicsProfile(): HasOne
    {
        return $this->hasOne(CrmElectronicsProfile::class, 'crm_client_id');
    }

    public function eventProfile(): HasOne
    {
        return $this->hasOne(CrmEventProfile::class, 'crm_client_id');
    }

    /**
     * Получить профиль по имени вертикали.
     */
    public function verticalProfile(): ?Model
    {
        return match ($this->vertical) {
            'beauty' => $this->beautyProfile,
            'hotel', 'hotels' => $this->hotelGuestProfile,
            'flowers' => $this->flowerClientProfile,
            'auto' => $this->autoProfile,
            'food' => $this->foodProfile,
            'furniture' => $this->furnitureProfile,
            'fashion' => $this->fashionProfile,
            'fitness' => $this->fitnessProfile,
            'real_estate', 'realestate' => $this->realEstateProfile,
            'medical' => $this->medicalProfile,
            'education' => $this->educationProfile,
            'travel' => $this->travelProfile,
            'pet', 'pets' => $this->petProfile,
            'taxi' => $this->taxiProfile,
            'electronics' => $this->electronicsProfile,
            'events', 'event_planning' => $this->eventProfile,
            default => null,
        };
    }
}
