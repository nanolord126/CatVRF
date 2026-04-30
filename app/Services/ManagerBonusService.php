<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ManagerBonus;
use App\Models\Tender;
use App\Models\TenderBid;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\DB;

final class ManagerBonusService
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly WalletService $walletService
    ) {}

    /**
     * Create bonus for manager when supplier wins tender
     */
    public function createTenderWonBonus(
        int $tenderId,
        int $bidId,
        int $managerId,
        float $saleAmount,
        int $tenantId,
        int $verticalId
    ): ManagerBonus {
        return $this->createGenericBonus(
            $managerId,
            'tender',
            $tenderId,
            $saleAmount,
            ManagerBonus::SUPPLIER_BONUS_PERCENT,
            $tenantId,
            $verticalId,
            ManagerBonus::TYPE_TENDER_WON,
            $bidId
        );
    }

    /**
     * Create generic bonus for any B2B sale
     */
    public function createGenericBonus(
        int $managerId,
        string $referenceType,
        int $referenceId,
        float $saleAmount,
        float $bonusPercent,
        int $tenantId,
        int $verticalId,
        ?string $type = null,
        ?int $bidId = null
    ): ManagerBonus {
        return $this->withSpan('manager.bonus.create.generic', function () use (
            $managerId,
            $referenceType,
            $referenceId,
            $saleAmount,
            $bonusPercent,
            $tenantId,
            $verticalId,
            $type,
            $bidId
        ) {
            $bonusAmount = $saleAmount * ($bonusPercent / 100);

            $bonusType = $type ?? match ($referenceType) {
                'tender' => ManagerBonus::TYPE_TENDER_WON,
                'b2b_order' => ManagerBonus::TYPE_B2B_SALE,
                'referral' => ManagerBonus::TYPE_REFERRAL,
                default => ManagerBonus::TYPE_B2B_SALE,
            };

            $bonus = new ManagerBonus([
                'manager_id' => $managerId,
                'tender_id' => $referenceType === 'tender' ? $referenceId : null,
                'bid_id' => $bidId,
                'tenant_id' => $tenantId,
                'vertical_id' => $verticalId,
                'type' => $bonusType,
                'sale_amount' => $saleAmount,
                'bonus_percent' => $bonusPercent,
                'bonus_amount' => $bonusAmount,
                'status' => ManagerBonus::STATUS_PENDING,
                'paid_until' => now()->addDays(30),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            $bonus->save();

            return $bonus;
        });
    }

    /**
     * Get manager for B2B order from platform assignment
     */
    public function getManagerForB2BOrder(int $businessId, int $tenantId, ?int $verticalId = null): ?int
    {
        $query = \App\Models\ManagerClientAssignment::active()
            ->where('business_id', $businessId)
            ->where('tenant_id', $tenantId);

        if ($verticalId) {
            $query->where(function ($q) use ($verticalId) {
                $q->where('vertical_id', $verticalId)
                    ->orWhereNull('vertical_id');
            });
        } else {
            $query->whereNull('vertical_id');
        }

        // Prefer primary manager
        $assignment = $query->orderBy('is_primary', 'desc')->first();

        return $assignment?->manager_id;
    }

    /**
     * Approve bonus
     */
    public function approveBonus(int $bonusId, int $approvedBy): ManagerBonus
    {
        return DB::transaction(function () use ($bonusId, $approvedBy) {
            $bonus = ManagerBonus::findOrFail($bonusId);

            if ($bonus->status !== ManagerBonus::STATUS_PENDING) {
                throw new \InvalidArgumentException('Only pending bonuses can be approved');
            }

            $bonus->approve();

            $this->logAction('manager_bonus', $bonusId, 'approved', [], $approvedBy);

            return $bonus;
        });
    }

    /**
     * Pay bonus to manager wallet
     */
    public function payBonus(int $bonusId, int $paidBy): ManagerBonus
    {
        return DB::transaction(function () use ($bonusId, $paidBy) {
            $bonus = ManagerBonus::findOrFail($bonusId);

            if ($bonus->status !== ManagerBonus::STATUS_APPROVED) {
                throw new \InvalidArgumentException('Only approved bonuses can be paid');
            }

            // Deposit to manager wallet
            $this->walletService->deposit(
                $bonus->manager_id,
                $bonus->tenant_id,
                $bonus->bonus_amount,
                "Bonus for tender #{$bonus->tender_id}"
            );

            $bonus->markAsPaid();

            $this->logAction('manager_bonus', $bonusId, 'paid', [
                'amount' => $bonus->bonus_amount,
            ], $paidBy);

            return $bonus;
        });
    }

    /**
     * Cancel bonus
     */
    public function cancelBonus(int $bonusId, string $reason, int $cancelledBy): ManagerBonus
    {
        $bonus = ManagerBonus::findOrFail($bonusId);

        if ($bonus->status === ManagerBonus::STATUS_PAID) {
            throw new \InvalidArgumentException('Paid bonuses cannot be cancelled');
        }

        $bonus->cancel($reason);

        $this->logAction('manager_bonus', $bonusId, 'cancelled', [
            'reason' => $reason,
        ], $cancelledBy);

        return $bonus;
    }

    /**
     * Get manager bonuses
     */
    public function getManagerBonuses(int $managerId, ?string $status = null)
    {
        $query = ManagerBonus::where('manager_id', $managerId)
            ->with(['tender', 'bid'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate(20);
    }

    /**
     * Get manager bonus statistics
     */
    public function getManagerStatistics(int $managerId): array
    {
        $stats = ManagerBonus::where('manager_id', $managerId)
            ->selectRaw('
                COUNT(*) as total_bonuses,
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_bonuses,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved_bonuses,
                COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_bonuses,
                SUM(CASE WHEN status = "paid" THEN bonus_amount ELSE 0 END) as total_paid,
                SUM(bonus_amount) as total_potential,
                AVG(bonus_amount) as avg_bonus_amount
            ')
            ->first();

        return [
            'total_bonuses' => $stats->total_bonuses ?? 0,
            'pending_bonuses' => $stats->pending_bonuses ?? 0,
            'approved_bonuses' => $stats->approved_bonuses ?? 0,
            'paid_bonuses' => $stats->paid_bonuses ?? 0,
            'total_paid' => $stats->total_paid ?? 0,
            'total_potential' => $stats->total_potential ?? 0,
            'avg_bonus_amount' => $stats->avg_bonus_amount ?? 0,
        ];
    }

    /**
     * Get all pending bonuses (for admin)
     */
    public function getPendingBonuses(int $tenantId = null)
    {
        $query = ManagerBonus::pending()
            ->with(['manager', 'tender', 'bid'])
            ->orderBy('created_at', 'asc');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->paginate(50);
    }

    /**
     * Get overdue bonuses
     */
    public function getOverdueBonuses(int $tenantId = null)
    {
        $query = ManagerBonus::overdue()
            ->with(['manager', 'tender', 'bid'])
            ->orderBy('paid_until', 'asc');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * Bulk approve bonuses
     */
    public function bulkApprove(array $bonusIds, int $approvedBy): array
    {
        $results = [];
        
        foreach ($bonusIds as $bonusId) {
            try {
                $bonus = $this->approveBonus($bonusId, $approvedBy);
                $results[] = ['id' => $bonusId, 'success' => true];
            } catch (\Exception $e) {
                $results[] = ['id' => $bonusId, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Bulk pay bonuses
     */
    public function bulkPay(array $bonusIds, int $paidBy): array
    {
        $results = [];
        
        foreach ($bonusIds as $bonusId) {
            try {
                $bonus = $this->payBonus($bonusId, $paidBy);
                $results[] = ['id' => $bonusId, 'success' => true];
            } catch (\Exception $e) {
                $results[] = ['id' => $bonusId, 'success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }
}
