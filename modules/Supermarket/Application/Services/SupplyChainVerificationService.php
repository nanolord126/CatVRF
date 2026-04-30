<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use Modules\Supermarket\Domain\Models\Document;
use Modules\Supermarket\Domain\Models\SupplyChainLink;
use Modules\Supermarket\Domain\Models\SupplierTier;
use Modules\Supermarket\Domain\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\WithAuditLogging;

/**
 * SupplyChainVerificationService — Сервис верификации цепочки поставок
 * 
 * Отвечает за:
 * - Проверку длины цепочки (максимум 4-5 звеньев)
 * - Проверку наличия первичного документа от производителя
 * - Блокировку несанкционированных перепродаж
 * - Валидацию переходов между уровнями поставщиков
 * 
 * Допустимые цепочки:
 * 1. Производитель → Оптовик → Оптовик → B2B Покупатель (максимум 4 звена)
 * 2. Производитель → Торговый дом → Оптовик → Оптовик → Потребитель (магазин) (максимум 5 звеньев)
 */
final class SupplyChainVerificationService
{
    use WithAuditLogging;

    public function __construct(
        private readonly \App\Services\AuditService $auditService
    ) {}

    /**
     * Проверить можно ли создать документ для указанной цепочки
     */
    public function canCreateDocument(array $data, int $supplierId): array
    {
        $supplierTier = SupplierTier::find($data['supplier_tier_id'] ?? null);
        
        if (!$supplierTier) {
            return ['valid' => false, 'reason' => 'Supplier tier not specified or invalid'];
        }

        // Если поставщик - производитель, цепочка начинается с него
        if ($supplierTier->isManufacturerLevel()) {
            return ['valid' => true, 'reason' => 'Manufacturer can start new chain'];
        }

        // Для не-производителей проверяем наличие первичного документа
        if ($supplierTier->requires_primary_document && empty($data['primary_document_id'])) {
            return [
                'valid' => false,
                'reason' => 'This tier requires primary document from manufacturer',
            ];
        }

        // Проверяем первичный документ
        if (!empty($data['primary_document_id'])) {
            $primaryDoc = Document::find($data['primary_document_id']);
            if (!$primaryDoc || $primaryDoc->is_resale_blocked) {
                return [
                    'valid' => false,
                    'reason' => 'Primary document not found or blocked',
                ];
            }

            // Проверяем длину цепочки от производителя
            $chainDepth = $this->calculateChainDepth($primaryDoc->batch_number ?? $data['batch_number']);
            $maxDepth = $supplierTier->getMaxChainLength();

            if ($chainDepth >= $maxDepth) {
                return [
                    'valid' => false,
                    'reason' => "Chain depth ({$chainDepth}) exceeds maximum allowed ({$maxDepth})",
                ];
            }
        }

        return ['valid' => true, 'reason' => 'Chain validation passed'];
    }

    /**
     * Создать звено цепочки поставок
     */
    public function createSupplyChainLink(
        Document $document,
        int $buyerId,
        float $quantity,
        ?int $userId = null
    ): SupplyChainLink {
        return DB::transaction(function () use ($document, $buyerId, $quantity, $userId) {
            $supplierTier = $document->supplierTier;
            $buyer = Customer::find($buyerId);
            
            if (!$buyer) {
                throw new \InvalidArgumentException('Buyer not found');
            }

            // Определяем уровень покупателя (по умолчанию - розница)
            $buyerTierId = $buyer->supplier_tier_id ?? SupplierTier::getRetailer()?->id;

            // Рассчитываем позицию в цепочке
            $chainPosition = 1;
            $chainDepth = 0;

            if ($document->primary_document_id) {
                $primaryDoc = Document::find($document->primary_document_id);
                if ($primaryDoc) {
                    $lastLink = SupplyChainLink::where('batch_identifier', $primaryDoc->batch_number)
                        ->orderBy('chain_position', 'desc')
                        ->first();
                    
                    if ($lastLink) {
                        $chainPosition = $lastLink->chain_position + 1;
                        $chainDepth = $lastLink->chain_depth + 1;
                    }
                }
            }

            // Проверяем превышение максимальной глубины
            if ($supplierTier && $chainDepth > $supplierTier->getMaxChainLength()) {
                $document->is_resale_blocked = true;
                $document->block_reason = "Chain depth ({$chainDepth}) exceeds maximum allowed";
                $document->save();
                
                throw new \InvalidArgumentException("Chain depth exceeds maximum allowed");
            }

            // Проверяем валидность перехода между уровнями
            if ($supplierTier && $buyerTierId) {
                $buyerTier = SupplierTier::find($buyerTierId);
                if ($buyerTier && !$supplierTier->canSellTo($buyerTier)) {
                    throw new \InvalidArgumentException("Invalid tier transition: {$supplierTier->code} cannot sell to {$buyerTier->code}");
                }
            }

            // Создаем звено цепочки
            $link = SupplyChainLink::createLink([
                'product_id' => $document->product_id,
                'batch_number' => $document->batch_number,
                'batch_identifier' => $document->batch_number ?? $document->document_number,
                'supplier_id' => $document->seller_id,
                'supplier_tier_id' => $document->supplier_tier_id,
                'buyer_id' => $buyerId,
                'buyer_tier_id' => $buyerTierId,
                'document_id' => $document->id,
                'primary_document_id' => $document->primary_document_id,
                'chain_position' => $chainPosition,
                'chain_depth' => $chainDepth,
                'quantity' => $quantity,
                'is_verified' => false,
                'is_compliant' => true,
            ]);

            // Логируем создание звена
            $this->logAction(
                'supply_chain_link_created',
                [
                    'link_id' => $link->id,
                    'document_id' => $document->id,
                    'supplier_id' => $document->seller_id,
                    'buyer_id' => $buyerId,
                    'chain_position' => $chainPosition,
                    'chain_depth' => $chainDepth,
                ],
                $userId
            );

            return $link;
        });
    }

    /**
     * Рассчитать глубину цепочки для партии
     */
    public function calculateChainDepth(?string $batchIdentifier): int
    {
        if (!$batchIdentifier) {
            return 0;
        }

        return SupplyChainLink::where('batch_identifier', $batchIdentifier)
            ->max('chain_depth') ?? 0;
    }

    /**
     * Проверить полную цепочку на валидность
     */
    public function validateFullChain(string $batchIdentifier): array
    {
        $chain = SupplyChainLink::getFullChain($batchIdentifier);
        
        if (empty($chain)) {
            return ['valid' => true, 'issues' => []];
        }

        $issues = [];
        $maxDepth = 0;

        foreach ($chain as $link) {
            $maxDepth = max($maxDepth, $link['chain_depth']);

            // Проверяем блокировку перепродажи
            if ($link['is_resale_blocked']) {
                $issues[] = [
                    'type' => 'resale_blocked',
                    'link_id' => $link['id'],
                    'reason' => $link['block_reason'] ?? 'Resale blocked',
                ];
            }

            // Проверяем соответствие
            if (!$link['is_compliant']) {
                $issues[] = [
                    'type' => 'non_compliant',
                    'link_id' => $link['id'],
                    'reason' => $link['compliance_notes'] ?? 'Non-compliant',
                ];
            }

            // Проверяем наличие первичного документа
            if ($link['chain_position'] > 1 && !$link['has_primary_document']) {
                $issues[] = [
                    'type' => 'missing_primary_document',
                    'link_id' => $link['id'],
                    'reason' => 'Missing primary document from manufacturer',
                ];
            }
        }

        // Проверяем общую длину цепочки
        if ($maxDepth > 5) {
            $issues[] = [
                'type' => 'chain_too_long',
                'reason' => "Chain depth ({$maxDepth}) exceeds maximum allowed (5)",
            ];
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'chain_depth' => $maxDepth,
            'chain_length' => count($chain),
        ];
    }

    /**
     * Блокировать несанкционированную перепродажу
     */
    public function blockUnauthorizedResale(int $documentId, string $reason, ?int $userId = null): bool
    {
        $document = Document::findOrFail($documentId);
        
        $document->is_resale_blocked = true;
        $document->block_reason = $reason;
        $document->save();

        // Блокируем все связанные звенья цепочки
        if ($document->batch_number) {
            SupplyChainLink::where('batch_identifier', $document->batch_number)
                ->update([
                    'is_resale_blocked' => true,
                    'block_reason' => $reason,
                    'is_compliant' => false,
                ]);
        }

        // Логируем блокировку
        $this->logAction(
            'document_resale_blocked',
            [
                'document_id' => $documentId,
                'reason' => $reason,
            ],
            $userId
        );

        return true;
    }

    /**
     * Разблокировать документ
     */
    public function unblockDocument(int $documentId, ?int $userId = null): bool
    {
        $document = Document::findOrFail($documentId);
        
        $document->is_resale_blocked = false;
        $document->block_reason = null;
        $document->save();

        // Разблокируем связанные звенья цепочки
        if ($document->batch_number) {
            SupplyChainLink::where('batch_identifier', $document->batch_number)
                ->update([
                    'is_resale_blocked' => false,
                    'block_reason' => null,
                    'is_compliant' => true,
                ]);
        }

        // Логируем разблокировку
        $this->logAction(
            'document_unblocked',
            [
                'document_id' => $documentId,
            ],
            $userId
        );

        return true;
    }

    /**
     * Получить статистику по цепочкам поставок
     */
    public function getSupplyChainStats(?int $tenantId = null): array
    {
        $query = SupplyChainLink::query();

        if ($tenantId) {
            $query->whereHas('document', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        $totalLinks = $query->count();
        $blockedLinks = (clone $query)->where('is_resale_blocked', true)->count();
        $nonCompliantLinks = (clone $query)->where('is_compliant', false)->count();
        $verifiedLinks = (clone $query)->where('is_verified', true)->count();

        // Средняя глубина цепочки
        $avgDepth = (clone $query)->avg('chain_depth') ?? 0;

        // Распределение по уровням
        $byTier = (clone $query)
            ->selectRaw('supplier_tier_id, COUNT(*) as count')
            ->groupBy('supplier_tier_id')
            ->get()
            ->pluck('count', 'supplier_tier_id')
            ->toArray();

        return [
            'total_links' => $totalLinks,
            'blocked_links' => $blockedLinks,
            'non_compliant_links' => $nonCompliantLinks,
            'verified_links' => $verifiedLinks,
            'compliance_rate' => $totalLinks > 0 ? (($totalLinks - $nonCompliantLinks) / $totalLinks) * 100 : 100,
            'average_chain_depth' => round($avgDepth, 2),
            'distribution_by_tier' => $byTier,
        ];
    }

    /**
     * Проверить документ на соответствие правилам цепочки
     */
    public function verifyDocumentCompliance(Document $document): array
    {
        $issues = [];

        // Проверяем наличие первичного документа для не-производителей
        if ($document->supplierTier && !$document->supplierTier->isManufacturerLevel()) {
            if ($document->requires_primary_document && !$document->primary_document_id) {
                $issues[] = [
                    'type' => 'missing_primary_document',
                    'message' => 'Primary document from manufacturer is required',
                ];
            }
        }

        // Проверяем блокировку
        if ($document->is_resale_blocked) {
            $issues[] = [
                'type' => 'resale_blocked',
                'message' => $document->block_reason ?? 'Document is blocked from resale',
            ];
        }

        // Проверяем длину цепочки
        if ($document->batch_number) {
            $chainDepth = $this->calculateChainDepth($document->batch_number);
            if ($document->supplierTier && $chainDepth > $document->supplierTier->getMaxChainLength()) {
                $issues[] = [
                    'type' => 'chain_too_long',
                    'message' => "Chain depth ({$chainDepth}) exceeds maximum allowed",
                ];
            }
        }

        return [
            'compliant' => empty($issues),
            'issues' => $issues,
        ];
    }
}
