<?php

declare(strict_types=1);

namespace Modules\Loyalty\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Loyalty\Domain\Entities\GuestLoyaltyProfile;
use Modules\Loyalty\Domain\ValueObjects\CurrencyAmount;
use Modules\Loyalty\Domain\ValueObjects\Points;

final class GuestLoyaltyProfileModel extends Model
{
    use SoftDeletes;

    protected $table = 'guest_loyalty_profiles';

    protected $fillable = [
        'tenant_id',
        'loyalty_program_id',
        'guest_id',
        'user_id',
        'uuid',
        'current_tier_id',
        'tier_updated_at',
        'available_points',
        'earned_points',
        'redeemed_points',
        'total_spend',
        'total_visits',
        'is_active',
        'enrolled_at',
        'last_activity_at',
        'birthday',
        'birthday_bonus_received',
        'birthday_bonus_year',
        'referred_by',
        'preferences',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'available_points' => 'decimal:2',
        'earned_points' => 'decimal:2',
        'redeemed_points' => 'decimal:2',
        'total_spend' => 'decimal:2',
        'total_visits' => 'integer',
        'is_active' => 'boolean',
        'enrolled_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'birthday' => 'date',
        'birthday_bonus_received' => 'boolean',
        'birthday_bonus_year' => 'integer',
        'preferences' => 'json',
        'metadata' => 'json',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgramModel::class, 'loyalty_program_id');
    }

    public function currentTier(): BelongsTo
    {
        return $this->belongsTo(LoyaltyTierModel::class, 'current_tier_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransactionModel::class, 'guest_loyalty_profile_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(GuestLoyaltyProfileModel::class, 'referred_by');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(GuestLoyaltyProfileModel::class, 'referred_by');
    }

    public function toDomain(): GuestLoyaltyProfile
    {
        return new GuestLoyaltyProfile(
            id: (string) $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenant_id,
            loyaltyProgramId: (string) $this->loyalty_program_id,
            guestId: $this->guest_id,
            userId: $this->user_id,
            currentTierId: $this->current_tier_id ? (string) $this->current_tier_id : null,
            tierUpdatedAt: $this->tier_updated_at ? \DateTimeImmutable::createFromMutable($this->tier_updated_at) : null,
            availablePoints: Points::fromFloat((float) $this->available_points),
            earnedPoints: Points::fromFloat((float) $this->earned_points),
            redeemedPoints: Points::fromFloat((float) $this->redeemed_points),
            totalSpend: CurrencyAmount::fromFloat((float) $this->total_spend),
            totalVisits: $this->total_visits,
            isActive: $this->is_active,
            enrolledAt: $this->enrolled_at ? \DateTimeImmutable::createFromMutable($this->enrolled_at) : null,
            lastActivityAt: $this->last_activity_at ? \DateTimeImmutable::createFromMutable($this->last_activity_at) : null,
            birthday: $this->birthday ? \DateTimeImmutable::createFromFormat('Y-m-d', $this->birthday->format('Y-m-d')) : null,
            birthdayBonusReceived: $this->birthday_bonus_received,
            birthdayBonusYear: $this->birthday_bonus_year,
            referredBy: $this->referred_by,
            preferences: $this->preferences,
            metadata: $this->metadata,
            correlationId: $this->correlation_id,
            createdAt: \DateTimeImmutable::createFromMutable($this->created_at),
            updatedAt: $this->updated_at ? \DateTimeImmutable::createFromMutable($this->updated_at) : null,
            deletedAt: $this->deleted_at ? \DateTimeImmutable::createFromMutable($this->deleted_at) : null
        );
    }

    public static function fromDomain(GuestLoyaltyProfile $profile): self
    {
        return new self([
            'id' => $profile->getId() !== '0' ? $profile->getId() : null,
            'uuid' => $profile->getUuid(),
            'tenant_id' => $profile->getTenantId(),
            'loyalty_program_id' => $profile->getLoyaltyProgramId(),
            'guest_id' => $profile->getGuestId(),
            'user_id' => $profile->getUserId(),
            'current_tier_id' => $profile->getCurrentTierId() !== '0' ? $profile->getCurrentTierId() : null,
            'tier_updated_at' => $profile->getTierUpdatedAt() ? \DateTime::createFromImmutable($profile->getTierUpdatedAt()) : null,
            'available_points' => $profile->getAvailablePoints()->getValue(),
            'earned_points' => $profile->getEarnedPoints()->getValue(),
            'redeemed_points' => $profile->getRedeemedPoints()->getValue(),
            'total_spend' => $profile->getTotalSpend()->getValue(),
            'total_visits' => $profile->getTotalVisits(),
            'is_active' => $profile->isActive(),
            'enrolled_at' => $profile->getEnrolledAt() ? \DateTime::createFromImmutable($profile->getEnrolledAt()) : null,
            'last_activity_at' => $profile->getLastActivityAt() ? \DateTime::createFromImmutable($profile->getLastActivityAt()) : null,
            'birthday' => $profile->getBirthday() ? \DateTime::createFromImmutable($profile->getBirthday()) : null,
            'birthday_bonus_received' => $profile->isBirthdayBonusReceived(),
            'birthday_bonus_year' => $profile->getBirthdayBonusYear(),
            'referred_by' => $profile->getReferredBy(),
            'preferences' => $profile->getPreferences(),
            'metadata' => $profile->getMetadata(),
            'correlation_id' => $profile->getCorrelationId(),
        ]);
    }
}
