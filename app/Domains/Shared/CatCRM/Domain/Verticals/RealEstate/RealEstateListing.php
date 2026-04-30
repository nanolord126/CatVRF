<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\RealEstate;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Real Estate Listing — Объект недвижимости в вертикали Недвижимость
 * 
 * Расширяет базовую Deal модель специфичными полями для риелторских агентств.
 */
final class RealEstateListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'agent_id',
        'property_type',
        'listing_type',
        'property_title',
        'property_description',
        'address',
        'city',
        'region',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'price',
        'currency',
        'price_per_sqm',
        'area_total',
        'area_living',
        'area_kitchen',
        'area_land',
        'rooms_count',
        'bedrooms_count',
        'bathrooms_count',
        'floor',
        'total_floors',
        'building_year',
        'renovation_year',
        'building_type',
        'parking_type',
        'parking_spaces',
        'balcony',
        'terrace',
        'furnished',
        'furniture_description',
        'amenities',
        'utilities_included',
        'heating_type',
        'cooling_type',
        'water_supply',
        'sewerage',
        'electricity',
        'gas_supply',
        'security_features',
        'nearby_facilities',
        'transport_access',
        'status',
        'published_date',
        'expiry_date',
        'viewings_count',
        'inquiries_count',
        'offers_count',
        'accepted_offer_id',
        'contract_signed_date',
        'closing_date',
        'commission_percent',
        'commission_amount',
        'commission_paid',
        'commission_status',
        'photos',
        'virtual_tour_url',
        'floor_plan_url',
        'documents',
        'legal_notes',
        'cadastral_number',
        'ownership_type',
        'mortgage_available',
        'mortgage_terms',
        'property_condition',
        'requires_renovation',
        'renovation_estimate',
        'highlighted',
        'featured_until',
        'source',
        'external_listing_id',
        'external_platform',
        'sync_status',
        'last_synced_at',
        'notes',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'published_date' => 'datetime',
        'expiry_date' => 'datetime',
        'contract_signed_date' => 'datetime',
        'closing_date' => 'datetime',
        'featured_until' => 'datetime',
        'last_synced_at' => 'datetime',
        'price' => 'decimal:2',
        'price_per_sqm' => 'decimal:2',
        'area_total' => 'decimal:2',
        'area_living' => 'decimal:2',
        'area_kitchen' => 'decimal:2',
        'area_land' => 'decimal:2',
        'rooms_count' => 'integer',
        'bedrooms_count' => 'integer',
        'bathrooms_count' => 'integer',
        'floor' => 'integer',
        'total_floors' => 'integer',
        'building_year' => 'integer',
        'renovation_year' => 'integer',
        'parking_spaces' => 'integer',
        'balcony' => 'boolean',
        'terrace' => 'boolean',
        'furnished' => 'boolean',
        'viewings_count' => 'integer',
        'inquiries_count' => 'integer',
        'offers_count' => 'integer',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_paid' => 'boolean',
        'amenities' => 'json',
        'utilities_included' => 'json',
        'security_features' => 'json',
        'nearby_facilities' => 'json',
        'transport_access' => 'json',
        'photos' => 'json',
        'documents' => 'json',
        'mortgage_available' => 'boolean',
        'mortgage_terms' => 'json',
        'requires_renovation' => 'boolean',
        'renovation_estimate' => 'decimal:2',
        'highlighted' => 'boolean',
        'metadata' => 'json',
    ];

    protected $table = 'crm_real_estate_listings';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('property_type', $type);
    }

    public function scopeByListingType($query, string $type)
    {
        return $query->where('listing_type', $type);
    }

    public function scopeByCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    public function scopeByAreaRange($query, $min, $max)
    {
        return $query->whereBetween('area_total', [$min, $max]);
    }

    public function scopeSale($query)
    {
        return $query->where('listing_type', 'sale');
    }

    public function scopeRent($query)
    {
        return $query->where('listing_type', 'rent');
    }

    public function scopeApartment($query)
    {
        return $query->where('property_type', 'apartment');
    }

    public function scopeHouse($query)
    {
        return $query->where('property_type', 'house');
    }

    public function scopeCommercial($query)
    {
        return $query->where('property_type', 'commercial');
    }

    public function scopeLand($query)
    {
        return $query->where('property_type', 'land');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }

    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    public function scopeRented($query)
    {
        return $query->where('status', 'rented');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('status', 'withdrawn');
    }

    public function scopeFeatured($query)
    {
        return $query->where('highlighted', true)
            ->where(function ($q) {
                $q->whereNull('featured_until')
                  ->orWhere('featured_until', '>', now());
            });
    }

    public function scopeWithPhotos($query)
    {
        return $query->whereNotNull('photos')
            ->where('photos', '!=', '[]');
    }

    // ========================
    // METHODS
    // ========================

    public function publish(): bool
    {
        $this->status = 'active';
        $this->published_date = now();
        return $this->save();
    }

    public function withdraw(string $reason): bool
    {
        $this->status = 'withdrawn';
        $this->notes = $reason;
        return $this->save();
    }

    public function markSold(): bool
    {
        $this->status = 'sold';
        $this->closing_date = now();
        return $this->save();
    }

    public function markRented(): bool
    {
        $this->status = 'rented';
        $this->closing_date = now();
        return $this->save();
    }

    public function markPending(): bool
    {
        $this->status = 'pending';
        return $this->save();
    }

    public function feature(int $days = 7): bool
    {
        $this->highlighted = true;
        $this->featured_until = now()->addDays($days);
        return $this->save();
    }

    public function unfeature(): bool
    {
        $this->highlighted = false;
        $this->featured_until = null;
        return $this->save();
    }

    public function incrementViewings(): bool
    {
        $this->increment('viewings_count');
        return true;
    }

    public function incrementInquiries(): bool
    {
        $this->increment('inquiries_count');
        return true;
    }

    public function addOffer(): bool
    {
        $this->increment('offers_count');
        return true;
    }

    public function acceptOffer(int $offerId): bool
    {
        $this->accepted_offer_id = $offerId;
        $this->status = 'pending';
        return $this->save();
    }

    public function signContract(): bool
    {
        $this->contract_signed_date = now();
        return $this->save();
    }

    public function calculateCommission(): float
    {
        return $this->price * ($this->commission_percent / 100);
    }

    public function markCommissionPaid(): bool
    {
        $this->commission_paid = true;
        $this->commission_status = 'paid';
        return $this->save();
    }

    public function isSale(): bool
    {
        return $this->listing_type === 'sale';
    }

    public function isRent(): bool
    {
        return $this->listing_type === 'rent';
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->expiry_date === null || $this->expiry_date->isFuture());
    }

    public function isFeatured(): bool
    {
        return $this->highlighted && 
               ($this->featured_until === null || $this->featured_until->isFuture());
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет price_per_sqm
            if ($model->price_per_sqm === 0 && $model->price > 0 && $model->area_total > 0) {
                $model->price_per_sqm = $model->price / $model->area_total;
            }
            
            // Автоматический расчет commission_amount
            if ($model->commission_amount === 0 && $model->price > 0 && $model->commission_percent > 0) {
                $model->commission_amount = $model->calculateCommission();
            }
        });
    }
}
