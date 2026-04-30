<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Loyalty\Domain\Entities\LoyaltyProgram;
use Modules\Loyalty\Domain\Enums\VerticalType;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;

final class LoyaltyProgramModel extends Model
{
    use SoftDeletes;

    protected $table = 'loyalty_programs';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'name',
        'description',
        'vertical_type',
        'is_active',
        'base_points_per_currency',
        'points_to_currency_rate',
        'signup_bonus_points',
        'birthday_bonus_points',
        'referral_bonus_points',
        'tier_system_enabled',
        'tier_config',
        'points_expire',
        'points_expiration_days',
        'starts_at',
        'ends_at',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'base_points_per_currency' => 'decimal:4',
        'points_to_currency_rate' => 'decimal:4',
        'signup_bonus_points' => 'decimal:2',
        'birthday_bonus_points' => 'decimal:2',
        'referral_bonus_points' => 'decimal:2',
        'tier_system_enabled' => 'boolean',
        'tier_config' => 'json',
        'points_expire' => 'boolean',
        'points_expiration_days' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(LoyaltyTierModel::class, 'loyalty_program_id');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(GuestLoyaltyProfileModel::class, 'loyalty_program_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(LoyaltyRuleModel::class, 'loyalty_program_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(LoyaltyRewardModel::class, 'loyalty_program_id');
    }

    public function toDomain(): LoyaltyProgram
    {
        return new LoyaltyProgram(
            id: (string) $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            businessGroupId: $this->business_group_id,
            name: $this->name,
            description: $this->description,
            verticalType: VerticalType::from($this->vertical_type),
            isActive: $this->is_active,
            basePointsPerCurrency: (float) $this->base_points_per_currency,
            pointsToCurrencyRate: (float) $this->points_to_currency_rate,
            signupBonusPoints: (float) $this->signup_bonus_points,
            birthdayBonusPoints: (float) $this->birthday_bonus_points,
            referralBonusPoints: (float) $this->referral_bonus_points,
            tierSystemEnabled: $this->tier_system_enabled,
            tierConfig: $this->tier_config,
            pointsExpire: $this->points_expire,
            pointsExpirationDays: $this->points_expiration_days,
            startsAt: $this->starts_at ? \DateTimeImmutable::createFromMutable($this->starts_at) : null,
            endsAt: $this->ends_at ? \DateTimeImmutable::createFromMutable($this->ends_at) : null,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: \DateTimeImmutable::createFromMutable($this->created_at),
            updatedAt: $this->updated_at ? \DateTimeImmutable::createFromMutable($this->updated_at) : null,
            deletedAt: $this->deleted_at ? \DateTimeImmutable::createFromMutable($this->deleted_at) : null
        );
    }

    public static function fromDomain(LoyaltyProgram $program): self
    {
        return new self([
            'id' => $program->getId() !== '0' ? $program->getId() : null,
            'uuid' => $program->getUuid(),
            'tenant_id' => $program->getTenantId(),
            'business_group_id' => $program->getBusinessGroupId(),
            'name' => $program->getName(),
            'description' => $program->getDescription(),
            'vertical_type' => $program->getVerticalType()->value,
            'is_active' => $program->isActive(),
            'base_points_per_currency' => $program->getBasePointsPerCurrency(),
            'points_to_currency_rate' => $program->getPointsToCurrencyRate(),
            'signup_bonus_points' => $program->getSignupBonusPoints(),
            'birthday_bonus_points' => $program->getBirthdayBonusPoints(),
            'referral_bonus_points' => $program->getReferralBonusPoints(),
            'tier_system_enabled' => $program->isTierSystemEnabled(),
            'tier_config' => $program->getTierConfig(),
            'points_expire' => $program->doPointsExpire(),
            'points_expiration_days' => $program->getPointsExpirationDays(),
            'starts_at' => $program->getStartsAt() ? \DateTime::createFromImmutable($program->getStartsAt()) : null,
            'ends_at' => $program->getEndsAt() ? \DateTime::createFromImmutable($program->getEndsAt()) : null,
            'metadata' => $program->getMetadata(),
            'correlation_id' => $program->getCorrelationId(),
        ]);
    }
}
