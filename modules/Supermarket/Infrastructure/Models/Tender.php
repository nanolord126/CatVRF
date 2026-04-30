<?php

declare(strict_types=1);

namespace Modules\Supermarket\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Tender extends Model
{
    use SoftDeletes;

    protected $table = 'supermarket_tenders';

    protected $fillable = [
        'uuid',
        'creator_id',
        'crm_lead_id',
        'type',
        'status',
        'title',
        'description',
        'published_at',
        'starts_at',
        'ends_at',
        'estimated_budget',
        'currency',
        'delivery_terms',
        'payment_terms',
        'requires_guarantee_letter',
        'guarantee_letter_path',
        'allowed_supplier_tiers',
        'restricted_regions',
        'awarded_supplier_id',
        'awarded_at',
        'final_amount',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'published_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'estimated_budget' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'delivery_terms' => 'array',
        'payment_terms' => 'array',
        'requires_guarantee_letter' => 'boolean',
        'allowed_supplier_tiers' => 'array',
        'restricted_regions' => 'array',
        'awarded_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const TYPE_PROCUREMENT = 'procurement';
    public const TYPE_SALE = 'sale';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_AWARDED = 'awarded';
    public const STATUS_CANCELLED = 'cancelled';

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(TenderLot::class, 'tender_id');
    }

    public function bids(): HasMany
    {
        return $this->hasMany(TenderBid::class, 'tender_id');
    }

    public function awardedSupplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'awarded_supplier_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->starts_at?->lte(now())
            && $this->ends_at?->gte(now());
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
