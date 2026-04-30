<?php

declare(strict_types=1);

namespace Modules\Bonuses\Domain\Entities;

use DateTimeImmutable;
use DomainException;
use Modules\Bonuses\Domain\Events\FloatYieldClaimed;
use Ramsey\Uuid\Uuid;

/**
 * Entity: FloatYield
 *
 * Represents a daily float yield transaction in the CatFloat monetization model.
 * Tracks platform revenue and user yield share from locked bonus balances.
 *
 * Float monetization mechanics:
 * - Platform earns 12-18% annual yield on locked bonus balances
 * - User earns 0.08-0.25% daily yield as micro-rewards
 * - Yield calculated daily at midnight based on average locked balance
 * - Platform revenue = locked_balance × platform_yield_rate / 365
 * - User yield = locked_balance × user_yield_rate / 365
 *
 * Yield rates:
 * - Platform rate: 12% (standard), 15% (high-yield partner), 18% (premium partner)
 * - User rate: 0.08% (standard), 0.15% (gold tier), 0.25% (platinum tier)
 * - Rates configured per partner or campaign
 * - Rates can be adjusted dynamically based on market conditions
 *
 * Yield calculation:
 * - Average daily locked balance = (start_balance + end_balance) / 2
 * - Platform yield = avg_balance × platform_rate / 365
 * - User yield = avg_balance × user_rate / 365
 * - Yield credited to user's bonus wallet (with hold period)
 *
 * Partner integration:
 * - Partners provide liquidity for float operations
 * - Partner earns commission on platform revenue (0.5-2%)
 * - Partner transaction ID for reconciliation
 * - Partner revenue tracked separately
 *
 * User visibility:
 * - Users see daily yield credited to their wallet
 * - Yield displayed as "micro-earnings" in app
 * - Yield history available in wallet view
 * - Yield notifications sent daily
 *
 * Platform revenue:
 * - Float revenue is a significant monetization stream
 * - Estimated: 15% yield on 10M ₽ locked = 1.5M ₽/year
 * - Revenue scales with locked bonus volume
 * - Revenue decreases as bonuses vest
 *
 * Compliance:
 * - All yield transactions logged with correlation ID
 * - Audit trail for financial reporting
 * - Partner reconciliation reports
 * - 152-ФЗ compliant: no PII in external logs
 *
 * @see Modules\Bonuses\Domain\Events\FloatYieldClaimed
 */
final readonly class FloatYield
{
    /**
     * Unique identifier for this yield transaction.
     */
    public string $id;

    /**
     * User ID who owns this yield.
     */
    public string $userId;

    /**
     * Bonus wallet ID.
     */
    public string $bonusWalletId;

    /**
     * Tenant ID for multi-tenancy.
     */
    public ?string $tenantId;

    /**
     * Date of yield calculation (YYYY-MM-DD).
     */
    public string $yieldDate;

    /**
     * Locked balance at start of day (kopecks).
     */
    public int $lockedBalanceStart;

    /**
     * Locked balance at end of day (kopecks).
     */
    public int $lockedBalanceEnd;

    /**
     * Average locked balance for the day (kopecks).
     */
    public int $averageLockedBalance;

    /**
     * Platform annual yield rate (as percentage, e.g., 15.0 for 15%).
     */
    public float $platformYieldRate;

    /**
     * User annual yield rate (as percentage, e.g., 0.15 for 0.15%).
     */
    public float $userYieldRate;

    /**
     * Total platform yield generated (kopecks).
     */
    public int $platformYield;

    /**
     * Total user yield credited (kopecks).
     */
    public int $userYield;

    /**
     * Partner name (if applicable).
     */
    public ?string $partnerName;

    /**
     * Partner transaction ID for reconciliation.
     */
    public ?string $partnerTransactionId;

    /**
     * Partner commission (kopecks).
     */
    public int $partnerCommission;

    /**
     * Net platform revenue after commission (kopecks).
     */
    public int $netPlatformRevenue;

    /**
     * Correlation ID for distributed tracing.
     */
    public string $correlationId;

    /**
     * Additional metadata.
     */
    public array $metadata;

    /**
     * Creates a new float yield transaction.
     */
    public static function create(
        string $userId,
        string $bonusWalletId,
        string $yieldDate,
        int $lockedBalanceStart,
        int $lockedBalanceEnd,
        float $platformYieldRate,
        float $userYieldRate,
        ?string $partnerName = null,
        ?string $partnerTransactionId = null,
        ?string $tenantId = null,
        string $correlationId = null,
        array $metadata = []
    ): self {
        $id = Uuid::uuid4()->toString();
        $correlationId = $correlationId ?? Uuid::uuid4()->toString();

        $averageBalance = (int) (($lockedBalanceStart + $lockedBalanceEnd) / 2);
        $platformYield = (int) (($averageBalance * $platformYieldRate) / 36500);
        $userYield = (int) (($averageBalance * $userYieldRate) / 36500);
        $partnerCommission = 0;
        $netRevenue = $platformYield;

        if ($partnerName !== null) {
            $partnerCommission = (int) ($platformYield * 0.01); // 1% default commission
            $netRevenue = $platformYield - $partnerCommission;
        }

        return new self(
            id: $id,
            userId: $userId,
            bonusWalletId: $bonusWalletId,
            tenantId: $tenantId,
            yieldDate: $yieldDate,
            lockedBalanceStart: $lockedBalanceStart,
            lockedBalanceEnd: $lockedBalanceEnd,
            averageLockedBalance: $averageBalance,
            platformYieldRate: $platformYieldRate,
            userYieldRate: $userYieldRate,
            platformYield: $platformYield,
            userYield: $userYield,
            partnerName: $partnerName,
            partnerTransactionId: $partnerTransactionId,
            partnerCommission: $partnerCommission,
            netPlatformRevenue: $netRevenue,
            correlationId: $correlationId,
            metadata: $metadata
        );
    }

    /**
     * Private constructor.
     */
    private function __construct(
        string $id,
        string $userId,
        string $bonusWalletId,
        ?string $tenantId,
        string $yieldDate,
        int $lockedBalanceStart,
        int $lockedBalanceEnd,
        int $averageLockedBalance,
        float $platformYieldRate,
        float $userYieldRate,
        int $platformYield,
        int $userYield,
        ?string $partnerName,
        ?string $partnerTransactionId,
        int $partnerCommission,
        int $netPlatformRevenue,
        string $correlationId,
        array $metadata
    ) {
        $this->id = $id;
        $this->userId = $userId;
        $this->bonusWalletId = $bonusWalletId;
        $this->tenantId = $tenantId;
        $this->yieldDate = $yieldDate;
        $this->lockedBalanceStart = $lockedBalanceStart;
        $this->lockedBalanceEnd = $lockedBalanceEnd;
        $this->averageLockedBalance = $averageLockedBalance;
        $this->platformYieldRate = $platformYieldRate;
        $this->userYieldRate = $userYieldRate;
        $this->platformYield = $platformYield;
        $this->userYield = $userYield;
        $this->partnerName = $partnerName;
        $this->partnerTransactionId = $partnerTransactionId;
        $this->partnerCommission = $partnerCommission;
        $this->netPlatformRevenue = $netPlatformRevenue;
        $this->correlationId = $correlationId;
        $this->metadata = $metadata;
    }

    /**
     * Reconstructs yield from array data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            userId: $data['user_id'],
            bonusWalletId: $data['bonus_wallet_id'],
            tenantId: $data['tenant_id'] ?? null,
            yieldDate: $data['yield_date'],
            lockedBalanceStart: (int) $data['locked_balance_start'],
            lockedBalanceEnd: (int) $data['locked_balance_end'],
            averageLockedBalance: (int) $data['average_locked_balance'],
            platformYieldRate: (float) $data['platform_yield_rate'],
            userYieldRate: (float) $data['user_yield_rate'],
            platformYield: (int) $data['platform_yield'],
            userYield: (int) $data['user_yield'],
            partnerName: $data['partner_name'] ?? null,
            partnerTransactionId: $data['partner_transaction_id'] ?? null,
            partnerCommission: (int) $data['partner_commission'],
            netPlatformRevenue: (int) $data['net_platform_revenue'],
            correlationId: $data['correlation_id'],
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Note: FloatYieldClaimed event is dispatched at the batch level during vesting calculations.
     * This entity represents the aggregated daily yield transaction for a user.
     */

    /**
     * Gets the total yield (platform + user).
     */
    public function getTotalYield(): int
    {
        return $this->platformYield + $this->userYield;
    }

    /**
     * Gets the yield margin (platform share as percentage).
     */
    public function getYieldMargin(): float
    {
        $total = $this->getTotalYield();
        if ($total === 0) {
            return 0.0;
        }
        return ($this->platformYield / $total) * 100;
    }

    /**
     * Gets the partner commission percentage.
     */
    public function getPartnerCommissionPercentage(): float
    {
        if ($this->platformYield === 0) {
            return 0.0;
        }
        return ($this->partnerCommission / $this->platformYield) * 100;
    }

    /**
     * Checks if this is a partner-backed yield.
     */
    public function hasPartner(): bool
    {
        return $this->partnerName !== null;
    }

    /**
     * Gets the annualized yield for the user.
     */
    public function getAnnualizedUserYield(): float
    {
        return $this->userYieldRate;
    }

    /**
     * Gets the annualized yield for the platform.
     */
    public function getAnnualizedPlatformYield(): float
    {
        return $this->platformYieldRate;
    }

    /**
     * Converts yield to array for serialization.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'bonus_wallet_id' => $this->bonusWalletId,
            'tenant_id' => $this->tenantId,
            'yield_date' => $this->yieldDate,
            'locked_balance_start' => $this->lockedBalanceStart,
            'locked_balance_end' => $this->lockedBalanceEnd,
            'average_locked_balance' => $this->averageLockedBalance,
            'platform_yield_rate' => $this->platformYieldRate,
            'user_yield_rate' => $this->userYieldRate,
            'platform_yield' => $this->platformYield,
            'user_yield' => $this->userYield,
            'total_yield' => $this->getTotalYield(),
            'yield_margin' => $this->getYieldMargin(),
            'partner_name' => $this->partnerName,
            'partner_transaction_id' => $this->partnerTransactionId,
            'partner_commission' => $this->partnerCommission,
            'partner_commission_percentage' => $this->getPartnerCommissionPercentage(),
            'net_platform_revenue' => $this->netPlatformRevenue,
            'has_partner' => $this->hasPartner(),
            'annualized_user_yield' => $this->getAnnualizedUserYield(),
            'annualized_platform_yield' => $this->getAnnualizedPlatformYield(),
            'correlation_id' => $this->correlationId,
            'metadata' => $this->metadata,
        ];
    }
}
