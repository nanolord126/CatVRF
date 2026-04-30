<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Events;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: FloatYieldClaimed
 *
 * Dispatched when a user claims their share of float yield from locked bonus batches.
 * This event represents the monetization aspect of CatFloat where platform earns revenue
 * from locked bonus liquidity and shares a portion with users.
 *
 * Key responsibilities:
 * - Credit user's bonus wallet with their share of yield
 * - Track platform revenue from float monetization
 * - Log audit trail for financial compliance (152-ФЗ)
 * - Update analytics for float revenue metrics
 * - Send notification to user about yield earned
 * - Track partner fintech transaction for reconciliation
 *
 * Float monetization mechanics:
 * - Platform uses locked bonus balance as liquidity for short-term loans
 * - Loans are made through partner fintech (Tinkoff, Tochka, etc.)
 * - Platform earns 12-18% annual yield on average float
 * - User earns 0.08-0.25% daily yield on their locked balance
 * - Platform keeps the difference as revenue (60-75% margin)
 *
 * Yield calculation:
 * - Daily yield = (locked_balance × daily_yield_rate) / 365
 * - Platform rate: 12-18% annually (0.033-0.049% daily)
 * - User rate: 0.08-0.25% daily (visible in app)
 * - Platform revenue = platform_yield - user_yield
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
 * - Commission split with partner
 *
 * User visibility:
 * - Yield is visible in app as "твой холд уже заработал +127 ₽"
 * - Daily push notification with yield amount
 * - Cumulative yield tracking
 * - Yield history with breakdown by batch
 *
 * Platform revenue:
 * - Tracked per batch for ROI calculation
 * - Aggregated for financial reporting
 * - Split by yield source for analytics
 * - Commission paid to partners tracked separately
 *
 * Event flow:
 * 1. CalculateFloatYieldJob runs daily → Calculates yield for all active batches
 * 2. FloatYieldClaimed event dispatched → Multiple listeners react
 * 3. WalletIntegrationService → Credits user's wallet with yield
 * 4. RevenueTrackingService → Logs platform revenue
 * 5. PartnerReconciliationService → Reconciles with partner
 * 6. NotificationService → Sends yield notification to user
 * 7. AnalyticsService → Tracks yield metrics and ROI
 *
 * Financial compliance:
 * - All yield transactions logged with correlation ID
 * - PII anonymized before external reporting
 * - Partner transactions reconciled monthly
 * - Revenue reported according to Russian tax law
 *
 * @see Modules\Bonuses\Application\Services\FloatYieldService
 * @see Modules\Bonuses\Domain\Entities\FloatYield
 */
final class FloatYieldClaimed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Unique identifier of the yield transaction.
     */
    public readonly string $yieldTransactionId;

    /**
     * User ID who is claiming the yield.
     */
    public readonly string $userId;

    /**
     * Tenant ID for multi-tenancy support.
     */
    public readonly ?string $tenantId;

    /**
     * Locked batch ID that generated this yield.
     */
    public readonly string $lockedBatchId;

    /**
     * Bonus wallet ID where yield is credited.
     */
    public readonly string $bonusWalletId;

    /**
     * Date for which yield was calculated.
     */
    public readonly string $yieldDate;

    /**
     * Locked balance at start of day (in kopecks).
     */
    public readonly int $lockedBalanceStart;

    /**
     * Locked balance at end of day (in kopecks).
     */
    public readonly int $lockedBalanceEnd;

    /**
     * Average locked balance for yield calculation (in kopecks).
     */
    public readonly int $averageLockedBalance;

    /**
     * Platform annual yield rate (e.g., 0.1800 for 18%).
     */
    public readonly float $platformYieldRate;

    /**
     * User daily yield rate (e.g., 0.0025 for 0.25%).
     */
    public readonly float $userYieldRate;

    /**
     * Platform yield amount (in kopecks).
     */
    public readonly int $platformYield;

    /**
     * User yield amount (in kopecks).
     */
    public readonly int $userYield;

    /**
     * Total yield generated (platform + user).
     */
    public readonly int $totalYield;

    /**
     * Source of yield (partner_lending, treasury, defi, etc.).
     */
    public readonly string $yieldSource;

    /**
     * Partner fintech name (Tinkoff, Tochka, etc.).
     */
    public readonly ?string $partnerName;

    /**
     * External transaction ID from partner.
     */
    public readonly ?string $partnerTransactionId;

    /**
     * Commission paid to partner (in kopecks).
     */
    public readonly int $partnerCommission;

    /**
     * Net platform revenue after partner commission.
     */
    public readonly int $netPlatformRevenue;

    /**
     * Timestamp when yield was claimed.
     */
    public readonly DateTimeImmutable $claimedAt;

    /**
     * Correlation ID for distributed tracing.
     */
    public readonly string $correlationId;

    /**
     * Additional metadata for analytics.
     */
    public readonly array $metadata;

    public function __construct(
        string $yieldTransactionId,
        string $userId,
        string $lockedBatchId,
        string $bonusWalletId,
        string $yieldDate,
        int $lockedBalanceStart,
        int $lockedBalanceEnd,
        int $averageLockedBalance,
        float $platformYieldRate,
        float $userYieldRate,
        int $platformYield,
        int $userYield,
        string $yieldSource,
        ?string $partnerName,
        ?string $partnerTransactionId,
        int $partnerCommission,
        ?string $tenantId = null,
        DateTimeImmutable $claimedAt = null,
        string $correlationId,
        array $metadata = []
    ) {
        $this->yieldTransactionId = $yieldTransactionId;
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->lockedBatchId = $lockedBatchId;
        $this->bonusWalletId = $bonusWalletId;
        $this->yieldDate = $yieldDate;
        $this->lockedBalanceStart = $lockedBalanceStart;
        $this->lockedBalanceEnd = $lockedBalanceEnd;
        $this->averageLockedBalance = $averageLockedBalance;
        $this->platformYieldRate = $platformYieldRate;
        $this->userYieldRate = $userYieldRate;
        $this->platformYield = $platformYield;
        $this->userYield = $userYield;
        $this->totalYield = $platformYield + $userYield;
        $this->yieldSource = $yieldSource;
        $this->partnerName = $partnerName;
        $this->partnerTransactionId = $partnerTransactionId;
        $this->partnerCommission = $partnerCommission;
        $this->netPlatformRevenue = $platformYield - $partnerCommission;
        $this->claimedAt = $claimedAt ?? new DateTimeImmutable();
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Gets the platform margin percentage.
     */
    public function getPlatformMarginPercentage(): float
    {
        if ($this->totalYield === 0) {
            return 0.0;
        }
        return ($this->netPlatformRevenue / $this->totalYield) * 100;
    }

    /**
     * Gets the user yield as percentage of total yield.
     */
    public function getUserYieldPercentage(): float
    {
        if ($this->totalYield === 0) {
            return 0.0;
        }
        return ($this->userYield / $this->totalYield) * 100;
    }

    /**
     * Gets the effective annual yield for user.
     */
    public function getUserEffectiveAnnualYield(): float
    {
        return $this->userYieldRate * 365;
    }

    /**
     * Gets the effective annual yield for platform.
     */
    public function getPlatformEffectiveAnnualYield(): float
    {
        return $this->platformYieldRate;
    }

    /**
     * Checks if this yield is from a partner source.
     */
    public function isPartnerSource(): bool
    {
        return $this->yieldSource === 'partner_lending' && $this->partnerName !== null;
    }

    /**
     * Converts event to array for serialization/logging.
     */
    public function toArray(): array
    {
        return [
            'yield_transaction_id' => $this->yieldTransactionId,
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'locked_batch_id' => $this->lockedBatchId,
            'bonus_wallet_id' => $this->bonusWalletId,
            'yield_date' => $this->yieldDate,
            'locked_balance_start' => $this->lockedBalanceStart,
            'locked_balance_end' => $this->lockedBalanceEnd,
            'average_locked_balance' => $this->averageLockedBalance,
            'platform_yield_rate' => $this->platformYieldRate,
            'user_yield_rate' => $this->userYieldRate,
            'platform_yield' => $this->platformYield,
            'user_yield' => $this->userYield,
            'total_yield' => $this->totalYield,
            'yield_source' => $this->yieldSource,
            'partner_name' => $this->partnerName,
            'partner_transaction_id' => $this->partnerTransactionId,
            'partner_commission' => $this->partnerCommission,
            'net_platform_revenue' => $this->netPlatformRevenue,
            'platform_margin_percentage' => $this->getPlatformMarginPercentage(),
            'user_yield_percentage' => $this->getUserYieldPercentage(),
            'user_effective_annual_yield' => $this->getUserEffectiveAnnualYield(),
            'platform_effective_annual_yield' => $this->getPlatformEffectiveAnnualYield(),
            'is_partner_source' => $this->isPartnerSource(),
            'claimed_at' => $this->claimedAt->format('Y-m-d H:i:s'),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }
}
