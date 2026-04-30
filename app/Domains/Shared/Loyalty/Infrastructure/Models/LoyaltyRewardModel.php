<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Loyalty\Domain\Entities\LoyaltyReward;
use Modules\Loyalty\Domain\Enums\LoyaltyRewardType;
use Modules\Loyalty\Domain\ValueObjects\Points;

final class LoyaltyRewardModel extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_rewards';

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'uuid',
        'name',
        'description',
        'type',
        'points_cost',
        'value_type',
        'value_amount',
        'menu_item_id',
        'item_code',
        'conditions',
        'stock_quantity',
        'redeemed_count',
        'target_tiers',
        'target_segments',
        'is_active',
        'starts_at',
        'ends_at',
        'max_redemptions_per_guest',
        'max_redemptions_total',
        'image_url',
        'sort_order',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'points_cost' => 'decimal:2',
        'value_type' => 'string',
        'value_amount' => 'decimal:2',
        'menu_item_id' => 'integer',
        'stock_quantity' => 'integer',
        'redeemed_count' => 'integer',
        'target_tiers' => 'json',
        'target_segments' => 'json',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'max_redemptions_per_guest' => 'integer',
        'max_redemptions_total' => 'integer',
        'sort_order' => 'integer',
        'metadata' => 'json',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgramModel::class, 'loyalty_program_id');
    }

    public function toDomain(): LoyaltyReward
    {
        return new LoyaltyReward(
            id: (string) $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            loyaltyProgramId: (string) $this->loyalty_program_id,
            name: $this->name,
            description: $this->description,
            type: LoyaltyRewardType::from($this->type),
            pointsCost: Points::fromFloat((float) $this->points_cost),
            valueType: $this->value_type,
            valueAmount: $this->value_amount !== null ? (float) $this->value_amount : null,
            menuItemId: $this->menu_item_id,
            itemCode: $this->item_code,
            conditions: $this->conditions,
            stockQuantity: $this->stock_quantity,
            redeemedCount: $this->redeemed_count,
            targetTiers: $this->target_tiers,
            targetSegments: $this->target_segments,
            isActive: $this->is_active,
            startsAt: $this->starts_at ? \DateTimeImmutable::createFromMutable($this->starts_at) : null,
            endsAt: $this->ends_at ? \DateTimeImmutable::createFromMutable($this->ends_at) : null,
            maxRedemptionsPerGuest: $this->max_redemptions_per_guest,
            maxRedemptionsTotal: $this->max_redemptions_total,
            imageUrl: $this->image_url,
            sortOrder: $this->sort_order,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: \DateTimeImmutable::createFromMutable($this->created_at),
            updatedAt: $this->updated_at ? \DateTimeImmutable::createFromMutable($this->updated_at) : null,
            deletedAt: $this->deleted_at ? \DateTimeImmutable::createFromMutable($this->deleted_at) : null
        );
    }

    public static function fromDomain(LoyaltyReward $reward): self
    {
        return new self([
            'id' => $reward->getId() !== '0' ? $reward->getId() : null,
            'uuid' => $reward->getUuid(),
            'tenant_id' => $reward->getTenantId(),
            'loyalty_program_id' => $reward->getLoyaltyProgramId(),
            'name' => $reward->getName(),
            'description' => $reward->getDescription(),
            'type' => $reward->getType()->value,
            'points_cost' => $reward->getPointsCost()->getValue(),
            'value_type' => $reward->getValueType(),
            'value_amount' => $reward->getValueAmount(),
            'menu_item_id' => $reward->getMenuItemId(),
            'item_code' => $reward->getItemCode(),
            'conditions' => $reward->getConditions(),
            'stock_quantity' => $reward->getStockQuantity(),
            'redeemed_count' => $reward->getRedeemedCount(),
            'target_tiers' => $reward->getTargetTiers(),
            'target_segments' => $reward->getTargetSegments(),
            'is_active' => $reward->isActive(),
            'starts_at' => $reward->getStartsAt() ? \DateTime::createFromImmutable($reward->getStartsAt()) : null,
            'ends_at' => $reward->getEndsAt() ? \DateTime::createFromImmutable($reward->getEndsAt()) : null,
            'max_redemptions_per_guest' => $reward->getMaxRedemptionsPerGuest(),
            'max_redemptions_total' => $reward->getMaxRedemptionsTotal(),
            'image_url' => $reward->getImageUrl(),
            'sort_order' => $reward->getSortOrder(),
            'metadata' => $reward->getMetadata(),
            'correlation_id' => $reward->getCorrelationId(),
        ]);
    }
}
