<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Loyalty\Domain\Entities\LoyaltyTier;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use Modules\Loyalty\Domain\ValueObjects\Points;

final class LoyaltyTierModel extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_tiers';

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'uuid',
        'name',
        'slug',
        'description',
        'min_points',
        'min_spend',
        'min_visits',
        'point_multiplier',
        'discount_percentage',
        'privileges',
        'color',
        'icon',
        'sort_order',
        'is_active',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'min_points' => 'decimal:2',
        'min_spend' => 'decimal:2',
        'min_visits' => 'integer',
        'point_multiplier' => 'decimal:4',
        'discount_percentage' => 'decimal:4',
        'privileges' => 'json',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'json',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgramModel::class, 'loyalty_program_id');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(GuestLoyaltyProfileModel::class, 'current_tier_id');
    }

    public function toDomain(): LoyaltyTier
    {
        return new LoyaltyTier(
            id: (string) $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            loyaltyProgramId: (string) $this->loyalty_program_id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            minPoints: Points::fromFloat((float) $this->min_points),
            minSpend: $this->min_spend !== null ? CurrencyAmount::fromFloat((float) $this->min_spend) : null,
            minVisits: $this->min_visits,
            pointMultiplier: (float) $this->point_multiplier,
            discountPercentage: (float) $this->discount_percentage,
            privileges: $this->privileges,
            color: $this->color,
            icon: $this->icon,
            sortOrder: $this->sort_order,
            isActive: $this->is_active,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: \DateTimeImmutable::createFromMutable($this->created_at),
            updatedAt: $this->updated_at ? \DateTimeImmutable::createFromMutable($this->updated_at) : null,
            deletedAt: $this->deleted_at ? \DateTimeImmutable::createFromMutable($this->deleted_at) : null
        );
    }

    public static function fromDomain(LoyaltyTier $tier): self
    {
        return new self([
            'id' => $tier->getId() !== '0' ? $tier->getId() : null,
            'uuid' => $tier->getUuid(),
            'tenant_id' => $tier->getTenantId(),
            'loyalty_program_id' => $tier->getLoyaltyProgramId(),
            'name' => $tier->getName(),
            'slug' => $tier->getSlug(),
            'description' => $tier->getDescription(),
            'min_points' => $tier->getMinPoints()->getValue(),
            'min_spend' => $tier->getMinSpend()?->getValue(),
            'min_visits' => $tier->getMinVisits(),
            'point_multiplier' => $tier->getPointMultiplier(),
            'discount_percentage' => $tier->getDiscountPercentage(),
            'privileges' => $tier->getPrivileges(),
            'color' => $tier->getColor(),
            'icon' => $tier->getIcon(),
            'sort_order' => $tier->getSortOrder(),
            'is_active' => $tier->isActive(),
            'metadata' => $tier->getMetadata(),
            'correlation_id' => $tier->getCorrelationId(),
        ]);
    }
}
