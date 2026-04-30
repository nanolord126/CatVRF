<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Facades;

use Illuminate\Support\Facades\Facade;
use App\Domains\Bonuses\DTOs\AwardBonusDto;
use App\Domains\Bonuses\DTOs\SpendBonusDto;
use App\Domains\Bonuses\DTOs\WithdrawBonusDto;
use App\Domains\Bonuses\DTOs\BonusBalanceDto;
use App\Domains\Bonuses\Models\BonusTransaction;

/**
 * BonusFacade - Single entry point for Bonus domain
 * 
 * Provides static access to Bonus domain operations.
 * Acts as the main API surface for the entire application.
 * 
 * Usage:
 * Bonus::award($userId, $tenantId, $amount, $type, $reason);
 * Bonus::spend($userId, $tenantId, $amount, $reason);
 * Bonus::withdraw($userId, $amount, $method);
 * Bonus::getBalance($userId);
 * Bonus::getRulesForVertical('Beauty');
 */
final class Bonus extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bonus.service';
    }

    /**
     * Award bonus to user
     * 
     * @param int $userId User ID
     * @param int $tenantId Tenant ID
     * @param int $amount Amount in cents
     * @param string $type Bonus type (loyalty, referral, first_order, etc.)
     * @param string|null $reason Optional reason
     * @param string|null $sourceType Source type (order, ai_constructor, etc.)
     * @param int|null $sourceId Source entity ID
     * @param string|null $verticalCode Vertical code
     * @return BonusTransaction
     */
    public static function award(
        int $userId,
        int $tenantId,
        int $amount,
        string $type = 'loyalty',
        ?string $reason = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $verticalCode = null,
        ?int $bonusRuleId = null,
    ): BonusTransaction {
        $dto = new AwardBonusDto(
            userId: $userId,
            tenantId: $tenantId,
            amount: $amount,
            type: \App\Domains\Bonuses\Enums\BonusType::from($type),
            reason: $reason,
            sourceType: $sourceType,
            sourceId: $sourceId,
            bonusRuleId: $bonusRuleId,
            bonusCampaignId: null,
            verticalCode: $verticalCode,
            userTier: \App\Domains\Bonuses\Enums\BonusTier::BRONZE,
            correlationId: \Illuminate\Support\Str::uuid()->toString(),
            idempotencyKey: null,
            metadata: [],
        );

        return static::getFacadeRoot()->award($dto);
    }

    /**
     * Spend bonus
     * 
     * @param int $userId User ID
     * @param int $tenantId Tenant ID
     * @param int $amount Amount in cents
     * @param string $reason Reason for spending
     * @param string|null $sourceType Source type
     * @param int|null $sourceId Source entity ID
     * @return void
     */
    public static function spend(
        int $userId,
        int $tenantId,
        int $amount,
        string $reason = 'checkout',
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): void {
        $dto = new SpendBonusDto(
            userId: $userId,
            tenantId: $tenantId,
            amount: $amount,
            reason: $reason,
            sourceType: $sourceType,
            sourceId: $sourceId,
            correlationId: \Illuminate\Support\Str::uuid()->toString(),
        );

        static::getFacadeRoot()->spend($dto);
    }

    /**
     * Withdraw bonus to real money (B2B only)
     * 
     * @param int $userId User ID
     * @param int $tenantId Tenant ID
     * @param int $amount Amount in cents
     * @param string $withdrawalMethod Withdrawal method (bank_transfer, etc.)
     * @param string|null $bankAccount Bank account number
     * @return BonusTransaction
     */
    public static function withdraw(
        int $userId,
        int $tenantId,
        int $amount,
        string $withdrawalMethod = 'bank_transfer',
        ?string $bankAccount = null,
    ): BonusTransaction {
        $dto = new WithdrawBonusDto(
            userId: $userId,
            tenantId: $tenantId,
            amount: $amount,
            userTier: \App\Domains\Bonuses\Enums\BonusTier::GOLD,
            bankAccount: $bankAccount,
            withdrawalMethod: $withdrawalMethod,
            reason: null,
            correlationId: \Illuminate\Support\Str::uuid()->toString(),
            idempotencyKey: null,
            metadata: [],
        );

        return static::getFacadeRoot()->withdraw($dto);
    }

    /**
     * Get user bonus balance
     * 
     * @param string $userId User ID
     * @return BonusBalanceDto
     */
    public static function getBalance(string $userId): BonusBalanceDto
    {
        return static::getFacadeRoot()->getBalance($userId);
    }

    /**
     * Get available bonus balance as integer
     * 
     * @param string $userId User ID
     * @return int Available balance in cents
     */
    public static function getAvailableBalance(string $userId): int
    {
        return static::getBalance($userId)->availableBalance;
    }

    /**
     * Get bonus transaction history
     * 
     * @param string $userId User ID
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function getHistory(string $userId, int $perPage = 20)
    {
        return static::getFacadeRoot()->getHistory($userId, $perPage);
    }

    /**
     * Calculate bonus for order amount
     * 
     * @param float $orderAmount Order amount
     * @param string $ruleType Rule type (loyalty, referral, first_purchase, etc.)
     * @param string|null $verticalCode Vertical code
     * @param string|null $userType User type (b2b, b2c)
     * @return int Bonus amount in cents
     */
    public static function calculateBonus(
        float $orderAmount,
        string $ruleType,
        ?string $verticalCode = null,
        ?string $userType = null,
    ): int {
        return static::getFacadeRoot()->calculateBonusForOrder(
            $orderAmount,
            $ruleType,
            $verticalCode,
            $userType,
        );
    }

    /**
     * Get available rules for vertical
     * 
     * @param string $ruleType Rule type
     * @param string|null $verticalCode Vertical code
     * @return \Illuminate\Support\Collection
     */
    public static function getRulesForVertical(
        string $ruleType,
        ?string $verticalCode = null,
    ): \Illuminate\Support\Collection {
        return static::getFacadeRoot()->getRulesForVertical($ruleType, $verticalCode);
    }

    /**
     * Unlock expired holds (scheduled task)
     * 
     * @return int Number of unlocked bonuses
     */
    public static function unlockExpiredHolds(): int
    {
        return static::getFacadeRoot()->unlockExpiredHolds();
    }

    /**
     * Expire old bonuses (scheduled task)
     *
     * @return int Number of expired bonuses
     */
    public static function expireOldBonuses(): int
    {
        return static::getFacadeRoot()->expireOldBonuses();
    }

    /**
     * Get user float yield summary
     *
     * @param int $userId User ID
     * @param int $tenantId Tenant ID
     * @return array Float yield summary
     */
    public static function getUserFloatSummary(int $userId, int $tenantId): array
    {
        return app(\App\Domains\Bonuses\Services\FloatYieldService::class)->getUserFloatSummary($userId, $tenantId);
    }

    /**
     * Get platform-wide float statistics
     *
     * @param int|null $tenantId Tenant ID (optional, for tenant-specific stats)
     * @param int $days Number of days to calculate statistics for
     * @return array Platform float statistics
     */
    public static function getPlatformStatistics(?int $tenantId = null, int $days = 30): array
    {
        return app(\App\Domains\Bonuses\Services\FloatYieldService::class)->getPlatformStatistics($tenantId, $days);
    }

    /**
     * Process daily float yield (scheduled task)
     *
     * @param string|null $date Date to process (defaults to yesterday)
     * @return array Processing results
     */
    public static function processDailyFloatYield(?string $date = null): array
    {
        return app(\App\Domains\Bonuses\Services\FloatYieldService::class)->processDailyYield($date);
    }

    /**
     * Get float yield rates from config
     *
     * @return array Yield rates configuration
     */
    public static function getFloatYieldRates(): array
    {
        return app(\App\Domains\Bonuses\Services\FloatYieldService::class)->getYieldRates();
    }
}
