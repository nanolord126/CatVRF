<?php

declare(strict_types=1);

namespace Modules\Supermarket\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use App\Services\FraudControlService;
use Modules\Supermarket\Domain\Exceptions\SupplierPenaltyException;
use Modules\Supermarket\Infrastructure\Models\SupplierPenalty;
use Modules\Supermarket\Domain\Models\Document;
use Modules\Supermarket\Domain\Models\SupplyChainLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SupplierPenaltyService
{
    use WithAuditLogging;
    use WithTelemetry;

    private readonly FraudControlService $fraudControl;

    // Коэффициенты штрафа
    private const float PENALTY_MULTIPLIER = 3.0; // 3x от суммы поставки
    private const float PLATFORM_SHARE_RATIO = 2.0; // 2x платформе
    private const float BUSINESS_SHARE_RATIO = 1.0; // 1x бизнесу

    public function __construct(FraudControlService $fraudControl)
    {
        $this->fraudControl = $fraudControl;
    }

    /**
     * Начислить штраф за просроченные товары
     * 
     * Штраф = 3x от суммы поставки
     * - 2x (66.67%) уходит платформе
     * - 1x (33.33%) уходит бизнесу, которому поставили просрочку
     */
    public function chargePenaltyForExpiredProducts(
        int $supplierId,
        float $supplyAmount,
        ?int $affectedBusinessId = null,
        ?int $documentId = null,
        ?int $supplyChainLinkId = null,
        array $expiredProducts = [],
        ?string $reason = null
    ): SupplierPenalty {
        return $this->withSpan(
            'supplier_penalty.charge',
            function () use (
                $supplierId,
                $supplyAmount,
                $affectedBusinessId,
                $documentId,
                $supplyChainLinkId,
                $expiredProducts,
                $reason
            ) {
                // Fraud check
                $this->fraudControl->check([
                    'operation_type' => 'supplier_penalty_charge',
                    'vertical' => 'supermarket',
                    'supplier_id' => $supplierId,
                    'amount' => $supplyAmount,
                    'correlation_id' => $this->generateCorrelationId(),
                ]);

                if ($supplyAmount <= 0) {
                    throw SupplierPenaltyException::invalidSupplyAmount($supplyAmount);
                }

                // Проверяем, не был ли уже начислен штраф за эту поставку
                $existingPenalty = SupplierPenalty::where('document_id', $documentId)
                    ->where('supplier_id', $supplierId)
                    ->where('status', '!=', SupplierPenalty::STATUS_WAIVED)
                    ->first();

                if ($existingPenalty) {
                    throw SupplierPenaltyException::penaltyAlreadyApplied($documentId ?? 0);
                }

                // Расчет штрафа
                $penaltyAmount = $supplyAmount * self::PENALTY_MULTIPLIER;
                $platformShare = $supplyAmount * self::PLATFORM_SHARE_RATIO;
                $businessShare = $supplyAmount * self::BUSINESS_SHARE_RATIO;

                return DB::transaction(function () use (
                    $supplierId,
                    $affectedBusinessId,
                    $documentId,
                    $supplyChainLinkId,
                    $supplyAmount,
                    $penaltyAmount,
                    $platformShare,
                    $businessShare,
                    $expiredProducts,
                    $reason
                ) {
                    $penalty = SupplierPenalty::create([
                        'uuid' => (string) \Illuminate\Support\Str::uuid(),
                        'supplier_id' => $supplierId,
                        'affected_business_id' => $affectedBusinessId,
                        'document_id' => $documentId,
                        'supply_chain_link_id' => $supplyChainLinkId,
                        'supply_amount' => $supplyAmount,
                        'penalty_amount' => $penaltyAmount,
                        'platform_share' => $platformShare,
                        'business_share' => $businessShare,
                        'status' => SupplierPenalty::STATUS_PENDING,
                        'reason' => $reason ?? 'Expired products detected',
                        'expired_products' => $expiredProducts,
                        'expired_quantity' => count($expiredProducts),
                        'charged_at' => now(),
                    ]);

                    // Audit logging
                    $this->logAction(
                        'supplier_penalty_charged',
                        [
                            'entity_type' => 'SupplierPenalty',
                            'entity_id' => $penalty->id,
                            'supplier_id' => $supplierId,
                            'affected_business_id' => $affectedBusinessId,
                            'supply_amount' => $supplyAmount,
                            'penalty_amount' => $penaltyAmount,
                            'platform_share' => $platformShare,
                            'business_share' => $businessShare,
                        ]
                    );

                    Log::info('Supplier penalty charged', [
                        'penalty_id' => $penalty->id,
                        'supplier_id' => $supplierId,
                        'supply_amount' => $supplyAmount,
                        'penalty_amount' => $penaltyAmount,
                    ]);

                    return $penalty;
                });
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'supplier_penalty_charge',
            ),
        );
    }

    /**
     * Проверить партию на просроченные товары и начислить штраф
     */
    public function checkAndChargeForExpiredBatch(
        SupplyChainLink $supplyChainLink,
        array $expiredProducts
    ): ?SupplierPenalty {
        if (empty($expiredProducts)) {
            return null;
        }

        $totalAmount = collect($expiredProducts)->sum('amount') ?? $supplyChainLink->quantity * $supplyChainLink->unit_price;

        return $this->chargePenaltyForExpiredProducts(
            supplierId: $supplyChainLink->supplier_id,
            supplyAmount: $totalAmount,
            affectedBusinessId: $supplyChainLink->buyer_id,
            documentId: $supplyChainLink->document_id,
            supplyChainLinkId: $supplyChainLink->id,
            expiredProducts: $expiredProducts,
            reason: 'Expired products detected in batch ' . $supplyChainLink->batch_identifier
        );
    }

    /**
     * Отменить штраф
     */
    public function waivePenalty(int $penaltyId, string $reason): bool
    {
        return $this->withSpan(
            'supplier_penalty.waive',
            function () use ($penaltyId, $reason) {
                $penalty = SupplierPenalty::findOrFail($penaltyId);

                if ($penalty->status === SupplierPenalty::STATUS_PAID) {
                    throw new \Exception('Cannot waive a paid penalty');
                }

                $penalty->status = SupplierPenalty::STATUS_WAIVED;
                $penalty->reason = $reason;
                $penalty->save();

                $this->logAction(
                    'supplier_penalty_waived',
                    [
                        'entity_type' => 'SupplierPenalty',
                        'entity_id' => $penalty->id,
                        'reason' => $reason,
                    ]
                );

                Log::info('Supplier penalty waived', [
                    'penalty_id' => $penaltyId,
                    'reason' => $reason,
                ]);

                return true;
            },
            $this->getStandardAttributes(
                vertical: 'supermarket',
                operation: 'supplier_penalty_waive',
            ),
        );
    }

    /**
     * Получить статистику штрафов поставщика
     */
    public function getSupplierPenaltyStats(int $supplierId): array
    {
        $penalties = SupplierPenalty::where('supplier_id', $supplierId);

        return [
            'total_penalties' => $penalties->count(),
            'total_amount_charged' => (float) $penalties->sum('penalty_amount'),
            'total_platform_share' => (float) $penalties->sum('platform_share'),
            'total_business_share' => (float) $penalties->sum('business_share'),
            'pending_penalties' => $penalties->where('status', SupplierPenalty::STATUS_PENDING)->count(),
            'paid_penalties' => $penalties->where('status', SupplierPenalty::STATUS_PAID)->count(),
        ];
    }
}
