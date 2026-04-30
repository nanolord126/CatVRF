<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Services;

use App\Domains\Bonuses\Models\BonusRule;
use App\Domains\Bonuses\Models\BonusWallet;
use App\Domains\Bonuses\DTOs\AwardBonusDto;
use App\Domains\Bonuses\DTOs\SpendBonusDto;
use App\Domains\Bonuses\DTOs\WithdrawBonusDto;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;

/**
 * BonusEligibilityService - Domain service for bonus eligibility checks
 * 
 * Validates if users are eligible for bonus operations.
 * Includes rate limiting, cooldown checks, and balance validations.
 */
final readonly class BonusEligibilityService
{
    private const RATE_LIMIT_WINDOW_SECONDS = 3600; // 1 hour
    private const COOLDOWN_CACHE_PREFIX = 'bonus_cooldown:';
    private const RATE_LIMIT_CACHE_PREFIX = 'bonus_rate_limit:';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Check if user is eligible to receive bonus
     */
    public function isEligibleForAward(AwardBonusDto $dto, BonusWallet $wallet): bool
    {
        // Check if bonuses are enabled
        if (!config('bonuses.enabled', true)) {
            $this->logger->info('Bonuses are disabled', ['user_id' => $dto->userId]);
            return false;
        }

        // Check max balance
        if (!$wallet->isBelowMaxBalance($dto->amount)) {
            $this->logger->info('User exceeds max bonus balance', [
                'user_id' => $dto->userId,
                'current_balance' => $wallet->getTotalBalance(),
                'amount' => $dto->amount,
                'max_balance' => $wallet->max_balance,
            ]);
            return false;
        }

        // Check rule cooldown
        if ($dto->bonusRuleId && !$this->checkCooldown((string) $dto->userId, (string) $dto->bonusRuleId)) {
            $this->logger->info('User is in cooldown period', [
                'user_id' => $dto->userId,
                'rule_id' => $dto->bonusRuleId,
            ]);
            return false;
        }

        // Check rate limit
        if (!$this->checkRateLimit((string) $dto->userId, 'award')) {
            $this->logger->info('User exceeded rate limit for bonus awards', [
                'user_id' => $dto->userId,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if user is eligible to spend bonus
     */
    public function isEligibleForSpend(SpendBonusDto $dto, BonusWallet $wallet): bool
    {
        // Check if user has sufficient balance
        if (!$wallet->hasSufficientBalance($dto->amount)) {
            $this->logger->info('User has insufficient bonus balance', [
                'user_id' => $dto->userId,
                'required' => $dto->amount,
                'available' => $wallet->available_balance,
            ]);
            return false;
        }

        // Check B2C spend limit
        if ($wallet->isB2C()) {
            $maxSpendPercentage = config('bonuses.b2c.max_spend_percentage', 30);
            // This would need order context to calculate percentage
            // For now, we'll just check if amount is positive
        }

        // Check rate limit
        if (!$this->checkRateLimit((string) $dto->userId, 'spend')) {
            $this->logger->info('User exceeded rate limit for bonus spending', [
                'user_id' => $dto->userId,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if user is eligible to withdraw bonus (B2B only)
     */
    public function isEligibleForWithdraw(WithdrawBonusDto $dto, BonusWallet $wallet): bool
    {
        // Check if user can withdraw
        if (!$wallet->can_withdraw) {
            $this->logger->info('User is not allowed to withdraw bonuses', [
                'user_id' => $dto->userId,
                'user_type' => $wallet->user_type,
                'tier' => $wallet->tier,
            ]);
            return false;
        }

        // Check if user is B2B Gold/Platinum
        if (!$wallet->isB2B() || !$wallet->isGoldOrPlatinum()) {
            $this->logger->info('User must be B2B Gold/Platinum to withdraw', [
                'user_id' => $dto->userId,
                'user_type' => $wallet->user_type,
                'tier' => $wallet->tier,
            ]);
            return false;
        }

        // Check if user has sufficient balance
        if (!$wallet->hasSufficientBalance($dto->amount)) {
            $this->logger->info('User has insufficient bonus balance for withdrawal', [
                'user_id' => $dto->userId,
                'required' => $dto->amount,
                'available' => $wallet->available_balance,
            ]);
            return false;
        }

        // Check withdrawal limit
        if (!$wallet->canWithdrawAmount($dto->amount)) {
            $this->logger->info('Withdrawal amount exceeds limit', [
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'max_withdrawal' => $wallet->getAvailableWithdrawalAmount(),
            ]);
            return false;
        }

        // Check minimum withdrawal amount
        $minPayoutAmount = config('bonuses.min_payout_amount', 100);
        if ($dto->amount < $minPayoutAmount) {
            $this->logger->info('Withdrawal amount below minimum', [
                'user_id' => $dto->userId,
                'amount' => $dto->amount,
                'minimum' => $minPayoutAmount,
            ]);
            return false;
        }

        // Check rate limit
        if (!$this->checkRateLimit($dto->userId, 'withdraw')) {
            $this->logger->info('User exceeded rate limit for bonus withdrawals', [
                'user_id' => $dto->userId,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Check if user is in cooldown period for a rule
     */
    private function checkCooldown(string $userId, string $ruleCode): bool
    {
        $rule = BonusRule::where('code', $ruleCode)->first();

        if (!$rule || $rule->cooldown_hours === 0) {
            return true;
        }

        $cacheKey = self::COOLDOWN_CACHE_PREFIX . $userId . ':' . $ruleCode;
        $lastAwardTime = $this->cache->get($cacheKey);

        if (!$lastAwardTime) {
            return true;
        }

        $cooldownEnd = $lastAwardTime + ($rule->cooldown_hours * 3600);
        
        return now()->timestamp >= $cooldownEnd;
    }

    /**
     * Set cooldown for user and rule
     */
    public function setCooldown(string $userId, string $ruleCode, int $cooldownHours): void
    {
        if ($cooldownHours === 0) {
            return;
        }

        $cacheKey = self::COOLDOWN_CACHE_PREFIX . $userId . ':' . $ruleCode;
        $ttl = $cooldownHours * 3600;

        $this->cache->put($cacheKey, now()->timestamp, $ttl);
    }

    /**
     * Check rate limit for user operation
     */
    private function checkRateLimit(string $userId, string $operation): bool
    {
        $config = config('bonuses.rules.' . $operation, []);
        $maxPerHour = $config['max_per_day'] ?? 10;

        if ($maxPerHour === 0) {
            return true;
        }

        $cacheKey = self::RATE_LIMIT_CACHE_PREFIX . $userId . ':' . $operation;
        $currentCount = $this->cache->get($cacheKey, 0);

        if ($currentCount >= $maxPerHour) {
            return false;
        }

        // Increment counter
        $this->cache->put($cacheKey, $currentCount + 1, self::RATE_LIMIT_WINDOW_SECONDS);

        return true;
    }

    /**
     * Check if user has reached max uses for a rule
     */
    public function hasReachedMaxUses(string $userId, BonusRule $rule): bool
    {
        if (!$rule->max_per_user) {
            return false;
        }

        $useCount = $rule->transactions()
            ->where('user_id', $userId)
            ->count();

        return $useCount >= $rule->max_per_user;
    }

    /**
     * Check if rule has reached daily limit
     */
    public function hasReachedDailyLimit(BonusRule $rule): bool
    {
        if (!$rule->max_per_day) {
            return false;
        }

        $todayCount = $rule->transactions()
            ->whereDate('created_at', today())
            ->count();

        return $todayCount >= $rule->max_per_day;
    }
}
