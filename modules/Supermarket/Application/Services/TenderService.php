<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Infrastructure\Models\Tender;
use Modules\Supermarket\Infrastructure\Models\TenderLot;
use Modules\Supermarket\Infrastructure\Models\TenderBid;
use Modules\Supermarket\Domain\Models\SupplierTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class TenderService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    /**
     * Создать тендер (через CRM)
     */
    public function createTender(array $data, ?int $crmLeadId = null): Tender
    {
        return $this->withSpan(
            'tender.create',
            function () use ($data, $crmLeadId) {
                // Fraud check
                $this->fraudControl->check([
                    'operation_type' => 'tender_create',
                    'vertical' => 'supermarket',
                    'creator_id' => $data['creator_id'],
                    'crm_lead_id' => $crmLeadId,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                return DB::transaction(function () use ($data, $crmLeadId) {
                    $tender = Tender::create([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'creator_id' => $data['creator_id'],
                        'crm_lead_id' => $crmLeadId,
                        'type' => $data['type'] ?? Tender::TYPE_PROCUREMENT,
                        'status' => Tender::STATUS_DRAFT,
                        'title' => $data['title'],
                        'description' => $data['description'] ?? null,
                        'estimated_budget' => $data['estimated_budget'] ?? null,
                        'currency' => $data['currency'] ?? 'RUB',
                        'delivery_terms' => $data['delivery_terms'] ?? null,
                        'payment_terms' => $data['payment_terms'] ?? null,
                        'requires_guarantee_letter' => $data['requires_guarantee_letter'] ?? true,
                        'allowed_supplier_tiers' => $data['allowed_supplier_tiers'] ?? null,
                        'restricted_regions' => $data['restricted_regions'] ?? null,
                    ]);

                    // Создаем лоты
                    if (!empty($data['lots'])) {
                        foreach ($data['lots'] as $lotData) {
                            $this->createTenderLot($tender->id, $lotData);
                        }
                    }

                    $this->logAction(
                        'tender_created',
                        [
                            'entity_type' => 'Tender',
                            'entity_id' => $tender->id,
                            'crm_lead_id' => $crmLeadId,
                            'type' => $tender->type,
                        ]
                    );

                    Log::info('Tender created', [
                        'tender_id' => $tender->id,
                        'crm_lead_id' => $crmLeadId,
                    ]);

                    return $tender;
                });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'tender_create',
            ),
        );
    }

    /**
     * Создать лот тендера
     */
    public function createTenderLot(int $tenderId, array $data): TenderLot
    {
        return TenderLot::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'tender_id' => $tenderId,
            'product_id' => $data['product_id'] ?? null,
            'product_name' => $data['product_name'],
            'product_sku' => $data['product_sku'] ?? null,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?? 'pcs',
            'allowed_units' => $data['allowed_units'] ?? ['pcs', 'kg', 'ton', 'pallet'],
            'starting_price' => $data['starting_price'] ?? null,
            'reserve_price' => $data['reserve_price'] ?? null,
            'request_for_price' => $data['request_for_price'] ?? false,
            'specifications' => $data['specifications'] ?? null,
            'brand' => $data['brand'] ?? null,
            'manufacturer' => $data['manufacturer'] ?? null,
            'country_of_origin' => $data['country_of_origin'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'requires_cold_chain' => $data['requires_cold_chain'] ?? false,
            'required_documents' => $data['required_documents'] ?? null,
        ]);
    }

    /**
     * Опубликовать тендер
     */
    public function publishTender(int $tenderId): Tender
    {
        $tender = Tender::findOrFail($tenderId);

        if ($tender->status !== Tender::STATUS_DRAFT) {
            throw new \Exception('Only draft tenders can be published');
        }

        $tender->update([
            'status' => Tender::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->logAction(
            'tender_published',
            [
                'entity_type' => 'Tender',
                'entity_id' => $tender->id,
            ]
        );

        return $tender;
    }

    /**
     * Разместить ставку (предложение от поставщика)
     */
    public function placeBid(int $tenderId, int $tenderLotId, int $supplierId, array $data): TenderBid
    {
        return $this->withSpan(
            'tender.place_bid',
            function () use ($tenderId, $tenderLotId, $supplierId, $data) {
                $tender = Tender::findOrFail($tenderId);

                if (!$tender->isActive()) {
                    throw new \Exception('Tender is not active');
                }

                // Проверяем уровень поставщика
                if (!empty($tender->allowed_supplier_tiers)) {
                    $supplierTier = SupplierTier::where('code', $data['supplier_tier_code'])->first();
                    if (!$supplierTier || !in_array($supplierTier->code, $tender->allowed_supplier_tiers)) {
                        throw new \Exception('Supplier tier not allowed for this tender');
                    }
                }

                // Если требуется гарантийное письмо
                if ($tender->requires_guarantee_letter && empty($data['guarantee_letter_path'])) {
                    throw new \Exception('Guarantee letter is required for this tender');
                }

                return DB::transaction(function () use ($tenderId, $tenderLotId, $supplierId, $data) {
                    $bid = TenderBid::create([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'tender_id' => $tenderId,
                        'tender_lot_id' => $tenderLotId,
                        'supplier_id' => $supplierId,
                        'supplier_tier_id' => $data['supplier_tier_id'] ?? null,
                        'offered_price' => $data['offered_price'],
                        'offered_quantity' => $data['offered_quantity'],
                        'unit' => $data['unit'] ?? 'pcs',
                        'available_from' => $data['available_from'] ?? null,
                        'delivery_date' => $data['delivery_date'] ?? null,
                        'guarantee_letter_path' => $data['guarantee_letter_path'] ?? null,
                        'attached_documents' => $data['attached_documents'] ?? null,
                        'status' => TenderBid::STATUS_SUBMITTED,
                    ]);

                    $this->logAction(
                        'tender_bid_placed',
                        [
                            'entity_type' => 'TenderBid',
                            'entity_id' => $bid->id,
                            'tender_id' => $tenderId,
                            'supplier_id' => $supplierId,
                        ]
                    );

                    Log::info('Tender bid placed', [
                        'bid_id' => $bid->id,
                        'tender_id' => $tenderId,
                        'supplier_id' => $supplierId,
                    ]);

                    return $bid;
                });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'tender_place_bid',
            ),
        );
    }

    /**
     * Принять ставку
     */
    public function acceptBid(int $bidId, float $rating = null, string $comment = null): TenderBid
    {
        $bid = TenderBid::findOrFail($bidId);

        DB::transaction(function () use ($bid, $rating, $comment) {
            // Отклоняем другие ставки на этот лот
            TenderBid::where('tender_lot_id', $bid->tender_lot_id)
                ->where('id', '!=', $bid->id)
                ->where('status', TenderBid::STATUS_SUBMITTED)
                ->update(['status' => TenderBid::STATUS_REJECTED]);

            // Принимаем эту ставку
            $bid->update([
                'status' => TenderBid::STATUS_ACCEPTED,
                'rating' => $rating,
                'review_comment' => $comment,
            ]);

            // Обновляем тендер
            $tender = $bid->tender;
            $tender->update([
                'status' => Tender::STATUS_AWARDED,
                'awarded_supplier_id' => $bid->supplier_id,
                'awarded_at' => now(),
                'final_amount' => $bid->offered_price * $bid->offered_quantity,
            ]);

            $this->logAction(
                'tender_bid_accepted',
                [
                    'entity_type' => 'TenderBid',
                    'entity_id' => $bid->id,
                    'tender_id' => $tender->id,
                    'supplier_id' => $bid->supplier_id,
                ]
            );
        });

        return $bid->fresh();
    }

    /**
     * Отклонить ставку
     */
    public function rejectBid(int $bidId, string $reason): TenderBid
    {
        $bid = TenderBid::findOrFail($bidId);

        $bid->update([
            'status' => TenderBid::STATUS_REJECTED,
            'review_comment' => $reason,
        ]);

        $this->logAction(
            'tender_bid_rejected',
            [
                'entity_type' => 'TenderBid',
                'entity_id' => $bid->id,
                'reason' => $reason,
            ]
        );

        return $bid;
    }

    /**
     * Загрузить гарантийное письмо
     */
    public function uploadGuaranteeLetter(int $tenderId, $file): string
    {
        $path = $file->store('tender-guarantees', 'public');
        
        Tender::findOrFail($tenderId)->update([
            'guarantee_letter_path' => $path,
        ]);

        return $path;
    }
}
