<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tender;
use App\Models\TenderBid;
use App\Models\TenderDocument;
use App\Models\B2BDocument;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\DB;

final class B2BDocumentService
{
    use WithAuditLogging;
    use WithTelemetry;

    /**
     * Generate tender completion documents (UPD, Invoice, Act)
     */
    public function generateTenderCompletionDocuments(int $tenderId, int $bidId, int $userId): array
    {
        return $this->withSpan('b2b.tender.documents.generate', function () use ($tenderId, $bidId, $userId) {
            $tender = Tender::findOrFail($tenderId);
            $bid = $tender->bids()->where('id', $bidId)->firstOrFail();

            if ($bid->status !== TenderBid::STATUS_ACCEPTED) {
                throw new \InvalidArgumentException('Can only generate documents for accepted bids');
            }

            $documents = [];

            // Calculate commission (7% for tenders)
            $commissionCalc = $tender->getTotalWithCommission($bid->bid_amount);

            // Generate UPD (Universal Transfer Document)
            $upd = $this->createTenderDocument(
                $tenderId,
                $bidId,
                TenderDocument::TYPE_UPD,
                $bid->bid_amount,
                $commissionCalc['commission_amount'],
                $commissionCalc['commission_percent']
            );
            $documents['upd'] = $upd;

            // Generate Invoice (Счет-фактура)
            $invoice = $this->createTenderDocument(
                $tenderId,
                $bidId,
                TenderDocument::TYPE_INVOICE,
                $bid->bid_amount,
                $commissionCalc['commission_amount'],
                $commissionCalc['commission_percent']
            );
            $documents['invoice'] = $invoice;

            // Generate Act (Акт выполненных работ)
            $act = $this->createTenderDocument(
                $tenderId,
                $bidId,
                TenderDocument::TYPE_ACT,
                $bid->bid_amount,
                $commissionCalc['commission_amount'],
                $commissionCalc['commission_percent']
            );
            $documents['act'] = $act;

            $this->logAction('tender', $tenderId, 'documents_generated', [
                'bid_id' => $bidId,
                'documents_count' => count($documents),
                'total_amount' => $bid->bid_amount,
                'commission_amount' => $commissionCalc['commission_amount'],
            ], $userId);

            return $documents;
        });
    }

    /**
     * Generate B2B order documents (Contract, UPD, Invoice, Act)
     */
    public function generateB2BOrderDocuments(
        int $orderId,
        int $businessId,
        int $supplierId,
        int $tenantId,
        int $verticalId,
        float $amount,
        int $userId
    ): array {
        return $this->withSpan('b2b.order.documents.generate', function () use (
            $orderId,
            $businessId,
            $supplierId,
            $tenantId,
            $verticalId,
            $amount,
            $userId
        ) {
            $documents = [];

            // Calculate commission (12% for B2B)
            $commission = $amount * (B2BDocument::PLATFORM_COMMISSION_PERCENT / 100);

            // Generate Contract
            $contract = $this->createB2BDocument(
                $orderId,
                $businessId,
                $supplierId,
                $tenantId,
                $verticalId,
                B2BDocument::TYPE_CONTRACT,
                $amount,
                $commission
            );
            $documents['contract'] = $contract;

            // Generate UPD
            $upd = $this->createB2BDocument(
                $orderId,
                $businessId,
                $supplierId,
                $tenantId,
                $verticalId,
                B2BDocument::TYPE_UPD,
                $amount,
                $commission
            );
            $documents['upd'] = $upd;

            // Generate Invoice
            $invoice = $this->createB2BDocument(
                $orderId,
                $businessId,
                $supplierId,
                $tenantId,
                $verticalId,
                B2BDocument::TYPE_INVOICE,
                $amount,
                $commission
            );
            $documents['invoice'] = $invoice;

            // Generate Act
            $act = $this->createB2BDocument(
                $orderId,
                $businessId,
                $supplierId,
                $tenantId,
                $verticalId,
                B2BDocument::TYPE_ACT,
                $amount,
                $commission
            );
            $documents['act'] = $act;

            $this->logAction('b2b_order', $orderId, 'documents_generated', [
                'business_id' => $businessId,
                'supplier_id' => $supplierId,
                'documents_count' => count($documents),
                'total_amount' => $amount,
                'commission_amount' => $commission,
            ], $userId);

            return $documents;
        });
    }

    /**
     * Sign a document
     */
    public function signDocument(string $documentType, int $documentId, int $userId): Model
    {
        return $this->withSpan('b2b.document.sign', function () use ($documentType, $documentId, $userId) {
            if ($documentType === 'tender') {
                $document = TenderDocument::findOrFail($documentId);
            } else {
                $document = B2BDocument::findOrFail($documentId);
            }

            if (!$document->canBeSigned()) {
                throw new \InvalidArgumentException('Document cannot be signed in current status');
            }

            $document->sign($userId);

            $this->logAction($documentType . '_document', $documentId, 'signed', [], $userId);

            return $document;
        });
    }

    /**
     * Create tender document
     */
    private function createTenderDocument(
        int $tenderId,
        int $bidId,
        string $type,
        float $amount,
        float $commissionAmount,
        float $commissionPercent
    ): TenderDocument {
        $document = new TenderDocument([
            'tender_id' => $tenderId,
            'bid_id' => $bidId,
            'type' => $type,
            'number' => '',
            'date' => now(),
            'amount' => $amount,
            'commission_amount' => $commissionAmount,
            'commission_percent' => $commissionPercent,
            'status' => TenderDocument::STATUS_GENERATED,
            'generated_at' => now(),
        ]);

        $document->number = $document->generateNumber();
        $document->save();

        return $document;
    }

    /**
     * Create B2B document
     */
    private function createB2BDocument(
        int $orderId,
        int $businessId,
        int $supplierId,
        int $tenantId,
        int $verticalId,
        string $type,
        float $amount,
        float $commissionAmount
    ): B2BDocument {
        $document = new B2BDocument([
            'order_id' => $orderId,
            'business_id' => $businessId,
            'supplier_id' => $supplierId,
            'tenant_id' => $tenantId,
            'vertical_id' => $verticalId,
            'type' => $type,
            'number' => '',
            'date' => now(),
            'amount' => $amount,
            'commission_amount' => $commissionAmount,
            'commission_percent' => B2BDocument::PLATFORM_COMMISSION_PERCENT,
            'status' => B2BDocument::STATUS_GENERATED,
            'generated_at' => now(),
        ]);

        $document->number = $document->generateNumber();
        $document->save();

        return $document;
    }

    /**
     * Get documents for tender
     */
    public function getTenderDocuments(int $tenderId): array
    {
        return TenderDocument::where('tender_id', $tenderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type')
            ->toArray();
    }

    /**
     * Get documents for B2B order
     */
    public function getB2BOrderDocuments(int $orderId): array
    {
        return B2BDocument::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type')
            ->toArray();
    }
}
