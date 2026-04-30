<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Loyalty\Domain\Entities\LoyaltyRule;
use Modules\Loyalty\Domain\Enums\LoyaltyRuleType;

final class LoyaltyRuleModel extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_rules';

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'uuid',
        'name',
        'description',
        'type',
        'conditions',
        'calculation_type',
        'points_value',
        'point_multiplier',
        'max_uses_per_guest',
        'max_uses_total',
        'current_uses',
        'is_active',
        'starts_at',
        'ends_at',
        'target_tiers',
        'target_segments',
        'priority',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'calculation_type' => 'string',
        'points_value' => 'decimal:2',
        'point_multiplier' => 'decimal:4',
        'max_uses_per_guest' => 'integer',
        'max_uses_total' => 'integer',
        'current_uses' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'target_tiers' => 'json',
        'target_segments' => 'json',
        'priority' => 'integer',
        'metadata' => 'json',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgramModel::class, 'loyalty_program_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransactionModel::class, 'loyalty_rule_id');
    }

    public function toDomain(): LoyaltyRule
    {
        return new LoyaltyRule(
            id: (string) $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            loyaltyProgramId: (string) $this->loyalty_program_id,
            name: $this->name,
            description: $this->description,
            type: LoyaltyRuleType::from($this->type),
            conditions: $this->conditions,
            calculationType: $this->calculation_type,
            pointsValue: $this->points_value !== null ? (float) $this->points_value : null,
            pointMultiplier: (float) $this->point_multiplier,
            maxUsesPerGuest: $this->max_uses_per_guest,
            maxUsesTotal: $this->max_uses_total,
            currentUses: $this->current_uses,
            isActive: $this->is_active,
            startsAt: $this->starts_at ? \DateTimeImmutable::createFromMutable($this->starts_at) : null,
            endsAt: $this->ends_at ? \DateTimeImmutable::createFromMutable($this->ends_at) : null,
            targetTiers: $this->target_tiers,
            targetSegments: $this->target_segments,
            priority: $this->priority,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: \DateTimeImmutable::createFromMutable($this->created_at),
            updatedAt: $this->updated_at ? \DateTimeImmutable::createFromMutable($this->updated_at) : null,
            deletedAt: $this->deleted_at ? \DateTimeImmutable::createFromMutable($this->deleted_at) : null
        );
    }

    public static function fromDomain(LoyaltyRule $rule): self
    {
        return new self([
            'id' => $rule->getId() !== '0' ? $rule->getId() : null,
            'uuid' => $rule->getUuid(),
            'tenant_id' => $rule->getTenantId(),
            'loyalty_program_id' => $rule->getLoyaltyProgramId(),
            'name' => $rule->getName(),
            'description' => $rule->getDescription(),
            'type' => $rule->getType()->value,
            'conditions' => $rule->getConditions(),
            'calculation_type' => $rule->getCalculationType(),
            'points_value' => $rule->getPointsValue(),
            'point_multiplier' => $rule->getPointMultiplier(),
            'max_uses_per_guest' => $rule->getMaxUsesPerGuest(),
            'max_uses_total' => $rule->getMaxUsesTotal(),
            'current_uses' => $rule->getCurrentUses(),
            'is_active' => $rule->isActive(),
            'starts_at' => $rule->getStartsAt() ? \DateTime::createFromImmutable($rule->getStartsAt()) : null,
            'ends_at' => $rule->getEndsAt() ? \DateTime::createFromImmutable($rule->getEndsAt()) : null,
            'target_tiers' => $rule->getTargetTiers(),
            'target_segments' => $rule->getTargetSegments(),
            'priority' => $rule->getPriority(),
            'metadata' => $rule->getMetadata(),
            'correlation_id' => $rule->getCorrelationId(),
        ]);
    }
}
