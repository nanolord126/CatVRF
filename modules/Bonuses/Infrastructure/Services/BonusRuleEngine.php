<?php

declare(strict_types=1);

namespace Modules\Bonuses\Infrastructure\Services;

use DateTimeImmutable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Bonuses\Domain\Enums\BonusType;
use Modules\Bonuses\Domain\Interfaces\BonusRuleEngineInterface;
use Modules\Bonuses\Domain\Repositories\BonusRepositoryInterface;
use Modules\Bonuses\Domain\ValueObjects\BonusAmount;

/**
 * Class BonusRuleEngine
 *
 * Implementation of BonusRuleEngineInterface with business rule evaluation.
 * Enforces bonus limits, caps, and eligibility rules.
 * Integrates with config for rule definitions and repository for cap checking.
 */
final class BonusRuleEngine implements BonusRuleEngineInterface
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly BonusRepositoryInterface $bonusRepository
    ) {}

    public function canAward(string $ownerId, BonusType $type, BonusAmount $amount, array $context = []): bool
    {
        // Check type-specific max amount
        if ($amount->getAmount() > $type->getMaxAmount()) {
            return false;
        }

        // Check monthly cap
        if ($this->hasReachedCap($ownerId, $type, 'monthly', $amount->getAmount())) {
            return false;
        }

        // Check vertical restrictions
        if (!$this->isVerticalRestricted($type, $context['vertical'] ?? null)) {
            return false;
        }

        return true;
    }

    public function calculateAmount(string $ownerId, BonusType $type, BonusAmount $requestedAmount, array $context = []): BonusAmount
    {
        $amount = $requestedAmount->getAmount();

        // Apply config-based limits
        $rules = $this->config->get('bonuses.rules.' . $type->value, []);
        if (isset($rules['max_amount']) && $amount > $rules['max_amount']) {
            $amount = $rules['max_amount'];
        }

        // Apply monthly cap
        $monthlyCap = $this->getRemainingCapacity($ownerId, $type, 'monthly');
        if ($amount > $monthlyCap) {
            $amount = $monthlyCap;
        }

        return new BonusAmount($amount);
    }

    public function calculateExpiration(string $ownerId, BonusType $type, array $context = []): ?DateTimeImmutable
    {
        // Use type default if not specified in context
        if (isset($context['expires_at'])) {
            return new DateTimeImmutable($context['expires_at']);
        }

        $defaultDays = $type->getDefaultExpirationDays();
        if ($defaultDays === null) {
            return null;
        }

        return new DateTimeImmutable("+{$defaultDays} days");
    }

    public function hasReachedCap(string $ownerId, BonusType $type, string $period, int $additionalAmount = 0): bool
    {
        $cap = $this->getCapForPeriod($type, $period);
        if ($cap === null) {
            return false; // No cap defined
        }

        $used = $this->getUsedAmountForPeriod($ownerId, $type, $period);

        return ($used + $additionalAmount) > $cap;
    }

    public function getRemainingCapacity(string $ownerId, BonusType $type, string $period): int
    {
        $cap = $this->getCapForPeriod($type, $period);
        if ($cap === null) {
            return PHP_INT_MAX; // No cap
        }

        $used = $this->getUsedAmountForPeriod($ownerId, $type, $period);

        return max(0, $cap - $used);
    }

    public function validateAward(string $ownerId, BonusType $type, BonusAmount $amount, array $context = []): void
    {
        if (!$this->canAward($ownerId, $type, $amount, $context)) {
            $reasons = $this->getDenialReasons($ownerId, $type, $amount, $context);
            throw new \InvalidArgumentException(implode(', ', $reasons));
        }

        if ($type->requiresVerification($context)) {
            if (!$this->performVerification($ownerId, $type, $context)) {
                throw new \InvalidArgumentException('Bonus verification failed');
            }
        }
    }

    public function applyTierMultiplier(string $ownerId, BonusType $type, BonusAmount $baseAmount): BonusAmount
    {
        // TODO: Implement tier multiplier calculation
        // This would integrate with LoyaltyCalculator
        return $baseAmount;
    }

    public function requiresVerification(BonusType $type, array $context = []): bool
    {
        return $type->requiresVerification();
    }

    public function performVerification(string $ownerId, BonusType $type, array $context = []): bool
    {
        // Referral bonus verification
        if ($type === BonusType::REFERRAL) {
            $referralId = $context['referral_id'] ?? null;
            if (!$referralId) {
                return false;
            }

            // TODO: Check if referral completed onboarding
            // This would integrate with Referral vertical
            return true;
        }

        // Turnover bonus verification
        if ($type === BonusType::TURNOVER) {
            $turnoverThreshold = $this->config->get('bonuses.rules.turnover.turnover_threshold', 1000000);
            $currentTurnover = $context['current_turnover'] ?? 0;

            return $currentTurnover >= $turnoverThreshold;
        }

        return true;
    }

    public function getDenialReasons(string $ownerId, BonusType $type, BonusAmount $amount, array $context = []): array
    {
        $reasons = [];

        if ($amount->getAmount() > $type->getMaxAmount()) {
            $reasons[] = sprintf('Amount exceeds maximum of %d for type %s', $type->getMaxAmount(), $type->value);
        }

        if ($this->hasReachedCap($ownerId, $type, 'monthly', $amount->getAmount())) {
            $reasons[] = 'Monthly cap reached';
        }

        if (!$this->isVerticalRestricted($type, $context['vertical'] ?? null)) {
            $reasons[] = sprintf('Bonus type %s is not available for vertical %s', $type->value, $context['vertical'] ?? 'none');
        }

        if ($type->requiresVerification($context) && !$this->performVerification($ownerId, $type, $context)) {
            $reasons[] = 'Verification failed';
        }

        return $reasons;
    }

    public function isVerticalRestricted(BonusType $type, ?string $vertical): bool
    {
        if ($type->isUniversal()) {
            return true; // Not restricted
        }

        $allowedVerticals = $this->getAllowedVerticals($type);
        if (empty($allowedVerticals)) {
            return true; // No restrictions defined
        }

        if ($vertical === null) {
            return false; // Vertical required but not provided
        }

        return in_array($vertical, $allowedVerticals, true);
    }

    public function getAllowedVerticals(BonusType $type): array
    {
        $config = $this->config->get('bonuses.vertical_restrictions.' . $type->value, []);

        return $config['allowed_verticals'] ?? [];
    }

    /**
     * Gets the cap amount for a specific period and bonus type.
     */
    private function getCapForPeriod(BonusType $type, string $period): ?int
    {
        $config = $this->config->get('bonuses.caps.' . $type->value, []);

        return $config[$period] ?? null;
    }

    /**
     * Gets the used amount for a specific period.
     */
    private function getUsedAmountForPeriod(string $ownerId, BonusType $type, string $period): int
    {
        $bonuses = $this->bonusRepository->findByOwnerId($ownerId);

        $now = new DateTimeImmutable();
        $totalUsed = 0;

        foreach ($bonuses as $bonus) {
            if ($bonus->getType() !== $type) {
                continue;
            }

            $issuedAt = $bonus->getIssuedAt();

            $isWithinPeriod = match ($period) {
                'daily' => $issuedAt->format('Y-m-d') === $now->format('Y-m-d'),
                'weekly' => $issuedAt->format('Y-W') === $now->format('Y-W'),
                'monthly' => $issuedAt->format('Y-m') === $now->format('Y-m'),
                'yearly' => $issuedAt->format('Y') === $now->format('Y'),
                'lifetime' => true,
                default => false,
            };

            if ($isWithinPeriod) {
                $totalUsed += $bonus->getInitialAmount()->getAmount();
            }
        }

        return $totalUsed;
    }
}
