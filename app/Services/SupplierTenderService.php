<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\TenderStatistic;
use App\Models\TenderReview;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\DB;

final class SupplierTenderService
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly WalletService $walletService
    ) {}

    /**
     * Submit a bid for a tender
     */
    public function submitBid(int $tenderId, array $data, int $supplierId, int $tenantId): TenderBid
    {
        // Fraud check
        $this->fraudControl->check($supplierId, 'tender_bid_submit', 0);

        return $this->withSpan('tender.bid.submit', function () use ($tenderId, $data, $supplierId, $tenantId) {
            $tender = Tender::findOrFail($tenderId);

            // Check if tender can be participated
            if (!$tender->canBeParticipated()) {
                throw new \InvalidArgumentException('This tender is not accepting bids');
            }

            // Check if supplier already has a bid
            $existingBid = $tender->bids()
                ->where('supplier_id', $supplierId)
                ->where('status', TenderBid::STATUS_SUBMITTED)
                ->first();

            if ($existingBid) {
                throw new \InvalidArgumentException('You already have an active bid for this tender');
            }

            // Check supplier balance (guarantee requirement)
            $balance = $this->walletService->getBalance($supplierId, $tenantId);
            if ($balance < TenderBid::MIN_SUPPLIER_BALANCE) {
                throw new \InvalidArgumentException(
                    "Minimum balance of " . number_format(TenderBid::MIN_SUPPLIER_BALANCE, 2) . 
                    " RUB required to participate. Current balance: " . number_format($balance, 2) . " RUB"
                );
            }

            return DB::transaction(function () use ($tender, $data, $supplierId, $tenantId) {
                $bid = new TenderBid([
                    'tender_id' => $tenderId,
                    'supplier_id' => $supplierId,
                    'tenant_id' => $tenantId,
                    'bid_amount' => $data['bid_amount'],
                    'proposal' => $data['proposal'] ?? null,
                    'terms' => $data['terms'] ?? null,
                    'status' => TenderBid::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                ]);

                // Freeze guarantee amount (10% of bid amount or minimum 500,000)
                $guaranteeAmount = max($bid->bid_amount * 0.1, TenderBid::MIN_SUPPLIER_BALANCE);
                $bid->freezeGuarantee($guaranteeAmount);

                $bid->save();

                // Update statistics
                $statistic = TenderStatistic::firstOrCreate(
                    [
                        'supplier_id' => $supplierId,
                        'vertical_id' => $tender->vertical_id,
                        'tenant_id' => $tenantId,
                    ],
                    [
                        'total_participated' => 0,
                        'total_won' => 0,
                        'total_lost' => 0,
                        'total_withdrawn' => 0,
                        'total_rejected' => 0,
                        'total_value_won' => 0,
                    ]
                );
                $statistic->incrementParticipated();

                $this->logCreated('tender_bid', $bid->id, [
                    'tender_id' => $tenderId,
                    'supplier_id' => $supplierId,
                    'bid_amount' => $bid->bid_amount,
                    'guarantee_amount' => $guaranteeAmount,
                ], $supplierId);

                return $bid;
            });
        });
    }

    /**
     * Withdraw a bid
     */
    public function withdrawBid(int $bidId, int $supplierId): TenderBid
    {
        $bid = TenderBid::where('id', $bidId)
            ->where('supplier_id', $supplierId)
            ->firstOrFail();

        if (!$bid->canBeWithdrawn()) {
            throw new \InvalidArgumentException('This bid cannot be withdrawn');
        }

        return DB::transaction(function () use ($bid, $supplierId) {
            $bid->status = TenderBid::STATUS_WITHDRAWN;
            $bid->withdrawn_at = now();
            $bid->releaseGuarantee();
            $bid->save();

            // Update statistics
            $statistic = TenderStatistic::where('supplier_id', $supplierId)
                ->where('vertical_id', $bid->tender->vertical_id)
                ->where('tenant_id', $bid->tenant_id)
                ->first();
            
            if ($statistic) {
                $statistic->incrementWithdrawn();
            }

            $this->logAction('tender_bid', $bidId, 'withdrawn', [], $supplierId);

            return $bid;
        });
    }

    /**
     * List available tenders for supplier
     */
    public function listAvailableTenders(int $tenantId, ?int $verticalId = null)
    {
        $query = Tender::where('status', Tender::STATUS_ACTIVE)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());

        if ($verticalId) {
            $query->where('vertical_id', $verticalId);
        }

        return $query->orderBy('ends_at', 'asc')->paginate(20);
    }

    /**
     * List supplier's bids
     */
    public function listMyBids(int $supplierId, ?string $status = null)
    {
        $query = TenderBid::where('supplier_id', $supplierId)
            ->with('tender');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Check if supplier can participate (balance check)
     */
    public function canParticipate(int $supplierId, int $tenantId): array
    {
        $balance = $this->walletService->getBalance($supplierId, $tenantId);
        $canParticipate = $balance >= TenderBid::MIN_SUPPLIER_BALANCE;

        return [
            'can_participate' => $canParticipate,
            'current_balance' => $balance,
            'required_balance' => TenderBid::MIN_SUPPLIER_BALANCE,
            'shortage' => max(0, TenderBid::MIN_SUPPLIER_BALANCE - $balance),
        ];
    }

    /**
     * Get supplier statistics (public, anonymized)
     */
    public function getPublicStatistics(int $supplierId, int $tenantId, int $verticalId): array
    {
        $statistic = TenderStatistic::where('supplier_id', $supplierId)
            ->where('tenant_id', $tenantId)
            ->where('vertical_id', $verticalId)
            ->first();

        if (!$statistic) {
            return [
                'vertical_id' => $verticalId,
                'total_participated' => 0,
                'total_won' => 0,
                'total_lost' => 0,
                'win_rate' => 0,
                'completion_rate' => 0,
                'average_rating' => null,
                'total_reviews' => 0,
            ];
        }

        return $statistic->toPublicArray();
    }

    /**
     * Add review for supplier
     */
    public function addReview(int $tenderId, int $bidId, array $data, int $businessId): TenderReview
    {
        $tender = Tender::where('id', $tenderId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $bid = $tender->bids()->where('id', $bidId)->firstOrFail();

        if ($bid->status !== TenderBid::STATUS_ACCEPTED) {
            throw new \InvalidArgumentException('Can only review accepted bids');
        }

        $review = new TenderReview([
            'tender_id' => $tenderId,
            'bid_id' => $bidId,
            'business_id' => $businessId,
            'supplier_id' => $bid->supplier_id,
            'tenant_id' => $tender->tenant_id,
            'rating' => $data['rating'],
            'review' => $data['review'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);

        if (!$review->validateRating()) {
            throw new \InvalidArgumentException('Rating must be between 1 and 5');
        }

        $review->save();

        // Update supplier statistics with new rating
        $statistic = TenderStatistic::where('supplier_id', $bid->supplier_id)
            ->where('vertical_id', $tender->vertical_id)
            ->where('tenant_id', $tender->tenant_id)
            ->first();

        if ($statistic) {
            $statistic->updateRating($data['rating']);
        }

        $this->logCreated('tender_review', $review->id, [
            'tender_id' => $tenderId,
            'supplier_id' => $bid->supplier_id,
            'rating' => $data['rating'],
        ], $businessId);

        return $review;
    }

    /**
     * Get supplier reviews (public only)
     */
    public function getPublicReviews(int $supplierId, int $tenantId)
    {
        return TenderReview::where('supplier_id', $supplierId)
            ->where('tenant_id', $tenantId)
            ->where('is_public', true)
            ->with(['tender' => function ($query) {
                $query->select('id', 'title', 'vertical_id');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }
}
