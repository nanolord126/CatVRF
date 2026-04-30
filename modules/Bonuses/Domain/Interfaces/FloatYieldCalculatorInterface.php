<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Interfaces;

use Modules\Bonuses\Domain\Entities\FloatYield;

/**
 * Interface: FloatYieldCalculatorInterface
 *
 * Defines the contract for calculating float yield from locked bonus balances.
 * Implementations handle the monetization logic for the CatFloat system.
 *
 * Float yield calculation responsibilities:
 * - Calculate daily platform yield on locked bonus balances
 * - Calculate daily user yield share
 * - Track partner fintech transactions for reconciliation
 * - Calculate partner commissions
 * - Aggregate yield statistics for reporting
 *
 * Yield mechanics:
 * - Platform uses locked bonus balance as liquidity for short-term loans
 * - Loans made through partner fintech (Tinkoff, Tochka, etc.)
 * - Platform earns 12-18% annual yield on average float
 * - User earns 0.08-0.25% daily yield on their locked balance
 * - Platform keeps the difference as revenue (60-75% margin)
 *
 * Yield calculation formula:
 * - Daily yield = (average_locked_balance × annual_rate) / 365
 * - Platform rate: 12-18% annually (0.033-0.049% daily)
 * - User rate: 0.08-0.25% daily (visible in app)
 * - Average balance = (start_balance + end_balance) / 2
 *
 * Yield sources:
 * - PARTNER_LENDING: Short-term loans through Tinkoff/Tochka
 * - TREASURY: Platform treasury management
 * - DEFI: DeFi protocols (experimental, high risk)
 * - CORPORATE_DEPOSITS: High-yield corporate deposits
 *
 * Partner integration:
 * - Partner transaction ID for reconciliation
 * - Partner name for reporting
 * - Settlement period (T+1, T+2, etc.)
 * - Commission split with partner (0.5-2% of platform revenue)
 *
 * Rate configuration:
 * - Platform rate varies by yield source and partner
 * - User rate varies by tier (standard: 0.08%, gold: 0.15%, platinum: 0.25%)
 * - Rates can be adjusted dynamically based on market conditions
 * - Rate changes logged with correlation ID
 *
 * Implementation requirements:
 * - Must be idempotent (same inputs = same outputs)
 * - Must handle edge cases (zero balance, negative rates, etc.)
 * - Must validate all inputs before calculation
 * - Must throw DomainException for invalid operations
 * - Must log all yield calculations with correlation ID
 *
 * Performance considerations:
 * - Calculations should be O(1) for single user
 * - Batch calculations should be optimized for bulk operations
 * - Use Redis for real-time balance tracking
 * - Cache yield rates where appropriate
 *
 * Compliance:
 * - All yield transactions logged with correlation ID
 * - Audit trail for financial reporting
 * - Partner reconciliation reports
 * - PII anonymized in external logs (152-ФЗ)
 * - Revenue reported according to Russian tax law
 *
 * @see Modules\Bonuses\Domain\Entities\FloatYield
 */
interface FloatYieldCalculatorInterface
{
    /**
     * Calculates daily yield for a user.
     *
     * @param string $userId User ID.
     * @param string $bonusWalletId Bonus wallet ID.
     * @param string $yieldDate Yield date (YYYY-MM-DD).
     * @param int $lockedBalanceStart Locked balance at start of day (kopecks).
     * @param int $lockedBalanceEnd Locked balance at end of day (kopecks).
     * @param float $platformYieldRate Platform annual yield rate (e.g., 15.0 for 15%).
     * @param float $userYieldRate User annual yield rate (e.g., 0.15 for 0.15%).
     * @param string|null $partnerName Partner name (if applicable).
     * @param string|null $partnerTransactionId Partner transaction ID.
     * @return FloatYield The yield transaction.
     */
    public function calculateDailyYield(
        string $userId,
        string $bonusWalletId,
        string $yieldDate,
        int $lockedBalanceStart,
        int $lockedBalanceEnd,
        float $platformYieldRate,
        float $userYieldRate,
        ?string $partnerName = null,
        ?string $partnerTransactionId = null
    ): FloatYield;

    /**
     * Calculates yield for multiple users in batch.
     *
     * @param array $userBalances Array of [userId => ['wallet_id' =>, 'start' =>, 'end' =>]].
     * @param string $yieldDate Yield date (YYYY-MM-DD).
     * @param float $platformYieldRate Platform annual yield rate.
     * @param float $userYieldRate User annual yield rate.
     * @return array Array of FloatYield entities.
     */
    public function calculateBatchYield(
        array $userBalances,
        string $yieldDate,
        float $platformYieldRate,
        float $userYieldRate
    ): array;

    /**
     * Gets the platform yield rate for a yield source.
     *
     * @param string $yieldSource Yield source (PARTNER_LENDING, TREASURY, etc.).
     * @param string|null $partnerName Partner name (for partner-specific rates).
     * @return float Annual yield rate (e.g., 15.0 for 15%).
     */
    public function getPlatformYieldRate(string $yieldSource, ?string $partnerName = null): float;

    /**
     * Gets the user yield rate for a user tier.
     *
     * @param string $userTier User tier (standard, gold, platinum).
     * @return float Annual yield rate (e.g., 0.15 for 0.15%).
     */
    public function getUserYieldRate(string $userTier): float;

    /**
     * Calculates partner commission on platform revenue.
     *
     * @param int $platformYield Platform yield in kopecks.
     * @param string $partnerName Partner name.
     * @return int Commission in kopecks.
     */
    public function calculatePartnerCommission(int $platformYield, string $partnerName): int;

    /**
     * Calculates net platform revenue after commission.
     *
     * @param int $platformYield Platform yield in kopecks.
     * @param int $partnerCommission Partner commission in kopecks.
     * @return int Net revenue in kopecks.
     */
    public function calculateNetRevenue(int $platformYield, int $partnerCommission): int;

    /**
     * Gets yield statistics for a date range.
     *
     * @param string $startDate Start date (YYYY-MM-DD).
     * @param string $endDate End date (YYYY-MM-DD).
     * @param string|null $userId User ID filter (null for all users).
     * @return array Statistics including total yield, average yield, etc.
     */
    public function getYieldStatistics(
        string $startDate,
        string $endDate,
        ?string $userId = null
    ): array;

    /**
     * Calculates annualized yield projection.
     *
     * @param int $averageLockedBalance Average locked balance in kopecks.
     * @param float $platformYieldRate Platform annual yield rate.
     * @param float $userYieldRate User annual yield rate.
     * @return array Projection with platform_yield and user_yield.
     */
    public function calculateAnnualizedProjection(
        int $averageLockedBalance,
        float $platformYieldRate,
        float $userYieldRate
    ): array;

    /**
     * Validates yield calculation parameters.
     *
     * @param int $lockedBalanceStart Locked balance at start.
     * @param int $lockedBalanceEnd Locked balance at end.
     * @param float $platformYieldRate Platform yield rate.
     * @param float $userYieldRate User yield rate.
     * @return bool True if valid.
     * @throws DomainException If invalid.
     */
    public function validateYieldParameters(
        int $lockedBalanceStart,
        int $lockedBalanceEnd,
        float $platformYieldRate,
        float $userYieldRate
    ): bool;

    /**
     * Gets the yield margin (platform share as percentage).
     *
     * @param int $platformYield Platform yield in kopecks.
     * @param int $userYield User yield in kopecks.
     * @return float Margin percentage (0-100).
     */
    public function calculateYieldMargin(int $platformYield, int $userYield): float;
}
