<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\TenderStatistic;
use App\Services\B2BDocumentService;
use App\Services\PlatformGuaranteeService;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class TenderService
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly FraudControlService $fraudControl,
        private readonly B2BDocumentService $documentService,
        private readonly PlatformGuaranteeService $guaranteeService
    ) {}

    /**
     * Create a new tender for business
     */
    public function createTender(array $data, int $businessId, int $tenantId, int $verticalId): Tender
    {
        // Fraud check
        $this->fraudControl->check($businessId, 'tender_create', 0);

        return $this->withSpan('tender.create', function () use ($data, $businessId, $tenantId, $verticalId) {
            $tender = new Tender([
                'business_id' => $businessId,
                'tenant_id' => $tenantId,
                'vertical_id' => $verticalId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? Tender::TYPE_SUPPLY,
                'min_amount' => $data['min_amount'],
                'duration_months' => $data['duration_months'] ?? null,
                'starts_at' => $data['starts_at'] ?? now(),
                'ends_at' => $data['ends_at'],
                'delivery_start_date' => $data['delivery_start_date'] ?? null,
                'delivery_end_date' => $data['delivery_end_date'] ?? null,
                'status' => Tender::STATUS_DRAFT,
                'requirements' => $data['requirements'] ?? null,
                'delivery_terms' => $data['delivery_terms'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
            ]);

            // Validate tender rules
            $errors = $tender->validateTenderRules();
            if (!empty($errors)) {
                throw new \InvalidArgumentException(implode('. ', $errors));
            }

            $tender->save();

            $this->logCreated('tender', $tender->id, [
                'business_id' => $businessId,
                'tenant_id' => $tenantId,
                'vertical_id' => $verticalId,
                'min_amount' => $tender->min_amount,
                'type' => $tender->type,
            ], $businessId);

            return $tender;
        });
    }

    /**
     * Activate a tender
     */
    public function activateTender(int $tenderId, int $businessId): Tender
    {
        $tender = Tender::where('id', $tenderId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        if ($tender->status !== Tender::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft tenders can be activated');
        }

        $tender->status = Tender::STATUS_ACTIVE;
        $tender->save();

        $this->logAction('tender', $tenderId, 'activated', [], $businessId);

        return $tender;
    }

    /**
     * Close a tender
     */
    public function closeTender(int $tenderId, int $businessId): Tender
    {
        $tender = Tender::where('id', $tenderId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        if ($tender->status !== Tender::STATUS_ACTIVE) {
            throw new \InvalidArgumentException('Only active tenders can be closed');
        }

        return DB::transaction(function () use ($tender, $businessId) {
            $tender->status = Tender::STATUS_CLOSED;
            $tender->save();

            // Release guarantees for all non-winning bids
            $tender->bids()
                ->where('status', TenderBid::STATUS_SUBMITTED)
                ->where('id', '!=', $tender->winning_bid_id)
                ->each(function ($bid) {
                    $bid->releaseGuarantee();
                });

            $this->logAction('tender', $tenderId, 'closed', [], $businessId);

            return $tender;
        });
    }

    /**
     * Select winning bid
     */
    public function selectWinningBid(int $tenderId, int $bidId, int $businessId): Tender
    {
        $tender = Tender::where('id', $tenderId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $bid = $tender->bids()->where('id', $bidId)->firstOrFail();

        if ($bid->status !== TenderBid::STATUS_SUBMITTED) {
            throw new \InvalidArgumentException('Only submitted bids can be selected');
        }, $bidId) {
            // Validate documents are signed (contract, UPD, invoice, act)
            $docValidation = $this->guaranteeService->validateDocumentsSigned($tender->id;
           if (!$docValidation['all_signed']) 
                throw new \InvalidArgumentException(
                    'Cannot select winner. Missing signed documents: ' . implode(', ', $docValidation['missing_docs'])
                );
            }


        return DB::transaction(function () use ($tender, $bid, $businessId) {
            // Mark all other bids as rejected
            $tender->bids()
                ->where('id', '!=', $bidId)
                ->where('status', TenderBid::STATUS_SUBMITTED)
                ->update([
                    'status' => TenderBid::STATUS_REJECTED,
                    'reviewed_at' => now(),
                ]);

            // Mark winning bid as accepted
            $bid->status = TenderBid::STATUS_ACCEPTED;
            $bid->reviewed_at = now();
            $bid->save();

            $tender->winning_bid_id = $bidId;
            $tender->status = Tender::STATUS_COMPLETED;
            $tender->save();

            // Generate completion documents (UPD, Invoice, Act) with 7% commission
            $this->documentService->generateTenderCompletionDocuments(
                $tender->id,
                $bidId,
                $businessId
            );

            // Hold funds if platform guarantee is not used (4 days before delivery)
            if (!$tender->requires_platform_guarantee) {
                $this->guaranteeService->holdCustomerFunds($tender->id, $bidId, $businessId);
            }

            // Update supplier statistics
            $this->updateSupplierStatistics($bid->supplier_id, $tender->vertical_id, $tender->tenant_id, 'won', $bid->bid_amount);

            // Create manager bonus (1.5% of sale amount)
            if ($tender->manager_id) {
                $this->bonusService->createTenderWonBonus(
                    $tender->id,
                    $bidId,
                    $tender->manager_id,
                    $bid->bid_amount,
                    $tender->tenant_id,
                    $tender->vertical_id
                );
            }

            // Release guarantees for rejected bids
            $tender->bids()
                ->where('status', TenderBid::STATUS_REJECTED)
                ->each(function ($rejectedBid) use ($tender) {
                    $rejectedBid->releaseGuarantee();
                    $this->updateSupplierStatistics(
                        $rejectedBid->supplier_id,
                        $tender->vertical_id,
                        $tender->tenant_id,
                        'lost'
                    );
                });

            $this->logAction('tender', $tenderId, 'winning_bid_selected', [
                'bid_id' => $bidId,
                'supplier_id' => $bid->supplier_id,
                'amount' => $bid->bid_amount,
                'commission_percent' => Tender::PLATFORM_COMMISSION_PERCENT,
                'platform_guarantee_used' => $tender->requires_platform_guarantee,
                'funds_held' => !$tender->requires_platform_guarantee,
            ], $businessId);

            return $tender;
        });
    }

    /**
     * Update supplier statistics
     */
    private function updateSupplierStatistics(int $supplierId, int $verticalId, int $tenantId, string $action, ?float $value = null): void
    {
        $statistic = TenderStatistic::firstOrCreate(
            [
                'supplier_id' => $supplierId,
                'vertical_id' => $verticalId,
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

        switch ($action) {
            case 'participated':
                $statistic->incrementParticipated();
                break;
            case 'won':
                $statistic->incrementWon($value ?? 0);
                break;
            case 'lost':
                $statistic->incrementLost();
                break;
            case 'withdrawn':
                $statistic->incrementWithdrawn();
                break;
            case 'rejected':
                $statistic->incrementRejected();
                break;
        }
    }

    /**
     * Get tender with bids
     */
    public function getTenderWithBids(int $tenderId, int $businessId): Tender
    {
        return Tender::where('id', $tenderId)
            ->where('business_id', $businessId)
            ->with(['bids' => function ($query) {
                $query->orderBy('bid_amount', 'asc');
            }])
            ->firstOrFail();
    }

    /**
     * List tenders for business
     */
    public function listTenders(int $businessId, ?string $status = null)
    {
        $query = Tender::where('business_id', $businessId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }
}
    }
}
